<?php
/**
 * @brief		subscriptions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		09 Feb 2018
 */

namespace IPS\nexus\modules\front\subscriptions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\core\Facebook\Pixel;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\extensions\nexus\Item\Subscription;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription\Package;
use IPS\nexus\Subscription\Table;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * subscriptions
 */
class subscriptions extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Work out currency */
		if ( isset( Request::i()->currency ) and in_array( Request::i()->currency, Money::currencies() ) )
		{
			if ( isset( Request::i()->csrfKey ) and Login::compareHashes( (string) Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
			{
				$_SESSION['cart'] = array();
				Request::i()->setCookie( 'currency', Request::i()->currency );

				$url = Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' );

				if( isset( Request::i()->register ) )
				{
					$url = $url->setQueryString( 'register', (int) Request::i()->register );
				}

				Output::i()->redirect( $url );
			}
		}

		parent::execute();
	}
	
	/**
	 * Show the subscription packages
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( ! Settings::i()->nexus_subs_enabled )
		{ 
			Output::i()->error( 'nexus_no_subs', '2X379/1', 404, '' );
		}

		/* Send no-cache headers for this page, required for guest sign-ups */
		Output::setCacheTime( false );

		/* Create the table */
		$table = new Table( Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' ) );
		$current = \IPS\nexus\Subscription::loadByMember( Member::loggedIn(), FALSE );

		if ( $current )
		{
			$table->activeSubscription = $current;
		}

		if ( isset( Request::i()->purchased ) and $table->activeSubscription )
		{
			try
			{
				$invoice = Invoice::load( $table->activeSubscription->invoice_id );

				/* Fire the pixel event */
				Pixel::i()->Purchase = array( 'value' => $invoice->total->amount, 'currency' => $invoice->total->currency );
				Output::i()->inlineMessage = Member::loggedIn()->language()->addToStack( 'nexus_subs_paid_flash_msg');
			}
			catch( Exception ) { }
		}

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_subscriptions.js', 'nexus', 'front' ) );

		Output::i()->breadcrumb['module'] = array( Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' ), Member::loggedIn()->language()->addToStack('nexus_front_subscriptions') );

		Output::i()->linkTags['canonical'] = (string) Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' );
		Output::i()->title = Member::loggedIn()->language()->addToStack('nexus_front_subscriptions');
		Output::i()->output = $table;
	}
	
	/**
	 * Change packages. It allows you to change packages. I mean again, the whole concept of PHPDoc seems to point out the obvious. A bit like GPS navigation for your front room. There's the sofa. There's the cat.
	 *
	 * @return void just like life, it is meaningless and temporary so live in the moment, enjoy each day and eat chocolate unless you have an allergy in which case don't. See your GP before starting any new diet.
	 */
	protected function change() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		try
		{
			$newPackage = Package::load( Request::i()->id );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'nexus_no_subs_package', '2X379/2', 404, '' );
		}

		/* Is the subscription purchasable ? */
		if ( !$newPackage->enabled )
		{
			Output::i()->error( 'node_error', '2X379/7', 403, '' );
		}

		try
		{
			$subscription = \IPS\nexus\Subscription::loadByMember( Member::loggedIn(), FALSE );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'nexus_no_subs_subs', '2X379/3', 404, '' );
		}

		/* Fetch purchase */
		$purchase = NULL;
		if( $subscription )
		{
			foreach ( Subscription::getPurchases( Customer::loggedIn(), $subscription->package->id, TRUE, TRUE ) as $row )
			{
				if ( !$row->cancelled OR ( $row->cancelled AND $row->can_reactivate ) )
				{
					$purchase = $row;
					break;
				}
			}
		}
		
		if ( $purchase === NULL )
		{
			Output::i()->error( 'nexus_sub_no_purchase', '2X379/4', 404, '' );
		}

		/* @var Purchase $purchase */
		/* We cannot process changes if an active Billing Agreement is in place */
		if( $purchase->billing_agreement and !$purchase->billing_agreement->canceled )
		{
			Output::i()->error( 'nexus_sub_no_change_ba', '2X379/B', 404, '' );
		}
		
		/* Right, that's all the "I'll tamper with the URLs for a laugh" stuff out of the way... */
		$upgradeCost = $newPackage->costToUpgrade( $subscription->package, Customer::loggedIn() );
		
		if ( $upgradeCost === NULL )
		{
			Output::i()->error( 'nexus_no_subs_nocost', '2X379/5', 404, '' );
		}
		
		$invoice = $subscription->package->upgradeDowngrade( $purchase, $newPackage );
		
		if ( $invoice )
		{
			Output::i()->redirect( $invoice->checkoutUrl() );
		}
		
		$purchase->member->log( 'subscription', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $purchase->name, 'name' => $newPackage->titleForLog(), 'system' => FALSE ) );
		
		Output::i()->redirect( Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' ) );
	}
		
	/**
	 * Purchase
	 *
	 * @return	void
	 */
	protected function purchase() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();

		/* Send no-cache headers for this page, required for guest sign-ups */
		Output::setCacheTime( false );
		
		/* Already purchased a subscription */
		if ( $current = \IPS\nexus\Subscription::loadByMember( Customer::loggedIn(), FALSE ) AND ( $current->purchase AND ( !$current->purchase->cancelled OR $current->purchase->can_reactivate ) ) )
		{
			Output::i()->error( 'nexus_subs_already_got_package', '2X379/6', 403, '' );
		}
				
		$package = Package::load( Request::i()->id );

		/* Is the subscription purchasable ? */
		if ( !$package->enabled )
		{
			Output::i()->error( 'node_error', '2X379/7', 403, '' );
		}

		$price = $package->price();
		
		$item = new Subscription( Customer::loggedIn()->language()->get( $package->_titleLanguageKey ), $price );
		$item->id = $package->id;
		try
		{
			$item->tax = Tax::load( $package->tax );
		}
		catch ( OutOfRangeException ) { }
		if ( $package->gateways !== '*' )
		{
			$item->paymentMethodIds = explode( ',', $package->gateways );
		}
		$item->renewalTerm = $package->renewalTerm( $price->currency );
		if ( $package->price and $costs = json_decode( $package->price, TRUE ) and isset( $costs['cost'] ) )
		{
			$item->initialInterval = new DateInterval( 'P' . $costs['term'] . mb_strtoupper( $costs['unit'] ) );
		}
		
		/* Generate the invoice */
		$invoice = new Invoice;
		$invoice->currency = $price->currency;
		$invoice->member = Customer::loggedIn();
		$invoice->addItem( $item );
		$invoice->return_uri = "app=nexus&module=subscriptions&controller=subscriptions&purchased=1";
		$invoice->save();
		
		/* Take them to it */
		Output::i()->redirect( $invoice->checkoutUrl() );
	}
	
	/**
	 * Reactivate
	 *
	 * @return	void
	 */
	protected function reactivate() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Get subscription and purchase */
		try
		{
			$package = Package::load( Request::i()->id );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'nexus_no_subs_package', '2X379/2', 404, '' );
		}
		try
		{
			$subscription = \IPS\nexus\Subscription::loadByMemberAndPackage( Member::loggedIn(), $package, FALSE );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'nexus_no_subs_subs', '2X379/8', 404, '' );
		}
		$purchase = NULL;
		foreach ( Subscription::getPurchases( Customer::loggedIn(), $subscription->package->id, TRUE, TRUE ) as $row )
		{
			if ( $row->can_reactivate )
			{
				$purchase = $row;
				break;
			}
		}
		if ( $purchase === NULL )
		{
			Output::i()->error( 'nexus_sub_no_purchase', '2X379/9', 404, '' );
		}

		/* @var Purchase $purchase */
		/* Set renewal terms */
		try
		{
			$currency = $purchase->original_invoice->currency;
		}
		catch ( Exception )
		{
			$currency = $purchase->member->defaultCurrency();
		}
		
		$purchase->renewals = $package->renewalTerm( $currency );
		$purchase->cancelled = FALSE;
		$purchase->save();
		$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'change_renewals', 'to' => array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) ) );

		/* Either send to renewal invoice or just back to subscriptions list */
		if ( !$purchase->active and $cycles = $purchase->canRenewUntil( NULL, TRUE ) AND $cycles !== FALSE )
		{
			$url = $cycles === 1 ? $purchase->url()->setQueryString( 'do', 'renew' )->csrf() : $purchase->url()->setQueryString( 'do', 'renew' );
			Output::i()->redirect( $url );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=nexus&module=subscriptions&controller=subscriptions', 'front', 'nexus_subscriptions' ) );
		}
	}
}