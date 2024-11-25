<?php
/**
 * @brief		Browse the gallery
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\modules\front\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Controller;
use IPS\Content\Filter;
use IPS\core\DataLayer;
use IPS\core\Facebook\Pixel;
use IPS\core\FrontNavigation;
use IPS\Dispatcher;
use IPS\gallery\Album;
use IPS\gallery\Album\Item;
use IPS\gallery\Album\Table as AlbumTable;
use IPS\gallery\Application;
use IPS\gallery\Category;
use IPS\gallery\Image;
use IPS\gallery\Image\Table;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Browse the gallery
 */
class browse extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\gallery\Category';
	
	/**
	 * Execute
	 * 
	 * @return 	void
	 */
	public function execute() : void
	{
		if( isset( Request::i()->album ) )
		{
			Request::i()->id	= Request::i()->album;
			static::$contentModel	= ( isset( Request::i()->do ) ) ? 'IPS\gallery\Album\Item' : 'IPS\gallery\Album';
		}

		/* Gallery uses caption for title */
		Member::loggedIn()->language()->words[ "sort_title" ] = Member::loggedIn()->language()->addToStack( "album_sort_caption", FALSE );

		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_submit.js', 'gallery' ) );
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_global.js', 'gallery' ) );

		Application::outputCss();
		parent::execute();
	}

	/**
	 * Determine what to show
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Legacy 3.x redirect */
		if ( isset( Request::i()->image ) )
		{
			try
			{
				Output::i()->redirect( Image::loadAndCheckPerms( Request::i()->image )->url() );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2G189/A', 404, '' );
			}
		}
		
		/* Add RSS feed */
		if ( Settings::i()->gallery_rss_enabled )
		{
			Output::i()->rssFeeds['gallery_rss_title']	= Url::internal( 'app=gallery&module=gallery&controller=browse&do=rss', 'front', 'gallery_rss' );

			if ( Member::loggedIn()->member_id )
			{
				$key = Member::loggedIn()->getUniqueMemberHash();

				Output::i()->rssFeeds['gallery_rss_title'] = Output::i()->rssFeeds['gallery_rss_title']->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
			}
		}

		/* And load data and display */
		if ( isset( Request::i()->category ) )
		{
			try
			{
				$category = Category::loadAndCheckPerms( Request::i()->category );
				
				$category->clubCheckRules();

				/* If we cannot view images and there's a custom error, show it now */
				if( !$category->can( 'read' ) AND Member::loggedIn()->language()->checkKeyExists( "gallery_category_{$category->id}_permerror" ) )
				{
					Output::i()->error( $category->errorMessage(), '1G189/B', 403, '' );
				}

				/* Add RSS feed */
				if ( Settings::i()->gallery_rss_enabled )
				{
					$rssUrl = Url::internal( 'app=gallery&module=gallery&controller=browse&do=rss&category=' . $category->id, 'front', 'gallery_rss' );

					if ( Member::loggedIn()->member_id )
					{
						$key = md5( ( Member::loggedIn()->members_pass_hash ?: Member::loggedIn()->email ) . Member::loggedIn()->members_pass_salt );

						$rssUrl = $rssUrl->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
					}

					Output::i()->rssFeeds[ Member::loggedIn()->language()->addToStack( 'gallery_rss_title_container', FALSE, array( 'sprintf' => array( $category->_title ) ) ) ]	= $rssUrl;
				}

				$this->_category( $category );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2G189/1', 404, '' );
			}
		}
		else if ( isset( Request::i()->album ) )
		{
			try
			{
				$album	= Album::loadAndCheckPerms( Request::i()->album );
				
				$album->category()->clubCheckRules();

				$album->asItem()->updateViews();

				/* Add RSS feed */
				if ( Settings::i()->gallery_rss_enabled )
				{
					$rssUrl = Url::internal('app=gallery&module=gallery&controller=browse&do=rss&album=' . $album->id, 'front', 'gallery_rss');

					if ( Member::loggedIn()->member_id )
					{
						$key = md5( ( Member::loggedIn()->members_pass_hash ?: Member::loggedIn()->email ) . Member::loggedIn()->members_pass_salt );

						$rssUrl = $rssUrl->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
					}

					Output::i()->rssFeeds[ Member::loggedIn()->language()->addToStack( 'gallery_rss_title_container', FALSE, array( 'sprintf' => array( $album->_title ) ) ) ] = $rssUrl;
				}
				$this->_album( $album );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2G189/2', 404, '' );
			}
		}
		else
		{
			$this->_index();
		}

		return null;
	}

	/**
	 * Unset the cover photo
	 *
	 * @return void
	 */
	protected function unsetCoverPhoto() : void
	{
		Session::i()->csrfCheck();

		$container = null;
		if( Request::i()->category )
		{
			$container = Category::loadAndCheckPerms( Request::i()->category );
		}
		elseif( Request::i()->album )
		{
			$container	= Album::loadAndCheckPerms( Request::i()->album );
		}

		if(  $container AND Image::modPermission( 'edit', Member::loggedIn(), $container ) )
		{
			$container->cover_img_id = 0;
			$container->save();
		}
		else
		{
			Output::i()->error( 'gallery_unset_cover_error', '2G189/E', 403, '' );
		}

		Output::i()->redirect( $container->url(), 'gallery_cover_photo_isunset' );
	}

	/**
	 * Show Index
	 *
	 * @return	void
	 */
	protected function _index() : void
	{
		/* Get stuff */
		$featured = array();
		if( Settings::i()->gallery_overview_show_carousel )
		{
			switch( Settings::i()->gallery_overview_carousel_type )
			{
				case 'featured':
						$featured	= iterator_to_array( Image::featured( Settings::i()->gallery_overview_carousel_count, NULL ) );
					break;

				case 'new':
						$featured = Image::getItemsWithPermission( Image::clubImageExclusion(), 'image_updated DESC', Settings::i()->gallery_overview_carousel_count, 'view' );
					break;
			}
		}

		/* New images */
		$new = array();
		if( Settings::i()->gallery_show_new_images )
		{
			$new = iterator_to_array( Image::getItemsWithPermission( Image::clubImageExclusion(), NULL, Settings::i()->gallery_new_images_count, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, !Settings::i()->club_nodes_in_apps, false, false, false, null, false, false, false ) );
		}

		/* Recently updated albums */
		$recentlyUpdatedAlbums = array();
		if( Settings::i()->gallery_show_recent_updated_albums )
		{
			$recentlyUpdatedAlbums = Item::getItemsWithPermission( Item::clubAlbumExclusion(), 'album_last_img_date DESC', Settings::i()->gallery_recent_updated_albums_count, 'view' );
		}

		/* Recently commented */
		$recentComments = array();
		if( Settings::i()->gallery_show_recent_comments )
		{
			$commentWhere = Image::clubImageExclusion();
			$commentWhere[] = array( 'image_comments > 0' );
			$recentComments = Image::getItemsWithPermission( $commentWhere, 'image_last_comment DESC', 10, 'read', Filter::FILTER_PUBLIC_ONLY, 0, NULL, !Settings::i()->club_nodes_in_apps, false, false, false, null, false, false, false );
		}

		/* Online User Location */
		Session::i()->setLocation( Url::internal( 'app=gallery', 'front', 'gallery' ), array(), 'loc_gallery_browsing' );
		
		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('gallery_title');
		Output::i()->output	= Theme::i()->getTemplate( 'browse' )->index( $featured, $new, $recentlyUpdatedAlbums, $recentComments );
	}

	/**
	 * Show a category listing
	 *
	 * @return	void
	 */
	protected function categories() : void
	{
		/* Online User Location */
		Session::i()->setLocation( Url::internal( 'app=gallery&module=gallery&controller=browse&do=categories', 'front', 'gallery_categories' ), array(), 'loc_gallery_browsing_categories' );
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('gallery_title');
		Output::i()->output	= Theme::i()->getTemplate( 'browse' )->categories();
	}
	
	/**
	 * Show Category
	 *
	 * @param	Category	$category	The category to show
	 * @return	void
	 */
	protected function _category( Category $category ) : void
	{
		/* Online User Location */
		$permissions = $category->permissions();
		Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_gallery_viewing_category', array( "gallery_category_{$category->id}" => TRUE ) );
				
		/* Output */
		Output::i()->title		= $category->_title;

		/* Need to show albums too */
		$albums	= NULL;

		if( $category->allow_albums )
		{
			$albums	= new AlbumTable( NULL, $category );
			$albums->title = 'albums';
			$albums->classes = array( 'ipsData--category', 'ipsData--gallery-category' );
			$albums	= ( $category->hasAlbums() ) ? (string) $albums : NULL;
		}

		Output::i()->breadcrumb	= array();
		Output::i()->breadcrumb['module'] = array( Url::internal( 'app=gallery&module=gallery&controller=browse', 'front', 'gallery' ), Dispatcher::i()->module->_title );
		
		if( !count( Image::getItemsWithPermission( array( array( 'image_category_id=? AND image_album_id=?', $category->_id, 0 ) ) ) ) )
		{
			$table = ( $category->childrenCount() or $albums ) ? '' : Theme::i()->getTemplate( 'browse' )->noImages( $category );
			
			$parents = iterator_to_array( $category->parents() );
			if ( count( $parents ) )
			{
				foreach( $parents AS $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
			}
			Output::i()->breadcrumb[] = array( NULL, $category->_title );
		}
		else
		{
			/* Build table */
			$table = new Table( 'IPS\gallery\Image', $category->url(), array( array( 'image_album_id=?', 0 ) ), $category );
			$table->limit = 50;
			$table->tableTemplate = array( Theme::i()->getTemplate( 'browse' ), 'imageTable' );
			$table->rowsTemplate = array( Theme::i()->getTemplate( 'browse' ), $this->getTableRowsTemplate() );
			$table->title = Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get('num_images'), array( $category->count_imgs ) );

			$table->sortBy	= Request::i()->sortby ? $table->sortBy : $category->sort_options_img;

			/* Make sure captions are sorted in the right order */
			if ( $table->sortBy == "title" )
			{
				$table->sortDirection = "asc";
			}

			if( !$category->allow_comments )
			{
				unset( $table->sortOptions['num_comments'] );
				unset( $table->sortOptions['last_comments'] );
			}

			if( !$category->allow_reviews )
			{
				unset( $table->sortOptions['num_reviews'] );
			}

			if( !$category->allow_rating )
			{
				unset( $table->sortOptions['rating'] );
			}
		}
		
		/* If we're viewing a club, set the breadcrumbs appropriately */
		if ( $club = $category->club() )
		{
			$club->setBreadcrumbs( $category );
		}

		/* Data Layer Page Context */
		if ( Settings::i()->core_datalayer_enabled AND !Request::i()->isAjax() )
		{
			foreach ( $category->getDataLayerProperties() as $property => $value )
			{
				DataLayer::i()->addContextProperty( $property, $value );
			}
		}

		if ( !Request::i()->isAjax() )
		{
			$category->updateViews();
		}

		Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_categories' ) ] = array( 'type' => 'gallery_image', 'nodes' => $category->_id );
		Output::i()->output	= Theme::i()->getTemplate( 'browse' )->category( $category, $albums, (string) $table );
	}

	/**
	 * Show Album
	 *
	 * @param Album $album	The album to show
	 * @return	void
	 */
	protected function _album( Album $album ) : void
	{
		if( !count( Image::getItemsWithPermission( array( array( 'image_album_id=?', $album->id ) ) ) ) )
		{
			/* Show a 'no images' template if there's nothing to display */
			$table = Theme::i()->getTemplate( 'browse' )->noImages( $album );
		}
		else
		{
			/* Build table */
			$table = new Table( 'IPS\gallery\Image', $album->url(), array( array( 'image_album_id=?', $album->id ) ), $album->category() );
			$table->limit	= 50;
			$table->sortBy	= Request::i()->sortby ? $table->sortBy : $album->_sortBy;

			/* Make sure captions are sorted in the right order */
			if ( $table->sortBy == "title" )
			{
				$table->sortDirection = "asc";
			}

			$table->tableTemplate = array( Theme::i()->getTemplate( 'browse' ), 'imageTable' );
			$table->rowsTemplate = array( Theme::i()->getTemplate( 'browse' ), $this->getTableRowsTemplate() );
			$table->title = Member::loggedIn()->language()->addToStack( 'num_images', FALSE, array( 'pluralize' => array( $album->count_imgs ) ) );

			if( !$album->allow_comments )
			{
				unset( $table->sortOptions['num_comments'] );
				unset( $table->sortOptions['last_comments'] );
			}

			if( !$album->allow_reviews )
			{
				unset( $table->sortOptions['num_reviews'] );
			}

			if( !$album->allow_rating )
			{
				unset( $table->sortOptions['rating'] );
			}
		}

		/* Load the item */
		$asItem = $album->asItem();

		/* Mark the album as read - we don't bother tracking page number because albums are sorted in all different manners so the
			page parameter is unreliable for determining if the album is read. By default, typically, new images show at the beginning
			of the album anyways */
		$asItem->markRead();

		/* Sort out comments and reviews */
		$tabs = $asItem->commentReviewTabs();
		$_tabs = array_keys( $tabs );
		$tab = isset( Request::i()->tab ) ? Request::i()->tab : array_shift( $_tabs );
		$activeTabContents = $asItem->commentReviews( $tab );

		if ( count( $tabs ) > 1 )
		{
			$commentsAndReviews = count( $tabs ) ? Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $tab, $activeTabContents, $album->url(), 'tab', FALSE, FALSE ) : NULL;
		}
		else
		{
			$commentsAndReviews = $activeTabContents;
		}
		
		/* Fetching comments/reviews */
		if( Request::i()->isAjax() AND !isset( Request::i()->listResort ) )
		{
			Output::i()->output = $activeTabContents;
			return;
		}

		/* Online User Location */
		$permissions = $album->category()->permissions();
		Session::i()->setLocation( $album->url(), explode( ",", $permissions['perm_view'] ), 'loc_gallery_viewing_album', array( $album->_title => FALSE ) );
				
		/* Output */
		Output::i()->title			= $album->_title;
		Output::i()->breadcrumb	= array();
		
		if ( $club = $album->category()->club() )
		{
			FrontNavigation::$clubTabActive = TRUE;
			Output::i()->breadcrumb = array();
			Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
			Output::i()->breadcrumb[] = array( $club->url(), $club->name );
			Output::i()->breadcrumb[] = array( $album->category()->url(), $album->category()->_title );
			
			if ( Settings::i()->clubs_header == 'sidebar' )
			{
				Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $album->category(), 'sidebar' );
			}
		}
		else
		{
			Output::i()->breadcrumb['module'] = array( Url::internal( 'app=gallery&module=gallery&controller=browse', 'front', 'gallery' ), Dispatcher::i()->module->_title );
			$parents = iterator_to_array( $album->category()->parents() );
			if ( count( $parents ) )
			{
				foreach( $parents AS $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
			}
			Output::i()->breadcrumb[] = array( $album->category()->url(), $album->category()->_title );
		}

		if( $album->coverPhoto('masked') )
		{
			Output::i()->metaTags['og:image']		= $album->coverPhoto('masked');
		}
		
		Output::i()->metaTags['og:title'] = $album->_title;
		
		$albumItem = $album->asItem();
		Pixel::i()->PageView = array(
			'item_id' => $album->id,
			'item_name' => $albumItem->mapped('title'),
			'item_type' => $albumItem::$contentType ?? $albumItem::$title,
			'category_name' => $albumItem->container()->_title
		);

		if ( !Request::i()->isAjax() )
		{
			$albumItem->updateViews();
		}

		Output::i()->breadcrumb[] = array( NULL, $album->_title );
		Output::i()->output	= Theme::i()->getTemplate( 'browse' )->album( $album, (string) $table, $commentsAndReviews );
	}

	/**
	 * Determine which table rows template to use
	 *
	 * @return	string
	 */
	protected function getTableRowsTemplate(): string
	{
		if( isset( Request::i()->cookie['thumbnailSize'] ) AND Request::i()->cookie['thumbnailSize'] == 'large' AND Request::i()->controller != 'search' )
		{
			return 'tableRowsLarge';
		}
		else if( isset( Request::i()->cookie['thumbnailSize'] ) AND Request::i()->cookie['thumbnailSize'] == 'rows' AND Request::i()->controller != 'search' )
		{
			return 'tableRowsRows';
		}
		else
		{
			return 'tableRowsThumbs';
		}
	}

	/**
	 * Edit album
	 *
	 * @return	void
	 */
	protected function editAlbum() : void
	{
		/* Load album and check permissions */
		try
		{
			$album	= Album::loadAndCheckPerms( Request::i()->album, 'read' );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2G189/5', 404, '' );
		}

		if( !$album->asItem()->canEdit() )
		{
			Output::i()->error( 'node_error', '2G189/4', 403, '' );
		}

		/* Build form */
		$form = new Form;
		$form->class .= 'ipsForm--vertical ipsForm--edit-album';

		$album->form( $form );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$values['album_owner']	= ( isset( $values['album_owner'] ) ) ? $values['album_owner'] : Member::loggedIn();

			if( !$values['album_name'] OR !$values['album_category'] )
			{
				if( !$values['album_name'] )
				{
					$form->elements['']['album_name']->error	= Member::loggedIn()->language()->addToStack('form_required');
				}

				if( !$values['album_category'] )
				{
					$form->elements['']['album_category']->error	= Member::loggedIn()->language()->addToStack('form_required');
				}

				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
				return;
			}

			$album->saveForm( $album->formatFormValues( $values ) );
			
			Output::i()->redirect( $album->url() );
		}
		
		/* Display form */
		Output::i()->title	 = Member::loggedIn()->language()->addToStack( 'edit_album' );
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Delete album
	 *
	 * @return	void
	 */
	protected function deleteAlbum() : void
	{
		/* Load album and check permissions */
		try
		{
			$album	= Album::loadAndCheckPerms( Request::i()->album, 'read' );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2G189/6', 404, '' );
		}

		if( !$album->canDelete() )
		{
			Output::i()->error( 'node_error', '2G189/7', 403, '' );
		}

		Session::i()->csrfCheck();

		/* Build form to move or delete images */
		$form = new Form;
		$form->class .= 'ipsForm--vertical ipsForm--delete-images';

		$form->add( new YesNo( "delete_images", TRUE, FALSE, array( 'togglesOff' => array( 'move_image_category', 'move_image_album' ) ) ) );

		$form->add( new Node( 'move_image_category', NULL, FALSE, array(
			'class'					=> 'IPS\gallery\Category',
			'permissionCheck'		=> function( $node ) {

				/* Do we have permission to add? */
				if( !$node->can( 'add' ) )
				{
					return false;
				}

				// Otherwise, if the node *requires* albums, also return FALSE
				if( $node->allow_albums == 2 )
				{
					return FALSE;
				}

				return TRUE;
			}
		), NULL, NULL, NULL, 'move_image_category' ) );

		$form->add( new Node( 'move_image_album', NULL, FALSE, array(
			'class'					=> 'IPS\gallery\Album',
			'permissionCheck' 		=> function( $node ) use ( $album )
			{
				/* Do we have permission to add? */
				if( !$node->can( 'add' ) )
				{
					return false;
				}

				/* This isn't the album we are deleting right? */
				if ( $node->id == $album->id )
				{
					return false;
				}
				
				return TRUE;
			}
		), NULL, NULL, NULL, 'move_image_album' ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$category = $album->category();
			/* Update the count */
			if( $album->type == $album::AUTH_TYPE_PUBLIC )
			{
				$category->public_albums = $category->public_albums - 1;
			}
			else
			{
				$category->nonpublic_albums = $category->nonpublic_albums - 1;
			}

			$category->save();

			/* Hide the album */
			$album->type = Album::AUTH_TYPE_DELETED;
			$album->save();
			
			/* Are we moving the images? */
			if( !$values['delete_images'] )
			{
				if( ( !isset( $values['move_image_category'] ) OR !( $values['move_image_category'] instanceof Model ) ) AND
					( !isset( $values['move_image_album'] ) OR !( $values['move_image_album'] instanceof Model ) ) )
				{
					$form->error	= Member::loggedIn()->language()->addToStack('gallery_cat_or_album');

					Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
					return;
				}
				
				$moveData = array( 'class' => 'IPS\gallery\Album', 'id' => $album->_id, 'deleteWhenDone' => TRUE );
				if( isset( $values['move_image_category'] ) AND $values['move_image_category'] instanceof Model )
				{
					$moveData['moveToClass'] = 'IPS\gallery\Category';
					$moveData['moveTo'] = $values['move_image_category']->_id;
				}
				else
				{
					$moveData['moveToClass'] = 'IPS\gallery\Album';
					$moveData['moveTo'] = $values['move_image_album']->_id;
				}
				
				Task::queue( 'core', 'DeleteOrMoveContent', $moveData );
			}
			else
			{
				Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => 'IPS\gallery\Album', 'id' => $album->_id, 'deleteWhenDone' => TRUE ) );
			}

			/* And then redirect */
			if( isset( $moveData['moveTo'] ) )
			{
				$target = $moveData['moveToClass']::load($moveData['moveTo']);
				Output::i()->redirect( $target->url() );
			}
			else
			{
				Output::i()->redirect( $album->category()->url() );
			}
		}

		/* Display form */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed() : void
	{
		Request::i()->id = Request::i()->album;
		parent::embed();
	}
}