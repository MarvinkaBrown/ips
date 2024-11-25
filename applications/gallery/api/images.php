<?php
/**
 * @brief		Gallery Images API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		8 Dec 2015
 */

namespace IPS\gallery\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Content\Api\ItemController;
use IPS\Content\Item;
use IPS\Db;
use IPS\File;
use IPS\gallery\Album;
use IPS\gallery\Category;
use IPS\gallery\Image;
use IPS\gallery\Image\Comment;
use IPS\gallery\Image\Review;
use IPS\Member;
use IPS\Request;
use IPS\Text\Parser;
use OutOfRangeException;
use function count;
use function defined;
use function strlen;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Gallery Images API
 */
class images extends ItemController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\gallery\Image';
	
	/**
	 * GET /gallery/images
	 * Get list of images
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only images the authorized user can view will be included
	 * @apiparam	string	ids			    Comma-delimited list of image IDs
	 * @apiparam	string	categories		Comma-delimited list of category IDs (will also include images in albums in those categories)
	 * @apiparam	string	albums			Comma-delimited list of album IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only images started by those members are returned
	 * @apiparam	int		locked			If 1, only images which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only images which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured		If 1, only images which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy			What to sort by. Can be 'date' for creation date, 'title', 'updated' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\gallery\Image>
	 * @return PaginatedResponse<Image>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();
		
		/* Albums */
		if ( isset( Request::i()->albums ) )
		{
			$where[] = array( Db::i()->in( 'image_album_id', array_filter( explode( ',', Request::i()->albums ) ) ) );
		}
				
		/* Return */
		return $this->_list( $where );
	}
	
	/**
	 * GET /gallery/images/{id}
	 * View information about a specific image
	 *
	 * @param		int		$id			ID Number
	 * @throws		2G316/1	INVALID_ID	The image ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\gallery\Image
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2G316/1', 404 );
		}
	}
	
	/**
	 * POST /gallery/images
	 * Upload an image
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock images).
	 * @reqapiparam	int					album					The ID number of the album the image should be created in - not required if category is provided (only provide one or the other)
	 * @reqapiparam	int					category				The ID number of the category the image should be created in - not required if album is provided (only provide one or the other)
	 * @reqapiparam	int					author					The ID number of the member uploading the image (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @reqapiparam	string				caption					The image caption
	 * @reqapiparam	string				filename				The image filename (e.g. 'image.png')
	 * @reqapiparam	string				image					The base64 encoded image contents
	 * @apiparam	string				description				The description as HTML (e.g. "<p>This is an image.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	string				copyright				The copyright
	 * @apiparam	string				credit					The credit information
	 * @apiparam	int					gpsShow					If the image contains the location in it's EXIF data, 1/0 indicating if a map should be shown (defaults to 1)
	 * @apiparam	string				prefix					Prefix tag
	 * @apiparam	string				tags					Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date					The date/time that should be used for the image post date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				ip_address				The IP address that should be stored for the image. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked					1/0 indicating if the image should be locked
	 * @apiparam	int					hidden					0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned					1/0 indicating if the image should be locked
	 * @apiparam	int					featured				1/0 indicating if the image should be featured
	 * @apiparam	bool				anonymous				If 1, the item will be posted anonymously.
	 * @throws		1G316/2				NO_CATEGORY_OR_ALBUM	The category or album does not exist
	 * @throws		1G316/3				NO_AUTHOR				The author ID does not exist
	 * @throws		1G316/4				NO_CAPTION				No caption was supplied
	 * @throws		1G316/5				NO_FILENAME				No filename was supplied
	 * @throws		1G316/6				NO_IMAGE				The image was invalid
	 * @throws		2G316/D				NO_PERMISSION			The authorized user does not have permission to create an image in that category/album
	 * @throws		1G316/E				IMAGE_TOO_BIG			The image exceeds the filesize the authorized user can upload
	 * @apireturn		\IPS\gallery\Image
	 * @return Response
	 */
	public function POSTindex(): Response
	{		
		/* Get category or album */
		if ( isset( Request::i()->album ) )
		{
			try
			{
				$album = Album::load( Request::i()->album );
				$category = $album->category();
			}
			catch ( OutOfRangeException $e )
			{
				throw new Exception( 'NO_CATEGORY_OR_ALBUM', '1G316/2', 400 );
			}
		}
		else
		{
			try
			{
				$category = Category::load( Request::i()->category );
			}
			catch ( OutOfRangeException $e )
			{
				throw new Exception( 'NO_CATEGORY_OR_ALBUM', '1G316/2', 400 );
			}
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( isset( Request::i()->album ) )
			{
				if ( !$album->can( 'add', $this->member ) )
				{
					throw new Exception( 'NO_PERMISSION', '2G316/D', 403 );
				}
			}
			elseif ( !$category->can( 'add', $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2G316/D', 403 );
			}
			$author = $this->member;
		}
		else
		{
			if ( Request::i()->author )
			{
				$author = Member::load( Request::i()->author );
				if ( !$author->member_id )
				{
					throw new Exception( 'NO_AUTHOR', '1G316/3', 400 );
				}
			}
			else
			{
				if ( (int) Request::i()->author === 0 )
				{
					$author = new Member;
				}
				else 
				{
					throw new Exception( 'NO_AUTHOR', '1G316/3', 400 );
				}
			}
		}
		
		/* Check we have a caption and a filename */
		if ( !Request::i()->caption )
		{
			throw new Exception( 'NO_CAPTION', '1G316/4', 400 );
		}
		if ( !Request::i()->filename )
		{
			throw new Exception( 'NO_FILENAME', '1G316/5', 400 );
		}
		
		/* Check it's a valid image */
		$imageContents = base64_decode( Request::i()->image );
		if ( $this->member and $this->member->group['g_max_upload'] and strlen( $imageContents ) > $this->member->group['g_max_upload'] * 1024 )
		{
			throw new Exception( 'IMAGE_TOO_BIG', '1G316/E', 400 );
		}
		try
		{
			$imageObject = \IPS\Image::create( $imageContents );
		}
		catch ( \Exception $e )
		{
			throw new Exception( 'NO_IMAGE', '1G316/6', 400 );
		}
		
		/* Create the file */
		$file = File::create( 'gallery_Images', Request::i()->filename, $imageContents );
		
		/* Create the object */
		$image = $this->_create( $category, $author );
		
		/* Set properties */
		$image->original_file_name	= (string) $file;
		$image->file_size	= $file->filesize();
		$image->file_name	= $file->originalFilename;
		$image->file_type	= File::getMimeType( $file->filename );
		if( \IPS\Image::exifSupported() )
		{
			$image->metadata	= $imageObject->parseExif();
			if( count( $image->metadata ) )
			{
				$image->parseGeolocation();
				$image->gps_show = isset( Request::i()->gpsShow ) ? Request::i()->gpsShow : 1;
			}
			$metadata = $image->metadata;
			$image->metadata = json_encode( $metadata );
		}
		$image->buildThumbnails( $file );
		$image->save();
					
		/* Output */
		return new Response( 201, $image->apiOutput( $this->member ) );
	}
	
	/**
	 * POST /gallery/images/{id}
	 * Edit an image
	 *
	 * @param int $id
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock topics).
	 * @apiparam	int					album					The ID number of the album (only provide this or category to move image)
	 * @apiparam	int					category				The ID number of the category (only provide this or category to move image)
	 * @apiparam	int					author					The ID number of the member uploading the image (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				caption					The image caption
	 * @apiparam	string				description				The description as HTML (e.g. "<p>This is an image.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	string				copyright				The copyright
	 * @apiparam	string				credit					The credit information
	 * @apiparam	int					gpsShow					If the image contains the location in it's EXIF data, 1/0 indicating if a map should be shown (defaults to 1)
	 * @apiparam	string				prefix					Prefix tag
	 * @apiparam	string				tags					Comma-separated list of tags (do not include prefix)
	 * @apiparam	string				ip_address				The IP address that should be stored for the image. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked					1/0 indicating if the image should be locked
	 * @apiparam	int					hidden					0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned					1/0 indicating if the image should be locked
	 * @apiparam	int					featured				1/0 indicating if the image should be featured
	 * @apiparam	bool				anonymous				If 1, the item will be posted anonymously.
	 * @throws		2G316/7				INVALID_ID				The image ID is invalid or the authorized user does not have permission to view it
	 * @throws		1G316/8				NO_CATEGORY_OR_ALBUM	The category or album does not exist or the authorized user does not have permission to post in it
	 * @throws		1G316/9				NO_AUTHOR				The author ID does not exist
	 * @throws		2G316/F				NO_PERMISSION			The authorized user does not have permission to edit the image
	 * @apireturn		\IPS\gallery\Image
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$image = Image::load( $id );
			if ( $this->member and !$image->can( 'read', $this->member ) )
			{
				throw new OutOfRangeException;
			}
			if ( $this->member and !$image->canEdit( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2G316/FD', 403 );
			}
			
			/* New category or album */
			if ( !$this->member or $image->canMove( $this->member ) )
			{
				try
				{
					if ( isset( Request::i()->album ) and Request::i()->album != $image->album_id )
					{
						$newAlbum = Album::load( Request::i()->album );
						if ( $this->member and !$newAlbum->can( 'add', $this->member ) )
						{
							throw new OutOfRangeException;
						}
						
						$image->move( $newAlbum );
					}
					elseif ( isset( Request::i()->category ) and Request::i()->category != $image->category_id )
					{
						$newCategory = Category::load( Request::i()->category );
						if ( $this->member and !$newCategory->can( 'add', $this->member ) )
						{
							throw new OutOfRangeException;
						}
						
						$image->move( $newCategory );
					}
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_CATEGORY_OR_ALBUM', '1G316/8', 400 );
				}
			}
			
			/* New author */
			if ( !$this->member and isset( Request::i()->author ) )
			{				
				try
				{
					$member = Member::load( Request::i()->author );
					if ( !$member->member_id )
					{
						throw new OutOfRangeException;
					}
					
					$image->changeAuthor( $member );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_AUTHOR', '1G316/9', 400 );
				}
			}
			
			/* Everything else */
			$this->_createOrUpdate( $image, 'edit' );
			
			/* Save and return */
			$image->save();
			return new Response( 200, $image->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2G316/7', 404 );
		}
	}
	
	/**
	 * GET /gallery/images/{id}/comments
	 * Get comments on an image
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2G316/A	INVALID_ID	The image ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		PaginatedResponse<IPS\gallery\Image\Comment>
	 * @return PaginatedResponse<Comment>
	 */
	public function GETitem_comments( int $id ): PaginatedResponse
	{
		try
		{
			return $this->_comments( $id, 'IPS\gallery\Image\Comment' );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2G316/A', 404 );
		}
	}
	
	/**
	 * GET /gallery/images/{id}/reviews
	 * Get reviews on an image
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2G316/B	INVALID_ID	The image ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		PaginatedResponse<IPS\gallery\Image\Review>
	 * @return PaginatedResponse<Review>
	 */
	public function GETitem_reviews( int $id ): PaginatedResponse
	{
		try
		{
			return $this->_comments( $id, 'IPS\gallery\Image\Review' );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2G316/B', 404 );
		}
	}
	
	/**
	 * Create or update image
	 *
	 * @param	Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	Item
	 */
	protected function _createOrUpdate( Item $item, string $type='add' ): Item
	{
		/* Album */
		if ( isset( Request::i()->album ) and !$item->id )
		{
			$item->album_id = Request::i()->album;
		}
		
		/* Caption */
		if ( isset( Request::i()->caption ) )
		{
			$item->caption = Request::i()->caption;
		}

		/* Description */
		if ( isset( Request::i()->description ) )
		{
			$descriptionContents = Request::i()->description;
			if ( $this->member )
			{
				$descriptionContents = Parser::parseStatic( $descriptionContents, NULL, $this->member, 'gallery_Images' );
			}
			$item->description = $descriptionContents;
		}

		/* Copyright */
		if ( isset( Request::i()->copyright ) )
		{
			$item->copyright = Request::i()->copyright;
		}

		/* Credit */
		if ( isset( Request::i()->credit ) )
		{
			$item->credit_info = Request::i()->credit;
		}
		
		/* GPS Show */
		if ( isset( Request::i()->gpsShow ) )
		{
			$item->gps_show = Request::i()->gpsShow;
		}
		
		/* Pass up */
		return parent::_createOrUpdate( $item, $type );
	}
		
	/**
	 * DELETE /gallery/images/{id}
	 * Delete an image
	 *
	 * @param		int		$id			ID Number
	 * @throws		2G316/C	INVALID_ID		The image ID does not exist
	 * @throws		2G316/G	NO_PERMISSION	The authorized user does not have permission to delete the image
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$item = Image::load( $id );
			if ( $this->member and !$item->canDelete( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2G316/G', 404 );
			}
			
			$item->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2G316/C', 404 );
		}
	}
}