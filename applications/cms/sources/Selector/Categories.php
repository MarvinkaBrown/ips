<?php
/**
 * @brief		Categories Selector Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		20 July 2015
 */

namespace IPS\cms\Selector;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\cms\Pages\Page;
use IPS\Member;
use IPS\Node\Model;
use IPS\Node\Permissions;
use OutOfRangeException;
use function defined;

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
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'database_id';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\cms\Selector\Databases';
	
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
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'cms';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'categories';
	
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
	public static string $permissionLangPrefix = 'perm_content_';
	
	/**
	 * Get title of category
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		/* If the DB is in a page, and we're not using categories, then return the page title, not the category title for continuity */
		if ( ! Databases::load( $this->database_id )->use_categories )
		{
			if ( ! $this->_catTitle )
			{
				try
				{
					$page = Page::loadByDatabaseId( $this->database_id );
					$this->_catTitle = $page->_title;
				}
				catch( OutOfRangeException $e )
				{
					$this->_catTitle = parent::get__title();
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
	 * @brief 	[Records] Database objects
	 */
	protected static array $database = array();

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
	
}