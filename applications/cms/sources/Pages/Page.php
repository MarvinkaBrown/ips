<?php
/**
 * @brief		Page Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		15 Jan 2014
 */

namespace IPS\cms\Pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use ErrorException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\cms\Databases;
use IPS\cms\extensions\core\FrontNavigation\Pages;
use IPS\cms\Templates;
use IPS\Content\Search\Index;
use IPS\Content\ViewUpdates;
use IPS\core\extensions\core\FrontNavigation\Menu;
use IPS\core\FrontNavigation;
use IPS\core\Feature;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Front;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Widget\Area;
use LogicException;
use OutOfBoundsException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function mb_substr;
use function strstr;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Page Model
 */
class Page extends Model implements Permissions
{
	use ViewUpdates;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'frontNavigation' );

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_pages';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'page_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('page_seo_name', 'page_full_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'folder_id';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\cms\Pages\Folder';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnOrder = 'seo_name';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static bool $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Title
	 */
	public static string $nodeTitle = 'page';

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
			'module'	=> 'pages',
			'prefix' 	=> 'page_'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'cms';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'pages';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
			'view' => 'view'
	);
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_content_page_';
	
	/**
	 * @brief	[Page] Loaded pages from paths
	 */
	protected static array $loadedPagesFromPath = array();
	
	/**
	 * @brief	[Page] Currently loaded page
	 */
	public static ?Page $currentPage = NULL;

	/**
	 * @brief	[Page] Default page
	 */
	public static array $defaultPage = array();

	/**
	 * @brief	Pre save flag
	 */
	const PRE_SAVE  = 1;
	
	/**
	 * @brief	Post save flag
	 */
	const POST_SAVE = 2;

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->js_css_ids       = '';
		$this->content          = '';
		$this->meta_keywords    = '';
		$this->meta_description = '';
		$this->template         = '';
		$this->full_path        = '';
		$this->js_css_objects   = '';
		$this->meta_index		= TRUE;
		$this->ipb_wrapper		= true;
	}

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as the key
	 */
	public static ?string $titleLangPrefix = 'cms_page_';

	/**
	 * Load record based on a URL
	 *
	 * @param Url $url	URL to load from
	 * @return    Page
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl(Url $url ): mixed
	{
		$qs = array_merge( $url->hiddenQueryString, $url->queryString );

		if ( isset( $qs['id'] ) )
		{
			return static::load( $qs['id'] );
		}
		else if ( isset( $qs['path'] ) )
		{
			try
			{
				$return = static::load( $qs['path'], 'page_full_path' );
			}
			catch( OutOfRangeException $ex )
			{
				$return = static::loadFromPath( $qs['path'] );
			}	
			
			if ( method_exists( $return, 'can' ) )
			{
				if ( !$return->can( 'view' ) )
				{
					throw new OutOfRangeException;
				}
			}
			return $return;
		}
	
		throw new InvalidArgumentException;
	}
	
	/**
	 * Get the page based on the database ID
	 * 
	 * @param int $databaseId
	 * @return    static object
	 * @throws  OutOfRangeException
	 */
	public static function loadByDatabaseId( int $databaseId ) : static
	{
		return static::load( Databases::load( $databaseId )->page_id );
	}
	
	/**
	 * Resets a page path
	 *
	 * @param 	int 	$folderId	Folder ID to reset
	 * @return	void
	 */
	public static function resetPath( int $folderId ) : void
	{
		$path = $folderId ? Folder::load( $folderId )->path : '';
	
		$children = static::getChildren( $folderId );
	
		foreach( $children as $id => $obj )
		{
			$obj->setFullPath( $path );
		}
	}
	
	/**
	 * Get all children of a specific folder.
	 *
	 * @param	INT 	$folderId		Folder ID to fetch children from
	 * @return	array
	 */
	public static function getChildren( int $folderId=0 ) : array
	{
		$children = array();
		foreach( Db::i()->select( '*', static::$databaseTable, array( 'page_folder_id=?', $folderId ), 'page_seo_name ASC' ) as $child )
		{
			$children[ $child[ static::$databasePrefix . static::$databaseColumnId ] ] = static::load( $child[ static::$databasePrefix . static::$databaseColumnId ] );
		}
	
		return $children;
	}

	/**
	 * Returns a page object (or NULL) based on the path
	 * 
	 * @param	string	$path	Path /like/this/ok.html
	 * @return	NULL|Page object
	 */
	public static function loadFromPath( string $path ) : static|null
	{
		$path = trim( $path, '/' );
		
		if ( ! array_key_exists( $path, static::$loadedPagesFromPath ) )
		{
			static::$loadedPagesFromPath[ $path ] = NULL;
			
			/* Try the simplest option */
			try
			{
				static::$loadedPagesFromPath[ $path ] =  static::load( $path, 'page_full_path' );
			}
			catch ( OutOfRangeException $e )
			{
				/* Nope - try a folder */
				try
				{
					if ( $path )
					{
						$class  = static::$parentNodeClass;
						/* @var $class Model */
						$folder = $class::load( $path, 'folder_path' );
						
						static::$loadedPagesFromPath[ $path ] = static::getDefaultPage( $folder->id );
					}
					else
					{
						static::$loadedPagesFromPath[ $path ] = static::getDefaultPage();
					}
				}
				catch ( OutOfRangeException $e )
				{
					/* May contain a database path */
					if ( strstr( $path, '/' ) )
					{
						$bits = explode( '/', $path );
						$pathsToTry = array();
						
						while( count( $bits ) )
						{
							$pathsToTry[] = implode( '/', $bits );
							
							array_pop($bits);
						}
						
						try
						{
							static::$loadedPagesFromPath[ $path ] = static::constructFromData( Db::i()->select( '*', 'cms_pages', Db::i()->in( 'page_full_path', $pathsToTry ), 'page_full_path DESC' )->first() );
						}
						catch( UnderFlowException $e )
						{
							/* Last chance saloon */
							foreach( Db::i()->select( '*', 'cms_pages', array( '? LIKE CONCAT( page_full_path, \'%\')', $path ), 'page_full_path DESC' ) as $page )
							{
								if ( mb_stristr( $page['page_content'], '{database' ) )
								{
									static::$loadedPagesFromPath[ $path ] = static::constructFromData( $page );
									break;
								}
							}
							
							/* Still here? It's possible this is a legacy URL that starts with "page" - last ditch effort */
							if ( static::$loadedPagesFromPath[ $path ] === NULL AND mb_substr( $path, 0, 5 ) === 'page/' )
							{
								$pathWithoutPage = str_replace( 'page/', '', $path );
								
								try
								{
									/* Pass back recursively so we don't have to duplicate all of the checks again */
									static::$loadedPagesFromPath[ $path ] = static::loadFromPath( $pathWithoutPage );
								}
								catch( OutOfRangeException $e ) {}
							}
						}
					}
				}
			}
		}
		
		if ( static::$loadedPagesFromPath[ $path ] === NULL )
		{
			throw new OutOfRangeException;
		}

		return static::$loadedPagesFromPath[ $path ];
	}

	/**
	 * Load from path history so we can 301 to the correct record.
	 *
	 * @param	string		$slug			Thing that lives in the garden and eats your plants
	 * @param	string|NULL	$queryString	Any query string to add to the end
	 * @return    Url
	 */
	public static function getUrlFromHistory( string $slug, ?string $queryString=NULL ) : Url
	{
		$slug = trim( $slug, '/' );
		
		try
		{
			$row = Db::i()->select( '*', 'cms_url_store', array( 'store_type=? and store_path=?', 'page', $slug ) )->first();

			return static::load( $row['store_current_id'] )->url();
		}
		catch( UnderflowException $ex )
		{
			/* Ok, perhaps this is a full URL with the page name at the beginning */
			foreach( Db::i()->select( '*', 'cms_url_store', array( 'store_type=? and ? LIKE CONCAT( store_path, \'%\') OR store_path=?', 'page', $slug, $slug ) ) as $item )
			{
				$url = static::load( $item['store_current_id'] )->url();
				$url = $url->setPath( '/' . trim( str_replace( $item['store_path'], trim( $url->data['path'], '/' ), $slug ), '/' ) );
				if ( $queryString !== NULL )
				{
					$url = $url->setQueryString( $queryString );
				}
				return $url;
			}
			
			/* Still here? Ok, now we may have changed the folder name at some point, so lets look for that */
			foreach( Db::i()->select( '*', 'cms_url_store', array( 'store_type=? and ? LIKE CONCAT( store_path, \'%\') OR store_path=?', 'folder', $slug, $slug ) ) as $item )
			{
				try
				{
					$folder = Folder::load( $item['store_current_id'] );

					/* Attempt to build the new path */
					$newPath = str_replace( $item['store_path'], $folder->path, $slug );

					/* Do we have a page with this path? */
					try
					{
						return static::load( $newPath, 'page_full_path' )->url();
					}
					catch( OutOfRangeException $ex )
					{
						/* This is not the path you are looking for */
					}

				}
				catch( OutOfRangeException $ex )
				{
					/* This also is not the path you are looking for */
				}
			}
		}

		/* Still here? Consistent with AR pattern */
		throw new OutOfRangeException();
	}
	
	/**
	 * Return the default page for this folder
	 *
	 * @param	INT 	$folderId		Folder ID to fetch children from
	 * @return    static|null
	 */
	public static function getDefaultPage( int $folderId=0 ) : static|null
	{
		if ( ! isset( static::$defaultPage[ $folderId ] ) )
		{
			/* Try the easiest method first */
			try
			{
				static::$defaultPage[ $folderId ] = Page::load( Db::i()->select( 'page_id', static::$databaseTable, array( 'page_default=? AND page_folder_id=?', 1, $folderId ) )->first() );
			}
			catch( Exception $ex )
			{
				throw new OutOfRangeException;
			}

			/* Got a page called index? */
			if ( ! isset( static::$defaultPage[ $folderId ] ) )
			{
				foreach( static::getChildren( $folderId ) as $id => $obj )
				{
					if ( mb_substr( $obj->seo_name, 0, 5 ) === 'index' )
					{
						return $obj;
					}
				}
			}
		}

		return ( isset( static::$defaultPage[ $folderId ] ) ) ? static::$defaultPage[ $folderId ] : NULL;
	}
	
	/**
	 * Delete compiled versions
	 *
	 * @param 	int|array 	$ids	Integer ID or Array IDs to remove
	 * @return void
	 */
	public static function deleteCompiled( int|array $ids ) : void
	{
		if ( is_numeric( $ids ) )
		{
			$ids = array( $ids );
		}
	
		foreach( $ids as $id )
		{
			$functionName = 'content_pages_' .  $id;
			if ( isset( Store::i()->$functionName ) )
			{
				unset( Store::i()->$functionName );
			}
		}
	}

	/**
	 * Removes all include objects from all pages
	 *
	 * @param   Url|string|null     $url     				The URL to find and remove
	 * @param	NULL|int	$storageConfiguration	Delete the cached includes from an alternate storage configuration
	 * @note	This method is called by \IPS\cms\extensions\core\FileStorage\Pages.php during a move, and in that process we want to remove resources
	 	from the old storage configuration, not the new one (which is what happens when the configuration id is not passed in to \IPS\File and a move is in progress)
	 * @return void
	 */
	static public function deleteCachedIncludes( Url|string|null $url=NULL, ?int $storageConfiguration=NULL ) : void
	{
		/* Remove them all */
		if ( $url === NULL )
		{
            /* Remove from DB */
            if ( Db::i()->checkForTable( 'cms_pages' ) )
            {
                Db::i()->update( 'cms_pages', array( 'page_js_css_objects' => NULL ) );
            }
			/* Remove from file system */
			File::getClass( $storageConfiguration ?: 'cms_Pages' )->deleteContainer('page_objects');
		}
		else
		{
			$bits = explode( '/', (string ) $url );
			$name = array_pop( $bits );

			/* Remove selectively */
			foreach( Db::i()->select( '*', 'cms_pages', array( "page_js_css_objects LIKE '%" . Db::i()->escape_string( $name ) . "%'" ) ) as $row )
			{
				Db::i()->update( 'cms_pages', array( 'page_js_css_objects' => NULL ), array( 'page_id=?', $row['page_id'] ) );
			}
		}
	}

	/**
	 * Show a custom error page
	 *
	 * @param   string  $title          Title of the page
	 * @param	string	$message		language key for error message
	 * @param	mixed	$code			Error code
	 * @param	int		$httpStatusCode	HTTP Status Code
	 * @param	array	$httpHeaders	Additional HTTP Headers
	 * @return  void
	 */
	static public function errorPage( string $title, string $message, mixed $code, int $httpStatusCode, array $httpHeaders=array() ) : void
	{
		try
		{
			$page = static::load( Settings::i()->cms_error_page );
			$content = $page->getHtmlContent();
			$content = str_replace( '{error_message}', $message, $content );
			$content = str_replace( '{error_code}', $code, $content );
			
			/* Pages are compiled and cached, which we do not want for the error page as the {error_*} tags are saved with their text content */
			$functionName = 'content_pages_' .  $page->id;
			if ( isset( Store::i()->$functionName ) )
			{
				unset( Store::i()->$functionName );
			}
			
			$page->output( $title, $httpStatusCode, $httpHeaders, $content );
		}
		catch( Exception $ex )
		{
			if( $httpStatusCode !== 200 )
			{
				/* Unset page token */
				unset( Output::i()->jsVars['page_token'] );
			}
			Output::i()->sidebar['enabled'] = FALSE;
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( $title, Theme::i()->getTemplate( 'global', 'core' )->error( $title, $message, $code, NULL, Member::loggedIn() ), Dispatcher::i()->getLocationData() ), $httpStatusCode, 'text/html', $httpHeaders, FALSE );
		}
	}

	/**
	 * Form elements
	 *
	 * @param	Page|null		$item	Page object or NULL
	 * @return	array
	 */
	static public function formElements( ?Page $item=NULL ) : array
	{
		$return   = array();
		$return['tab_details'] = array( 'content_page_form_tab__details', NULL, NULL, 'ipsForm--horizontal' );

		$return['page_name'] = new Translatable( 'page_name', NULL, TRUE, array( 'app' => 'cms', 'key' => ( $item and $item->id ) ? "cms_page_" . $item->id : NULL, 'maxLength' => 64 ), function( $val )
		{
			if ( empty( $val ) )
			{
				throw new DomainException('form_required');
			}		
		}, NULL, NULL, 'page_name' );
		
		$return['page_seo_name'] = new Text( 'page_seo_name', $item ? $item->seo_name : '', FALSE, array( 'maxLength' => 255 ), function( $val ) use ( $item )
		{
			if ( empty( $val ) )
			{
				$val = Friendly::seoTitle( $val );
			}
			
			/* We cannot have a page name the same as a folder name in this folder */
			try
			{
				$testFolder = Folder::load( $val, 'folder_name' );

				/* Ok, we have a folder, but is it on the same tree as us ?*/
				if ( intval( Request::i()->page_folder_id ) == $testFolder->parent_id )
				{
					/* Yep, this will break designers' mode and may confuse the FURL engine so we cannot allow this */
					throw new InvalidArgumentException('content_folder_name_furl_collision_pages');
				}
			}
			catch ( OutOfRangeException $e )
			{
			}

			/* If we hit here, we don't have an existing name so that's good */
			if ( Page::isFurlCollision( $val ) )
			{
				throw new InvalidArgumentException('content_folder_name_furl_collision_pages_app');
			}

			try
			{
				$test = Db::i()->select( '*', 'cms_pages', [ 'page_seo_name=? and page_id <> ? and page_folder_id=?', $val, ( (int)$item?->_id ), Request::i()->page_folder_id ] )->first();
				throw new InvalidArgumentException( 'content_page_file_name_in_use' );
			}
			catch( UnderflowException )
			{
				return true;
			}
		}, NULL, NULL, 'page_seo_name' );

		$return['page_folder_id'] = new Node( 'page_folder_id', ( $item ? intval( $item->folder_id ) : ( ( isset( Request::i()->parent ) and Request::i()->parent ) ? Request::i()->parent : 0 ) ), FALSE, array(
				'class'         => 'IPS\cms\Pages\Folder',
				'zeroVal'       => 'node_no_parent',
				'subnodes'		=> false
		), NULL, NULL, NULL, 'page_folder_id' );

		$return['page_ipb_wrapper'] = new YesNo( 'page_ipb_wrapper', ( $item AND $item->id ) ? $item->ipb_wrapper : 1, TRUE, array(
				'togglesOff' => array( 'page_wrapper_template' )
		), NULL, NULL, NULL, 'page_ipb_wrapper' );

		$wrapperTemplates = array( '_none_' => Member::loggedIn()->language()->addToStack('cms_page_wrapper_template_none') );
		foreach( Templates::getTemplates( Templates::RETURN_PAGE + Templates::RETURN_DATABASE_AND_IN_DEV ) as $id => $obj )
		{
			/* @var Templates $obj */
			if ( $obj->isSuitableForCustomWrapper() )
			{
				$wrapperTemplates[ Templates::readableGroupName( $obj->group ) ][ $obj->group . '__' . $obj->title . '__' . $obj->key ] = Templates::readableGroupName( $obj->title );
			}
		}

		/* List of templates */
		$return['page_wrapper_template'] = new Select( 'page_wrapper_template', ($item?->wrapper_template), null, array(
			         'options' => $wrapperTemplates
		), function( $val ){
			if( $val == '_none_' and !Request::i()->page_ipb_wrapper )
			{
				throw new InvalidArgumentException('form_required');
			}
		}, NULL, Theme::i()->getTemplate( 'pages', 'cms', 'admin' )->previewTemplateLink(), 'page_wrapper_template' );

		if ( count( Theme::themes() ) > 1 )
		{
			$themes = array( 0 => 'cms_page_theme_id_default' );
			foreach ( Theme::themes() as $theme )
			{
				$themes[ $theme->id ] = $theme->_title;
			}

			$return['page_theme'] = new Select( 'page_theme', $item ? $item->theme : 0, FALSE, array( 'options' => $themes ), NULL, NULL, NULL, 'page_theme' );
		}

		/* Only show this dropdown if we are editing an existing page that already has a wrapper */
		if( $item and $item->template and $item->template != 'page_builder__single_column__page_page_builder_single_column' and $item->template != 'page_builder__single_column__page_builder_single_column' )
		{
			$builderTemplates = array( '' => Member::loggedIn()->language()->addToStack( 'cms_page_wrapper_template_none' ) );
			foreach( Templates::getTemplates( Templates::RETURN_PAGE + Templates::RETURN_DATABASE_AND_IN_DEV ) as $id => $obj )
			{
				if ( $obj->isSuitableForBuilderWrapper() )
				{
					$builderTemplates[ Templates::readableGroupName( $obj->group ) ][ $obj->group . '__' . $obj->title . '__' . $obj->key ] = Templates::readableGroupName( $obj->title );
				}
			}

			$return['page_template'] = new Select( 'page_template', $item->template, FALSE, array( 'options' => $builderTemplates ), NULL, NULL, NULL, 'page_template' );
		}

		/* Page CSS and JS */
		$js  = Templates::getTemplates( Templates::RETURN_ONLY_JS + Templates::RETURN_DATABASE_ONLY );
		$css = Templates::getTemplates( Templates::RETURN_ONLY_CSS + Templates::RETURN_DATABASE_ONLY );

		if ( count( $js ) OR count( $css ) )
		{
			$return['tab_js_css'] = array( 'content_page_form_tab__includes', NULL, NULL, 'ipsForm--horizontal ipsForm--page-includes' );
			$return['msg_js_css'] = array( 'cms_page_includes_message', 'ipsMessage ipsMessage_info ipsCmsIncludesMessage' );

			if ( count( $js ) )
			{
				$jsincludes = array();
				foreach( $js as $obj )
				{
					$jsincludes[ $obj->key ] = Templates::readableGroupName( $obj->group ) . '/' . Templates::readableGroupName( $obj->title );
				}
				ksort( $jsincludes );

				$return['page_includes_js'] = new CheckboxSet( 'page_includes_js', $item ? $item->js_includes : FALSE, FALSE, array( 'options' => $jsincludes, 'multiple' => true ), NULL, NULL, NULL, 'page_includes_js' );
			}

			if ( count( $css ) )
			{
				$cssincludes = array();
				foreach( $css as $obj )
				{
					$cssincludes[ $obj->key ] = Templates::readableGroupName( $obj->group ) . '/' . Templates::readableGroupName( $obj->title );
				}
				ksort( $cssincludes );
				
				$return['page_includes_css'] = new CheckboxSet( 'page_includes_css', $item ? $item->css_includes : FALSE, FALSE, array( 'options' => $cssincludes, 'multiple' => true ), NULL, NULL, NULL, 'page_includes_css' );
			}
		}
	
		$return['tab_meta'] = array( 'content_page_form_tab__meta', NULL, NULL, 'ipsForm--horizontal ipsForm--page-meta' );
		$return['page_title'] = new Text( 'page_title', $item ? $item->title : '', FALSE, array( 'maxLength' => 64 ), NULL, NULL, NULL, 'page_title' );
		$return['page_meta_description'] = new TextArea( 'page_meta_description', $item ? $item->meta_description : '', FALSE, array(), NULL, NULL, NULL, 'page_meta_description' );
		$return['page_meta_image'] = new Form\Upload( 'page_meta_image', ( $item AND $item->meta_image ) ? File::get( 'cms_PagesImages', $item->meta_image ) : null, false, [ 'storageExtension' => 'cms_PagesImages', 'image' => true, 'multiple' => false ], null, null, null, 'page_meta_image' );

		$disabledIndex = ( $item AND $item->id AND !$item->canView(new Member()));

		$return['page_meta_index'] = new YesNo('page_meta_index', !$disabledIndex ? ( $item ? $item->meta_index : TRUE ) : FALSE , FALSE, array( 'disabled' => $disabledIndex), NULL, NULL, NULL, 'page_meta_index' );

		if( $disabledIndex )
		{
			Member::loggedIn()->language()->words['page_meta_index_desc'] = Member::loggedIn()->language()->addToStack('page_meta_index_desc_disabled');
		}

		Output::i()->globalControllers[]  = 'cms.admin.pages.form';
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_pages.js', 'cms' ) );
		
		return $return;
	}
	
	/**
	 * Create a new page from a form. Pretty much what the function says.
	 * 
	 * @param	array		 $values	Array of form values
	 * @return    Page object
	 */
	static public function createFromForm( array $values ) : static
	{
		$page = new self;
		$page->type = 'builder';
		$page->save();

		$page->saveForm( $page->formatFormValues( $values ) );

		/* Set permissions */
		Db::i()->update( 'core_permission_index', array( 'perm_view' => '*' ), array( 'app=? and perm_type=? and perm_type_id=?', 'cms', 'pages', $page->id ) );
		
		return $page;
	}

	/**
	 * Ensure there aren't any collision issues when the CMS is the default app and folders such as "forums" are created when
	 * the forums app is installed.
	 *
	 * @param   string  $path   Path to check
	 * @return  boolean
	 */
	static public function isFurlCollision( string $path ) : bool
	{
		$path   = trim( $path , '/');
		$bits   = explode( '/', $path );
		$folder = $bits[0];
		
		/* Ensure we cannot have a structure that starts with core/interface as we have this partial URL blacklisted in \IPS\Text\Parser::safeIframeRegexp() */
		if ( mb_substr( $path, 0, 15 ) == 'core/interface/' )
		{
			return TRUE;
		}

		if ( mb_substr( $path, 0, 7 ) == 'static/' )
		{
			return TRUE;
		}
		
		/* Cannot have /page/ as it confuses SEO pagination */
		if ( mb_strstr( '/' . $path . '/', '/page/' ) )
		{
			return TRUE;
		}

		/* What about system folders? */
		try
		{
			$folder = Folder::load( Request::i()->page_folder_id );
			$base = ROOT_PATH . '/' . $folder->path . '/';
		}
		catch ( OutOfRangeException $e )
		{
			$base = ROOT_PATH . '/';
		}

		if( is_dir( $base . $path ) )
		{
			return TRUE;
		}

		if( Request::i()->page_folder_id  )
		{
			return FALSE;
		}

		$defaultApplication = Db::i()->select( 'app_directory', 'core_applications', 'app_default=1' )->first();

		foreach( Application::applications() as $key => $app )
		{
			if ( $app->directory === 'cms' )
			{
				continue;
			}

			$furlDefinitionFile = ROOT_PATH . "/applications/{$app->directory}/data/furl.json";
			if ( file_exists( $furlDefinitionFile ) )
			{
				$furlDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( $furlDefinitionFile ) ), TRUE );

				if ( isset( $furlDefinition['topLevel'] ) )
				{
					if ( $furlDefinition['topLevel'] == $folder )
					{
						return TRUE;
					}

					if ( isset( $furlDefinition['pages'] ) )
					{
						foreach( $furlDefinition['pages'] as $name => $data )
						{
							if ( isset( $data['friendly'] ) )
							{
								$furlBits = explode( '/', $data['friendly'] );

								if ( $furlBits[0] == $folder )
								{
									return TRUE;
								}
							}
						}
					}
				}
			}
		}
		
		/* Still here? Some apps use very loose matching, like calendar looks for {id}-{?} which may conflict with a page with a filename of 123-foo */
		try
		{
			$url = Url::createFromString( Url::baseUrl() . $path );
		
			if ( $url instanceof Friendly and $url->seoTemplate !== 'content_page_path' )
			{
				return TRUE;
			}
		}
		catch( Exception $ex )
		{
			/* If we get an error, then it cannot be a legitimate link */
		}

		return FALSE;
	}
	
	/**
	 * Delete all stored includes so they can be rebuilt on demand.
	 *
	 * @return	void
	 */
	public static function deleteCompiledIncludes() : void
	{
		Db::i()->update( 'cms_pages', array( 'page_js_css_objects' => NULL ) );
		Templates::deleteCompiledFiles();
	}
	
	/**
	 * Create a datastore object of page IDs and URLs.
	 *
	 * @return void
	 */
	public static function buildPageUrlStore() : void
	{
		/* This fails because hooks are not installed when this is attempted to build via admin/install */
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'setup' )
		{
			return;
		}
		
		/* This also fails if we're installing via ACP */
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'admin' and isset( Request::i()->do ) and Request::i()->do == 'install' )
		{
			return;
		}
		
		$store = array();
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'cms_pages' ), 'IPS\cms\Pages\Page' ) as $page )
		{
			$perms = $page->permissions();
			$store[ $page->id ] = array( 'url' => (string) $page->url(), 'perm' => $perms['perm_view'] );
		}
		
		Store::i()->pages_page_urls = $store;
	}
	
	/**
	 * Returns (and builds if required) the pages id => url datastore
	 *
	 * @return array
	 */
	public static function getStore(): array
	{
		if ( ! isset( Store::i()->pages_page_urls ) )
		{
			static::buildPageUrlStore();
		}
		
		return Store::i()->pages_page_urls;
	}

	/**
	 * Get page path, returning stripped path and current page number
	 *
	 * @param 	string		$path		Page path
	 * @return 	array					Current path, Current page number
	 */
	public static function getStrippedPagePath( string $path ): array
	{
		/* Have a bash at pagination as it's not like we've much else to do */
		$stripped = Friendly::stripPageComponent( '/' . trim( $path, '/' ) . '/' );

		if ( trim( $path, '/' ) != trim( $stripped, '/' ) )
		{
			if ( $stripped !== '/' )
			{
				$pageStuff = str_replace( ltrim( $stripped, '/' ), '', $path );
			}
			else
			{
				$pageStuff = $path;
			}
		}
		else
		{
			return array( $path, NULL );
		}

		$bomb = explode( '/', $pageStuff );
		if ( !empty( $bomb[1] ) )
		{
			return array( trim( $stripped, '/' ), $bomb[1] );
		}

		return array( $path, NULL );
	}
		
	/**
	 * Set JS/CSS include keys
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set__js_css_ids( array|string $value ) : void
	{
		$this->_data['js_css_ids'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get JS/CSS include keys
	 *
	 * @return	array|null
	 */
	protected function get__js_css_ids() : array|null
	{
		if ( $this->_data['js_css_ids'] !== NULL AND ! is_array( $this->_data['js_css_ids'] ) )
		{
			$this->_data['js_css_ids'] = json_decode( $this->_data['js_css_ids'], true );
		}

		return ( is_array( $this->_data['js_css_ids'] ) ) ? $this->_data['js_css_ids'] : array();
	}

	/**
	 * Get JS include keys
	 *
	 * @return	array
	 */
	protected function get_js_includes() : array
	{
		/* Makes sure js_css_ids is unpacked if required */
		$foo = $this->_js_css_ids;

		if ( isset( $this->_data['js_css_ids']['js'] ) )
		{
			return $this->_data['js_css_ids']['js'];
		}

		return array();
	}

	/**
	 * Get CSS include keys
	 *
	 * @return	array
	 */
	protected function get_css_includes() : array
	{
		/* Makes sure js_css_ids is unpacked if required */
		$foo = $this->_js_css_ids;

		if ( isset( $this->_data['js_css_ids']['css'] ) )
		{
			return $this->_data['js_css_ids']['css'];
		}

		return array();
	}

	/**
	 *  Get JS/CSS Objects
	 *
	 * @return array|null
	 */
	public function getIncludes() : array|null
	{
		$return = array( 'css' => NULL, 'js' => NULL );
		
		/* Empty? Lets take a look and see if we need to compile anything */
		if ( empty( $this->_data['js_css_objects'] ) )
		{
			/* Lock it up to prevent a race condition */
			if ( Theme::checkLock( "page_object_build" . $this->id ) )
			{
				return NULL;
			}

			Theme::lock( "page_object_build" . $this->id );
			
			if ( count( $this->js_includes ) )
			{
				/* Build a file object for each JS */
				foreach( $this->js_includes as $key )
				{
					try
					{
						$template = Templates::load( $key );
						$object   = $template->_file_object;

						$return['js'][ $key ] = $object;
					}
					catch( OutOfRangeException $e )
					{
						continue;
					}
				}
			}

			if ( count( $this->css_includes ) )
			{
				/* Build a file object for each JS */
				foreach( $this->css_includes as $key )
				{
					try
					{
						$template = Templates::load( $key );
						$object   = $template->_file_object;

						$return['css'][ $key ] = $object;
					}
					catch( Exception $e )
					{
						continue;
					}
				}
			}
			
			/* Save this to prevent it looking for includes on every page refresh */
			$this->js_css_objects = json_encode( $return );
			$this->save();
			
			Theme::unlock( "page_object_build" . $this->id );
		}
		else
		{
			$return = json_decode( $this->_data['js_css_objects'], TRUE );
		}

		foreach( $return as $type => $data )
		{
			if ( is_array( $data ) )
			{
				foreach( $data as $key => $object )
				{
					$return[ $type ][ $key ] = (string) File::get( 'cms_Pages', $object )->url;
				}
			}
		}

		return $return;
	}

	/**
	 * Get the content type of this page. Calculates based on page extension
	 *
	 * @return string
	 */
	public function getContentType() : string
	{
		$map  = array(
			'js'   => 'text/javascript',
			'css'  => 'text/css',
			'txt'  => 'text/plain',
			'xml'  => 'text/xml',
			'rss'  => 'text/xml',
			'html' => 'text/html',
			'json' => 'application/json'
		);

		$extension = mb_substr( $this->seo_name, ( mb_strrpos( $this->seo_name, '.' ) + 1 ) );

		if ( in_array( $extension, array_keys( $map ) ) )
		{
			return $map[ $extension ];
		}

		return 'text/html';
	}

	/**
	 * Return the title for the publicly viewable HTML page
	 * 
	 * @return string	Title to use between <title> tags
	 */
	public function getHtmlTitle() : string
	{
		if ( $this->title )
		{
			return $this->title;
		}
		
		if ( $this->_title )
		{
			return $this->_title;
		}
		
		return $this->name;
	}
	
	/**
	 * Return the content for the publicly viewable HTML page
	 * 
	 * @return	string	HTML to use on the page
	 */
	public function getHtmlContent() : string
	{
		$functionName = 'content_pages_' .  $this->id;

		if ( ! isset( Store::i()->$functionName ) )
		{
			Store::i()->$functionName = Theme::compileTemplate( $this->content, $functionName );
		}

		Theme::runProcessFunction( Store::i()->$functionName, $functionName );

		$themeFunction = 'IPS\\Theme\\'. $functionName;
		return $themeFunction();
	}

	/**
	 * Get an array containing all the areas in this page
	 *
	 * @param 	string|null 		$area		The area to filter by; by default (null) it will get all areas
	 *
	 * @return Area[]		Returns an array mapping the widget areas to the widgets in that area
	 */
	public function getAreasFromDatabase( ?string $area=null ) : array
	{
		$areas = array();
		$where = [ array( 'area_page_id=?', $this->id ) ];

		if ( $area !== null )
		{
			$where[] = [ 'area_area=?', $area ];
		}

		foreach( Db::i()->select( '*', 'cms_page_widget_areas', $where ) as $row )
		{
			if( $row['area_tree'] )
			{
				$areas[$row['area_area']] = new Area( json_decode( $row['area_tree'], true ), $row['area_area'] );
			}
			elseif( $row['area_widgets'] )
			{
				$areas[$row['area_area']] = Area::create( $row['area_area'], json_decode( $row['area_widgets'], true ) );
			}
		}

		return $areas;
	}

	/**
	 * Save an area to the database and link it to the page
	 *
	 * @param Area $area
	 * @return void
	 */
	public function saveArea( Area $area ) : void
	{
		/* If we have no content, clear this out entirely */
		if( !$area->hasWidgets() )
		{
			Db::i()->delete( 'cms_page_widget_areas', [ 'area_area=? and area_page_id=?', $area->id, $this->id ] );
			return;
		}

		/* Store the widget configuration */
		foreach( $area->getAllWidgets() as $widget )
		{
			if( isset( $widget['configuration'] ) AND !empty( $widget['configuration'] ) )
			{
				Db::i()->replace( 'core_widgets_config', [
					'id' => $widget['unique'],
					'data' => json_encode( $widget['configuration'] )
				] );
			}
		}

		/* Does the area exist? */
		try
		{
			$row = Db::i()->select( '*', 'cms_page_widget_areas', [ 'area_area=? and area_page_id=?', $area->id, $this->id ] )->first();

			Db::i()->update( 'cms_page_widget_areas', [
				'area_tree' => json_encode( $area->toArray( true, false ) )
			], ['area_area=? AND area_page_id=?', $area->id, $this->id ] );
		}
		catch( UnderflowException )
		{
			Db::i()->insert( 'cms_page_widget_areas', [
				'area_page_id' => $this->id,
				'area_area' => $area->id,
				'area_orientation' => $area->orientation(),
				'area_tree' => json_encode( $area->toArray( true, false ) )
			]);
		}
	}

	/**
	 * Once widget ordering has ocurred, post process if required
	 *
	 * @return void
	 */
	public function postSaveArea() : void
	{
		/* Check for database changes and update mapping if required */
		$databaseUsed = NULL;
		foreach( $this->getAreasFromDatabase() as $item )
		{
			$widgetsToRemove = [];
			foreach( $item->getAllWidgets() as $id => $pageBlock )
			{
				if( isset( $pageBlock['app'] ) and $pageBlock['app'] == 'cms' AND $pageBlock['key'] == 'Database' AND ! empty( $pageBlock['configuration']['database'] ) )
				{
					if ( $databaseUsed === NULL )
					{
						$databaseUsed = $pageBlock['configuration']['database'];
					}
					else
					{
						/* Already got a database, so remove this one */
						$widgetsToRemove[] = $pageBlock['unique'];
					}
				}
			}

			if( count( $widgetsToRemove ) )
			{
				foreach( $widgetsToRemove as $widgetId )
				{
					$item->removeWidget( $widgetId );
				}
				$this->saveArea( $item );
			}
		}

		if ( $databaseUsed === NULL and $this->type === 'html' )
		{
			$databaseUsed = $this->getDatabaseIdFromHtml();
		}

		if ( $databaseUsed !== NULL )
		{
			$this->mapToDatabase( intval( $databaseUsed ) );
		}
		else
		{
			try
			{
				$this->removeDatabaseMap();
			}
			catch( LogicException $e ) { }
		}
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 * @endcode
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );
		$return  = array();
		
		if ( isset( $buttons['add'] ) )
		{
			unset( $buttons['add'] );
		}
		
		if ( isset( $buttons['edit'] ) )
		{
			$return['builder'] = array(
					'icon'	   => 'magic',
					'title'    => 'content_launch_page_builder',
					'link'	   => $this->url()->setQueryString( array( '_blockManager' => 1 ) ),
					'target'   => '_blank'
			);
		}
		
		if ( isset( $buttons['edit'] ) )
		{
			/* Is this a database page? We don't allow editing from here, use the database URL instead */
			try
			{
				$db = Databases::load( $this->id, 'database_page_id' );
				$buttons['edit']['link'] = Url::internal( "app=cms&module=databases&controller=databases&do=form&id=" . $db->id )->setFragment( 'form_header_content_database_form_options_page' );
				$buttons['edit']['data'] = [];

				if( isset( $buttons['permissions'] ) )
				{
					$buttons['permissions']['link'] = Url::internal( "app=cms&module=databases&controller=databases&do=permissions&id=" . $db->id );
				}

				/* Remove the delete button here; we don't want someone doing this by mistake */
				if( isset( $buttons['delete'] ) )
				{
					unset( $buttons['delete'] );
				}
			}
			catch( OutOfRangeException )
			{
				$buttons['edit']['title'] = Member::loggedIn()->language()->addToStack('content_edit_page');
				$buttons['edit']['data']  = null;
			}
		}
	
		/* Re-arrange */
		if ( isset( $buttons['edit'] ) )
		{
			$return['edit'] = $buttons['edit'];
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'pages', 'page_edit' ) )
		{
			$return['default'] = array(
				'icon'	=> $this->default ? 'star' : 'regular fa-star',
				'title'	=> 'content_default_page',
				'link'	=> $url->setQueryString( array( 'id' => $this->id, 'subnode' => 1, 'do' => 'setAsDefault' ) )->csrf()
			);
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'pages', 'page_edit' ) and $this->type !== 'builder' )
		{
			$return['default_error'] = array(
				'icon'	=> Settings::i()->cms_error_page == $this->id ? 'exclamation-circle' : 'exclamation',
				'title'	=> Settings::i()->cms_error_page == $this->id ? 'content_remove_error_page' : 'content_default_error_page',
				'link'	=> $url->setQueryString( array( 'id' => Settings::i()->cms_error_page ? 0 : $this->id, 'subnode' => 1, 'do' => 'toggleDefaultError' ) )->csrf()
			);
		}

		$return['view'] = array(
			'icon'	   => 'search',
			'title'    => 'content_launch_page',
			'link'	   => $this->url(),
			'target'   => '_blank'
		);
		
		if ( isset( $buttons['permissions'] ) )
		{
			$return['permissions'] = $buttons['permissions'];
		}
		
		if ( isset( $buttons['delete'] ) )
		{
			$return['delete'] = $buttons['delete'];
		}
		
		return $return;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		/* We shut off the main class and add it per-tab to allow the content field to look good */
		$form->class = '';

		foreach( static::formElements( $this ) as $name => $field )
		{
			if ( is_array( $field ) )
			{
				if ( mb_substr( $name, 0, 4 ) === 'tab_' )
				{
					$form->addTab( ...$field );
				}
				else if ( mb_substr( $name, 0, 4 ) === 'msg_' )
				{
					$form->addMessage( ...$field );
				}
			}
			else
			{
				$form->add( $field );
			}
		}

		if ( ! $this->id )
		{
			$form->addTab( 'content_page_form_tab__menu', NULL, NULL, 'ipsForm--horizontal ipsForm--page-menu' );
			$toggles    = array( 'menu_manager_access_type', 'menu_parent' );
			$formFields = array();
			
			foreach( Pages::configuration( array() ) as $field )
			{
				if ( $field->name !== 'menu_content_page' )
				{
					$toggles[] = $field->name;
					$formFields[ $field->name ] = $field;
				}
			}
			$form->add( new YesNo( 'page_add_to_menu', FALSE, FALSE, array( 'togglesOn' => $toggles ) ) );
			
			$roots = array( '' => '' );
			foreach ( FrontNavigation::i()->roots() as $item )
			{
				if( $item instanceof Menu )
				{
					$roots[ $item->id ] = $item->title();
				}
			}
			$form->add( new Select( 'menu_parent', '*', false, array( 'options' => $roots ), NULL, NULL, NULL, 'menu_parent' ) );

			
			foreach( $formFields as $name => $field )
			{
				$form->add( $field );
			}
			
			$groups = array();
			foreach ( Group::groups() as $group )
			{
				$groups[ $group->g_id ] = $group->name;
			}
			$form->add( new Radio( 'menu_manager_access_type', 0, TRUE, array(
				'options'	=> array( 0 => 'menu_manager_access_type_inherit', 1 => 'menu_manager_access_type_override' ),
				'toggles'	=> array( 1 => array( 'menu_manager_access' ) )
			), NULL, NULL, NULL, 'menu_manager_access_type' ) );
			$form->add( new CheckboxSet( 'menu_manager_access', '*', NULL, array( 'multiple' => TRUE, 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'everyone', 'impliedUnlimited' => TRUE ), NULL, NULL, NULL, 'menu_manager_access' ) );
		}

		if ( $this->id )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'global', 'core', 'global' )->message( Member::loggedIn()->language()->addToStack('content_acp_page_builder_msg_edit', TRUE, array( 'sprintf' => array( $this->url() ) ) ), 'information', NULL, FALSE );
		}
		else
		{
			Output::i()->output .= Theme::i()->getTemplate( 'global', 'core', 'global' )->message( Member::loggedIn()->language()->addToStack('content_acp_page_builder_msg_new' ), 'information', NULL, FALSE );
		}

		if( $this->id )
		{
			$form->canSaveAndReload = true;
		}
		
		Output::i()->title  = $this->id ? Member::loggedIn()->language()->addToStack( 'content_editing_page', FALSE, array( 'sprintf' => array( $this->_title ) ) ) : Member::loggedIn()->language()->addToStack('content_add_page');
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$isNew = $this->_new;

		if ( ! $this->id )
		{
			$this->type = 'builder';
			$this->save();
		}

		if( isset( $values['page_name'] ) )
		{
			$_copied	= $values['page_name'];
			$values['page_seo_name'] = empty( $values['page_seo_name'] ) ? ( is_array( $_copied ) ? array_shift( $_copied ) : $_copied ) : $values['page_seo_name'];

			$bits = explode( '.', $values['page_seo_name'] );
			foreach( $bits as $i => $v )
			{
				$bits[ $i ] = Friendly::seoTitle( $v );
			}

			$values['page_seo_name'] = implode( '.', $bits );

			Lang::saveCustom( 'cms', "cms_page_" . $this->id, $values['page_name'] );
		}
		
		if ( isset( $values['page_folder_id'] ) AND !empty( $values['page_folder_id'] ) )
		{
			$values['page_folder_id'] = ( $values['page_folder_id'] instanceof Folder ) ? $values['page_folder_id']->id : 0;
		}

		if ( isset( $values['page_includes_js'] ) OR isset( $values['page_includes_css'] ) )
		{
			$includes = array();
			if ( isset( $values['page_includes_js'] ) )
			{
				$includes['js'] = $values['page_includes_js'];
			}

			if ( isset( $values['page_includes_css'] ) )
			{
				$includes['css'] = $values['page_includes_css'];
			}

			$this->_js_css_ids = $includes;

			/* Trash file objects to be sure */
			$this->js_css_objects = NULL;
			$values['js_css_objects'] = NULL;

			unset( $values['page_includes_js'], $values['page_includes_css'] );
		}

		/* Page filename changed? */
		if ( ! $isNew and $values['page_seo_name'] !== $this->seo_name )
		{
			$this->storeUrl();
		}

		$values['page_meta_image'] = ( isset( $values['page_meta_image'] ) and $values['page_meta_image'] instanceof File ) ? (string) $values['page_meta_image'] : null;

		/* Menu stuffs */
		if ( isset( $values['page_add_to_menu'] ) )
		{
			if( $values['page_add_to_menu'] )
			{
				$permission = $values['menu_manager_access'] == '*' ? '*' : implode( ',', $values['menu_manager_access'] );
				
				if ( $values['menu_manager_access_type'] === 0 )
				{
					$permission = '';
				}

				$save = array(
					'app'			=> 'cms',
					'extension'		=> 'Pages',
					'config'		=> '',
					'parent'		=> $values['menu_parent'] ?: 0,
					'permissions'   => $permission
				);
				
				try
				{
					$save['position'] = Db::i()->select( 'MAX(position)', 'core_menu', array( 'parent=?', Request::i()->parent ) )->first() + 1;
				}
				catch ( UnderflowException $e )
				{
					$save['position'] = 1;
				}
				
				$id = Db::i()->insert( 'core_menu', $save );
				
				$values = Pages::parseConfiguration( $values, $id );
				$config = array( 'menu_content_page' => $this->id );
				
				foreach( array( 'menu_title_page_type', 'menu_title_page' ) as $field )
				{
					if ( isset( $values[ $field ] ) )
					{
						$config[ $field ] = $values[ $field ];
					}
				}
				
				Db::i()->update( 'core_menu', array( 'config' => json_encode( $config ) ), array( 'id=?', $id ) );
			}

			unset( $values['page_add_to_menu'], $values['menu_title_page_type'], $values['menu_title_page'], $values['menu_parent'], $values['menu_manager_access'], $values['menu_manager_access_type'] );
		}

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
		$this->setFullPath( ( $this->folder_id ? Folder::load( $this->folder_id )->path : '' ) );
		$this->save();
		
		Index::i()->index( $this->item() );
	}

	/**
	 * Stores the URL so when its changed, the old can 301 to the new location
	 *
	 * @return void
	 */
	public function storeUrl() : void
	{
		Db::i()->insert( 'cms_url_store', array(
			'store_path'       => $this->full_path,
			'store_current_id' => $this->_id,
			'store_type'       => 'page'
		) );
	}

	/**
	 * Get the Database from the page
	 *
	 * @return null|Databases
	 */
	public function getDatabase() : Databases|null
	{
		try
		{
			return Databases::load( $this->id, 'database_page_id' );
		}
		catch( OutOfRangeException $e ) { }
	
		return null;
	}

	/**
	 * Get the database ID from the page content
	 *
	 * @return  int|null
	 */
	public function getDatabaseIdFromHtml() : int|null
	{
		if ( $this->type !== 'html' )
		{
			throw new LogicException('cms_page_not_html');
		}

		preg_match( '#{database="([^"]+?)"#', $this->content, $matches );

		if ( isset( $matches[1] ) )
		{
			if ( is_numeric( $matches[1] ) )
			{
				return intval( $matches[1] );
			}
			else
			{
				try
				{
					$database = Databases::load( $matches[1], 'database_key' );
					return $database->id;
				}
				catch( OutOfRangeException $ex )
				{
					return NULL;
				}
			}
		}

		return NULL;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get URL
	 * 
	 * @return Url|string|null object
	 */
	public function url(): Url|string|null
	{
		if( $this->_url === NULL )
		{
			if ( Application::load('cms')->default AND $this->default AND ! $this->folder_id )
			{
				/* No - that's easy */
				$this->_url = Url::internal( '', 'front' );
			}
			else
			{
				$this->_url = Url::internal( 'app=cms&module=pages&controller=page&path=' . $this->full_path, 'front', 'content_page_path', array( $this->full_path ) );
			}
		}

		return $this->_url;
	}
	
	/**
	 * Set Theme
	 *
	 * @return	void
	 */
	public function setTheme() : void
	{
		if ( $this->theme )
		{
			try
			{
				Theme::switchTheme( $this->theme );
			}
			catch ( Exception $e ) { }
		}
	}

	/**
	 * Map this database to a specific page
	 *
	 * @param   int $databaseId Page ID
	 * @return  boolean
	 * @throws  LogicException
	 */
	public function mapToDatabase( int $databaseId ) : bool
	{
		/* Ensure this page has an ID (as in, $page->save() has not been called yet on a new page) */
		if ( ! $this->id )
		{
			throw new LogicException('cms_err_page_id_is_empty');
		}
		try
		{
			/* is this page already in use */
			$database = Databases::load( $this->id, 'database_page_id' );

			if ( $database->id == $databaseId )
			{
				/* Nothing to update as this page is mapped to this database */
				return TRUE;
			}
			else
			{
				/* We're using another DB on this page */
				throw new LogicException('cms_err_db_already_on_page' );
			}
		}
		catch( OutOfRangeException $e )
		{
			/* We didn't load a database based on this page, so make sure the database we want isn't being used elsewhere */
			$database = Databases::load( $databaseId );

			if ( $database->page_id > 0 )
			{
				/* We're using another DB on this page */
				throw new LogicException('cms_err_db_in_use_other_page');
			}
			else
			{
				/* Ok here as this DB is not in use, and this page doesn't have a DB in use */
				$database->page_id = $this->id;
				$database->save();

				/* Restore content in the search index */
				Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\cms\Records' . $database->id ) );
				
				/* Restore content in social promote table */
				$class = 'IPS\cms\Records' . $database->id;
				Feature::changeHiddenByClass( new $class, FALSE );
			}

			return TRUE;
		}
	}

	/**
	 * Removes all mapped DBs for this page
	 *
	 * @return void
	 */
	public function removeDatabaseMap() : void
	{
		try
		{
			$database = Databases::load( $this->id, 'database_page_id' );
			$database->page_id = 0;
			$database->save();

			/* Remove from search */
			Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records' . $database->id );
			
			/* Remove content in social promote table */
			$class = 'IPS\cms\Records' . $database->id;
			Feature::changeHiddenByClass( new $class, TRUE );
		}
		catch( OutOfRangeException $ex )
		{
			/* Page was never mapped */
			throw new LogicException('cms_err_db_page_never_used');
		}
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		try
		{
			$this->removeDatabaseMap();
		}
		catch( LogicException $e )
		{

		}
		
		$delete = $this->getMenuItemIds();
		
		if ( count( $delete ) )
		{
			Db::i()->delete( 'core_menu', Db::i()->in( 'id', $delete ) );
		}

		/* Remove any widgets for this page */
		Db::i()->delete( 'cms_page_widget_areas', array( 'area_page_id=?', $this->id ) );
		
		parent::delete();
		
		Index::i()->removeFromSearchIndex( $this->item() );
	}
	
	/**
	 * Returns core_menu ids for all menu items associated with this page
	 *
	 * @return array
	 */
	public function getMenuItemIds() : array
	{
		$items = array();
		foreach( Db::i()->select( '*', 'core_menu', array( 'app=? AND extension=?', 'cms', 'Pages' ) ) as $item )
		{
			$json = json_decode( $item['config'], TRUE );
			
			if ( isset( $json['menu_content_page'] ) )
			{
				if ( $json['menu_content_page'] == $this->id )
				{
					$items[] = $item['id'];
				}
			}
		}
		
		return $items;
	}

	/**
	 * Set the permission index permissions
	 *
	 * @param	array	$insert	Permission data to insert
	 * @return  void
	 */
	public function setPermissions( array $insert ) : void
	{
		parent::setPermissions( $insert );
		static::buildPageUrlStore();
		
		/* Update perms if we have a child database */
		/* EME: We no longer need this because the permissions are set from the database and not the other way around */
		/*try
		{
			$database = Databases::load( $this->id, 'database_page_id' );

			foreach( Db::i()->select( '*', 'cms_database_categories', array( 'category_database_id=?', $database->id ) ) as $cat )
			{
				$class    = '\IPS\cms\Categories' . $database->id;
				$category = $class::constructFromData( $cat );

				Index::i()->massUpdate( 'IPS\cms\Records' . $database->id, $category->_id, NULL, $category->readPermissionMergeWithPage() );
				Index::i()->massUpdate( 'IPS\cms\Records\Comment' . $database->id, $category->_id, NULL, $category->readPermissionMergeWithPage() );
				Index::i()->massUpdate( 'IPS\cms\Records\Review' . $database->id, $category->_id, NULL, $category->readPermissionMergeWithPage() );
			}
		}
		catch( Exception $e ) { }*/
		
		Index::i()->index( $this->item() );
	}

	/**
	 * Save data
	 *
	 * @return void
	 */
	public function save(): void
	{
		if ( $this->id )
		{
			static::deleteCompiled( $this->id );
		}
		
		parent::save();
		
		static::buildPageUrlStore();
	}
		
	/**
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName() : string
	{
		return $this->seo_name;
	}

	/**
	 * Set default
	 *
	 * @return void
	 */
	public function setAsDefault() : void
	{
		Db::i()->update( 'cms_pages', array( 'page_default' => 0 ), array( 'page_folder_id=?', $this->folder_id ) );
		Db::i()->update( 'cms_pages', array( 'page_default' => 1 ), array( 'page_id=?', $this->id ) );
		
		static::buildPageUrlStore();
	}
	
	/**
	 * Resets a folder path
	 *
	 * @param	string	$path	Path to reset
	 * @return	void
	 */
	public function setFullPath( string $path ) : void
	{
		$this->full_path = trim( $path . '/' . $this->seo_name, '/' );
		$this->save();
	}

	/**
	 * Displays a page
	 *
	 * @param	string|NULL	$title			The Page title
	 * @param	int|NULL	$httpStatusCode	HTTP Status Code
	 * @param	array|NULL	$httpHeaders	Additional HTTP Headers
	 * @param	string|NULL	$content		Optional content to use. Useful if dynamic replacements need to be made at runtime
	 * @throws ErrorException
	 * @return  void
	 */
	public function output( ?string $title=NULL, ?int $httpStatusCode=NULL, ?array $httpHeaders=NULL, ?string $content=NULL ) : void
	{
		$includes = $this->getIncludes();

		if ( isset( $includes['js'] ) and is_array( $includes['js'] ) )
		{
			Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, array_values( $includes['js'] ) );
		}

		$this->setTheme();

		/* This has to be done after setTheme(), otherwise \IPS\Theme::switchTheme() can wipe out CSS includes */
		if ( isset( $includes['css'] ) and is_array( $includes['css'] ) )
		{
			Output::i()->cssFiles  = array_merge( Output::i()->cssFiles, array_values( $includes['css'] ) );
		}

		/* Meta tags */
		$this->setMetaTags();

		Output::i()->jsVars['pageID'] = $this->id;

		/* Display */
		if ( $this->ipb_wrapper )
		{
			$nav = array();
			Output::i()->title  = $this->getHtmlTitle();

			/* Populate \IPS\Output::i()->sidebar['widgets'] sidebar/header/footer widgets */
			foreach( $this->getAreasFromDatabase() as $area )
			{
				/* Make sure to set the global areas (header, sidebar, footer, global footer) */
				if( in_array( $area->id, Area::$reservedAreas ) )
				{
					Output::i()->sidebar['widgetareas'] = Output::i()->sidebar['widgetareas'] ?? [];
					Output::i()->sidebar['widgetareas'][$area->id] = $area;
					Output::i()->sidebar['widgets'][$area->id] = [];
				}
			}

			/* Make sure we initialize the main area */
			Output::i()->sidebar['widgets']['col1'] = [];

			Output::i()->output = Theme::i()->getTemplate( 'pages' )->globalWrap( $nav, $this->getPageContent(), $this );

			if ( isset( Settings::i()->cms_error_page ) and Settings::i()->cms_error_page and Settings::i()->cms_error_page == $this->id )
			{
				Output::i()->sidebar['enabled'] = false;
			}

			try
			{
				$database = Databases\Dispatcher::i()->databaseId ? Databases::load( Databases\Dispatcher::i()->databaseId ) : null;
				if ( !$database or !$database->allow_club_categories )
				{
					throw new OutOfRangeException;
				}
			}
			catch ( OutOfRangeException | OutOfBoundsException )
			{
				if ( !( Application::load( 'cms' )->default and !$this->folder_id and $this->default ) )
				{
					Output::i()->breadcrumb['module'] = array( $this->url(), $this->_title );
				}
			}
			
			Output::i()->pageName = $this->full_path;

			if ( isset( Settings::i()->cms_error_page ) and Settings::i()->cms_error_page and Settings::i()->cms_error_page == $this->id )
			{
				/* Set the title */
				Output::i()->title = ( $title ) ?: $this->getHtmlTitle();
				Output::i()->output = $content ?: $this->getHtmlContent();
				Member::loggedIn()->language()->parseOutputForDisplay( Output::i()->title );

				/* Send straight to the output engine */
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ), ( $httpStatusCode ?: 200 ), 'text/html', ( $httpHeaders ?: array() ) );
			}
			else
			{
				Output::i()->allowDefaultWidgets = FALSE;
			}
		}
		else
		{
			/* Global Meta tags */
			Output::i()->buildMetaTags();

			/* Set up the content */
			$content = Theme::i()->getTemplate( 'pages', 'cms' )->globalWrap( null, $content ?: $this->getPageContent(), $this );

			/* Ensure MFA pop up shows */
			$content .= ( Front::i()->checkMfa( TRUE ) ?: '' );
			if ( $this->wrapper_template and $this->wrapper_template !== '_none_' and ! Request::i()->isAjax() )
			{
				try
				{
					[ $group, $name, $key ] = explode( '__', $this->wrapper_template );
					Output::i()->sendOutput( \IPS\cms\Theme::i()->getTemplate($group, 'cms', 'page')->$name( $content, $this->getHtmlTitle() ), 200, $this->getContentType() );
				}
				catch( OutOfRangeException $e ){}
			}

			Output::i()->sidebar['enabled'] = false;

			/* Set the title */
			Output::i()->title = ( $title ) ?: $this->getHtmlTitle();
			Member::loggedIn()->language()->parseOutputForDisplay( Output::i()->title );

			/* Send straight to the output engine */
			Output::i()->sendOutput( $content, ( $httpStatusCode ?: 200 ), $this->getContentType(), ( $httpHeaders ?: array() ) );
		}
	}

	/**
	 * Build the page content from the widgets
	 *
	 * @return string
	 */
	protected function getPageContent() : string
	{
		$widgets = [];

		foreach( Db::i()->select( '*', 'cms_page_widget_areas', [ 'area_page_id=?', $this->id ] ) as $item )
		{
			/* If we don't have a template, skip any reserved areas, those would be picked up earlier */
			if( !$this->template and in_array( $item['area_area'], Area::$reservedAreas ) )
			{
				continue;
			}

			$tree = $item['area_tree'] ? json_decode( $item['area_tree'], true ) : [];
			if ( empty( $tree ) )
			{
				/* If the tree is empty, generate one from the database rows */
				$area = Area::create( $item['area_area'], json_decode( $item['area_widgets'], true ) );
				$this->saveArea( $area );
			}
			else
			{
				$area = new Area( $tree, $item['area_area'] );
			}

			$widgets[ $item['area_area'] ] = (string) $area;
		}

		if( $this->template )
		{
			[ $group, $name, $key ] = explode( '__', $this->template );
			return \IPS\cms\Theme::i()->getTemplate( $group, 'cms', 'page' )->$name( $this, $widgets );
		}

		/* If we don't have a main area, just init with an empty area */
		if( !array_key_exists( 'col1', $widgets ) )
		{
			$widgets['col1'] = new Area( [], 'col1' );
		}

		return Theme::i()->getTemplate( 'pages', 'cms' )->mainArea( $this, $widgets );
	}

	/**
	 * Set meta tags for this page based on page properties
	 *
	 * @return void
	 */
	protected function setMetaTags() : void
	{
		/* Set the meta tags, but do not reset them if they are already set - articles can define custom meta tags and this code
				overwrites the ones set by articles if we don't verify they aren't set first */
		if ( $this->meta_description AND ( !isset( Output::i()->metaTags['description'] ) OR !Output::i()->metaTags['description'] ) )
		{
			Output::i()->metaTags['description'] = $this->meta_description;
			Output::i()->metaTags['og:description'] = $this->meta_description;
		}

		if( $this->meta_image AND ( !isset( Output::i()->metaTags['og:image'] ) ) )
		{
			try
			{
				Output::i()->metaTags['og:image'] = (string) ( File::get( 'cms_PagesImages', $this->meta_image )->url );
			}
			catch( Exception ){}
		}

		/* If this is a default page, we may be accessing this from the folder only. The isset() check is to ensure canonical
			tags for more specific things (like databases) are not overridden. */
		if ( !isset( Output::i()->linkTags['canonical'] ) )
		{
			Output::i()->linkTags['canonical'] = (string) $this->url();
		}

		if ( !isset( Output::i()->metaTags['og:url'] ) )
		{
			Output::i()->metaTags['og:url'] = (string) $this->url();
		}

		if ( !isset( Output::i()->metaTags['og:title'] ) )
		{
			Output::i()->metaTags['og:title'] = Output::i()->title;
		}

		if ( !isset( Output::i()->metaTags['og:type'] ) )
		{
			Output::i()->metaTags['og:type'] = 'website';
		}

		if( !$this->meta_index )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}
	}

	/**
	 * Get item
	 *
	 * @return	PageItem
	 */
	public function item(): PageItem
	{
		$data = array();
		foreach ( $this->_data as $k => $v )
		{
			$data[ 'page_' . $k ] = $v; 
		}
		
		return PageItem::constructFromData( $data );
	}
}