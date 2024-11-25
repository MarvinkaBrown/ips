<?php
/**
 * @brief		Mapbox Maps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Nov 2017
 */

namespace IPS\GeoLocation\Maps;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mapbox Maps
 */
class Mapbox
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
	 * @return	string
	 */
	public function render( int $width, int $height, float $zoom=NULL ): string
	{
		if( !($this->geolocation->long and $this->geolocation->lat ) )
		{
			return "";
		}

		/* Check group permissions */
		if( Settings::i()->mapbox_groups != '*' and !Member::loggedIn()->inGroup( explode( ",", Settings::i()->mapbox_groups ) ) )
		{
			return '';
		}

		if( empty( $zoom ) )
		{
			$zoom = Settings::i()->mapbox_zoom ?: null;
		}

		return Theme::i()->getTemplate( 'global', 'core', 'global' )->staticMap( NULL, $this->mapUrl( $width, $height, $zoom ), $this->geolocation->lat, $this->geolocation->long, $width, $height );
	}

	/**
	 * Return the map image URL
	 *
	 * @param int $width	Width
	 * @param int $height	Height
	 * @param float|null $zoom	The zoom amount (a value between 0 being totally zoomed out view of the world, and 1 being as fully zoomed in as possible) or NULL to zoom automatically based on how much data is available
	 * @return	Url|NULL
	 */
	public function mapUrl( int $width, int $height, float $zoom=NULL ): ?Url
	{
		if( !($this->geolocation->long and $this->geolocation->lat ) )
		{
			return NULL;
		}

		$location = str_replace( ',', '.', $this->geolocation->long ) . ',' . str_replace( ',', '.', $this->geolocation->lat );

		return Url::external( "https://api.mapbox.com/styles/v1/mapbox/streets-v10/static/pin-l-marker+f00({$location})/{$location},14,0,60/{$width}x{$height}@2x" )->setQueryString( array(
			'access_token'	=> Settings::i()->mapbox_api_key,
			'zoom' => $zoom
		) );
	}
}