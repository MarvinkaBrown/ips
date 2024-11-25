<?php
/**
 * @brief		IP Address Lookup: Private Messages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Oct 2016
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Private Messages
 */
class Messages extends IpAddressesAbstract
{
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
			return Db::i()->select( 'COUNT(*)', 'core_message_posts', array( "msg_ip_address LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_message_posts', $baseUrl, array( "msg_ip_address LIKE ?", $ip ) );
				
		/* Columns we need */
		$table->include = array( 'msg_author_id', 'msg_date', 'msg_ip_address' );
		$table->mainColumn = 'msg_author_id';
		$table->langPrefix = '';

		$table->tableTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'table' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'rows' );
				
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'msg_date';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'msg_author_id'			=> function( $val, $row )
			{
				$member = Member::load( $row['msg_author_id'] );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
			},
			'msg_date'			=> function( $val, $row )
			{
				return DateTime::ts( $row['msg_date'] );
			},
			'msg_ip_address'		=> function( $val, $row )
			{
				return $row['msg_ip_address'];
			},
		);
		
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
		return Db::i()->select( "msg_ip_address AS ip, count(*) AS count, MIN(msg_date) AS first, MAX(msg_date) AS last", 'core_message_posts', array( 'msg_author_id=?', $member->member_id ), NULL, NULL, 'ip' )->setKeyField( 'ip' );
	}	
}