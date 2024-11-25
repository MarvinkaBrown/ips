<?php
/**
 * @brief		Front Navigation Extension: Gift Cards
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		28 Aug 2018
 */

namespace IPS\nexus\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Gift Cards
 */
class Gifts extends FrontNavigationAbstract
{	
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('gift_vouchers');
	}
	
	/**
	 * Can this item be used at all?
	 * For example, if this will link to a particular feature which has been diabled, it should
	 * not be available, even if the user has permission
	 *
	 * @return    bool
	 */
	public static function isEnabled(): bool
	{
		return ( ( $giftVouchers = json_decode( Settings::i()->nexus_gift_vouchers, TRUE ) and count( $giftVouchers ) ) or Settings::i()->nexus_gift_vouchers_free );
	}
			
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('gift_vouchers');
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return Url::internal( "app=nexus&module=store&controller=gifts", 'front', 'store_giftvouchers' );
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return Dispatcher::i()->application->directory === 'nexus' and Dispatcher::i()->module and Dispatcher::i()->module->key === 'store' and Dispatcher::i()->controller == 'gifts';
	}
}