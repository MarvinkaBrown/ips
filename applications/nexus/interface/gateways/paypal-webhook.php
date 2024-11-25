<?php
/**
 * @brief		PayPal Webhook Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Oct 2019
 */

use IPS\core\AdminNotification;
use IPS\Db;
use IPS\nexus\extensions\nexus\Item\MiscellaneousCharge;
use IPS\nexus\Gateway;
use IPS\nexus\Gateway\PayPal;
use IPS\nexus\Gateway\PayPal\BillingAgreement;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Session\Front;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';
Front::i();

Output::setCacheTime( false );

/**
 * PayPal Webhook Handler
 */
class paypalWebhookHandler
{
	/**
	 * @brief	Raw Webhook Data (as a string)
	 */
	private mixed $body;
	
	/**
	 * @brief	Parsed Webhook Data (as an array)
	 */
	private array $data;
	
	/**
	 * @brief	The payment method
	 */
	private Gateway $method;
		
	/**
	 * Constructor
	 *
	 * @param	string	$body	The raw body posted to this script
	 * @return	void
	 */
	public function __construct( string $body )
	{
		$this->body = $body;
		$this->data = json_decode( $body, TRUE );
	}
	
	/**
	 * Sale completed
	 *
	 * @return	string
	 */
	public function saleCompleted() : string
	{
		/* Is this a billing agreement payment? */
		if ( isset( $this->data['resource'] ) and isset( $this->data['resource']['id'] ) and isset( $this->data['resource']['billing_agreement_id'] ) )
		{
			/* Have we seen this transaction before? */
			try
			{
				$transaction = Db::i()->select( '*', 'nexus_transactions', array( 't_gw_id=? AND t_method=?', $this->data['resource']['id'], $this->method->id ), flags: Db::SELECT_FROM_WRITE_SERVER )->first();
				return 'ALREADY_PROCESSED';
			}
			catch (UnderflowException ) {}
						
			/* Get the billing agreement */
			try
			{
				$billingAgreement = BillingAgreement::constructFromData( Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_gw_id=? AND ba_method=?', $this->data['resource']['billing_agreement_id'], $this->method->id ) )->first() );
			}
			catch (UnderflowException )
			{
				throw new DomainException('UNKNOWN_BILLING_AGREEMENT');
			}
			
			/* Do it */
			try
			{
				$transaction = Transaction::constructFromData( Db::i()->select( '*', 'nexus_transactions', array( 't_billing_agreement=? AND t_method=? AND t_status=?', $billingAgreement->id, $this->method->id, Transaction::STATUS_GATEWAY_PENDING ) )->first() );
				$transaction->gw_id = $this->data['resource']['id'];
				$transaction->save();
				
				return $this->_processInitialBillingAgreementTransaction( $transaction );
			}
			catch (UnderflowException )
			{
				$transaction = new Transaction;
				$transaction->member = $billingAgreement->member;
				$transaction->method = $this->method;
				$transaction->amount = new Money( $this->data['resource']['amount']['total'], $this->data['resource']['amount']['currency'] );
				$transaction->date = new \IPS\DateTime( $this->data['resource']['create_time'] );
				$transaction->extra = array( 'automatic' => TRUE );
				$transaction->gw_id = $this->data['resource']['id'];
				$transaction->billing_agreement = $billingAgreement;
				
				return $this->_processRecurringBillingAgreementTransaction( $transaction );
			}
		}
		else
		{
			return 'NOT_BILLING_AGREEMENT';
		}
	}
	
	/**
	 * Process the initial transaction for a billing agreement
	 *
	 * @param	Transaction	$transaction	The transaction
	 * @return	string
	 */
	protected function _processInitialBillingAgreementTransaction( Transaction $transaction ) : string
	{
		/* Take any fraud action */
		$fraudResult = $transaction->fraud_blocked?->action;
		if ( $fraudResult )
		{
			$transaction->executeFraudAction( $fraudResult, TRUE );
		}
		if ( !$fraudResult or $fraudResult === Transaction::STATUS_PAID )
		{
			$transaction->approve();
		}
		
		/* Let the customer know */
		$transaction->sendNotification();
		
		/* Return */
		return 'OK-INITIAL';
	}
	
	/**
	 * Process a reccuring (i.e. not the intitial) transaction for a billing agreement
	 *
	 * @param	Transaction	$transaction	The transaction
	 * @return	string
	 */
	protected function _processRecurringBillingAgreementTransaction( Transaction $transaction ) : string
	{
		/* Get purchases */
		$purchases = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( 'ps_billing_agreement=?', $transaction->billing_agreement->id ) ), 'IPS\nexus\Purchase' );
		
		/* Generate an invoice */
		$invoice = new Invoice;
		$invoice->system = TRUE;
		$invoice->date = $transaction->date;
		$invoice->currency = $transaction->amount->currency;
		$invoice->member = $transaction->billing_agreement->member;
		$invoice->billaddress = $transaction->billing_agreement->member->primaryBillingAddress();
		foreach ( $purchases as $purchase )
		{
			/* @var Purchase $purchase */
			if( !$purchase->renewals )
			{
				// Renewals have been cancelled for this purchase since PayPal tried to bill for it.
				$amount = new Money( $purchase->renewal_price, $transaction->amount->currency );
				$invoice->addItem( new MiscellaneousCharge( sprintf( $purchase->member->language()->get('renew_payment_no_ba'), $purchase->name ), $amount ) );
				continue;
			}

			$invoice->addItem( Renewal::create( $purchase ) );
		}
		$invoice->save();
		
		/* Assign the transaction to it */
		$transaction->invoice = $invoice;
		$transaction->save();
		$transaction->approve();
		$invoice->status = $transaction->invoice->status;
		
		/* Log */
		$invoice->member->log( 'transaction', array(
			'type' => 'paid',
			'status' => Transaction::STATUS_PAID,
			'id' => $transaction->id,
			'invoice_id' => $invoice->id,
			'invoice_title' => $invoice->title,
			'automatic' => TRUE,
		), FALSE );
		
		/* Update the purchase */
		if ( $invoice->status !== $invoice::STATUS_PAID )
		{
			foreach ( $purchases as $purchase )
			{
				$purchase->invoice_pending = $invoice;
				$purchase->save();
			}
		}
	
		/* Send notification */
		$invoice->sendNotification();
		
		/* Update billing agreement */
		if ( $invoice->status === $invoice::STATUS_PAID )
		{
			$transaction->billing_agreement->next_cycle = $transaction->billing_agreement->nextPaymentDate();
		}
		else
		{
			$transaction->billing_agreement->next_cycle = NULL;
		}
		$transaction->billing_agreement->save();
		
		/* Return */
		return 'OK-RECURRING';
	}
	
	/**
	 * Run
	 *
	 * @return	string
	 */
	public function run() : string
	{
		/* Do it */
		if ( isset( $this->data['event_type'] ) )
		{
			switch ( $this->data['event_type'] )
			{
				/* Sale completed: Used for automatic billing agreement purchases */
				case 'PAYMENT.SALE.COMPLETED':
					/* Validate it */
					foreach ( Gateway::roots() as $method )
					{
						if ( $method instanceof PayPal )
						{
							try
							{
								$settings = json_decode( $method->settings, TRUE );

								/* If billing agreements are not enabled, skip. We may have more than one PayPal gateway set up, and the first one may not be using billing agreements but the next one could be. */
								if ( !isset( $settings['billing_agreements'] ) OR !$settings['billing_agreements'] )
								{
									continue;
								}
								/* If the webhook ID does not exist... */
								if( empty( $settings['webhook_id'] ) )
								{
									/* First, try to set it automatically if we can */
									try
									{
										$newSettings		= $method->testSettings( $settings );
										$method->settings	= json_encode( $newSettings );
										$method->save();
									}
									/* But if that fails, send an AdminCP notification letting the admin know they need to fix it. */
									catch (Exception )
									{
										AdminNotification::send( 'nexus', 'ConfigurationError', "pm{$method->id}", FALSE );
									}
								}
								
								if( isset( $this->data['event_type'] ) AND $this->data['event_type'] == 'PAYMENT.SALE.COMPLETED' AND 
									isset( $this->data['resource'] ) AND isset( $this->data['resource']['id'] ) )
								{
									$response = $method->api( 'payments/sale/' . $this->data['resource']['id'], NULL, 'get' );

									if ( isset( $response['state'] ) and mb_strtoupper( $response['state'] ) === 'COMPLETED' )
									{
										$this->method = $method;
										
										return $this->saleCompleted();
									}
								}
							}
							catch (Exception )
							{
								// Do nothing - try the next one
							}
						}
					}
					
					throw new Exception('COULD_NOT_VALIDATE');
					
				// PAYMENTS.PAYMENT.CREATED, PAYMENT.AUTHORIZATION.CREATED and then PAYMENT.CAPTURE.COMPLETED are actually buying something (not a billing agreement payment)
				
				/* Everything else: unneeded */
				default:
					return 'UNNEEDED_TYPE';
			}
		}
		else
		{
			throw new Exception('INVALID_HOOK_DATA');
		}
	}
}

$class = new paypalWebhookHandler( trim( @file_get_contents('php://input') ) );
try
{
	$headers = array();
	foreach ( $_SERVER as $k => $v )
	{
		if ( mb_substr( $k, 0, 12 ) === 'HTTP_PAYPAL_' )
		{
			$headers[ str_replace( '_', '-', mb_substr( $k, 5 ) ) ] = $v;
		}
	}
	$response = $class->run();
	Output::i()->sendOutput( $response, 200, 'text/plain' );
}
catch (Exception $e)
{
	Output::i()->sendOutput( $e->getMessage(), 500, 'text/plain' );
}