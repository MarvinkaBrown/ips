<?php
/**
 * @brief		Invoice Item Class for Subscription Upgrade Charges
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		8 May 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Charge;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription\Package;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Item Class for Subscription Upgrade Charges
 */
class SubscriptionUpgrade extends Charge
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'sub_upgrade';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'turn-up';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'upgrade_charge';
	
	/**
	 * On Paid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onPaid( Invoice $invoice ): void
	{
		try
		{
			$purchase = Purchase::load( $this->id );
			
			$oldPackage = Package::load( $this->extra['oldPackage'] );
			$newPackage = Package::load( $this->extra['newPackage'] );
			$oldPackage->upgradeDowngrade( $purchase, $newPackage, TRUE );
			$purchase->member->log( 'subscription', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $oldPackage->titleForLog(), 'name' => $newPackage->titleForLog(), 'system' => TRUE ) );
		}
		catch ( OutOfRangeException ){ }
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
			$oldPackage = Package::load( $this->extra['oldPackage'] );
			$newPackage = Package::load( $this->extra['newPackage'] );
			
			$return[] = Member::loggedIn()->language()->addToStack( 'invoice_unpaid_change', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'purchase_number', FALSE, array( 'sprintf' => array( $this->id ) ) ), $newPackage->_title, $oldPackage->_title ) ) );
		}
		catch ( OutOfRangeException ){}
		
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
			$oldPackage = Package::load( $this->extra['oldPackage'] );
			$newPackage = Package::load( $this->extra['newPackage'] );
			$newPackage->upgradeDowngrade( $purchase, $oldPackage, TRUE );
			$purchase->member->log( 'subscription', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $newPackage->_title, 'name' => $oldPackage->_title, 'system' => TRUE ) );
		}
		catch ( OutOfRangeException ){}
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
}