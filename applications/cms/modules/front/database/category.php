<?php

/**
 * @brief		[Database] Category Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Content
 * @since		16 April 2014
 */

namespace IPS\cms\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Databases\Controller;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Fields;
use IPS\cms\Pages\Page;
use IPS\cms\Records;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Table\Content;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use const IPS\Helpers\Table\SEARCH_BOOL;
use const IPS\Helpers\Table\SEARCH_CHECKBOX;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NUMERIC_TEXT;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class category extends Controller
{

	/**
	 * Store any active filters for this view
	 *
	 * @var	array
	 */	
	static public array $activeFilters = array();
	
	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage() : void
	{
		$this->view();
	}

	/**
	 * Clear any filters
	 *
	 * @return void
	 */
	public function clearFilters() : void
	{
		Session::i()->csrfCheck();

		/* @var Categories $catClass */
		$catClass = 'IPS\cms\Categories' .  Dispatcher::i()->databaseId;

		try
		{
			$category = $catClass::loadAndCheckPerms( Dispatcher::i()->categoryId );
			$category->saveFilterCookie( FALSE );

			Output::i()->redirect( $category->url(), 'cms_filters_cleared' );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2T254/1', 403, '' );
		}
	}

	/**
	 * Display a category. Please.
	 *
	 * @return	void
	 */
	public function view() : void
	{
		/* @var Categories $catClass
		 * @var Fields $fieldClass */
		$category     = NULL;
		$fieldClass   = 'IPS\cms\Fields' .  Dispatcher::i()->databaseId;
		$catClass     = 'IPS\cms\Categories' .  Dispatcher::i()->databaseId;
		$database     = Databases::load( Dispatcher::i()->databaseId );
		$breadcrumbs  = NULL;

		try
		{
			$category = $catClass::loadAndCheckPerms( Dispatcher::i()->categoryId );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2T254/2', 403, '' );
		}

		$customFields = $fieldClass::data( 'view', $category, $fieldClass::FIELD_SKIP_TITLE_CONTENT );

		if ( ! $database->use_categories )
		{
			$breadcrumbs = Output::i()->breadcrumb;
		}
		
		$recordsClass = $category::$contentItemClass;
		
		/* AdvancedSearch can wipe out the checked box as it is looking for _checkbox. Note to self in 4.5, rewrite the filter widget to avoid using advancedSearch :/ */
		if ( ! isset( Request::i()->cms_record_i_started_checkbox ) and isset( Request::i()->cms_record_i_started ) and Request::i()->cms_record_i_started )
		{
			Request::i()->cms_record_i_started_checkbox = Request::i()->cms_record_i_started;
		}
		
		/* Check cookie */
		$where = array();
		$cookie = $category->getFilterCookie();

		if ( $cookie !== NULL )
		{
			foreach( $cookie as $f => $v )
			{
				if( $f == 'cms_record_i_started' and Member::loggedIn()->member_id )
				{
					/* FilterMessage template only accepts \IPS\cms\Fields */
					$field = new Fields;
					$field->id = 'cms_record_i_started';

					Member::loggedIn()->language()->words['content_field_cms_record_i_started'] = Member::loggedIn()->language()->addToStack( 'cms_record_i_started_sprintf', FALSE, array( 'sprintf' => $database->recordWord() ) );

					static::$activeFilters['cms_record_i_started'] = array( 'field' => $field, 'value' => Member::loggedIn()->language()->addToStack('content_field_cms_record_i_started_on') );
				}
				else
				{
					$displayValue = $customFields[ $f ]->displayValue( $v );

					if ( $customFields[ $f ]->type === 'Member' )
					{
						if ( ! empty( $v ) )
						{
							$newDisplayValue = array();
							$concat = '\n';

							if ( is_array( $v ) )
							{
								foreach( $v as $m )
								{
									$member = Member::load( $m );
									$newDisplayValue[] = $member->name;
								}
							}
							else
							{
								$member = Member::load( $v );
								$newDisplayValue[] = $member->name;
							}

							$displayValue = implode( ', ', $newDisplayValue );
						}
					}

					static::$activeFilters[ $f ] = array( 'field' => $customFields[ $f ], 'value' => $displayValue );
				}
			}

			$where = $category->buildWhereFromCookie( $cookie );
		}
		else
		{
			foreach( $customFields as $field )
			{
				if ( $field->type === 'Member' )
				{
					$requestField = 'content_field_' . $field->id;
					$didWeJustHitSubmit = $requestField . '_original';
					$names = NULL;
					
					/* We need to homogenize data, we store IDs, but the Form helper needs objects or names */ 
					if ( isset( Request::i()->$requestField ) and ! isset( Request::i()->$didWeJustHitSubmit ) )
					{
						/* The URL alwyas uses IDs, so map these to names */
						$names = array_map( function( $id )
						{
							return Member::load( $id )->name;
						}, ( is_array( Request::i()->$requestField ) ? Request::i()->$requestField : array( Request::i()->$requestField ) ) );
					}
					else if ( isset( Request::i()->$requestField ) )
					{
						/* The names are foo\nbar so already names */
						$names = explode( "\n", Request::i()->$requestField );
					}
				
					if ( $names !== NULL )
					{
						Request::i()->$requestField = $names;
					}
				}
			}
		}

		/* Set the meta image */
		if( $category->image )
		{
			try
			{
				Output::i()->metaTags['og:image'] = File::get( 'cms_Categories', $category->image )->url;
			}
			catch( OutOfRangeException ){}
		}

		if ( ( $category->hasChildren() AND $category->show_records ) OR ! $category->hasChildren() )
		{
			if ( ! count( $where ) )
			{
				$where = NULL;
			}

			if( Request::i()->advanced_search_submitted )
			{
				Request::i()->csrfKey = Session::i()->csrfKey;
			}

			/* @var Records $recordsClass */
			$table = new Content( 'IPS\cms\Records' . Dispatcher::i()->databaseId, $category->url(), $where, $category, NULL, 'read', !isset(Request::i()->rss));
			$table->tableTemplate = array( \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' ), 'categoryTable' );
			$table->rowsTemplate = array( \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' ), 'recordRow' );
			$table->baseUrl = $table->baseUrl->setQueryString( 'd', Dispatcher::i()->databaseId );

			/* Does the category have a custom layout? */
			if( $category->template_listing == 0 )
			{
				$table->extra = [ 'layout' => $database->display_settings['listing']['layout'] ];
			}
			else
			{
				if( $displaySettings = $category->display_settings )
				{
					$table->extra = [ 'layout' => $displaySettings['layout'] ];
				}
				else
				{
					$table->extra = [ 'layout' => 'table' ];
				}
			}

			$table->hover = TRUE;
			$table->sortBy		  = ( isset( Request::i()->sortby ) ) ? Request::i()->sortby  : (  $database->field_sort ?  $recordsClass::$databaseTable . '.' . $recordsClass::$databasePrefix . $database->field_sort : 'record_last_comment' );
			$table->sortDirection = ( isset( Request::i()->sortdirection ) ) ? Request::i()->sortdirection : ( $database->field_direction ? $database->field_direction : 'desc' );
			if ( $database->field_sort )
			{
				$table->defaultSortBy = $recordsClass::$databasePrefix . $database->field_sort;
				$table->defaultSortDirection = $database->field_direction;
			}
			$table->limit		  = $database->field_perpage   ? $database->field_perpage   : 25;
			$table->title = Member::loggedIn()->language()->addToStack( $database->use_categories ? 'x_records_in_this_category' : 'x_records' , FALSE, array( 'sprintf' => array( $category->_items, $database->recordWord( $category->_items ) ) ) );

			/* Set up sort fields to allow sorting numerically or by date */
			$sortFields = $customFields;
			$sortFields[ $database->field_title ] = $fieldClass::load( $database->field_title );
			foreach( $sortFields as $id => $obj )
			{
				if ( $table->sortBy == $recordsClass::$databaseTable . '.' . 'field_' . $id OR $table->sortBy == 'field_' . $id )
				{
					if ( in_array( $obj->type, array( 'Number', 'Date' ) ) )
					{
						$fieldName = $table->sortBy;
						if ( mb_strstr( $table->sortBy, '.' ) )
						{
							[ $db, $fieldName ] = explode( '.', $table->sortBy );
						}

						$table->sortOptions[ $fieldName ] = 'CAST(`field_' . $id . '` AS UNSIGNED)';
						break;
					}
				}
			}

			/* Make sure table doesn't add breadcrumbs if we're not using categories */
			if ( ! $database->use_categories )
			{
				Output::i()->breadcrumb = $breadcrumbs;
			}

			/* Custom Search */
			$filterOptions = array(
					'all'			=> 'content_all_records',
					'open'			=> 'content_open_records',
					'locked'		=> 'content_locked_records',
			);
			$timeFrameOptions = array(
					'show_all'			=> 'show_all',
					'today'				=> 'today',
					'last_5_days'		=> 'last_5_days',
					'last_7_days'		=> 'last_7_days',
					'last_10_days'		=> 'last_10_days',
					'last_15_days'		=> 'last_15_days',
					'last_20_days'		=> 'last_20_days',
					'last_25_days'		=> 'last_25_days',
					'last_30_days'		=> 'last_30_days',
					'last_60_days'		=> 'last_60_days',
					'last_90_days'		=> 'last_90_days',
			);

			if ( Member::loggedIn()->member_id AND Member::loggedIn()->last_visit )
			{
				$timeFrameOptions['since_last_visit'] = Member::loggedIn()->language()->addToStack('since_last_visit', FALSE, array( 'sprintf' => array( DateTime::ts( (int) Member::loggedIn()->last_visit ) ) ) );
			}

			$sortBy = array(
				'record_updated'	=> 'content_record_last_updated',
				'record_comments'		=> 'content_record_comments',
				'record_views'			=> 'content_record_views',
				'field_' . $database->field_title	=> 'content_record_title',
				'record_publish_date'	=> 'content_record_publish_date'
			);
			
			/* Ensure we have all sort options available */
			$table->sortOptions = array_unique( array_merge( $table->sortOptions, array_combine( array_keys( $sortBy ), array_keys( $sortBy ) ) ) );
			
			/* To avoid confusion, label 'updated' as 'Recently Updated' as last comment */
			Member::loggedIn()->language()->words[ $table->langPrefix . 'sort_updated' ] = Member::loggedIn()->language()->addToStack('content_record_last_comment');

			if ( !isset( $sortBy[ $database->field_sort ] ) )
			{
				switch ( $database->field_sort )
				{
					case 'primary_id_field':
						$sortBy[ $database->field_sort ] = 'database_field__id';
						$table->sortOptions['database_field__id'] = $database->field_sort;
						Member::loggedIn()->language()->words['sort_database_field__id'] = Member::loggedIn()->language()->addToStack('database_field__id');
						break;
					case 'member_id':
						$sortBy[ $database->field_sort ] = 'database_field__member';
						$table->sortOptions['database_field__member'] = $database->field_sort;
						Member::loggedIn()->language()->words['sort_database_field__member'] = Member::loggedIn()->language()->addToStack('database_field__member');
						break;
					case 'record_rating':
						$sortBy[ $database->field_sort ] = 'database_field__rating';
						$table->sortOptions['rating'] = $database->field_sort;
						Member::loggedIn()->language()->words['sort_database_field__rating'] = Member::loggedIn()->language()->addToStack('database_field__rating');
						break;
				}
			}
			
			if ( !$database->options['comments'] )
			{
				unset ( $sortBy['record_last_comment'] );
				unset ( $sortBy['record_comments'] );
				unset ( $table->sortOptions['record_last_comment'] );
				unset ( $table->sortOptions['record_comments'] );
				unset ( $table->sortOptions['last_comment'] );
				unset ( $table->sortOptions['num_comments'] );
			}

			if ( !$database->options['reviews'] and !$category->allow_rating )
			{
				unset ( $table->sortOptions['num_reviews'] );
				unset( $table->sortOptions['rating'] );
			}

			/* If the sort field isn't one of the above, best add it */
			if ( mb_substr( $database->field_sort, 0, 6 ) === 'field_' )
			{
				if ( $database->field_title !== mb_substr( $database->field_sort, 6 ) )
				{
					$sortBy[ $database->field_sort ] = Member::loggedIn()->language()->addToStack( 'content_field_' . mb_substr( $database->field_sort, 6 ) );
					$table->sortOptions[ $database->field_sort ] = $database->field_sort;
				}
			}

			$table->advancedSearch = array(
				'record_type'	 => array( SEARCH_SELECT, array( 'options' => $filterOptions ) ),
				'sort_by'		 => array( SEARCH_SELECT, array( 'options' => $sortBy ) ),
				'sort_direction' => array( SEARCH_SELECT, array( 'options' => array(
					'asc'			=> 'asc',
					'desc'			=> 'desc',
				) )
				),
				'time_frame'	=> array( SEARCH_SELECT, array( 'options' => $timeFrameOptions ) ),
				'cms_record_i_started' => array( SEARCH_CHECKBOX, array() ),
			);

			foreach( $customFields as $obj )
			{
				if ( $obj->filter )
				{
					Member::loggedIn()->language()->words['content_field_' . $obj->id ] = $obj->_title;
					if ( in_array( $obj->type, array( 'Date', 'DateRange' ) ) )
					{
						$table->advancedSearch[ 'content_field_' . $obj->id ] = array( SEARCH_DATE_RANGE, array( 'noDefault' => true ) );
					}
					else if ( $obj->type == 'Number' )
					{
						$table->advancedSearch[ 'content_field_' . $obj->id ] = array( SEARCH_NUMERIC_TEXT, array( 'noDefault' => true ) );
					}
					else if ( $obj->type == 'YesNo' )
					{
						$table->advancedSearch[ 'content_field_' . $obj->id ] = array( SEARCH_BOOL, array( 'noDefault' => true ) );
					}
					else if ( $obj->type == 'Member' )
					{
						/* Don't show this on the custom modal form because you cannot have two autocompletes with the same name on the same page, and the fix is more invasive than the value of this feature on the modal.
						   The purpose of this is to show in the sidebar filter form */
						if ( ! isset( Request::i()->advancedSearchForm ) )
						{
							$table->advancedSearch[ 'content_field_' . $obj->id ] = array( SEARCH_MEMBER, array( 'noDefault' => true, 'multiple' => NULL ) );
						}
					}
					else
					{
						$table->advancedSearch[ 'content_field_' . $obj->id ] = array( SEARCH_SELECT, array( 'options' => $obj->extra, 'multiple' => TRUE, 'noDefault' => true ) );
					}
					
					$table->advancedSearch['sort_by'][1]['options']['field_' . $obj->id ] = 'content_field_' . $obj->id;
				}

				if ( in_array( $obj->type, array( 'Date', 'DateRange', 'Number' ) ) )
				{
					$table->sortOptions[ 'field_' . $obj->id ] = 'CAST(`field_' . $obj->id . '` AS UNSIGNED)';
				}
				else
				{
					$table->sortOptions[ 'field_' . $obj->id ] = $table->sortOptions['field_' . $obj->id] ?? 'field_' . $obj->id;
				}
			}

			$table->advancedSearchCallback = function( $table, $values ) use ( $database, $sortBy, $customFields )
			{
				/* Type */
				foreach( $values as $k => $v )
				{
					if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
					{
						$key =  mb_substr( $k, 14 );
						$concat = ',';
						$displayValue = $customFields[ $key ]->displayValue( $v );
						 
						if ( $customFields[ $key ]->type === 'Member' )
						{
							if ( is_array( $v ) and count( $v ) )
							{
								$newValue = array();
								$newDisplayValue = array();
								$concat = '\n';
								
								foreach( $v as $member )
								{
									if ( $member instanceof Member )
									{
										$newDisplayValue[] = $member->name;
										$newValue[] = $member->member_id;
									}
								}
		
								$displayValue = implode( ', ', $newDisplayValue );
								$v = $newValue;
							}
							else
							{
								/* The content_field_x can be an empty array from the member form */
								continue;
							}
						}
						
						if ( is_array( $v ) )
						{
							if ( array_key_exists( 'start', $v ) or array_key_exists( 'end', $v ) )
							{
								$start = ( $v['start'] instanceof DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
								$end   = ( $v['end'] instanceof DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
								
								if ( $start or $end )
								{
									$table->where[] = array( '( ' . mb_substr( $k, 8 ) . ' BETWEEN ' . $start . ' AND ' . $end . ' )' );
								}
							}
							else
							{
								$like = array();
								foreach( $v as $val )
								{
									if ( $val === 0 or ! empty( $val ) )
									{
										$like[] = $val;
									}
								}

								if( $customFields[ $key ]->default_value and in_array( $customFields[ $key ]->default_value, $v ) )
								{
									$table->where[] = array( "( " . mb_substr( $k, 8 ) . " IS NULL OR " . Db::i()->findInSet( mb_substr( $k, 8 ), $like ) . ")" );
								}
								else
								{
									$table->where[] = array( Db::i()->findInSet( mb_substr( $k, 8 ), $like ) );
								}
							}
						}
						else
						{
							if ( $v !== '___any___' )
							{ 
								if ( is_bool( $v ) )
								{
									/* YesNo fields are false or true */
									if ( $v === false )
									{
										$table->where[] = array( '(' . mb_substr( $k, 8 ) . ' IS NULL or ' . mb_substr( $k, 8 ) . '=0)' );
									}
									else
									{
										$table->where[] = array( mb_substr( $k, 8 ) . "=1" );
									}
								}
								else
								{
									if ( $v !== 0 and ! $v )
									{
										$table->where[] = array( mb_substr( $k, 8 ) . " IS NULL" );
									}
									else
									{
										$table->where[] = array( mb_substr( $k, 8 ) . "=?", $v );
									}
								}
							}
						}

						category::$activeFilters[ $key ] = array( 'field' => $customFields[ $key ], 'value' => $displayValue );
					}
				}
				
				if ( isset( $values['cms_record_i_started'] ) and $values['cms_record_i_started'] and Member::loggedIn()->member_id )
				{ 
					$table->where[] = 'cms_custom_database_' . $database->id . '.member_id=' . Member::loggedIn()->member_id;
				}

				if ( isset( $values['record_type'] ) )
				{
					switch ( $values['record_type'] )
					{
						case 'open':
							$table->where[] = 'record_locked=0';
							break;
						case 'locked':
							$table->where[] = 'record_locked=1';
							break;
					}
				}

				/* Sort */
				if ( isset( $values['sort_by'] ) and isset( $sortBy[ $values['sort_by'] ] ) )
				{
					if ( isset( $customFields[ mb_substr( $values['sort_by'], 6 ) ] ) and $customFields[ mb_substr( $values['sort_by'], 6 ) ]->type == 'Number' )
					{
						$table->sortOptions[ $values['sort_by'] ] = 'LENGTH(`' . $values['sort_by'] . '`) ' . $table->sortDirection . ',`' . $values['sort_by'] . '`';
					}
					elseif( isset( $customFields[ mb_substr( $values['sort_by'], 6 ) ] ) and $customFields[ mb_substr( $values['sort_by'], 6 ) ]->type == 'Date' )
					{
						$table->sortOptions[ $values['sort_by'] ] = 'CAST(`' . $values['sort_by'] . '` AS UNSIGNED)';
					}
					else
					{
						$table->sortOptions[ $values['sort_by'] ] = $values['sort_by'];
					}
					
					/* Ensure we remove any duplicates added by the advanced sort form */
					$table->sortOptions = array_unique( $table->sortOptions );

					$table->sortBy = $values['sort_by'];
					$table->sortDirection = $values['sort_direction'];
				}

				/* Cutoff */
				$days = NULL;
				if ( isset( $values['time_frame'] ) )
				{
					switch ( $values['time_frame'] )
					{
						case 'today':
							$days = 1;
							break;
						case 'last_5_days':
							$days = 5;
							break;
						case 'last_7_days':
							$days = 7;
							break;
						case 'last_10_days':
							$days = 10;
							break;
						case 'last_15_days':
							$days = 15;
							break;
						case 'last_20_days':
							$days = 20;
							break;
						case 'last_25_days':
							$days = 25;
							break;
						case 'last_30_days':
							$days = 30;
							break;
						case 'last_60_days':
							$days = 60;
							break;
						case 'last_90_days':
							$days = 90;
							break;
						case 'since_last_visit':
							$table->where[] = array( 'record_last_comment>?', Member::loggedIn()->last_visit );
							break;
					}
					if ( $days !== NULL )
					{
						$table->where[] = array( 'record_last_comment>?', DateTime::create()->sub( new DateInterval( 'P' . $days . 'D' ) )->getTimestamp() );
					}
				}
			};

			/* RSS */
			if ( $database->rss )
			{
				$rssUrl  = $table->baseUrl->setQueryString('rss', 1 );
				$rssName = $database->_title . ': ' . $category->metaTitle();
				Output::i()->rssFeeds[ $rssName ] = $rssUrl;
				
				/* Show RSS feed */
				if ( isset( Request::i()->rss ) )
				{
					$rssName = Member::loggedIn()->language()->get('content_db_' . $database->id ) . ': ' . $category->metaTitle();
					$document     = Rss::newDocument( $table->baseUrl, $rssName, $rssName );
					$contentField = 'field_' . $database->field_content;
					
					foreach ( $table->getRows( array() ) as $record )
					{
						if ( ! $record->hidden() )
						{
							$content = $record->$contentField;
							
							if ( $record->record_image )
							{
								$content = \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' )->rssItemWithImage( $content, $record->record_image );
							}

							$document->addItem( $record->_title, $record->url(), $content, DateTime::ts( ( $record->record_last_comment > $record->record_publish_date ) ? $record->record_publish_date : $record->record_last_comment ), $record->_id );
						}
					}
			
					/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
					Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
				}
			}
		}
		else
		{
			Output::i()->metaTags['title'] = $category->metaTitle();
			Output::i()->metaTags['description'] = $category->metaDescription();
			Output::i()->metaTags['og:title'] = $category->metaTitle();
			Output::i()->metaTags['og:description'] = $category->metaDescription();
			Output::i()->linkTags['canonical'] = (string) $category->url();

			/* Set breadcrumb */
			if ( $club = $category->_club )
			{
				$club->setBreadcrumbs( $category );
			}
			else
			{
				foreach ( $category->parents() as $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}

				Output::i()->breadcrumb[] = array( NULL, $category->_title );
			}
		}

		/* Node handler does not support keywords, so we need to do it manually */
		if ( $category->meta_keywords )
		{
			Output::i()->metaTags['keywords'] = $category->meta_keywords;
		}

		/* Show club header, if applicable */
		$this->showClubHeader( $category );

		/* Update location */
		$permissions = $category->permissions();
		Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_cms_viewing_db_cat', array( 'content_db_' . $database->id => TRUE, 'content_cat_name_' . $category->id => TRUE ) );

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'records/list.css', 'cms', 'front' ) );

		$stringTable = ( ( $category->hasChildren() AND $category->show_records ) OR ! $category->hasChildren() ) ? (string) $table : '';
		
		Dispatcher::i()->output .= \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' )->categoryHeader( $category, $stringTable, static::$activeFilters );
		
		Dispatcher::i()->output .= $stringTable;

		Dispatcher::i()->output .= \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' )->categoryFooter( $category, $stringTable, static::$activeFilters );
		
		/* Set default search */
		if ( ! $database->search )
		{
			Output::i()->defaultSearchOption = array( 'all', 'search_everything' );
		}
		else
		{
			$type = mb_strtolower( str_replace( '\\', '_', mb_substr( $recordsClass, 4 ) ) );
			Output::i()->defaultSearchOption = array( $type, "{$type}_pl" );
		}

		$titleSuffix = $database->use_categories ? $category->_title . ' - ' . $category->pageTitle() : $category->pageTitle();
		
		if( ( $category->hasChildren() AND $category->show_records ) OR ! $category->hasChildren() )
		{
			Output::i()->title = ( $table->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $titleSuffix, $table->page ) ) ) : $titleSuffix;
		}
		else
		{
			Output::i()->title = $titleSuffix;
		}
	}
	
	/**
	 * Form
	 *
	 * @return	void
	 */
	public function form() : void
	{
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_records.js', 'cms' ) );

		/* @var Categories $categoryClass
		 * @var Records $recordClass
		 * @var Fields $fieldsClass
		 */
		$database		= Databases::load( Dispatcher::i()->databaseId );
		$recordClass	= '\IPS\cms\Records' . Dispatcher::i()->databaseId;
		$categoryClass	= '\IPS\cms\Categories' . Dispatcher::i()->databaseId;
		$category		= $categoryClass::loadAndCheckPerms( Dispatcher::i()->categoryId );
		$fieldsClass	= '\IPS\cms\Fields' . Dispatcher::i()->databaseId;
		$title			= Member::loggedIn()->language()->addToStack( 'content_record_form_new_record', FALSE, array( 'sprintf' => array( $database->recordWord( 1, TRUE ) ) ) );

		$form = $recordClass::create( $category );
		$form->class = 'ipsForm--vertical ipsForm--database-category';
	
		$hasModOptions = FALSE;
		
		$canHide = ( Member::loggedIn()->group['g_hide_own_posts'] == '1' or in_array( 'IPS\cms\Records' . Dispatcher::i()->databaseId, explode( ',', Member::loggedIn()->group['g_hide_own_posts'] ) ) );
		if ( $recordClass::modPermission( 'lock', NULL, $category ) or
			 $recordClass::modPermission( 'pin', NULL, $category ) or
			 $canHide or
			 $recordClass::modPermission( 'feature', NULL, $category ) or
			 $fieldsClass::fixedFieldFormShow( 'record_allow_comments' ) or
			 $fieldsClass::fixedFieldFormShow( 'record_expiry_date' ) or
			 $fieldsClass::fixedFieldFormShow( 'record_comment_cutoff' ) or
			 Member::loggedIn()->modPermission('can_content_edit_meta_tags') )
		{
			$hasModOptions = TRUE;
		}

		$this->showClubHeader( $category );
		Output::i()->allowDefaultWidgets = FALSE;
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->output = $form->customTemplate( array( \IPS\cms\Theme::i()->getTemplate( $database->template_form, 'cms', 'database' ), 'recordForm' ), NULL, $category, $database, Page::$currentPage, $title, $hasModOptions );
		Dispatcher::i()->output .= Output::i()->output;
		Output::i()->title = Member::loggedIn()->language()->addToStack( $title );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'records/form.css', 'cms', 'front' ) );

		try
		{
			if ( $database->use_categories )
			{
				foreach( $category->parents() AS $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
		}
		catch( Exception $e ) {}
	
		Output::i()->breadcrumb[] = array( NULL, $title );
	}
	
	/**
	 * Mark Read
	 *
	 * @return	void
	 */
	protected function markRead() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			/* @var Categories $meowBreed */
			$meowBreed = '\IPS\cms\Categories' . Dispatcher::i()->databaseId;
			$meow      = $meowBreed::load( Dispatcher::i()->categoryId );
			Records::markContainerRead( $meow );
			Output::i()->redirect( $meow->url() );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'module_no_permission', '2T254/3', 403, '' );
		}
	}

	/**
	 * Show Club header
	 *
	 * @param Categories $category
	 * @return void
	 */
	protected function showClubHeader( Categories $category ) : void
	{
		if ( $club = $category->_club )
		{
			Output::i()->sidebar['contextual'] = '';

			/* Club info in sidebar */
			if ( Settings::i()->clubs_header == 'sidebar' )
			{
				Output::i()->sidebar['enabled'] = true;
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core', 'front' )->header( $club, $category, 'sidebar' );
			}
			else
			{
				Dispatcher::i()->output .= Theme::i()->getTemplate( 'clubs', 'core', 'front' )->header( $club, $category, 'full' );
			}

			if( ( GeoLocation::enabled() and Settings::i()->clubs_locations AND $location = $club->location() ) )
			{
				Output::i()->sidebar['enabled'] = true;
				Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'clubs', 'core', 'front' )->clubLocationBox( $club, $location );
			}
		}
	}
}