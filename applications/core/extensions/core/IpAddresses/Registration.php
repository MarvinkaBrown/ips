<?php
/**
 * @brief		IP Address Lookup: Registration
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Dispatcher;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Registration
 */
class Registration extends IpAddressesAbstract
{
	/**
	 * Removes the logged IP address
	 *
	 * @param int $time
	 * @return void
	 */
	public function pruneIpAddresses( int $time ) : void
	{
		Db::i()->update('core_members', [ 'ip_address' => '' ] , [ "ip_address != '' and joined <?", $time ] );
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
			return Db::i()->select( 'COUNT(*)', 'core_members', array( "ip_address LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_members', $baseUrl, array( "ip_address LIKE ?", $ip ) );
		$table->langPrefix = 'members_';
				
		/* Columns we need */
		$table->include = array( 'photo', 'name', 'email', 'joined', 'member_group_id', 'ip_address' );
		$table->mainColumn = 'name';
		$table->noSort	= array( 'photo' );
		
		if( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' )
		{
			$table->include = array_merge( array( 'member_id' ), $table->include );
			$table->rowsTemplate = array( Theme::i()->getTemplate( 'modcp', 'core', 'front' ), 'memberManagementRow' );
		}
				
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'joined';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'photo'				=> function( $val, $row )
			{
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( Member::constructFromData( $row ), 'mini' );
			},
			'joined'			=> function( $val, $row )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'member_group_id'	=> function( $val, $row )
			{
				return Group::load( $val )->formattedName;
			},
			'name'	=> function( $val, $row )
			{
				$link = ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' ) ? Member::constructFromData( $row )->url() : Member::constructFromData( $row )->acpUrl();
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $link, TRUE, $val );
			},
		);
		
		/* Buttons */
		$table->rowButtons = function( $row )
		{
            $member = Member::load( $row['member_id'] );
			return array(
				'edit'	=> array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' ) ? $member->url()->setQueryString( array( 'do' => 'edit' ) ) : $member->acpUrl(),
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
		return Db::i()->select( 'ip_address AS ip, 1 AS count, joined AS first, joined AS last', 'core_members', array( 'member_id=?', $member->member_id ) )->setKeyField( 'ip' );
	}	
}