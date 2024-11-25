<?php
/**
 * @brief		IP Address Lookup: Logins
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Feb 2017
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Http\UserAgent;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Logins
 */
class Logins extends IpAddressesAbstract
{
	/**
	 * Removes the logged IP address
	 *
	 * @param int $time
	 * @return void
	 */
	public function pruneIpAddresses( int $time ) : void
	{
		// Main cleanup task takes care of this
	}
	
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
			return Db::i()->select( 'COUNT(*)', 'core_members_known_ip_addresses', array( "ip_address LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_members_known_ip_addresses', $baseUrl, array( array( 'ip_address LIKE ?', $ip ) ) );
		$table->joins[] = array( 'select' => 'user_agent', 'from' => 'core_members_known_devices', 'where' => 'core_members_known_devices.device_key=core_members_known_ip_addresses.device_key' );
		$table->langPrefix = 'device_table_';
		$table->include = array( 'user_agent', 'member_id', 'login_handler', 'last_seen' );
		$table->sortBy = $table->sortBy ?: 'last_seen';
		$table->parsers = array(
			'user_agent'	=> function( $val, $row ) {
				return (string) UserAgent::parse( $val );
			},
			'member_id'	=> function( $val ) {
				$member = Member::load( $val );
				if ( $member->member_id )
				{
					return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . Theme::i()->getTemplate( 'global', 'core' )->userLink( $member, 'tiny' );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'login_handler'		=> function( $val, $row ) {
				return Theme::i()->getTemplate('members')->deviceHandler( $val );
			},
			'last_seen'	=> function( $val ) {
				return DateTime::ts( $val );
			},
		);
		$table->rowButtons = function( $row )
		{
			return array(
				'view'	=> array(
					'title'	=> 'view',
					'icon'	=> 'search',
					'link'	=> Url::internal( 'app=core&module=members&controller=devices&do=device' )->setQueryString( 'key', $row['device_key'] )->setQueryString( 'member', $row['member_id'] ),
				),
			);
		};
		
		/* Return */
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
	public function findByMember( Member $member ) : array|Select
	{
		return Db::i()->select( "ip_address AS ip, count(*) AS count, MIN(last_seen) AS first, MAX(last_seen) AS last", 'core_members_known_ip_addresses', array( 'member_id=?', $member->member_id ), NULL, NULL, 'ip_address' )->setKeyField( 'ip' );
	}	
}