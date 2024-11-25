<?php
/**
 * @brief		Member Listener
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		22 May 2023
 */

namespace IPS\nexus\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Subscription;
use IPS\nexus\Subscription\Package;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use Exception;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Listener
 */
class Nexus extends MemberListenerType
{
	/**
	 * Member has logged on
	 *
	 * @param	Member	$member		Member that logged in
	 * @return	void
	 */
	public function onLogin( Member $member ) : void
	{
		if ( isset( Request::i()->cookie['cm_reg'] ) and Request::i()->cookie['cm_reg'] )
		{
			try
			{
				$invoice = Invoice::load( Request::i()->cookie['cm_reg'] );

				Request::i()->setCookie( 'cm_reg', 0 );

				if ( !$invoice->member->member_id )
				{
					$invoice->member = $member;
					$invoice->save();
				}

				if ( $invoice->member->member_id === $member->member_id )
				{
					Output::i()->redirect( $invoice->checkoutUrl() );
				}
			}
			catch ( Exception )
			{
				Request::i()->setCookie( 'cm_reg', 0 );
			}
		}
	}

	/**
	 * Member is merged with another member
	 *
	 * @param	Member	$member		Member being kept
	 * @param	Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( Member $member, Member $member2 ) : void
	{
		Db::i()->update( 'nexus_customer_addresses', array( 'member' => $member->member_id ), array( '`member`=?', $member2->member_id ) );
		Db::i()->update( 'nexus_customer_cards', array( 'card_member' => $member->member_id ), array( 'card_member=?', $member2->member_id ) );
		Db::i()->update( 'nexus_invoices', array( 'i_member' => $member->member_id ), array( 'i_member=?', $member2->member_id ) );
		Db::i()->update( 'nexus_purchases', array( 'ps_member' => $member->member_id ), array( 'ps_member=?', $member2->member_id ) );
		Db::i()->update( 'nexus_purchases', array( 'ps_pay_to' => $member->member_id ), array( 'ps_pay_to=?', $member2->member_id ) );
		Db::i()->update( 'nexus_transactions', array( 't_member' => $member->member_id ), array( 't_member=?', $member2->member_id ) );
		Db::i()->update( 'nexus_alternate_contacts', array( 'main_id' => $member->member_id ), array( 'main_id=?', $member2->member_id ) );
		Db::i()->update( 'nexus_alternate_contacts', array( 'alt_id' => $member->member_id ), array( 'alt_id=?', $member2->member_id ) );
		Db::i()->update( 'nexus_billing_agreements', array( 'ba_member' => $member->member_id ), array( 'ba_member=?', $member2->member_id ) );

		Db::i()->delete( 'nexus_customer_spend', array( 'spend_member_id=?', $member2->member_id ) );

		Db::i()->delete( 'nexus_alternate_contacts', array( 'main_id = alt_id' ) );

		/* Account Credit */
		$customerToKeep = Customer::load( $member->member_id );
		$creditToKeep = $customerToKeep->cm_credits;
		$creditToMerge = Customer::load( $member2->member_id )->cm_credits;

		foreach( Money::currencies() as $currency )
		{
			if( isset( $creditToMerge[$currency] ) )
			{
				if( isset( $creditToKeep[$currency] ) )
				{
					$creditToKeep[$currency]->amount = $creditToKeep[$currency]->amount->add( $creditToMerge[$currency]->amount );
				}
				else
				{
					$creditToKeep[$currency] = $creditToMerge[$currency];
				}
			}
		}
		$customerToKeep->cm_credits = $creditToKeep;
		$customerToKeep->save();

		/* Subscription packages */
		if ( $keepSub = Subscription::loadByMember( $member, TRUE ) )
		{
			if ( $dropSub = Subscription::loadByMember( $member2, TRUE ) AND $package = Package::load( $dropSub->package_id ) )
			{
				$package->removeMember(  Customer::load( $member2->member_id ) );
			}
		}

		/* Recount total spend */
		$customerToKeep->recountTotalSpend();
	}

	/**
	 * Member is deleted
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	public function onDelete( Member $member ) : void
	{
		Db::i()->delete( 'nexus_customer_addresses', array( '`member`=?', $member->member_id ) );
		Db::i()->delete( 'nexus_customer_cards', array( 'card_member=?', $member->member_id ) );
		Db::i()->delete( 'nexus_customers', array( 'member_id=?', $member->member_id ) );
		Db::i()->delete( 'nexus_alternate_contacts', array( 'main_id=?', $member->member_id ) );
		Db::i()->delete( 'nexus_alternate_contacts', array( 'alt_id =?', $member->member_id ) );

		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( 'ps_member=?', $member->member_id ) ), 'IPS\nexus\Purchase' ) as $purchase )
		{
			$purchase->delete();
		}

		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_member=?', $member->member_id ) ), '\IPS\nexus\Customer\BillingAgreement' ) as $agreement )
		{
			try
			{
				$agreement->cancel();
			}
			catch ( Exception ) {}
		}

		Db::i()->update( 'nexus_purchases', array( 'ps_pay_to' => 0 ), array( 'ps_pay_to=?', $member->member_id ) );

		/* Subscriptions (mop up orphaned members) */
		Db::i()->delete( 'nexus_member_subscriptions', array( "sub_member_id=?", $member->member_id ) );

		Db::i()->delete( 'nexus_customer_spend', array( 'spend_member_id=?', $member->member_id ) );
	}
}