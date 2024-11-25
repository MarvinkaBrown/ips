<?php

/**
 * @brief        AdvertisementLocationsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/16/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Advertisement;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class AdvertisementLocationsAbstract
{
	/**
	 * If this ad location is used in a list view, we allow the admin
	 * to define intervals or fixed positions within the list
	 *
	 * @var bool
	 */
	public static bool $listView = false;

	/**
	 * Get the locations and the additional settings
	 *
	 * @param	array	$settings	Current setting values
	 * @return	array	Array with two elements: 'locations' which should have keys as the location keys and values as the fields to toggle, and 'settings' which are additional fields to add to the form
	 */
	abstract public function getSettings( array $settings ): array;

	/**
	 * Return an array of setting values to store
	 *
	 * @param	array	$values	Values from the form submission
	 * @return	array 	Array of setting key => value to store
	 */
	abstract public function parseSettings( array $values ): array;

	/**
	 * Check if the advertisement can be displayed, based on settings
	 *
	 * @param Advertisement $advertisement
	 * @param string $location
	 * @return bool
	 */
	public function canShow( Advertisement $advertisement, string $location ) : bool
	{
		return TRUE;
	}
}