<?php
/**
 * @brief		ACP Member Profile: Locations Map Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\MemberACPProfile\LazyLoadingBlock;
use IPS\Db;
use IPS\GeoLocation;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function floatval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Locations Map Block
 */
class Locations extends LazyLoadingBlock
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function lazyOutput(): string
	{
		$mapMarkers = array();
		if ( Settings::i()->ipsgeoip and GeoLocation::enabled() )
		{
			foreach ( Db::i()->select( 'DISTINCT ip_address', 'core_members_known_ip_addresses', array( 'member_id=?', $this->member->member_id ) ) as $ipAddress )
			{
				try
				{
					$location = GeoLocation::getByIp( $ipAddress );
					$mapMarkers[ $ipAddress ] = array(
						'lat'	=> floatval( $location->lat ),
						'long'	=> floatval( $location->long ),
						'title'	=> $ipAddress
					);
				}
				catch ( Exception $e ) { }
			}
		}
		
		return (string) Theme::i()->getTemplate('memberprofile')->locations( $this->member, $mapMarkers );
	}
}