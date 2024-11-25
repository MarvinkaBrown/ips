<?php
/**
 * @brief		Databases Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		31 March 2014
 */

namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\cms\Pages\Page;
use IPS\Content\Search\Index;
use IPS\Content\ViewUpdates;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Settings;
use IPS\Task;
use IPS\Widget\Area;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Databases Model
 */
class Databases extends Model implements Permissions
{
	use ViewUpdates;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons = array();

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'database_reciprocal_links', 'cms_databases' );
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'database_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Table
	 */
	public static ?string $databaseTable = 'cms_databases';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'database_key', 'database_page_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnOrder = 'id';
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = FALSE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = '';
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static bool $gotAll = FALSE;
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
			'view' 				=> 'view',
			'read'				=> 2,
			'add'				=> 3,
			'edit'				=> 4,
			'reply'				=> 5,
			'review'            => 7,
			'rate'				=> 6
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'cms';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'databases';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_content_';
		
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = FALSE;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'content_db_';

	/**
	 * [Brief]	Bump on edit only
	 */
	const BUMP_ON_EDIT = 1;
	
	/**
	 * [Brief]	Bump on comment only
	 */
	const BUMP_ON_COMMENT = 2;
	
	/**
	 * [Brief]	Bump on edit only
	 */
	const CATEGORY_VIEW_CATEGORIES = 0;
	
	/**
	 * [Brief]	Bump on comment only
	 */
	const CATEGORY_VIEW_FEATURED = 1;

	/**
	 * [Brief] Database template groups
	 */
	public static array $templateGroups = array(
		'categories' => 'category_index',
		'index'   => 'category_articles',
		'listing'    => 'listing',
		'display'    => 'display',
		'form'       => 'form'
	);

	/**
	 * @brief	Bitwise values for database_options field
	 */
	public static array $bitOptions = array(
		'options' => array(
			'options' => array(
				'comments'              => 1,   // Enable comments?
				'reviews'               => 2,   // Enable reviews?
				'comments_mod'          => 4,   // Enable comment moderation?
				'reviews_mod'           => 8,   // Enable reviews moderation?
			    'indefinite_own_edit'   => 16,  // Enable authors to indefinitely edit their own articles
				'assignments'			=> 32,	// Enable assignments on this database
			)
		)
	);

	/**
	 * Mapping of node columns to specific actions (e.g. comment, review)
	 * Note: Mappings can also reference bitoptions keys.
	 *
	 * @var array
	 */
	public static array $actionColumnMap = array(
		'comments' 			=> 'comments',
		'reviews'			=> 'reviews',
		'moderate_comments'	=> 'comments_mod',
		'moderate_items'	=> 'record_approve',
		'moderate_reviews'  => 'reviews_mod',
		'tags'				=> 'tags_enabled',
		'prefix'			=> 'tags_noprefixes'
	);
	
	/**
	 * @brief	Page title
	 */
	protected ?string $pageTitle = NULL;

	/**
	 * @breif   Used by the dataLayer
	 */
	public static string $contentArea = 'pages_database';

	/**
	 * @breif   Used by the dataLayer
	 */
	public static string $containerType = 'pages_database_category';

    public static bool $_hasLoadedPerms = false;

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * Get the properties that can be added to the datalayer for this key
	 *
	 * @return  array
	 */
	public function getDataLayerProperties(): array
	{
		if ( empty( $this->_dataLayerProperties ) )
		{
			$properties = parent::getDataLayerProperties();
			$properties['content_area'] = $this->_title;
			$properties['container_type'] = "{$this->key}_database";
			$this->_dataLayerProperties = $properties;
		}

		return $this->_dataLayerProperties;
	}

	/**
	 * Check the action column map if the action is enabled in this node
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAction( string $action ) : bool
	{
		$return = parent::checkAction( $action );

		/* Some actions here are reversed, we mark them as disabled instead of enabled */
		if( $action == 'prefix' )
		{
			return !$return;
		}

		return $return;
	}

    /**
     * Load Record
     *
     * @param int|string|null $id ID
     * @param string|null $idField The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
     * @param mixed $extraWhereClause Additional where clause(s) (see \IPS\Db::build for details) - if used will cause multiton store to be skipped and a query always ran
     * @return ActiveRecord|Databases
     * @see        Db::build
     */
    public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
    {
        if ( ! Bridge::i()->pagesAllowDatabaseAccess() )
        {
            throw new OutOfRangeException('pages_not_available');
        }

        return parent::load( $id, $idField, $extraWhereClause );
    }
	
	/**
	 * Return all databases
	 *
	 * @return	array
	 */
	public static function databases(): array
	{
        if ( ! Bridge::i()->pagesAllowDatabaseAccess() )
        {
            static::$gotAll = true;
            static::$multitons = [];
        }
        else
        {
            if ( ! static::$gotAll )
            {
                /* Avoid using SHOW TABLES LIKE / checkForTable() */
                try
                {
                    foreach( static::getStore() as $db )
                    {
                        $id = $db[ static::$databasePrefix . static::$databaseColumnId ];
                        static::$multitons[ $id ] = static::constructFromData( $db );
                    }
                }
                catch( Exception $e ) { }

                static::$gotAll = true;
            }
        }

		return static::$multitons;
	}
	
	/**
	 * Returns database data from the store
	 *
	 * @return array
	 */
	public static function getStore(): array
	{
		if ( ! isset( Store::i()->cms_databases ) )
		{
			Store::i()->cms_databases = iterator_to_array(
				Db::i()->select(
						static::$databaseTable . '.*, core_permission_index.perm_id, core_permission_index.perm_view, core_permission_index.perm_2, core_permission_index.perm_3, core_permission_index.perm_4, core_permission_index.perm_5, core_permission_index.perm_6, core_permission_index.perm_7',
						static::$databaseTable
					)->join(
							'core_permission_index',
							array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType )
					)
				->setKeyField('database_id')
			);
		}
		
		return Store::i()->cms_databases;
	}

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): static
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		$obj->preLoadWords();

		return $obj;
	}
	
		
	/**
	 * Can this database accept RSS imports? 
	 *
	 * @return boolean
	 */
	public function canImportRss(): bool
	{
		if ( ! $this->page_id )
		{
			return FALSE;
		}
		
		$fieldsClass = '\IPS\cms\Fields' . $this->id;
		/* @var $fieldsClass Fields */
		try
		{
			if ( ! in_array( IPS::mb_ucfirst( $fieldsClass::load( $this->field_title )->type ), array( 'Text', 'TextArea', 'Editor' ) ) )
			{
				return FALSE;
			}
			
			if ( ! in_array( IPS::mb_ucfirst( $fieldsClass::load( $this->field_content )->type ), array( 'TextArea', 'Editor' ) ) )
			{
				return FALSE;
			}
		}
		catch( Exception $e )
		{
			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * Return data for the ACP Menu
	 * 
	 * @return array
	 */
	public static function acpMenu(): array
	{
		$menu = array();

		foreach(
			Db::i()->select( '*, core_sys_lang_words.word_custom as database_name, core_sys_lang_words2.word_custom as record_name', 'cms_databases', NULL, 'core_sys_lang_words.word_custom' )
				->join( 'core_sys_lang_words', "core_sys_lang_words.word_key=CONCAT( 'content_db_', cms_databases.database_id ) AND core_sys_lang_words.lang_id=" . Member::loggedIn()->language()->id )
				->join( array( 'core_sys_lang_words', 'core_sys_lang_words2' ), "core_sys_lang_words2.word_key=CONCAT( 'content_db_lang_pu_', cms_databases.database_id ) AND core_sys_lang_words2.lang_id=" . Member::loggedIn()->language()->id )
			as $row )
		{
			$menu[] = array(
				'id'             => $row['database_id'],
				'title'          => $row['database_name'],
				'record_name'	 => $row['record_name'],
				'use_categories' => $row['database_use_categories']
			);
		}

        return $menu;
	}

	/**
	 * Checks and fixes existing DB
	 *
	 * @param int $id     Database ID
	 * @return  int     $fixes  Number of fixes made (0 if none)
	 *
	 * @throws OutOfRangeException
	 */
	public static function checkandFixDatabaseSchema(int $id ): int
	{
		$fixes     = 0;
		$json      = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/cms/data/databaseschema.json" ), true );
		$table     = $json['cms_custom_database_1'];
		$tableName = 'cms_custom_database_' . $id;

		if ( ! Db::i()->checkForTable( $tableName ) )
		{
			throw new OutOfRangeException;
		}

		$schema		= Db::i()->getTableDefinition( $tableName );
		$changes	= array();

		/* Columns */
		foreach( $table['columns'] as $key => $data )
		{
			if ( ! isset( $schema['columns'][ $key ] ) )
			{
				$changes[] = "ADD COLUMN " . Db::i()->compileColumnDefinition( $data );
				$fixes++;
			}
		}

		/* Indexes */
		foreach( $table['indexes'] as $key => $data )
		{
			/* No index */
			if ( ! isset( $schema['indexes'][ $key ] ) )
			{
				$changes[] = Db::i()->buildIndex( $tableName, $data );
				$fixes++;
			}
			else if ( implode( '.', $data['columns'] ) != implode( '.', (array) $schema['indexes'][ $key ]['columns'] ) )
			{
				/* Check columns */
				if( $key == 'PRIMARY KEY' )
				{
					$changes[] = "DROP PRIMARY KEY";
				}
				else
				{
					$changes[] = "DROP KEY `" . Db::i()->escape_string( $key ) . "`";
				}

				$changes[] =  Db::i()->buildIndex( $tableName, $data );
				$fixes++;
			}
		}

		/* We collect all the changes so we can run one database query instead of, potentially, dozens */
		if( count( $changes ) )
		{
			Db::i()->query( "ALTER TABLE " . Db::i()->prefix . $tableName . " " . implode( ', ', $changes ) );
		}

		return $fixes;
	}
	
	/**
	 * Create a new database
	 * 
	 * @param Databases $database		ID of database to create
	 * @return	void
	 */
	public static function createDatabase( Databases $database ) : void
	{
		$json  = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/cms/data/databaseschema.json" ), true );
		$table = $json['cms_custom_database_1'];
	
		$table['name'] = 'cms_custom_database_' . $database->id;
		
		foreach( $table['columns'] as $name => $data )
		{
			if ( mb_substr( $name, 0, 6 ) === 'field_' )
			{
				unset( $table['columns'][ $name ] );
			}
		}
		
		foreach( $table['indexes'] as $name => $data )
		{
			if ( mb_substr( $name, 0, 6 ) === 'field_' )
			{
				unset( $table['indexes'][ $name ] );
			}
		}
		
		try
		{
			if ( ! Db::i()->checkForTable( $table['name'] ) )
			{
				Db::i()->createTable( $table );
			}
		}
		catch( Db\Exception $ex )
		{
			throw new LogicException( $ex );
		}

		/* Populate default custom fields */
		$fieldsClass = 'IPS\cms\Fields' . $database->id;
		$fieldTitle   = array();
		$fieldContent = array();
		$catTitle     = array();
		$catDesc      = array();

		foreach( Lang::languages() as $id => $lang )
		{
			/* Try to get the actual database noun if it has been created */
			try
			{
				$title = $lang->get( 'content_db_lang_pu_' . $database->id );
			}
			catch( Exception $e )
			{
				$title = $lang->get('content_database_noun_pu');
			}

			$fieldTitle[ $id ]   = $lang->get('content_fields_is_title');
			$fieldContent[ $id ] = $lang->get('content_fields_is_content');
			$catTitle[ $id ]     = $title;
			$catDesc[ $id ]      = '';
		}

		/* Title */
		$titleField = new $fieldsClass;
		$titleField->saveForm( $titleField->formatFormValues( array(
			'field_title'			=> $fieldTitle,
			'field_type'			=> 'Text',
			'field_key'				=> 'titlefield_' . $database->id,
			'field_required'		=> 1,
			'field_user_editable'	=> 1,
			'field_display_listing'	=> 1,
			'field_display_display'	=> 1,
			'field_is_searchable'	=> 1,
			'field_max_length'		=> 255
	       ) ) );

		$database->field_title = $titleField->id;
		$perms = $titleField->permissions();

		Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		/* Content */
		$contentField = new $fieldsClass;
		$contentField->saveForm( $contentField->formatFormValues( array(
			'field_title'			=> $fieldContent,
			'field_type'			=> 'Editor',
			'field_key'				=> 'contentfield_' . $database->id,
			'field_required'		=> 1,
			'field_user_editable'	=> 1,
			'field_truncate'		=> 100,
			'field_topic_format'	=> '{value}',
			'field_display_listing'	=> 1,
			'field_display_display'	=> 1,
			'field_is_searchable'	=> 1
         ) ) );

		$database->field_content = $contentField->id;
		$perms = $contentField->permissions();

		Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		/* Save the db now to save custom field ids */
		$database->save();

		/* Create a category */
		$categoryClass = '\IPS\cms\Categories' . $database->id;
		$category = new $categoryClass;
		$category->database_id = $database->id;

		$category->saveForm( $category->formatFormValues( array(
             'category_name'		 => $catTitle,
             'category_description'  => $catDesc,
             'category_parent_id'    => 0,
             'category_has_perms'    => 0,
             'category_show_records' => 1,
			 'category_image' => null
         ) ) );

		$perms = $category->permissions();

		Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		$database->options['comments'] = 1;
		$database->save();
	}

	/**
	 * @brief   Language strings preloaded
	 */
	protected bool $langLoaded = FALSE;

	/**
	 * Get database id
	 * 
	 * @return int
	 */
	public function get__id(): int
	{
		return $this->id;
	}

	/**
	 * Get comment bump
	 *
	 * @return int
	 */
	public function get__comment_bump(): int
	{
		if ( $this->comment_bump === 0 )
		{
			return static::BUMP_ON_EDIT;
		}
		else if ( $this->comment_bump === 1 )
		{
			return static::BUMP_ON_COMMENT;
		}
		else if ( $this->comment_bump === 2 )
		{
			return static::BUMP_ON_EDIT + static::BUMP_ON_COMMENT;
		}

		/* Still here? Use the default */
		return static::BUMP_ON_COMMENT;
	}
	
	/**
	 * Get database name
	 *
	 * @return string
	 */
	public function get__title(): string
	{
		return Member::loggedIn()->language()->addToStack('content_db_' . $this->id);
	}

	/**
	 * Get database description
	 *
	 * @return string|null
	 */
	public function get__description(): ?string
	{
		return Member::loggedIn()->language()->addToStack('content_db_' . $this->id . '_desc');
	}

	/**
	 * Get default category
	 *
	 * @return string|null
	 */
	public function get__default_category(): ?string
	{
		$categoryClass = '\IPS\cms\Categories' . $this->id;
		/* @var $categoryClass Categories */
		if ( $this->default_category )
		{
			try
			{
				$categoryClass::load( $this->default_category );
				return $this->default_category;
			}
			catch( OutOfRangeException $e )
			{
				$this->default_category = NULL;
			}
		}

		if ( ! $this->default_category )
		{
			$roots = $categoryClass::roots( NULL );

			if ( ! count( $roots ) )
			{
				/* Create a category */
				$category = new $categoryClass;
				$category->database_id = $this->id;

				$catTitle = array();
				$catDesc  = array();

				foreach( Lang::languages() as $id => $lang )
				{
					$catTitle[ $id ] = $lang->get('content_database_noun_pu');
					$catDesc[ $id ]  = '';
				}

				$category->saveForm( $category->formatFormValues( array(
                  'category_name'		  => $catTitle,
                  'category_description'  => $catDesc,
                  'category_parent_id'    => 0,
                  'category_has_perms'    => 0,
                  'category_show_records' => 1
                ) ) );

				$perms = $category->permissions();

				Db::i()->update( 'core_permission_index', array(
					'perm_view'	 => '*',
					'perm_2'	 => '*',
					'perm_3'     => '*'
				), array( 'perm_id=?', $perms['perm_id']) );

				$roots = $categoryClass::roots( NULL );
			}

			$category = array_shift( $roots );

			$this->default_category = $category->id;
			$this->save();

			/* Update records */
			Db::i()->update( 'cms_custom_database_' . $this->id, array( 'category_id' => $category->id ), array( 'category_id=0' ) );
		}

		return $this->default_category;
	}

	/**
	 * Get fixed field data
	 * 
	 * @return array
	 */
	public function get_fixed_field_perms(): array
	{
		if ( ! is_array( $this->_data['fixed_field_perms'] ) )
		{
			$this->_data['fixed_field_perms'] = json_decode( (string) $this->_data['fixed_field_perms'], true );
		}
		
		if ( is_array( $this->_data['fixed_field_perms'] ) )
		{
			return $this->_data['fixed_field_perms'];
		}
		
		return array();
	}

	/**
	 * Set the "fixed field" field
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function set_fixed_field_perms( mixed $value ) : void
	{
		$this->_data['fixed_field_perms'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get fixed field settings
	 *
	 * @return array
	 */
	public function get_fixed_field_settings(): array
	{
		if ( ! is_array( $this->_data['fixed_field_settings'] ) )
		{
			$this->_data['fixed_field_settings'] = json_decode( (string) $this->_data['fixed_field_settings'], true );
		}

		if ( is_array( $this->_data['fixed_field_settings'] ) )
		{
			return $this->_data['fixed_field_settings'];
		}

		return array();
	}

	/**
	 * Set the "fixed field" settings field
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function set_fixed_field_settings( mixed $value ) : void
	{
		$this->_data['fixed_field_settings'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * @return Page|null
	 */
	public function get_page() : ?Page
	{
		if( $this->page_id )
		{
			try
			{
				return Page::load( $this->page_id );
			}
			catch( OutOfRangeException ){}
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public function get_allow_club_categories() : bool
	{
		if( Settings::i()->clubs )
		{
			return $this->_data['allow_club_categories'];
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_display_settings() : array
	{
		$return = [];
		if( isset( $this->_data['display_settings'] ) and $this->_data['display_settings'] )
		{
			$return = json_decode( $this->_data['display_settings'], true );
		}

		$default = array(
			'index' => [ 'type' => 'all', 'layout' => 'featured', 'template' => null ],
			'categories' => [ 'layout' => 'table', 'template' => null ],
			'listing' => [ 'layout' => 'table', 'template' => null ],
			'display' => [ 'layout' => 'custom', 'template' => 'display' ],
			'form' => [ 'layout' => 'custom', 'template' => 'form' ]
		);;

		foreach( $default as $k => $v )
		{
			if( !isset( $return[ $k ] ) )
			{
				$return[ $k ] = $v;
			}
		}

		return $return;
	}

	/**
	 * @param array|null $val
	 * @return void
	 */
	public function set_display_settings( ?array $val ) : void
	{
		$this->_data['display_settings'] = ( is_array( $val ) and count( $val ) ) ? json_encode( $val ) : null;
	}

	/**
	 * @return string
	 */
	public function get_template_form() : string
	{
		return $this->display_settings['form']['template'];
	}

	/**
	 * @return string
	 */
	public function get_template_display() : string
	{
		return $this->display_settings['display']['template'];
	}

	/**
	 * @return string
	 */
	public function get_template_listing() : string
	{
		return $this->display_settings['listing']['layout'] == 'custom' ? $this->display_settings['listing']['template'] : 'listing';
	}

	/**
	 * @return string
	 */
	public function get_template_categories() : string
	{
		return $this->display_settings['categories']['layout'] == 'custom' ? $this->display_settings['categories']['template'] : 'category_index';
	}

	/**
	 * @return string
	 */
	public function get_template_featured() : string
	{
		if( $this->display_settings['index']['type'] != 'categories' )
		{
			return $this->display_settings['index']['layout'] == 'custom' ? $this->display_settings['index']['template'] : 'category_articles';
		}

		return $this->template_categories;
	}

	/**
	 * Get the title of the page when using a database
	 *
	 * @return string
	 */
	public function pageTitle(): string
	{
		if ( $this->pageTitle === NULL )
		{
			if ( $this->use_as_page_title )
			{ 
				$this->pageTitle = $this->_title;
			}
			else
			{
				try
				{
					$this->pageTitle = Page::load( $this->page_id )->getHtmlTitle();
				}
				catch( Exception $e ) { }
			}
		}
		
		return $this->pageTitle;
	}

	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission						A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param Group|Member|null $member							The member or group to check (NULL for currently logged in member)
	 * @param bool $considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public function can( mixed $permission, Group|Member $member=NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		/* If we're looking from the front, make sure the database page also passes */
		if ( $permission === 'view' and Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' and $this->page_id )
		{
			try
			{
				return parent::can( 'view', $member, $considerPostBeforeRegistering ) AND Page::load( $this->page_id )->can( 'view', $member, $considerPostBeforeRegistering );
			}
			catch( OutOfRangeException $ex )
			{
				return parent::can( 'view', $member, $considerPostBeforeRegistering );
			}
		}

		return parent::can( $permission, $member, $considerPostBeforeRegistering );
	}

	/**
	 * Sets up and preloads some words
	 *
	 * @return void
	 */
	public function preLoadWords() : void
	{
		/* Skip this during installation / uninstallation as the words won't be loaded */
		if ( !Dispatcher::hasInstance() or Dispatcher::i()->controllerLocation === 'setup' OR ( Dispatcher::i()->controllerLocation === 'admin' AND Dispatcher::i()->module->key === 'applications' ) )
		{
			$this->langLoaded = TRUE;
			return;
		}
		 
		if ( ! $this->langLoaded )
		{
			if ( Dispatcher::i()->controllerLocation === 'admin' )
			{
				/* Moderator tools */
				Member::loggedIn()->language()->words['modperms__core_Content_cms_Records' . $this->id ] = $this->_title;
				Member::loggedIn()->language()->words['cms' . $this->id ] = Member::loggedIn()->language()->addToStack('categories');
				
				/* Editor Areas */
				Member::loggedIn()->language()->words['editor__cms_Records' . $this->id ] = $this->_title;

				foreach( array( 'pin', 'unpin', 'feature', 'unfeature', 'edit', 'hide', 'unhide', 'view_hidden', 'future_publish', 'view_future', 'move', 'lock', 'unlock', 'reply_to_locked', 'delete', 'feature_comments', 'unfeature_comments', 'add_item_message', 'edit_item_message', 'delete_item_message', 'view_reports' ) as $lang )
				{
					Member::loggedIn()->language()->words['can_' . $lang . '_content_db_lang_sl_' . $this->id ] = Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_record', FALSE, array( 'sprintf' => array( $this->recordWord(1) ) ) );
					Member::loggedIn()->language()->words['can_' . $lang . '_content_db_lang_su_' . $this->id ] = Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_record', FALSE, array( 'sprintf' => array( $this->recordWord(1, TRUE) ) ) );

					if ( in_array( $lang, array( 'edit', 'hide', 'unhide', 'view_hidden', 'delete' ) ) )
					{
						Member::loggedIn()->language()->words['can_' . $lang . '_content_record_comments_title_' . $this->id ] = Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_rcomment', FALSE, array( 'sprintf' => array( $this->recordWord(1) ) ) );
						Member::loggedIn()->language()->words['can_' . $lang . '_content_record_reviews_title_' . $this->id ] = Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_rreview', FALSE, array( 'sprintf' => array( $this->recordWord(1) ) ) );

					}
				}
			}

			$this->langLoaded = true;
		}
	}

	/**
	 * "Records" / "Record" word
	 *
	 * @param int $number	Number
	 * @param bool $upper  ucfirst string
	 * @return	string
	 */
	public function recordWord( int $number = 2, bool $upper=FALSE ): string
	{
		if ( Application::appIsEnabled('cms') )
		{
			return Member::loggedIn()->language()->recordWord( $number, $upper, $this->id );
		}
		else
		{
			/* If the Pages app is disabled, just load a generic phrase */
			$key = "content_database_noun_" . ( $number > 1 ? "p" : "s" ) . ( $upper ? "u" : "l" );
			return Member::loggedIn()->language()->addToStack( $key );
		}
	}
	
	/**
	 * [ActiveRecord] Save Record
	 *
	 * @return    void
	 */
	public function save(): void
	{
		/* If we are enabling search, we will need to index that content */
		$rebuildSearchIndex = ( !$this->_new AND isset( $this->changed['search'] ) AND $this->changed['search'] );
		$removeSearchIndex	= ( !$this->_new AND isset( $this->changed['search'] ) AND !$this->changed['search'] );

		parent::save();

		/* If this database isn't searchable, make sure its content is not in the search index */
		if( $removeSearchIndex )
		{
			Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records' . $this->id );
			Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records\Comment' . $this->id );
			Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records\Review' . $this->id );

			/* If there are any bg tasks to rebuild index, clear them */
			foreach( Db::i()->select( '*', 'core_queue', array( '`key`=?', 'RebuildSearchIndex' ) ) as $queue )
			{
				$details = json_decode( $queue['data'], true );

				if( isset( $details['class'] ) AND $details['class'] == 'IPS\cms\Records' . $this->id )
				{
					Db::i()->delete( 'core_queue', array( 'id=?', $queue['id'] ) );
				}
			}
		}
		elseif( $rebuildSearchIndex )
		{
			Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\cms\Records' . $this->id ), 5, TRUE );
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$fieldsClass = '\IPS\cms\Fields' . $this->id;

		$class = '\IPS\cms\Categories' . $this->id;
		/* @var $class Categories */
		/* @var $fieldsClass Fields */
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'cms_database_categories', array( 'category_database_id=?', $this->id ) ), '\IPS\cms\Categories' . $this->id ) as $cat )
		{
			$cat->delete();
		}

		$fileFields = array( 'record_image', 'record_image_thumb' );
		$isMultiple = false;

		foreach( $fieldsClass::roots( NULL ) as $id => $field )
		{
			$field->delete( TRUE );

			if( $field->type == 'Upload' )
			{
				$fileFields[] = 'field_' . $field->id;

				/* Delete thumbnails */
				Task::queue( 'core', 'FileCleanup', array(
					'table'				=> 'cms_database_fields_thumbnails',
					'column'			=> 'thumb_location',
					'storageExtension'	=> 'cms_Records',
					'where'				=> array( array( 'thumb_field_id=?', $field->id ) ),
					'deleteRows'		=> TRUE
				), 4 );

				if( $field->is_multiple )
				{
					$isMultiple = TRUE;
				}
			}
		}
		
		/* Delete comments */
		Db::i()->delete( 'cms_database_comments', array( 'comment_database_id=?', $this->id ) );
		Db::i()->delete( 'cms_database_reviews', array( 'review_database_id=?', $this->id ) );

		/* Delete from view counter */
		Db::i()->delete( 'core_view_updates', array( 'classname=? ', 'IPS\cms\Records' . $this->id ) );

		/* Delete records */
		Task::queue( 'core', 'FileCleanup', array(
			'table'				=> 'cms_custom_database_' . $this->id,
			'column'			=> $fileFields,
			'storageExtension'	=> 'cms_Records',
			'primaryId'			=> 'primary_id_field',
			'dropTable'			=> 'cms_custom_database_' . $this->id,
			'multipleFiles'		=> $isMultiple
		), 4 );
		
		/* Delete revisions */
		Db::i()->delete( 'cms_database_revisions', array( 'revision_database_id=?', $this->id ) );
		
		/* Remove any reciprocal linking */
		Db::i()->delete( 'cms_database_fields_reciprocal_map', array( 'map_foreign_database_id=? or map_origin_database_id=?', $this->id, $this->id ) );

		/* Remove any fields in other databases associated to this DB */
		foreach( Db::i()->select( '*', 'cms_database_fields', array( 'field_type=?', 'Item' ) ) as $fieldData )
		{
			$fieldClass     = '\IPS\cms\Fields' .  $fieldData['field_database_id'];
			/* @var $fieldClass Fields */
			if ($field = $fieldClass::load($fieldData['field_id']) AND isset( $field->extra['database'] ) and $field->extra['database'] AND $field->extra['database'] == $this->id )
			{
				$field->delete();
			}
		}

		/* Delete notifications */
		$memberIds	= array();

		foreach( Db::i()->select( '`member`', 'core_notifications', array( 'item_class=? ', 'IPS\cms\Records' . $this->id ) ) as $member )
		{
			$memberIds[ $member ]	= $member;
		}

		Db::i()->delete( 'core_notifications', array( 'item_class=? ', 'IPS\cms\Records' . $this->id ) );
		Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=?', 'cms', 'records' . $this->id ) );

		/* Remove promoted content */
		Db::i()->delete( 'core_content_promote', array( 'promote_class=?', 'IPS\cms\Records' . $this->id ) );

		/* remove deletion log */
		Db::i()->delete( 'core_deletion_log', array( 'dellog_content_class=?', 'IPS\cms\Records' . $this->id  ) );
		Db::i()->delete( 'core_deletion_log', array( 'dellog_content_class=?', 'IPS\cms\Records\Comment' . $this->id  ) );
		Db::i()->delete( 'core_deletion_log', array( 'dellog_content_class=?', 'IPS\cms\Records\Review' . $this->id  ) );

		/* remove metadata */
		Db::i()->delete( 'core_content_meta', array( "meta_class=? ", 'IPS\cms\Records' . $this->id  ) );

		foreach( $memberIds as $member )
		{
			Member::load( $member )->recountNotifications();
		}

		/* Remove rss imports */
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_rss_import', array( 'rss_import_class=?', 'IPS\cms\Records' . $this->id ) ), 'IPS\core\Rss\Import' ) as $import )
		{
			$import->delete();
		}

		/* Remove from search */
		Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records' . $this->id );
		Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records\Comment' . $this->id );
		Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records\Review' . $this->id );

		/* Delete custom languages */
		Lang::deleteCustom( 'cms', "content_db_" . $this->id );
		Lang::deleteCustom( 'cms', "content_db_" . $this->id . '_desc');
		Lang::deleteCustom( 'cms', "content_db_lang_sl_" . $this->id );
		Lang::deleteCustom( 'cms', "content_db_lang_pl_" . $this->id );
		Lang::deleteCustom( 'cms', "content_db_lang_su_" . $this->id );
		Lang::deleteCustom( 'cms', "content_db_lang_pu_" . $this->id );
		Lang::deleteCustom( 'cms', "content_db_lang_ia_" . $this->id );
		Lang::deleteCustom( 'cms', "content_db_lang_sl_" . $this->id . '_pl' );
		Lang::deleteCustom( 'cms', "__indefart_content_db_lang_sl_" . $this->id );
		Lang::deleteCustom( 'cms', "__defart_content_db_lang_sl_" . $this->id );
		Lang::deleteCustom( 'cms', "cms_records" . $this->id . '_pl' );
		Lang::deleteCustom( 'cms', "module__cms_records" . $this->id );
		Lang::deleteCustom( 'cms', "digest_area_cms_records" . $this->id );
		Lang::deleteCustom( 'cms', "digest_area_cms_categories" . $this->id );

		/* Unclaim attachments */
		File::unclaimAttachments( 'cms_Records', NULL, NULL, $this->id );
		File::unclaimAttachments( 'cms_Records' . $this->id );

		/* Remove widgets */
		$this->removeWidgets();

		/* Delete the page */
		if( $page = $this->page )
		{
			$page->delete();
		}

		/* Delete the database record */
		parent::delete();
	}

	/**
	 * Remove any database widgets
	 *
	 * @return void
	 */
	public function removeWidgets() : void
	{
		$databaseWidgets = array( 'Database', 'LatestArticles' );

		foreach ( Db::i()->select( '*', 'cms_page_widget_areas' ) as $item )
		{
			if( $pageBlocks = json_decode( $item['area_tree'], true ) )
			{
				$update = false;
				$area = new Area( $pageBlocks, $item['area_area'] );
				foreach( $area->getAllWidgets() as $widget )
				{
					if( $widget['app'] == 'cms' and in_array( $widget['key'], $databaseWidgets ) and !empty( $widget['configuration']['database'] ) and $widget['configuration']['database'] == $this->_id )
					{
						$area->removeWidget( $widget['unique'] );
						$update = true;
					}
				}

				if( $update )
				{
					Db::i()->update( 'cms_page_widget_areas', array( 'area_tree' => json_encode( $area->toArray( true, false ) ) ), array( 'area_page_id=? and area_area=?', $item['area_page_id'], $item['area_area'] ) );
				}
			}
		}
	}

	/**
	 * Set the permission index permissions
	 *
	 * @param array $insert	Permission data to insert
	 * @return  void
	 */
	public function setPermissions( array $insert ) : void
	{
		parent::setPermissions( $insert );
		
		/* Clear cache */
		unset( Store::i()->cms_databases );
		
		/* Clone these permissions to all categories that do not have permissions */
		$class = '\IPS\cms\Categories' . $this->id;
		/* @var $class Categories */
		foreach( $class::roots( NULL ) as $category )
		{
			$this->setPermssionsRecursively( $category );
		}

		/* Clone the view permissions to the page */
		if( $page = $this->page )
		{
			$page->setPermissions([
				'app' => $page::$permApp, 'perm_type' => $page::$permType, 'perm_type_id' => $page->_id, 'perm_view' => $insert['perm_view']
			]);
		}
	}
	
	/**
	 * Recursively set permissions
	 *
	 * @param Categories $category		Category object
	 * @return	void
	 */
	protected function setPermssionsRecursively( Categories $category ) : void
	{
		if ( ! $category->has_perms )
		{
			$category->cloneDatabasePermissions();
		}
		
		foreach( $category->children() as $child )
		{
			$this->setPermssionsRecursively( $child );
		}
	}

	/**
	 * Get the number of categories in this database
	 *
	 * @return  int
	 */
	public function numberOfCategories() : int
	{
		static $count = null;
		if ( $count === NULL )
		{
			$count = Db::i()->select( 'count(*)', 'cms_database_categories', array( 'category_database_id=?', $this->_id ) )->first();
		}
		return $count;
	}

	/**
	 * Get the number of club categories in this database
	 *
	 * @return  int
	 */
	public function numberOfClubCategories() : int
	{
		static $count = null;
		if ( $count === NULL )
		{
			$count = Db::i()->select( 'count(*)', 'cms_database_categories', array( 'category_database_id=? AND category_club_id IS NOT NULL', $this->_id ) )->first();
		}
		return $count;
	}


	/**
	 * Determines if any fields from other databases are crosslinking to items in this database via the Relational field
	 *
	 * @param int $databaseId		The ID of the database
	 * @return boolean
	 */
	public static function hasReciprocalLinking( int $databaseId ): bool
	{
		if ( isset( Store::i()->database_reciprocal_links ) )
		{
			$values = Store::i()->database_reciprocal_links;
		}
		else
		{
			$values = array();
			foreach( static::databases() as $database )
			{
				$fieldsClass = 'IPS\cms\Fields' . $database->_id;
				/* @var $fieldsClass Fields */
				foreach( $fieldsClass::data() as $field )
				{
					if ( $field->type === 'Item' )
					{
						$extra = $field->extra;
						if ( ! empty( $extra['database'] ) )
						{
							$values[ $database->_id ][] = $extra;
						}
					}
				}

				Store::i()->database_reciprocal_links = $values;
			}
		}

		if ( is_array( $values ) )
		{
			foreach( $values as $id => $fields )
			{
				foreach( $fields as $fieldid => $data )
				{
					if ( $data['database'] == $databaseId and !empty( $data['crosslink'] ) )
					{
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Determines if any fields from other databases are linking to items in this database via the Relational field
	 *
	 * @param	int		$databaseId		The ID of the database
	 * @return boolean
	 */
	public static function isUsedAsReciprocalField( int $databaseId ) : bool
	{
		if ( isset( Store::i()->database_reciprocal_links ) )
		{
			$values = Store::i()->database_reciprocal_links;
		}
		else
		{
			$values = array();
			foreach( static::databases() as $database )
			{
				$fieldsClass = 'IPS\cms\Fields' . $database->_id;
				/* @var $fieldsClass Fields */
				foreach( $fieldsClass::data() as $field )
				{
					if ( $field->type === 'Item' )
					{
						$extra = $field->extra;
						if ( ! empty( $extra['database'] ) )
						{
							$values[ $database->_id ][] = $extra;
						}
					}
				}

				Store::i()->database_reciprocal_links = $values;
			}
		}

		if ( is_array( $values ) )
		{
			foreach( $values as $id => $fields )
			{
				foreach( $fields as $fieldid => $data )
				{
					if ( $data['database'] == $databaseId )
					{
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}
	
	/**
	 * Rebuild the reciprocal linking maps across all databases
	 *
	 * @return void
	 */
	public static function rebuildReciprocalLinkMaps() : void
	{
		/* Ensure the SPL are loaded from /cms/Application.php as this may be called by a task or upgrade module */
		Application::load('cms');
		
		Db::i()->delete( 'cms_database_fields_reciprocal_map' );
		
		foreach( static::databases() as $database )
		{
			$fieldsClass = 'IPS\cms\Fields' . $database->_id;
			/* @var $fieldsClass Fields */
			foreach( $fieldsClass::data() as $field )
			{
				if ( $field->type === 'Item' )
				{
					Task::queue( 'cms', 'RebuildReciprocalMaps', array( 'database' => $database->_id, 'field' => $field->id ), 2, array( 'field' ) );
				}
			}
		}
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse			int					id				ID number
	 * @apiresponse			string				name			Name
	 * @apiresponse			bool					useCategories	If this database uses categories
	 * @clientapiresponse	[\IPS\cms\Fields]	fields			The fields
	 * @apiresponse			string				url				URL
	 * @clientapiresponse	object|null			permissions		Node permissions
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$return = array(
			'id'			=> $this->id,
			'name'			=> $this->_title,
			'useCategories'	=> (bool) $this->use_categories,
		);
		
		if ( $authorizedMember === NULL )
		{
			$return['fields'] = array();
			$fieldsClass = '\IPS\cms\Fields' . $this->id;
			/* @var $fieldsClass Fields */
			foreach ( $fieldsClass::roots() as $field )
			{
				$return['fields'][] = $field->apiOutput( $authorizedMember );
			}
		}
		
		try
		{
			$pagePath   = Page::loadByDatabaseId( $this->id )->full_path;
			$return['url'] = (string) Url::internal( "app=cms&module=pages&controller=page&path=" . $pagePath, 'front', 'content_page_path' );
		}
		catch( OutOfRangeException $ex )
		{
			$return['url'] = NULL;		
		}

		if( $authorizedMember === NULL )
		{
			$return['permissions']	= in_array( 'IPS\Node\Permissions', class_implements( get_class( $this ) ) ) ? $this->permissions() : NULL;
		}
		
		return $return;
	}
}