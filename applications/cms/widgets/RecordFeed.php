<?php
/**
 * @brief		RecordFeed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		24 Nov 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ErrorException;
use IPS\cms\Databases;
use IPS\cms\Fields;
use IPS\Content\Widget;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget\Polymorphic;
use OutOfRangeException;
use function count;
use function defined;
use function get_called_class;
use function in_array;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * RecordFeed Widget
 */
class RecordFeed extends Widget
implements Polymorphic
{

	public static function getBaseKey(): string
	{
		return "RecordFeed";
	}

	/**
	 * @brief	Widget Key
	 */
	public string $key = 'RecordFeed';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';
	
	/**
	 * Class
	 */
	protected static string $class = 'IPS\cms\Records';

	/**
	 * @var int|null
	 */
	public static ?int $customDatabaseId = null;

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey			Unique key for this specific instance
	 * @param	array				$configuration		Widget custom configuration
	 * @param array|string|null $access				Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation		Horizontal or vertical orientation
	 * @param string $layout		Current layout in use
	 * @return	void
	 */
	public function __construct( string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='' )
	{
		/* If we called this class directly and we have a database ID, call the child class.
		We do this so that we can support existing widgets. */
		if( get_called_class() == 'IPS\cms\widgets\RecordFeed' and isset( $configuration['cms_rf_database'] ) )
		{
			$widgetClass = 'IPS\cms\widgets\RecordFeed' . $configuration['cms_rf_database'];
			return new $widgetClass( $uniqueKey, $configuration, $access, $orientation, $layout );
		}

		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );

		/* Force the database to be set to the DatabaseID */
		if( !isset( $this->configuration['cms_rf_database'] ) )
		{
			$this->configuration['cms_rf_database'] = static::$customDatabaseId;
		}

		$this->key = 'RecordFeed' . static::$customDatabaseId;
		static::$class = 'IPS\cms\Records' . static::$customDatabaseId;

		/* Load block title and description */
		Member::loggedIn()->language()->words[ 'block_RecordFeed' . static::$customDatabaseId ] = Member::loggedIn()->language()->addToStack( 'cms_db_feed_block_with_name', FALSE, array('sprintf' => array( Member::loggedIn()->language()->addToStack('content_db_' . static::$customDatabaseId ) ) ) );
		Member::loggedIn()->language()->words[ 'block_RecordFeed' . static::$customDatabaseId . '_desc' ] = Member::loggedIn()->language()->addToStack( 'cms_db_feed_block_with_name_desc', false, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'content_db_lang_pl_' . static::$customDatabaseId ) ) ) );
	}

	/**
	 * Initialise this widget
	 *
	 * @return void
	 * @throws ErrorException
	 */
	public function init(): void
	{
		$this->template( array( Theme::i()->getTemplate( 'widgets', 'cms', 'front' ), 'RecordFeed' ) );
	}

	/**
	 * @return Databases|null
	 */
	protected function database() : ?Databases
	{
		if( static::$customDatabaseId )
		{
			return Databases::load( static::$customDatabaseId );
		}
		elseif( isset( $this->configuration['cms_rf_database'] ) )
		{
			return Databases::load( $this->configuration['cms_rf_database'] );
		}

		return null;
	}

	/**
	 * Specify widget configuration
	 *
	 * @param Form|null  $form Form object
	 * @return Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$database  = $this->database();

		$form = parent::configuration( $form );

		$form->addDummy( 'cms_rf_database', $database->_title, Member::loggedIn()->language()->addToStack( 'cms_rf_database_description' ) );
		$form->hiddenValues['cms_rf_database'] = $database->_id;

		$class = static::$class;

		/* If the database does not use categories, hide that field from the form */
		if( $database and !$database->use_categories and isset( $form->elements['']['widget_feed_container_' . $class::$title ] ) )
		{
			unset( $form->elements['']['widget_feed_container_' . $class::$title ] );
		}

		/* Sort */
		$sortOptions = array(
			'primary_id_field'    => 'database_field__id',
			'member_id'		      => 'database_field__member',
			'record_publish_date' => 'database_field__saved',
			'record_updated' => ( $database and $database->_comment_bump === Databases::BUMP_ON_EDIT ) ? 'database_field__edited' : 'database_field__updated',
			'record_last_comment' => 'sort_record_last_comment',
			'record_rating' 	  => 'database_field__rating'
		);

		/* @var array $databaseColumnMap */
		foreach ( array( 'num_comments', 'views', 'rating_average' ) as $k )
		{
			if ( isset( $class::$databaseColumnMap[ $k ] ) )
			{
				$sortOptions[ $class::$databaseColumnMap[ $k ] ] = 'sort_' . $k;
			}
		}

		/* Tags */
		if ( $database )
		{
			 /* @var Fields $fieldsClass */
			$fieldsClass = '\IPS\cms\Fields' . $database->id;

			foreach( $fieldsClass::data() as $id => $field )
			{
				if ( in_array( $field->type, array( 'checkbox', 'multiselect', 'attachments' ) ) )
				{
					continue;
				}

				$sortOptions[ 'field_' . $field->id ] = $field->_title;
			}
			
			if ( $database->tags_enabled )
			{
				$options = array( 'autocomplete' => array( 'unique' => TRUE, 'source' => NULL, 'freeChoice' => TRUE ) );
	
				if ( Settings::i()->tags_force_lower )
				{
					$options['autocomplete']['forceLower'] = TRUE;
				}
	
				$options['autocomplete']['prefix'] = FALSE;
	
				$form->add( new Text( 'widget_feed_tags', ( $this->configuration['widget_feed_tags'] ?? array( 'tags' => NULL ) ), FALSE, $options ) );
			}
		}

		$form->add( new Select( 'widget_feed_sort_on', $this->configuration['widget_feed_sort_on'] ?? $class::$databaseColumnMap['updated'], FALSE, array( 'options' => $sortOptions ), NULL, NULL, NULL, 'widget_feed_sort_on' ) );
		
		/* Any filterable fields */
		if ( $database )
		{
			/* @var Fields $fieldClass */
			$fieldClass   = 'IPS\cms\Fields' .  $database->id;
			foreach( $fieldClass::fields( $this->_getCustomValuesFromConfiguration(), 'view', NULL, $fieldClass::FIELD_SKIP_TITLE_CONTENT | $fieldClass::FIELD_DISPLAY_FILTERS ) as $id => $field )
			{
				$form->add( $field );
			}
		}
		
		if ( $database )
		{
			Member::loggedIn()->language()->words['widget_feed_container_content_db_lang_su_' . $database->id ] = Member::loggedIn()->language()->addToStack('widget_feed_container_cms');
			
			if ( $database->_comment_bump === Databases::BUMP_ON_EDIT )
			{
				Member::loggedIn()->language()->words['sort_updated'] = Member::loggedIn()->language()->addToStack('database_field__edited');
			}
		}
		
		return $form;
 	} 
 	
 	/**
	 * Fetch custom field values from the saved configuration
	 *
	 * @param	boolean	$keyAsInt	Returns an array with just the field ID, as opposed to 'field_x'
	 * @return array
	 */
 	protected function _getCustomValuesFromConfiguration( bool $keyAsInt=false ) : array
 	{
	 	$customValues = array();
		foreach( $this->configuration as $k => $v )
		{
			if ( mb_substr( $k, 0, 8 ) === 'content_' )
			{
				$customValues[ $keyAsInt ? str_replace( 'content_field_', '', $k ) : mb_substr( $k, 8 ) ] = $v;
			}
		}
		
		return $customValues;
 	}
 	
 	/**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
	 	static::$class = '\IPS\cms\Records' . static::$customDatabaseId;

	 	foreach( $values as $k => $v )
	 	{
		 	/* We need to reformat this a little */
		 	if ( is_array( $v ) and isset( $v['start'] ) and isset( $v['end'] ) )
		 	{
				$start = ( $v['start'] instanceof DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
				$end   = ( $v['end'] instanceof DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
				
				$values[ $k ] = array( 'start' => $start, 'end' => $end );
			}
	 	}
	 	
	 	return parent::preConfig( $values );
 	}
 	
 	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere(): array
	{
		static::$class = '\IPS\cms\Records' . static::$customDatabaseId;
		
		$where = parent::buildWhere();

		/* @var Fields $fieldClass */
		$fieldClass   = 'IPS\cms\Fields' .  static::$customDatabaseId;
		$customFields = $fieldClass::data( 'view', NULL, $fieldClass::FIELD_SKIP_TITLE_CONTENT | $fieldClass::FIELD_DISPLAY_FILTERS );

		foreach( $this->_getCustomValuesFromConfiguration( TRUE ) as $f => $v )
		{
			$k = 'field_' . $f;
			if ( isset( $customFields[ $f ] ) and $v !== '___any___' AND $v !== NULL )
			{
				if ( is_array( $v ) )
				{
					if ( array_key_exists( 'start', $v ) or array_key_exists( 'end', $v ) )
					{
						$start = ( $v['start'] instanceof DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
						$end   = ( $v['end'] instanceof DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );
						
						if ( $start or $end )
						{
							$where[] = array( '( ' . $k . ' BETWEEN ' . $start . ' AND ' . $end . ' )' );
						}
					}
					else
					{
						$like = array();
						if ( count( $v ) )
						{
							foreach( $v as $val )
							{
								if ( $val === 0 or ! empty( $val ) )
								{
									$like[]  = "CONCAT( ',', " .  $k . ", ',') LIKE '%," . Db::i()->real_escape_string( $val ) . ",%'";
								}
							}
							
							$where[] = array( '( ' . Db::i()->in( $k, $v ) .  ( count( $like ) ? " OR (" . implode( ' OR ', $like ) . ') )' : ')' ) );
						}
					}
				}
				else
				{
					if ( $v === false )
					{
						continue;
					}
					if ( $v !== 0 and ! $v )
					{
						$where[] = array( $k . " IS NULL" );
					}
					else
					{
						$where[] = array( $k . "=?", $v );
					}
				}
			}
		}
		
		return $where;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if( $database = $this->database() )
		{
			if( $database->page_id )
			{
				return parent::render();
			}
		}

		return '';
	}
}