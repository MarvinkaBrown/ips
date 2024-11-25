<?php
/**
 * @brief		Account Credit Increase
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
use IPS\Helpers\Form\Number;
use IPS\Member;
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
 * Account Credit Topup
 */
class AccountCreditIncrease extends Charge
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'topup';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'folder-open';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'account_credit_increase';
	
	/**
	 * @brief	Can use coupons?
	 */
	public static bool $canUseCoupons = FALSE;
	
	/**
	 * @brief	Can use account credit?
	 */
	public static bool $canUseAccountCredit = FALSE;
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	Form	$form		The form
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public static function form( Form $form, Invoice $invoice ): void
	{
		$form->add( new Number( 'credit_amount', 0, TRUE, array( 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values	Values from form
	 * @param	Invoice	$invoice	The invoice
	 * @return    AccountCreditIncrease
	 */
	public static function createFromForm( array $values, Invoice $invoice ): AccountCreditIncrease
	{		
		return new static( $invoice->member->language()->get('account_credit'), new Money( $values['credit_amount'], $invoice->currency ) );
	}
	
	/**
	 * On Paid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onPaid( Invoice $invoice ): void
	{
		$credits = $invoice->member->cm_credits;
		
		$oldAmount = $credits[ $this->price->currency ]->amount;
		$credits[ $this->price->currency ]->amount = $credits[ $this->price->currency ]->amount->add( $this->price->amount );
		$invoice->member->cm_credits = $credits;
		$invoice->member->save();
		
		$invoice->member->log( 'comission', array(
			'type'			=> 'bought',
			'currency'		=> $this->price->currency,
			'amount'		=> $oldAmount,
			'new_amount'	=> $credits[ $this->price->currency ]->amount,
			'invoice_id'	=> $invoice->id,
			'invoice_title'	=> $invoice->title
		) );
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
		
		$message = Member::loggedIn()->language()->addToStack('account_credit_remove', FALSE, array( 'sprintf' => array( $this->price, $invoice->member->cm_name ) ) );
		
		$credits = $invoice->member->cm_credits;
		if ( !$credits[ $this->price->currency ]->amount->subtract( $this->price->amount )->isPositive() )
		{
			$return[] = array( 'message' => $message, 'warning' => Member::loggedIn()->language()->addToStack('account_credit_remove_neg') );
		}
		else
		{
			$return[] = $message;
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
		$credits = $invoice->member->cm_credits;
		
		$oldAmount = $credits[ $this->price->currency ]->amount;
		$credits[ $this->price->currency ]->amount = $credits[ $this->price->currency ]->amount->subtract( $this->price->amount );
		$invoice->member->cm_credits = $credits;
		$invoice->member->save();
		
		$invoice->member->log( 'comission', array(
			'type'			=> 'bought',
			'currency'		=> $this->price->currency,
			'amount'		=> $oldAmount,
			'new_amount'	=> $credits[ $this->price->currency ]->amount,
			'invoice_id'	=> $invoice->id,
			'invoice_title'	=> $invoice->title
		) );
	}
}