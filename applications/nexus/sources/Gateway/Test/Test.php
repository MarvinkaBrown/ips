<?php
/**
 * @brief		Test Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Feb 2014
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Helpers\Form;
use IPS\nexus\Customer;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Test Gateway
 */
class Test extends Gateway
{
	/* !Features */
	
	const SUPPORTS_REFUNDS = TRUE;
	const SUPPORTS_PARTIAL_REFUNDS = TRUE;
	
	/* !Payment Gateway */
	
	/**
	 * Payment Screen Fields
	 *
	 * @param	Invoice		$invoice	Invoice
	 * @param	Money		$amount		The amount to pay now
	 * @param Customer|null $member		The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param array $recurrings	Details about recurring costs
	 * @param string $type		'checkout' means the cusotmer is doing this on the normal checkout screen, 'admin' means the admin is doing this in the ACP, 'card' means the user is just adding a card
	 * @return    array
	 */
	public function paymentScreen(Invoice $invoice, Money $amount, ?Customer $member = NULL, array $recurrings = array(), string $type = 'checkout' ): array
	{
		return array();
	}
	
	/**
	 * Capture
	 *
	 * @param	Transaction	$transaction	Transaction
	 * @return    void
	 * @throws	LogicException
	 */
	public function capture( Transaction $transaction ): void
	{
		
	}
	
	/**
	 * Refund
	 *
	 * @param	Transaction	$transaction	Transaction to be refunded
	 * @param mixed|NULL $amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @param string|null $reason
	 * @return    mixed                                    Gateway reference ID for refund, if applicable
	 * @throws	Exception
 	 */
	public function refund(Transaction $transaction, mixed $amount = NULL, ?string $reason = NULL): mixed
	{
		return null;
	}
	
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param Form $form	The form
	 * @return    void
	 */
	public function settings( Form $form ): void
	{
		
	}
	
	/**
	 * Test Settings
	 *
	 * @param array $settings	Settings
	 * @return    array
	 * @throws	InvalidArgumentException
	 */
	public function testSettings(array $settings = array() ): array
	{
		return $settings;
	}
}