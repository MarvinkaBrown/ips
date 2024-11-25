<?php
/**
 * @brief		Stripe Apple Pay Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		20 Jul 2017
 */

use IPS\Log;
use IPS\Member;
use IPS\Member\Device;
use IPS\nexus\Gateway;
use IPS\nexus\Gateway\Stripe;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Session\Front;
use IPS\Settings;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';
Front::i();

Output::setCacheTime( false );

/* Get the invoice */
try
{
	$invoice = Invoice::load( Request::i()->invoice );
	
	if ( !$invoice->canView() )
	{
		throw new OutOfRangeException;
	}
}
catch (OutOfRangeException )
{
	Output::i()->sendOutput( json_encode( array( 'success' => 0 ) ), 500, 'application/json' );
}

/* Get the gateway */
try
{
	$gateway = Gateway::load( Request::i()->gateway );
	if ( !( $gateway instanceof Stripe ) )
	{
		throw new OutOfRangeException;
	}
}
catch (OutOfRangeException )
{
	Output::i()->sendOutput( json_encode( array( 'success' => 0 ) ), 500, 'application/json' );
}

/* Create a transaction */
$transaction = new Transaction;
$transaction->member = Member::loggedIn();
$transaction->invoice = $invoice;
$transaction->amount = new Money( Request::i()->amount, mb_strtoupper( Request::i()->currency ) );
$transaction->ip = Request::i()->ipAddress();
$transaction->method = $gateway;

/* Create a MaxMind request */
$maxMind = NULL;
if ( Settings::i()->maxmind_key and ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways == '*' or in_array( $transaction->method->id, explode( ',', Settings::i()->maxmind_gateways ) ) ) )
{
	$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
	$maxMind->setTransaction( $transaction );
}

/* Authorize and Capture */			
try
{
	/* Authorize */
	$transaction->auth = $gateway->auth( $transaction, array( "{$gateway->id}_card" => Request::i()->token ), $maxMind );
	
	/* Check Fraud Rules and capture */
	$memberJustCreated = $transaction->checkFraudRulesAndCapture( $maxMind );
	if ( $memberJustCreated )
	{
		Session::i()->setMember( $memberJustCreated );
		Device::loadOrCreate( $memberJustCreated, FALSE )->updateAfterAuthentication( NULL );
	}
	
}
catch (Exception $e )
{
	Log::log( $e, 'applepay' );
	Output::i()->sendOutput( json_encode( array( 'success' => 0 ) ), 500, 'application/json' );
}

/* Send email receipt */
$transaction->sendNotification();

/* Return */
Output::i()->sendOutput( json_encode( array( 'success' => 1, 'url' => (string) $transaction->url() ) ), 200, 'application/json' );