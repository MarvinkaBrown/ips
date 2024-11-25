<?php
/**
 * @brief		IP Address Lookup extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Sep 2014
 */

namespace IPS\nexus\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Transaction;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup extension
 */
class Transactions extends IpAddressesAbstract
{
	/**
	 * Supported in the ModCP IP address lookup tool?
	 *
	 * @return	bool
	 * @note	If the method does not exist in an extension, the result is presumed to be TRUE
	 */
	public function supportedInModCp(): bool
	{
		return FALSE;
	}

	/** 
	 * Find Records by IP
	 *
	 * @param	string			$ip			The IP Address
	 * @param	Url|null	$baseUrl	URL table will be displayed on or NULL to return a count
	 * @return	string|int|null
	 */
	public function findByIp( string $ip, ?Url $baseUrl = NULL ): string|int|null
	{
		/* Return count */
		if ( $baseUrl === NULL )
		{
			return Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( "t_ip LIKE ?", $ip ) )->first();
		}
		
		$table = Transaction::table( array( array( "t_ip LIKE ?", $ip ) ), $baseUrl );
		$table->include[]	= 't_ip';

		return (string) $table;
	}
	
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
	public function findByMember( Member $member ): array|Select
	{
		return Db::i()->select( "t_ip AS ip, count(*) AS count, MIN(t_date) AS first, MAX(t_date) AS last", 'nexus_transactions', array( "t_member=?", $member->member_id ), NULL, NULL, "t_ip" )->setKeyField( 'ip' );
	}	
}