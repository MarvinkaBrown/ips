<?php
/**
 * @brief		Stripe Pay Out Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		7 Apr 2014
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\nexus\Payout as NexusPayout;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Pay Out Gateway
 */
class Payout extends NexusPayout
{
	/**
	 * ACP Settings
	 *
	 * @return	array
	 */
	public static function settings() : array
	{
		return array();
	}
	
	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	public static function form() :array
	{
		return array();
	}
	
	/**
	 * Get data and validate
	 *
	 * @param	array	$values	Values from form
	 * @return	mixed
	 * @throws	DomainException
	 */
	public function getData( array $values ) : mixed
	{
		return NULL;	
	}

	/**
	 * Process the payout
	 * Return the new status for this payout record
	 *
	 * @return	string
	 * @throws	Exception
	 */
	public function process() : string
	{
		throw new DomainException('stripe_payout_deprecated');
	}
}