<?php
/**
 * @brief		PayPal Gateway
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		07 Mar 2014
 */

use IPS\Db;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member\Device;
use IPS\nexus\Gateway\PayPal\BillingAgreement;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Session\Front;
use IPS\Settings;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';
Front::i();

/* Load Transaction */
try
{
	$transaction = Transaction::load( Request::i()->nexusTransactionId );
	
	if ( $transaction->status !== Transaction::STATUS_PENDING )
	{
		throw new OutOfRangeException;
	}
}
catch (OutOfRangeException )
{
	Output::i()->redirect( Url::internal( "app=nexus&module=checkout&controller=checkout&do=transaction&id=&t=" . Request::i()->nexusTransactionId, 'front', 'nexus_checkout' ) );
}

$fraudCheck = function( $transaction, $refuseExecute=TRUE ) {
	/* Check fraud rules */
	$maxMind = NULL;
	if ( Settings::i()->maxmind_key and ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways == '*' or in_array( $transaction->method->id, explode( ',', Settings::i()->maxmind_gateways ) ) ) )
	{
		$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
		$maxMind->setTransaction( $transaction );
		$maxMind->setTransactionType( 'paypal' );
	}
	$fraudResult = $transaction->runFraudCheck( $maxMind );
	if ( $refuseExecute AND $fraudResult === Transaction::STATUS_REFUSED )
	{
		$transaction->executeFraudAction( $fraudResult, FALSE );
		$transaction->sendNotification();
		Output::i()->redirect( $transaction->url() );
	}

	return $fraudResult;
};

/* Process */
try
{
	/* Subscription */
	if ( isset( Request::i()->subscription ) )
	{
		/* Get details */
		$response = $transaction->method->api( "billing/subscriptions/" . Request::i()->subscription_id, NULL, 'get' );
		
		/* Create Billing Agreement */
		try
		{
			$billingAgreement = BillingAgreement::constructFromData( Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_gw_id=? AND ba_method=?', $response['id'], $transaction->method->id ), flags: Db::SELECT_FROM_WRITE_SERVER )->first() );
		}
		catch (UnderflowException )
		{
			$billingAgreement = new BillingAgreement;
			$billingAgreement->gw_id = $response['id'];
			$billingAgreement->method = $transaction->method;
			$billingAgreement->member = $transaction->member;
			$billingAgreement->started = \IPS\DateTime::create();
			$billingAgreement->next_cycle = ( new \IPS\DateTime( $response['billing_info']['next_billing_time'] ) );
			$billingAgreement->save();
		}
		$transaction->billing_agreement = $billingAgreement;
		$transaction->save();

		/* Check Fraud */
		$fraudResult = $fraudCheck( $transaction, FALSE );

		/* Just save that we're waiting for the webhook, but also try again in a few minutes because PayPal is flakey */
		$transaction->status = Transaction::STATUS_GATEWAY_PENDING;
		$transaction->save();
		$transaction->sendNotification();
		Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='gatewayPending'" );
		Output::i()->redirect( $transaction->url() );
	}
	
	/* Billing Agreement (DEPRECATED) */
	elseif ( isset( Request::i()->billingAgreement ) )
	{
		/* Execute */
		$response = $transaction->method->api( "payments/billing-agreements/" . Request::i()->token . "/agreement-execute" );
		$agreementId = $response['id'];
		if ( isset( $response['payer'] ) and isset( $response['payer']['status'] ) )
		{
			$extra = $transaction->extra;
			$extra['verified'] = $response['payer']['status'];
			$transaction->extra = $extra;
		}
				
		/* Create Billing Agreement */
		$billingAgreement = new BillingAgreement;
		$billingAgreement->gw_id = $agreementId;
		$billingAgreement->method = $transaction->method;
		$billingAgreement->member = $transaction->member;
		$billingAgreement->started = \IPS\DateTime::create();
		$billingAgreement->next_cycle = \IPS\DateTime::create()->add( new DateInterval( 'P' . $response['plan']['payment_definitions'][0]['frequency_interval'] . mb_substr( $response['plan']['payment_definitions'][0]['frequency'], 0, 1 ) ) );
		$billingAgreement->save();
		$transaction->billing_agreement = $billingAgreement;
		$transaction->save();

		/* Get the initial setup transaction if possible */
		$haveInitialTransaction = FALSE;
		$transactions = $transaction->method->api( "payments/billing-agreements/{$billingAgreement->gw_id}/transactions?start_date=" . date( 'Y-m-d', time() - 86400 ) . '&end_date=' . date( 'Y-m-d' ), NULL, 'get' );
		foreach ( $transactions['agreement_transaction_list'] as $t )
		{
			if ( $t['status'] == 'Completed' )
			{
				$transaction->gw_id = $t['transaction_id'];
				$transaction->save();

				/* Check Fraud Actions */
				$fraudResult = $fraudCheck( $transaction );
				if ( $fraudResult and $fraudResult !== Transaction::STATUS_PAID )
				{
					$transaction->executeFraudAction( $fraudResult, TRUE );
				}
				else
				{
					$transaction->member->log( 'transaction', array(
						'type'			=> 'paid',
						'status'		=> $transaction::STATUS_PAID,
						'id'			=> $transaction->id,
						'invoice_id'	=> $transaction->invoice->id,
						'invoice_title'	=> $transaction->invoice->title,
					) );

					$memberJustCreated = $transaction->approve();
					if ( $memberJustCreated )
					{
						Session::i()->setMember( $memberJustCreated );
						Device::loadOrCreate( $memberJustCreated, FALSE )->updateAfterAuthentication( NULL );
					}
				}

				$transaction->sendNotification();
				Output::i()->redirect( $transaction->url() );
			}
		}
				
		/* Just save that we're waiting for the webhook */
		$transaction->status = Transaction::STATUS_GATEWAY_PENDING;
		$transaction->save();
		$transaction->sendNotification();
		Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='gatewayPending'" );
		Output::i()->redirect( $transaction->url() );
	}
	
	/* Normal */
	else
	{
		$response = $transaction->method->api( "checkout/orders/{$transaction->gw_id}/authorize", array(
			'payer_id'	=> Request::i()->PayerID,
		), 'post', TRUE, NULL, md5( $transaction->invoice->checkoutUrl() . ';' . $transaction->id . ';' . $transaction->gw_id ), 2 );
		$transaction->gw_id = $response['purchase_units'][0]['payments']['authorizations'][0]['id']; // Was previously a payment ID. This sets it to the actual transaction ID for the authorization. At capture, it will be updated again to the capture transaction ID
		$transaction->auth = \IPS\DateTime::ts( strtotime( $response['purchase_units'][0]['payments']['authorizations'][0]['expiration_time'] ) );
		if ( isset( $response['payer'] ) and isset( $response['payer']['status'] ) )
		{
			$extra = $transaction->extra;
			$extra['verified'] = $response['payer']['status'];
			$transaction->extra = $extra;
		}
		$transaction->save();

		/* Fraud Check */
		$fraudResult = $fraudCheck( $transaction, FALSE );
	}
	
	/* Capture */
	if ( $fraudResult )
	{
		$transaction->executeFraudAction( $fraudResult, TRUE );
	}
	if ( !$fraudResult or $fraudResult === Transaction::STATUS_PAID )
	{
		$memberJustCreated = $transaction->captureAndApprove();
		if ( $memberJustCreated )
		{
			Session::i()->setMember( $memberJustCreated );
			Device::loadOrCreate( $memberJustCreated, FALSE )->updateAfterAuthentication( NULL );
		}
	}
	$transaction->sendNotification();
	
	/* Redirect */
	Output::i()->redirect( $transaction->url() );
}
catch (Exception $e )
{
	Log::log( $e, 'paypal' );
	Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $e->getMessage() ) ) );
}