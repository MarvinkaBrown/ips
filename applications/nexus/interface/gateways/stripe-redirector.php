<?php
/**
 * @brief		Stripe Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		20 Jul 2017
 */

use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Device;
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

/* Little IN_DEV helper to prevent firing this until we've triggered the webhooks */
if ( \IPS\IN_DEV and !isset( Request::i()->indevconfirm ) )
{
	echo "<a href='" . Request::i()->url()->setQueryString( 'indevconfirm', 1 ) . "'>Continue</a>";
	exit;
}

/* Wait a few seconds so the webhook has time to come through */
sleep( 5 );

/* Load Source */
try
{
	$transaction = Transaction::load( Request::i()->nexusTransactionId );
	$intent = $transaction->method->api( 'payment_intents/' . Request::i()->payment_intent, null, 'GET' );
	if( $intent['client_secret'] != Request::i()->payment_intent_client_secret )
	{
		throw new Exception;
	}
}
catch (Exception )
{
	Output::i()->redirect( Url::internal( "app=nexus&module=checkout&controller=checkout&do=transaction&id=&t=" . Request::i()->nexusTransactionId, 'front', 'nexus_checkout', Settings::i()->nexus_https ) );
}

/* If we're a guest, but the transaction belongs to a member, that's because the webhook has
	processed the transaction and created an account - so we need to log the newly created
	member in. This is okay to do because we've checked client_secret is correct, meaning
	we know this is a genuine redirect back from Stripe after payment of this transaction */
if ( !Member::loggedIn()->member_id and $transaction->member->member_id )
{
	Session::i()->setMember( $transaction->member );
	Device::loadOrCreate( $transaction->member, FALSE )->updateAfterAuthentication( NULL );
}

/* And then send them on */
switch( $intent['status'] )
{
	case 'processing':
		Output::i()->redirect( $transaction->url()->setQueryString( 'pending', 1 ) );
		break;

	case 'succeeded':
		Output::i()->redirect( $transaction->url() );
		break;

	default:
		$url = $transaction->invoice->checkoutUrl();
		if( isset( $intent['last_payment_error'] ) )
		{
			$url = $url->setQueryString( 'err', $intent['last_payment_error']['message'] );
		}
		Output::i()->redirect( $url );
		break;
}