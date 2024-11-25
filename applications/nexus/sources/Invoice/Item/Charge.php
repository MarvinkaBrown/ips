<?php
/**
 * @brief		Invoice Item Class for Charges
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\Invoice\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\nexus\Invoice\Item;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Item Class for Charges
 */
abstract class Charge extends Item
{
	/**
	 * @brief	Act (new/charge)
	 */
	public static string $act = 'charge';
	
	/**
	 * @brief	Requires login to purchase?
	 */
	public static bool $requiresAccount = FALSE;

	/**
	 * @brief	Can use coupons?
	 */
	public static bool $canUseCoupons = false;
}