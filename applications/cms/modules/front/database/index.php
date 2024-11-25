<?php
/**
 * @brief		[Database] Category List Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		16 April 2014
 */

namespace IPS\cms\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Categories;
use IPS\cms\Databases;
use IPS\cms\Databases\Controller;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Pages\Page;
use IPS\cms\Records;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use function count;
use function defined;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List
 */
class index extends Controller
{

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage() : void
	{
		/* If the Databases module is set as default we end up here, but not routed through the database dispatcher which means the
			database ID isn't set. In that case, just re-route back through the pages controller which handles everything. */
		if( Dispatcher::i()->databaseId === NULL )
		{
			$pages = new \IPS\cms\modules\front\pages\page;
			$pages->manage();
			return;
		}

		$database = Databases::load( Dispatcher::i()->databaseId );

		/* Not using categories? */
		if ( ! $database->use_categories AND $database->display_settings['index']['type'] == 'categories' )
		{
			$controller = new category( $this->url );
			$controller->view();
			return;
		}
		
		$this->view();
	}

	/**
	 * Display database category list.
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		$database    = Databases::load( Dispatcher::i()->databaseId );
		$recordClass = 'IPS\cms\Records' . Dispatcher::i()->databaseId;
		$url         = Url::internal( "app=cms&module=pages&controller=page&path=" . Page::$currentPage->full_path, 'front', 'content_page_path', Page::$currentPage->full_path );

		/* RSS */
		if ( $database->rss )
		{
			/* Show the link */
			Output::i()->rssFeeds[ $database->_title ] = $url->setQueryString( 'rss', 1 );

			/* Or actually show RSS feed */
			if ( isset( Request::i()->rss ) )
			{
				$document     = Rss::newDocument( $url, Member::loggedIn()->language()->get('content_db_' . $database->id ), Member::loggedIn()->language()->get('content_db_' . $database->id . '_desc' ) );
				$contentField = 'field_' . $database->field_content;

				/* @var Records $recordClass */
				foreach ( $recordClass::getItemsWithPermission( array(), $database->field_sort . ' ' . $database->field_direction, $database->rss ) as $record )
				{
					$content = $record->$contentField;
						
					if ( $record->record_image )
					{
						$content = \IPS\cms\Theme::i()->getTemplate( 'listing', 'cms', 'database' )->rssItemWithImage( $content, $record->record_image );
					}

					$document->addItem( $record->_title, $record->url(), $content, DateTime::ts( $record->_publishDate ), $record->_id );
				}
		
				/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
				Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			}
		}

		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		if ( $database->display_settings['index']['type'] != 'categories' and ! isset( Request::i()->show ) )
		{
			/* Featured */
			$limit = 0;
			$count = 0;
			$perPage = ( $database->field_perpage ?? 25 );

			if ( isset( Request::i()->page ) )
			{
				$limit = $perPage * ( $page - 1 );
			}

			$where = [];

			/** @var Categories $categoryClass */
			$categoryClass = 'IPS\cms\Categories' . $database->id;
			$category = $categoryClass::load( $database->_default_category );
			$cookie = $category->getFilterCookie();
			if( $cookie !== null )
			{
				$where = $category->buildWhereFromCookie( $cookie );
			}
			elseif( isset( Request::i()->advanced_search_submitted ) and Request::i()->advanced_search_submitted )
			{
				$cookie = [];
				foreach( Request::i() as $k => $v )
				{
					if( $k == 'cms_record_i_started' )
					{
						$cookie[ $k ] = $v;
					}
					elseif( str_starts_with( $k, 'content_field_' ) )
					{
						$cookie[ mb_substr( $k, 14 ) ] = $v;
					}
				}
				if( count( $cookie ) )
				{
					$where = $category->buildWhereFromCookie( $cookie );
				}
			}

			if( $database->display_settings['index']['type'] == 'featured' )
			{
				$where[] = [ 'record_featured=?', 1 ];
			}

			$sort = (  $database->field_sort ?  $recordClass::$databaseTable . '.' . $recordClass::$databasePrefix . $database->field_sort : 'record_last_comment' );
			$sortDirection = $database->field_direction ?? 'desc';

			$articles = $recordClass::getItemsWithPermission( $where, 'record_pinned DESC, ' . $sort . ' ' . $sortDirection, array( $limit, $perPage ), 'read', Filter::FILTER_AUTOMATIC, 0, NULL, TRUE );

			$count = $recordClass::getItemsWithPermission( $where, 'record_pinned DESC, ' . $sort . ' ' . $sortDirection, $perPage, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );

			/* Pagination */
			$pagination = array(
				'page'  => $page,
				'pages' => ( $count > 0 ) ? ceil( $count / $perPage ) : 1
			);
			
			/* Make sure we are viewing a real page */
			if ( $page > $pagination['pages'] )
			{
				Output::i()->redirect( Request::i()->url()->setPage( 'page', 1 ), NULL, 303 );
			}
			
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'database_index/featured.css', 'cms', 'front' ) );
			Output::i()->title = ( $page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $database->pageTitle(), $page ) ) ) : $database->pageTitle();

			Dispatcher::i()->output .= Output::i()->output = \IPS\cms\Theme::i()->getTemplate( $database->template_featured, 'cms', 'database' )->index( $database, $articles, $url, $pagination );
		}
		else
		{
			/* Category view */
			/* @var Categories $class */
			$class = '\IPS\cms\Categories' . $database->id;
			
			/* Load into memory */
			$class::loadIntoMemory();

			/* Get only the categories we can view. Otherwise, there is a risk the output will show a list of categories with no actual content or error message (the template that checks view permission is separate from the one that verifies the count in our oob templates) */
			$categories = [];
			foreach ( $class::roots() as $category )
			{
				if ( $category->can('view') )
				{
					$categories[] = $category;
				}
			}

			Output::i()->title = $database->pageTitle();
			Dispatcher::i()->output .= Output::i()->output = \IPS\cms\Theme::i()->getTemplate( $database->template_categories, 'cms', 'database' )->index( $database, $categories, $url );
		}
	}

	/**
	 * Show the pre add record form. This is used when no category is set.
	 *
	 * @return	void
	 */
	protected function form() : void
	{
		/* If the page is the default page and Pages is the default app, the node selector cannot find the page as it bypasses the Database dispatcher */
		if ( Page::$currentPage === NULL and Dispatcher::i()->databaseId === NULL and isset( Request::i()->page_id ) )
		{
			try
			{
				Page::$currentPage = Page::load( Request::i()->page_id );
				$database = Page::$currentPage->getDatabase();
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'content_err_page_404', '2T389/1', 404, '' );
			}
		}
		else if ( Page::$currentPage === NULL and Dispatcher::i()->databaseId === NULL and isset( Request::i()->d ) )
		{
			Page::$currentPage = Page::loadByDatabaseId( Request::i()->d );
		}
		
		$form = new Form( 'select_category', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--select-database-category ipsForm--noLabels';
		$form->add( new Node( 'category', NULL, TRUE, array(
			'url'					=> Page::$currentPage->url()->setQueryString( array( 'do' => 'form', 'page_id' => Page::$currentPage->id ) ),
			'class'					=> 'IPS\cms\Categories' . Page::$currentPage->getDatabase()->_id,
			'permissionCheck'		=> function( $node )
			{
				if ( $node->can( 'view' ) )
				{
					if ( $node->can( 'add' ) )
					{
						return TRUE;
					}

					return FALSE;
				}

				return NULL;
			},
		) ) );

		if ( $values = $form->values() )
		{
			Output::i()->redirect( $values['category']->url()->setQueryString( 'do', 'form' ) );
		}

		Output::i()->title						= Member::loggedIn()->language()->addToStack( 'cms_select_category' );
		Output::i()->breadcrumb[]				= array( NULL, Member::loggedIn()->language()->addToStack( 'cms_select_category' ) );
		Dispatcher::i()->output	= Output::i()->output = Theme::i()->getTemplate( 'records' )->categorySelector( $form );
	}
}
