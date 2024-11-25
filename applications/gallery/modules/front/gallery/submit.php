<?php
/**
 * @brief		Gallery Submission
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\modules\front\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Api\Webhook;
use IPS\core\FrontNavigation;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\gallery\Album;
use IPS\gallery\Application;
use IPS\gallery\Category;
use IPS\gallery\Image as ImageClass;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gallery Submission
 */
class submit extends Controller
{
	/**
	 * Manage addition of gallery images
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Init */
		$url = Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' );

		if( isset( Request::i()->_pi ) )
		{
			$url = $url->setQueryString( '_pi', Request::i()->_pi );
		}

		/* Init our form variables and check container */
		$container	= $this->chooseContainerForm( $url );
		$images		= NULL;
		$errors		= array();

		/* If we've submitted that and have our values we need, it's time to show the upload form */
		if( is_array( $container ) )
		{
			$url = $url->setQueryString( 'category', $container['category']->_id );

			if ( $container['album'] )
			{
				$url = $url->setQueryString( 'album', $container['album']->_id );
			}
			else
			{
				$url = $url->setQueryString( 'noAlbum', 1 );
			}
			
			if ( isset( $container['guest_email'] ) )
			{
				$url = $url->setQueryString( 'guest_email', $container['guest_email'] );
			}

			$images = $this->chooseImagesForm( $url, $container );

			if( isset( $images['errors'] ) )
			{
				$errors = $images['errors'];
			}

			$images	= $images['html'];
		}

		/* Are we in da club? */
		$club = NULL;

		if ( is_array( $container ) AND isset( $container['category'] ) AND $container['category'] )
		{
			try
			{
				if ( $club = $container['category']->club() )
				{
					FrontNavigation::$clubTabActive = TRUE;
					Output::i()->breadcrumb = array();
					Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
					Output::i()->breadcrumb[] = array( $club->url(), $club->name );
					Output::i()->breadcrumb[] = array( $container['category']->url(), $container['category']->_title );
					
					if ( Settings::i()->clubs_header == 'sidebar' )
					{
						Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $container['category'], 'sidebar' );
					}
				}
			}
			catch ( OutOfRangeException ) {}
		}

		/* Set online user location */
		Session::i()->setLocation( Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ), array(), 'loc_gallery_adding_image' );

		/* Output */
		Application::outputCss();
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'submit.css' ) );

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery-ui.js', 'core', 'interface' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_submit.js', 'gallery' ) );
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->title = Member::loggedIn()->language()->addToStack('add_gallery_image');
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( ( Member::loggedIn()->group['g_movies'] ) ? 'add_gallery_image_movies' : 'add_gallery_image' ) );

		if( Request::i()->isAjax() && isset( Request::i()->noWrapper ) )
		{
			$tagsField		= NULL;
			$imageTagsField = $tagsField;

			/* Tags */
			if ( is_array( $container ) AND isset( $container['category'] ) AND $container['category'] AND ImageClass::canTag( NULL, $container['category'] ) )
			{
				$tagsField		= ImageClass::tagsFormField( null, $container['category'] );
				$imageTagsField	= $tagsField;

				if( $tagsField )
				{
					$tagsField = $tagsField->html();

					$imageTagsField->name	= $imageTagsField->name . '_DEFAULT';
					$imageTagsField			= $imageTagsField->html();
				}

				Member::loggedIn()->language()->parseOutputForDisplay( $tagsField );
			}

			Output::i()->json( array(
				'container'		=> is_string( $container ) ? $container : NULL,
				'containerInfo'	=> is_string( $container ) ? '' : Theme::i()->getTemplate( 'submit' )->container( $container ),
				'images'		=> $images,
				'imageTags'		=> ( $imageTagsField ) ? preg_replace( '/data-ipsAutocomplete(?!\-)/', '', $imageTagsField ) : $imageTagsField,
				'tagsField'		=> $tagsField,
				'imageErrors'	=> $errors,
			) );
		}
		else
		{
			/* We create a dummy generic form so that we can output its elements and then clone them using fancy javascript */
			$allImagesForm = new Form( 'all_images_form', 'submit' );
			$allImagesForm->add( new TextArea( 'image_credit_info', NULL, FALSE ) );
			$allImagesForm->add( new Text( 'image_copyright', NULL, FALSE, array( 'maxLength' => 255 ) ) );

			if( Member::loggedIn()->member_id )
			{
				$allImagesForm->add( new YesNo( 'image_auto_follow', (bool) Member::loggedIn()->auto_follow['content'], FALSE, array(), NULL, NULL, Member::loggedIn()->language()->addToStack( 'image_auto_follow_suffix' ) ) );
			}

			if( Settings::i()->gallery_nsfw )
			{
				$allImagesForm->add( new YesNo( 'image_nsfw', FALSE, FALSE, array(), NULL, NULL, Member::loggedIn()->language()->addToStack( 'image_nsfw_suffix' ) ) );
			}

			/* Tags */
			if ( is_array( $container ) AND isset( $container['category'] ) AND $container['category'] AND ImageClass::canTag( NULL, $container['category'] ) )
			{
				if( $tagsField = ImageClass::tagsFormField( null, $container['category'] ) )
				{
					$allImagesForm->add( $tagsField );
				}
			}

			$formElements = ImageClass::formElements( NULL, ( isset( $container['category'] ) ) ? $container['category'] : NULL );

			foreach( $formElements as $element )
			{
				if( $element->name == 'image_tags' )
				{
					if ( !is_array( $container ) OR !isset( $container['category'] ) OR !$container['category'] OR !ImageClass::canTag( NULL, $container['category'] ) )
					{
						continue;
					}
				}

				$element->name	= $element->name . '_DEFAULT';
				$allImagesForm->add( $element );
			}

			$allImagesForm->add( new TextArea( 'image_textarea_DEFAULT', NULL, FALSE ) );

			/* These fields are conditional and will not always show for each image */
			$allImagesForm->add( new YesNo( "image_gps_show_DEFAULT", Settings::i()->gallery_maps_default, FALSE ) );
			$allImagesForm->add( new Upload( "image_thumbnail_DEFAULT", NULL, FALSE, array(
				'storageExtension'	=> 'gallery_Images', 
				'image'				=> TRUE,
				'maxFileSize'		=> Member::loggedIn()->group['g_max_upload'] ? ( Member::loggedIn()->group['g_max_upload'] / 1024 ) : NULL,
				'canBeModerated'	=> TRUE
			) ) );

			/* And output */
			$category = ( is_array( $container ) AND isset( $container['category'] ) AND $container['category'] ) ? $container['category'] : NULL;
			Output::i()->output = Theme::i()->getTemplate( 'submit' )->wrapper( $container, $images, $club, $allImagesForm );
		}
	}
	
	/**
	 * Step 1: Choose the container
	 *
	 * @param Url $url	The URL
	 * @return	string|array
	 */
	public function chooseContainerForm( Url $url ): array|string
	{
		/* Have we chosen a category? */
		$category = NULL;

		if ( isset( Request::i()->category ) )
		{
			try
			{
				$category = Category::loadAndCheckPerms( Request::i()->category, 'add' );
			}
			catch ( OutOfRangeException ) { }
		}

		/* What about an album? */
		$album = NULL;

		if ( isset( Request::i()->album ) )
		{
			try
			{
				$album = Album::loadAndCheckPerms( Request::i()->album, 'add' );

				/* If it's an album, make sure we grab the current category */
				try
				{
					$category = Category::loadAndCheckPerms( $album->category_id, 'add' );
				}
				catch ( OutOfRangeException ) { }
			}
			catch ( OutOfRangeException ) { }
		}
		
		/* If we have chosen an album we can return now */
		if( $category AND $album )
		{
			return array( 'category' => $category, 'album' => $album, 'guest_email' => isset( Request::i()->guest_email ) ? Request::i()->guest_email : NULL );
		}

		/* If we have chosen no album specifically, we can just return now */
		if ( isset( Request::i()->noAlbum ) AND Request::i()->noAlbum AND $category AND $category->allow_albums != 2 )
		{
			return array( 'category' => $category, 'album' => NULL, 'guest_email' => isset( Request::i()->guest_email ) ? Request::i()->guest_email : NULL );
		}

		/* If we haven't selected a category yet... */
		if ( !$category )
		{
			/* If there's only one category automatically select it, otherwise show the form */
			$category = Category::theOnlyNode();

			if( !$category )
			{
				$chooseCategoryForm = new Form( 'choose_category', 'continue' );
				$chooseCategoryForm->add( new Node( 'image_category', NULL, TRUE, array(
					'url'					=> $url,
					'class'					=> 'IPS\gallery\Category',
					'permissionCheck'		=> function( $node ){
						if( $node->can('add') and ( $node->allow_albums != 2 or Member::loggedIn()->group['g_create_albums'] or Album::loadForSubmit( $node ) ) )
						{
							return TRUE;
						}
						return FALSE;
					},
					'clubs'					=> Settings::i()->club_nodes_in_apps
				) ) );

				if ( $chooseCategoryFormValues = $chooseCategoryForm->values() )
				{
					$category = $chooseCategoryFormValues['image_category'];
				}
				else
				{
					return $chooseCategoryForm->customTemplate( array( Theme::i()->getTemplate('submit'), 'chooseCategory' ) );
				}
			}
		}
		
		/* Do we have any posting information to show? */
		$guestEmailError = NULL;
		if ( isset( Request::i()->guest_email_submit ) )
		{
			if ( !Request::i()->guest_email )
			{
				$guestEmailError = 'form_required';
			}
			else
			{
				try
				{
					Email::validateEmail( Request::i()->guest_email, TRUE );
					Request::i()->_pi = TRUE;
				}
				catch ( Exception $e )
				{
					$guestEmailError = $e->getMessage();
				}
			}
		}
		if ( !isset( Request::i()->_pi ) )
		{			
			$guestPostBeforeRegister = ( !Member::loggedIn()->member_id ) ? ( $category and !$category->can( 'add', Member::loggedIn(), FALSE ) ) : FALSE;
			$modQueued = ImageClass::moderateNewItems( Member::loggedIn(), $category, $guestPostBeforeRegister );
			if ( $guestPostBeforeRegister or $modQueued )
			{
				return Theme::i()->getTemplate('submit')->postingInformation( $guestPostBeforeRegister, $modQueued, $url->setQueryString( array( 'category' => $category->_id ) ), $guestEmailError );
			}
		}
					
		/* Can we create an album in this category? */
		$canCreateAlbum		= ( $category->allow_albums and Member::loggedIn()->group['g_create_albums'] );
		$maximumAlbums		= Member::loggedIn()->group['g_album_limit'];
		$currentAlbumCount	= Db::i()->select( 'COUNT(*)', 'gallery_albums', array( 'album_owner_id=?', Member::loggedIn()->member_id ) )->first();

		/* If we can, build a form */
		$createAlbumForm	= NULL;
		if ( $canCreateAlbum and ( !$maximumAlbums or $maximumAlbums > $currentAlbumCount ) )
		{
			/* Build the create form... */
			$createAlbumForm = new Form( 'new_album', 'create_new_album' );
			$createAlbumForm->class .= 'ipsForm--vertical ipsForm--new-album';
			$createAlbumForm->hiddenValues['category'] = $category->_id;
			$createAlbumForm->hiddenValues['_pi'] = Request::i()->_pi;

			$album	= new Album;
			$album->form( $createAlbumForm );
			unset( $createAlbumForm->elements['']['album_category'] );
			
			/* And when we submit it, create an album... */
			if ( $createAlbumFormValues = $createAlbumForm->values() )
			{
				unset( $createAlbumFormValues['category'], $createAlbumFormValues['_pi'] );
				$createAlbumFormValues['album_category'] = $category;
				$album->saveForm( $album->formatFormValues( $createAlbumFormValues ) );
				
				Webhook::fire( 'galleryAlbum_create', $album, $album->webhookFilters() );

				Member::loggedIn()->achievementAction( 'core', 'NewContentItem', $album->asItem() );

				return array( 'category' => $category, 'album' => $album, 'guest_email' => isset( Request::i()->guest_email ) ? Request::i()->guest_email : NULL );
			}
			
			/* Otherwise, display it*/
			$createAlbumForm = $createAlbumForm->customTemplate( array( Theme::i()->getTemplate( 'submit', 'gallery' ), 'createAlbum' ) );
		}
		
		/* Can we choose an existing album? */
		$existingAlbumForm	= NULL;
		$albumsInCategory	= Member::loggedIn()->member_id ? Album::loadForSubmit( $category ) : array();

		if ( count( $albumsInCategory ) )
		{
			/* Build the existing album form... */
			$existingAlbumForm = new Form( 'choose_album', 'choose_selected_album' );
			$existingAlbumForm->class .= 'cGalleryChooseAlbum';
			$existingAlbumForm->hiddenValues['category'] = $category->_id;
			$existingAlbumForm->hiddenValues['_pi'] = Request::i()->_pi;
			$albums = array();
			foreach( $albumsInCategory as $id => $album )
			{
				$albums[ $id ] = $album->_title;
			}
			$existingAlbumForm->add( new Radio( 'existing_album', NULL, FALSE, array( 'options' => $albums, 'noDefault' => TRUE ), NULL, NULL, NULL, 'set_album_owner' ) );
			
			/* When we submit it, we can continue... */
			if ( $existingAlbumFormValues = $existingAlbumForm->values() )
			{
				return array( 'category' => $category, 'album' => Album::loadAndCheckPerms( $existingAlbumFormValues['existing_album'], 'add' ), 'guest_email' => isset( Request::i()->guest_email ) ? Request::i()->guest_email : NULL );
			}
			
			/* Otherwise, display it */
			$existingAlbumForm = $existingAlbumForm->customTemplate( array( Theme::i()->getTemplate( 'submit', 'gallery' ), 'existingAlbumForm' ), $category );
		}
		
		/* If there's nothing we can do, we can just continue */
		if ( !$canCreateAlbum and !$albumsInCategory )
		{
			if ( $category->allow_albums == 2 )
			{
				Output::i()->error( 'node_error_no_perm', '2G376/2', 403, '' );
			}
			return array( 'category' => $category, 'album' => NULL, 'guest_email' => isset( Request::i()->guest_email ) ? Request::i()->guest_email : NULL );
		}
		/* Otherwise, ask the user what they want to do */
		else
		{
			return Theme::i()->getTemplate('submit')->chooseAlbum( $category, $createAlbumForm, $canCreateAlbum, $maximumAlbums, $existingAlbumForm );
		}
	}
	
	/**
	 * Step 2: Upload images and configure details
	 *
	 * @param Url $url	The URL
	 * @param array $data	The current data
	 * @return	string|array
	 */
	public function chooseImagesForm( Url $url, array $data ): array|string
	{				
		$album		= $data['album'];
		$category	= $data['category'];

		/* How many images are allowed? */
		$maxNumberOfImages = NULL;
		if ( $album and Member::loggedIn()->group['g_img_album_limit'] )
		{
			$maxNumberOfImages = Member::loggedIn()->group['g_img_album_limit'] - ( $album->count_imgs + $album->count_imgs_hidden );
		}

		/* Limit uploads to what we know we can process on this server */
		if( $maxVars = @ini_get( 'max_input_vars') )
		{
			$maxNumberOfImages = round( ( $maxVars / 2 ) - 10, 0 );
		}

		/* Init form */
		$form = new Form( 'upload_images', 'continue', $url );
		$form->class = 'ipsForm--vertical ipsForm--choose-images-from';

		/* These form fields are not displayed to the user, however the fancy uploader process populates them via javascript */
		$form->add( new TextArea( 'credit_all', NULL, FALSE ) );
		$form->add( new TextArea( 'copyright_all', NULL, FALSE ) );
		$form->add( new TextArea( 'tags_all', NULL, FALSE ) );
		$form->add( new TextArea( 'prefix_all', NULL, FALSE ) );
		$form->add( new TextArea( 'images_order', NULL, FALSE ) );
		$form->add( new TextArea( 'images_info', NULL, FALSE ) );

		if( Settings::i()->gallery_nsfw )
		{
			$form->add( new Number( 'nsfw_all', NULL, FALSE ) );
		}

		if( Member::loggedIn()->member_id )
		{
			$form->add( new Number( 'images_autofollow_all', 1, FALSE ) );
		}

		/* Add upload field */
		$maxFileSizes = array();
		$options = array(
			'storageExtension'	=> 'gallery_Images',
			'image'				=> TRUE,
			'checkImage'		=> FALSE,
			'multiple'			=> TRUE,
			'minimize'			=> FALSE,
			'template'			=> "gallery.submit.imageItem",
			'canBeModerated'	=> TRUE
		);

		if( $maxNumberOfImages )
		{
			$options['maxFiles'] = $maxNumberOfImages;
		}

		$unlimitedMaxSize = TRUE;

		if ( Member::loggedIn()->group['g_max_upload'] )
		{
			$maxFileSizes['image'] = Member::loggedIn()->group['g_max_upload'] / 1024;
			$unlimitedMaxSize = FALSE;
		}
		if ( Member::loggedIn()->group['g_movies'] )
		{
			$options['image'] = NULL;
			$options['allowedFileTypes'] = array_merge( Image::supportedExtensions(), array( 'flv', 'f4v', 'wmv', 'mpg', 'mpeg', 'mp4', 'mkv', 'm4a', 'm4v', '3gp', 'mov', 'avi', 'webm', 'ogg', 'ogv' ) );
			if ( Member::loggedIn()->group['g_movie_size'] )
			{
				$maxFileSizes['movie'] = Member::loggedIn()->group['g_movie_size'] / 1024;
				$unlimitedMaxSize = FALSE;
			}
			else
			{
				$unlimitedMaxSize = TRUE;
			}
		}
		if ( count( $maxFileSizes ) AND !$unlimitedMaxSize )
		{
			$options['maxFileSize'] = max( $maxFileSizes );
		}

		$uploader = new Upload( 'images', array(), TRUE, $options, function( $val ) use ( $maxNumberOfImages, $maxFileSizes ) {
			if ( $maxNumberOfImages !== NULL and count( $val ) > $maxNumberOfImages )
			{
				if ( $maxNumberOfImages < 1 )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'gallery_images_no_more' ) );
				}
				else
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'gallery_images_too_many', FALSE, array( 'pluralize' => array( $maxNumberOfImages ) ) ) );
				}
			}

			foreach ( $val as $file )
			{
				$ext = mb_substr( $file->filename, ( mb_strrpos( $file->filename, '.' ) + 1 ) );
				if ( in_array( mb_strtolower( $ext ), Image::supportedExtensions() ) )
				{
					/* The size was saved as kb, then divided by 1024 above to figure out how many MB to allow. So now we have '2' for 2MB for instance, so we need
						to multiply that by 1024*1024 in order to get the byte size again */
					if ( isset( $maxFileSizes['image'] ) and $file->filesize() > ( $maxFileSizes['image'] * 1048576 ) )
					{
						throw new DomainException( Member::loggedIn()->language()->addToStack( 'upload_image_too_big', FALSE, array( 'sprintf' => array( Filesize::humanReadableFilesize( $maxFileSizes['image'] * 1048576 ) ) ) ) );
					}
				}
				elseif ( isset( $maxFileSizes['movie'] ) and $file->filesize() > ( $maxFileSizes['movie'] * 1048576 ) )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'upload_movie_too_big', FALSE, array( 'sprintf' => array( Filesize::humanReadableFilesize( $maxFileSizes['movie'] * 1048576 ) ) ) ) );
				}
			}
		} );

		$uploader->template = array( Theme::i()->getTemplate( 'forms', 'gallery', 'front' ), 'imageUpload' );
		$form->add( $uploader );

		/* Add tag fields so we can validate it */
		if( isset( Request::i()->images_info ) AND ImageClass::canTag( NULL, $category ) )
		{
			$imagesData		= json_decode( Request::i()->images_info, true );

			foreach( Request::i()->images_existing as $imageId )
			{
				if( $tagsField = ImageClass::tagsFormField( null, $category ) )
				{
					$tagsFieldName		= $tagsField->name . '_' . $imageId;
					$tagsField->name	= $tagsFieldName;
					$tagsPrefix			= NULL;
					$tagsValue			= NULL;

					foreach( $imagesData as $_imageData )
					{
						if( $_imageData['name'] == 'image_tags_' . $imageId )
						{
							$tagsValue = $_imageData['value'];
						}

						if( $_imageData['name'] == 'image_tags_' . $imageId . '_prefix' )
						{
							$tagsPrefix = $_imageData['value'];
						}
					}

					if( !$tagsValue )
					{
						$tagsValue	= Request::i()->tags_all;
						$tagsPrefix	= Request::i()->prefix_all;
					}

					$checkboxInput	= $tagsFieldName . '_freechoice_prefix';
					$prefixinput	= $tagsFieldName . '_prefix';

					Request::i()->$tagsFieldName	= ( is_array( $tagsValue ) ) ? implode( "\n", $tagsValue ) : $tagsValue;
					Request::i()->$checkboxInput	= 1;
					Request::i()->$prefixinput		= $tagsPrefix;

					$form->add( $tagsField );
				}
			}
		}

		$imagesWithIssues = array();

		/* Process submission */
		if ( $values = $form->values() )
		{
			return array( 'html' => $this->processUploads( $values, $url, $data ) );
		}
		elseif( isset( Output::i()->httpHeaders['X-IPS-FormError'] ) AND Output::i()->httpHeaders['X-IPS-FormError'] == 'true' )
		{
			foreach ( $form->elements as $elements )
			{
				foreach ( $elements as $_name => $element )
				{
					if ( !$element->valueSet )
					{
						$element->setValue( FALSE, TRUE );
					}

					if( !empty( $element->error ) )
					{
						if( $element->name == 'images' )
						{
							$fieldName	= 'images';
							$fieldId	= 0;
						}
						else
						{
							$delim		= mb_strrpos( $element->name, '_' );
							$fieldName	= mb_substr( $element->name, 0, $delim );
							$fieldId	= mb_substr( $element->name, $delim + 1 );
						}

						$fieldError	= $element->error;

						if( isset( $imagesWithIssues[ $fieldId ] ) )
						{
							$imagesWithIssues[ $fieldId ][ $fieldName ] = Member::loggedIn()->language()->addToStack( $fieldError );
						}
						else
						{
							$imagesWithIssues[ $fieldId ] = array( $fieldName => Member::loggedIn()->language()->addToStack( $fieldError ) );
						}
					}
				}
			}
		}
		
		/* Display */
		return array( 'html' => Theme::i()->getTemplate( 'submit' )->uploadImages( $form, $category ), 'errors' => $imagesWithIssues );
	}
	
	/**
	 * Process the uploaded files
	 *
	 * @param array $values		Values from the form submission
	 * @param Url $url	The URL
	 * @param array $data	The current data
	 * @return	string
	 * @note	This returns a multiredirector instance which processes all of the images
	 */
	public function processUploads( array $values, Url $url, array $data ): string
	{
		/* Get any records we had before in case we need to delete them */
		$existing = iterator_to_array( Db::i()->select( '*', 'gallery_images_uploads', array( 'upload_session=?', session_id() ) )->setKeyField( 'upload_location' ) );

		/* Get our image order first, as that's the order we want to loop through in */
		$imageOrder = json_decode( $values['images_order'], true );

		/* Get the image info (caption, etc.) - note this data has NOT been sanitized at this point */
		$imagesData = json_decode( $values['images_info'], true );

		/* Build a list of image tags */
		$imageTags = [];

		/* Loop through the values we have */
		$inserts	= array();
		$i			= 0;

		foreach ( $values['images'] as $image )
		{
			$i++;

			$imageData = array();

			if( is_array( $imagesData ) )
			{
				foreach( $imagesData as $dataEntry )
				{
					if( mb_strpos( $dataEntry['name'], '_' . $image->tempId ) !== FALSE )
					{
						$imageData[ str_replace( '_' . $image->tempId, '', str_replace( 'filedata__', '', $dataEntry['name'] ) ) ] = $dataEntry['value'];
					}
				}
			}

			/* Set the global values if they're not overridden */
			if( !isset( $imageData['image_copyright'] ) OR !$imageData['image_copyright'] )
			{
				$imageData['image_copyright'] = $values['copyright_all'];
			}

			if( !isset( $imageData['image_credit_info'] ) OR !$imageData['image_credit_info'] )
			{
				$imageData['image_credit_info'] = $values['credit_all'];
			}

			if( !isset( $imageData['image_tags'] ) OR !$imageData['image_tags'] )
			{
				$imageData['image_tags']		= $values['tags_all'];
				$imageData['image_tags_prefix']	= $values['prefix_all'];
			}

			if( Member::loggedIn()->member_id )
			{
				$imageData['image_auto_follow'] = $values['images_autofollow_all'];
			}

			if( Settings::i()->gallery_nsfw and !isset( $imageData['image_nsfw'] ) )
			{
				$imageData['image_nsfw'] = $values['nsfw_all'];
			}

			/* Fix descriptions */
			$imageData['image_description'] = ( isset( $imageData['image_textarea'] ) AND $imageData['image_textarea'] ) ? $imageData['image_textarea'] : ( ( isset( $imageData['image_description'] ) ) ? $imageData['image_description'] : '' );
			
			/* Will we need to moderate this? */
			$imageData['requires_moderation'] = $image->requiresModeration;

			/* Image scanner labels */
			$imageData['labels'] = $image->labels;

			if( isset( $imageData['image_tags'] ) and $imageData['image_tags'] )
			{
				$tags = is_array( $imageData['image_tags'] ) ? $imageData['image_tags'] : explode( "\n", $imageData['image_tags'] );
				$imageTags = array_merge( $imageTags, $tags );
			}

			if ( !isset( $existing[ (string) $image ] ) )
			{
				$inserts[] = array(
					'upload_session'	=> session_id(),
					'upload_member_id'	=> (int) Member::loggedIn()->member_id,
					'upload_location'	=> (string) $image,
					'upload_file_name'	=> $image->originalFilename,
					'upload_date'		=> time(),
					'upload_order'		=> ( is_array( $imageOrder ) ) ? array_search( $image->tempId, $imageOrder ) : $i,
					'upload_data'		=> json_encode( $imageData ),
					'upload_exif'		=> $image->exifData ? json_encode( $image->exifData ) : NULL
				);
			}

			unset( $existing[ (string) $image ], $image );
		}

		/* Insert them into the database */
		if( count( $inserts ) )
		{
			Db::i()->insert( 'gallery_images_uploads', $inserts );
		}

		/* Delete any that we don't have any more */
		foreach ( $existing as $location => $file )
		{
			try
			{
				File::get( 'gallery_Images', $location )->delete();
			}
			catch ( Exception ) { }
			
			Db::i()->delete( 'gallery_images_uploads', array( 'upload_session=? and upload_location=?', $file['upload_session'], $file['upload_location'] ) );
		}

		/* Get the total number of images now as it will decrease each cycle moving forward */
		$totalImages = Db::i()->select( 'count(*)', 'gallery_images_uploads', array( 'upload_session=?', session_id() ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();

		$url = $url->setQueryString( [
			'totalImages' => $totalImages,
			'tags' => $imageTags ] );
		if ( isset( $data['guest_email'] ) )
		{
			$url = $url->setQueryString( 'guest_email', $data['guest_email'] );
		}

		/* Now return the multiredirector */
		return $this->saveImages( $url );
	}

	/**
	 * Wizard step: Process the saved data to create an album and save images
	 *
	 * @param Url|null $url The URL
	 * @return string|null
	 */
	public function saveImages( Url $url=NULL ): ?string
	{
		/* Process */
		$url = $url ? $url->setQueryString( 'do', 'saveImages' ) : Request::i()->url()->stripQueryString( array( 'mr' ) );
		
		/* Return the multiredirector */
		$multiRedirect = (string) new MultipleRedirect( $url,
			/* Function to process each image */
			function( $offset ) use ( $url )
			{
				$offset = intval( $offset );
				
				$existing = Db::i()->select( '*', 'gallery_images_uploads', array( 'upload_session=?', session_id() ), 'upload_order ASC', array( 0, 1 ), NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->setKeyField( 'upload_location' );

				/* Get category and album data */
				$data = $this->chooseContainerForm( $url );

				foreach( $existing as $location => $file )
				{

					/* Start with the basic data */
					$values = array(
						'category'		=> $data['category']->_id,
						'imageLocation'	=> $location,
						'album'			=> $data['album'] ? $data['album']->_id : NULL,
						'guest_email'	=> $data['guest_email'] ?? NULL
					);
					
					/* Get the data from the row and set */
					$fileData = json_decode( $file['upload_data'], TRUE );
					if ( isset( $fileData['requires_moderation'] ) )
					{
						$values['imageRequiresModeration'] = $fileData['requires_moderation'];
						unset( $fileData['requires_moderation'] );
					}

					if( count( $fileData ) )
					{
						foreach( $fileData as $k => $v )
						{
							$values[ preg_replace("/^filedata_[0-9]*_/i", '', $k ) ]	= $v;
						}	
					}

					if( isset( $values['image_tags'] ) AND $values['image_tags'] AND !is_array( $values['image_tags'] ) )
					{
						$values['image_tags']	= explode( "\n", $values['image_tags'] );
					}
					
					/* If no title was saved, use the original file name */
					if( !isset( $values['image_title'] ) )
					{
						$values['image_title'] = $file['upload_file_name'];
					}

					/* Fix thumbnail reference if this is a video */
					if( isset( $values['image_thumbnail'] ) )
					{
						$thumbnailReset = FALSE;

						foreach( $values as $key => $value )
						{
							if( mb_strpos( $key, 'image_thumbnail_existing' ) === 0 )
							{
								try
								{
									$thumb = Db::i()->select( '*', 'core_files_temp', array( 'id=?', $value ) )->first();

									$values['image_thumbnail'] = $thumb['contents'];
									$values['image_thumbnail_requires_moderation'] = $thumb['requires_moderation'];
									$thumbnailReset = TRUE;
									Db::i()->delete( 'core_files_temp', array( 'id=?', $value ) );
									break;
								}
								catch( UnderflowException ){}
							}
						}

						if( !$thumbnailReset )
						{
							unset( $values['image_thumbnail'] );
						}
					}

					/* If GPS is supported but the admin did not specify whether to show the map or not, then default to showing it */
					$image = File::get( 'gallery_Images', $location );

					$exif = $file['upload_exif'] ? json_decode( $file['upload_exif'], true ) : NULL;
					$values['_exif'] = $exif;

					if( GeoLocation::enabled() and $exif )
					{
						if( isset( $exif['GPS.GPSLatitudeRef'] ) && isset( $exif['GPS.GPSLatitude'] ) && isset( $exif['GPS.GPSLongitudeRef'] ) && isset( $exif['GPS.GPSLongitude'] ) )
						{
							$values['image_gps_show'] = ( isset( $values['image_gps_show'] ) ) ? (int) ( isset( $values['image_gps_show_checkbox'] ) ) : Settings::i()->gallery_maps_default;
						}
					}

					/* We will create a dummy form to sanitize the elements */
					$formElements	= ImageClass::formElements();
					$testValuesForm	= new Form;

					foreach( $formElements as $key => $element )
					{
						/* If this is a guest posting before registration, we can't check the CAPTCHA as none is added to the form */
						if( $key == 'captcha' )
						{
							continue;
						}

						$testValuesForm->add( $element );

						$name = 'image_' . $key;

						if( isset( $values[ $name ] ) )
						{
							if( $name == 'image_description' )
							{
								Request::i()->filedata__image_description = $values[ $name ];
							}
							else
							{
								Request::i()->$name	= ( is_array( $values[ $name ] ) ) ? implode( "\n", $values[ $name ] ) : $values[ $name ];
							}

							if( $name == 'image_tags' )
							{
								$checkboxInput	= $name . '_freechoice_prefix';
								$prefixinput	= $name . '_prefix';

								Request::i()->$checkboxInput	= 1;
								Request::i()->$prefixinput		= ( isset( $values[ $name . '_prefix' ] ) ) ? $values[ $name . '_prefix' ] : '';

								unset( $values[ $name . '_prefix' ] );
							}
							elseif( $name == 'image_auto_follow' AND Member::loggedIn()->member_id )
							{
								$checkboxInput	= $name . '_checkbox';

								Request::i()->$checkboxInput	= $values[ $name ];
							}
							elseif( $name == 'image_nsfw' )
							{
								$checkboxInput	= $name . '_checkbox';

								Request::i()->$checkboxInput	= $values[$checkboxInput] ?? $values[$name];
								Request::i()->$name			= $values[$checkboxInput] ?? $values[$name];
							}

							unset( $values[ $name ] );
						}
					}

					$submitted = "{$testValuesForm->id}_submitted";

					Request::i()->$submitted	= true;
					Request::i()->csrfKey		= Session::i()->csrfKey;

					if( $cleaned = $testValuesForm->values() )
					{
						foreach( $cleaned as $k => $v )
						{
							$values[ str_replace( 'filedata__', '', $k ) ] = $v;
						}
					}

					/* And now create the images */
					$image	= ImageClass::createFromForm( $values, $data['category'], FALSE );
					$image->markRead();
					
					/* Delete that file */
					Db::i()->delete( 'gallery_images_uploads', array( 'upload_unique_id=?', $file['upload_unique_id'] ) );

					/* Go to next */
					return array( ++$offset, Member::loggedIn()->language()->addToStack('processing'), number_format( 100 / ( Request::i()->totalImages ?: $offset ) * $offset, 2 ) );
				}

				/* Update last image info */
				$data['category']->setLastImage();
				$data['category']->save();

				/* And Album */
				if( $data['album'] )
				{
					$data['album']->setLastImage();
					$data['album']->save();
				}
				
				return NULL;
			},
			
			/* Function to call when done */
			function() use( $url )
			{
				if ( isset( Request::i()->guest_email ) )
				{
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register', 'front', 'register' ) );
				}
				elseif ( Request::i()->totalImages === 1 )
				{
					/* If we are only sending one image, send a normal notification and award points */
					$image = ImageClass::constructFromData( Db::i()->select( '*', 'gallery_images', NULL, 'image_id DESC', 1 )->first() );
					if ( !$image->hidden() )
					{
						$image->sendNotifications();
					}
					else if( !in_array( $image->hidden(), array( -1, -3 ) ) )
					{
						$image->sendUnapprovedNotification();
					}
										
					/* Then redirect */
					Output::i()->redirect( $image->url() );
				}
				else
				{
					/* Get category and album data */
					$data = $this->chooseContainerForm( $url );

					if ( Member::loggedIn()->moderateNewContent() OR ImageClass::moderateNewItems( Member::loggedIn(), $data['category'] ) )
					{
						ImageClass::_sendUnapprovedNotifications( $data['category'], $data['album'] );
					}
					else
					{
						ImageClass::_sendNotifications( $data['category'], $data['album'], null, Request::i()->tags ?? [] );
					}
					
					Output::i()->redirect( $data['album'] ? $data['album']->url() : $data['category']->url() );
				}
			}
		);
		
		/* Display redirect */
		return Theme::i()->getTemplate( 'submit' )->processing( $multiRedirect );
	}

	/**
	 * Determine whether the uploaded image has GPS information embedded
	 *
	 * @return void
	 */
	protected function checkGps() : void
	{
		/* If the service is not enabled just return now */
		if( !GeoLocation::enabled() )
		{
			Output::i()->json( array( 'hasGeo' => 0 ) );
		}

		try
		{
			$temporaryImage = Db::i()->select( '*', 'core_files_temp', array( 'storage_extension=? AND id=?', 'gallery_Images', Request::i()->imageId ) )->first();
		}
		catch( UnderflowException )
		{
			Output::i()->error( 'node_error', '2G376/1', 404, '' );
		}

		if( Image::exifSupported() and mb_strpos( $temporaryImage['mime'], 'image' ) === 0 )
		{
			$exif	= Image::create( File::get( $temporaryImage['storage_extension'], $temporaryImage['contents'] )->contents() )->parseExif();

			if( count( $exif ) )
			{
				if( isset( $exif['GPS.GPSLatitudeRef'] ) && isset( $exif['GPS.GPSLatitude'] ) && isset( $exif['GPS.GPSLongitudeRef'] ) && isset( $exif['GPS.GPSLongitude'] ) )
				{
					Output::i()->json( array( 'hasGeo' => 1 ) );
				}
			}
		}

		Output::i()->json( array( 'hasGeo' => 0 ) );
	}
}