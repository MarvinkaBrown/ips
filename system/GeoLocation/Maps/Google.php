<?php
/**
 * @brief		Google Maps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS\GeoLocation\Maps;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Google Maps
 */
class Google
{	
	/**
	 * @brief	GeoLocation
	 */
	public ?GeoLocation $geolocation = NULL;

	/**
	 * Constructor
	 *
	 * @param	GeoLocation	$geoLocation	Location
	 * @return	void
	 */
	public function __construct( GeoLocation $geoLocation )
	{
		$this->geolocation	= $geoLocation;
	}
	
	/**
	 * Render
	 *
	 * @param int $width	Width
	 * @param int $height	Height
	 * @param float|null $zoom	The zoom amount (a value between 0 being totally zoomed out view of the world, and 1 being as fully zoomed in as possible) or NULL to zoom automatically based on how much data is available
	 * @param int $scale	Google maps scale to use (https://developers.google.com/maps/documentation/static-maps/intro#scale_values)
	 * @param string $maptype	Type of map to use. Valid values are roadmap (default), satellite, terrain, and hybrid (https://developers.google.com/maps/documentation/static-maps/intro#MapTypes)
	 * @return	string
	 */
	public function render( int $width, int $height, float $zoom=NULL, int $scale=1, string $maptype='roadmap' ): string
	{
		/* Check permissions */
		if( Settings::i()->google_maps_groups != '*' and !Member::loggedIn()->inGroup( explode( ",", Settings::i()->google_maps_groups ) ) )
		{
			return '';
		}

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_core.js', 'core', 'global' ) );

		if( empty( $zoom ) )
		{
			$zoom = Settings::i()->google_maps_zoom ?: null;
		}

		return Settings::i()->google_maps_static_use_embed ?
			Theme::i()->getTemplate( 'global', 'core', 'global' )->googleMap( array(
				'lat' => $this->geolocation->lat,
				'long' => $this->geolocation->long,
				'key' => Settings::i()->google_maps_api_key,
				'zoom' => $zoom,
				'scale' => $scale,
				'maptype' => $maptype,
				'width' => $width,
				'height' => $height )) :
			Theme::i()->getTemplate( 'global', 'core', 'global' )->staticMap( Url::external( 'https://maps.google.com/' )->setQueryString( 'q', $this->_getLocation() ), $this->mapUrl( $width, $height, $zoom, $scale, $maptype ), $this->geolocation->lat, $this->geolocation->long, $width, $height );
	}

	/**
	 * Return the map image URL
	 *
	 * @param int $width	Width
	 * @param int $height	Height
	 * @param float|null $zoom	The zoom amount (a value between 0 being totally zoomed out view of the world, and 1 being as fully zoomed in as possible) or NULL to zoom automatically based on how much data is available
	 * @param int $scale	Google maps scale to use (https://developers.google.com/maps/documentation/static-maps/intro#scale_values)
	 * @param string $maptype	Type of map to use. Valid values are roadmap (default), satellite, terrain, and hybrid (https://developers.google.com/maps/documentation/static-maps/intro#MapTypes)
	 * @return	Url|NULL
	 */
	public function mapUrl( int $width, int $height, float $zoom=NULL, int $scale=1, string $maptype='roadmap' ): ?Url
	{
		$location = $this->_getLocation();
		
		return Url::external( 'https://maps.googleapis.com/maps/api/staticmap' )->setQueryString( array(
			'center'	=> $location,
			'zoom'		=> $zoom === NULL ? NULL : ceil( $zoom * 8 ),
			'size'		=> "{$width}x{$height}",
			'markers'	=> $location,
			'key'		=> Settings::i()->google_maps_api_key,
			'scale'		=> $scale,
			'maptype'	=> $maptype
		) );
	}

	/**
	 * Fetch the location
	 *
	 * @return	string
	 */
	protected function _getLocation(): string
	{
		if ( $this->geolocation->lat and $this->geolocation->long )
		{
			$location = sprintf( "%F", $this->geolocation->lat ) . ',' . sprintf( "%F", $this->geolocation->long );
		}
		else
		{
			$location = array();
			foreach ( array( 'postalCode', 'country', 'region', 'city', 'addressLines' ) as $k )
			{
				if ( $this->geolocation->$k )
				{
					if ( is_array( $this->geolocation->$k ) )
					{
						foreach ( array_reverse( $this->geolocation->$k ) as $v )
						{
							if( $v )
							{
								$location[] = $v;
							}
						}
					}
					else
					{
						$location[] = $this->geolocation->$k;
					}
				}
			}
			$location = implode( ', ', array_reverse( $location ) );
		}

		return $location;
	}
}