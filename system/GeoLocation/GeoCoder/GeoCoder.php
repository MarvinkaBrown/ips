<?php
/**
 * @brief		GeoCoder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Dec 2017
 */

namespace IPS\GeoLocation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\GeoLocation\GeoCoder\Google;
use IPS\GeoLocation\GeoCoder\Mapbox;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * GeoCoder abstract class
 */
abstract class GeoCoder
{
	/**
	 * @brief	Cached GeoCoder instance
	 */
	protected static GeoCoder|null $instance = NULL;

	/**
	 * Return instance of GeoCoder
	 *
	 * @return	GeoCoder|null
	 * @throws	BadMethodCallException
	 */
	public static function i(): GeoCoder|null
	{
		if( static::$instance === NULL )
		{
			if ( Settings::i()->googlemaps and Settings::i()->google_maps_api_key )
			{
				static::$instance = new Google();
			}
			elseif ( Settings::i()->mapbox and Settings::i()->mapbox_api_key )
			{
				static::$instance = new Mapbox();
			}
			else
			{
				throw new BadMethodCallException;
			}
		}

		return static::$instance;
	}
}
