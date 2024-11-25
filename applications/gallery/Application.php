<?php
/**
 * @brief		Gallery Application Class 
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 * @version		
 */
 
namespace IPS\gallery;

use Exception;
use IPS\Application as SystemApplication;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\File;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;

/**
 * Gallery Application Class
 */
class Application extends SystemApplication
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init(): void
	{
		/* Handle RSS requests */
		if ( Request::i()->module == 'gallery' and Request::i()->controller == 'browse' and Request::i()->do == 'rss' )
		{
			$member = NULL;
			if( Request::i()->member AND Request::i()->key )
			{
				$member = Member::load( Request::i()->member );
				if( !Login::compareHashes( $member->getUniqueMemberHash(), (string) Request::i()->key ) )
				{
					$member = NULL;
				}
			}

			$this->sendGalleryRss( $member ?? new Member );

			if( !Member::loggedIn()->group['g_view_board'] )
			{
				Output::i()->error( 'node_error', '2G218/1', 404, '' );
			}
		}

		if( Member::loggedIn()->members_bitoptions['remove_gallery_access'] )
		{
			Output::i()->error( 'node_error', '2G218/1', 404, '' );
		}
	}

	/**
	 * Send the gallery RSS feed for the indicated member
	 *
	 * @param Member $member		Member
	 * @return	void
	 * @note	We use a template so that we can embed image directly into feed while still allowing it to be customized
	 */
	protected function sendGalleryRss( Member $member ) : void
	{
		if( !Settings::i()->gallery_rss_enabled )
		{
			Output::i()->error( 'gallery_rss_offline', '2G189/3', 403, 'gallery_rss_offline_admin' );
		}
		
		$where	= array();

		if( isset( Request::i()->category ) )
		{
			try
			{
				$category	= Category::load( Request::i()->category );

				if( !$category->can( 'read', $member ) )
				{
					throw new OutOfRangeException;
				}

				$where[]	= array( 'image_category_id=?', $category->id );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2G189/8', 404, '' );
			}
		}
		else if ( isset( Request::i()->album ) )
		{
			try
			{
				$album		= Album::load( Request::i()->album );

				if( !$album->can( 'read', $member ) )
				{
					throw new OutOfRangeException;
				}

				$where[]	= array( 'image_album_id=?', $album->id );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2G189/9', 404, '' );
			}
		}

		$document = Rss::newDocument( isset( $category ) ? $category->url() : ( isset( $album ) ? $album->url() : Url::internal( 'app=gallery&module=gallery&controller=browse', 'front', 'gallery' ) ), $member->language()->get('gallery_rss_title'), $member->language()->get('gallery_rss_title') );

		foreach (Image::getItemsWithPermission( $where, NULL, 10, 'read', Filter::FILTER_AUTOMATIC, 0, $member ) as $image )
		{
			$document->addItem( $image->caption, $image->url(), Theme::i()->getTemplate( 'view' )->rssContent( $image ), DateTime::ts( $image->updated ), $image->id );
		}
		
		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		Output::i()->sendOutput( $document->asXML(), 200, 'text/xml', array(), TRUE );
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note    Return the class for the icon (e.g. 'globe')
	 * @return string
	 */
	protected function get__icon(): string
	{
		return 'camera';
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Gallery' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters() : void
	{
		/* convert ?module=images&section=img_ctrl&img=100&file=medium */
		/* convert ?module=images&section=img_ctrl&id=100&file=medium */
		if( isset( Request::i()->section ) AND Request::i()->section == 'img_ctrl' )
		{
			$id = ( isset( Request::i()->img ) ) ? Request::i()->img : Request::i()->id;

			if( $id )
			{
				if( Request::i()->file == 'med' )
				{
					Request::i()->file = 'medium';
				}

				$imageSize = ( ( Request::i()->file == 'small' ) ? 'small' : 'masked' ) . '_file_name';

				try
				{
					Output::i()->redirect( (string) File::get( 'gallery_Images', Image::load( $id )->$imageSize )->url );
				}
				catch ( Exception ){}
			}
		}

		/* convert ?app=gallery&module=images&section=viewimage&img=14586 */
		if( isset( Request::i()->section ) AND Request::i()->section == 'viewimage' )
		{
			$id = ( isset( Request::i()->img ) ) ? Request::i()->img : Request::i()->id;

			if( $id )
			{
				if( Request::i()->file == 'med' )
				{
					Request::i()->file = 'medium';
				}

				$imageSize = ( ( Request::i()->file == 'small' ) ? 'small' : 'masked' ) . '_file_name';

				try
				{
					Output::i()->redirect( Image::load( $id )->url() );
				}
				catch ( Exception ){}
			}
		}
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'gallery.css', 'gallery' ) );
	}
	
	/**
	 * Get any settings that are uploads
	 *
	 * @return	array
	 */
	public function uploadSettings(): array
	{
		/* Apps can overload this */
		return array( 'gallery_watermark_path' );
	}

	/**
	 * Returns a list of all existing webhooks and their payload in this app.
	 *
	 * @return array
	 */
	public function getWebhooks() : array
	{
		return array_merge( [
			'galleryAlbum_create' => Album::class
		],parent::getWebhooks());
	}

}