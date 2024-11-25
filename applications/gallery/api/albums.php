<?php
/**
 * @brief		Gallery Albums API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		14 Dec 2015
 */

namespace IPS\gallery\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Db;
use IPS\gallery\Album;
use IPS\gallery\Album\Item;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Gallery Albums API
 */
class albums extends Controller
{	
	/**
	 * GET /gallery/albums
	 * Get list of albums
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only albums the authorized user can view will be included
	 * @apiparam	string	categories		Comma-delimited list of categiry IDs - if provided, only albums in those categories are returned
	 * @apiparam	string	owners			Comma-delimited list of member IDs - if provided, only albums owned by those members are returned
	 * @apiparam	string	sortBy			What to sort by. Can be 'name', 'count_images' for number of images, or do not specify for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\gallery\Album>
	 * @return PaginatedResponse<Album>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();
		
		/* Categories */
		if ( isset( Request::i()->categories ) )
		{
			$where[] = array( Db::i()->in( 'album_category_id', array_filter( explode( ',', Request::i()->categories ) ) ) );
		}
		
		/* Owners */
		if ( isset( Request::i()->owners ) )
		{
			$where[] = array( Db::i()->in( 'album_owner_id', array_filter( explode( ',', Request::i()->owners ) ) ) );
		}
		
		/* Privacy */
		if ( isset( Request::i()->privacy ) )
		{
			$privacy = array();
			foreach ( array_filter( explode( ',', Request::i()->privacy ) ) as $type )
			{
				switch ( $type )
				{
					case 'public':
						$privacy[] = Album::AUTH_TYPE_PUBLIC;
						break;
					case 'private':
						$privacy[] = Album::AUTH_TYPE_PRIVATE;
						break;
					case 'restricted':
						$privacy[] = Album::AUTH_TYPE_RESTRICTED;
						break;
				}
			}
			
			$where[] = array( Db::i()->in( 'album_type', $privacy ) );
		}
			
		/* Sort */
		if ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'name', 'count_images' ) ) )
		{
			$sortBy = 'album_' . Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'album_id';
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		/* Get results */
		if ( $this->member )
		{
			$joins = array();
			$where[] = Item::getItemsWithPermissionWhere( $where, $this->member, $joins );
		}
		
		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'gallery_albums', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\gallery\Album',
			Db::i()->select( 'COUNT(*)', 'gallery_albums', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
	
	/**
	 * GET /gallery/albums/{id}
	 * Get information about a specific album
	 *
	 * @param		int		$id			ID Number
	 * @throws		2G315/1	INVALID_ID	The album ID does not exist or the authorized user does not have permisison to view it
	 * @apireturn		\IPS\gallery\Album
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			$album = $this->member ? Album::loadAndCheckPerms( $id, 'view', $this->member ) : Album::load( $id );
			return new Response( 200, $album->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2G315/1', 404 );
		}
	}
}