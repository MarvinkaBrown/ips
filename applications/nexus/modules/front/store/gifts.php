<?php
/**
 * @brief		Gift Vouchers
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		5 May 2014
 */

namespace IPS\nexus\modules\front\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\extensions\nexus\Item\GiftVoucher;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gift Vouchers
 */
class gifts extends Controller
{

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_store.js', 'nexus', 'front' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'store.css', 'nexus' ) );

		parent::execute();
	}
	
	/**
	 * Buy
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Work out what our options are */
		$memberCurrency = ( ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency() );
		$options = array();
		foreach ( json_decode( Settings::i()->nexus_gift_vouchers, TRUE ) as $voucher )
		{
			if ( isset( $voucher[ $memberCurrency ] ) and $voucher[ $memberCurrency ] )
			{
				$options[ $voucher[ $memberCurrency ] ] = new Money( $voucher[ $memberCurrency ], $memberCurrency );
			}
		}
		if ( Settings::i()->nexus_gift_vouchers_free )
		{
			$options['x'] = Member::loggedIn()->language()->addToStack('other');
		}
		if ( empty( $options ) )
		{
			Output::i()->error( 'no_module_permission', '4X213/2', 403, '' );
		}
		
		$form = new Form( 'form', 'buy_gift_voucher' );
		$form->class = 'ipsForm--vertical ipsForm--buy-gift-voucher';
		$form->add( new Color( 'gift_voucher_color', '3b3b3b', FALSE ) );
		$form->add( new Radio( 'gift_voucher_amount', NULL, TRUE, array( 'options' => $options, 'parse' => 'normal', 'userSuppliedInput' => 'x' ) ) );
		$form->add( new Radio( 'gift_voucher_method', NULL, TRUE, array( 'options' => array( 'email' => 'email', 'print' => 'gift_voucher_print' ), 'toggles' => array( 'email' => array( 'gift_voucher_email' ) ) ) ) );
		$form->add( new Email( 'gift_voucher_email', NULL, NULL, array(), function( $val )
		{
			if ( !$val and Request::i()->gift_voucher_method === 'email' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'gift_voucher_email' ) );
		$form->add( new Text( 'gift_voucher_recipient' ) );
		$form->add( new Text( 'gift_voucher_sender', Member::loggedIn()->name ) );
		$form->add( new TextArea( 'gift_voucher_message' ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( ! is_numeric( $values['gift_voucher_amount'] ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'gift_voucher_invalid_value' );
			}
			elseif( ! abs( $values['gift_voucher_amount'] ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'gift_voucher_invalid_amount' );
			}

			if( !$form->error )
			{
				$item = new GiftVoucher( Member::loggedIn()->language()->get( 'gift_voucher' ), new Money( abs( $values[ 'gift_voucher_amount' ] ), $memberCurrency ) );
				$item->paymentMethodIds = array_keys( Gateway::roots( NULL, NULL, array( 'm_active=1' ) ) );

				$item->extra[ 'method' ] = $values[ 'gift_voucher_method' ];
				$item->extra[ 'recipient_email' ] = $values[ 'gift_voucher_email' ];
				$item->extra[ 'recipient_name' ] = $values[ 'gift_voucher_recipient' ];
				$item->extra[ 'sender' ] = $values[ 'gift_voucher_sender' ];
				$item->extra[ 'message' ] = $values[ 'gift_voucher_message' ];
				$item->extra[ 'amount' ] = abs( $values[ 'gift_voucher_amount' ] );
				$item->extra[ 'color' ] = $values[ 'gift_voucher_color' ];
				$item->extra[ 'currency' ] = $memberCurrency;

				$invoice = new Invoice;
				$invoice->currency = $memberCurrency;
				$invoice->member = Customer::loggedIn();
				$invoice->addItem( $item );
				$invoice->save();

				Output::i()->redirect( $invoice->checkoutUrl() );
			}
		}
		
		/* Display */
		$formTemplate = $form->customTemplate( array( Theme::i()->getTemplate( 'store', 'nexus' ), 'giftCardForm' ) );

		Output::i()->output = Theme::i()->getTemplate('store')->giftCard( $formTemplate );
		Output::i()->title = Member::loggedIn()->language()->addToStack('buy_gift_voucher');
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('buy_gift_voucher') );
	}
	
	/**
	 * [AJAX] Format currency amount
	 *
	 * @return	void
	 */
	protected function formatCurrency() : void
	{
		Output::i()->json( (string) new Money( Request::i()->amount, ( ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : Customer::loggedIn()->defaultCurrency() ) ) );
	}
	
	/**
	 * Redeem
	 *
	 * @return	void
	 */
	protected function redeem() : void
	{
		if ( !Customer::loggedIn()->member_id )
		{
			Output::i()->error( 'redeem_must_be_logged_in', '1X213/1', 403, '' );
		}
		
		$form = new Form( 'form', 'redeem_gift_voucher' );
		$form->add( new Text( 'redemption_code', isset( Request::i()->code ) ? Request::i()->code : NULL, TRUE, array(), function( $val )
		{
			try
			{
				GiftVoucher::getPurchase( $val );
			}
			catch ( InvalidArgumentException )
			{
				throw new DomainException('redeem_gift_voucher_error');
			}
		} ) );
		if ( $values = $form->values() )
		{
			$purchase = GiftVoucher::getPurchase( $values['redemption_code'] );
			$extra = $purchase->extra;
			$currency = isset( $extra['currency'] ) ? $extra['currency'] : Customer::loggedIn()->defaultCurrency();
			
			$credits = Customer::loggedIn()->cm_credits;
			$credits[ $currency ]->amount = $credits[ $currency ]->amount->add( new Number( number_format( $extra['amount'], Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
			Customer::loggedIn()->cm_credits = $credits;
			Customer::loggedIn()->save();
			
			Customer::loggedIn()->log( 'giftvoucher', array( 'type' => 'redeemed', 'code' => $values['redemption_code'], 'amount' => $extra['amount'], 'currency' => $extra['currency'], 'ps_member' => $purchase->member->member_id, 'newCreditAmount' => $credits[ $currency ]->amount ) );
			$purchase->member->log( 'giftvoucher', array( 'type' => 'used', 'code' => $values['redemption_code'], 'amount' => $extra['amount'], 'currency' => $extra['currency'], 'by' => Customer::loggedIn()->member_id ) );
			
			$purchase->delete();
			
			Output::i()->redirect( Url::internal( "app=nexus&module=store&controller=store&currency={$currency}", 'front', 'store' ) );
		}

		if( Request::i()->isAjax() )
		{
			$form->class = 'ipsForm--vertical ipsForm--redeem-voucher';
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core', 'front' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'redemption_code' );
			Output::i()->output = $form;
		}		
	}
}