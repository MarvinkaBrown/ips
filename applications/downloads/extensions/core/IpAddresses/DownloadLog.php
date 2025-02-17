<?php
/**
 * @brief		IP Address Lookup extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		30 Dec 2013
 */

namespace IPS\downloads\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\downloads\File;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Http\Useragent;
use IPS\Member;
use IPS\Output\Plugin\Filesize;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup extension
 */
class DownloadLog extends IpAddressesAbstract
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
			return Db::i()->select( 'COUNT(*)', 'downloads_downloads', array( "dip LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'downloads_downloads', $baseUrl, array( "dip LIKE ?", $ip ) );

		$table->tableTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'table' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'rows' );
		
		$table->include = array( 'dfid', 'dtime', 'dsize', 'dua', 'dmid', 'dip' );
		$table->sortBy = $table->sortBy ?: 'dtime';
		
		/* Parsers */
		$table->parsers = array(
			'dfid'	=> function( $val )
			{
				try
				{
					$file = File::load( $val );
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->url(), TRUE, $file->name );
				}
				catch ( OutOfRangeException $e )
				{
					return Member::loggedIn()->language()->addToStack('content_deleted');
				}
			},
			'dtime'	=> function( $val )
			{
				return (string) DateTime::ts( $val );
			},
			'dsize'	=> function( $val )
			{
				return Filesize::humanReadableFilesize( $val );
			},
			'dua'	=> function( $val )
			{
				return (string) Useragent::parse( $val );
			},
			'dmid'	=> function( $val )
			{
				$member = Member::load( $val );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
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
	 * @return	array
	 */
	public function findByMember( Member $member ) : array
	{
		return iterator_to_array(
			Db::i()->select( "dip AS ip, count(*) AS count, MIN(dtime) AS first, MAX(dtime) AS last", 'downloads_downloads', array( "dmid=?", $member->member_id ), NULL, NULL, 'dip' )->setKeyField( 'ip' )
		);
	}	
}