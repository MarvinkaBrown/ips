<?php
/**
 * @brief		View Customer
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		11 Feb 2014
 */

namespace IPS\nexus\modules\admin\customers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Customer\Address;
use IPS\nexus\Customer\AlternativeContact;
use IPS\nexus\Customer\CreditCard;
use IPS\nexus\Customer\CustomField;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Tax;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View
 */
class view extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * @brief	Member
	 */
	protected Customer $member;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_view' );
		
		try
		{
			$this->member = Customer::load( Request::i()->id );
			if ( !$this->member->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X233/1', 404, '' );
		}
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customer.css', 'nexus', 'admin' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_customer.js', 'nexus', 'admin' ) );
		Output::i()->title = "{$this->member->cm_name}";
		
		parent::execute();
	}

	/**
	 * View Customer
	 *
	 * @return	void
	 * @deprecated
	 */
	protected function manage() : void
	{
		Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/**
	 * View Addresses
	 *
	 * @return	void
	 */
	protected function addresses() : void
	{
		$addresses = new \IPS\Helpers\Table\Db( 'nexus_customer_addresses', Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( 'view', 'addresses' ), array( '`member`=?', $this->member->member_id ) );
		$addresses->sortBy = 'primary_billing, added';
		$addresses->include = array( 'address', 'primary_billing' );
		$addresses->parsers = array( 'address' => function( $val )
		{
			$address = GeoLocation::buildFromJson( $val );
			return $address->toString( '<br>' ) . ( ( isset( $address->business ) and $address->business and isset( $address->vat ) and $address->vat ) ? ( '<br>' . Member::loggedIn()->language()->addToStack('cm_checkout_vat_number') . ': ' . Theme::i()->getTemplate( 'global', 'nexus' )->vatNumber( $address->vat ) ) : '' );
		} );
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_details' ) )
		{
			$addresses->rootButtons = array(
				'add'	=> array(
					'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( 'do', 'addressForm' ),
					'title'	=> 'add',
					'icon'	=> 'plus',
					'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add_address') )
				)
			);
			$addresses->rowButtons = function( $row )
			{
				return array(
					'edit'	=> array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'addressForm', 'address_id' => $row['id'] ) ),
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit_address') )
					),
					'delete'	=> array(
						'link'	=> Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array( 'do' => 'deleteAddress', 'address_id' => $row['id'] ) ),
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'data'	=> array( 'delete' => '' )
					)
				);
			};
		}
	
		$addresses->tableTemplate = array( Theme::i()->getTemplate('customers'), 'addressTable' );
		$addresses->rowsTemplate = array( Theme::i()->getTemplate('customers'), 'addressTableRows' );
		Output::i()->output = Theme::i()->getTemplate('customers')->customerPopup( $addresses );
	}
		
	/**
	 * Edit Customer Fields
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		
		$form = new Form;
		
		$form->add( new Text( 'cm_first_name', $this->member->cm_first_name, FALSE ) );
		$form->add( new Text( 'cm_last_name', $this->member->cm_last_name, FALSE ) );
		
		foreach ( CustomField::roots() as $field )
		{
			/* @var CustomField $field */
			$column = $field->column;
			if ( $field->type === 'Editor' )
			{
				$field::$editorOptions = array_merge( $field::$editorOptions, array( 'attachIds' => array( $this->member->member_id ) ) );
			}
			$form->add( $field->buildHelper( $this->member->$column ) );
		}
		
		if ( $values = $form->values(TRUE) )
		{
			$changes = array();
			foreach ( array( 'cm_first_name', 'cm_last_name' ) as $k )
			{
				if ( $values[ $k ] != $this->member->$k )
				{
					/* We only need to log this once, so do it if it isn't set */
					if ( !isset( $changes['name'] ) )
					{
						$changes['name'] = $this->member->cm_name;
					}
					
					$this->member->$k = $values[ $k ];
				}
			}
			foreach ( CustomField::roots() as $field )
			{
				/* @var CustomField $field */
				$column = $field->column;
				if ( $this->member->$column != $values["nexus_ccfield_{$field->id}"] )
				{
					$changes['other'][] = array( 'name' => 'nexus_ccfield_' . $field->id, 'value' => $field->displayValue( $values["nexus_ccfield_{$field->id}"] ), 'old' => $this->member->$column );
					$this->member->$column = $values["nexus_ccfield_{$field->id}"];
				}
				
				if ( $field->type === 'Editor' )
				{
					$field->claimAttachments( $this->member->member_id );
				}
			}
			if ( !empty( $changes ) )
			{
				$this->member->log( 'info', $changes );
			}
			$this->member->save();
			Output::i()->redirect( $this->member->acpUrl() );
		}
		
		Output::i()->output = $form;
	}
	
	/**
	 * Edit Credits
	 *
	 * @return	void
	 */
	public function credits() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_edit_credit' );
		
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--edit-credits';
		foreach ( Money::currencies() as $currency )
		{
			$form->add( new Form\Number( $currency, isset( $this->member->cm_credits[ $currency ] ) ? $this->member->cm_credits[ $currency ]->amount : 0, FALSE, array( 'min' => 0, 'decimals' => Money::numberOfDecimalsForCurrency( $currency ) ), NULL, NULL, $currency ) );
		}
		
		if ( $values = $form->values() )
		{
			$credits = $this->member->cm_credits;
			foreach ( $values as $currency => $amount )
			{
				$amount = new Number( number_format( $amount, Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) );
				if ( ( isset( $this->member->cm_credits[ $currency ] ) and $this->member->cm_credits[ $currency ]->amount->compare( $amount ) !== 0 ) or $amount )
				{
					$this->member->log( 'comission', array( 'type' => 'manual', 'old' => isset( $this->member->cm_credits[ $currency ] ) ? $this->member->cm_credits[ $currency ]->amountAsString() : 0, 'new' => (string) $amount, 'currency' => $currency ) );
				}
				$credits[ $currency ] = new Money( $amount, $currency );
			}
			$this->member->cm_credits = $credits;
			$this->member->save();
			Output::i()->redirect( $this->member->acpUrl() );
		}
		
		Output::i()->output = (string) $form;
	}
	
	/**
	 * Add/Edit Note
	 *
	 * @return	void
	 */
	public function noteForm() : void
	{
		$noteId = NULL;
		$note = NULL;
		if ( Request::i()->note_id )
		{
			Dispatcher::i()->checkAcpPermission( 'customer_notes_edit' );
			$noteId = intval( Request::i()->note_id );
			try
			{
				$note = Db::i()->select( 'note_text', 'nexus_notes', array( 'note_id=?', Request::i()->note_id ) )->first();
			}
			catch ( UnderflowException )
			{
				Output::i()->error( 'node_error', '2X233/3', 404, '' );
			}
		}
		else
		{
			Dispatcher::i()->checkAcpPermission( 'customer_notes_add' );
		}
		
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--customer-notes';
		$form->add( new Editor( 'customer_note', $note, TRUE, array(
			'app'			=> 'nexus',
			'key'			=> 'Customer',
			'autoSaveKey'	=> $noteId ? "nexus-note-{$this->member->member_id}-{$noteId}" : "nexus-note-{$this->member->member_id}-new",
			'attachIds'		=> $noteId ? array( $this->member->member_id, $noteId, 'note' ) : NULL
		) ) );
		if ( $values = $form->values() )
		{
			if ( Request::i()->note_id )
			{
				Db::i()->update( 'nexus_notes', array(
					'note_text'	=> $values['customer_note']
				), array( 'note_id=?', Request::i()->note_id ) );
				
				$this->member->log( 'note', 'edited' );
			}
			else
			{
				$noteId = Db::i()->insert( 'nexus_notes', array(
					'note_member'	=> $this->member->member_id,
					'note_text'		=> $values['customer_note'],
					'note_author'	=> Member::loggedIn()->member_id,
					'note_date'		=> time(),
				) );
				
				File::claimAttachments( "nexus-note-{$this->member->member_id}-new", $this->member->member_id, $noteId, 'note' );
				
				$this->member->log( 'note', 'added' );
			}

			Output::i()->redirect( $this->member->acpUrl() );
		}
		Output::i()->output = $form;
	}
	
	/** 
	 * Delete Note
	 *
	 * @return	void
	 */
	public function deleteNote() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customer_notes_delete' );
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		Db::i()->delete( 'nexus_notes', array( 'note_id=?', Request::i()->note_id ) );
		$this->member->log( 'note', 'deleted' );
		
		Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/**
	 * Add Address
	 *
	 * @return	void
	 */
	public function addressForm() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		
		if ( Request::i()->address_id )
		{
			try
			{
				$address = Address::load( Request::i()->address_id );
				if ( $address->member !== $this->member )
				{
					throw new OutOfRangeException;
				}
			}
			catch ( OutOfRangeException )
			{
				Output::i()->error( 'node_error', '2X233/2', 404, '' );
			}
		}
		else
		{
			$address = new Address;
			$address->member = $this->member;
			$address->primary_billing = ( Db::i()->select( 'COUNT(*)', 'nexus_customer_addresses', array( '`member`=? AND primary_billing=1', $this->member->member_id ) )->first() == 0 );
		}
		
		$needTaxStatus = NULL;
		foreach ( Tax::roots() as $tax )
		{
			if ( $tax->type === 'eu' )
			{
				$needTaxStatus = 'eu';
				break;
			}
			if ( $tax->type === 'business' )
			{
				$needTaxStatus = 'business';
			}
		}
		$addressHelperClass = $needTaxStatus ? 'IPS\nexus\Form\BusinessAddress' : 'IPS\Helpers\Form\Address';
		$addressHelperOptions = ( $needTaxStatus === 'eu' ) ? array( 'vat' => TRUE ) : array();
		
		$form = new Form;
		$form->add( new $addressHelperClass( 'address', $address->address, TRUE, $addressHelperOptions ) );
		$form->add( new YesNo( 'primary_billing', $address->primary_billing ) );
		if ( $values = $form->values() )
		{
			if ( $address->id )
			{
				if ( $values['address'] != $address->address )
				{
					$this->member->log( 'address', array( 'type' => 'edit', 'new' => json_encode( $values['address'] ), 'old' => json_encode( $address->address ) ) );
				}
				if ( $values['primary_billing'] and !$address->primary_billing )
				{
					Db::i()->update( 'nexus_customer_addresses', array( 'primary_billing' => 0 ), array( '`member`=?', $this->member->member_id ) );
					$this->member->log( 'address', array( 'type' => 'primary_billing', 'details' => json_encode( $values['address'] ) ) );
				}
			}
			else
			{
				$this->member->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['address'] ) ) );
			}
			
			$address->address = $values['address'];
			$address->primary_billing = $values['primary_billing'];
			$address->save();
			
			Output::i()->redirect( $this->member->acpUrl() );
		}
		Output::i()->output = $form;
	}
	
	/** 
	 * Delete Address
	 *
	 * @return	void
	 */
	public function deleteAddress() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$address = Address::load( Request::i()->address_id );
			$this->member->log( 'address', array( 'type' => 'delete', 'details' => json_encode( $address->address ) ) );
			$address->delete();
		}
		catch ( OutOfRangeException ) { }
		Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/** 
	 * Add Card
	 *
	 * @csrfChecked	Uses Form helper in Gateway classes 7 Oct 2019
	 * @return	void
	 */
	public function addCard() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_gateways.js', 'nexus', 'global' ) );
		$form = CreditCard::create( $this->member, TRUE );
		if ( $form instanceof CreditCard )
		{
			$this->member->log( 'card', array( 'type' => 'add', 'number' => $form->card->lastFour ) );
			Output::i()->redirect( $this->member->acpUrl() );
		}
		else
		{
			Output::i()->output = $form;
		}		
	}
	
	/** 
	 * Delete Card
	 *
	 * @return	void
	 */
	public function deleteCard() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		try
		{
			$card = CreditCard::load( Request::i()->card_id );
			$this->member->log( 'card', array( 'type' => 'delete', 'number' => $card->card->lastFour ) );
			$card->delete();
		}
		catch ( OutOfRangeException ) { }
		Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/**
	 * Add/Edit Alternative Contact
	 *
	 * @return	void
	 */
	public function alternativeContactForm() : void
	{
		$existing = NULL;
		if ( isset( Request::i()->alt_id ) )
		{
			try
			{
				$existing = AlternativeContact::constructFromData( Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', $this->member->member_id, Request::i()->alt_id ) )->first() );
			}
			catch ( UnderflowException ) {}
		}
				
		$form = new Form;
		if ( !$existing )
		{
			$form->add( new Form\Member( 'altcontact_member_admin', NULL, TRUE, array(), function( $val )
			{
				if( $this->member->member_id === $val->member_id )
				{
					throw new DomainException('altcontact_member_admin_self');
				}
			} ) );
		}
		$form->add( new Node( 'altcontact_purchases_admin', $existing ? iterator_to_array( $existing->purchases ) : NULL, FALSE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => $this->member, 'multiple' => TRUE ) ) );
		$form->add( new YesNo( 'altcontact_billing_admin', $existing ? $existing->billing : FALSE ) );
		if ( $values = $form->values() )
		{
			if ( $existing )
			{
				$altContact = $existing;
				$this->member->log( 'alternative', array( 'type' => 'edit', 'alt_id' => $altContact->alt_id->member_id, 'alt_name' => $altContact->alt_id->name, 'purchases' => json_encode( $values['altcontact_purchases_admin'] ?: array() ), 'billing' => $values['altcontact_billing_admin'] ) );
			}
			else
			{
				$altContact = new AlternativeContact;
				$altContact->main_id = $this->member;
				$altContact->alt_id = $values['altcontact_member_admin'];
				$this->member->log( 'alternative', array( 'type' => 'add', 'alt_id' => $values['altcontact_member_admin']->member_id, 'alt_name' => $values['altcontact_member_admin']->name, 'purchases' => json_encode( $values['altcontact_purchases_admin'] ?: array() ), 'billing' => $values['altcontact_billing_admin'] ) );
			}
			$altContact->purchases = $values['altcontact_purchases_admin'] ?: array();
			$altContact->billing = $values['altcontact_billing_admin'];
			$altContact->save();
			
			Output::i()->redirect( $this->member->acpUrl() );
		}
		Output::i()->output = $form;
	}
	
	/** 
	 * Delete Alternative Contact
	 *
	 * @return	void
	 */
	public function deleteAlternativeContact() : void
	{
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$contact = AlternativeContact::constructFromData( Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', $this->member->member_id, Request::i()->alt_id ) )->first() );
			$this->member->log( 'alternative', array( 'type' => 'delete', 'alt_id' => $contact->alt_id->member_id, 'alt_name' => $contact->alt_id->name ) );
			$contact->delete();
		}
		catch ( OutOfRangeException ) { }
		Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/** 
	 * Void Account
	 *
	 * @return	void
	 */
	public function void() : void
	{
		Dispatcher::i()->checkAcpPermission( 'customers_void' );
		
		if ( isset( Request::i()->process ) )
		{
			Session::i()->csrfCheck();
			$values = array(
				'void_refund_transactions'			=> Request::i()->trans,
				'void_cancel_billing_agreements'	=> Request::i()->ba,
				'void_cancel_purchases' 			=> Request::i()->purch,
			);
		}
		else
		{		
			$form = new Form( 'void_account', 'void_account' );
			$form->ajaxOutput = TRUE;
			$form->addMessage( 'void_account_warning' );
			$form->add( new YesNo( 'void_refund_transactions', TRUE ) );
			if ( Gateway::billingAgreementGateways() )
			{
				$form->add( new YesNo( 'void_cancel_billing_agreements', TRUE ) );
			}
			$form->add( new YesNo( 'void_cancel_purchases', TRUE ) );
			$form->add( new YesNo( 'void_cancel_invoices', TRUE ) );
			if ( $this->member->member_id != Member::loggedIn()->member_id )
			{
				$form->add( new YesNo( 'void_ban_account', TRUE ) );
			}
			$form->add( new Editor( 'void_add_note', NULL, FALSE, array(
				'app'			=> 'nexus',
				'key'			=> 'Customer',
				'autoSaveKey'	=> "nexus-note-{$this->member->member_id}-new",
				'minimize'		=> 'void_add_note_placeholder'
			) ) );
			
			if ( $values = $form->values() )
			{
				if ( $values['void_cancel_invoices'] )
				{
					Db::i()->update( 'nexus_invoices', array( 'i_status' => Invoice::STATUS_CANCELED ), array( 'i_member=? AND i_status<>?', $this->member->member_id, Invoice::STATUS_PAID ) );
				}
				if ( $this->member->member_id != Member::loggedIn()->member_id and $values['void_ban_account'] )
				{
					$this->member->temp_ban = -1;
					$this->member->save();
				}
				if ( $values['void_add_note'] )
				{
					$noteId = Db::i()->insert( 'nexus_notes', array(
						'note_member'	=> $this->member->member_id,
						'note_text'		=> $values['void_add_note'],
						'note_author'	=> Member::loggedIn()->member_id,
						'note_date'		=> time(),
					) );
					
					File::claimAttachments( "nexus-note-{$this->member->member_id}-new", $this->member->id, $noteId, 'note' );
				}
				
				if ( !$values['void_refund_transactions'] and !$values['void_cancel_purchases'] and !$values['void_cancel_billing_agreements'] )
				{
					Output::i()->redirect( $this->member->acpUrl() );
				}
			}
		}
		
		if ( $values )
		{
			$member = $this->member;
						
			Output::i()->output = new MultipleRedirect( Url::internal("app=nexus&module=customers&controller=view&id={$this->member->member_id}")->setQueryString( array(
				'do'		=> 'void',
				'process'	=> 1,
				'trans'		=> $values['void_refund_transactions'],
				'ba'		=> $values['void_cancel_billing_agreements'] ?? FALSE,
				'purch'		=> $values['void_cancel_purchases'],
			) )->csrf(), function( $data ) use ( $member )
			{		
				if ( !is_array( $data ) )
				{
					$data = array( 'trans' => 0, 'ba' => 0, 'purch' => 0, 'fail' => array() );
				}
				
				$done = 0;
				
				if ( Request::i()->trans )
				{
					foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_transactions', array( 't_member=?', $member->member_id ), 't_id', array( $data['trans'], 10 ) ), 'IPS\nexus\Transaction' ) as $transaction )
					{
						/* @var Transaction $transaction */
						if ( in_array( $transaction->status, array( $transaction::STATUS_PENDING, $transaction::STATUS_WAITING, $transaction::STATUS_GATEWAY_PENDING ) ) )
						{
							$transaction->status = $transaction::STATUS_REVIEW;
							$transaction->save();
						}
						elseif ( in_array( $transaction->status, array( $transaction::STATUS_PAID, $transaction::STATUS_HELD, $transaction::STATUS_REVIEW, $transaction::STATUS_PART_REFUNDED ) ) )
						{
							try
							{
								if ( $transaction->auth and in_array( $transaction->status, array( $transaction::STATUS_HELD, $transaction::STATUS_REVIEW ) ) )
								{
									$transaction->void();
								}
								else
								{
									$transaction->refund();
								}
								
								$transaction->invoice->markUnpaid( Invoice::STATUS_CANCELED, Member::loggedIn() );
							}
							catch ( Exception )
							{
								$data['fail'][] = $transaction->id;
							}
						}
						
						$data['trans']++;
						$done++;
						if ( $done >= 10 )
						{
							return array( $data, Member::loggedIn()->language()->addToStack('processing') );
						}
					}
				}
				
				if ( Request::i()->ba )
				{
					foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_member=?', $member->member_id ), 'ba_id', array( $data['ba'], 10 ) ), 'IPS\nexus\Customer\BillingAgreement' ) as $billingAgreement )
					{
						/* @var Customer\BillingAgreement $billingAgreement */
						try
						{
							$billingAgreement->cancel();
						}
						catch ( Exception ) { }
						
						$data['ba']++;
						$done++;
						if ( $done >= 10 )
						{
							return array( $data, Member::loggedIn()->language()->addToStack('processing') );
						}
					}
				}
				
				if ( Request::i()->purch )
				{
					foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_purchases', array( 'ps_member=?', $member->member_id ), 'ps_id', array( $data['purch'], 10 ) ), 'IPS\nexus\Purchase' ) as $purchase )
					{
						$purchase->cancelled = TRUE;
						$purchase->can_reactivate = FALSE;
						$purchase->save();
						
						$data['purch']++;
						$done++;
						if ( $done >= 10 )
						{
							return array( $data, Member::loggedIn()->language()->addToStack('processing') );
						}
					}
				}
				
				$_SESSION['voidAccountFails'] = $data['fail'];
				return NULL;
			}, function() use ( $member )
			{
				if ( count( $_SESSION['voidAccountFails'] ) )
				{
					Output::i()->redirect( $member->acpUrl()->setQueryString( 'do', 'voidFails' ) );
				}
				else
				{
					Output::i()->redirect( $member->acpUrl() );
				}
			} );
		}
		else
		{
			Output::i()->output = $form;
		}
	}
	
	/** 
	 * Void Account Results
	 *
	 * @return	void
	 */
	public function voidFails() : void
	{
		Output::i()->output = Theme::i()->getTemplate( 'customers' )->voidFails( $_SESSION['voidAccountFails'] );
	}
}