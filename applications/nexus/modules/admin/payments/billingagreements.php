<?php
/**
 * @brief		Billing Agreememts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		16 Dec 2015
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\nexus\Customer\BillingAgreement;
use IPS\nexus\Gateway\PayPal\Exception;
use IPS\nexus\Purchase;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Request;
use IPS\Session;
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
 * Billing Agreements
 */
class billingagreements extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'billingagreements_view' );
		parent::execute();
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Load */
		try
		{
			$billingAgreement = BillingAgreement::load( Request::i()->id );
			
			if ( !$billingAgreement->canceled and $billingAgreement->status() == $billingAgreement::STATUS_CANCELED )
			{
				$billingAgreement->canceled = TRUE;
				$billingAgreement->next_cycle = NULL;
				$billingAgreement->save();
			}
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X320/1', 404, '' );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'billing_agreement_error_public', '4X320/9', 500, 'billing_agreement_error', array(), $e->getName() );
		}
		
		/* Show */
		try
		{
			/* Purchases */
			$purchases = NULL;
			if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) and ( !isset( Request::i()->table ) ) )
			{
				$purchases = Purchase::tree( $billingAgreement->acpUrl(), array( array( 'ps_billing_agreement=?', $billingAgreement->id ) ), 'ba' );
			}
			
			/* Transactions */
			$transactions = NULL;
			if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) and ( !isset( Request::i()->table ) ) )
			{
				$transactions = Transaction::table( array( array( 't_billing_agreement=? AND t_status<>?', $billingAgreement->id, Transaction::STATUS_PENDING ) ), $billingAgreement->acpUrl(), 'ba' );
				$transactions->limit = 50;
				foreach ( $transactions->include as $k => $v )
				{
					if ( in_array( $v, array( 't_method', 't_member' ) ) )
					{
						unset( $transactions->include[ $k ] );
					}
				}
			}
			
			/* Action Buttons */
			if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'billingagreements_manage' ) )
			{
				if ( $billingAgreement->status() == $billingAgreement::STATUS_ACTIVE )
				{
					Output::i()->sidebar['actions']['refresh'] = array(
						'icon'	=> 'refresh',
						'title'	=> 'billing_agreement_check',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'refresh' ) )->csrf(),
						'data'	=> array( 'confirm' => '' )
					);

					Output::i()->sidebar['actions']['suspend'] = array(
						'icon'	=> 'times',
						'title'	=> 'billing_agreement_suspend',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'suspend' ) )->csrf(),
						'data'	=> array( 'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->addToStack('billing_agreement_suspend_confirm') )
					);
				}
				elseif ( $billingAgreement->status() == $billingAgreement::STATUS_SUSPENDED )
				{
					Output::i()->sidebar['actions']['reactivate'] = array(
						'icon'	=> 'check',
						'title'	=> 'billing_agreement_reactivate',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'reactivate' ) )->csrf(),
						'data'	=> array( 'confirm' => '' )
					);
				}
				if ( $billingAgreement->status() != $billingAgreement::STATUS_CANCELED )
				{
					Output::i()->sidebar['actions']['cancel'] = array(
						'icon'	=> 'times-circle',
						'title'	=> 'billing_agreement_cancel',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'cancel' ) )->csrf(),
					);
				}
			}
							
			/* Display */
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'billing_agreement_id', FALSE, array( 'sprintf' => array( $billingAgreement->gw_id ) ) );
			Output::i()->output = Theme::i()->getTemplate( 'billingagreements' )->view( $billingAgreement, $purchases, $transactions );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'billing_agreement_error', '1X320/2', 500, '', array(), $e->getName() );
		}
		catch ( DomainException )
		{
			Output::i()->error( 'billing_agreement_error', '1X320/3' );
		}
	}
	
	/**
	 * Reconcile - resets next_cycle date
	 *
	 * @return	void
	 */
	public function reconcile() : void
	{
		Session::i()->csrfCheck();
		
		/* Load */
		try
		{
			$billingAgreement = BillingAgreement::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X320/5', 404, '' );
		}
		
		/* Reconcile */
		try
		{
			$billingAgreement->next_cycle = $billingAgreement->nextPaymentDate();
			$billingAgreement->save();
			
			Output::i()->redirect( $billingAgreement->acpUrl() );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'billing_agreement_error', '1X320/6', 500, '', array(), $e->getName() );
		}
		catch ( DomainException )
		{
			Output::i()->error( 'billing_agreement_error', '1X320/7' );
		}
	}
	
	/**
	 * Suspend/Reactivate/Cancel
	 *
	 * @return	void
	 */
	public function act() : void
	{
		Session::i()->csrfCheck();
		
		/* Check act */
		$act = Request::i()->act;
		if ( !in_array( $act, array( 'suspend', 'reactivate', 'cancel', 'refresh' ) ) )
		{
			Output::i()->error( 'node_error', '3X320/8', 403, '' );
		}
		
		/* Load */
		try
		{
			$billingAgreement = BillingAgreement::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X320/8', 404, '' );
		}
		
		/* Perform Action */
		try
		{
			$billingAgreement->$act();
			
			Output::i()->redirect( $billingAgreement->acpUrl() );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'billing_agreement_error', '1X320/9', 500, '', array(), $e->getName() );
		}
		catch ( DomainException )
		{
			Output::i()->error( 'billing_agreement_error', '1X320/A' );
		}
	}
}