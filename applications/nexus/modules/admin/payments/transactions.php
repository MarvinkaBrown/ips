<?php
/**
 * @brief		Transactions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Feb 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use function defined;
use function in_array;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NODE;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Transactions
 */
class transactions extends Controller
{	
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'transactions_manage' );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'transaction.css', 'nexus', 'admin' ) );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Create Table */
		$table = Transaction::table( array( array( 't_status<>?', Transaction::STATUS_PENDING ) ), Url::internal( 'app=nexus&module=payments&controller=transactions' ), 't' );
		$table->filters = array(
			'trans_attention_required'	=> array( Db::i()->in( 't_status', array( Transaction::STATUS_HELD, Transaction::STATUS_WAITING, Transaction::STATUS_REVIEW, Transaction::STATUS_DISPUTED ) ) ),
		);
		$table->advancedSearch = array(
			't_id'		=> SEARCH_CONTAINS_TEXT,
			't_status'	=> array( SEARCH_SELECT, array( 'options' => Transaction::statuses(), 'multiple' => TRUE ) ),
			't_member'	=> SEARCH_MEMBER,
			't_amount'	=> SEARCH_NUMERIC,
			't_method'	=> array( SEARCH_NODE, array( 'class' => '\IPS\nexus\Gateway' ) ),
			't_date'	=> SEARCH_DATE_RANGE,
		);
		$table->quickSearch = 't_id';
		
		/* Display */
		if ( isset( Request::i()->attn ) and Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( '( t_status=? OR t_status=? OR t_status=? )', Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_DISPUTED ) ) )
		{
			$table->filter = 'trans_attention_required';
		}
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__nexus_payments_transactions');
		Output::i()->output	= (string) $table;
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view() : void
	{
		/* Load Transaction */
		try
		{
			$transaction = Transaction::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X186/8', 404, '' );
		}
				
		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'transaction_number', FALSE, array( 'sprintf' => array( $transaction->id ) ) );
		Output::i()->output = Theme::i()->getTemplate( 'transactions' )->view( $transaction );
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	public function approve() : void
	{
		Dispatcher::i()->checkAcpPermission( 'transactions_edit' );
		Session::i()->csrfCheck();

		/* Load Transaction */
		try
		{
			$transaction = Transaction::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X186/9', 404, '' );
		}

		$method = $transaction->method;

		/* Can we approve it? */
		if ( !$method or !in_array( $transaction->status, array( Transaction::STATUS_WAITING, Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_GATEWAY_PENDING, Transaction::STATUS_DISPUTED ) ) )
		{
			Output::i()->error( 'transaction_status_err', '2X186/A', 403, '' );
		}
		
		/* Log it */
		if( $transaction->member )
		{
			$transaction->member->log( 'transaction', array(
				'type'		=> 'status',
				'status'	=> Transaction::STATUS_PAID,
				'id'		=> $transaction->id
			) );
		}

		/* Do it */
		try
		{
			if ( $transaction->status !== Transaction::STATUS_DISPUTED )
			{
				$transaction->capture();
			}

			$transaction->approve( Member::loggedIn() );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( $e->getMessage(), '3X186/2', 500, '' );
		}
		catch ( RuntimeException )
		{
			Output::i()->error( 'transaction_capture_err', '3X186/3', 500, '' );
		}
		
		/* Send Email */
		$transaction->sendNotification();
				
		/* Redirect */
		if ( Request::i()->isAjax() and Request::i()->queueStatus )
		{
			Output::i()->json( array( 'message' => Member::loggedIn()->language()->addToStack('tstatus_okay_set'), 'queue' => \IPS\nexus\extensions\core\AdminNotifications\Transaction::queueHtml( Request::i()->queueStatus ) ) );
		}
		else
		{
			$this->_redirect( $transaction );
		}
	}
	
	/**
	 * Flag for review
	 *
	 * @return	void
	 */
	public function review() : void
	{
		Dispatcher::i()->checkAcpPermission( 'transactions_edit' );
		Session::i()->csrfCheck();
		
		/* Load Transaction */
		try
		{
			$transaction = Transaction::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X186/C', 404, '' );
		}

		/* Can we flag it? */
		if ( !in_array( $transaction->status, array( Transaction::STATUS_WAITING, Transaction::STATUS_HELD ) ) )
		{
			Output::i()->error( 'transaction_status_err', '2X186/B', 403, '' );
		}
		
		/* Set it */
		$extra = $transaction->extra;
		$extra['history'][] = array( 's' => Transaction::STATUS_REVIEW, 'on' => time(), 'by' => Member::loggedIn()->member_id );
		$transaction->extra = $extra;
		$transaction->status = Transaction::STATUS_REVIEW;
		$transaction->save();
		
		/* Log it */
		if( $transaction->member )
		{
			$transaction->member->log( 'transaction', array(
				'type'		=> 'status',
				'status'	=> Transaction::STATUS_REVIEW,
				'id'		=> $transaction->id
			) );
		}
		
		/* Notification */
		AdminNotification::send( 'nexus', 'Transaction', Transaction::STATUS_REVIEW, TRUE, $transaction );
		
		/* Redirect */
		if ( Request::i()->isAjax() and Request::i()->queueStatus )
		{
			Output::i()->json( array( 'message' => Member::loggedIn()->language()->addToStack('tstatus_revw_set'), 'queue' => \IPS\nexus\extensions\core\AdminNotifications\Transaction::queueHtml( Request::i()->queueStatus ) ) );
		}
		else
		{
			$this->_redirect( $transaction );
		}
	}
	
	/**
	 * Void
	 *
	 * @return	void
	 */
	public function void() : void
	{
		Dispatcher::i()->checkAcpPermission( 'transactions_edit' );
		Session::i()->csrfCheck();
		
		/* Load Transaction */
		try
		{
			$transaction = Transaction::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X186/4', 404, '' );
		}

		/* Can we void it? */
		if ( !in_array( $transaction->status, array( Transaction::STATUS_WAITING, Transaction::STATUS_GATEWAY_PENDING ) ) and ( !$transaction->auth or !in_array( $transaction->status, array( Transaction::STATUS_HELD, Transaction::STATUS_REVIEW ) ) ) )
		{
			Output::i()->error( 'transaction_status_err', '2X186/5', 403, '' );
		}
		
		/* Void it */
		try
		{
			$transaction->void();
		}
		catch ( Exception $e )
		{
			if ( !isset( Request::i()->override ) )
			{
				Output::i()->error( Member::loggedIn()->language()->addToStack( 'transaction_void_err', FALSE, array( 'sprintf' => array( $transaction->acpUrl()->setQueryString( array( 'do' => 'void', 'override' => 1 ) ) ) ) ), '3X186/6', 500, '', array(), $e->getMessage() );
			}
		}
		
		/* Send Email */
		$transaction->sendNotification();
		
		/* Redirect */
		if ( Request::i()->isAjax() and Request::i()->queueStatus )
		{
			Output::i()->json( array( 'message' => Member::loggedIn()->language()->addToStack('tstatus_fail_set'), 'queue' => \IPS\nexus\extensions\core\AdminNotifications\Transaction::queueHtml( Request::i()->queueStatus ) ) );
		}
		else
		{
			$this->_redirect( $transaction );
		}
	}	
	
	/**
	 * Refund
	 *
	 * @return	void
	 */
	public function refund() : void
	{
		Dispatcher::i()->checkAcpPermission( 'transactions_refund' );
		
		/* Load Transaction */
		try
		{
			$transaction = Transaction::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X186/D', 404, '' );
		}
		$method = $transaction->method;
		
		/* Can we refund it? */
		if ( !in_array( $transaction->status, array( Transaction::STATUS_PAID, Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_PART_REFUNDED, Transaction::STATUS_DISPUTED ) ) )
		{
			Output::i()->error( 'transaction_status_err', '2X186/E', 403, '' );
		}
		
		/* What are the refund methods? */
		$refundMethods = array();
		$refundMethodToggles = array( 'none' => array( 'refund_reverse_credit' ) );
		$refundReasons = array();
		if ( $method and $method::SUPPORTS_REFUNDS )
		{
			$refundMethods['gateway'] = 'transaction_refund';
			$refundMethodToggles['gateway'] = array( 'refund_reverse_credit' );
			if ( $method::SUPPORTS_PARTIAL_REFUNDS )
			{
				$refundMethodToggles['gateway'][] = 'refund_amount';
			}
			if ( $refundReasons = $method::refundReasons() )
			{
				$refundMethodToggles['gateway'][] = 'refund_reason';
			}
		}
		if ( $transaction->credit->amount->compare( $transaction->amount->amount ) === -1 )
		{
			$refundMethods['credit'] = 'refund_method_credit';
			$refundMethodToggles['credit'][] = 'refund_credit_amount';
		}
		$refundMethods['none'] = 'refund_method_none';
		
		/* Build form */
		$form = new Form;
		$form->add( new Radio( 'refund_method', ( $method and $method::SUPPORTS_REFUNDS ) ? 'gateway' : 'credit', TRUE, array( 'options' => $refundMethods, 'toggles' => $refundMethodToggles ) ) );
		if ( $refundReasons )
		{
			$form->add( new Radio( 'refund_reason', NULL, FALSE, array( 'options' => $refundReasons ), NULL, NULL, NULL, 'refund_reason' ) );
		}
		if ( $method and $method::SUPPORTS_REFUNDS and $method::SUPPORTS_PARTIAL_REFUNDS )
		{
			$form->add( new Number( 'refund_amount', 0, TRUE, array(
				'unlimited'		=> 0,
				'unlimitedLang'	=> (
					$transaction->partial_refund->amount->isGreaterThanZero()
						? Member::loggedIn()->language()->addToStack( 'refund_full_remaining', FALSE, array( 'sprintf' => array(
							new Money( $transaction->amount->amount->subtract( $transaction->partial_refund->amount ), $transaction->currency ) )
						) )
						: Member::loggedIn()->language()->addToStack( 'refund_full', FALSE, array( 'sprintf' => array( $transaction->amount ) ) )
				),
				'max'			=> (string) $transaction->amount->amount->subtract( $transaction->partial_refund->amount ),
				'decimals' 		=> TRUE
			), NULL, NULL, $transaction->amount->currency, 'refund_amount' ) );
			
			if ( $transaction->credit->amount->isGreaterThanZero() )
			{
				Member::loggedIn()->language()->words['refund_amount_desc'] = sprintf( Member::loggedIn()->language()->get('refund_amount_descwarn'), $transaction->credit );
			}
		}
		if ( $transaction->credit->amount->compare( $transaction->amount->amount ) === -1 )
		{
			$form->add( new Number( 'refund_credit_amount', 0, TRUE, array(
				'unlimited'		=> 0,
				'unlimitedLang'	=> (
					$transaction->credit->amount->isGreaterThanZero()
						? Member::loggedIn()->language()->addToStack( 'refund_full_remaining', FALSE, array( 'sprintf' => array(
							new Money( $transaction->amount->amount->subtract( $transaction->credit->amount ), $transaction->currency ) )
						) )
						: Member::loggedIn()->language()->addToStack( 'refund_full', FALSE, array( 'sprintf' => array( $transaction->amount ) ) )
				),
				'max'			=> (string) $transaction->amount->amount->subtract( $transaction->credit->amount ),
				'decimals' 		=> TRUE
			), NULL, NULL, $transaction->amount->currency, 'refund_credit_amount' ) );
			
			if ( $transaction->partial_refund->amount->isGreaterThanZero() )
			{
				Member::loggedIn()->language()->words['refund_credit_amount_desc'] = sprintf( Member::loggedIn()->language()->get('refund_credit_amount_descwarn'), $transaction->partial_refund );
			}
		}
		if ( $transaction->credit->amount->isGreaterThanZero() )
		{
			$form->add( new YesNo( 'refund_reverse_credit', TRUE, TRUE, array( 'togglesOn' => array( 'form_refund_reverse_credit_warning' ) ), NULL, NULL, NULL, 'refund_reverse_credit' ) );
			Member::loggedIn()->language()->words['refund_reverse_credit'] = sprintf( Member::loggedIn()->language()->get( 'refund_reverse_credit' ), $transaction->credit );
			
			$credits = $transaction->member->cm_credits;
			if ( $credits[ $transaction->amount->currency ]->amount->compare( $transaction->credit->amount ) === -1 )
			{
				Member::loggedIn()->language()->words['refund_reverse_credit_warning'] = Member::loggedIn()->language()->addToStack( 'account_credit_remove_neg' );
			}
		}
		if ( $transaction->invoice !== NULL and $transaction->invoice->status === Invoice::STATUS_PAID )
		{
			$field = new Radio( 'refund_invoice_status', Invoice::STATUS_PENDING, TRUE, array(
				'options' => array(
					Invoice::STATUS_PAID	=> 'refund_invoice_paid',
					Invoice::STATUS_PENDING	=> 'refund_invoice_pending',
					Invoice::STATUS_CANCELED	=> 'refund_invoice_canceled',
				),
				'toggles'	=> array(
					Invoice::STATUS_PENDING	=> array( 'form_refund_invoice_status_warning' ),
					Invoice::STATUS_CANCELED	=> array( 'form_refund_invoice_status_warning' )
				)
			) );
			$field->warningBox = Theme::i()->getTemplate('invoices')->unpaidConsequences( $transaction->invoice );
			$form->add( $field );
		}
		if ( $billingAgreement = $transaction->billing_agreement )
		{
			$form->add( new YesNo( 'refund_cancel_billing_agreement', TRUE, NULL, array( 'togglesOff' => array( 'form_refund_cancel_billing_agreement_warning' ) ) ) );
			
			if ( Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_billing_agreement=? AND t_id<?', $billingAgreement->id, $transaction->id ) )->first() )
			{
				unset( Member::loggedIn()->language()->words['refund_cancel_billing_agreement_warning'] );
			}
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Handle billing agreement */
			if ( $transaction->billing_agreement )
			{
				if ( isset( $values['refund_cancel_billing_agreement'] ) and $values['refund_cancel_billing_agreement'] )
				{
					try
					{
						$transaction->billing_agreement->cancel();
					}
					catch ( Exception $e )
					{
						Output::i()->error( 'billing_agreement_cancel_error', '3X186/G', 500, '', array(), $e->getMessage() );
					}
				}
			}
			
			/* Reverse credit */
			if ( $values['refund_method'] !== 'credit' and isset( $values['refund_reverse_credit'] ) and $values['refund_reverse_credit'] )
			{
				$transaction->reverseCredit();
			}
			
			/* Refund */
			try
			{
				$amount = NULL;
				if ( $values['refund_method'] === 'gateway' and isset( $values['refund_amount'] ) )
				{
					$amount = $values['refund_amount'];
				}
				elseif ( $values['refund_method'] === 'credit' and isset( $values['refund_credit_amount'] ) )
				{
					$amount = $values['refund_credit_amount'];
				}
								
				$transaction->refund( $values['refund_method'], $amount, $values['refund_reason'] ?? NULL);
			}
			catch ( LogicException $e )
			{
				Output::i()->error( $e->getMessage(), '1X186/1', 500, '' );
			}
			catch ( RuntimeException )
			{
				Output::i()->error( 'refund_failed', '3X186/7', 500, '' );
			}
			
			/* Handle invoice */
			if( $transaction->invoice !== NULL )
			{
				if ( isset( $values['refund_invoice_status'] ) and $values['refund_invoice_status'] !== Invoice::STATUS_PAID )
				{
					$transaction->invoice->markUnpaid( $values['refund_invoice_status'] );

					if( $transaction->invoice->member )
					{
						$transaction->invoice->member->log( 'invoice', array(
							'type'	=> 'status',
							'new'	=> $values['refund_invoice_status'],
							'id'	=> $transaction->invoice->id,
							'title' => $transaction->invoice->title
						) );
					}
				}

				/* Send Email */
				$transaction->sendNotification();
			}
						
			/* Redirect */
			$this->_redirect( $transaction );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'transaction_refund_title', FALSE, array( 'sprintf' => array( $transaction->amount ) ) );
		Output::i()->output = $form;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'transactions_delete' );
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		/* Load Transaction */
		try
		{
			$transaction = Transaction::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X186/F', 404, '' );
		}
		
		/* Delete */
		$transaction->delete();
		
		/* Log it */
		try
		{
			if( $transaction->member )
			{
				$transaction->member->log( 'transaction', array(
					'type'		=> 'delete',
					'id'		=> $transaction->id,
					'method'	=> $transaction->method ? $transaction->method->id : NULL,
				) );
			}
		}
		catch ( OutOfRangeException )
		{
			// If the member no longer exists, we just won't log it
		}
		
		/* Redirect */
		Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=transactions')->getSafeUrlFromFilters());
	}
	
	
	/**
	 * Redirect
	 *
	 * @param	Transaction	$transaction	The transaction
	 * @return	void
	 */
	protected function _redirect( Transaction $transaction ) : void
	{
		if ( isset( Request::i()->r ) )
		{
			switch ( Request::i()->r )
			{
				case 'v':
					Output::i()->redirect( $transaction->acpUrl()->getSafeUrlFromFilters() );
					break;
					
				case 'i':
					Output::i()->redirect( $transaction->invoice->acpUrl()->getSafeUrlFromFilters() );
					break;
				
				case 'c':
					Output::i()->redirect( $transaction->member->acpUrl()->getSafeUrlFromFilters());
					break;
				
				case 't':
					Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=transactions')->getSafeUrlFromFilters() );
					break;
					
				case 'n':
					Output::i()->redirect( Url::internal('app=core&module=overview&controller=notifications')->getSafeUrlFromFilters());
					break;
			}
		}
		
		Output::i()->redirect( $transaction->acpUrl()->getSafeUrlFromFilters());
	}
}