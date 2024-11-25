<?php
/**
 * @brief		[Front] Page Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		25 Feb 2014
 */

namespace IPS\cms\modules\front\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases\Dispatcher;
use IPS\cms\Pages\Page as PageClass;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Log;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use OutOfRangeException;
use ParseError;
use function defined;
use function func_get_args;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class page extends Controller
{
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
	 * Display a page. Sounds simple doesn't it? Well it's not.
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		$page = $this::getPage();
		
		/* Database specific checks */
		if ( isset( Request::i()->advancedSearchForm ) AND isset( Request::i()->d ) )
		{
			/* showTableSearchForm just triggers __call which returns the database dispatcher HTML as we
			 * do not want the page content around the actual database */
			Output::i()->output = $this->showTableSearchForm();
			return;
		}

		if ( Request::i()->path == $page->full_path )
		{
			/* Are we using Friendly URL's at all? */
			if ( Settings::i()->use_friendly_urls )
			{
				/* Did we have a trailing slash? */
				if ( Settings::i()->htaccess_mod_rewrite and mb_substr( Request::i()->url()->data[ Url::COMPONENT_PATH ], -1 ) != '/' )
				{
					$url = $page->url();
					
					foreach( Request::i()->url()->queryString as $k => $v )
					{
						$url = $url->setQueryString( $k, $v );
					}
					
					if ( ! empty( Request::i()->url()->fragment ) )
					{
						$url = $url->setFragment( Request::i()->url()->fragment );
					}
	
					Output::i()->redirect( $url );
				}
				else if ( ! Settings::i()->htaccess_mod_rewrite and ! mb_strstr( Request::i()->url()->data[ Url::COMPONENT_QUERY ], $page->url()->data[ Url::COMPONENT_QUERY ] ) )
				{
					$url = $page->url();
					
					foreach( Request::i()->url()->queryString as $k => $v )
					{
						if ( mb_substr( $k, 0, 1 ) == '/' and mb_substr( $k, -1 ) != '/' )
						{
							$k .= '/';
						}
					}
					
					$url = $url->setQueryString( $k, $v );
					
					if ( ! empty( Request::i()->url()->fragment ) )
					{
						$url = $url->setFragment( Request::i()->url()->fragment );
					}
	
					Output::i()->redirect( $url );
				}
			}

			/* Just viewing this page, no database categories or records */
			$permissions = $page->permissions();
			Session::i()->setLocation( $page->url(), explode( ",", $permissions['perm_view'] ), 'loc_cms_viewing_page', array( 'cms_page_' . $page->_id => TRUE ) );
		}
		
		try
		{
			$page->output();
		}
		catch ( ParseError $e )
		{
			Log::log( $e, 'page_error' );
			Output::i()->error( 'content_err_page_500', '2T187/4', 500, 'content_err_page_500_admin', array(), $e );
		}
	}
	
	/**
	 * Get the current page
	 * 
	 * @return PageClass|null
	 */
	public static function getPage(): ?PageClass
	{
		$page = null;
		if ( isset( Request::i()->page_id ) )
		{
			try
			{
				$page = PageClass::load( Request::i()->page_id );
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'content_err_page_404', '2T187/1', 404, '' );
			}
		}
		else if ( isset( Request::i()->path ) AND  Request::i()->path != '/' )
		{
			/* Sort out pagination for pages */
			list( $path, $pageNumber ) = PageClass::getStrippedPagePath( Request::i()->path );
			if( $pageNumber AND !Request::i()->page )
			{
				Request::i()->page = $pageNumber;
			}

			try
			{
				$page = PageClass::loadFromPath( $path );
			}
			catch ( OutOfRangeException $e )
			{
				try
				{
					$page = PageClass::getUrlFromHistory( Request::i()->path, ( isset( Request::i()->url()->data['query'] ) ? Request::i()->url()->data['query'] : NULL ) );

					if( (string) $page == (string) Request::i()->url() )
					{
						Output::i()->error( 'content_err_page_404', '2T187/3', 404, '' );
					}

					Output::i()->redirect( $page );
				}
				catch( OutOfRangeException $e )
				{
					Output::i()->error( 'content_err_page_404', '2T187/2', 404, '' );
				}
			}
		}
		else
		{
            try
            {
                $page = PageClass::getDefaultPage();
            }
            catch ( OutOfRangeException $e )
            {
                Output::i()->error( 'content_err_page_404', '2T257/1', 404, '' );
            }
		}
		
		if ( $page === NULL )
		{
            Output::i()->error( 'content_err_page_404', '2T257/2', 404, '' );
		}

		if ( ! $page->can('view') )
		{
			Output::i()->error( 'content_err_page_403', '2T187/3', 403, '' );
		}
		
		if ( IPS::classUsesTrait( $page->item(), 'IPS\Content\ViewUpdates' ) )
		{
			$page->item()->updateViews();
		}
		
		/* Set the current page, so other blocks, DBs, etc don't have to figure out where they are */
		PageClass::$currentPage = $page;
		
		return $page;
	}
	
	/**
	 * Capture database specific things
	 *
	 * @param string $method	Desired method
	 * @param array $args	Arguments
	 * @return	mixed
	 */
	public function __call( string $method, array $args ) : mixed
	{
		$page = $this::getPage();
		$page->setTheme();
		$databaseId = ( isset( Request::i()->d ) ) ? Request::i()->d : $page->getDatabase()->_id;

		if ( $databaseId !== NULL )
		{
			try
			{
				if ( Request::i()->isAjax() )
				{
					Dispatcher::i()->setDatabase( $databaseId )->run();
					return null;
				}
				else
				{
					$page->output();
				}
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'content_err_page_404', '2T257/3', 404, '' );
			}
		}

		return null;
	}

	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed() : void
	{
		$this->__call( 'embed', func_get_args() );
	}
}