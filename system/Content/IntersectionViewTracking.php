<?php
/**
 * @brief		Intersection View Tracking Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		July 2023
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Platform\Bridge;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Intersection View Tracking Trait (IPS Cloud Feature) - Track the time this content was viewed on the front end
 */
trait IntersectionViewTracking
{
	/**
	 * Get a hash of the view tracking to be consumed by IPS Cloud Services
	 *
	 * @return	string
	 */
	public function getViewTrackingHash() : string
	{
		return Bridge::i()->viewTrackingHash( $this ) ?: '';
	}

	/**
	 * Get additional data for a bit of content to include with its intersection view tracking
	 *
	 * @return array
	 */
	public function getViewTrackingData() : array
	{
		return Bridge::i()->viewTrackingData( $this ) ?: [];
	}
}