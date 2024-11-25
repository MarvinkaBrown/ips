<?php
/**
 * @brief		DatabaseFilters Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		02 Sept 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Fields;
use IPS\DateTime;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Widget;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * LatestArticles Widget
 */
class DatabaseFilters extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'DatabaseFilters';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* Viewing or adding/editing a record */
		if ( Dispatcher::i()->recordId or Request::i()->do == 'form' )
		{
			return '';
		}

		if ( ! Dispatcher::i()->databaseId AND ! Dispatcher::i()->categoryId )
		{
			return '';
		}
		
		try
		{
			$database = Databases::load( Dispatcher::i()->databaseId );
			$database->preLoadWords();
		}
		catch ( OutOfRangeException $e )
		{
			return '';
		}
		
		try
		{
			/* @var Categories $categoryClass */
			$categoryClass = "IPS\cms\Categories" . Dispatcher::i()->databaseId;
			$category = $categoryClass::load( Dispatcher::i()->categoryId );
		}
		catch ( OutOfRangeException $e )
		{
			return '';
		}
		
		/* @var Fields $fieldClass */
		$fieldClass = 'IPS\cms\Fields' . $database->id;
		
		$fields = array();
		$cookie = $category->getFilterCookie();
		$cookieValues = ( $cookie !== NULL ) ? array_combine( array_map( function( $k ) { return "field_" . $k; }, array_keys( $cookie ) ), $cookie ) : array();
		$databaseFields = $fieldClass::roots();
		
		$urlValues = array();
		
		foreach( Request::i() as $k => $v )
		{
			if( mb_strpos( $k, 'content_field_' ) !== FALSE )
			{
				/* YesNo fields come in as _checkbox */
				if ( mb_substr( $k, -9 ) === '_checkbox' )
				{
					$k = mb_substr( $k, 0, -9 );
				}
				
				$fieldId = str_replace( 'content_field_', '', $k );
				
				if ( isset( $databaseFields[ $fieldId ] ) and $databaseFields[ $fieldId ]->type == 'Member' )
				{
					$urlValues[ str_replace( 'content_', '', $k ) ] = is_array( $v ) ? implode( '\n', $v ) : $v;
				}
				else
				{
					$urlValues[ str_replace( 'content_', '', $k ) ] = is_array( $v ) ? implode( ',', $v ) : $v;
				}
			}
		}

		$cookieValues = array_merge( $urlValues, $cookieValues );

		foreach( $databaseFields as $field )
		{
			/* If we pass in what is stored in the database, eg: 1\n20, \IPS\Helpers\Form\Member() actually tries to load via name, not ID */
			if ( $field->type === 'Member' and isset( $cookieValues[ 'field_' . $field->id ] ) )
			{
				$members = array();
				$memberArray = is_array( $cookieValues[ 'field_' . $field->id ] ) ? $cookieValues[ 'field_' . $field->id ] : explode( '\n', $cookieValues[ 'field_' . $field->id ] );
				foreach( $memberArray as $id )
				{
					try
					{
						$members[] = Member::load( $id );
					}
					catch( Exception $e ) { }
				}
				
				$cookieValues[ 'field_' . $field->id ] = $members;
			}
		}
		foreach( $fieldClass::fields( $cookieValues, 'view', $category, $fieldClass::FIELD_SKIP_TITLE_CONTENT | $fieldClass::FIELD_DISPLAY_FILTERS, NULL, FALSE  ) as $id => $field )
		{
			/* If this is a select field, remove "other" from the list */
			if( $field instanceof Form\Select )
			{
				$options = $field->options;
				if( $userSuppliedInput = $options['userSuppliedInput'] )
				{
					unset( $options['options'][ $userSuppliedInput ] );
					$options['userSuppliedInput'] = null;
				}
				$field->options = $options;
			}

			/* Force a unique ID to prevent other areas using this same field htmlID */
			$field->htmlId = $field->name .'_' . md5( uniqid() );
			$fields[ $id ] = $field;
		}
		
		if ( count( $fields ) )
		{
			$form = new Form( 'category_filters', 'update', $category->url() );
			$form->class = 'ipsForm--vertical ipsForm--database-widget-filters'; 
			if ( Request::i()->sortby )
			{
				$form->hiddenValues['sortby']		 = Request::i()->sortby;
				$form->hiddenValues['sortdirection'] = isset( Request::i()->sortdirection ) ? Request::i()->sortdirection : 'desc';
			}
			else
			{
				$form->hiddenValues['sortby']		 = $database->field_sort;
				$form->hiddenValues['sortdirection'] = $database->field_direction;
			}
			
			$form->hiddenValues['record_type'] = 'all';
			$form->hiddenValues['time_frame'] = 'show_all';
			
			foreach( $fields as $id => $field )
			{
				$form->add( $field );
			}
			
			if ( Member::loggedIn()->member_id )
			{
				$iStarted = FALSE;
				if ( isset( $cookie['cms_record_i_started'] ) and $cookie['cms_record_i_started'] )
				{
					$iStarted = TRUE;
				}
				
				/* Form submission takes preference over any previously stored cookie values */
				if ( ( isset( Request::i()->cms_record_i_started ) and Request::i()->cms_record_i_started ) )
				{
					$iStarted = TRUE;
				}

				$form->add( new Checkbox( 'cms_record_i_started', $iStarted, FALSE ) );

				/* we need this language later (and in Widgets\DatabaseFilters which is executed just before output) */
				Member::loggedIn()->language()->words['cms_record_i_started'] = Member::loggedIn()->language()->addToStack( 'cms_record_i_started_sprintf', FALSE, array( 'sprintf' => $database->recordWord() ) );
			}

			$field = new Checkbox( 'cms_widget_filters_remember', $cookie !== null, false );
			$field->label = Member::loggedIn()->language()->addToStack( 'cms_widget_filters_remember_text' );
			$form->add( $field );

			if ( $values = $form->values() )
			{
				$url    = $category->url()->setQueryString( array( 'advanced_search_submitted' => 1 ) );
				$cookie = array();
				$params = array();
				foreach( $values as $k => $v )
				{
					if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
					{
						$id = mb_substr( $k, 14 );
						
						if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof CheckboxSet )
						{
							/* We need to reformat this a little */
							$v = array_combine( $v, $v );
						}
						else if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof YesNo )
						{
							/* The form class looks for {$name}_checkbox to determine the value */
							$k = $k . '_checkbox';
						}
						else if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof DateRange )
						{
							/* We need to reformat this a little */
							$start = ( $v['start'] instanceof DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
							$end   = ( $v['end'] instanceof DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
							$v = array( 'start' => $start, 'end' => $end );
						}
						else if ( isset( $fields[ $id ] ) and $fields[ $id ] instanceof Form\Member )
						{
							$ids = array();
							if ( is_array( $v ) )
							{
								foreach( $v as $member )
								{
									if ( $member instanceof Member )
									{
										$ids[] = $member->member_id;
									}
								}
								
								$v = $ids;
							}
							else if ( $v instanceof Member )
							{
								$v = $v->member_id;
							}
						}
						
						$cookie[ $id ] = $v;
						$params[ $k ] = $v;
					}
					
					if ( ! empty( $values['cms_record_i_started_checkbox'] ) or ! empty( $values['cms_record_i_started'] ) )
					{
						$cookie['cms_record_i_started'] = 1;
						$params['cms_record_i_started'] = 1;
					}
				}
				
				if ( count( $form->hiddenValues ) )
				{
					foreach( $form->hiddenValues as $k => $v )
					{
						if ( $k !== 'csrfKey' )
						{
							if ( !in_array( $k, array( 'sortby', 'sortdirection' ) ) )
							{
								$cookie[ $k ] = $v;
							}
							$params[ $k ] = $v;
						}
					}
				}
				
				if ( $values['cms_widget_filters_remember'] )
				{
					$category->saveFilterCookie( $cookie );
					Output::i()->redirect( $category->url() );
				}
				else
				{
					/* Remove the filter cookie */
					$category->saveFilterCookie( FALSE );
					Output::i()->redirect( $url->setQueryString( $params ) );
				}
			}

			return $this->output( $database, $category, $form );
		}
		else
		{
			return '';
		}
	}
}