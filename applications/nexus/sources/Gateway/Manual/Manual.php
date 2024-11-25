<?php
/**
 * @brief		Manual Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Mar 2014
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\nexus\Customer;
use IPS\nexus\Customer\CreditCard;
use IPS\nexus\Fraud\MaxMind\Request;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use IPS\Output;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manual Gateway
 */
class Manual extends Gateway
{	
	/* !Payment Gateway */
		
	/**
	 * Authorize
	 *
	 * @param	Transaction					$transaction	Transaction
	 * @param array|CreditCard $values			Values from form OR a stored card object if this gateway supports them
	 * @param	Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made
	 * @param	array									$recurrings		Details about recurring costs
	 * @param string|NULL $source			'checkout' if the customer is doing this at a normal checkout, 'renewal' is an automatically generated renewal invoice, 'manual' is admin manually charging. NULL is unknown
	 * @return    array|DateTime|NULL                        Auth is valid until or NULL to indicate auth is good forever
	 * @throws	LogicException							Message will be displayed to user
	 */
	public function auth(Transaction $transaction, array|CreditCard $values, Request $maxMind = NULL, array $recurrings = array(), ?string $source = NULL ): DateTime|array|null
	{
		$transaction->status = Transaction::STATUS_WAITING;
		$extra = $transaction->extra;
		$extra['history'][] = array( 's' => Transaction::STATUS_WAITING );
		$transaction->extra = $extra;
		$transaction->save();

		/* Send Notification */
		$transaction->sendNotification();
		AdminNotification::send( 'nexus', 'Transaction', Transaction::STATUS_WAITING, TRUE, $transaction );

		Output::i()->redirect( $transaction->url() );
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
		$form->add( new Translatable( 'manual_instructions', NULL, TRUE, array( 'app' => 'nexus', 'key' => ( $this->id ? "nexus_gateway_{$this->id}_ins" : NULL ), 'editor' => array( 'app' => 'nexus', 'key' => 'Admin', 'autoSaveKey' => ( $this->id ? "nexus-gateway-{$this->id}" : "nexus-new-gateway" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'manual_gateway_description_placeholder' ) ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( !$this->id )
		{
			$this->save();
			File::claimAttachments( 'nexus_gateway_new', $this->id, NULL, 'gateway', TRUE );
		}

		if( isset( $values['manual_instructions'] ) )
		{
			Lang::saveCustom( 'nexus', "nexus_gateway_{$this->id}_ins", $values['manual_instructions'] );
			unset( $values['manual_instructions'] );
		}

		return parent::formatFormValues( $values );
	}
	
	/**
	 * Refund
	 *
	 * @param	Transaction	$transaction	Transaction to be refunded
	 * @param mixed|NULL $amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @param string|null $reason
	 * @return    mixed                                    Gateway reference ID for refund, if applicable
	 */
	public function refund(Transaction $transaction, mixed $amount = NULL, ?string $reason = NULL): mixed
	{
		return null;
	}

	/**
	 * Payment Screen Fields
	 *
	 * @param Invoice $invoice Invoice
	 * @param Money $amount The amount to pay now
	 * @param Customer|null $member The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param array $recurrings Details about recurring costs
	 * @param string $type 'checkout' means the cusotmer is doing this on the normal checkout screen, 'admin' means the admin is doing this in the ACP, 'card' means the user is just adding a card
	 * @return    array
	 */
	public function paymentScreen(Invoice $invoice, Money $amount, ?Customer $member = NULL, array $recurrings = array(), string $type = 'checkout'): array
	{
		return [];
	}

	/**
	 * Capture
	 *
	 * @param Transaction $transaction Transaction
	 * @return    void
	 * @throws    LogicException
	 */
	public function capture(Transaction $transaction): void
	{

	}

	/**
	 * Test Settings
	 *
	 * @param array $settings Settings
	 * @return    array
	 * @throws    InvalidArgumentException
	 */
	public function testSettings(array $settings = array()): array
	{
		return $settings;
	}
}