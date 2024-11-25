<?php
/**
 * @brief		License Key Model - Standard
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
use function chr;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * License Key Model - Standard
 */
class Standard extends LicenseKey
{	
	/**
	 * @brief	Number of blocks
	 */
	protected static int $blocks = 5;
	
	/**
	 * @brief	Number of characters in a block
	 */
	protected static int $characters = 4;
	
	/**
	 * @brief	Lowest allowed ASCII number
	 */
	protected static int $low = 48; // 0
	
	/**
	 * @brief	Highest allowed ASCII number
	 */
	protected static int $high = 90; // Z
	
	/**
	 * @brief	Disallowed ASCII numbers
	 */
	protected static array $disallowed = array( 58, 59, 60, 61, 62, 63, 64 ); // Various non A-Z / 0-9 characters
	
	/**
	 * @brief	Seperator between blocks
	 */
	protected static string $seperator	= '-';

	/**
	 * Generates a License Key
	 *
	 * @return	string
	 */
	public function generateKey() : string
	{
		$key = array();
		foreach ( range( 1, static::$blocks ) as $i )
		{
			$_k = '';
			foreach ( range( 1, static::$characters ) as $j )
			{
				do
				{
					$chr = rand( static::$low, static::$high );
				}
				while ( in_array( $chr, static::$disallowed ) );
				$_k .= chr( $chr );
			}
			$key[] = $_k;
		}
		
		return implode( static::$seperator, $key );
	}
}