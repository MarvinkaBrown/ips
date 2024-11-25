<?php
/**
 * @brief		Browse Files Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		08 Oct 2013
 */

namespace IPS\downloads\modules\front\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\downloads\Category;
use IPS\downloads\File;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Money;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Session\Front;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Browse Files
 */
class browse extends Controller
{
	
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
			$category	= Category::load( Request::i()->id );

			File::markContainerRead( $category, NULL, FALSE );

			Output::i()->redirect( $category->url() );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2D175/3', 403, 'no_module_permission_guest' );
		}
	}

	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( isset( Request::i()->currency ) and in_array( Request::i()->currency, Money::currencies() ) and isset( Request::i()->csrfKey ) and Request::i()->csrfKey === Front::i()->csrfKey )
		{
			Request::i()->setCookie( 'currency', Request::i()->currency );
		}
		
		if ( isset( Request::i()->id ) )
		{
			if ( Request::i()->id == 'clubs' and Settings::i()->club_nodes_in_apps )
			{
				Session::i()->setLocation( Url::internal( 'app=downloads&module=downloads&controller=browse&do=categories', 'front', 'downloads_categories' ), array(), 'loc_downloads_browsing_categories' );
				Output::i()->breadcrumb[] = array( Url::internal( 'app=downloads&module=downloads&controller=browse&do=categories', 'front', 'downloads_categories' ), Member::loggedIn()->language()->addToStack('download_categories') );
				Output::i()->breadcrumb[] = array( Url::internal( 'app=downloads&module=downloads&controller=browse&id=clubs', 'front', 'downloads_clubs' ), Member::loggedIn()->language()->addToStack('club_node_downloads') );
				Output::i()->title		= Member::loggedIn()->language()->addToStack('club_node_downloads');
				Output::i()->output	= Theme::i()->getTemplate( 'browse' )->categories( TRUE );
			}
			else
			{
				try
				{
					$this->_category( Category::loadAndCheckPerms( Request::i()->id, 'read' ) );
				}
				catch ( OutOfRangeException $e )
				{
					Output::i()->error( 'node_error', '2D175/1', 404, '' );
				}
			}
		}
		else
		{
			$this->_index();
		}
	}
	
	/**
	 * Show Index
	 *
	 * @return	void
	 */
	protected function _index() : void
	{
		/* Add RSS feed */
		if ( Settings::i()->idm_rss )
		{
			Output::i()->rssFeeds['idm_rss_title'] = Url::internal( 'app=downloads&module=downloads&controller=browse&do=rss', 'front', 'downloads_rss' );

			if ( Member::loggedIn()->member_id )
			{
				$key = Member::loggedIn()->getUniqueMemberHash();

				Output::i()->rssFeeds['idm_rss_title'] = Output::i()->rssFeeds['idm_rss_title']->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
			}
		}
		
		/* Get stuff */
		$featured = Settings::i()->idm_show_featured ? iterator_to_array( File::featured( Settings::i()->idm_featured_count, '_rand' ) ) : array();

		if ( Settings::i()->idm_newest_categories )
		{
			$newestWhere = array( array( 'downloads_categories.copen=1 and ' . Db::i()->in('file_cat', explode( ',', Settings::i()->idm_newest_categories ) ) ) );
		}
		else
		{
			$newestWhere = array( array( 'downloads_categories.copen=1' ) );
		}
		if ( !Settings::i()->club_nodes_in_apps )
		{
			$newestWhere[] = array( 'downloads_categories.cclub_id IS NULL' );
		}

        $new = ( Settings::i()->idm_show_newest) ? File::getItemsWithPermission( $newestWhere, NULL, 14, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();

		if ( Settings::i()->idm_highest_rated_categories )
		{
			$highestWhere = array( array( 'downloads_categories.copen=1 and ' . Db::i()->in('file_cat', explode( ',', Settings::i()->idm_highest_rated_categories ) ) ) );
		}
		else
		{
			$highestWhere = array( array( 'downloads_categories.copen=1' ) );
		}
		$highestWhere[] = array( 'file_rating > ?', 0 );
		if ( !Settings::i()->club_nodes_in_apps )
		{
			$highestWhere[] = array( 'downloads_categories.cclub_id IS NULL' );
		}
		$highestRated = ( Settings::i()->idm_show_highest_rated ) ? File::getItemsWithPermission( $highestWhere, 'file_rating DESC, file_reviews DESC', 14, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();

		if (Settings::i()->idm_show_most_downloaded_categories )
		{
			$mostDownloadedWhere = array( array( 'downloads_categories.copen=1 and ' . Db::i()->in('file_cat', explode( ',', Settings::i()->idm_show_most_downloaded_categories ) ) ) );
		}
		else
		{
			$mostDownloadedWhere = array( array( 'downloads_categories.copen=1' ) );
		}
		$mostDownloadedWhere[] = array( 'downloads_categories.copen=1 and file_downloads > ?', 0 );
		if ( !Settings::i()->club_nodes_in_apps )
		{
			$mostDownloadedWhere[] = array( 'downloads_categories.cclub_id IS NULL' );
		}
		$mostDownloaded = ( Settings::i()->idm_show_most_downloaded ) ? File::getItemsWithPermission( $mostDownloadedWhere, 'file_downloads DESC', 14, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();
		
		/* Online User Location */
		Session::i()->setLocation( Url::internal( 'app=downloads', 'front', 'downloads' ), array(), 'loc_downloads_browsing' );
		
		/* Display */
		Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'browse' )->indexSidebar( Category::canOnAny('add') );
		Output::i()->title		= Member::loggedIn()->language()->addToStack('downloads');
		Output::i()->output	= Theme::i()->getTemplate( 'browse' )->index( $featured, $new, $highestRated, $mostDownloaded );
	}
	
	/**
	 * Show Category
	 *
	 * @param Category $category	The category to show
	 * @return	void
	 */
	protected function _category( Category $category ) : void
	{
		$category->clubCheckRules();
		
		Output::i()->sidebar['contextual'] = '';
		
		$_count = File::getItemsWithPermission( array( array( File::$databasePrefix . File::$databaseColumnMap['container'] . '=?', $category->_id ) ), NULL, 1, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );

		if( !$_count )
		{
			/* If we're viewing a club, set the breadcrumbs appropriately */
			if ( $club = $category->club() )
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

			/* Show a 'no files' template if there's nothing to display */
			$table = Theme::i()->getTemplate( 'browse' )->noFiles( $category );
		}
		else
		{
			/* Build table */
			$table = new Content( 'IPS\downloads\File', $category->url(), NULL, $category );
			if ( Member::loggedIn()->getLayoutValue( 'downloads_categories' ) == 'grid')
			{
				$table->classes = array( 'ipsData--grid ipsData--entries ipsData--download-file-table' );
			} else {
				$table->classes = array( 'ipsData--table ipsData--entries ipsData--download-file-table' );
			}
			$table->sortOptions = array_merge( $table->sortOptions, array( 'file_downloads' => 'file_downloads' ) );

			if ( !$category->bitoptions['reviews_download'] )
			{
				unset( $table->sortOptions['num_reviews'] );
			}

			if ( !$category->bitoptions['comments'] )
			{
				unset( $table->sortOptions['last_comment'] );
				unset( $table->sortOptions['num_comments'] );
			}

			if ( $table->sortBy === 'downloads_files.file_title' )
			{
				$table->sortDirection = 'asc';
			}
			
			if ( Application::appIsEnabled( 'nexus' ) and Settings::i()->idm_nexus_on )
			{
				$table->filters = array(
					'file_free'	=> "( ( file_cost='' OR file_cost IS NULL ) AND ( file_nexus='' OR file_nexus IS NULL ) )",
					'file_paid'	=> "( file_cost<>'' OR file_nexus>0 )",
				);
			}
			$table->title = Member::loggedIn()->language()->pluralize(  Member::loggedIn()->language()->get('download_file_count'), array( $_count ) );
		}

		/* Online User Location */
		$permissions = $category->permissions();
		Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_downloads_viewing_category', array( "downloads_category_{$category->id}" => TRUE ) );
				
		/* Set default search option */
		Output::i()->defaultSearchOption = array( 'downloads_file', "downloads_file_pl" );

		/* Update Views */
		if ( ! Request::i()->isAjax() )
		{
			$category->updateViews();
		}

		/* Output */
		Output::i()->title		= $category->_title;
		Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_downloads_categories' ) ] = array( 'type' => 'downloads_file', 'nodes' => $category->_id );
		Output::i()->sidebar['contextual'] .= Theme::i()->getTemplate( 'browse' )->indexSidebar( Category::canOnAny('add'), $category );
		Output::i()->output	= Theme::i()->getTemplate( 'browse' )->category( $category, (string) $table );
	}

	/**
	 * Show a category listing
	 *
	 * @return	void
	 */
	protected function categories() : void
	{
		/* Online User Location */
		Session::i()->setLocation( Url::internal( 'app=downloads&module=downloads&controller=browse&do=categories', 'front', 'downloads_categories' ), array(), 'loc_downloads_browsing_categories' );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('downloads_categories_pagetitle');
		Output::i()->breadcrumb[] = array( Url::internal( 'app=downloads&module=downloads&controller=browse&do=categories', 'front', 'downloads_categories' ), Member::loggedIn()->language()->addToStack('download_categories') );
		Output::i()->output = Theme::i()->getTemplate( 'browse' )->categories();
	}
}