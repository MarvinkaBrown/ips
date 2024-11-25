<?php
/**
 * @brief		Coupon Discount
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		12 May 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\nexus\Coupon;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Charge;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Coupon Discount
 */
class CouponDiscount extends Charge
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'coupon';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'ticket';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'coupon';
	
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
			$coupon = Coupon::load( $this->id );
			if ( $coupon->uses >= 1 )
			{
				$coupon->uses--;
				$coupon->save();
			}
		}
		catch ( OutOfRangeException ) { }
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
			$coupon = Coupon::load( $this->id );
			if ( $coupon->uses != -1 )
			{
				$coupon->uses++;
				$coupon->save();
			}
			
			$this->onInvoiceCancel( $invoice );
		}
		catch ( OutOfRangeException ) { }
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onInvoiceCancel( Invoice $invoice ): void
	{
		try
		{
			$coupon = Coupon::load( $this->id );
			$uses = $coupon->used_by ? json_decode( $coupon->used_by, TRUE ) : array();
			$member = $this->extra['usedBy'] ?? $invoice->member->member_id;
			if ( isset( $uses[ $member ] ) )
			{
				if ( $uses[ $member ] === 1 )
				{
					unset( $uses[ $member ] );
				}
				else
				{
					$uses[ $member ]--;
				}
				$coupon->used_by = json_encode( $uses );
				$coupon->save();
			}
		}
		catch ( OutOfRangeException ) { }
	}
}