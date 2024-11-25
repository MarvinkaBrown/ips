<?php
/**
* @brief		Pages Controller
* @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
* @copyright	(c) Invision Power Services, Inc.
* @license		https://www.invisioncommunity.com/legal/standards/
* @package		Invision Community
* @subpackage	Content
* @since		15 Jan 2013
* @version		SVN_VERSION_NUMBER
*/

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Pages\Folder;
use IPS\cms\Pages\Page;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Badge;
use IPS\Helpers\Badge\Icon;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Tree\Tree;
use IPS\Http\url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function strpos;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
exit;
}

/**
* Page management
*/
class pages extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\cms\Pages\Folder';
	
	/**
	 * Store the database page map to prevent many queries
	 */
	protected static ?array $pageToDatabaseMap = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'page_manage' );

		static::$pageToDatabaseMap = iterator_to_array( Db::i()->select( 'database_id, database_page_id', 'cms_databases', array( 'database_page_id > 0' ) )->setKeyField('database_page_id')->setValueField('database_id') );

		parent::execute();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		return [];
	}

	/**
	 * Show the pages tree
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$url = Url::internal( "app=cms&module=pages&controller=pages" );
		
		/* Display the table */
		Output::i()->title  = Member::loggedIn()->language()->addToStack('menu__cms_pages_pages');
		Output::i()->output = new Tree( $url, 'menu__cms_pages_pages',
			/* Get Roots */
			function () use ( $url )
			{
				$data = pages::getRowsForTree( 0 );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					$rows[ $id ] = ( $row instanceof Page ) ? pages::getPageRow( $row, $url ) : pages::getFolderRow( $row, $url );
				}

				return $rows;
			},
			/* Get Row */
			function ( $id, $root ) use ( $url )
			{
				if ( $root )
				{
					return pages::getFolderRow( Folder::load( $id ), $url );
				}
				else
				{
					return pages::getPageRow( Page::load( $id ), $url );
				}
			},
			/* Get Row Parent ID*/
			function ()
			{
				return NULL;
			},
			/* Get Children */
			function ( $id ) use ( $url )
			{
				$rows = array();
				$data = pages::getRowsForTree( $id );

				if ( ! isset( Request::i()->subnode ) )
				{
					foreach ( $data as $id => $row )
					{
						$rows[ $id ] = ( $row instanceof Page ) ? pages::getPageRow( $row, $url ) : pages::getFolderRow( $row, $url );
					}
				}
				return $rows;
			},
           array( $this, '_getRootButtons' ),
           TRUE,
           FALSE,
           FALSE
		);
		
		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'pages', 'page_add' )  )
		{
			Output::i()->sidebar['actions']['add_folder'] = array(
				'primary'	=> true,
				'icon'	=> 'folder-open',
				'title'	=> 'content_add_folder',
				'link'	=> Url::internal( 'app=cms&module=pages&controller=pages&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('content_add_folder') )
			);

			Output::i()->sidebar['actions']['add_page'] = array(
				'primary'	=> true,
				'icon'	=> 'plus-circle',
				'title'	=> 'content_add_page',
				'link'	=>  Url::internal( 'app=cms&module=pages&controller=pages&subnode=1&do=form&subnode=1' )
			);
		}
	}
	
	/**
	 * Download .htaccess file
	 *
	 * @return	void
	 */
	protected function htaccess() : void
	{
		$dir = str_replace( 'admin/index.php', '', $_SERVER['PHP_SELF'] );
		$dirs = explode( '/', trim( $dir, '/' ) );
		
		if ( count( $dirs ) )
		{
			array_pop( $dirs );
			$dir = implode( '/', $dirs );
			
			if ( ! $dir )
			{
				$dir = '/';
			}
		}
		
		$path = $dir . 'index.php';
		
		if( strpos( $dir, ' ' ) !== FALSE )
		{
			$dir = '"' . $dir . '"';
			$path = '"' . $path . '"';
		}


		$htaccess = <<<FILE
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$dir}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \\.(js|css|jpeg|jpg|gif|png|ico)(\\?|$) - [L,NC,R=404]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$path} [L]
</IfModule>
FILE;

		Output::i()->sendOutput( $htaccess, 200, 'application/x-htaccess', array( 'Content-Disposition' => 'attachment; filename=.htaccess' ) );
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		/* If this is a database page, redirect to database form */
		if( isset( static::$pageToDatabaseMap[ Request::i()->id ] ) )
		{
			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=databases&do=form&id=" . static::$pageToDatabaseMap[ Request::i()->id ] ) );
		}

		parent::form();
	}

	/**
	 * Permissions
	 *
	 * @return	void
	 */
	protected function permissions() : void
	{
		/* If this is a database page, redirect to database permissions */
		if( isset( static::$pageToDatabaseMap[ Request::i()->id ] ) )
		{
			Output::i()->redirect( Url::internal( "app=cms&module=databases&controller=databases&do=permissions&id=" . static::$pageToDatabaseMap[ Request::i()->id ] ) );
		}

		parent::permissions();
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		/* Check if we are working with a page or a folder */
		if( isset( Request::i()->subnode ) and Request::i()->subnode )
		{
			/* Database pages cannot be deleted from here */
			if( isset( static::$pageToDatabaseMap[ Request::i()->id ] ) )
			{
				Output::i()->error( Member::loggedIn()->language()->addToStack( 'content_acp_err_page_db_delete' ), '3T260/1', 403 );
			}

			if ( isset( Request::i()->id ) )
			{
				Session::i()->csrfCheck();
				Page::deleteCompiled( Request::i()->id );
			}
		}

		parent::delete();
	}

	/**
	 * Set as default page for this folder
	 *
	 * @return void
	 */
	protected function setAsDefault() : void
	{
		Session::i()->csrfCheck();
		Page::load( Request::i()->id )->setAsDefault();
		Output::i()->redirect( Url::internal( "app=cms&module=pages&controller=pages" ), 'saved' );
	}

	/**
	 * Set as default error page
	 *
	 * @return void
	 */
	protected function toggleDefaultError() : void
	{
		Session::i()->csrfCheck();
		Settings::i()->changeValues( array( 'cms_error_page' => Request::i()->id ? Request::i()->id : NULL ) );
		Output::i()->redirect( Url::internal( "app=cms&module=pages&controller=pages" ), 'saved' );
	}

	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = array();
		$url  = Url::internal( "app=cms&module=pages&controller=pages" );

		/* Get results */
		$folders = Folder::search( 'folder_name'  , Request::i()->input, 'folder_name' );
		$pages   = Page::search( 'page_seo_name', Request::i()->input, 'page_seo_name' );

		$results =  Folder::munge( $folders, $pages );

		/* Convert to HTML */
		foreach ( $results as $id => $result )
		{
			$rows[ $id ] = ( $result instanceof Page ) ? $this->getPageRow( $result, $url ) : $this->getFolderRow( $result, $url );
		}

		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * Return HTML for a page row
	 *
	 * @param Page $page Row data
	 * @param url $url \IPS\Http\Url object
	 * @return    string    HTML
	 */
	public static function getPageRow( Page $page, Url $url ): string
	{
		$badge = NULL;
		$description = "";
		
		if ( isset( static::$pageToDatabaseMap[ $page->id ] ) )
		{
			$badge = array( 0 => 'style7', 1 => Member::loggedIn()->language()->addToStack( 'page_database_display', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack('content_db_' . static::$pageToDatabaseMap[ $page->id ] ) ) ) ) );

			$description = "<i class='fa-solid fa-triangle-exclamation i-margin-end_icon'></i> " .
				Member::loggedIn()->language()->addToStack( 'page_database_edit_message', true, array(
					'sprintf' => array( Url::internal( "app=cms&module=databases&controller=databases&do=form&id=" . static::$pageToDatabaseMap[ $page->id ] ) )
				) );
		}
		return Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $page->id, $page->seo_name, false, $page->getButtons( \IPS\Http\Url::internal('app=cms&module=pages&controller=pages'), true ), Member::loggedIn()->language()->addToStack( 'num_views_with_number', NULL, array( 'pluralize' => array( $page->views ) ) ), 'file-text-o', NULL, FALSE, NULL, NULL, $badge, FALSE, FALSE, FALSE );

	}

	/**
	 * Return HTML for a folder row
	 *
	 * @param Folder $folder	Row data
	 * @param Url $url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getFolderRow( Folder $folder, Url $url ): string
	{
		return Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $folder->id, $folder->name, true, $folder->getButtons( Url::internal('app=cms&module=pages&controller=pages') ),  "", 'folder', NULL );
	}

	/**
	 * Fetch rows of folders/pages
	 *
	 * @param int $folderId		Parent ID to fetch from
	 * @return array
	 */
	public static function getRowsForTree( int $folderId=0 ) : array
	{
		try
		{
			if ( $folderId === 0 )
			{
				$folders = Folder::roots();
			}
			else
			{
				$folders = Folder::load( $folderId )->children( NULL, NULL, FALSE );
			}
		}
		catch( OutOfRangeException $ex )
		{
			$folders = array();
		}

		$pages   = Page::getChildren( $folderId );

		return Folder::munge( $folders, $pages );
	}

	/**
	 * Redirect after save
	 *
	 * @param Model|null $old A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new The node now
	 * @param string|bool $lastUsedTab The tab last used in the form
	 * @return    void
	 */
	protected function _afterSave( ?Model $old, Model $new, mixed $lastUsedTab=FALSE ): void
	{
		/* Is there a default page in the folder? */
		try
		{
			$existingDefault = Db::i()->select( 'page_id', 'cms_pages', array( 'page_folder_id=? and page_default=? and page_id <> ?', $new->folder_id, 1, $new->id ) )->first();

			/* If this page was the default in a folder, and it was moved to a new folder that already has a default, we need to unset the
			default page flag or there will be two defaults in the destination folder */
			if( $old !== NULL AND $old->folder_id != $new->folder_id AND $old->default )
			{
				Db::i()->update( 'cms_pages', array( 'page_default' => 0 ), array( 'page_id=?', $new->id ) );

				Page::buildPageUrlStore();
			}
		}
		catch( UnderflowException $e )
		{
			/* No default found in destination folder, make this the default */
			Db::i()->update( 'cms_pages', array( 'page_default' => 1 ), array( 'page_id=?', $new->id ) );

			Page::buildPageUrlStore();
		}
		
		/* If page filename changes or the folder ID changes, we need to clear front navigation cache*/
		if( $old !== NULL AND ( $old->folder_id != $new->folder_id OR $old->seo_name != $new->seo_name ) )
		{
			unset( Store::i()->pages_page_urls );
		}

		parent::_afterSave( $old, $new, $lastUsedTab );
	}
}