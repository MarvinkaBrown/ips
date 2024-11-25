<?php
/**
 * @brief		Mapbox GeoCoder
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
 * Mapbox GeoCoder class
 */
class Mapbox extends GeoCoder
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
		if ( Settings::i()->mapbox AND Settings::i()->mapbox_api_key )
		{
			$data = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/{$location}.json" )->setQueryString( array(
				'access_token'		=> Settings::i()->mapbox_api_key,
			) )->request()->get()->decodeJson();

			$obj = new GeoLocation;

			$_address	= '';

			/* Make sure the response from Mapbox is valid */
			if( isset( $data['results'] ) AND is_array( $data['results'] ) AND count( $data['results'] ) )
			{
				if( isset( $data['results'][0]['geometry'] ) AND isset( $data['results'][0]['geometry']['coordinates'] ) )
				{
					$obj->long = $data['results'][0]['geometry']['coordinates'][0];
					$obj->lat = $data['results'][0]['geometry']['coordinates'][1];
				}

				$obj->placeName = $data['results'][0]['place_name'];

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
						$obj->county	= $component['long_name'];
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
		if ( Settings::i()->mapbox AND Settings::i()->mapbox_api_key )
		{
			$location = $long . ',' . $lat;
			$data = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/{$location}.json" )->setQueryString( array(
				'access_token'		=> Settings::i()->mapbox_api_key,
			) )->request()->get()->decodeJson();
			
			$obj = new GeoLocation;
			$obj->lat			= $lat;
			$obj->long			= $long;

			$_address	= '';

			/* Make sure the response from Mapbox is valid */
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
						$obj->county	= $component['long_name'];
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
	public function setLatLong( GeoLocation &$geoLocation, bool $setAddress=FALSE ) : void
	{
		if ( Settings::i()->mapbox AND Settings::i()->mapbox_api_key AND $geoLocation->toString() )
		{
			try
			{
				$data = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode( html_entity_decode( $geoLocation->toString() ) ) . ".json" )->setQueryString( array(
					'access_token'		=> Settings::i()->mapbox_api_key,
				) )->request()->get()->decodeJson();
			}
			catch( RuntimeException $e )
			{
				return;
			}

			if ( !isset( $data['features'] ) or !count( $data['features'] ) )
			{
				return;
			}

			$_address	= NULL;

			$geoLocation->long	= $data['features'][0]['center'][0];
			$geoLocation->lat	= $data['features'][0]['center'][1];

			if( $setAddress === TRUE )
			{
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

						if( $component['types'][0] == 'administrative_area_level_2' )
						{
							$geoLocation->county	= $component['long_name'];
						}

						if( $component['types'][0] == 'locality' )
						{
							$geoLocation->city	= $component['long_name'];
						}
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
