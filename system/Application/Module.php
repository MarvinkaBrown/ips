<?php
/**
 * @brief		Module Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Application;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Node\Permissions;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Node class for Modules
 */
class Module extends Model implements Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_modules';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'sys_module_';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'sys_module_key' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
		
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'application';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\Application';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'applications_and_modules';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static ?string $databaseColumnEnabledDisabled = 'visible';
	
	/**
	* @brief	[Node] App for permission index
	*/
	public static ?string $permApp = 'core';
		
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'module';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'module_';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static ?array $restrictions = array( 'app' => 'core', 'module' => 'applications', 'all' => 'module_manage' );

	/**
	 * @brief	[Node] Sortable?
	 */
	public static bool $nodeSortable = FALSE;
	
	/**
	 * @brief	All modules
	 */
	protected static ?array $modules = NULL;

	/**
	 * @var bool
	 */
	public bool $_skipClearingMenuCache = false;

	/**
	 * Get Modules
	 *
	 * @return array
	 */
	public static function modules(): array
	{
		if( static::$modules === NULL )
		{
			static::$modules = array();
			foreach ( static::getStore() as $row )
			{
				static::$modules[ $row['sys_module_application'] ][ $row['sys_module_area'] ][ $row['sys_module_key'] ] = static::constructFromData( $row );
			}
		}
		
		return static::$modules;
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 * @note	Note that all records are returned, even disabled report rules. Enable status needs to be checked in userland code when appropriate.
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->modules ) )
		{
			Store::i()->modules = iterator_to_array( Db::i()->select( '*', 'core_modules', NULL, 'sys_module_position' )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=core_modules.sys_module_id", 'core', 'module' ) ) );
		}
		
		return Store::i()->modules;
	}
	
	/**
	 * Get a module
	 *
	 * @param string $app
	 * @param string $key
	 * @param string|null $area
	 * @return    Module
	 * @throws	OutOfRangeException
	 */
	public static function get( string $app, string $key, string $area=NULL ): Module
	{
		$modules = static::modules();
		if ( isset( $modules[ $app ] ) )
		{
			$area = $area ?: Dispatcher::i()->controllerLocation;
			if ( isset( $modules[ $app ][ $area ] ) )
			{
				if ( isset( $modules[ $app ][ $area ][ $key ] ) )
				{
					return $modules[ $app ][ $area ][ $key ];
				}
			}
		}
		
		throw new OutOfRangeException;
	}
	
	/**
	 * Set module as default for this area
	 *
	 * @return void
	 */
	public function setAsDefault() : void
	{
		Db::i()->update( 'core_modules', array( 'sys_module_default' => 0 ), array( 'sys_module_area=? AND sys_module_application=?', $this->area, $this->application ) );
		Db::i()->update( 'core_modules', array( 'sys_module_default' => 1 ), array( 'sys_module_id=?', $this->id ) );
		unset( Store::i()->modules );
	}
	
	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, string $order=NULL, mixed $where=array() ): array
	{
		if ( $column === '_title' )
		{
			$return = array();
			foreach( Member::loggedIn()->language()->words as $k => $v )
			{
				if ( preg_match( '/^module__([a-z]*)_([a-z]*)$/', $k, $matches ) and mb_strpos( mb_strtolower( $v ), mb_strtolower( $query ) ) !== FALSE )
				{
					try
					{
						$module = static::load( $matches[2], 'sys_module_key', count( $where ) ? array_merge( array( array( 'sys_module_application=? and sys_module_area=?', $matches[1], 'front' ) ), array( $where ) ) : array( array( 'sys_module_application=?', $matches[1] ) ) );
						$return[ $module->_id ] = $module;
					}
					catch ( OutOfRangeException $e ) { }
				}
			}
			return $return;
		}
		return parent::search( $column, $query, $order, $where );
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	* array(
	 		* array(
	 			* 'icon'	=>	array(
	 				* 'icon.png'			// Path to icon
	 				* 'core'				// Application icon belongs to
	 			* ),
	 			* 'title'	=> 'foo',		// Language key to use for button's title parameter
	 			* 'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			* 'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		* ),
	 		* ...							// Additional buttons
	 	* );
	 * @endcode
	 * @param Url $url Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ):array
	{
		$buttons = array();

		if( $this->canManagePermissions() )
		{
			$buttons['permissions'] = array(
				'icon'	=> 'lock',
				'title'	=> 'permissions',
				'link'	=> "{$url}&do=permissions&id={$this->_id}" . ( $subnode ? '&subnode=1' : '' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('permissions') )
			);
		}

		$buttons['default']	= array(
			'icon'		=> $this->default ? 'star' : 'regular fa-star',
			'title'		=> 'make_default_module',
			'link'		=> $url->csrf() . "&do=setDefaultModule&id={$this->_id}&default=1",
		);

		return $buttons;
	}
	
	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		$key = "module__{$this->application}_{$this->key}";
		return Member::loggedIn()->language()->addToStack( $key );
	}
	
	/**
	 * [Node] Get the title to store in the log
	 *
	 * @return	string|null
	 */
	public function titleForLog(): ?string
	{
		try
		{ 
			return Lang::load( Lang::defaultLanguage() )->get( "module__{$this->application}_{$this->key}" );
		}
		catch ( UnderflowException $e )
		{
			return $this->_title;
		}
	}
	
	/**
	 * [Node] Get Node Icon
	 *
	 * @return	string
	 */
	protected function get__icon(): mixed
	{
		return 'cube';
	}
			
	/**
	 * [Node] Get whether or not this node is locked to current enabled/disabled status
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__locked(): ?bool
	{
		return $this->protected;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add a child node?
	 *
	 * @return	bool
	 * @note	Modules don't really have "child nodes".  Controllers are not addable via the ACP.
	 */
	public function canAdd(): bool
	{
		return false;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions(): bool
	{
		if ( $this->protected )
		{
			return FALSE;
		}
		
		return parent::canManagePermissions();
	}

	/**
	 * [Node] Does this node have children?
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param bool $subnodes			Include subnodes?
	 * @param mixed $_where				Additional WHERE clause
	 * @return	bool
	 */
	public function hasChildren( ?string $permissionCheck='view', Member $member=NULL, bool $subnodes=TRUE, mixed $_where=array() ): bool
	{
		return false;
	}
	
	/**
	 * [Node] Fetch Child Nodes
	 *
	 * @param string|null $permissionCheck	The permission key to check for or NULL to not check permissions
	 * @param Member|null $member				The member to check permissions for or NULL for the currently logged in member
	 * @param bool|null $subnodes			Include subnodes?
	 * @param array|null $skip				Children IDs to skip
	 * @param mixed $_where				Additional WHERE clause
	 * @return	array
	 */
	public function children( ?string $permissionCheck='view', Member $member=NULL, bool|null $subnodes=TRUE, array $skip=null, mixed $_where=array() ): array
	{
		return array();
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void {}

	/**
	 * Save
	 *
	 * @return    void
	 */
	public function save(): void
	{
		$new = $this->_new;
		
		parent::save();

		$this->_skipClearingMenuCache = FALSE;
		
		if ( $new )
		{
			/* There is a unique constraint against app + perm_type + perm_type_id, so we use replace() instead of insert()
				in case there is already a row in the database for this constraint */
			Db::i()->replace( 'core_permission_index', array(
					'app'			=> 'core',
					'perm_type'		=> 'module',
					'perm_type_id'	=> $this->id,
					'perm_view'		=> '*',
			) );
		}
	}

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'modules' );
}