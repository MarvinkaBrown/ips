<?php
/**
 * @brief		License Key Model - MD5
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		30 Apr 2014
 */

namespace IPS\nexus\Purchase\LicenseKey;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\nexus\Purchase\LicenseKey;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * License Key Model - MD5
 */
class Mdfive extends LicenseKey
{	
	/**
	 * Generates a License Key
	 *
	 * @return	string
	 */
	public function generateKey() : string
	{
		return md5( mt_rand() );
	}
}