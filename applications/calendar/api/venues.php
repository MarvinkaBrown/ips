<?php
/**
 * @brief		Calendar Venues API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		5 June 2018
 */

namespace IPS\calendar\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\calendar\Venue;
use IPS\GeoLocation;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Node\Api\NodeController;
use IPS\Node\Model;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Calendar Venues API
 */
class venues extends NodeController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\calendar\Venue';

	/**
	 * GET /calendar/venues
	 * Get list of venues
	 *
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\calendar\Venue>
	 * @return PaginatedResponse<Venue>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Return */
		return $this->_list();
	}

	/**
	 * GET /calendar/venues/{id}
	 * Get specific venue
	 *
	 * @param		int		$id			ID Number
	 * @throws		1L384/1	INVALID_ID	The venue does not exist
	 * @apireturn		\IPS\calendar\Venue
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
			throw new Exception( 'INVALID_ID', '1L384/1', 404 );
		}
	}

	/**
	 * POST /calendar/venues
	 * Create a venue
	 *
	 * @apiclientonly
	 * @reqapiparam	string		title				The venue title
	 * @reqapiparam	\IPS\GeoLocation		address				The venue address (latitude and longitude do not need to be supplied and will be ignored)
	 * @apiparam	string		description			The venue description
	 * @apireturn		\IPS\calendar\Venue
	 * @throws		1L384/2	NO_ADDRESS	An address is required for the venue but was not supplied
	 * @throws		1L384/3	INVALID_ADDRESS	An invalid address was supplied
	 * @throws		1L384/4	NO_TITLE	No title was supplied for the venue
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		if( !Request::i()->address OR !is_array( Request::i()->address ) )
		{
			throw new Exception( 'NO_ADDRESS', '1L384/2', 400 );
		}
		else
		{
			/* Just check before we try to save */
			$this->_getGeoLocationObject();
		}

		if ( !Request::i()->title )
		{
			throw new Exception( 'NO_TITLE', '1L384/4', 400 );
		}

		return new Response( 201, $this->_create()->apiOutput( $this->member ) );
	}

	/**
	 * POST /calendar/venues/{id}
	 * Edit a venue
	 *
	 * @apiclientonly
	 * @reqapiparam	string		title				The venue title
	 * @reqapiparam	\IPS\GeoLocation		address				The venue address (latitude and longitude do not need to be supplied and will be ignored)
	 * @apiparam	string		description			The venue description
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\calendar\Venue
	 * @throws		1L384/2	NO_ADDRESS	An address is required for the venue but was not supplied
	 * @throws		1L384/3	INVALID_ADDRESS	An invalid address was supplied
	 * @throws		1L384/4	NO_TITLE	No title was supplied for the venue
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		/* @var Venue $class */
		$class = $this->class;
		$venue = $class::load( $id );

		return new Response( 200, $this->_createOrUpdate( $venue )->apiOutput( $this->member ) );
	}

	/**
	 * DELETE /calendar/venues/{id}
	 * Delete a venue
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		return $this->_delete( $id );
	}

	/**
	 * Create or update node
	 *
	 * @param	Model	$venue				The node
	 * @return	Model
	 */
	protected function _createOrUpdate( Model $venue ): Model
	{
		if( Request::i()->address AND is_array( Request::i()->address ) )
		{
			$geoLocation = $this->_getGeoLocationObject();

			$venue->address = json_encode( $geoLocation );
		}

		if ( isset( Request::i()->title ) )
		{
			Lang::saveCustom( 'calendar', 'calendar_venue_' . $venue->id, Request::i()->title );
			$venue->title_seo = Friendly::seoTitle( Request::i()->title );
		}

		if( isset( Request::i()->description ) )
		{
			Lang::saveCustom( 'calendar', 'calendar_venue_' . $venue->id .'_desc', Request::i()->description );
		}

		return parent::_createOrUpdate( $venue );
	}

	/**
	 * Get geolocation object
	 *
	 * @return GeoLocation
	 */
	protected function _getGeoLocationObject(): GeoLocation
	{
		$geoLocation = new GeoLocation;
		$geoLocation->addressLines = array();

		foreach( Request::i()->address as $k => $v )
		{
			if( in_array( $k, array( 'city', 'postalCode', 'region', 'addressLines', 'country' ) ) )
			{
				$geoLocation->$k = $v;
			}
		}

		try
		{
			$geoLocation->getLatLong();
		}
		catch( BadMethodCallException $e )
		{
			throw new Exception( 'INVALID_ADDRESS', '1L384/3', 400 );
		}

		return $geoLocation;
	}
}