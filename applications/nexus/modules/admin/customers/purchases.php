<?php
/**
 * @brief		Purchases
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Feb 2014
 */

namespace IPS\nexus\modules\admin\customers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Renewal;
use IPS\nexus\Money;
use IPS\nexus\Package\CustomField;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\RenewalTerm;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use function defined;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_SELECT;

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
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @var Purchase|null
	 */
	protected ?Purchase $purchase = null;
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_view' );

		try
		{
			$this->purchase = Purchase::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X195/2', 404, '' );
		}

		Output::i()->title = $this->purchase->name . " (#{$this->purchase->id})";
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'purchases.css', 'nexus', 'admin' ) );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$this->view();
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function view() : void
	{
		/* Popup view */
		if ( Request::i()->isAjax() and isset( Request::i()->hovercard ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'purchases' )->hovercard( $this->purchase );
			return;
		}
		
		/* Create children tree */
		$children = Purchase::tree( $this->purchase->acpUrl(), array(), "p{$this->purchase->id}", $this->purchase, FALSE );
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $children;
			return;
		}
		
		/* Custom Fields */
		$customFields = array();
		foreach ( $this->purchase->custom_fields  as $k => $v )
		{
			try
			{
				if ( $displayValue = trim( CustomField::load( $k )->displayValue( $v, TRUE ) ) )
				{
					$customFields[ $k ] = $displayValue;
				}
			}
			catch ( OutOfRangeException ) { }
		}
		
		/* Get customer */
		try
		{
			$customer = $this->purchase->member;
		}
		catch ( OutOfRangeException )
		{
			$customer = NULL;
		}
				
		/* Display */
		Output::i()->sidebar['actions'] = $this->purchase->buttons();
		Output::i()->output = Theme::i()->getTemplate( 'purchases' )->view( $this->purchase, $customer, $children, $customFields );
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_members.js', 'core' ) );
	}

	/**
	 * Show the associated invoices table for a purchase
	 *
	 * @note	This is lazy loaded after the purchase page is generated
	 * @return	void
	 */
	protected function showInvoices() : void
	{
		/* Create associated invoices table */		
		try
		{
			$originalInvoice = $this->purchase->original_invoice;
			$invoices = Invoice::table( array( array( 'i_id=? OR ' . Db::i()->findInSet( 'i_renewal_ids', array( $this->purchase->id ) ), $originalInvoice->id ) ), $this->purchase->acpUrl()->setQueryString( 'do', 'showInvoices' ), "p.{$this->purchase->id}" );
		}
		catch ( OutOfRangeException )
		{
			$invoices = Invoice::table( array( array( Db::i()->findInSet( 'i_renewal_ids', array( $this->purchase->id ) ) ) ), $this->purchase->acpUrl()->setQueryString( 'do', 'showInvoices' ), "p.{$this->purchase->id}" );
		}
		$invoices->filters = array(
			'paid'		=> array( 'i_status=?', Invoice::STATUS_PAID )
		);
		$invoices->advancedSearch = array(
			'i_status'	=> array( SEARCH_SELECT, array( 'options' => Invoice::statuses(), 'multiple' => TRUE ) ),
			'i_total'	=> SEARCH_NUMERIC,
			'i_date'	=> SEARCH_DATE_RANGE,
		);

		Output::i()->output = (string) $invoices;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_edit' );
		
		/* Work stuff out */
		$renewals = $this->purchase->renewals ?: NULL;
		$groupedChildren = array();
		if ( $renewals and Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_parent=? AND ps_grouped_renewals<>? AND ps_cancelled=0', $this->purchase->id, '' ) )->first() )
		{
			$renewals = $this->purchase->renewals;
			foreach ( $this->purchase->children() as $child )
			{
				if ( $child->grouped_renewals and !$child->cancelled )
				{
					$groupedChildren[] = $child;
					$childGroupedRenewals = $child->grouped_renewals;

					/* Figure out tax */
					$tax = NULL;

					try
					{
						if( $this->purchase->tax )
						{
							$tax = Tax::load( $this->purchase->tax );
						}
					}
					catch( OutOfRangeException ){}

					try
					{
						$childGroupedRenewalTerm = new RenewalTerm( new Money( $childGroupedRenewals['price'], $this->purchase->renewals->cost->currency ), new DateInterval( 'P' . $childGroupedRenewals['term'] . mb_strtoupper( $childGroupedRenewals['unit'] ) ), $tax );
						$renewals = new RenewalTerm( $renewals->subtract( $childGroupedRenewalTerm ), $renewals->interval );
					}
					catch ( Exception ) { }
				}
			}
		}
				
		/* Build form */
		$form = new Form;
		$this->purchase->acpEdit( $form, $renewals );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Ungroup children */
			foreach ( $groupedChildren as $child )
			{
				$child->ungroupFromParent();
			}
			
			/* Update purchase */
			$this->purchase->acpEditSave( $values );
			
			/* Regroup children */
			foreach ( $groupedChildren as $child )
			{
				$child->groupWithParent();
			}
			
			/* Log */
			$this->purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $this->purchase->id, 'name' => $this->purchase->name ) );
			
			/* Redirect */
			$this->_redirect();
		}
		
		/* Display */
		Output::i()->output = $form;
	}
	
	/**
	 * Transfer
	 *
	 * @return	void
	 */
	protected function transfer() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_transfer' );
		if ( $this->purchase->grouped_renewals )
		{
			Output::i()->error( 'not_with_grouped', '2X195/6', 403, '' );
		}
		if ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/G', 403, '' );
		}
		
		/* Build form */
		$form = new Form;
		$form->add( new Form\Member( 'ps_member', NULL, TRUE ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Transfer */
			$previousOwner = $this->purchase->member;
			try
			{
				$this->purchase->transfer( $values['ps_member'] );
			}
			catch ( DomainException $e )
			{
				Output::i()->error( $e->getMessage(), '2X195/8', 403 );
			}
			
			/* Log */
			$previousOwner->log( 'purchase', array( 'type' => 'transfer_from', 'id' => $this->purchase->id, 'name' => $this->purchase->name, 'to' => $values['ps_member']->member_id ) );
			$this->purchase->member->log( 'purchase', array( 'type' => 'transfer_to', 'id' => $this->purchase->id, 'name' => $this->purchase->name, 'from' => $previousOwner->member_id ) );
			
			/* Redirect */
			$this->_redirect();
		}
		
		/* Display */
		Output::i()->output = $form;
	}
		
	/**
	 * Renew
	 *
	 * @return	void
	 */
	public function renew() : void
	{
		Dispatcher::i()->checkAcpPermission( 'invoices_add', 'nexus', 'payments' );
		
		if ( $this->purchase->grouped_renewals )
		{
			Output::i()->error( 'not_with_grouped', '2X195/7', 403, '' );
		}
		if ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/D', 403, '' );
		}
		
		/* If this purchase has renewal term, we cannot generate a renewal invoice */
		if ( !$this->purchase->renewals )
		{
			Output::i()->error( 'purchase_no_renew', '2X195/3', 403, '' );
		}
		
		/* Build form */
		$form = new Form;
		$cycles = $this->purchase->canRenewUntil( NULL, TRUE, TRUE );
		$form->add( new Number( 'renew_cycles', 1, TRUE, array( 'min' => 1, 'max' => ( $cycles === TRUE ? NULL : $cycles ) ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Generate Invoice */
			$invoice = new Invoice;
			$invoice->currency = $this->purchase->renewal_currency;
			$invoice->member = $this->purchase->member;
			$invoice->billaddress = $this->purchase->member->primaryBillingAddress();
			$invoice->addItem( Renewal::create( $this->purchase, $values['renew_cycles'] ) );
			$invoice->save();
			$invoice->sendNotification();
			
			/* Update the purchase */
			$this->purchase->invoice_pending = $invoice;
			$this->purchase->save();
			
			/* Take us to that invoice */
			Output::i()->redirect( $invoice->acpUrl() );
		}
		
		/* If this purchase already has an unpaid renewal invoice, display a warning */
		if ( $this->purchase->invoice_pending )
		{
			/* Which will be a proper warning if that invoice is still pending */
			if ( $this->purchase->invoice_pending->status === Invoice::STATUS_PENDING )
			{
				Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('warn_renew_invoice_pending', FALSE, array( 'sprintf' => array( $this->purchase->invoice_pending->acpUrl() ) ) ), 'warning', NULL, FALSE, TRUE );
			}
			/* Or just informational if it is canceled or expired */
			elseif ( $this->purchase->invoice_pending->status !== Invoice::STATUS_PAID )
			{
				Output::i()->output .= Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('info_renew_invoice_pending', FALSE, array( 'sprintf' => array( $this->purchase->invoice_pending->acpUrl() ) ) ), 'information', NULL, FALSE, TRUE );
			}
		}
		
		/* Display */
		Output::i()->output .= $form;
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 */
	protected function cancel() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_cancel' );
		
		$parent = $this->purchase->parent();
		
		if ( ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled ) or ( $this->purchase->grouped_renewals and $parent->billing_agreement and !$parent->billing_agreement->canceled ) )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/H', 403, '' );
		}
		
		/* What options are available for cancelling? */
		$options = array();
		if ( $this->purchase->renewals )
		{
			$options['no_renew'] = 'cancel_type_no_renew';
		}
		$options['cancel'] = 'cancel_type_cancel';
		
		/* Build form */
		$form = new Form;
		$form->add( new Radio( 'cancel_type', 'no_renew', FALSE, array(
			'options'	=> $options,
			'toggles'	=> array( 'cancel' => array( 'form_cancel_type_warning' ) )
		) ) );
		$form->add( new YesNo( 'ps_can_reactivate', FALSE ) );
		if ( $onCancelWarning = $this->purchase->onCancelWarning() )
		{
			Member::loggedIn()->language()->words['cancel_type_warning'] = $onCancelWarning;
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* If grouped, ungroup */
			$grouped = $this->purchase->grouped_renewals;
			if ( $grouped )
			{
				$this->purchase->ungroupFromParent();
				$this->purchase->save();
			}
			
			/* Update purchase and log */
			if ( $values['cancel_type'] === 'no_renew' )
			{
				$this->purchase->renewals = NULL;
				$this->purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $this->purchase->id, 'name' => $this->purchase->name, 'info' => 'remove_renewals' ) );
			}
			else
			{
				$this->purchase->cancelled = TRUE;
				$this->purchase->member->log( 'purchase', array( 'type' => 'cancel', 'id' => $this->purchase->id, 'name' => $this->purchase->name ) );
			}
			$this->purchase->can_reactivate = $values['ps_can_reactivate'];
			$this->purchase->save();
			
			/* If grouped, regroup */
			if ( $grouped )
			{
				$this->purchase->groupWithParent();
				$this->purchase->save();
			}
			
			/* Redirect */
			$this->_redirect();
		}
		
		/* Display form */
		Output::i()->output = $form;
	}
	
	/**
	 * Reactivate
	 *
	 * @return	void
	 */
	protected function reactivate() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_cancel' );
		Session::i()->csrfCheck();
		
		$error = NULL;
		if ( !$this->purchase->canAcpReactivate( $error ) )
		{
			Output::i()->error( $error ?? 'can_not_reactivate', '2X195/K', 403, '' );
		}
		
		$parent = $this->purchase->parent();
		
		if ( ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled ) or ( $this->purchase->grouped_renewals and $parent->billing_agreement and !$parent->billing_agreement->canceled ) )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/I', 403, '' );
		}
		
		/* If grouped, ungroup */
		$grouped = $this->purchase->grouped_renewals;
		if ( $grouped )
		{
			$this->purchase->ungroupFromParent();
			$this->purchase->save();
		}
		
		/* Do it */
		$this->purchase->cancelled = FALSE;
		$this->purchase->save();
		$this->purchase->member->log( 'purchase', array( 'type' => 'uncancel', 'id' => $this->purchase->id, 'name' => $this->purchase->name ) );
		
		/* If grouped, regroup */
		if ( $grouped )
		{
			$this->purchase->groupWithParent();
			$this->purchase->save();
		}
		
		/* Redirect */
		$this->_redirect();
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_delete' );
		Request::i()->confirmedDelete();

		$parent = $this->purchase->parent();
		
		if ( ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled ) or ( $this->purchase->grouped_renewals and $parent->billing_agreement and !$parent->billing_agreement->canceled ) )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/J', 403, '' );
		}
		
		if ( $this->purchase->grouped_renewals )
		{
			$this->purchase->ungroupFromParent();
			$this->purchase->save();
		}
		
		$this->purchase->member->log( 'purchase', array( 'type' => 'delete', 'id' => $this->purchase->id, 'name' => $this->purchase->name ) );
		$this->purchase->delete();
		
		if ( $parent )
		{
			Output::i()->redirect( $parent->acpUrl() );
		}
		else
		{
			Output::i()->redirect( $this->purchase->member->acpUrl() );
		}
	}
	
	/**
	 * Extra
	 *
	 * @return	void
	 */
	protected function extra() : void
	{
		if ( $output = $this->purchase->acpAction() )
		{
			Output::i()->output = $output;
		}
		else
		{
			Output::i()->redirect( $this->purchase->acpUrl() );
		}
	}
	
	/**
	 * Group
	 *
	 * @return	void
	 */
	protected function group() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_edit' );
		Session::i()->csrfCheck();
		
		if ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/E', 403, '' );
		}
		
		try
		{
			$this->purchase->groupWithParent();
			$this->purchase->member->log( 'purchase', array( 'type' => 'group', 'id' => $this->purchase->id, 'name' => $this->purchase->name ) );
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'grouperr_' . $e->getMessage(), '1X195/4', 403, '' );
		}
		$this->_redirect();
	}
	
	/**
	 * Ungroup
	 *
	 * @return	void
	 */
	protected function ungroup() : void
	{
		Dispatcher::i()->checkAcpPermission( 'purchases_edit' );
		Session::i()->csrfCheck();
		
		if ( $this->purchase->billing_agreement and !$this->purchase->billing_agreement->canceled )
		{
			Output::i()->error( 'not_with_billing_agreement', '2X195/F', 403, '' );
		}
		
		try
		{
			$this->purchase->ungroupFromParent();
			$this->purchase->member->log( 'purchase', array( 'type' => 'ungroup', 'id' => $this->purchase->id, 'name' => $this->purchase->name ) );
		}
		catch ( LogicException )
		{
			Output::i()->error( '', '1X195/5', 403, '' );
		}
		$this->_redirect();
	}
	
	/**
	 * Redirect
	 *
	 * @return	void
	 */
	protected function _redirect() : void
	{
		if ( isset( Request::i()->r ) )
		{
			switch ( mb_substr( Request::i()->r, 0, 1 ) )
			{
				case 'p':
					try
					{
						Output::i()->redirect( Purchase::load( $this->purchase->parent )->acpUrl() );
					}
					catch ( OutOfRangeException )
					{
						Output::i()->redirect( $this->purchase->member->acpUrl() );
					}
					break;

				case 'v':
					Output::i()->redirect( $this->purchase->acpUrl() );
					break;
										
				case 'c':
					Output::i()->redirect( $this->purchase->member->acpUrl() );
					break;
			}
		}
		
		Output::i()->redirect( $this->purchase->acpUrl() );
	}
}