<?php
/**
 * @brief		Google GeoCoder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Dec 2017
 */

namespace IPS\GeoLocation\GeoCoder;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use BadMethodCallException;
use IPS\GeoLocation;
use IPS\GeoLocation\GeoCoder;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Settings;
use RuntimeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Google GeoCoder class
 */
class Google extends GeoCoder
{
	/**
	 * Get by location string
	 *
	 * @param string $location
	 * @return GeoLocation
	 * @throws BadFunctionCallException
	 */
	public static function decodeLocation( string $location ): GeoLocation
	{
		if ( Settings::i()->googlemaps AND Settings::i()->google_maps_api_key )
		{
			$data = Url::external( "https://maps.googleapis.com/maps/api/geocode/json" )->setQueryString( array(
				'address' => $location,
				'sensor'	=> 'false',
				'key'		=> Settings::i()->google_maps_api_key_secret ?: Settings::i()->google_maps_api_key
			) )->request()->get()->decodeJson();

			$obj = new GeoLocation;

			$_address	= '';

			/* Make sure the response from Google is valid */
			if( isset( $data['results'] ) AND is_array( $data['results'] ) AND count( $data['results'] ) )
			{
				if( isset( $data['results'][0]['geometry'] ) AND $data['results'][0]['geometry']['location'] )
				{
					$obj->lat = $data['results'][0]['geometry']['location']['lat'];
					$obj->long = $data['results'][0]['geometry']['location']['lng'];
				}

				if( isset( $data['results'][0]['name'] ) )
				{
					$obj->placeName = $data['results'][0]['name'];
				}

				foreach( $data['results'][0]['address_components'] as $component )
				{
					if( $component['types'][0] == 'street_number' )
					{
						$_address	= $component['long_name'];
					}
					elseif( $component['types'][0] == 'route' )
					{
						$_address	.= " " . $component['long_name'];
					}

					if( $component['types'][0] == 'postal_code' )
					{
						$obj->postalCode	= $component['long_name'];
					}

					if( $component['types'][0] == 'country' )
					{
						$obj->country	= $component['short_name'];
					}

					if( $component['types'][0] == 'administrative_area_level_1' )
					{
						$obj->region	= $component['long_name'];
					}

					if( $component['types'][0] == 'administrative_area_level_2' )
					{
						$obj->county = $component['long_name'];
					}

					if( $component['types'][0] == 'locality' )
					{
						$obj->city	= $component['long_name'];
					}
				}
			}

			if( $_address )
			{
				$obj->addressLines	= array( $_address );
			}

			return $obj;
		}
		else
		{
			throw new BadFunctionCallException;
		}
	}

	/**
	 * Get by latitude and longitude
	 *
	 * @param float $lat	Latitude
	 * @param float $long	Longitude
	 * @return	GeoLocation
	 * @throws	BadFunctionCallException
	 * @throws	Exception
	 */
	public static function decodeLatLong( float $lat, float $long ): GeoLocation
	{
		if ( Settings::i()->googlemaps AND Settings::i()->google_maps_api_key )
		{
			$data = Url::external( "https://maps.googleapis.com/maps/api/geocode/json" )->setQueryString( array(
				'latlng'	=> "{$lat},{$long}",
				'sensor'	=> 'false',
				'key'		=> Settings::i()->google_maps_api_key_secret ?: Settings::i()->google_maps_api_key
			) )->request()->get()->decodeJson();
			
			$obj = new GeoLocation;
			$obj->lat			= $lat;
			$obj->long			= $long;

			$_address	= '';

			/* Make sure the response from Google is valid */
			if( isset( $data['results'] ) AND is_array( $data['results'] ) AND count( $data['results'] ) )
			{
				foreach( $data['results'][0]['address_components'] as $component )
				{
					if( $component['types'][0] == 'street_number' )
					{
						$_address	= $component['long_name'];
					}
					elseif( $component['types'][0] == 'route' )
					{
						$_address	.= " " . $component['long_name'];
					}

					if( $component['types'][0] == 'postal_code' )
					{
						$obj->postalCode	= $component['long_name'];
					}

					if( $component['types'][0] == 'country' )
					{
						$obj->country	= $component['short_name'];
					}

					if( $component['types'][0] == 'administrative_area_level_1' )
					{
						$obj->region	= $component['long_name'];
					}

					if( $component['types'][0] == 'administrative_area_level_2' )
					{
						$obj->county = $component['long_name'];
					}

					if( $component['types'][0] == 'locality' )
					{
						$obj->city	= $component['long_name'];
					}
				}
			}

			if( $_address )
			{
				$obj->addressLines	= array( $_address );
			}

			return $obj;
		}
		else
		{
			throw new BadFunctionCallException;
		}
	}

	/**
	 * Get the latitude and longitude for the current object. Address must be set.
	 *
	 * @param	GeoLocation	$geoLocation	Geolocation object
	 * @param bool $setAddress		Whether or not to update the address information from the GeoCoder service
	 * @return	void
	 * @throws	BadMethodCallException
	 */
	public function setLatLong(GeoLocation &$geoLocation, bool $setAddress=FALSE ) : void
	{
		if ( Settings::i()->googlemaps AND Settings::i()->google_maps_api_key AND $geoLocation->toString() )
		{
			try
			{
				$data = Url::external( "https://maps.googleapis.com/maps/api/geocode/json" )->setQueryString( array(
					'address'	=> $geoLocation->toString(),
					'sensor'	=> 'false',
					'key'		=> Settings::i()->google_maps_api_key_secret ?: Settings::i()->google_maps_api_key
				) )->request()->get()->decodeJson();
			}
			catch( RuntimeException $e )
			{
				return;
			}
			
			if ( !count( $data['results'] ) )
			{
				return;
			}

			$_address	= NULL;

			$geoLocation->lat	= $data['results'][0]['geometry']['location']['lat'];
			$geoLocation->long	= $data['results'][0]['geometry']['location']['lng'];

			if( $setAddress === TRUE )
			{
				$_address	= '';

				/* Set the address data */
				foreach( $data['results'][0]['address_components'] as $component )
				{
					if( $component['types'][0] == 'street_number' )
					{
						$_address	= $component['long_name'];
					}
					elseif( $component['types'][0] == 'route' )
					{
						$_address	.= " " . $component['long_name'];
					}

					if( $component['types'][0] == 'postal_code' )
					{
						$geoLocation->postalCode	= $component['long_name'];
					}

					if( $component['types'][0] == 'country' )
					{
						$geoLocation->country	= $component['short_name'];
					}

					if( $component['types'][0] == 'administrative_area_level_1' )
					{
						$geoLocation->region	= $component['long_name'];
					}

					if( $component['types'][0] == 'locality' )
					{
						$geoLocation->city	= $component['long_name'];
					}
				}

				if( $_address )
				{
					$geoLocation->addressLines	= array( $_address );
				}
			}
		}
		else
		{
			throw new BadFunctionCallException;
		}
	}
}
