<?php
/**
 * @brief		Addresses
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		06 May 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\nexus\Customer;
use IPS\nexus\Customer\Address;
use IPS\nexus\Tax;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Addresses
 */
class addresses extends Controller
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
			Output::i()->error( 'no_module_permission_guest', '2X235/1', 403, '' );
		}
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_forms.js', 'nexus', 'global' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses' ), Member::loggedIn()->language()->addToStack('client_addresses') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_addresses');
		Output::i()->sidebar['enabled'] = FALSE;
		
		if ( $output = MFAHandler::accessToArea( 'nexus', 'Addresses', Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses' ) ) )
		{
			Output::i()->output = Theme::i()->getTemplate('clients')->addresses( NULL, NULL, array() ) . $output;
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
		$addresses = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_customer_addresses', array( '`member`=?', Member::loggedIn()->member_id ) ), 'IPS\nexus\Customer\Address' );

		$billingAddress = NULL;
		$otherAddresses = array();

		foreach ( $addresses as $address )
		{
			if( $address->primary_billing )
			{
				$billingAddress = $address;
			}
			else
			{
				$otherAddresses[] = $address;
			}
		}

		Output::i()->output = Theme::i()->getTemplate('clients')->addresses( $billingAddress, $otherAddresses );
	}
	
	/**
	 * Add/Edit
	 *
	 * @return	void
	 */
	protected function form() : void
	{
		$existing = NULL;
		if ( isset( Request::i()->id ) )
		{
			try
			{
				$existing = Address::load( Request::i()->id );
				if ( $existing->member->member_id != Member::loggedIn()->member_id )
				{
					throw new OutOfRangeException;
				}
			}
			catch ( OutOfRangeException )
			{
				$existing = NULL;
			}
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
		$form->add( new $addressHelperClass( 'address', $existing?->address, TRUE, $addressHelperOptions ) );
		
		if ( $values = $form->values() )
		{
			if ( !$existing )
			{
				$existing = new Address;
				$existing->member = Member::loggedIn();
				$existing->primary_billing = !Db::i()->select( 'count(*)', 'nexus_customer_addresses', array( '`member`=? AND primary_billing=1', Member::loggedIn()->member_id ) )->first();

				Customer::loggedIn()->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['address'] ) ) );
			}
			else
			{
				Customer::loggedIn()->log( 'address', array( 'type' => 'edit', 'new' => json_encode( $values['address'] ), 'old' => json_encode( $existing->address ) ) );
			}
			
			$existing->address = $values['address'];
			$existing->save();
			
			Request::i()->setCookie( 'location', NULL );
			
			Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses' ) );
		}

		if ( Request::i()->isAjax() )
		{
			$form->class = 'ipsForm--vertical ipsForm--edit-address ipsForm--noLabels';
			Output::i()->sendOutput( $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) );
		}
		else
		{
			Output::i()->output = $form;
		}		
	}
	
	/**
	 * Make Primary
	 *
	 * @return	void
	 */
	protected function primary() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$address = Address::load( Request::i()->id );
			if ( $address->member->member_id == Member::loggedIn()->member_id )
			{
				Db::i()->update( 'nexus_customer_addresses', array( 'primary_billing' => 0 ), array( '`member`=?', Member::loggedIn()->member_id ) );
				$address->primary_billing = TRUE;
				$address->save();
				
				Customer::loggedIn()->log( 'address', array( 'type' => 'primary_billing', 'details' => json_encode( $address->address ) ) );
			}
		}
		catch ( OutOfRangeException ) {}
		
		Request::i()->setCookie( 'location', NULL );

		Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses' ) );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		Session::i()->csrfCheck();
		
		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$address = Address::load( Request::i()->id );
			if ( $address->member->member_id == Member::loggedIn()->member_id )
			{
				$address->delete();
				Customer::loggedIn()->log( 'address', array( 'type' => 'delete', 'details' => json_encode( $address->address ) ) );
			}
		}
		catch ( OutOfRangeException ) {}

		Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses' ) );
	}
}