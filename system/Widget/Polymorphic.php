<?php

/**
 * @brief        Polymorphic
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        Oct 2024
 */

namespace IPS\Widget;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;
use function header;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

interface Polymorphic
{
	/**
	 * Get the base key. When the class name is dynamically generated, we need to use the base key to look in the database and find the actual base configuration
	 *
	 * @return string
	 */
	public static function getBaseKey() : string;
}