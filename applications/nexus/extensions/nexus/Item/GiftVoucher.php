<?php
/**
 * @brief		Gift Voucher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Apr 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Email;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Member;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function chr;
use function defined;
use function in_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gift Voucher
 */
class GiftVoucher extends Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'giftvoucher';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'gift';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'gift_voucher';
	
	/**
	 * @brief	Can use coupons?
	 */
	public static bool $canUseCoupons = FALSE;
	
	/**
	 * Get purchase from redeem code
	 *
	 * @param	string	$redemptionCode	The redemption code
	 * @return	Purchase
	 * @throws	InvalidArgumentException
	 */
	public static function getPurchase( string $redemptionCode ) : Purchase
	{
		$exploded = explode( 'X', $redemptionCode );

		if ( !isset( $exploded[0] ) or !is_numeric( $exploded[0] ) )
		{
			throw new InvalidArgumentException('BAD_FORMAT');
		}
		try
		{
			$purchase = Purchase::load( $exploded[0] );
		}
		catch ( OutOfRangeException )
		{
			throw new InvalidArgumentException('NO_PURCHASE');
		}
		if ( $purchase->app != 'nexus' or $purchase->type != 'giftvoucher' )
		{
			throw new InvalidArgumentException('BAD_PURCHASE');
		}
		if ( !$purchase->active or $purchase->cancelled )
		{
			throw new InvalidArgumentException('CANCELED');
		}
		$extra = $purchase->extra;
		if ( !isset( $extra['code'] ) or $redemptionCode !== "{$extra['code']}" )
		{
			throw new InvalidArgumentException('BAD_CODE');
		}
		
		return $purchase;
	}
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( Form $form, Invoice $invoice ) : void
	{
		$form->add( new Number( 'gift_voucher_amount', 0, TRUE, array(), NULL, NULL, $invoice->currency ) );
		$form->add( new Color( 'gift_voucher_color', '3b3b3b', FALSE ) );
		$form->add( new Radio( 'gift_voucher_method', 'print', TRUE, array( 'options' => array( 'email' => 'gift_voucher_email', 'print' => 'gift_voucher_print' ), 'toggles' => array( 'email' => array( 'gift_voucher_email' ) ) ) ) );
		$form->add( new Form\Email( 'gift_voucher_email', NULL, NULL, array(), function($val )
		{
			if ( !$val and Request::i()->gift_voucher_method === 'email' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'gift_voucher_email' ) );
		$form->add( new Text( 'gift_voucher_recipient' ) );
		$form->add( new Text( 'gift_voucher_sender', $invoice->member->name ) );
		$form->add( new TextArea( 'gift_voucher_message' ) );
	}

	/**
	 * Create From Form
	 *
	 * @param array $values Values from form
	 * @param Invoice $invoice The invoice
	 * @return GiftVoucher
	 */
	public static function createFromForm( array $values, Invoice $invoice ): GiftVoucher
	{
		$item = new GiftVoucher( Member::loggedIn()->language()->get('gift_voucher'), new Money( $values['gift_voucher_amount'], $invoice->currency ) );
		$item->paymentMethodIds = array_keys( Gateway::roots( NULL, NULL, array( 'm_active=1' ) ) ); // It is against 2CO terms to use them for buying gift vouchers

		$item->extra['method'] = $values['gift_voucher_method'];
		$item->extra['recipient_email'] = $values['gift_voucher_email'];
		$item->extra['recipient_name'] = $values['gift_voucher_recipient'];
		$item->extra['sender'] = $values['gift_voucher_sender'];
		$item->extra['message'] = $values['gift_voucher_message'];
		$item->extra['amount'] = $values['gift_voucher_amount'];
		$item->extra['color'] = $values['gift_voucher_color'];
		$item->extra['currency'] = $invoice->currency;
		
		return $item;
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function onPurchaseGenerated( Purchase $purchase, Invoice $invoice ): void
	{
		/* Generate a redemption code */
		$code = "{$purchase->id}X{$purchase->member->member_id}X";
		foreach ( range( 1, 10 ) as $j )
		{
			do
			{
				$chr = rand( 48, 90 );
			}
			while ( in_array( $chr, array( 58, 59, 60, 61, 62, 63, 64, 88 ) ) );
			$code .= chr( $chr );
		}
		$extra = $purchase->extra;
		$extra['code'] = $code;
		$purchase->extra = $extra;
		$purchase->save();
		
		/* Send the email */
		if ( $purchase->extra['method'] === 'email' )
		{
			Email::buildFromTemplate( 'nexus', 'giftVoucher', array( $purchase->extra['recipient_name'], new Money( $purchase->extra['amount'], $purchase->extra['currency'] ), $code, $purchase->extra['message'], $purchase->extra['sender'], $purchase->extra['color'] ), Email::TYPE_TRANSACTIONAL )->send( $purchase->extra['recipient_email'] );
		}
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    string
	 */
	public static function acpPage( Purchase $purchase ): string
	{
		$extra = $purchase->extra;
		return (string) Theme::i()->getTemplate('purchases')->giftvoucher( $purchase, $extra );
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    array
	 */
	public static function clientAreaPage( Purchase $purchase ): array
	{
		$extra = $purchase->extra;
		return array(
			'purchaseInfo'	=> Theme::i()->getTemplate('purchases')->giftvoucher( $purchase, $extra )
		);
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	Purchase	$purchase	The purchase
	 * @return    void
	 */
	public static function clientAreaAction( Purchase $purchase ): void
	{
		$extra = $purchase->extra;
		if ( $extra['method'] === 'print' )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'purchases' )->giftvoucherPrint( $extra ) );
		}
	}
	
	/**
	 * Requires Billing Address
	 *
	 * @return	bool
	 * @throws	DomainException
	 */
	public function requiresBillingAddress(): bool
	{
		return in_array( 'giftvoucher', explode( ',', Settings::i()->nexus_require_billing ) );
	}

}