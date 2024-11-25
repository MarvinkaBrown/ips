<?php

/**
 * @brief        IpAddressesAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/17/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db\Select;
use IPS\Http\Url;
use IPS\Member;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class IpAddressesAbstract
{
	/**
	 * Supported in the ACP IP address lookup tool?
	 *
	 * @return	bool
	 * @note	If the method does not exist in an extension, the result is presumed to be TRUE
	 */
	public function supportedInAcp(): bool
	{
		return TRUE;
	}

	/**
	 * Supported in the ModCP IP address lookup tool?
	 *
	 * @return	bool
	 * @note	If the method does not exist in an extension, the result is presumed to be TRUE
	 */
	public function supportedInModCp(): bool
	{
		return TRUE;
	}

	/**
	 * Removes the logged IP address
	 *
	 * @param int $time
	 * @return void
	 */
	public function pruneIpAddresses( int $time ) : void
	{
		//
	}

	/**
	 * Find Records by IP
	 *
	 * @param	string			$ip			The IP Address
	 * @param	Url|null	$baseUrl	URL table will be displayed on or NULL to return a count
	 * @return	string|int|null
	 */
	abstract public function findByIp( string $ip, ?Url $baseUrl = NULL ): string|int|null;

	/**
	 * Find IPs by Member
	 *
	 * @code
	return array(
	'::1' => array(
	'ip'		=> '::1'// string (IP Address)
	'count'		=> ...	// int (number of times this member has used this IP)
	'first'		=> ... 	// int (timestamp of first use)
	'last'		=> ... 	// int (timestamp of most recent use)
	),
	...
	);
	 * @endcode
	 * @param	Member	$member	The member
	 * @return	array|Select
	 */
	abstract public function findByMember( Member $member ): array|Select;
}