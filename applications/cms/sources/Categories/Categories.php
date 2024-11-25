<?php
/**
 * @brief		Categories Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		8 April 2014
 */

namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\cms\Pages\Page;
use IPS\Content\ClubContainer;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Search\Index;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Node\Statistics;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Widget\Area;
use OutOfBoundsException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_called_class;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Categories Model
 */
class Categories extends Model implements Permissions
{
	use Statistics,
		ViewUpdates,
		ClubContainer
	{
		ClubContainer::clubAcpTitle as public _clubAcpTitle;
		ClubContainer::clubForm as public _clubForm;
		ClubContainer::_saveClubForm as public _saveClubFormParent;
		ClubContainer::canBeAddedToClub as public _canBeAddedToClub;
	}

	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	[Records] Custom Database Id
	 */
	public static ?int $customDatabaseId = NULL;
	
	/**
	 * @brief	[Records] Content item class
	 */
	public static ?string $contentItemClass = NULL;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_database_categories';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'category_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('category_furl_name', 'category_full_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = FALSE;
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'r__categories';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'content_cat_name_';

	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';

	/**
	 * @breif   Used by the dataLayer, overwritten by \IPS\cms\Application
	 */
	public static string $contentArea = 'pages_database';

	/**
	 * @breif   Used by the dataLayer, overwritten by \IPS\cms\Application
	 */
	public static string $containerType = 'pages_database_category';

	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'furl_name';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 array(
	 'app'		=> 'core',				// The application key which holds the restrictrions
	 'module'	=> 'foo',				// The module key which holds the restrictions
	 'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 'add'			=> 'foo_add',
	 'edit'			=> 'foo_edit',
	 'permissions'	=> 'foo_perms',
	 'delete'		=> 'foo_delete'
	 ),
	 'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
			'app'		=> 'cms',
			'module'	=> 'databases',
			'prefix' 	=> 'categories_'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'cms';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = NULL;
	
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
	 * @brief	[Node] Moderator Permission
	 */
	public static string $modPerm = 'cms';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_content_category_';
	
	/**
	 * @brief	[Page] Loaded pages from paths
	 */
	protected static array $loadedCatsFromPath = array();

	/**
	 * @brief 	[Records] Database objects
	 */
	protected static array $database = array();

	/**
	 * @brief   Latest posted record
	 */
	protected static ?Records $latestRecordAdded = NULL;

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * Returns a database category object (or NULL) based on the path
	 *
	 * @param string $path		Path /like/this/maybearecordhere-r1234
	 * @param int|null $databaseId	Database ID to look up
	 * @return	NULL|Categories object
	 */
	public static function loadFromPath( string $path, int $databaseId=NULL ): ?Categories
	{
		$path = trim( $path, '/' );
	
		if ( ! array_key_exists( $path, static::$loadedCatsFromPath ) )
		{
			static::$loadedCatsFromPath[ $path ] = NULL;
				
			/* Try the simplest option */
			try
			{
				$where = ( $databaseId === NULL ) ? NULL : array( 'category_database_id=?', $databaseId );
				static::$loadedCatsFromPath[ $path ] =  static::load( $path, 'category_full_path', $where );
			}
			catch ( OutOfRangeException $e )
			{
				/* May contain a record name */
				$where = ( $databaseId === NULL ) ? array( '? LIKE CONCAT( TRIM(TRAILING \'/\' FROM category_full_path), \'/\', \'%\')', rtrim( $path, '/' ) . '/' ) : array( 'category_database_id=? AND ? LIKE CONCAT( TRIM(TRAILING \'/\' FROM category_full_path), \'/\', \'%\')', $databaseId, rtrim( $path, '/' ) . '/' );

				foreach(
					Db::i()->select(
						static::$databaseTable . '.*, core_permission_index.perm_id, core_permission_index.perm_view, core_permission_index.perm_2, core_permission_index.perm_3, core_permission_index.perm_4, core_permission_index.perm_5, core_permission_index.perm_6, core_permission_index.perm_7',
						static::$databaseTable,
						$where,
						'LENGTH(category_full_path) DESC'
					)->join(
							'core_permission_index',
							array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType )
					)
					as $meow )
				{
					static::$loadedCatsFromPath[ $path ] = static::constructFromData( $meow );

					break;
				}
			}
		}
	
		return static::$loadedCatsFromPath[ $path ];
	}

	/**
	 * Returns the database parent
	 *
	 * @return Databases
	 */
	public static function database(): Databases
	{
		if ( !isset( static::$database[ static::$customDatabaseId ] ) )
		{
			static::$database[ static::$customDatabaseId ] = Databases::load( static::$customDatabaseId );
		}

		return static::$database[ static::$customDatabaseId ];
	}

	/**
	 * @var array|null
	 */
	protected static ?array $containerIds = null;
	
	/**
	 * Test to see if this is a valid container ID
	 *
	 * @param int $id		Container ID
	 * @return	boolean
	 */
	public static function isValidContainerId( int $id ): bool
	{
		if ( static::$containerIds === NULL )
		{
			static::$containerIds = iterator_to_array( Db::i()->select( 'category_id', static::$databaseTable, array( array( 'category_database_id=?', static::$customDatabaseId ) ) ) );
		}

		return in_array( $id, static::$containerIds );
	}

	/**
	 * Get acp language string
	 *
	 * @return	string
	 */
	public static function clubAcpTitle(): string
	{
		return static::database()->_title . Member::loggedIn()->language()->addToStack( static::$nodeTitle );
	}

	/**
	 * Check if the node can be added as a club feature
	 * This was mainly implemented to handle the club categories,
	 * which are based on database settings.
	 *
	 * @return bool
	 */
	public static function canBeAddedToClub() : bool
	{
		return static::database()->allow_club_categories;
	}

	/**
	 * Set form for creating a node of this type in a club
	 *
	 * @param Form $form Form object
	 * @param Club $club
	 * @return    void
	 */
	public function clubForm( Form $form, Club $club ): void
	{
		$this->database_id = static::$customDatabaseId;

		static::_clubForm( $form, $club );

		if( $club->type != Club::TYPE_PUBLIC )
		{
			$current = $this->club_view_permission;
			$current = ( !$current OR mb_strpos( '*', $current) !== false ) ? [ 'nonmember', 'member', 'moderator' ] : array_filter( explode( ",", $current ) );

			$form->add( new CheckboxSet( 'page_can_view', $current, false, array(
				'options' => array(
					'nonmember' => 'club_page_nonmembers',
					'member' => 'club_page_members',
					'moderator' => 'club_page_moderators'
				),
				'toggles' => array(
					'nonmember' => array( 'club_category_meta_index' )
				)
			) ) );

			/* Add the index setting if this page is shown to guests */
			if( $club->type !== Club::TYPE_PRIVATE and Member::loggedIn()->group['gbw_club_manage_indexing'] )
			{
				$form->add( new YesNo( 'club_category_meta_index', $this->club_category_meta_index, false, [], null, null, null,'club_category_meta_index' ) );
			}
		}
	}

	/**
	 * Class-specific routine when saving club form
	 *
	 * @param	Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function _saveClubForm( Club $club, array &$values ): void
	{
		if( !$this->id )
		{
			$this->_updatePaths = true;
			$this->save();
		}

		$this->name = $values['club_node_name'];
		Lang::saveCustom( 'cms', 'content_cat_name_' . $this->_id, $this->name );
		$this->furl_name = Friendly::seoTitle( $this->name );
		if( static::furlNameIsTaken( $this->furl_name, static::$customDatabaseId, $this->_id ?: null ) )
		{
			$this->furl_name = $this->_id . '_' . $this->furl_name;
		}

		if( is_numeric( $this->furl_name ) )
		{
			$this->furl_name= 'n_' . $this->furl_name;
		}

		$values['club_node_public'] = $this->isPublic();
		if( array_key_exists( 'page_can_view', $values ) )
		{
			$this->club_view_permission= implode( ",", $values['page_can_view'] );
			$values['club_node_public'] = intval( in_array( 'nonmember', $values['page_can_view'] ) or mb_strpos( '*', $this->club_view_permission ) !== false );
			unset( $values['page_can_view'] );
		}

		if( array_key_exists( 'club_category_meta_index', $values ) )
		{
			$this->club_category_meta_index = (int) $values['club_category_meta_index'];
		}
		elseif( !in_array( 'nonmember', explode( ",", $this->club_view_permission ) ) )
		{
			$this->club_category_meta_index = 0;
		}

		$this->save();
		$this->setFullPath();
	}

	/**
	 * @brief Cache of categories we've fetched
	 */
	protected static array $cache = array();
	
	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		if ( static::$customDatabaseId !== NULL )
		{
			$where[] = array( 'category_database_id=?', static::$customDatabaseId );
		}
		
		return parent::roots( $permissionCheck, $member, $where, $limit );
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param mixed $where				Additional WHERE clause
	 * @param string|null $order				ORDER BY clause
	 * @param array|null $limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	protected static function nodesWithPermission( ?string $permissionCheck, ?Member $member, mixed $where=array(), string $order=NULL, array $limit=NULL ): array
	{
		if( static::$customDatabaseId !== null )
		{
			$where[] = array( 'category_database_id=?', static::$customDatabaseId );
		}

		return parent::nodesWithPermission( $permissionCheck, $member, $where, $order, $limit );
	}
	
	/**
	 * [Node] Fetch Child Nodes
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULL to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param bool|null $subnodes			Include subnodes? NULL to *only* check subnodes
	 * @param array|null $skip				Children IDs to skip
	 * @param mixed $_where				Additional WHERE clause
	 * @return	array
	 */
	public function children( ?string $permissionCheck='view', Member $member=NULL, bool|null $subnodes=TRUE, array $skip=null, mixed $_where=array() ): array
	{
		$permissionCheck = ( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'admin' ) ? NULL : $permissionCheck;
		
		return parent::children( $permissionCheck, $member, $subnodes, $skip, $_where );
	}
	
	/**
	 * Resets a category path
	 *
	 * @param int $categoryId		Category ID to reset
	 * @return	void
	 */
	public static function resetPath( int $categoryId ) : void
	{
		try
		{
			$category = static::load( $categoryId );
		}
		catch ( OutOfRangeException $ex )
		{
			throw new OutOfRangeException;
		}
	
		$category->setFullPath();
	}

	/**
	 * Ensure there aren't any collision issues.
	 *
	 * @param string $path   Path to check
	 * @return  boolean
	 */
	static public function isFurlCollision( string $path ): bool
	{
		$path  = trim( $path , '/');
		$bits  = explode( '/', $path );
		$root  = $bits[0];
		
		/* _ is here due to IP.Board 3.x using that to denote the articles database (eg. articles.html/_/category/record-r1/), which we need to redirect from for records and articles. */
		if ( in_array( $root, array( 'submit', '_' ) ) )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * Set the "extra" field
	 *
	 * @param array|string $value
	 * @return void
	 */
	public function set_fields( array|string $value ) : void
	{
		$this->_data['fields'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "extra" field
	 *
	 * @return array|string
	 */
	public function get_fields(): array|string
	{
		return ( $this->_data['fields'] === '*' OR empty( $this->_data['fields'] ) ) ? '*' : json_decode( $this->_data['fields'], TRUE );
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		parent::__clone();
		
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}
		
		$this->furl_name .= '_' . $this->id;
		$this->save();

		$this->setFullPath();
	}

	/**
	 * Retrieve an array of IDs a member has posted in.
	 *
	 * @param Member|null $member	The member (NULL for currently logged in member)
	 * @param array|null $inSet	If supplied, checks will be restricted to only the ids provided
	 * @param array|null $additionalWhere    Additional where clause
	 * @param array|null $commentJoinWhere	Additional join clause for comments table
	 * @return	array				An array of content item ids
	 */
	public function contentPostedIn( Member $member=NULL, array $inSet=NULL, array $additionalWhere=NULL, array $commentJoinWhere=NULL ): array
	{
		$database = Databases::load( $this->database_id );
		
		if ( $database->forum_record and $database->forum_forum )
		{
			return array();
		}
		
		/* What about local category forum sync? */
		if ( $this->forum_record and $this->forum_forum )
		{
			return array();
		}

		/* @var Item $contentItemClass
		 * @var Comment $commentClass */
		$contentItemClass = static::$contentItemClass;
		$commentClass     = $contentItemClass::$commentClass;

		return parent::contentPostedIn( $member, $inSet, NULL, $commentClass::commentWhere() );
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$database = Databases::load( Request::i()->database_id );

		/* Build form */
		$form->addTab( 'content_content_form_tab__config' );
		
		$form->add( new Translatable( 'category_name', NULL, TRUE, array(
				'app'		  => 'cms',
				'key'		  => ( $this->id ? "content_cat_name_" .  $this->id : NULL )
		) ) );
		
		if ( ! $this->id )
		{
			$form->add( new YesNo( 'category_furl_name_choice', FALSE, FALSE, array(
					'togglesOn' => array('category_furl_name')
			), NULL, NULL, NULL, 'category_furl_name_choice' ) );
		}
		
		$form->add( new Text( 'category_furl_name', $this->furl_name, FALSE, array(), function( $val )
		{
			/* Make sure key is unique */
			if ( empty( $val ) )
			{
				return true;
			}
			
			if ( Request::i()->category_parent_id == 0 and Categories::isFurlCollision( $val ) )
			{
				throw new InvalidArgumentException('content_cat_furl_collision');
			}
			
			try
			{
				$cat = Db::i()->select( '*', 'cms_database_categories', array( 'category_database_id=? and category_parent_id=? and category_furl_name=?', Request::i()->database_id, Request::i()->category_parent_id, $val ) )->first();
			}
			catch( UnderflowException $ex )
			{
				/* Nuffink matches */
				return true;
			}
			
			if ( isset( Request::i()->id ) )
			{
				if ( $cat['category_id'] != Request::i()->id )
				{
					throw new InvalidArgumentException('content_cat_furl_not_unique');
				}
			}
			else
			{
				throw new InvalidArgumentException('content_cat_furl_not_unique');
			}
			
			return true;
		}, NULL, NULL, 'category_furl_name' ) );
		
		$form->add( new Translatable( 'category_description', NULL, FALSE, array(
				'app'		  => 'cms',
				'key'		  => ( $this->id ? "content_cat_name_" .  $this->id . "_desc" : NULL )
		) ) );

		$class = get_called_class();

		$form->add( new Node( 'category_parent_id', ( ! $this->id ) ? ( isset( Request::i()->parent ) ? Request::i()->parent : 0 ) : $this->parent_id, FALSE, array(
			'class'		      => '\IPS\cms\Categories' . $database->id,
			'disabled'	      => false,
			'zeroVal'         => 'node_no_parent',
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}
					
				return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
			}
		) ) );

		$form->add( new Form\Upload( 'category_image', ( $this->id AND $this->image ) ? File::get( 'cms_Categories', $this->image ) : null, false, array( 'allowStockPhotos' => true, 'storageExtension' => 'cms_Categories', 'image' => true, 'multiple' => false ) ) );
		
		$form->add( new YesNo( 'category_show_records', $this->id ? $this->show_records : TRUE, FALSE, array(), NULL, NULL, NULL, 'category_show_records' ) );
		$form->add( new YesNo( 'category_allow_rating', $this->allow_rating, FALSE, array(), NULL, NULL, NULL, 'category_allow_rating' ) );
		$form->add( new YesNo( 'category_has_perms', $this->has_perms, FALSE, array(), NULL, NULL, NULL, 'category_has_perms' ) );
		$form->add( new YesNo( 'category_can_view_others', $this->id ? $this->can_view_others : TRUE, FALSE, array(), NULL, NULL, NULL, 'category_can_view_others' ) );
		$form->add( new YesNo( 'allow_anonymous', $this->id ? $this->allow_anonymous : FALSE, FALSE, array() ) );
		
		$form->addHeader('cms_categories_header_display');
		$templatesList     = array();
		$templatesDisplay  = array( 0 => Member::loggedIn()->language()->addToStack('cms_categories_use_database') );

		foreach(Templates::getTemplates( Templates::RETURN_DATABASE + Templates::RETURN_DATABASE_AND_IN_DEV ) as $template )
		{
			$title = Templates::readableGroupName( $template->group );

			switch( $template->original_group )
			{
				case 'listing':
					if( $template->group != 'listing' )
					{
						$templatesList[ $template->group ] = $title;
					}
					break;
				case 'display':
					$templatesDisplay[ $template->group ] = $title;
					break;
			}
		}

		$layouts = [ 0 => Member::loggedIn()->language()->addToStack('cms_categories_use_database') ];
		foreach( Area::$widgetOnlyLayouts as $feedLayout )
		{
			if( !str_ends_with( $feedLayout, '-carousel' ) )
			{
				$layouts[ $feedLayout ] = 'core_pagebuilder_wrap__' . $feedLayout;
			}
		}

		$custom = ['custom' => 'database_layout_custom' ];

		$form->add( new Select( 'category_listing_layout', $this->template_listing == 0 ? 0 : ( $this->display_settings['layout'] ?? 'table' ), true, [
			'options' => ( count( $templatesList ) ? array_merge( $layouts, $custom ) : $layouts ),
			'toggles' => [ 'custom' => [ 'category_template_listing' ] ]
		] ) );
		if( count( $templatesList ) )
		{
			$form->add( new Select( 'category_template_listing', ( $this->display_settings and $this->display_settings['layout'] == 'custom' ) ? $this->display_settings['template'] : NULL, FALSE, array( 'options' => $templatesList ), NULL, NULL, null, 'category_template_listing' ) );
		}

		$form->add( new Select( 'category_template_display', ( ( $this->id and $this->template_display ) ? $this->template_display : '0' ), FALSE, array( 'options' => $templatesDisplay ) ) );

		$form->addTab( 'content_content_form_header__meta' );
		
		$form->add( new Text( 'category_page_title',  $this->page_title, FALSE, array(), NULL, NULL, NULL ) );
		$form->add( new TextArea( 'category_meta_keywords', $this->meta_keywords, FALSE, array(), NULL, NULL, NULL, 'category_meta_keywords' ) );
		$form->add( new TextArea( 'category_meta_description', $this->meta_description, FALSE, array(), NULL, NULL, NULL, 'category_meta_description' ) );

		if ( Application::appIsEnabled( 'forums' ) )
		{
			$form->addTab( 'content_content_form_tab__forum' );
			
			$form->add( new YesNo( 'category_forum_override', ( $this->id ? $this->forum_override : NULL ), FALSE, array(
					'togglesOn' => array(
						'database_forum_record',
						'database_forum_comments',
						'database_forum_forum',
						'database_forum_prefix',
						'database_forum_suffix',
						'database_forum_delete'	
					)
			), NULL, NULL, NULL, 'category_forum_override' ) );

			try
			{
				Db::i()->select( '*', 'core_queue', [ "`app`=? AND `key`=? AND `data` LIKE CONCAT( '%', ?, '%' )", 'cms', 'MoveComments', 'databaseID":' . $database->id ] )->first();
				Member::loggedIn()->language()->words['database_forum_record_desc'] = Member::loggedIn()->language()->addToStack( 'database_forum_comments_in_progress' );
				$disabled = true;
			}
			catch( UnderflowException $e )
			{
				$disabled = FALSE;
			}
			
			$form->add( new YesNo( 'database_forum_record', $this->id ? $this->forum_record : FALSE, FALSE, array( 'togglesOn' => array(
					'database_forum_comments',
					'database_forum_forum',
					'database_forum_prefix',
					'database_forum_suffix',
					'database_forum_delete'
			),
				'disabled' => $disabled ), NULL, NULL, NULL, 'database_forum_record' ) );

			$form->add( new YesNo( 'database_forum_comments', $this->id ? $this->forum_comments : FALSE, FALSE, array( 'disabled' => $disabled ), NULL, NULL, NULL, 'database_forum_comments' ) );

			$form->add( new Node( 'database_forum_forum', $this->id ? $this->forum_forum : NULL, FALSE, array(
					'class'		      => '\IPS\forums\Forum',
					'disabled'	      => false,
					'permissionCheck' => function( $node )
					{
						return $node->sub_can_post;
					}
			), function( $val )
			{
				if ( ! $val and Request::i()->category_forum_override and Request::i()->database_forum_record_checkbox )
				{
					throw new InvalidArgumentException('cms_database_no_forum_selected');
				}
				return true;
			}, NULL, NULL, 'database_forum_forum' ) );
				
			$form->add( new Text( 'database_forum_prefix',  $this->id ? $this->forum_prefix: '', FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'database_forum_prefix' ) );
			$form->add( new Text( 'database_forum_suffix',  $this->id ? $this->forum_suffix: '', FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'database_forum_suffix' ) );
			$form->add( new YesNo( 'database_forum_delete', $this->id ? $this->forum_delete : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_forum_delete' ) );
		}
		
		$form->addTab( 'content_content_form_header__fields' );
		
		$cats		= $this->id ? ( $this->fields === NULL ? '*' : $this->fields ) : '*';
		$options	= array();
		$fieldClass	= 'IPS\cms\Fields' . $database->id;
		/* @var $fieldClass Fields */
		foreach( $fieldClass::data( NULL, NULL, $fieldClass::FIELD_SKIP_TITLE_CONTENT ) as $field )
		{
			if ( $field->id !== $database->field_title AND $field->id !== $database->field_content )
			{
				$options[ $field->id ] = $field->_title;
			}		
		}

		if ( count( $options ) )
		{
			$form->add( new Select( 'category_fields', $cats, FALSE, array(
				'multiple'  => true,
				'unlimited' => '*',
				'options'   => $options,
			), NULL, NULL, NULL, 'category_fields' ) );
		}

        parent::form( $form );
	}

	/**
	 * See if a furl name is taken within a database
	 *
	 * @param string 	$furl				The default FURL (of a category)
	 * @param int		$databaseId			The database
	 * @param int|null 	$excludeId			Optional: an id to exclude from the query
	 *
	 * @return bool
	 */
	public static function furlNameIsTaken( string $furl, int $databaseId, ?int $excludeId=null ) : bool
	{
		$where = array( ['category_database_id=?', $databaseId ] );
		if ( $excludeId )
		{
			$where[] = [ 'category_id <> ?', $excludeId ];
		}

		foreach ( (new ActiveRecordIterator( Db::i()->select( '*', 'cms_database_categories', $where ), self::class )) as $cat )
		{
			if ( ( !empty( $cat->furl_name ) and $cat->furl_name == $furl ) or ( empty( $cat->furl_name ) AND !empty( $cat->name ) AND Friendly::seoTitle( $cat->name ) == $furl ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( ! $this->database_id )
		{
			if ( isset( $values['category_database_id'] ) )
			{
				$this->database_id = $values['category_database_id'];
			}
			else if ( isset( Request::i()->database_id ) )
			{
				$this->database_id = Request::i()->database_id;
			}

			$values['category_database_id'] = $this->database_id;
		}
		
		/* Need this for later */
		$_new = $this->_new;

		if ( ! $this->id )
		{
			$this->_updatePaths = TRUE;
			$this->save();
		}

		if ( isset( $values['category_name'] ) AND is_array( $values['category_name'] ) )
		{
			$name = $values['category_name'][ Lang::defaultLanguage() ];
		}
		else if( isset( $values['category_name'] ) )
		{
			$name = $values['category_name'];
		}

		/* Save the name and description */
		if( isset( $values['category_name'] ) )
		{
			Lang::saveCustom( 'cms', 'content_cat_name_' . $this->id, $values['category_name'] );
		}

		if( isset( $values['category_description'] ) )
		{
			Lang::saveCustom( 'cms', 'content_cat_name_' . $this->id . '_desc', $values['category_description'] );
			unset( $values['category_description'] );
		}
		
		if ( isset( $name ) AND empty( $values['category_furl_name'] ) )
		{
			$ok  = static::furlNameIsTaken( Friendly::seoTitle( $name ), $this->database_id, $this->id ?: null );

			if ( Request::i()->category_parent_id == 0 and Categories::isFurlCollision( Friendly::seoTitle( $name ) ) )
			{
				$ok = FALSE;
			}

			$values['furl_name'] = $ok ? Friendly::seoTitle( $name ) : $this->id . '_' . Friendly::seoTitle( $name );
		}
		else if( isset( $values['category_furl_name'] ) )
		{
			$values['furl_name'] = Friendly::seoTitle( $values['category_furl_name'] );
			
			/* We cannot have numeric furl_names 'cos you could do page/2/ and it will confuse SEO pagination. This is not possible with other areas as it is always /id-furl/ */
			if ( is_numeric( $values['furl_name'] ) )
			{
				$values['furl_name'] = 'n' . $values['furl_name'];
			}
		}

		if( array_key_exists( 'category_furl_name_choice', $values ) )
		{
			unset( $values['category_furl_name_choice'] );
		}
		
		if ( isset( $values['category_parent_id'] ) AND ( ! empty( $values['category_parent_id'] ) OR $values['category_parent_id'] === 0 ) )
		{
			$values['category_parent_id'] = ( $values['category_parent_id'] === 0 ) ? 0 : $values['category_parent_id']->id;
		}

		if ( $this->furl_name !== $values['furl_name'] or $this->parent_id !== $values['category_parent_id'] )
		{
			$this->_updatePaths = TRUE;
		}

		$values['category_image'] = ( isset( $values['category_image'] ) and $values['category_image'] instanceof File ) ? (string) $values['category_image'] : null;

		if( array_key_exists( 'category_listing_layout', $values ) )
		{
			if( $values['category_listing_layout'] == 0 )
			{
				$values['category_template_listing'] = 0;
			}
			else
			{
				$layout = ( $values['category_listing_layout'] == 'custom' ) ?
					[ 'layout' => 'custom', 'template' => $values['category_template_listing'] ] :
					[ 'layout' => $values['category_listing_layout'], 'template' => null ];
				$values['category_template_listing'] = json_encode( $layout );
			}
			unset( $values['category_listing_layout'] );
		}

		if ( isset( $values['category_template_display'] ) )
		{
			$values['category_template_display'] = ( $values['category_template_display'] !== '_none_' ) ? $values['category_template_display'] : NULL;
		}

		if ( Application::appIsEnabled( 'forums' ) )
		{
			$values['forum_override'] = $values['category_forum_override'] ?? 0;
			unset( $values['category_forum_override'] );
			
			foreach( array( 'forum_record', 'forum_comments', 'forum_prefix', 'forum_suffix', 'forum_delete' ) as $field )
			{
				if ( array_key_exists( 'database_' . $field, $values ) )
				{
					$values[ $field ] = $values[ 'database_' . $field ];
					unset( $values[ 'database_' . $field ] );
				}
			}
			
			/* Are we changing where comments go? */
			if ( !$_new AND ( (int) $this->forum_record != (int) $values['forum_record'] OR (int) $this->forum_comments != (int) $values['forum_comments'] ) )
			{
				Task::queue( 'cms', 'MoveComments', array(
					'databaseId'		=> $this->database()->id,
					'categoryId'		=> $this->_id,
					'to'				=> ( $values['forum_comments'] AND $values['forum_record'] ) ? 'forums' : 'pages',
					'deleteTopics'		=> ( !$values['forum_record'] )
				), 1, array( 'databaseId', 'to', 'categoryId' ) );
			}

			if( array_key_exists( 'database_forum_forum', $values ) )
			{
				$values['forum_forum'] = ( !$values['database_forum_forum'] ) ? 0 : $values['database_forum_forum']->id;
				unset( $values['database_forum_forum'] );
			}
		}

		$values['category_allow_rating']	= ( isset( $values['category_allow_rating'] ) ) ? (int) $values['category_allow_rating'] : 0;
		$values['category_has_perms']		= ( isset( $values['category_has_perms'] ) ) ? (int) $values['category_has_perms'] : 0;
		$values['forum_record']				= ( isset( $values['forum_record'] ) ) ? (int) $values['forum_record'] : 0;
		$values['forum_comments']			= ( isset( $values['forum_comments'] ) ) ? (int) $values['forum_comments'] : 0;
		$values['forum_delete']				= ( isset( $values['forum_delete'] ) ) ? (int) $values['forum_delete'] : 0;

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		$this->save();

		/* Clone permissions from the database */
		if ( ! $this->has_perms )
		{
			$this->cloneDatabasePermissions();
		}

		if ( $this->_updatePaths )
		{
			$this->setFullPath();
		}

        parent::postSaveForm( $values );
	}

	/**
	 * Clone permissions from the parent database
	 * 
	 * @return void
	 */
	public function cloneDatabasePermissions() : void
	{
		$catPerms = $this->permissions(); /* Called to ensure it has a perm row */
		$dbPerms  = Databases::load( $this->database_id )->permissions();

		$this->_permissions = array_merge( $dbPerms, array( 'perm_id' => $catPerms['perm_id'], 'perm_type_id' => $this->_id, 'perm_type' => 'categories_' . $this->database_id ) );

		if( $this->_permissions['perm_view'] === NULL )
		{
			$this->_permissions['perm_view'] = '';
		}

		Db::i()->update( 'core_permission_index', $this->_permissions, array( 'perm_id=?', $catPerms['perm_id'] ) );

		/* Update tags permission cache */
		if ( isset( static::$permissionMap['read'] ) )
		{
			Db::i()->update( 'core_tags_perms', array( 'tag_perm_text' => $dbPerms[ 'perm_' . static::$permissionMap['read'] ] ), array( 'tag_perm_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );
		}
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return    bool
	 */
	public function canDelete(): bool
	{
		$database = Databases::load( $this->database_id );
		if ( !$database->cat_index_type and $database->numberOfCategories() <= 1 )
		{
			return FALSE;
		}
		return parent::canDelete();
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		if ( !$this->_new AND isset( $this->changed['can_view_others'] ) )
		{
			$this->updateSearchIndexPermissions();
		}
		parent::save();
	}

	/**
	 *  Delete
	 *
	 * @return void
	 */
	public function delete(): void
	{
		/* Remove tags, if any */
		$aap = md5( 'content;categories;' . $this->id );

		Db::i()->delete( 'core_tags', array( 'tag_aap_lookup=?', $aap ) );
		Db::i()->delete( 'core_tags_perms', array( 'tag_perm_aap_lookup=?', $aap ) );

		/* Delete category follows */
		Db::i()->delete( 'core_follow', array( "follow_app=? AND follow_area=? AND follow_rel_id=?", 'cms', 'categories' . $this->database()->id, $this->_id ) );

		/* If this category belonged to a club, delete the row from the club nodes table */
		Db::i()->delete( 'core_clubs_node_map', [ 'node_class=? and node_id=?', 'IPS\cms\Categories' . static::$customDatabaseId, $this->_id ] );

		parent::delete();
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * @brief	Cached title
	 */
	protected ?string $_catTitle = NULL;
	
	/**
	 * @brief	Cached title for strip tags version
	 */
	protected ?string $_catTitleLangKey = NULL;
	
	/**
	 * @brief	Last comment time
	 */
	protected bool $_lastCommentTime = FALSE;

	/**
	 * @brief   Permissions mashed up with db
	 */
	protected bool $_permsMashed = FALSE;

	/**
	 * @brief   FURL changed
	 */
	protected bool $_updatePaths = FALSE;
	
	/**
	 * Disabled permissions
	 * Allow node classes to define permissions that are unselectable in the permission matrix
	 *
	 * @return array	array( {group_id} => array( 'read', 'view', 'perm_7' );
	 */
	public function disabledPermissions(): array
	{
		$database  = Databases::load( $this->database_id );
		$dbPerms   = $database->permissions();
		$disabled  = array();
		
		foreach( array( 'view', 2, 3, 4, 5, 6, 7 ) as $perm )
		{
			/* Remove unticked database permissions */
			if ( $dbPerms['perm_' . $perm ] != '*' )
			{
				$db = explode( ',', $dbPerms['perm_' . $perm ] );
				
				foreach ( Group::groups() as $group )
				{
					if ( ! in_array( $group->g_id, $db ) )
					{
						$disabled[ $group->g_id ][] = $perm;
					}
				}
			}
		}

		try
		{
			$guestGroup = Group::load( Settings::i()->guest_group );
		}
		catch( OutOfRangeException $e )
		{
			throw new UnderflowException( 'invalid_guestgroup_admin', 199 );
		}

		if( !$this->can_view_others )
		{
			$disabled[ $guestGroup->g_id ] = array( 'view', 2, 3, 4, 5, 6, 7 );
		}

		return $disabled;
	}
	
	/**
	 * Get permissions
	 *
	 * @return	array
	 */
	public function permissions(): array
	{
		/* Let the ACP/Setup use normal permissions or it'll get messy. Also use normal permissions for club categories */
		if ( ! Dispatcher::hasInstance() OR Dispatcher::i()->controllerLocation !== 'front' OR $this->isClubCategory() )
		{
			$this->_permsMashed = true;
			return parent::permissions();
		}

		if ( ! $this->_permsMashed )
		{
			/* Make sure we have perms */
			if ( ! $this->_permissions )
			{
				parent::permissions();
			}

			$dbPerms   = static::database()->permissions();
			$savePerms = $this->_permissions;

			foreach( array( 'view', 2, 3, 4, 5, 6, 7 ) as $perm )
			{
				/* Make sure category permission cannot be better than database permissions */
				if ( $dbPerms['perm_' . $perm ] != $savePerms['perm_' . $perm ] )
				{
					/* Category using *? Use database instead */
					if ( $savePerms['perm_' . $perm ] == '*' )
					{
						$savePerms['perm_' . $perm ] = $dbPerms['perm_' . $perm ];
					}
					else if ( $dbPerms['perm_' . $perm ] == '*' )
					{
						/* That's fine, cat is going to be less permissive than * */
						continue;
					}
					else
					{
						/* Make sure that groups not in the database are not in here too */
						$db  = explode( ',', (string) $dbPerms['perm_' . $perm ] );
						$cat = explode( ',', (string) $savePerms['perm_' . $perm ] );

						$savePerms['perm_' . $perm ] = implode( ',', array_intersect( $db, $cat ) );
					}
				}
			}

			$savePerms['perm_2'] = $this->readPermissionMergeWithPage( $savePerms );

			$this->_permissions = $savePerms;
			$this->_permsMashed = TRUE;
		}

		return $this->_permissions;
	}

	/**
	 * Return least favourable permissions based on category and page
	 *
	 * @param array|null $perms      Array of perms
	 *
	 * @return string|null
	 */
	public function readPermissionMergeWithPage( array $perms=NULL ): ?string
	{
		/* Now check against the page */
		if( static::database()->page_id )
		{
			try
			{
				$page      = Page::load( static::database()->page_id );
				$pagePerms = $page->permissions();
				$catPerms  = ( $perms ) ?: $this->permissions();

				if ( $pagePerms['perm_view'] === '*' )
				{
					return (string) $catPerms['perm_2'];
				}
				else if ( $catPerms['perm_2'] === '*' )
				{
					return (string) $pagePerms['perm_view'];
				}
				else
				{
					return implode( ',', array_intersect( explode( ',', $pagePerms['perm_view'] ), explode( ',', $catPerms['perm_2'] ) ) );
				}
			}
			catch ( OutOfRangeException $ex ){}
		}

		return (string) $this->_permissions['perm_2'];
	}

	/**
	 * Get URL
	 *
	 * @return Url|string|null
	 */
	public function url(): Url|string|null
	{
		if( $this->_url === NULL )
		{
			if ( Page::$currentPage and static::database()->page_id == Page::$currentPage->id )
			{
				$pagePath = Page::$currentPage->full_path;
			}
			else
			{
				try
				{
					$pagePath = Page::loadByDatabaseId( static::$customDatabaseId )->full_path;
				}
				catch( OutOfRangeException $e )
				{
					return NULL;
				}
			}
			
			$catPath  = $this->full_path;

			if ( static::database()->use_categories )
			{
				$this->_url = Url::internal( "app=cms&module=pages&controller=page&path=" . $pagePath . '/' . $catPath, 'front', 'content_page_path', $this->furl_name );
			}
			else
			{
				$this->_url = Url::internal( "app=cms&module=pages&controller=page&path=" . $pagePath, 'front', 'content_page_path', $this->furl_name );
			}
		}

		return $this->_url;
	}
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @return	Url
	 */
	public static function urlFromIndexData( array $indexData, array $itemData, ?array $containerData ): Url
	{
		$recordClass = $indexData['index_class'];
		if ( in_array( 'IPS\Content\Comment', class_parents( $recordClass ) ) )
		{
			$recordClass = $recordClass::$itemClass;
		}
		if ( $recordClass::$pagePath === NULL )
		{
			$recordClass::$pagePath = Db::i()->select( array( 'page_full_path' ), 'cms_pages', array( 'page_id=?', $recordClass::database()->page_id ) )->first();
		}
				
		if ( $recordClass::database()->use_categories )
		{
			return Url::internal( "app=cms&module=pages&controller=page&path=" . $recordClass::$pagePath . '/' . $itemData['extra'], 'front', 'content_page_path', $itemData['extra'] );
		}
		else
		{
			return Url::internal( "app=cms&module=pages&controller=page&path=" . $recordClass::$pagePath, 'front', 'content_page_path', '' );
		}
	}
	
	/**
	 * Get title from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	bool		$escape			If the title should be escaped for HTML output
	 * @return	mixed
	 */
	public static function titleFromIndexData(array $indexData, array $itemData, ?array $containerData, bool $escape = TRUE ): mixed
	{
		$recordClass = $indexData['index_class'];
		if ( in_array( 'IPS\Content\Comment', class_parents( $recordClass ) ) )
		{
			$recordClass = $recordClass::$itemClass;
		}
		
		if ( $recordClass::database()->use_categories )
		{
			return parent::titleFromIndexData( $indexData, $itemData, $containerData, $escape );
		}
		else
		{
			return $escape ? $recordClass::database()->_title : $recordClass::database()->getTitleForLanguage( Member::loggedIn()->language() );
		}
	}
	
	/**
	 * Get Page Title for use in `<title>` tag
	 *
	 * @return	string
	 */
	public function pageTitle(): string
	{
		if ( $this->page_title )
		{
			return $this->page_title;
		}
		
		return $this->database()->pageTitle();
	}

	/**
	 * Get the club of this category, or null if there is no club
	 *
	 * @return Club|null
	 */
	public function get__club() : Club|null
	{
		if ( $this->isClubCategory() )
		{
			return $this->club();
		}
		return null;
	}

	/**
	 * Get title of category
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		/* If the DB is in a page, and we're not using categories, then return the page title, not the category title for continuity */
		$database = static::database();
		if ( ! $database->use_categories and !$this->_club )
		{
			if ( ! $this->_catTitle )
			{
				if ( $database->use_as_page_title )
				{
					$this->_catTitle = $database->_title;
				}
				else
				{
					try
					{
						$page = Page::loadByDatabaseId( $this->database_id );
						$this->_catTitle = $page->_title;
					}
					catch ( OutOfRangeException $e )
					{
						$this->_catTitle = parent::get__title();
					}
				}
			}

			return $this->_catTitle;
		}
		else
		{
			return parent::get__title();
		}
	}

	/**
	 * Get the title for a node using the specified language object
	 * This is commonly used where we cannot use the logged in member's language, such as sending emails
	 *
	 * @param Lang $language	Language object to fetch the title with
	 * @param array $options	What options to use for language parsing
	 * @return	string
	 */
	public function getTitleForLanguage( Lang $language, array $options=array() ): string
	{
		if ( ! Databases::load( $this->database_id )->use_categories )
		{
			return $language->addToStack( 'content_db_' . $this->database_id, NULL, $options );
		}
		
		return parent::getTitleForLanguage( $language, $options );
	}
	
	/**
	 * [Node] Get Title language key, not added to a language stack
	 *
	 * @return	string|null
	 */
	protected function get__titleLanguageKey(): ?string
	{
		/* If the DB is in a page, and we're not using categories, then return the page title, not the category title for continuity */
		if ( ! Databases::load( $this->database_id )->use_categories )
		{
			if ( ! $this->_catTitleLangKey )
			{
				try
				{
					$page = Page::loadByDatabaseId( $this->database_id );
					$this->_catTitleLangKey = $page->_titleLanguageKey;
				}
				catch( OutOfRangeException $e )
				{
					$this->_catTitleLangKey = parent::get__titleLanguageKey();
				}
			}

			return $this->_catTitleLangKey;
		}
		else
		{
			return parent::get__titleLanguageKey();
		}
	}

	/**
	 * [Node] Get Description
	 *
	 * @return	string|null
	 */
	protected function get__description(): ?string
	{
		if ( ! static::database()->use_categories )
		{
			return static::database()->_description;
		}

		return ( Member::loggedIn()->language()->addToStack('content_cat_name_' . $this->id . '_desc') === 'content_cat_name_' . $this->id . '_desc' ) ? $this->description : Member::loggedIn()->language()->addToStack('content_cat_name_' . $this->id . '_desc');
	}

	/**
	 * Get number of items
	 *
	 * @return	int|null
	 */
	protected function get__items(): ?int
	{
		if ( ! $this->can_view_others and !Member::loggedIn()->modPermission( 'can_content_view_others_records' ) )
		{
			return Db::i()->select('count(*)', 'cms_custom_database_' . $this->database_id, array("record_future_date=0 AND category_id=? AND record_approved=1 AND member_id=?", $this->id, Member::loggedIn()->member_id) )->first();
		}
		
		return (int) $this->records;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Items
	 * @return	void
	 */
	protected function set__items( int $val ) : void
	{
		$this->records = $val;
	}

	/**
	 * Get number of reviews
	 *
	 * @return	int|null
	 */
	protected function get__reviews(): ?int
	{
		return $this->record_reviews;
	}

	/**
	 * Set number of reviews
	 *
	 * @param	int	$val	Comments
	 * @return	void
	 */
	protected function set__reviews( int $val ): void
	{
		$this->record_reviews = $val;
	}

	/**
	 * Get number of unapproved reviews
	 *
	 * @return int|null
	 */
	protected function get__unapprovedReviews(): ?int
	{
		return $this->record_reviews_queued;
	}

	/**
	 * Set number of unapproved reviews
	 *
	 * @param	int	$val	Comments
	 * @return	void
	 */
	protected function set__unapprovedReviews( int $val ): void
	{
		$this->record_reviews_queued = $val;
	}

	/**
	 * Get number of comments
	 *
	 * @return	int|null
	 */
	protected function get__comments(): ?int
	{
		return $this->record_comments;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Comments
	 * @return	void
	 */
	protected function set__comments( int $val ) : void
	{
		$this->record_comments = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return	int
	 */
	protected function get__unapprovedItems() : int
	{
		return $this->records_queued;
	}

	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return int|null
	 */
	protected function get__unapprovedComments(): ?int
	{
		return $this->record_comments_queued;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @param	int	$val	Unapproved Items
	 * @return	void
	 */
	protected function set__unapprovedItems( int $val ) : void
	{
		$this->records_queued = $val;
	}

	/**
	 * [Node] Get number of future publishing items
	 *
	 * @return	int|null
	 */
	protected function get__futureItems(): ?int
	{
		return $this->records_future;
	}

	/**
	 * [Node] Get number of future content items
	 *
	 * @param	int	$val	Future Items
	 * @return	void
	 */
	protected function set__futureItems( int $val ) : void
	{
		$this->records_future = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @param	int	$val	Unapproved Comments
	 * @return	void
	 */
	protected function set__unapprovedComments( int $val ) : void
	{
		$this->record_comments_queued = $val;
	}

	/**
	 * @return array|null
	 */
	public function get_display_settings() : ?array
	{
		if( $this->template_listing and $data = json_decode( $this->template_listing, true ) )
		{
			return $data;
		}

		return null;
	}

	/**
	 * Get the template listing template
	 *
	 * @return  string      Templateg group
	 */
	public function get__template_listing(): string
	{
		if ( $this->template_listing AND static::database()->use_categories )
		{
			$data = json_decode( $this->template_listing, true );
			return ( $data['layout'] == 'custom' ) ? $data['template'] : 'listing';
		}

		return static::database()->template_listing;
	}

	/**
	 * Get the template display template
	 *
	 * @return  string      Templateg group
	 */
	public function get__template_display(): string
	{
		if ( $this->template_display AND static::database()->use_categories )
		{
			return $this->template_display;
		}

		return static::database()->template_display;
	}

	/**
	 * Check the action column map if the action is enabled in this node
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAction( string $action ) : bool
	{
		return static::database()->checkAction( $action );
	}

	/**
	 * Set last comment
	 *
	 * @param Comment|null $comment The latest comment or NULL to work it out
	 * @param Item|null $updatedItem We sometimes run setLastComment() when an item has been edited, if so, that item will be here
	 * @return    void
	 */
	protected function _setLastComment( Comment $comment=NULL, Item $updatedItem=NULL ) : void
	{
		$database = Databases::load( $this->database_id );

		/* Make sure it wasn't a comment added to a hidden record */
		if ( $comment !== NULL )
		{
			if ( $comment->item()->hidden() OR $comment->item()->isFutureDate() )
			{
				$comment = NULL;
			}
		}

		if ( $comment === NULL )
		{   
			try
			{
				$recordClass  = '\IPS\cms\Records' . $this->database_id;
				$commentClass = '\IPS\cms\Records\Comment' . $this->database_id;
				$comment      = NULL;
				/* @var $recordClass Records */
				/* @var $commentClass Comment */
				if ( static::$latestRecordAdded === NULL )
				{
					static::$latestRecordAdded = $recordClass::constructFromData(
						Db::i()->select(
							'*',
							'cms_custom_database_' . $this->database_id,
							array( 'category_id=? AND record_approved=1 AND record_future_date=0', $this->id ),
							'record_last_comment DESC, primary_id_field DESC', /* Just in case RSS imports the exact same time */
							array( 0, 1 ),
							NULL,
							NULL,
							Db::SELECT_FROM_WRITE_SERVER
						)->first()
					);
				}

				if ( static::$latestRecordAdded->record_comments )
				{
					if ( static::$latestRecordAdded->record_comments AND ( $database->_comment_bump & Databases::BUMP_ON_COMMENT ) )
					{
						if ( static::$latestRecordAdded->useForumComments() )
						{
							$syncRecord = static::$latestRecordAdded;
							
							try
							{
								$comment = $syncRecord->comments( 1, 0, 'date', 'desc', NULL, FALSE );
							}
							catch( Exception $e ) { }
						}
						else
						{
							try
							{
								$comment = $commentClass::constructFromData( Db::i()->select( '*', 'cms_database_comments', array( 'comment_record_id=? AND comment_approved=1', Db::i()->select( 'primary_id_field', 'cms_custom_database_' . $this->database_id, array( 'category_id=? AND record_approved=1', $this->id ), 'record_last_comment DESC', 1 )->first() ), 'comment_date DESC', 1, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first() );
							}
							catch( UnderflowException $e ) { }
						}
					}

					if ( $comment and ( static::$latestRecordAdded->record_last_comment > $comment->mapped('date') ) )
					{
						$comment = NULL;

					}
				}
			}
			catch ( UnderflowException $e )
			{
				$this->last_record_date   = 0;
				$this->last_record_member = 0;
				$this->last_record_name = '';
				$this->last_title = NULL;
				$this->last_record_id = 0;
				$this->last_poster_anon = 0;
				return;
			}
		}

		if ( $comment !== NULL and ( $database->_comment_bump & Databases::BUMP_ON_COMMENT ) )
		{
			$this->last_record_date     = $comment->mapped('date');
			$this->last_record_member   = intval( $comment->author()->member_id );
			$this->last_record_name     = $comment->author()->member_id ? $comment->author()->name : NULL;
			$this->last_record_seo_name = Friendly::seoTitle( (string) $this->last_poster_name );
			$this->last_title           = mb_substr( $comment->item()->mapped('title'), 0, 255 );
			$this->last_seo_title       = Friendly::seoTitle( $this->last_title );
			$this->last_record_id       = $comment->item()->_id;
			$this->last_poster_anon		= $comment->isAnonymous();
		}
		else if ( static::$latestRecordAdded !== NULL )
		{
			$this->last_record_date     = static::$latestRecordAdded->record_saved;
			$this->last_record_member   = static::$latestRecordAdded->member_id;
			$this->last_record_name     = static::$latestRecordAdded->member_id ? static::$latestRecordAdded->record_last_comment_name : NULL;
			$this->last_title           = mb_substr( static::$latestRecordAdded->_title, 0, 255 );
			$this->last_seo_title       = Friendly::seoTitle( mb_substr( static::$latestRecordAdded->_title, 0, 255 ) );
			$this->last_record_id       = static::$latestRecordAdded->_id;
			$this->last_poster_anon		= static::$latestRecordAdded->isAnonymous();
		}
		
		$this->records        = Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $this->database_id, array( 'record_approved=1 AND record_future_date=0 AND category_id=?', $this->id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$this->records_queued = Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $this->database_id, array( 'record_approved=0 AND category_id=?', $this->id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$this->records_future = Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $this->database_id, array( 'record_future_date=1 AND category_id=?', $this->id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();

		static::$latestRecordAdded = NULL;
	}

	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @param   Member|null    $member         MemberObject
	 * @return	DateTime|NULL
	 */
	public function getLastCommentTime( Member $member = NULL ): ?DateTime
	{
		$member = $member ?: Member::loggedIn();
		if( !$this->can_view_others and !$member->modPermission( 'can_content_view_others_records' ) )
		{
			try
			{
				$select = Db::i()->select('record_last_comment', 'cms_custom_database_' . $this->database_id, array("record_future_date=0 AND category_id=? AND record_approved=1 AND member_id=?", $this->id, $member->member_id ), 'record_last_comment DESC', 1 )->first();
			}
			catch ( UnderflowException $e )
			{
				return NULL;
			}

			return $select ?  DateTime::ts( $select ) : NULL;
		}

		return $this->last_record_date ? DateTime::ts( $this->last_record_date ) : NULL;
	}
	
	/**
	 * Get last post data
	 *
	 * @return	array|NULL
	 */
	public function lastPost(): ?array
	{
		$result = NULL;
		$RecordsClass = static::$contentItemClass;
		/* @var $RecordsClass Records */
		/* This category does not allow you to see records from other users... */
		if( $this->can_view_others or Member::loggedIn()->modPermission( 'can_content_view_others_records' ) )
		{
			if ( $this->last_record_date )
			{
				try
				{
					$result = array( 'author' => Member::load( $this->last_record_member ), 'record_url' => $RecordsClass::load( $this->last_record_id )->url(), 'record_title' => $this->last_title, 'date' => $this->last_record_date );

					if ( !$this->last_record_member AND $this->last_record_name )
					{
						$result[ 'author' ]->name = $this->last_record_name;
					}
				}
				catch ( OutOfRangeException $e )
				{
				}
			}
		}
		else
		{
			try
			{
				$record = $RecordsClass::constructFromData( Db::i()->select('*', 'cms_custom_database_' . $this->database_id, array("record_future_date=0 AND category_id=? AND record_approved=1 AND member_id=?", $this->id, Member::loggedIn()->member_id), 'record_last_comment DESC', 1 )->first() );
				$result = array( 'author' => $record->author(), 'record_url' => $record->url(), 'record_title' => $record->_title, 'date' => $record->record_last_comment );
			}
			catch ( Exception $e )
			{
			}
		}

		foreach( $this->children() as $child )
		{
			if ( $childLastPost = $child->lastPost() )
			{
				if ( !$result or $childLastPost['date'] > $result['date'] )
				{
					$result = $childLastPost;
				}
			}
		}

		return $result;
	}

	/**
	 * Resets a folder path
	 *
	 * @return	void
	 */
	public function setFullPath() : void
	{
		$this->full_path = $this->furl_name;

		if ( $this->parent_id )
		{
			$parentId = $this->parent_id;
			$failSafe = 0;
			$path     = array();

			while( $parentId != 0 )
			{
				if ( $failSafe > 50 )
				{
					break;
				}

				try
				{
					$parent = static::load( $parentId );

					if ( ! $parent->furl_name )
					{
						$parent->furl_name = Friendly::seoTitle( $parent->name );
					}

					$parentId = $parent->parent_id;
					$path[]   = $parent->furl_name;
				}
				catch( OutOfRangeException $e )
				{
					break;
				}

				$failSafe++;
			}

			krsort( $path );
			$path[] = $this->furl_name;

			$this->full_path = trim( implode( '/', $path ), '/' );
		}

		$this->save();

		foreach ( $this->children( NULL ) as $child )
		{
			$child->setFullPath();
		}
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		/* For club categories, we don't want the permissions to be managed via the ACP */
		if ( $this->isClubCategory() )
		{
			if( Dispatcher::i()->controllerLocation != 'front' )
			{
				return false;
			}

			return $this->can( 'add' );
		}

		$can = parent::canManagePermissions();
		
		return ( $can === FALSE ) ? FALSE : (boolean) $this->has_perms;
	}
	
	/**
	 * Get which permission keys can access all records in a category which
	 * can normally only show records to the author
	 * 
	 * @return	array
	 */
	public function permissionsThatCanAccessAllRecords() : array
	{
		$normal		= $this->searchIndexPermissions();
		$return		= array();
		$members	= array();
		
		foreach ( Db::i()->select( '*', 'core_moderators' ) as $moderator )
		{
			if ( $moderator['perms'] === '*' or in_array( 'can_content_view_others_records', explode( ',', $moderator['perms'] ) ) )
			{
				if( $moderator['type'] === 'g' )
				{
					$return[] = $moderator['id'];
				}
				else
				{
					$members[] = "m{$moderator['id']}";
				}
			}
		}
		
		$return = ( $normal == '*' ) ? array_unique( $return ) : array_intersect( explode( ',', $normal ), array_unique( $return ) );
	
		if( count( $members ) )
		{
			$return = array_merge( $return, $members );
		}
		
		return $return;
	}
	
	/**
	 * Update search index permissions
	 *
	 * @return  void
	 */
	protected function updateSearchIndexPermissions(): void
	{
		if ( $this->can_view_others )
		{
			parent::updateSearchIndexPermissions();
		}
		else
		{
			$permissions = implode( ',', $this->permissionsThatCanAccessAllRecords() );
			Index::i()->massUpdate( 'IPS\cms\Records' . static::database()->_id, $this->_id, NULL, $permissions, NULL, NULL, NULL, NULL, NULL, TRUE );
			Index::i()->massUpdate( 'IPS\cms\Records\Comment' . static::database()->_id, $this->_id, NULL, $permissions, NULL, NULL, NULL, NULL, NULL, TRUE );
		}
	}
	
	/**
	 * Get the filter cookie for this category
	 *
	 * @return array|null
	 */
	public function getFilterCookie(): ?array
	{
		if ( isset( Request::i()->cookie['cms_filters'] ) )
		{
			$saved = json_decode( Request::i()->cookie['cms_filters'], TRUE );

			if ( array_key_exists( $this->id, $saved ) and count( $saved[ $this->id ] ) )
			{
				return $saved[ $this->id ];
			}
		}

		return NULL;
	}

	/**
	 * Save filter cookie for this category
	 *
	 * @param array|bool $values Filter values to save (array) or FALSE to remove cookie
	 * @return void
	 */
	public function saveFilterCookie( array|bool $values ): void
	{
		$cookie = ( isset( Request::i()->cookie['cms_filters'] ) ) ? json_decode( Request::i()->cookie['cms_filters'], TRUE ) : array();

		if ( $values === FALSE )
		{
			if ( array_key_exists( $this->id, $cookie ) )
			{
				unset( $cookie[ $this->id ] );
			}
		}
		else
		{
			/* We only want to include ones where we have actually specified values to filter on */
			$toSave = array();
			foreach( $values AS $key => $data )
			{
				if ( is_numeric( $key ) or $key == 'cms_record_i_started' )
				{
					$toSave[$key] = $data;
				}
			}
			
			$cookie[ $this->id ] = $toSave;
		}

		Request::i()->setCookie( 'cms_filters', json_encode( $cookie ), DateTime::create()->add( new DateInterval( 'P7D' ) ) );
	}

	/**
	 * Use the cookie to build filters
	 *
	 * @param array $cookie
	 * @return array
	 */
	public function buildWhereFromCookie( array $cookie ) : array
	{
		$where = [];
		$database = static::database();

		/** @var Fields $fieldClass */
		$fieldClass = 'IPS\cms\Fields' . $database->id;
		$customFields = $fieldClass::data( 'view', $this, $fieldClass::FIELD_SKIP_TITLE_CONTENT );

		foreach( $cookie as $f => $v )
		{
			if ( $f == 'cms_record_i_started' and Member::loggedIn()->member_id )
			{
				$where[] = array( 'cms_custom_database_' . $database->id . '.member_id=' . Member::loggedIn()->member_id );
				continue;
			}

			$k = 'content_field_' . $f;

			if ( $customFields[ $f ]->type === 'Member' )
			{
				if ( ! empty( $v ) )
				{
					$newValue = array();
					if ( is_array( $v ) )
					{
						foreach( $v as $m )
						{
							$member = Member::load( $m );
							$newValue[] = $member->member_id;
						}
					}
					else
					{
						$member = Member::load( $v );
						$newValue[] = $member->member_id;
					}

					$v = $newValue;
				}
				else
				{
					/* The content_field_x can be an empty array from the member form */
					continue;
				}
			}

			if ( isset( $customFields[ $f ] ) and $v !== '___any___' )
			{
				if ( is_array( $v ) )
				{
					if ( array_key_exists( 'start', $v ) or array_key_exists( 'end', $v ) )
					{
						$start = ( $v['start'] instanceof DateTime ) ? $v['start']->getTimestamp() : intval( $v['start'] );
						$end   = ( $v['end'] instanceof DateTime )   ? $v['end']->getTimestamp()   : intval( $v['end'] );

						if ( $start or $end )
						{
							$where[] = array( '( ' . mb_substr( $k, 8 ) . ' BETWEEN ' . $start . ' AND ' . $end . ' )' );
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

						if( $customFields[ $f ]->default_value and in_array( $customFields[ $f ]->default_value, $v ) )
						{
							$where[] = array( "( " . mb_substr( $k, 8 ) . " IS NULL OR " . Db::i()->findInSet( mb_substr( $k, 8 ), $like ) . ")" );
						}
						else
						{
							$where[] = array( Db::i()->findInSet( mb_substr( $k, 8 ), $like ) );
						}
					}
				}
				else
				{
					if ( is_bool( $v ) )
					{
						/* YesNo fields are false or true */
						if ( $v === false )
						{
							$where[] = array( '(' . mb_substr( $k, 8 ) . ' IS NULL or ' . mb_substr( $k, 8 ) . '=0)' );
						}
						else
						{
							$where[] = array( mb_substr( $k, 8 ) . "=1" );
						}
					}
					else
					{
						if ( $v !== 0 and ! $v )
						{
							$where[] = array( mb_substr( $k, 8 ) . " IS NULL" );
						}
						else
						{
							$where[] = array( mb_substr( $k, 8 ) . "=?", $v );
						}
					}
				}
			}
		}

		return $where;
	}

	/**
	 * [Node] Get meta description
	 *
	 * @return string|null
	 */
    public function metaDescription(): ?string
    {
        return $this->meta_description ?: static::database()->metaDescription();
    }

	/**
	 * [Node] Get meta title
	 *
	 * @return	string
	 */
	public function metaTitle(): string
	{
		return $this->page_title ?: static::database()->metaTitle();
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
		$_member = $member ?: Member::loggedIn();

		if ( !$_member->member_id and !$this->can_view_others )
		{
			return FALSE;
		}

		if ( $club = $this->_club )
		{
			if ( in_array( $permission, [ 'add', 'edit', 'delete', 'reply', 'review' ] ) and $_member instanceof Member )
			{
				if( in_array( $club->memberStatus( $_member ), [
					Club::STATUS_MEMBER,
					Club::STATUS_LEADER,
					Club::STATUS_MODERATOR
				] ) or $_member->modPermission('can_access_all_clubs' ) )
				{
					return parent::can( $permission, $_member, $considerPostBeforeRegistering );
				}

				return false;
			}

			if ( in_array( $permission, ['view', 'read'] ) )
			{
				/* Check the database permission first */
				if( !static::database()->can( $permission, $_member ) )
				{
					return false;
				}

				/* Site moderators can see everything */
				if ( $_member instanceof Member and $_member->modPermission('can_access_all_clubs') )
				{
					return TRUE;
				}

				/* If it's not approved, only moderators and the person who created it can see it */
				if ( Settings::i()->clubs_require_approval and !$club->approved and $_member instanceof Member )
				{
					return ( $_member->modPermission('can_access_all_clubs') or ( $club->owner AND $_member->member_id == $club->owner->member_id ) );
				}

				/* Owner or leader? */
				if ( $_member instanceof Member and ( $_member->member_id === $club->owner->member_id OR $club->memberStatus( $_member ) === Club::STATUS_LEADER ) )
				{
					return TRUE;
				}

				$viewGroups = explode( ',', $this->club_view_permission );

				/* Can literally anyone, guest included, view this and the club? */
				if ( $this->club_category_meta_index and $club->type !== Club::TYPE_PRIVATE and in_array( 'nonmember', $viewGroups ) )
				{
					return true;
				}

				/* Nonmembers */
				if ( in_array( 'nonmember', $viewGroups ) and $_member instanceof Member and $_member->member_id )
				{
					return true;
				}

				/* Moderators? */
				if ( in_array( 'moderator', $viewGroups ) AND $club->memberStatus( $_member ) === Club::STATUS_MODERATOR )
				{
					return TRUE;
				}

				/* Members */
				if (
					( mb_strpos( '*', $this->club_view_permission ) !== false or in_array( 'member', $viewGroups ) ) and
					in_array(
						$club->memberStatus( $_member ),
						[
							Club::STATUS_MEMBER,
							Club::STATUS_INVITED,
							Club::STATUS_INVITED_BYPASSING_PAYMENT,
							Club::STATUS_EXPIRED,
							Club::STATUS_EXPIRED_MODERATOR
						]
					)
				)
				{
					return TRUE;
				}
			}

			return false;
		}

		return parent::can( $permission, $member, $considerPostBeforeRegistering );
	}

	/**
	 * Search
	 *
	 * @param string $column	Column to search
	 * @param string $query	Search query
	 * @param string|null $order	Column to order by
	 * @param mixed $where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, ?string $order=NULL, mixed $where=array() ): array
	{
		$results = parent::search( $column, $query, $order, $where );

		return array_filter( $results, function( $node ){
			return $node->database_id == static::$customDatabaseId;
		} );
	}

	/**
	 * Get the properties that can be added to the datalayer for this key
	 *
	 * @return  array
	 */
	public function getDataLayerProperties(): array
	{
		if ( empty( $this->_dataLayerProperties ) )
		{
			$db = $this->database();

			if ( $db->use_categories )
			{
				$properties = parent::getDataLayerProperties();
				$properties['content_area'] = static::$contentArea;
				$properties['container_type'] = static::$containerType;
			}
			else
			{
				$properties = $db->getDataLayerProperties();
			}

			$this->_dataLayerProperties = $properties;
		}

		return $this->_dataLayerProperties;
	}

	/**
	 * See if this category belongs to a club
	 * @return bool
	 */
	public function isClubCategory() : bool
	{
		return ( static::database()->allow_club_categories and $this->club() );
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		if( $this->image )
		{
			return File::get( 'cms_Categories', $this->image );
		}

		return parent::primaryImage();
	}
}