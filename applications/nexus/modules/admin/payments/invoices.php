<?php
/**
 * @brief		Invoices
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Feb 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Email;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Customer\Address;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Money;
use IPS\nexus\Package\Group;
use IPS\nexus\Purchase;
use IPS\nexus\Tax;
use IPS\nexus\Transaction;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function in_array;
use function is_array;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoices
 */
class invoices extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'invoices_manage' );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'invoice.css', 'nexus', 'admin' ) );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		/* Table */
		$url = Url::internal( 'app=nexus&module=payments&controller=invoices' );
		$where = array();
		$customer = NULL;
		if ( isset( Request::i()->member ) )
		{
			try
			{
				$customer = Customer::load( Request::i()->member );
				$url = $url->setQueryString( 'member', $customer->member_id );
				$where[] = array( 'i_member=?', $customer->member_id );
			}
			catch ( OutOfRangeException ) { }
		}
		$table = Invoice::table( $where, $url, 't' );
		$table->advancedSearch = array(
			'i_id'		=> SEARCH_CONTAINS_TEXT,
			'i_title'	=> SEARCH_CONTAINS_TEXT,
			'i_status'	=> array( SEARCH_SELECT, array( 'options' => Invoice::statuses(), 'multiple' => TRUE ) ),
			'i_member'	=> SEARCH_MEMBER,
			'i_total'	=> SEARCH_NUMERIC,
			'i_date'	=> SEARCH_DATE_RANGE,
		);
		$table->quickSearch = 'i_id';
		$table->filters		= array(
			'istatus_paid'   => array( 'i_status=?', Invoice::STATUS_PAID ),
			'istatus_pend' => array( 'i_status=?', Invoice::STATUS_PENDING ),
			'istatus_expd'	 => array( 'i_status=?', Invoice::STATUS_EXPIRED ),
			'istatus_canc'	 => array( 'i_status=?'  , Invoice::STATUS_CANCELED )
		);
		$table->mainColumn = 'i_title';
		if ( $customer )
		{
			unset( $table->advancedSearch['i_member'] );
		}
				
		/* Action Buttons */
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_add' ) )
		{
			$generateUrl = Url::internal( "app=nexus&module=payments&controller=invoices&do=generate&_new=1" );
			
			if ( $customer )
			{
				$generateUrl = $generateUrl->setQueryString( 'member', $customer->member_id );
			}
			
			Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'plus',
				'title'	=> 'generate_invoice',
				'link'	=> $generateUrl
			);
		}
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_settings' ) and !$customer )
		{
			Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'cog',
				'title'	=> 'invoice_settings',
				'link'	=> Url::internal( "app=nexus&module=payments&controller=invoices&do=settings" )
			);
		}
		
		/* Display */
		Output::i()->title = $customer ? Member::loggedIn()->language()->addToStack( 'members_invoices', FALSE, array( 'sprintf' => array( $customer->cm_name ) ) ) : Member::loggedIn()->language()->addToStack('menu__nexus_payments_invoices');
		Output::i()->output = (string) $table;
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view() : void
	{
		/* Load Invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/3', 404, '' );
		}
				
		/* Get transactions */
		$transactions = NULL;
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) and ( !isset( Request::i()->table ) ) )
		{
			$transactions = Transaction::table( array( array( 't_invoice=? and t_status<>?', $invoice->id, Transaction::STATUS_PENDING ) ), $invoice->acpUrl(), 'i' );
			$transactions->limit = 50;
			$transactions->tableTemplate = array( Theme::i()->getTemplate('invoices'), 'transactionsTable' );
			$transactions->rowsTemplate = array( Theme::i()->getTemplate('invoices'), 'transactionsTableRows' );

			foreach ( $transactions->include as $k => $v )
			{
				if ( in_array( $v, array( 't_member', 't_invoice' ) ) )
				{
					unset( $transactions->include[ $k ] );
				}
			}
		}

		/* Add Buttons */
		Output::i()->sidebar['actions'] = $invoice->buttons( 'v' );
		
		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $invoice->id ) ) );
		Output::i()->output = Theme::i()->getTemplate( 'invoices' )->view( $invoice, $invoice->summary(), $transactions );
	}
	
	/**
	 * Paid
	 *
	 * @return	void
	 */
	public function paid() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_edit' );
		Session::i()->csrfCheck();
				
		/* Load Invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/6', 404, '' );
		}
		
		/* Do we have a billing address? */
		if ( !$invoice->billaddress AND $invoice->hasItemsRequiringBillingAddress() )
		{
			Output::i()->error( 'err_no_billaddress', '2X190/I', 403 );
		}
		
		/* Any pending transactions? */
		if ( !isset( Request::i()->override ) and Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
		{
			$pendingTransactions = $invoice->transactions( array( Transaction::STATUS_WAITING, Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_GATEWAY_PENDING ) );
			if ( count( $pendingTransactions ) )
			{
				$transUrl = $invoice->acpUrl();
				if ( count( $pendingTransactions ) === 1 )
				{
					foreach ( $pendingTransactions as $transaction )
					{
						$transUrl = $transaction->acpUrl();
					}
				}

				Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->decision( 'invoice_paid_trans', array(
					'invoice_paid_trans_view'	=> $transUrl,
					'invoice_paid_trans_ovrd'	=> $invoice->acpUrl()->setQueryString( array( 'do' => 'paid', 'override' => 1 ) )->csrf()
				) );
				return;
			}
		}
		
		/* Log (do this first so the log appears in the correct order) */
		$invoice->member->log( 'invoice', array(
			'type'	=> 'status',
			'new'	=> Invoice::STATUS_PAID,
			'id'	=> $invoice->id,
			'title' => $invoice->title
		) );
		
		/* Send Email */
		Email::buildFromTemplate( 'nexus', 'invoiceMarkedPaid', array( $invoice, $invoice->summary() ), Email::TYPE_TRANSACTIONAL )
			->send(
				$invoice->member,
				array_map(
					function( $contact )
					{
						return $contact->alt_id->email;
					},
					iterator_to_array( $invoice->member->alternativeContacts( array( 'billing=1' ) ) )
				),
				( ( in_array( 'new_invoice', explode( ',', Settings::i()->nexus_notify_copy_types ) ) AND Settings::i()->nexus_notify_copy_email ) ? explode( ',', Settings::i()->nexus_notify_copy_email ) : array() )
			);
		
		/* Do it */
		$invoice->markPaid( Member::loggedIn() );
		
		/* Redirect */
		$this->_redirect( $invoice );
	}
	
	/**
	 * Charge to card
	 *
	 * @return	void
	 */
	public function card() : void
	{
		Dispatcher::i()->checkAcpPermission( 'chargetocard' );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customer.css', 'nexus', 'admin' ) );

		/* Load Invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/8', 404, '' );
		}
		
		/* Get gateways */
		$gateways = Gateway::manualChargeGateways( $invoice->member );
		
		/* Can we do this? */
		if ( $invoice->status !== Invoice::STATUS_PENDING or !count( $gateways ) )
		{
			Output::i()->error( 'invoice_status_err', '2X190/9', 403, '' );
		}

		$self = $this;
		/* Wizard */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'invoice_charge_to_card' );
		Output::i()->output = (string) new Wizard(
			array(
				't_amount'		=> function( $data ) use ( $invoice )
				{					
					$amountToPay = $invoice->amountToPay()->amount;
					$form = new Form( 'amount', 'continue' );
					$form->add( new Number( 't_amount', $amountToPay, TRUE, array( 'min' => 0.01, 'max' => (string) $amountToPay, 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
					if ( $values = $form->values() )
					{
						return $values;
					}
					return $form;
				},
				'checkout_pay'	=> function( $data ) use ( $invoice, $gateways, $self )
				{
					$amountToPay = new Money( $data['t_amount'], $invoice->currency );
					
					/* Get elements */
					$elements = array();
					$paymentMethodsToggles = array();
					foreach ( $gateways as $gateway )
					{
						foreach ( $gateway->paymentScreen( $invoice, $amountToPay, $invoice->member, array(), 'admin' ) as $element )
						{
							if ( !$element->htmlId )
							{
								$element->htmlId = $gateway->id . '-' . $element->name;
							}
							$elements[] = $element;
							$paymentMethodsToggles[ $gateway->id ][] = $element->htmlId;
						}
					}
					$paymentMethodOptions = array();
					foreach ( $gateways as $k => $v )
					{
						$paymentMethodOptions[ $k ] = $v->_title;
					}
					
					/* Build form */
					$form = new Form( 'charge', 'invoice_charge_to_card' );
					if ( isset( Request::i()->previousTransactions ) )
					{
						$form->hiddenValues['previousTransactions'] = Request::i()->previousTransactions;
					}
					else
					{
						if ( $previousTransactions = $invoice->transactions() and count( $previousTransactions ) )
						{
							$previousTransactionIds = array();
							foreach ( $previousTransactions as $previousTransaction )
							{
								$previousTransactionIds[] = $previousTransaction->id;
							}
							$form->hiddenValues['previousTransactions'] = implode( ',', $previousTransactionIds );
						}
					}
					if ( count( $gateways ) > 1 )
					{
						$form->add( new Radio( 'payment_method', NULL, TRUE, array( 'options' => $paymentMethodOptions, 'toggles' => $paymentMethodsToggles ) ) );
					}
					foreach ( $elements as $element )
					{
						$form->add( $element );
					}
						
					/* Handle submissions */
					if ( $values = $form->values() )
					{
						if ( count( $gateways ) === 1 )
						{
							$gateway = array_pop( $gateways );
						}
						else
						{
							$gateway = $gateways[ $values['payment_method'] ];
						}
						
						$transaction = new Transaction;
						$transaction->member = $invoice->member;
						$transaction->invoice = $invoice;
						$transaction->method = $gateway;
						$transaction->amount = $amountToPay;						
						$transaction->currency = $invoice->currency;
						$transaction->ip = Request::i()->ipAddress();
						$transaction->extra = array( 'admin' => Member::loggedIn()->member_id );
						
						try
						{
							$auth = $gateway->auth( $transaction, $values, NULL, array(), 'manual' );
							if ( is_array( $auth ) )
							{
								return $this->_webhookRedirector( $invoice, $auth );
							}
							else
							{				
								$transaction->auth = $auth;
							}
							$transaction->capture();
							
							$transaction->member->log( 'transaction', array(
								'type'			=> 'paid',
								'status'		=> Transaction::STATUS_PAID,
								'id'			=> $transaction->id,
								'invoice_id'	=> $invoice->id,
								'invoice_title'	=> $invoice->title,
							) );
							
							$transaction->approve();
							
							$transaction->sendNotification();
							
							$self->_redirect( $invoice );
						}
						catch ( Exception $e )
						{
							$form->error = $e->getMessage();
							return $form;
						}						
					}
					
					/* Display form */
					Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_gateways.js', 'nexus', 'global' ) );
					return $form;
				}

			),
			$invoice->acpUrl()->setQueryString( 'do', 'card' )
		);
	}
	
	/**
	 * Charge to account credit
	 *
	 * @return	void
	 */
	public function credit() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_edit' );
		
		/* Load Invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/A', 404, '' );
		}
				
		/* Can we do this? */
		if ( $invoice->status !== Invoice::STATUS_PENDING )
		{
			Output::i()->error( 'invoice_status_err', '2X190/B', 403, '' );
		}
		
		/* How much can we do? */
		$amountToPay = $invoice->amountToPay()->amount;
		$credits = $invoice->member->cm_credits;
		$credit = $credits[ $invoice->currency ]->amount;
		$maxCanCharge = ( $credit->compare( $amountToPay ) === -1 ) ? $credit : $amountToPay;

		/* Build Form */
		$form = new Form( 'amount', 'invoice_charge_to_credit' );
		$form->add( new Number( 't_amount', $maxCanCharge, TRUE, array( 'min' => 0.01, 'max' => (string) $maxCanCharge, 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{			
			$transaction = new Transaction;
			$transaction->member = $invoice->member;
			$transaction->invoice = $invoice;
			$transaction->amount = new Money( $values['t_amount'], $invoice->currency );
			$transaction->ip = Request::i()->ipAddress();
			$transaction->extra = array( 'admin' => Member::loggedIn()->member_id );
			$transaction->save();
			$transaction->approve( NULL );
			$transaction->sendNotification();
			
			$credits[ $invoice->currency ]->amount = $credits[ $invoice->currency ]->amount->subtract( $transaction->amount->amount );
			$invoice->member->cm_credits = $credits;
			$invoice->member->save();
			
			$invoice->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> Transaction::STATUS_PAID,
				'id'			=> $transaction->id,
				'invoice_id'	=> $invoice->id,
				'invoice_title'	=> $invoice->title,
			) );
			
			$this->_redirect( $invoice );
		}
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'invoice_charge_to_credit' );
		Output::i()->output = $form;
	}
	
	/**
	 * Reissue
	 *
	 * @return	void
	 */
	public function resend() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_resend' );
		Session::i()->csrfCheck();
		
		/* Load Invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/C', 404, '' );
		}
		
		/* Update */
		$invoice->date = new DateTime;
		$invoice->status = Invoice::STATUS_PENDING;
		$invoice->save();
		
		/* Send email */
		$emailSent = FALSE;
		if ( isset( Request::i()->prompt ) and Request::i()->prompt )
		{
			$emailSent = TRUE;
			$invoice->sendNotification();
		}
		
		/* Log */
		$invoice->member->log( 'invoice', array( 'type' => 'resend', 'id' => $invoice->id, 'title' => $invoice->title, 'email' => $emailSent ) );
		
		/* Redirect */
		$this->_redirect( $invoice );
	}
	
	/**
	 * Print
	 *
	 * @return	void
	 */
	public function printout() : void
	{
		/* Load */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/D', 404, '' );
		}
		
		/* Get output */
		$output = Theme::i()->getTemplate( 'invoices', 'nexus', 'global' )->printInvoice( $invoice, $invoice->summary(), $invoice->billaddress ?: $invoice->member->primaryBillingAddress() );
		Output::i()->title = 'I' . $invoice->id;
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'front' )->blankTemplate( $output ) );
	}
	
	/**
	 * Unpaid
	 *
	 * @return	void
	 */
	public function unpaid() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_edit' );
		
		/* Load Invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/7', 404, '' );
		}
						
		/* Get paid transactions */
		$transactions = $invoice->transactions( array( Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED ) );
		
		/* Build form */
		$form = new Form;
		
		/* Ask what we want to do with the transactions */
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_refund' ) )
		{
			foreach ( $transactions as $transaction )
			{
				/* What refund options are available? */
				/* @var Gateway $method */
				$method = $transaction->method;
				$refundMethods = array();
				$refundMethodToggles = array( 'none' => array( $transaction->id . '_refund_reverse_credit' ) );
				$refundReasons = array();
				if ( $method and $method::SUPPORTS_REFUNDS )
				{
					$refundMethods['gateway'] = 'transaction_refund';
					$refundMethodToggles['gateway'] = array( $transaction->id . '_refund_reverse_credit' );
					if ( $method::SUPPORTS_PARTIAL_REFUNDS )
					{
						$refundMethodToggles['gateway'][] = $transaction->id . '_refund_amount';
					}
					if ( $refundReasons = $method::refundReasons() )
					{
						$refundMethodToggles['gateway'][] = $transaction->id . '_refund_reason';
					}
				}
				if ( $transaction->credit->amount->compare( $transaction->amount->amount ) === -1 )
				{
					$refundMethods['credit'] = 'refund_method_credit';
					$refundMethodToggles['credit'][] = $transaction->id . '_refund_credit_amount';
				}
				$refundMethods['none'] = 'refund_method_none';
				
				/* How do we want to refund? */
				$field = new Radio( $transaction->id . '_refund_method', 'none', TRUE, array( 'options' => $refundMethods, 'toggles' => $refundMethodToggles ) );
				$field->label = count( $transactions ) === 1 ? Member::loggedIn()->language()->addToStack( 'refund_method' ) : Member::loggedIn()->language()->addToStack( 'trans_refund_method', FALSE, array( 'sprintf' => array( $transaction->id ) ) );
				$form->add( $field );
				if ( $refundReasons )
				{
					$field = new Radio( $transaction->id . '_refund_reason', NULL, FALSE, array( 'options' => $refundReasons ), NULL, NULL, NULL, $transaction->id . '_refund_reason' );
					$field->label = count( $transactions ) === 1 ? Member::loggedIn()->language()->addToStack( 'refund_reason' ) : Member::loggedIn()->language()->addToStack( 'trans_refund_reason', FALSE, array( 'sprintf' => array( $transaction->id ) ) );
					$form->add( $field );
				}
				
				/* Partial refund? */
				if ( $method and $method::SUPPORTS_REFUNDS and $method::SUPPORTS_PARTIAL_REFUNDS )
				{
					$field = new Number( $transaction->id . '_refund_amount', 0, TRUE, array(
						'unlimited' => 0,
						'unlimitedLang'	=> (
							$transaction->partial_refund->amount->isGreaterThanZero()
								? Member::loggedIn()->language()->addToStack( 'refund_full_remaining', FALSE, array( 'sprintf' => array(
									new Money( $transaction->amount->amount->subtract( $transaction->partial_refund->amount ), $transaction->currency ) )
								) )
								: Member::loggedIn()->language()->addToStack( 'refund_full', FALSE, array( 'sprintf' => array( $transaction->amount ) ) )
						),
						'max'			=> (string) $transaction->amount->amount->subtract( $transaction->partial_refund->amount ),
						'decimals' 		=> TRUE
					), NULL, NULL, $transaction->amount->currency, $transaction->id . '_refund_amount' );
					$field->label = Member::loggedIn()->language()->addToStack( 'refund_amount' );
					$form->add( $field );
					if ( $transaction->credit->amount->isGreaterThanZero() )
					{
						Member::loggedIn()->language()->words[ $transaction->id . '_refund_amount_desc' ] = sprintf( Member::loggedIn()->language()->get('refund_amount_descwarn'), $transaction->credit );
					}
				}
				if ( $transaction->credit->amount->compare( $transaction->amount->amount ) === -1 )
				{
					$field = new Number( $transaction->id . '_refund_credit_amount', 0, TRUE, array(
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
					), NULL, NULL, $transaction->amount->currency, $transaction->id . '_refund_credit_amount' );
					$field->label = Member::loggedIn()->language()->addToStack( 'refund_credit_amount' );
					$form->add( $field );
					
					if ( $transaction->partial_refund->amount->isGreaterThanZero() )
					{
						Member::loggedIn()->language()->words[ $transaction->id . '_refund_credit_amount_desc' ] = sprintf( Member::loggedIn()->language()->get('refund_credit_amount_descwarn'), $transaction->partial_refund );
					}
				}
				
				/* Reverse credit? */
				if ( $transaction->credit->amount->isGreaterThanZero() )
				{
					$field = new YesNo( $transaction->id . '_refund_reverse_credit', TRUE, TRUE, array( 'togglesOn' => array( "form_{$transaction->id}_refund_reverse_credit_warning" ) ), NULL, NULL, NULL, $transaction->id . '_refund_reverse_credit' );
					$field->label = Member::loggedIn()->language()->addToStack( 'refund_reverse_credit', FALSE, array( 'sprintf' => array( $transaction->credit ) ) );
					Member::loggedIn()->language()->words[ $transaction->id . '_refund_reverse_credit_desc' ] = Member::loggedIn()->language()->addToStack( 'refund_reverse_credit_desc' );
					$form->add( $field );
					
					$credits = $transaction->member->cm_credits;
					if ( $credits[ $transaction->amount->currency ]->amount->compare( $transaction->credit->amount ) === -1 )
					{
						Member::loggedIn()->language()->words[ $transaction->id . '_refund_reverse_credit_warning' ] = Member::loggedIn()->language()->addToStack( 'account_credit_remove_neg' );
					}
				}
				
				/* Billing Agreement? */
				/* @var Customer\BillingAgreement $billingAgreement */
				if ( $billingAgreement = $transaction->billing_agreement AND $billingAgreement->status() !== $billingAgreement::STATUS_CANCELED )
				{
					$field = new YesNo( $transaction->id . '_refund_cancel_billing_agreement', TRUE, NULL, array( 'togglesOff' => array( "form_{$transaction->id}_refund_cancel_billing_agreement_warning" ) ) );
					$field->label = Member::loggedIn()->language()->addToStack( 'refund_cancel_billing_agreement' );
					Member::loggedIn()->language()->words[ $transaction->id . '_refund_cancel_billing_agreement_desc' ] = Member::loggedIn()->language()->addToStack( 'refund_cancel_billing_agreement_desc' );
					if ( !Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_billing_agreement=? AND t_id<?', $billingAgreement->id, $transaction->id ) )->first() )
					{
						Member::loggedIn()->language()->words[ $transaction->id . '_refund_cancel_billing_agreement_warning' ] = Member::loggedIn()->language()->addToStack( 'refund_cancel_billing_agreement_warning' );
					}
					
					$form->add( $field );
				}
			}
		}
		
		/* Do we want to mark the invoice as pending or canceled? */
		if ( $invoice->status === Invoice::STATUS_PAID )
		{
			$statusOptions = array();
			if ( !$invoice->total->amount->isZero() )
			{
				$statusOptions[ Invoice::STATUS_PENDING ] = 'refund_invoice_pending';
			}
			if ( Settings::i()->cm_invoice_expireafter )
			{
				$statusOptions[ Invoice::STATUS_EXPIRED ] = 'refund_invoice_expired';
			}
			$statusOptions[ Invoice::STATUS_CANCELED ] = 'refund_invoice_canceled';
			$field = new Radio( 'refund_invoice_status', Invoice::STATUS_CANCELED, TRUE, array( 'options' => $statusOptions ) );
			$field->warningBox = Theme::i()->getTemplate('invoices')->unpaidConsequences( $invoice );
			$form->add( $field );
		}
		else
		{
			$statusOptions = array();
			if ( Settings::i()->cm_invoice_expireafter )
			{
				$statusOptions[ Invoice::STATUS_EXPIRED ] = 'invoice_status_expd';
			}
			$statusOptions[ Invoice::STATUS_CANCELED ] = 'invoice_status_canc';
			$form->add( new Radio( 'refund_invoice_status', Invoice::STATUS_CANCELED, TRUE, array( 'options' => $statusOptions ) ) );
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Refund transactions */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_refund' ) )
			{
				foreach ( $transactions as $transaction )
				{
					/* Handle billing agreement */
					if ( $transaction->billing_agreement )
					{
						if ( isset( $values[ $transaction->id . '_refund_cancel_billing_agreement' ] ) and $values[ $transaction->id . '_refund_cancel_billing_agreement' ] )
						{
							try
							{
								$transaction->billing_agreement->cancel();
							}
							catch ( Exception $e )
							{
								Output::i()->error( 'billing_agreement_cancel_error', '3X190/G', 500, '', array(), $e->getMessage() );
							}
						}
					}
					/* Reverse credit */
					if ( $values[ $transaction->id . '_refund_method' ] !== 'credit' and isset( $values[ $transaction->id . '_refund_reverse_credit' ] ) and $values[ $transaction->id . '_refund_reverse_credit' ] )
					{
						$transaction->reverseCredit();
					}
					
					/* Refund */
					try
					{
						$amount = NULL;
						if ( $values[ $transaction->id . '_refund_method' ] === 'gateway' and isset( $values[ $transaction->id . '_refund_amount' ] ) )
						{
							$amount = $values[ $transaction->id . '_refund_amount' ];
						}
						elseif ( $values[ $transaction->id . '_refund_method' ] === 'credit' and isset( $values[ $transaction->id . '_refund_credit_amount' ] ) )
						{
							$amount = $values[ $transaction->id . '_refund_credit_amount' ];
						}
						
						$transaction->refund( $values[ $transaction->id . '_refund_method' ], $amount, isset( $values[ $transaction->id . '_refund_reason' ] ) ? $values[ $transaction->id . '_refund_reason' ] : NULL );
					}
					catch ( LogicException $e )
					{
						Output::i()->error( $e->getMessage(), '1X190/1', 500, '' );
					}
					catch ( RuntimeException )
					{
						Output::i()->error( 'refund_failed', '3X190/2', 500, '' );
					}
				}
			}
			
			/* Log */
			$invoice->member->log( 'invoice', array(
				'type'	=> 'status',
				'new'	=> $values['refund_invoice_status'],
				'id'	=> $invoice->id,
				'title' => $invoice->title
			) );
			
			/* Change invoice status */
			$invoice->markUnpaid( $values['refund_invoice_status'], Member::loggedIn() );
			
			/* Boink */
			$this->_redirect( $invoice );
		}
		
		/* Display */
		Output::i()->output = $form;
	}
	
	/**
	 * PO Number
	 *
	 * @return	void
	 */
	public function poNumber() : void
	{
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/4', 404, '' );
		}
		
		$form = new Form;
		$form->add( new Text( 'invoice_po_number', $invoice->po, FALSE, array( 'maxLength' => 255 ) ) );
		if ( $values = $form->values() )
		{
			$invoice->po = $values['invoice_po_number'];
			$invoice->save();
			$this->_redirect( $invoice );
		}
		Output::i()->output = $form;
	}
	
	/**
	 * Notes
	 *
	 * @return	void
	 */
	public function notes() : void
	{
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/5', 404, '' );
		}
		
		$form = new Form;
		$form->add( new TextArea( 'invoice_notes', $invoice->notes ) );
		if ( $values = $form->values() )
		{
			$invoice->notes = $values['invoice_notes'];
			$invoice->save();
			$this->_redirect( $invoice );
		}
		Output::i()->output = $form;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_delete' );
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		/* Load Transaction */
		try
		{
			$invoice = Invoice::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/E', 404, '' );
		}
				
		/* Log it */
		$invoice->member->log( 'invoice', array(
			'type'		=> 'delete',
			'id'		=> $invoice->id,
			'title'		=> $invoice->title
		) );
		
		/* Delete */
		$invoice->delete();
		
		/* Redirect */
		Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=invoices')->getSafeUrlFromFilters() );
	}
	
	/**
	 * Generate
	 *
	 * @return	void
	 */
	public function generate() : void
	{
		/* Init */
		Dispatcher::i()->checkAcpPermission( 'invoices_add' );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_store.js', 'nexus', 'admin' ) );
		$url = Url::internal("app=nexus&module=payments&controller=invoices&do=generate");
		if ( isset( Request::i()->member ) )
		{
			$url = $url->setQueryString( 'member', Request::i()->member );
		}
		
		/* Are we editing an invoice? */
		$invoice = NULL;
		if ( isset( Request::i()->id ) )
		{
			try
			{
				$invoice = Invoice::load( Request::i()->id );
				
				if ( $invoice->status === Invoice::STATUS_PAID )
				{
					Output::i()->error( 'cannot_edit_paid_invoice', '2X190/F', 403, '' );
				}
				
				$url = $url->setQueryString( 'id', Request::i()->id );
			}
			catch ( OutOfRangeException ) { }
		}
		Output::i()->title = $invoice ? Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $invoice->id ) ) ) : Member::loggedIn()->language()->addToStack('generate_invoice');
			
		/* Select Customer */	
		$steps = array();
		if ( !isset( Request::i()->member ) and ( !$invoice or !$invoice->member->member_id ) )
		{
			$steps['invoice_generate_member'] = function( $data )
			{
				$form = new Form('customer', 'continue');
				$form->add( new Form\Member( 'invoice_generate_member', NULL, TRUE ) );
				if ( $values = $form->values() )
				{
					return array( 'member' => $values['invoice_generate_member']->member_id );
				}
				return $form;
			};
		}
		
		/* Select Addresses */
		$steps['invoice_generate_settings'] = function( $data ) use ( $invoice )
		{
			$customer = $invoice ? $invoice->member : ( Customer::load( isset( Request::i()->member ) ? Request::i()->member : $data['member'] ) );
			
			$form = new Form('settings', 'continue');
			
			$form->addHeader( 'invoice_settings' );
			$currencies = Money::currencies();
			if ( count( $currencies ) > 1 )
			{
				$form->add( new Radio( 'currency', $invoice ? $invoice->currency : $customer->defaultCurrency(), TRUE, array( 'options' => array_combine( $currencies, $currencies ) ) ) );
			}
			$statusOptions = array();
			$statusOptions[ Invoice::STATUS_PAID ] = 'invoice_status_paid';
			$statusOptions[ Invoice::STATUS_PENDING ] = 'invoice_status_pend';
			if ( Settings::i()->cm_invoice_expireafter )
			{
				$statusOptions[ Invoice::STATUS_EXPIRED ] = 'invoice_status_expd';
			}
			$statusOptions[ Invoice::STATUS_CANCELED ] = 'invoice_status_canc';
			$form->add( new Radio( 'invoice_status', $invoice ? $invoice->status : Invoice::STATUS_PENDING, TRUE, array( 'options' => $statusOptions ) ) );
			$form->add( new Text( 'invoice_title', $invoice?->title, FALSE ) );
			$form->add( new Text( 'invoice_po_number', $invoice?->po_number, FALSE, array( 'maxLength' => 255 ) ) );
			$form->add( new TextArea( 'invoice_notes', $invoice?->notes) );
			
			$needTaxStatus = NULL;
			foreach ( Tax::roots() as $tax )
			{
				if ( $tax->type === 'eu' )
				{
					$needTaxStatus = 'eu';
					break;
				}
				if ( $tax->type === 'business' )
				{
					$needTaxStatus = 'business';
				}
			}
			$addressHelperClass = $needTaxStatus ? 'IPS\nexus\Form\BusinessAddress' : 'IPS\Helpers\Form\Address';
			$addressHelperOptions = ( $needTaxStatus === 'eu' ) ? array( 'vat' => TRUE ) : array();
			
			$form->addHeader( 'invoice_generate_addresses' );
			$addresses = Db::i()->select( '*', 'nexus_customer_addresses', array( '`member`=?', $customer->member_id ) );
			if ( count( $addresses ) )
			{
				$primaryBillingAddressId = NULL;
				$chosenBillingAddressId = 0;
				$options = array();
				foreach ( new ActiveRecordIterator( $addresses, 'IPS\nexus\Customer\Address' ) as $address )
				{
					$options[ $address->id ] = $address->address->toString('<br>') . ( ( isset( $address->address->business ) and $address->address->business and isset( $address->address->vat ) and $address->address->vat ) ? ( '<br>' . Member::loggedIn()->language()->addToStack('cm_checkout_vat_number') . ': ' . Theme::i()->getTemplate( 'global', 'nexus' )->vatNumber( $address->address->vat ) ) : '' );
					if ( $address->primary_billing )
					{
						$primaryBillingAddressId = $address->id;
					}
					
					if ( $invoice and $invoice->billaddress and $invoice->billaddress == $address->address )
					{
						$chosenBillingAddressId = $address->id;
					}
				}
				$options[0] = Member::loggedIn()->language()->addToStack('other');
				
				$form->add( new Radio( 'billing_address', ( $invoice and $invoice->billaddress ) ? $chosenBillingAddressId : $primaryBillingAddressId, TRUE, array( 'options' => $options, 'toggles' => array( 0 => array( 'new_billing_address' ) ), 'parse' => 'raw' ) ) );
				$newAddress = new $addressHelperClass( 'new_billing_address', $invoice?->billaddress, FALSE, $addressHelperOptions, NULL, NULL, NULL, 'new_billing_address' );
				$newAddress->label = ' ';
				$form->add( $newAddress );
			}
			else
			{
				$form->add( new $addressHelperClass( 'new_billing_address', $invoice?->billaddress, FALSE, $addressHelperOptions, function($val ) {
					if ( Request::i()->invoice_status === Invoice::STATUS_PAID and !$val )
					{
						throw new DomainException('billing_address_req');
					}
				} ) );
			}
			
			if ( $values = $form->values() )
			{
				if ( count( $addresses ) and $values['billing_address'] )
				{
					$data['billaddress'] = Address::load( $values['billing_address'] )->address;
				}
				else
				{
					if( $values['new_billing_address'] === NULL OR empty( $values['new_billing_address']->addressLines ) or !$values['new_billing_address']->city or !$values['new_billing_address']->country or ( !$values['new_billing_address']->region and array_key_exists( $values['new_billing_address']->country, GeoLocation::$states ) ) or !$values['new_billing_address']->postalCode )
					{
						$data['billaddress'] = NULL;
					}
					else
					{
						$data['billaddress'] = $values['new_billing_address'];
					}
				}

				$data['currency'] = $values['currency'] ?? $customer->defaultCurrency();
				$data['status'] = $values['invoice_status'];
				$data['title'] = $values['invoice_title'];
				$data['po_number'] = $values['invoice_po_number'];
				$data['notes'] = $values['invoice_notes'];
				
				if ( $invoice )
				{
					$data['items'] = $invoice->items;
				}
				
				return $data;
			}
			
			return $form;
		};
		
		/* Add Items */
		$steps['invoice_generate_items'] = function( $data ) use ( $url, $invoice )
		{
			if ( !$invoice )
			{
				$invoice = new Invoice;
				$invoice->member = Customer::load( isset( Request::i()->member ) ? Request::i()->member : $data['member'] );
			}
			$invoice->currency = $data['currency'];
			if ( $data['billaddress'] )
			{
				$invoice->billaddress = $data['billaddress'];
			}
			$invoice->items = isset( $data['items'] ) ? json_encode( $data['items'] ) : json_encode( array() );			
																		
			if ( isset( Request::i()->continue ) )
			{
				$invoice->recalculateTotal();

				if ( $data['title'] )
				{
					$invoice->title = $data['title'];
				}
				if ( $data['po_number'] )
				{
					$invoice->po = $data['po_number'];
				}
				if ( $data['notes'] )
				{
					$invoice->notes = $data['notes'];
				}
				
				if ( $data['status'] === Invoice::STATUS_PAID )
				{
					$invoice->status = Invoice::STATUS_PENDING;
					$invoice->save();
					
					$invoice->member->log( 'invoice', array(
						'type'	=> 'status',
						'new'	=> Invoice::STATUS_PAID,
						'id'	=> $invoice->id,
						'title' => $invoice->title
					) );
					$invoice->markPaid();
				}
				else
				{
					$invoice->status = $data['status'];
					$invoice->save();
				}

				/* Now that we have an ID, do we need to update purchase rows? */
				if ( isset( $data['update_purchase_invoice_pending'] ) and is_array( $data['update_purchase_invoice_pending'] ) )
				{
					foreach( $data['update_purchase_invoice_pending'] as $id )
					{
						try
						{
							$purchase = Purchase::load( $id );
							$purchase->invoice_pending = $invoice;
							$purchase->save();
						}
						catch( Exception ) {}
					}
				}

				if ( $data['status'] !== Invoice::STATUS_CANCELED )
				{
					$invoice->sendNotification();
				}

				Output::i()->redirect( $invoice->acpUrl() );
			}
			elseif ( isset( Request::i()->remove ) )
			{
				unset( $data['items'][ Request::i()->remove ] );
				$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;
				Output::i()->redirect( $url );
			}
			elseif ( isset( Request::i()->addRenewal ) )
			{
				$form = new Form;
				$form->add( new Node( 'purchases_to_renew', NULL, TRUE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => $invoice->member, 'multiple' => TRUE, 'permissionCheck' => function( $purchase )
				{
					return (bool) $purchase->renewals;
				} ) ) );
				$form->add( new Number( 'renew_cycles', 1, TRUE, array( 'min' => 1 ) ) );
				if ( $values = $form->values() )
				{
					foreach ( $values['purchases_to_renew'] as $purchase )
					{
						$invoice->addItem( Renewal::create( $purchase, $values['renew_cycles'] ) );
						$data['update_purchase_invoice_pending'][] = $purchase->id;
					}
					$data['items'] = $invoice->items->getArrayCopy();
					$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;
					Output::i()->redirect( $url );
				}
				return $form;
			}
						
			$itemTypes = Application::allExtensions( 'nexus', 'Item', TRUE, NULL, NULL, FALSE );
			if ( isset( Request::i()->add ) and isset( $itemTypes[ Request::i()->add ] ) )
			{
				$class = $itemTypes[ Request::i()->add ];
				
				$formUrl = $url->setQueryString( 'add', Request::i()->add );
				$form = new Form( 'add', 'invoice_add_item', $formUrl );
				if ( method_exists( $class, 'formSecondStep' ) )
				{
					$form->ajaxOutput = TRUE;
				}
				$class::form( $form, $invoice );
				if ( $values = $form->values() or ( method_exists( $class, 'formSecondStep' ) and isset( Request::i()->firstStep ) ) )
				{
					if ( method_exists( $class, 'formSecondStep' ) )
					{
						$firstStepValues = isset( Request::i()->firstStep ) ? urldecode( Request::i()->firstStep ) : json_encode( array_map( function( $val )
						{
							return ( $val instanceof Model ) ? $val->_id : $val;
						}, $values ) );						
						$secondStepForm = new Form( 'add2', 'invoice_add_item', $formUrl->setQueryString( 'firstStep', $firstStepValues ) );
						$secondStepForm->ajaxOutput = TRUE;
						$secondStepForm->hiddenValues['firstStep'] = $firstStepValues;
						if ( $class::formSecondStep( json_decode( $firstStepValues, TRUE ), $secondStepForm, $invoice ) )
						{
							if ( $secondStepValues = $secondStepForm->values() )
							{
								$item = $class::createFromForm( $secondStepValues, $invoice );
								if( is_array( $item ) )
								{
									foreach ( $item as $i )
									{
										$invoice->addItem( $i );
									}
								}
								else
								{
									$invoice->addItem( $item );
								}
								$data['items'] = $invoice->items->getArrayCopy();
								$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;
								Output::i()->redirect( $url );
							}
							
							if ( Request::i()->isAjax() )
							{
								Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $secondStepForm ) );
							}
							else
							{
								Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, $secondStepForm, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
							}
						}
					}

					$item = $class::createFromForm( $values, $invoice );
					if( is_array( $item ) )
					{
						foreach ( $item as $i )
						{
							$invoice->addItem( $i );
						}
					}
					else
					{
						$invoice->addItem( $item );
					}

					$data['items'] = $invoice->items->getArrayCopy();
					$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;					
					Output::i()->redirect( $url );
				}
				
				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $form ) );
				}
				else
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, $form, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
				}
			}
			
			return Theme::i()->getTemplate('invoices')->generate( $invoice->summary(), $itemTypes, $url );
			
		};
		
		/* Display */
		Output::i()->output = new Wizard( $steps, $url, ( !isset( Request::i()->add ) and !isset( Request::i()->addRenewal ) ) );
	}
	
	/**
	 * Product Tree (AJAX)
	 *
	 * @return	void
	 */
	public function productTree() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_add' );
		
		$output = '';
		foreach( Group::load( Request::i()->id )->children() as $child )
		{
			if ( $child instanceof Group )
			{
				$output .= Theme::i()->getTemplate('invoices')->packageSelectorGroup( $child );
			}
			else
			{
				$output .= Theme::i()->getTemplate('invoices')->packageSelectorProduct( $child );
			}
		}
		
		Output::i()->json( $output );
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_settings' );

		$form = new Form;
		$form->addheader('invoice_flow');
		$form->addMessage('invoice_flow_visualise');
		$form->add( new Interval( 'cm_invoice_generate', Settings::i()->cm_invoice_generate, FALSE, array( 'valueAs' => Interval::HOURS, 'min' => 1 ), NULL, NULL, Member::loggedIn()->language()->addToStack('cm_invoice_generate_suffix') ) );
		$form->add( new Interval( 'cm_invoice_warning', Settings::i()->cm_invoice_warning, FALSE, array( 'valueAs' => Interval::HOURS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, NULL, Member::loggedIn()->language()->addToStack('cm_invoice_warning_suffix') ) );
		$form->add( new Interval( 'cm_invoice_expireafter', Settings::i()->cm_invoice_expireafter, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0 ), NULL, NULL, NULL ) );
		$form->addHeader('invoice_layout');
		$form->addMessage('invoice_layout_blurb');
		$form->add( new Editor( 'nexus_invoice_header', Settings::i()->nexus_invoice_header, FALSE, array( 'app' => 'nexus', 'key' => 'Admin', 'autoSaveKey'	=> 'nexus-invoice-header', 'attachIds' => array( NULL, NULL, 'invoice-header' ), 'minimize' => 'nexus_invoice_header_placeholder'  ) ) );
		$form->add( new Editor( 'nexus_invoice_footer', Settings::i()->nexus_invoice_footer, FALSE, array( 'app' => 'nexus', 'key' => 'Admin', 'autoSaveKey'	=> 'nexus-invoice-footer', 'attachIds' => array( NULL, NULL, 'invoice-footer' ), 'minimize' => 'nexus_invoice_footer_placeholder'  ) ) );
		
		if ( $values = $form->values() )
		{
			Db::i()->update( 'core_tasks', array( 'enabled' => (int) (bool) $values['cm_invoice_expireafter'] ), "`key`='expireInvoices'" );
			
			$form->saveAsSettings();
			Session::i()->log( 'acplogs__invoice_settings' );
			Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=invoices&do=settings'), 'saved' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('invoice_settings');
		Output::i()->output = $form;
	}
	
	/**
	 * Track
	 *
	 * @return	void
	 */
	protected function track() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$invoice = Invoice::load( Request::i()->id );
			
			if ( Request::i()->track )
			{
				Db::i()->insert( 'nexus_invoice_tracker', array(
					'member_id'		=> Member::loggedIn()->member_id,
					'invoice_id'		=> $invoice->id
				), TRUE );
			}
			else
			{
				Db::i()->delete( 'nexus_invoice_tracker', array( 'member_id=? AND invoice_id=?', Member::loggedIn()->member_id, $invoice->id ) );
			}
			
			$this->_redirect( $invoice );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/G', 404, '' );
		}
	}
	
	/**
	 * Redirect
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return	void
	 */
	protected function _redirect( Invoice $invoice ) : void
	{
		if ( isset( Request::i()->r ) )
		{
			switch ( mb_substr( Request::i()->r, 0, 1 ) )
			{
				case 'v':
					Output::i()->redirect( $invoice->acpUrl() );
					break;
				
				case 'p':
					try
					{
						Output::i()->redirect( Purchase::load( mb_substr( Request::i()->r, 2 ) )->acpUrl() );
						break;
					}
					catch ( OutOfRangeException ) {}
				
				case 'c':
					Output::i()->redirect( $invoice->member->acpUrl() );
					break;
				
				case 't':
					Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=invoices') );
					break;
					
			}
		}
		
		Output::i()->redirect( $invoice->acpUrl() );
	}
	
	/**
	 * Wait for the webhook for a transaction to come through before it has been created
	 *
	 * @return	void
	 */
	public function webhook() : void
	{
		/* Load the invoice */
		try
		{
			$invoice = Invoice::load( Request::i()->invoice );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X190/J', 404, '' );
		}
		
		/* Have we decided to give up waiting and just show a pending screen? */
		if ( isset( Request::i()->pending ) )
		{
			Output::i()->error( 'webhook_not_received', '2X190/K', 404 );
		}
		
		/* Nope - show a redirector */
		Output::i()->output = $this->_webhookRedirector( $invoice, isset( Request::i()->exclude ) ? explode( ',', Request::i()->exclude ) : array() );
	}
	
	/**
	 * Get a redirector that points to do=webhook
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @param array $exclude	Transaction IDs to exclude
	 * @return	MultipleRedirect
	 */
	protected function _webhookRedirector( Invoice $invoice, array $exclude ): MultipleRedirect
	{
		$self = $this;
		return new MultipleRedirect(
			Url::internal('app=nexus&module=payments&controller=invoices')->setQueryString( array( 'do' => 'webhook', 'invoice' => $invoice->id, 'exclude' => implode( ',', $exclude ) ) ),
			function( $data ) use ( $self, $invoice, $exclude ) {	
				if ( $data === NULL )
				{
					return array( time(), Member::loggedIn()->language()->addToStack('processing_the_payment') );
				}
				else
				{
					/* Do we have any transactions yet? */
					foreach ( $invoice->transactions( array( Transaction::STATUS_PAID, Transaction::STATUS_HELD, Transaction::STATUS_REFUSED ), $exclude ? array( array( Db::i()->in( 't_id', $exclude, TRUE ) ) ) : array() ) as $transaction )
					{
						$self->_redirect( $invoice );
					}
					
					$giveUpTime = ( $data + 60 );
					if ( time() > $giveUpTime )
					{
						return NULL;
					}
					else
					{
						sleep(5);
						return array( $data, Member::loggedIn()->language()->addToStack('processing_the_payment') );
					}
				}
			},
			function() use( $invoice ) {
				Output::i()->redirect( Url::internal('app=nexus&module=payments&controller=invoices')->setQueryString( array( 'do' => 'webhook', 'invoice' => $invoice->id, 'pending' => 1 ) ) );
			}
		);
	}
}