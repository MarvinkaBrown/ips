<?php
/**
 * @brief		Billing Agreements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Dec 2015
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\nexus\Customer;
use IPS\nexus\Customer\BillingAgreement;
use IPS\nexus\Gateway\PayPal\Exception;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Billing Agreements
 */
class billingagreements extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2X321/1', 403, '' );
		}
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=billingagreements', 'front', 'clientsbillingagreements' ), Member::loggedIn()->language()->addToStack('client_billing_agreements') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_billing_agreements');
		Output::i()->sidebar['enabled'] = FALSE;
		
		if ( $output = MFAHandler::accessToArea( 'nexus', 'BillingAgreements', Url::internal( 'app=nexus&module=clients&controller=billingagreements', 'front', 'clientsbillingagreements' ) ) )
		{
			Output::i()->output = Theme::i()->getTemplate('clients')->billingAgreements( array() ) . $output;
			return;
		}
		
		parent::execute();
	}
	
	/**
	 * View List
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$billingAgreements = array();
		
		$where = array( 'ba_member=?', Member::loggedIn()->member_id );
		$parentContacts = Customer::loggedIn()->parentContacts( array( 'billing=1' ) );
		if ( count( $parentContacts ) )
		{
			foreach ( array_keys( iterator_to_array( $parentContacts ) ) as $id )
			{
				$where[0] .= ' OR ba_member=?';
				$where[] = $id;
			}
		}
		
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_billing_agreements', $where ), 'IPS\nexus\Customer\BillingAgreement' ) as $billingAgreement )
		{
			/* @var BillingAgreement $billingAgreement */
			try
			{
				$status = $billingAgreement->status();
			}
			catch ( \Exception )
			{
				$status = NULL;
			}
			
			try
			{
				$term = $billingAgreement->term();
			}
			catch ( \Exception )
			{
				$term = NULL;
			}
			
			$billingAgreements[] = array(
				'status'	=> $status,
				'id'		=> $billingAgreement->gw_id,
				'term'		=> $term,
				'url'		=> $billingAgreement->url()
			);
		}
		
		Output::i()->output = Theme::i()->getTemplate('clients')->billingAgreements( $billingAgreements );
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		/* Load Billing Agreement */
		try
		{
			$billingAgreement = BillingAgreement::loadAndCheckPerms( Request::i()->id );
			$billingAgreement->status(); // Just to make the API call so we can catch the error if there is one
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X320/4', 404, '' );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'billing_agreement_error', '4X321/5', 500, '', array(), $e->getName() );
		}
		
		/* Get associated purchases */
		$purchases = array();
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( 'ps_billing_agreement=?', $billingAgreement->id ) ), 'IPS\nexus\Purchase' ) as $purchase )
		{
			$purchases[0][ $purchase->id ] = $purchase;
		}
		
		/* Transactions */
		$currentPage = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
		$perPage = 25;
		$invoices = new ActiveRecordIterator(
			Db::i()->select(
				'*',
				'nexus_invoices',
				array( 'i_id IN(?)', Db::i()->select( 't_invoice', 'nexus_transactions', array( 't_billing_agreement=?', $billingAgreement->id ) ) ),
				'i_date DESC',
				array( ( $currentPage - 1 ) * $perPage, $perPage )
			),
			'IPS\nexus\Invoice'
		);
		$invoicesCount = Db::i()->select( 'COUNT(*)', 'nexus_invoices', array( 'i_id IN(?)', Db::i()->select( 't_invoice', 'nexus_transactions', array( 't_billing_agreement=?', $billingAgreement->id ) ) ) )->first();
		$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $billingAgreement->url(), ceil( $invoicesCount / $perPage ), $currentPage, $perPage );
		
		Output::i()->breadcrumb[] = array( $billingAgreement->url(), $billingAgreement->gw_id );
		Output::i()->output = Theme::i()->getTemplate('clients')->billingAgreement( $billingAgreement, $purchases, $invoices, $pagination );
	}
	
	/**
	 * Act
	 *
	 * @return	void
	 */
	protected function act() : void
	{
		Session::i()->csrfCheck();
		
		/* Check act */
		$act = Request::i()->act;
		if ( !in_array( $act, array( 'suspend', 'reactivate', 'cancel' ) ) )
		{
			Output::i()->error( 'node_error', '3X321/3', 403, '' );
		}
		
		/* Load Billing Agreement */
		try
		{
			$billingAgreement = BillingAgreement::loadAndCheckPerms( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X321/2', 404, '' );
		}
		
		/* Perform Action */
		try
		{
			$billingAgreement->$act();
			
			Output::i()->redirect( $billingAgreement->url() );
		}
		catch ( DomainException )
		{
			Output::i()->error( 'billing_agreement_error_public', '3X321/4', 500, '' );
		}
	}
}