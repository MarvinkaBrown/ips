<?php
/**
 * @brief		Miscellaneous Charge
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		25 Mar 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Helpers\Form\Member;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Charge;
use IPS\nexus\Money;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Miscellaneous Charge
 */
class MiscellaneousCharge extends Charge
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'charge';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'dollar-sign';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'miscellaneous_charge';
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( Form $form, Invoice $invoice ) : void
	{
		$form->add( new Text( 'item_name', NULL, TRUE ) );
		$form->add( new Number( 'item_net_price', 0, TRUE, array( 'decimals' => TRUE, 'min' => NULL ), NULL, NULL, $invoice->currency ) );
		$form->add( new Node( 'item_tax_rate', 0, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'item_tax_rate_none' ) ) );
		$form->add( new Node( 'item_paymethods', 0, FALSE, array( 'class' => 'IPS\nexus\Gateway', 'multiple' => TRUE, 'zeroVal' => 'all' ) ) );

		$form->add( new YesNo( 'item_pay_other', FALSE, FALSE, array( 'togglesOn' => array( 'item_pay_to', 'item_commission', 'item_fee' ) ) ) );
		$form->add( new Member( 'item_pay_to', FALSE, FALSE, array(), NULL, NULL, NULL, 'item_pay_to' ) );
		$form->add( new Number( 'item_commission', FALSE, FALSE, array( 'min' => 0, 'max' => 100 ), NULL, NULL, '%', 'item_commission' ) );
		$form->add( new Number( 'item_fee', FALSE, FALSE, array(), NULL, NULL, $invoice->currency, 'item_fee' ) );
		$form->add( new Number( 'invoice_quantity', 1, FALSE, array( 'min' => 1 ) ) );
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values		Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return    MiscellaneousCharge
	 */
	public static function createFromForm( array $values, Invoice $invoice ): MiscellaneousCharge
	{
		$obj = new static( $values['item_name'], new Money( $values['item_net_price'], $invoice->currency ) );
		$obj->quantity = $values['invoice_quantity'];
		if ( $values['item_tax_rate'] )
		{
			$obj->tax = $values['item_tax_rate'];
		}
		if ( $values['item_paymethods'] )
		{
			$obj->paymentMethodIds = array_keys( $values['item_paymethods'] );
		}
		if ( $values['item_pay_other'] )
		{
			$obj->payTo = $values['item_pay_to'];
			$obj->commission = $values['item_commission'];
			$obj->fee = new Money( $values['item_fee'], $invoice->currency );
		}
		return $obj;
	}
}