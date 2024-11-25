<?php
/**
 * @brief		All Blogs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		03 Mar 2014
 */

namespace IPS\blog\modules\front\blogs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\blog\Blog;
use IPS\blog\Blog\Table;
use IPS\blog\Category;
use IPS\blog\Entry;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * browse
 */
class browse extends Controller
{
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Category */
		try
		{
			$category = Category::loadAndCheckPerms( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			$category = NULL;
		}

		/* Featured stuff */
		$featured = iterator_to_array( Entry::featured( 5, '_rand' ) );
		$blogs = Blog::loadByOwner( Member::loggedIn(), array( array( 'blog_disabled=?', 0 ) ) );

		$viewMode = Member::loggedIn()->getLayoutValue( 'blog_view' );
		
		/* Grid view */
		if ( $viewMode == 'grid' )
		{
			$perpage = 23;
			$page    = 1;
			
			if ( Request::i()->page )
			{
				$page = intval( Request::i()->page );
				if ( !$page OR $page < 1 )
				{
					$page = 1;
				}
			}
			
			/* @note We cannot check individual member permissions here, so entries in draft status are excluded. */

			$where		= array();
			$where[]		= array( "blog_entries.entry_status!=?", 'draft' );
			if ( !Settings::i()->club_nodes_in_apps )
			{
				$where[] = array( "blog_blogs.blog_club_id IS NULL" );
			}
			if( $category )
			{
				$where[] = array( 'blog_blogs.blog_category_id=?', $category->_id );
			}
			$count   = Entry::getItemsWithPermission( $where, 'entry_date desc', NULL, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE, FALSE, FALSE, TRUE );
			$entries = Entry::getItemsWithPermission( $where, 'entry_date desc', array( ( $perpage * ( $page - 1 ) ), $perpage ), 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE );

			/* Validate Pagination */
			$totalPages = 	 ceil( $count / $perpage );
			$redirect = false;
			if ( $page > 1 )
			{
				if ( $totalPages and $page > $totalPages)
				{
					$redirect = true;
				}
			}

			$queryString = 'app=blog&module=blogs&controller=browse';
            if( $category )
            {
                $queryString .= '&id=' . $category->_id;
            }
            $paginationUrl = Url::internal( $queryString, 'front', ( $category ) ? 'blog_category' : 'blogs' );

			if( $redirect )
			{
				Output::i()->redirect( $paginationUrl->setPage('page', 1 ), NULL, 303 );
			}

			$pagination = array(
				'page'    => $page,
				'pages'   => $totalPages,
				'perpage' => $perpage,
				'url'     => $paginationUrl
			);

			Output::i()->output = Theme::i()->getTemplate( 'browse' )->indexGrid( $entries, $featured, $blogs, $pagination, $viewMode, $category );
		}
		else
		{	
			/* Blogs table */
			$table = new Table( Url::internal( 'app=blog&module=blogs&controller=browse', 'front', 'blogs' ) );
			if( $category )
			{
				$table->where[] = array( 'blog_category_id=?', $category->_id );
			}
			$table->title = 'our_community_blogs';
			$table->classes = array( 'cBlogList', 'ipsBlogData' );
	
			/* Filters */
			$table->filters = array(
				'my_blogs'				=> array( '(' . Db::i()->findInSet( 'blog_groupblog_ids', Member::loggedIn()->groups ) . ' OR ' . 'blog_member_id=? )', Member::loggedIn()->member_id ),
				'blogs_with_content'	=> array( 'blog_count_entries>0' )
			);
			
			Output::i()->output = Theme::i()->getTemplate( 'browse' )->index( $table, $featured, $blogs, $viewMode, $category );
		}
		
		Session::i()->setLocation( Url::internal( 'app=blog', 'front', 'blogs' ), array(), 'loc_blog_viewing_index' );
				
		/* Display */
		if( count( Category::roots() ) > 1 )
		{
			Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'browse' )->categories( $category );
		}

		if( $category )
		{
			foreach( $category->parents() as $parent )
			{
				Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			Output::i()->breadcrumb[] = array( $category->url(), $category->_title );

			/* Set default search option */
			Output::i()->defaultSearchOption = array( 'blog_entry', 'blog_entry_pl' );
		}
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_browse.js', 'blog', 'front' ) );
		Output::i()->title		= Member::loggedIn()->language()->addToStack('blogs');
		
	}
}