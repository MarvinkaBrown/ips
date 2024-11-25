<?php
/**
 * @brief		Purchases
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		06 May 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Purchase;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Purchases
 */
class purchases extends Controller
{
	/**
	 * @brief	Purchase object
	 */
	protected ?Purchase $purchase = NULL;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2X212/6', 403, '' );
		}
		
		/* Purchases breadcrumb */
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=purchases', 'front', 'clientspurchases' ), Member::loggedIn()->language()->addToStack('client_purchases') );
		
		/* Load Purchase */
		if ( isset( Request::i()->id ) )
		{
			try
			{
				$this->purchase = Purchase::load( Request::i()->id );
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2X212/1', 404, '' );
			}
			if ( !$this->purchase->canView() )
			{
				Output::i()->error( 'no_module_permission', '2X212/2', 403, '' );
			}
			
			Output::i()->breadcrumb[] = array( $this->purchase->url(), $this->purchase->name );
			Output::i()->title = $this->purchase->name;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('client_purchases');
			if ( isset( Request::i()->do ) )
			{
				Output::i()->error( 'node_error', '2X212/3', 403, '' );
			}
		}
		
		/* Execute */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}

	/**
	 * View List
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$where = array( array( 'ps_member=?', Member::loggedIn()->member_id ) );

		$parentContacts = Customer::loggedIn()->parentContacts();
		if ( count( $parentContacts ) )
		{
			foreach ( $parentContacts as $contact )
			{
				$where[0][0] .= ' OR ' . Db::i()->in( 'ps_id', $contact->purchaseIds() );
			}
		}
		$where[] = array( 'ps_show=1' );
		
		/* Get only the purchases from active applications */
		$where[] = array( "ps_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')" );

		$purchases = array();
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', $where, 'ps_active DESC, ps_expire DESC, ps_start DESC' ), 'IPS\nexus\Purchase' ) as $purchase )
		{
			$purchases[ $purchase->parent ][ $purchase->id ] = $purchase;
		}
		Output::i()->output = Theme::i()->getTemplate('clients')->purchases( $purchases );
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view() : void
	{		
		Output::i()->output = Theme::i()->getTemplate('clients')->purchase( $this->purchase );
	}
	
	/**
	 * Extra
	 *
	 * @return	void
	 */
	protected function extra() : void
	{
		$this->purchase->clientAreaAction();
		Output::i()->redirect( $this->purchase->url() );
	}
	
	/**
	 * Renew
	 *
	 * @return	void
	 */
	protected function renew() : void
	{
		$cycles = $this->purchase->canRenewUntil( NULL, TRUE );
		if ( $cycles === FALSE )
		{
			Output::i()->error( 'you_cannot_renew', '2X212/4', 403, '' );
		}
		elseif ( $cycles === 1 )
		{
			Session::i()->csrfCheck();
		}
		elseif ( isset( Request::i()->cycles ) and Login::compareHashes( (string) Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
		{
			$_cycles = intval( Request::i()->cycles );
			if ( $_cycles >= 1 and ( $cycles === TRUE or $_cycles <= $cycles ) )
			{
				$cycles = $_cycles;
			}
		}
		
		$term = $this->purchase->renewals->getTerm();
		if ( $term['term'] > 1 )
		{
			$suffix = '&times; ' . $this->purchase->renewals->getTermUnit();
		}
		else
		{
			switch( $term['unit'] )
			{
				case 'd':
					$suffix = Member::loggedIn()->language()->addToStack('days');
					break;
				case 'm':
					$suffix = Member::loggedIn()->language()->addToStack('months');
					break;
				case 'y':
					$suffix = Member::loggedIn()->language()->addToStack('years');
					break;
			}
		}
		
		$form = new Form( 'form', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--renew';
		$form->add( new Number( 'renew_for', 1, TRUE, array( 'min' => 1, 'max' => $cycles === TRUE ? NULL : $cycles ), NULL, NULL, $suffix ) );
		if( $values = $form->values() or $cycles === 1 )
		{
			if ( $pendingInvoice = $this->purchase->invoice_pending and $pendingInvoice->status === $pendingInvoice::STATUS_PENDING )
			{
				if ( count( $pendingInvoice->items ) === 1 )
				{
					foreach ( $pendingInvoice->items as $item )
					{
						if ( $item instanceof Renewal and $item->id === $this->purchase->id and $item->quantity === ( $cycles === 1 ? 1 : $values['renew_for'] ) )
						{
							Output::i()->redirect( $pendingInvoice->checkoutUrl() );
						}
					}
				}
				
				$pendingInvoice->status = $pendingInvoice::STATUS_CANCELED;
				$pendingInvoice->save();
				$pendingInvoice->member->log( 'invoice', array( 'type' => 'status', 'new' => 'canc', 'id' => $pendingInvoice->id, 'title' => $pendingInvoice->title ) );
			}
						
			$invoice = new Invoice;
			$invoice->member = Customer::loggedIn();
			$invoice->currency = $this->purchase->renewals->cost->currency;
			$invoice->addItem( Renewal::create( $this->purchase, $cycles === 1 ? 1 : $values['renew_for'] ) );
			$invoice->save();
			
			$this->purchase->invoice_pending = $invoice;
			$this->purchase->save();
			
			Output::i()->redirect( $invoice->checkoutUrl() );
		}
		
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 */
	protected function cancel() : void
	{
		Session::i()->csrfCheck();
						
		if ( !$this->purchase->canCancel() )
		{
			Output::i()->error( 'you_cannot_cancel', '2X212/5', 403, '' );
		}
		
		$this->purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $this->purchase->id, 'name' => $this->purchase->name, 'info' => 'remove_renewals' ) );
		
		/* If we have a pending renewal invoice, cancel it (as at this point, we need to reactivate instead) */
		if ( $this->purchase->invoice_pending )
		{
			$this->purchase->invoice_pending->status = Invoice::STATUS_CANCELED; # The constant has a typo and it make me sad
			$this->purchase->invoice_pending->save();
			
			$this->purchase->invoice_pending = NULL;
		}
		
		$this->purchase->renewals = NULL;
		$this->purchase->can_reactivate = TRUE;
		$this->purchase->save();
		
		if ( $ref = Request::i()->referrer( FALSE, TRUE ) )
		{
			Output::i()->redirect( $ref );
		}
		Output::i()->redirect( $this->purchase->url() );
	}
}