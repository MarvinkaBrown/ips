<?php
/**
 * @brief		IP Address Lookup: Ratings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Oct 2016
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Ratings
 */
class Ratings extends IpAddressesAbstract
{
	/**
	 * Removes the logged IP address
	 *
	 * @param int $time
	 * @return void
	 */
	public function pruneIpAddresses( int $time ) : void
	{
		Db::i()->update('core_ratings', [ 'ip' => '' ] , [ "ip != '' and rating_date IS NOT NULL and rating_date <?", $time ] );
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
			return Db::i()->select( 'COUNT(*)', 'core_ratings', array( "ip LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_ratings', $baseUrl, array( "ip LIKE ?", $ip ) );
				
		/* Columns we need */
		$table->include = array( 'member', 'rating', 'rated', 'ip' );
		$table->mainColumn = 'ip';
		$table->langPrefix = 'rating_';

		$table->tableTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'table' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'rows' );
				
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'id';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'member'			=> function( $val, $row )
			{
				$member = Member::load( $val );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
			},
			'rated'				=> function( $val, $row )
			{
				try
				{
					$class = $row['class'];
					$rated = $class::load( $row['item_id'] );
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $rated->url(), TRUE, $rated->mapped('title') );
				}
				catch ( OutOfRangeException $e )
				{
					return '';
				}
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
		return Db::i()->select( "ip, count(*) AS count, MIN(rating_date) AS first, MAX(rating_date) AS last", 'core_ratings', array( '`member`=?', $member->member_id ), NULL, NULL, 'ip' )->setKeyField( 'ip' );
	}	
}