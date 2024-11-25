<?php
/**
 * @brief		Invoices
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		06 May 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Events\Event;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;

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
	 * @var Invoice|null
	 */
	protected ?Invoice $invoice = null;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{	
		/* Load Invoice */
		if ( isset( Request::i()->id ) )
		{
			if ( isset( Request::i()->printout ) )
			{
				Request::i()->do = 'printout';
			}
			
			if ( Member::loggedIn()->member_id )
			{
				try
				{
					$this->invoice = Invoice::loadAndCheckPerms( Request::i()->id );
				}
				catch ( OutOfRangeException )
				{
					Output::i()->error( 'node_error', '2X215/1', 404, '' );
				}
			}
			else
			{
				/* Prevent the vid key from being exposed in referrers */
				Output::i()->sendHeader( "Referrer-Policy: origin" );

				$key = isset( Request::i()->key ) ? Request::i()->key : ( isset( Request::i()->cookie['guestTransactionKey'] ) ? Request::i()->cookie['guestTransactionKey'] : NULL );
				$this->invoice = Invoice::load( Request::i()->id );

				if( $this->invoice->member->member_id or !$key or !isset( $this->invoice->guest_data['guestTransactionKey'] ) or !Login::compareHashes( $key, $this->invoice->guest_data['guestTransactionKey'] ) )
				{
					Output::i()->error( 'no_module_permission_guest', '2X215/6', 404, '' );
				}

				/* Do not cache this guest view invoice */
				Output::setCacheTime( false );
			}
				
			Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=invoices', 'front', 'clientsinvoices' ), Member::loggedIn()->language()->addToStack('client_invoices') );
			Output::i()->breadcrumb[] = array( $this->invoice->url(), $this->invoice->title );
			Output::i()->title = $this->invoice->title;
		}
		else
		{
			if ( !Member::loggedIn()->member_id )
			{
				Output::i()->error( 'no_module_permission_guest', '2X215/3', 403, '' );
			}
		
			Output::i()->title = Member::loggedIn()->language()->addToStack('client_invoices');
			if ( isset( Request::i()->do ) )
			{
				Output::i()->error( 'node_error', '2X215/2', 403, '' );
			}
		}
		
		/* Execute */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}
	
	/**
	 * @brief Invoices Per Page
	 */
	protected static int $invoicesPerPage = 25;

	/**
	 * View List
	 *
	 * @return	void
	 */
	protected function manage() : void
	{	
		$where = array( 'i_member=?', Member::loggedIn()->member_id );
		$parentContacts = Customer::loggedIn()->parentContacts( array( 'billing=1' ) );
		if ( count( $parentContacts ) )
		{
			foreach ( array_keys( iterator_to_array( $parentContacts ) ) as $id )
			{
				$where[0] .= ' OR i_member=?';
				$where[] = $id;
			}
		}
		
		$count = Db::i()->select( 'COUNT(*)', 'nexus_invoices', $where )->first();
		$page = isset( Request::i()->page ) ? Request::i()->page : 1;
		
		if ( $page < 1 )
		{
			$page = 1;
		}
		
		$pages = ( $count > 0 ) ? ceil( $count / static::$invoicesPerPage ) : 1;
		
		if ( $page > $pages )
		{
			Output::i()->redirect( Url::internal( "app=nexus&module=clients&controller=invoices", 'front', 'clientsinvoices' ) );
		}
		
		$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( Url::internal( "app=nexus&module=clients&controller=invoices", 'front', 'clientsinvoices' ), $pages, $page, static::$invoicesPerPage );
				
		$invoices = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_invoices', $where, 'i_date DESC', array( ( $page - 1 ) * static::$invoicesPerPage, static::$invoicesPerPage ) ), 'IPS\nexus\Invoice' );
		Output::i()->output = Theme::i()->getTemplate('clients')->invoices( $invoices, $pagination );
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view() : void
	{
		Output::i()->output = Theme::i()->getTemplate('clients')->invoice( $this->invoice );
	}
	
	/**
	 * PO Number
	 *
	 * @return	void
	 */
	public function poNumber() : void
	{		
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--po-number ipsForm--noLabels';
		$form->add( new Text( 'invoice_po_number', $this->invoice->po, FALSE, array( 'maxLength' => 255 ) ) );
		if ( $values = $form->values() )
		{
			$this->invoice->po = $values['invoice_po_number'];
			$this->invoice->save();
			Output::i()->redirect( $this->invoice->url() );
		}
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Notes
	 *
	 * @return	void
	 */
	public function notes() : void
	{		
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--invoice-notes ipsForm--noLabels';
		$form->add( new TextArea( 'invoice_notes', $this->invoice->notes ) );
		Member::loggedIn()->language()->words['invoice_notes_desc'] = '';
		if ( $values = $form->values() )
		{
			$this->invoice->notes = $values['invoice_notes'];
			$this->invoice->save();
			Output::i()->redirect( $this->invoice->url() );
		}
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Print
	 *
	 * @return	void
	 */
	public function printout() : void
	{
		$output = Theme::i()->getTemplate( 'invoices', 'nexus', 'global' )->printInvoice( $this->invoice, $this->invoice->summary(), $this->invoice->billaddress ?: $this->invoice->member->primaryBillingAddress() );
		Output::i()->title = 'I' . $this->invoice->id;
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ) );
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 */
	public function cancel() : void
	{
		/* CSRF check */
		Session::i()->csrfCheck();
		
		/* Can only cancel the invoice if it's pending and there are no processing transactions */
		if ( $this->invoice->status !== Invoice::STATUS_PENDING or count( $this->invoice->transactions( [ Transaction::STATUS_HELD, Transaction::STATUS_REVIEW, Transaction::STATUS_GATEWAY_PENDING, Transaction::STATUS_DISPUTED ] ) ) )
		{
			Output::i()->error( 'order_already_paid', '2X215/4', 403, '' );
		}
				        
        /* If they have already made a partial payment, refund it to their account credit */
        foreach ( $this->invoice->transactions( array( Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED ) ) as $transaction )
		{
			/* @var Transaction $transaction */
			try
			{
				$transaction->refund( 'credit' );
			}
			catch ( Exception $e )
			{
				Output::i()->error( 'order_cancel_error', '4C171/5', 500, $e->getMessage() );
			}
		}
		
		/* Cancel the invoice */
		$this->invoice->status = invoice::STATUS_CANCELED;
		$this->invoice->save();
		$this->invoice->member->log( 'invoice', array( 'type' => 'status', 'new' => 'canc', 'id' => $this->invoice->id, 'title' => $this->invoice->title ) );

		/* Run any callbacks (for example, coupons get unmarked as being used) */
        foreach ( $this->invoice->items as $item )
        {
            $item->onInvoiceCancel( $this->invoice );

			Event::fire( 'onInvoiceCancel', $item, array( $this->invoice ) );
        }
        
        /* Redirect */
		Output::i()->redirect( $this->invoice->url() );
	}
}