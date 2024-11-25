<?php
/**
 * @brief		IP Address Lookup: Validations
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Oct 2016
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
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Validations
 */
class Validations extends IpAddressesAbstract
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
			return Db::i()->select( 'COUNT(*)', 'core_validating', array( "ip_address LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_validating', $baseUrl, array( "ip_address LIKE ?", $ip ) );
		$table->langPrefix = 'members_';
				
		/* Columns we need */
		$table->include = array( 'photo', 'name', 'validatingType', 'entry_date', 'ip_address' );
		$table->mainColumn = 'name';
		$table->noSort	= array( 'photo' );
	
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'entry_date';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'photo'				=> function( $val, $row )
			{
				$member = Member::load( $row['member_id'] );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'mini' );
			},
			'entry_date'		=> function( $val, $row )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'name'				=> function( $val, $row )
			{
				$member = Member::load( $row['member_id'] );
				$link = ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' ) ? $member->url() : $member->acpUrl();
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $link, FALSE, $member->name );
			},
			'validatingType'	=> function( $val, $row )
			{
				if( $row['new_reg'] )
				{
					return Member::loggedIn()->language()->addToStack( 'validate_type_newreg' );
				}
				elseif( $row['lost_pass'] )
				{
					return Member::loggedIn()->language()->addToStack( 'validate_type_lostpass' );
				}
				elseif( $row['email_chg'] )
				{
					return Member::loggedIn()->language()->addToStack( 'validate_type_emailchg' );
				}

				return '';
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
		return Db::i()->select( 'ip_address AS ip, count(*) AS count, MIN(entry_date) AS first, MAX(entry_date) AS last', 'core_validating', array( 'member_id=?', $member->member_id ), NULL, NULL, 'ip_address' )->setKeyField( 'ip' );
	}	
}