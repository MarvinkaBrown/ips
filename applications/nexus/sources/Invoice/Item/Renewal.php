<?php
/**
 * @brief		Invoice Item Class for Renewals
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		01 Apr 2014
 */

namespace IPS\nexus\Invoice\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\File;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Coupon;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Transaction;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Item Class for Renewals
 */
class Renewal extends Item
{
	/**
	 * @brief	Act (new/charge)
	 */
	public static string $act = 'renewal';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'arrows-rotate';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'renewal';
	
	/**
	 * @brief	Requires login to purchase?
	 */
	public static bool $requiresAccount = TRUE;
	
	/**
	 * @brief	New expiry date (NULL will cause automatic calculation)
	 */
	public mixed $expire = NULL;

	/**
	 * @brief	Expiry date (NULL will cause automatic calculation)
	 */
	public mixed $expireDate = NULL;
	
	/**
	 * Create
	 *
	 * @param	Purchase	$purchase	The purchase to renew
	 * @param int $cycles		The number of cycles to renew for
	 * @return    static
	 */
	public static function create( Purchase $purchase, int $cycles = 1 ): static
	{
		$obj = new static( sprintf( $purchase->member->language()->get('renew_title'), $purchase->name ), $purchase->renewals->cost );
		$obj->tax = $purchase->renewals->tax;
		$obj->quantity = $cycles;
		$obj::$application = $purchase->app;
		$obj::$type = $purchase->type;
		$obj->id = $purchase->id;
		
		if ( $purchase->pay_to )
		{
			$obj->payTo = $purchase->pay_to;
			$obj->commission = $purchase->commission ?? 0;
			$obj->fee = $purchase->fee ?? 0;
		}
		
		if ( $renewalPaymentMethodIds = $purchase->renewalPaymentMethodIds() )
		{
			$obj->paymentMethodIds = $renewalPaymentMethodIds;
		}
		
		return $obj;
	}

	/**
	 * Used to override the static property, if necessary
	 *
	 * @return bool
	 */
	public function canUseAccountCredit() : bool
	{
		/* Account credits can only be used for renewals if the original item
		is allowed to use credit */
		if( $extension = $this->getPurchaseExtension() )
		{
			return $extension->canUseAccountCredit();
		}

		return static::$canUseAccountCredit;
	}

	/**
	 * Used to override the static property, if necessary
	 *
	 * @return bool
	 */
	public function canUseCoupons() : bool
	{
		/* Coupons can only be used on renewals for packages that allow coupons */
		if( $extension = $this->getPurchaseExtension() )
		{
			return $extension->canUseCoupons();
		}

		return static::$canUseCoupons;
	}

	/**
	 * Determines if the coupon can be applied to this item
	 *
	 * @param array|string $data
	 * @param Invoice $invoice
	 * @param Customer $customer
	 * @return bool
	 */
	public function isValid( array|string $data, Invoice $invoice, Customer $customer ) : bool
	{
		/* Coupons can only be used on renewals for packages that allow coupons */
		if( $extension = $this->getPurchaseExtension() )
		{
			return $extension->isValid( $data, $invoice, $customer );
		}

		return false;
	}
	
	/**
	 * On Paid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onPaid( Invoice $invoice ): void
	{
		$purchase = Purchase::load( $this->id );
		
		if ( $purchase->renewals and $interval = $purchase->renewals->interval and !$purchase->cancelled and $expire = $purchase->expire )
		{			
			$_expire = clone $expire;
			if ( $_expire->add( new DateInterval( 'PT' . $purchase->grace_period . 'S' ) )->getTimestamp() < time() )
			{				
				$expire = new DateTime;
			}
			for ( $i=0; $i<$this->quantity; $i++ )
			{
				$expire->add( $interval );
			}
			
			$purchase->expire = $expire;
			$purchase->invoice_pending = NULL;
			
			$billingAgreement = NULL;
			foreach ( $invoice->transactions( array( Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED ), array( array( 't_billing_agreement IS NOT NULL' ) ) ) as $transaction )
			{
				$billingAgreement = $transaction->billing_agreement;
			}
			if ( $billingAgreement )
			{
				$purchase->billing_agreement = $billingAgreement;
			}
			
			$purchase->save();
			$purchase->onRenew($this->quantity);
			
			$purchase->member->log( 'purchase', array( 'type' => 'renew', 'id' => $purchase->id, 'name' => $purchase->name, 'invoice_id' => $invoice->id, 'invoice_title' => $invoice->title ) );
		}		
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( Invoice $invoice ): array
	{
		$return = parent::onUnpaidDescription( $invoice );
		
		try
		{
			$purchase = Purchase::load( $this->id );
		}
		catch ( OutOfRangeException )
		{
			return $return;
		}
		
		if ( $purchase->renewals and $interval = $purchase->renewals->interval and !$purchase->cancelled and $expire = $purchase->expire )
		{
			for ( $i=0; $i<$this->quantity; $i++ )
			{
				$expire->sub( $interval );
			}
			
			$return[] = Member::loggedIn()->language()->addToStack( 'renewal_unpaid', FALSE, array( 'sprintf' => array( $purchase->name, $purchase->id, $expire->localeDate() ) ) );
		}
		
		return $return;
	}
	
	/**
	 * On Unpaid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @param	string				$status		Status
	 * @return    void
	 */
	public function onUnpaid( Invoice $invoice, string $status ): void
	{
		try
		{
			$purchase = Purchase::load( $this->id );
		}
		catch ( OutOfRangeException )
		{
			return;
		}
		
		if ( $purchase->renewals and $interval = $purchase->renewals->interval and !$purchase->cancelled and $expire = $purchase->expire )
		{
			for ( $i=0; $i<$this->quantity; $i++ )
			{
				$expire->sub( $interval );
			}
			$purchase->expire = $expire;
			$purchase->invoice_pending = $invoice;
			$purchase->save();
			
			$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'invoice_id' => $invoice->id, 'invoice_title' => $invoice->title, 'system' => TRUE ) );
		}
	}
	
	/**
	 * Client Area URL
	 *
	 * @return Url|string|null
	 */
	function url(): Url|string|null
	{
		try
		{
			return Purchase::load( $this->id )->url();
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * ACP URL
	 *
	 * @return Url|null
	 */
	public function acpUrl(): Url|null
	{
		try
		{
			return Purchase::load( $this->id )->acpUrl();
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Image
	 *
	 * @return File|null
	 */
	public function image(): File|null
	{
		try
		{
			return Purchase::load( $this->id )->image();
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Utility method to get the purchase. Useful if we are unsure if the purchase exists, or has been removed due to a refund of the original transaction.
	 *
	 * @return	Purchase|NULL
	 */
	public function getPurchase() : Purchase|null
	{
		try
		{
			return Purchase::load( $this->id );
		}
		catch( OutOfRangeException )
		{
			return NULL;
		}
	}

	public function getPurchaseExtension() : Item|null
	{
		if( $purchase = $this->getPurchase() )
		{
			/* Copy the code to load the extension. It's a protected method and I don't really want to
			expose it */
			try
			{
				foreach ( Application::load( $purchase->app )->extensions( 'nexus', 'Item', FALSE ) as $ext )
				{
					if ( $ext::$type == $purchase->type )
					{
						$price = new Money( "0", $purchase->member->defaultCurrency() );
						if( $purchase->renewals instanceof RenewalTerm )
						{
							$price = $purchase->renewals->cost;
						}
						return new $ext( $purchase->name, $price );
					}
				}
			}
			catch( Exception ){}
		}

		return null;
	}
}