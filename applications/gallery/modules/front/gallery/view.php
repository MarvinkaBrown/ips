<?php
/**
 * @brief		View image
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\modules\front\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DateTimeInterface;
use DomainException;
use Exception;
use IPS\Content\Controller;
use IPS\Content\Item;
use IPS\core\FrontNavigation;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\gallery\Album;
use IPS\gallery\Application;
use IPS\gallery\Image;
use IPS\gallery\Image\Table;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Image as ImageClass;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Redis;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function intval;
use function strlen;
use const IPS\CACHE_CONFIG;
use const IPS\CACHE_METHOD;
use const IPS\PHOTO_THUMBNAIL_SIZE;
use const IPS\REDIS_CONFIG;
use const IPS\REDIS_ENABLED;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View image or movie
 */
class view extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\gallery\Image';

	/**
	 * Image object
	 */
	protected ?Image $image = NULL;

	/**
	 * Init
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( Request::i()->do != 'embed' )
		{
			try
			{
				$this->image = Image::load( Request::i()->id );
				
				$this->image->container()->clubCheckRules();
				
				if ( !$this->image->canView( Member::loggedIn() ) )
				{
					Output::i()->error( $this->image->container()->errorMessage(), '2G188/1', 403, '' );
				}				
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2G188/2', 404, '' );
			}
		}

		/* When preloading we don't want to update stuff */
		if( isset( Request::i()->preload ) AND Request::i()->preload )
		{
			$this->updateViewsAndMarkersOnAjax = FALSE;
		}

		Application::outputCss();

		parent::execute();
	}
	
	/**
	 * View Image
	 *
	 * @return	mixed
	 * @link	http://www.videojs.com/projects/mimes.html
	 * @note	Only HTML5 and some flash-based video formats will work. MP4, webm and ogg are relatively safe bets but anything else isn't likely to play correctly.
	 *	The above link will allow you to check what is supported in the browser you are using.
	 * @note	As of RC1 we fall back to a generic 'embed' for non-standard formats for better upgrade compatibility...need to look into transcoding in the future
	 */
	protected function manage() : mixed
	{
		/* Init */
		parent::manage();

		/* Check restrictions */
		if( Settings::i()->gallery_detailed_bandwidth AND ( Member::loggedIn()->group['g_max_transfer'] OR Member::loggedIn()->group['g_max_views'] ) )
		{
			$lastDay		= DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp();

			if( Member::loggedIn()->group['g_max_views'] )
			{
				if( Db::i()->select( 'COUNT(*) as total', 'gallery_bandwidth', array( 'member_id=? AND bdate > ?', (int) Member::loggedIn()->member_id, $lastDay ) )->first() >= Member::loggedIn()->group['g_max_views'] )
				{
					Output::i()->error( 'maximum_daily_views', '1G188/7', 403, 'maximum_daily_views_admin' );
				}
			}

			if( Member::loggedIn()->group['g_max_transfer'] )
			{
				if( Db::i()->select( 'SUM(bsize) as total', 'gallery_bandwidth', array( 'member_id=? AND bdate > ?', (int) Member::loggedIn()->member_id, $lastDay ) )->first() >= ( Member::loggedIn()->group['g_max_transfer'] * 1024 ) )
				{
					Output::i()->error( 'maximum_daily_transfer', '1G188/8', 403, 'maximum_daily_transfer_admin' );
				}
			}
		}

		/* Set some meta tags */
		if( $this->image->media )
		{
			Output::i()->metaTags['og:video']		= File::get( 'gallery_Images', $this->image->original_file_name )->url;
			Output::i()->metaTags['og:video:type']	= $this->image->file_type;
			Output::i()->metaTags['og:type']		= 'video';

			if( count( $this->image->tags() ) )
			{
				Output::i()->metaTags['og:video:tag']	= $this->image->tags();
			}

			if( $this->image->masked_file_name )
			{
				Output::i()->metaTags['og:image']		= File::get( 'gallery_Images', $this->image->masked_file_name )->url;
			}
		}
		else
		{
			Output::i()->metaTags['og:image']		= File::get( 'gallery_Images', $this->image->masked_file_name )->url;
			Output::i()->metaTags['og:image:type']	= $this->image->file_type;

			if( count( $this->image->tags() ) )
			{
				Output::i()->metaTags['og:object:tag']	= $this->image->tags();
			}
		}

		/* Prioritize the main image */
		Output::i()->linkTags[]	= array(
			'rel'	=> 'preload',
			'href'	=> (string) File::get( 'gallery_Images', $this->image->masked_file_name ?: $this->image->original_file_name  )->url,
			'as'	=> $this->image->media ? 'video' : 'image',
			'type'	=> $this->image->file_type
		);

		/* Sort out comments and reviews */
		$tabs = $this->image->commentReviewTabs();
		$_tabs = array_keys( $tabs );
		$tab = isset( Request::i()->tab ) ? Request::i()->tab : array_shift( $_tabs );
		$activeTabContents = $this->image->commentReviews( $tab, Request::i()->lightbox );

		if ( count( $tabs ) > 1 )
		{
			$commentsAndReviews = count( $tabs ) ? Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $tab, $activeTabContents, Request::i()->lightbox ? $this->image->url()->setQueryString( 'lightbox', 1 ) : $this->image->url(), 'tab', FALSE, FALSE, Request::i()->lightbox ? 'ipsTabs--small ipsTabs--stretch' : '' ) : NULL;
		}
		else
		{
			$commentsAndReviews = $activeTabContents;
		}

		/* Set the session location */
		Session::i()->setLocation( $this->image->url(), $this->image->onlineListPermissions(), 'loc_gallery_viewing_image', array( $this->image->caption => FALSE ) );

		/* Store bandwidth log */
		if( Settings::i()->gallery_detailed_bandwidth )
		{
			/* Media items should get the file size of the original file instead of a thumbnail */
			if( $this->image->media )
			{
				$displayedImage = File::get( 'gallery_Images', $this->image->original_file_name, $this->image->file_size );
			}
			/* Otherwise, fetch the thumbnails */
			else
			{
				$displayedImage	= File::get( 'gallery_Images', $this->image->masked_file_name );
			}

			/* Get filesize, but don't error out if there is a problem fetching it at this point */
			try
			{
				$filesize = ( ( isset( $displayedImage ) AND $displayedImage->filesize() ) ? $displayedImage->filesize() : $this->image->file_size );
			}
			catch( Exception )
			{
				$filesize = $this->image->file_size;
			}

			Db::i()->insert( 'gallery_bandwidth', array(
				'member_id'		=> (int) Member::loggedIn()->member_id,
				'bdate'			=> time(),
				'bsize'			=> (int) $filesize,
				'image_id'		=> $this->image->id
			)	);
		}

		/* Add JSON-ld */
		Output::i()->jsonLd['gallery']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "MediaObject",
			'@id'			=> (string) $this->image->url(),
			'url'			=> (string) $this->image->url(),
			'name'			=> $this->image->mapped('title'),
			'description'	=> $this->image->truncated( TRUE, NULL ),
			'dateCreated'	=> DateTime::ts( $this->image->date )->format( DateTimeInterface::ATOM ),
			'fileFormat'	=> $this->image->file_type,
			'keywords'		=> $this->image->tags(),
			'author'		=> array(
				'@type'		=> 'Person',
				'name'		=> Member::load( $this->image->member_id )->name,
				'image'		=> Member::load( $this->image->member_id )->get_photo( TRUE, TRUE )
			),
			'interactionStatistic'	=> array(
				array(
					'@type'					=> 'InteractionCounter',
					'interactionType'		=> "https://schema.org/ViewAction",
					'userInteractionCount'	=> $this->image->views
				)
			)
		);

		/* Do we have a real author? */
		if( $this->image->member_id )
		{
			Output::i()->jsonLd['gallery']['author']['url']	= (string) Member::load( $this->image->member_id )->url();
		}

		if ( $this->image->container()->allow_comments AND $this->image->directContainer()->allow_comments )
		{
			Output::i()->jsonLd['gallery']['interactionStatistic'][] = array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "https://schema.org/CommentAction",
				'userInteractionCount'	=> $this->image->mapped('num_comments')
			);

			Output::i()->jsonLd['gallery']['commentCount'] = $this->image->mapped('num_comments');
		}

		if ( $this->image->container()->allow_reviews AND $this->image->directContainer()->allow_reviews )
		{
			Output::i()->jsonLd['gallery']['interactionStatistic'][] = array(
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "https://schema.org/ReviewAction",
				'userInteractionCount'	=> $this->image->mapped('num_reviews')
			);

			if ( $this->image->averageReviewRating() )
			{
				Output::i()->jsonLd['gallery']['aggregateRating'] = array(
					'@type'			=> 'AggregateRating',
					'ratingValue'	=> $this->image->averageReviewRating(),
					'reviewCount'	=> $this->image->reviews,
					'bestRating'	=> Settings::i()->reviews_rating_out_of
				);
			}
		}

		if( $this->image->media )
		{
			if( $this->image->masked_file_name )
			{
				Output::i()->jsonLd['gallery']['thumbnail']	= (string) File::get( 'gallery_Images', $this->image->masked_file_name )->url;
				Output::i()->jsonLd['gallery']['thumbnailUrl']	= (string) File::get( 'gallery_Images', $this->image->masked_file_name )->url;
			}

			Output::i()->jsonLd['gallery']['contentSize'] = (string) File::get( 'gallery_Images', $this->image->original_file_name )->filesize();
		}
		else
		{
			try
			{
				$largeFile	= File::get( 'gallery_Images', $this->image->masked_file_name );
				$dimensions	= $this->image->_dimensions;

				Output::i()->jsonLd['gallery']['artMedium']	= 'Digital';
				Output::i()->jsonLd['gallery']['width'] 		= $dimensions['large'][0];
				Output::i()->jsonLd['gallery']['height'] 		= $dimensions['large'][1];
				Output::i()->jsonLd['gallery']['image']		= array(
					'@type'		=> 'ImageObject',
					'url'		=> (string) $largeFile->url,
					'caption'	=> $this->image->mapped('title'),
					'thumbnail'	=> (string) File::get( 'gallery_Images', $this->image->small_file_name )->url,
					'width'		=> $dimensions['large'][0],
					'height'	=> $dimensions['large'][1],
				);

				if( is_array( $this->image->metadata ) AND count( $this->image->metadata ) )
				{
					Output::i()->jsonLd['gallery']['image']['exifData'] = array();

					foreach( $this->image->metadata as $k => $v )
					{
						Output::i()->jsonLd['gallery']['image']['exifData'][] = array(
							'@type'		=> 'PropertyValue',
							'name'		=> $k, 
							'value'		=> $v
						);
					}
				}
				Output::i()->jsonLd['gallery']['thumbnailUrl']	= (string) File::get( 'gallery_Images', $this->image->small_file_name )->url;
			}
			/* File doesn't exist */
			catch ( RuntimeException ){}
		}

		/* Display */
		if( Request::i()->isAjax() && isset( Request::i()->browse ) )
		{
			$return = array(
				'title' => htmlspecialchars( $this->image->mapped('title'), ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ),
				'image' => Theme::i()->getTemplate( 'view' )->imageFrame( $this->image ),
				'info' => Theme::i()->getTemplate( 'view' )->imageInfo( $this->image )
			);

			if( $this->image->directContainer()->allow_comments )
			{
				$return['comments'] = $commentsAndReviews;
			}

			/* Data Layer Properties */
			if ( Settings::i()->core_datalayer_enabled )
			{
				$return['dataLayer'] = $this->image->getDataLayerProperties();
			}

			Output::i()->json( $return );
		}
		/* Switching comments only */
		elseif( Request::i()->isAjax() AND !isset( Request::i()->rating_submitted ) AND Request::i()->tab )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_view.js', 'gallery' ) );
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_browse.js', 'gallery' ) );
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_global.js', 'gallery' ) );

			Output::i()->output = Theme::i()->getTemplate( 'view' )->image( $this->image, $commentsAndReviews );
		}

		return null;
	}

	/**
	 * Download the full size image
	 *
	 * @return	void
	 */
	protected function download() : void
	{
		if( $this->image->canDownloadOriginal() == Image::DOWNLOAD_ORIGINAL_NONE )
		{
			Output::i()->error( 'cannot_download_original_image', '2G188/E', 403, '' );
		}

		try
		{
			/* Get file and data */
			$image = NULL;

			try
			{
				switch( $this->image->canDownloadOriginal() )
				{
					case Image::DOWNLOAD_ORIGINAL_WATERMARKED:
						/* We need to watermark the original image on the fly in this case */
						$file		= File::get( 'gallery_Images', $this->image->original_file_name );

						if( $file->isImage() )
						{
							$image		= $this->image->createImageFile( $file, NULL );
						}
					break;

					case Image::DOWNLOAD_ORIGINAL_RAW:
						$file		= File::get( 'gallery_Images', $this->image->original_file_name );
					break;
				}

				if( isset( $file ) AND $file->filesize() === false )
				{
					throw new RuntimeException( 'DOES_NOT_EXIST' );
				}
			}
			catch( RuntimeException )
			{
				Log::log( "Original image for {$this->image->id} is missing, falling back to masked image", 'gallery_image_missing' );
				$file = File::get( 'gallery_Images', $this->image->masked_file_name );
			}

			$headers	= array_merge( Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => Output::getContentDisposition( 'attachment', $file->originalFilename ), "X-Content-Type-Options" => "nosniff" ) );

			/* Send headers and print file */
			Output::i()->sendStatusCodeHeader( 200 );
			Output::i()->sendHeader( "Content-type: " . File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );

			foreach( $headers as $key => $header )
			{
				Output::i()->sendHeader( $key . ': ' . $header );
			}
			
			Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
			Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );

			if( $image !== NULL )
			{
				Output::i()->sendHeader( "Content-Length: " . strlen( (string) $image ) );
				print (string) $image;
			}
			else
			{
				Output::i()->sendHeader( "Content-Length: " . $file->filesize() );
				$file->printFile();
			}
			exit;
		}
		catch ( UnderflowException )
		{
			Output::i()->sendOutput( '', 404 );
		}
	}

	/**
	 * Mark the image as read
	 *
	 * @note	We preload the next/prev image in the lightbox and do not want those images marked as read when doing so. While this speeds up the loading to the end user, we still need to separately mark the image as read when it's pulled into view.
	 * @return	void
	 */
	public function markread() : void
	{
		/* Run CSRF check */
		Session::i()->csrfCheck();

		/* Mark image as read */
		$this->image->markRead();

		/* We also want to update the views */
		$countUpdated = false;
		if ( Redis::isEnabled() )
		{
			try
			{
				Redis::i()->zIncrBy( 'topic_views', 1, static::$contentModel .'__' . $this->image->id );
				$countUpdated = true;
			}
			catch( Exception ) {}
		}
		
		if ( ! $countUpdated )
		{
			Db::i()->insert( 'core_view_updates', array(
					'classname'	=> static::$contentModel,
					'id'		=> $this->image->id
			) );
		}

		/* And return an AJAX response */
		Output::i()->json('OK');
	}

	/**
	 * View all of the metadata for this image
	 *
	 * @return	void
	 */
	protected function metadata() : void
	{
		/* Set navigation and title */
		$this->_setBreadcrumbAndTitle( $this->image );

		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'gallery_metadata', FALSE, array( 'sprintf' => $this->image->caption ) );
		Output::i()->output	= Theme::i()->getTemplate( 'view' )->metadata( $this->image );
	}

	/**
	 * Set this image as a cover photo
	 *
	 * @return	void
	 */
	protected function cover() : void
	{
		switch( Request::i()->set )
		{
			case 'album':
				$check = $this->image->canSetAsAlbumCover();
			break;

			case 'category':
				$check = $this->image->canSetAsCategoryCover();
			break;

			case 'both':
			default:
				$check = ( $this->image->canSetAsAlbumCover() AND $this->image->canSetAsCategoryCover() );
			break;
		}

		if ( !$check )
		{
			Output::i()->error( 'node_error', '2G188/5', 403, '' );
		}

		Session::i()->csrfCheck();
		$lang = '';

		if( $this->image->canSetAsAlbumCover() && ( Request::i()->set == 'album' or Request::i()->set == 'both' ) )
		{
			$this->image->directContainer()->cover_img_id	= $this->image->id;
			$this->image->directContainer()->save();

			$lang = Member::loggedIn()->language()->addToStack('set_as_album_done');
		}

		if( $this->image->canSetAsCategoryCover() && ( Request::i()->set == 'category' or Request::i()->set == 'both' ) )
		{
			$this->image->container()->cover_img_id	= $this->image->id;
			$this->image->container()->save();

			if( $lang )
			{
				$lang = Member::loggedIn()->language()->addToStack('set_as_both_done');
			}
			else
			{
				$lang = Member::loggedIn()->language()->addToStack('set_as_category_done');
			} 
		}

		/* Redirect back to image */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'message' => $lang ) );
		}
		else
		{
			Output::i()->redirect( $this->image->url() );
		}		
	}

	/**
	 * Rotate image
	 *
	 * @return	void
	 */
	protected function rotate() : void
	{
		/* Check permission */
		if( !$this->image->canEdit() )
		{
			Output::i()->error( 'node_error', '2G188/3', 403, '' );
		}

		Session::i()->csrfCheck();

		/* Determine angle to rotate */
		if( Request::i()->direction == 'right' )
		{
			$angle = ( Settings::i()->image_suite == 'imagemagick' and class_exists( 'Imagick', FALSE ) ) ? 90 : -90;
		}
		else
		{
			$angle = ( Settings::i()->image_suite == 'imagemagick' and class_exists( 'Imagick', FALSE ) ) ? -90 : 90;
		}

		/* Rotate the image and rebuild thumbnails */
		$file	= File::get( 'gallery_Images', $this->image->original_file_name );
		$image	= ImageClass::create( $file->contents() );
		$image->rotate( $angle );
		$file->replace( (string) $image );
		$this->image->buildThumbnails( $file );
		$this->image->original_file_name = (string) $file;
		$this->image->save();

		/* Respond or redirect */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array(
				'src'		=> (string) File::get( 'gallery_Images', $this->image->masked_file_name )->url,
				'message'	=> Member::loggedIn()->language()->addToStack('gallery_image_rotated'),
				'width'		=> $this->image->_dimensions['large'][0],
				'height'	=> $this->image->_dimensions['large'][1],
			) );
		}
		else
		{
			Output::i()->redirect( $this->image->url() );
		}
	}

	/**
	 * Change Author
	 *
	 * @return	void
	 */
	public function changeAuthor() : void
	{
		/* Permission check */
		if ( !$this->image->canChangeAuthor() )
		{
			Output::i()->error( 'no_module_permission', '2G188/6', 403, '' );
		}
		
		/* Build form */
		$form = new Form;
		$form->add( new Form\Member( 'author', NULL, TRUE ) );
		$form->class .= 'ipsForm--vertical ipsForm--change-image-author';

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$this->image->changeAuthor( $values['author'] );
			$this->image->save();
			
			Output::i()->redirect( $this->image->url() );
		}
		
		/* Display form */
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Set this image as a profile image
	 *
	 * @return	void
	 */
	public function setAsPhoto() : void
	{
		/* Permission check */
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission', '2G188/9', 403, '' );
		}

		/* Only images... */
		if ( $this->image->media )
		{
			Output::i()->error( 'no_photo_for_media', '2G188/A', 403, '' );
		}
		
		Session::i()->csrfCheck();
		
		/* Update profile photo */
		$file	= File::get( 'gallery_Images', $this->image->masked_file_name );
		$image	= ImageClass::create( $file->contents() );
		$photo	= File::create( 'core_Profile', $file->filename, (string) $image );

		Member::loggedIn()->pp_main_photo = (string) $photo;
		Member::loggedIn()->pp_thumb_photo = (string) $photo->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE );
		Member::loggedIn()->pp_photo_type = "custom";
		Member::loggedIn()->photo_last_update = time();
		Member::loggedIn()->save();
		Member::loggedIn()->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => 'gallery', 'id' => $this->image->id ) );

		/* Redirect back to image */
		if( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'message' => Member::loggedIn()->language()->addToStack('set_as_profile_photo') ) );
		}
		else
		{
			Output::i()->redirect( $this->image->url() );
		}
	}

	/**
	 * Get the next image
	 *
	 * @param bool $return (bool)		Return image object or redirect?
	 * @return Image|void
	 */
	protected function next( bool $return=FALSE ) : Image
	{
		$image = $this->image->nextItem() ? $this->image->nextItem() : $this->image->fetchFirstOrLast('first');

		if ( $return )
		{
			return $image;
		}

		$this->redirectToUrl( $image );
	}

	/**
	 * Get the previous image
	 *
	 * @param bool $return (bool)		Return image object or redirect?
	 * @return Image|void
	 */
	protected function previous( bool $return=FALSE ) : Image
	{
		$image = $this->image->prevItem() ? $this->image->prevItem() : $this->image->fetchFirstOrLast('last');

		if ( $return )
		{
			return $image;
		}

		$this->redirectToUrl( $image );
	}

	/**
	 * Move
	 *
	 * @return	void
	 * @note	Overridden so we can show an album selector as well
	 */
	protected function move(): void
	{
		try
		{
			/* @var Item $class */
			$class = static::$contentModel;
			$item = $class::loadAndCheckPerms( Request::i()->id );
			if ( !$item->canMove() )
			{
				throw new DomainException;
			}
			
			$form = Table::buildMoveForm( $item->container(), 'IPS\\gallery\\Image', array( 'where' => array( "album_owner_id=? OR " . Db::i()->in( 'album_submit_type', array( Album::AUTH_SUBMIT_PUBLIC, Album::AUTH_SUBMIT_GROUPS, Album::AUTH_SUBMIT_MEMBERS, Album::AUTH_SUBMIT_CLUB ) ), $item->author()->member_id ) ), $item->author() );

			if ( $values = $form->values() )
			{
				if ( isset( $values['move_to'] ) )
				{
					if ( $values['move_to'] == 'new_album' )
					{
						$albumValues = $values;
						unset( $albumValues['move_to'] );
						unset( $albumValues['move_to_category'] );
						unset( $albumValues['move_to_album'] );
						
						$target = new Album;
						$target->saveForm( $target->formatFormValues( $albumValues ) );
						$target->save();
					}
					else
					{						
						$target = ( Request::i()->move_to == 'category' ) ? $values['move_to_category'] : $values['move_to_album'];
					}
				}
				else
				{
					$target = $values['move_to_category'] ?? $values['move_to_album'];
				}

				$item->move( $target, FALSE );
				Output::i()->redirect( $item->url() );
			}
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		catch ( Exception )
		{
			Output::i()->error( 'node_error', '2G188/B', 403, '' );
		}
	}

	/**
	 * Set the breadcrumb and title
	 *
	 * @param	Item	$item	Content item
	 * @param	bool				$link	Link the content item element in the breadcrumb
	 * @return	void
	 */
	protected function _setBreadcrumbAndTitle( Item $item, bool $link=TRUE ): void
	{
		$container	= NULL;
		try
		{
			$container = $this->image->container();
			
			if ( $club = $container->club() )
			{
				FrontNavigation::$clubTabActive = TRUE;
				Output::i()->breadcrumb = array();
				Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
				Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				
				if ( Settings::i()->clubs_header == 'sidebar' )
				{
					Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $container, 'sidebar' );
				}
			}
			else
			{
				foreach ( $container->parents() as $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
			}
			Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
		}
		catch ( Exception ) { }

		/* Add album */
		if( $this->image->album_id )
		{
			Output::i()->breadcrumb[] = array( $this->image->directContainer()->url(), $this->image->directContainer()->_title );
		}

		Output::i()->breadcrumb[] = array( $link ? $this->image->url() : NULL, $this->image->mapped('title') );
		
		$title = ( isset( Request::i()->page ) and Request::i()->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $this->image->mapped('title'), intval( Request::i()->page ) ) ) ) : $this->image->mapped('title');
		Output::i()->title = $container ? ( $title . ' - ' . $container->_title ) : $title;
	}

	/**
	 * Redirect to the URL or album/category on error
	 *
	 * @param Image|null $image
	 * @return void
	 */
	protected function redirectToUrl( ?Image $image=null ): void
	{
		if ( $image )
		{
			$url = $image->url();

			if ( Request::i()->url()->queryString )
			{
				foreach ( Request::i()->url()->queryString as $k => $v )
				{
					if ( !in_array( $k, array( 'id', 'do' ) ) )
					{
						$url = $url->setQueryString( $k, $v );
					}
				}
			}

			Output::i()->redirect( $url );
		}
		else
		{
			/* Go to the album or category */
			Output::i()->redirect( $this->image->directContainer()->url() );
		}
	}

	/**
	 * Toggle not safe for work
	 *
	 * @return	void
	 */
	public function toggleNSFW() : void
	{
		/* Permission check */
		if ( !Settings::i()->gallery_nsfw or !$this->image->canEdit() )
		{
			Output::i()->error( 'no_module_permission', '1G188/F', 403, '' );
		}

		Session::i()->csrfCheck();

		/* Update profile photo */
		$this->image->nsfw = !$this->image->nsfw;
		$this->image->save();

		/* Redirect back to image */
		Output::i()->redirect( $this->image->url(), $this->image->nsfw ? 'set_gallery_image_nsfw_off' : 'set_gallery_image_nsfw_on' );
	}
}