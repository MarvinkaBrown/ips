<?php
/**
 * @brief		Alternative Contacts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		08 May 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Email;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\nexus\Customer;
use IPS\nexus\Customer\AlternativeContact;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Alternative Contacts
 */
class alternatives extends Controller
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
			Output::i()->error( 'no_module_permission_guest', '2X237/1', 403, '' );
		}

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives' ), Member::loggedIn()->language()->addToStack('client_alternatives') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_alternatives');
		Output::i()->sidebar['enabled'] = FALSE;

		if ( $output = MFAHandler::accessToArea( 'nexus', 'Alternatives', Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives' ) ) )
		{
			Output::i()->output = Theme::i()->getTemplate('clients')->alternatives( TRUE ) . $output;
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
		foreach ( Customer::loggedIn()->alternativeContacts() as $contact )
		{
			$contact->alt_id;
		}

		Output::i()->output = Theme::i()->getTemplate('clients')->alternatives();
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
				$existing = AlternativeContact::constructFromData( Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', Customer::loggedIn()->member_id, Request::i()->id ) )->first() );
			}
			catch ( UnderflowException ) {}
		}

		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--edit-alernatives';
		if ( !$existing )
		{
			$form->add( new Email( 'altcontact_email', NULL, TRUE, array(), function( $val )
			{
				if( Member::loggedIn()->email == $val )
				{
					throw new DomainException('altcontact_email_self');
				}

				$member = Member::load( $val, 'email' );
				if ( !$member->member_id )
				{
					throw new DomainException('altcontact_email_error');
				}

				try
				{
					Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', Customer::loggedIn()->member_id, $member->member_id ) )->first();
					throw new DomainException('altcontact_already_exists');
				}
				catch ( UnderflowException ) {}
			} ) );
		}
		$form->add( new Node( 'altcontact_purchases', $existing ? iterator_to_array( $existing->purchases ) : NULL, FALSE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => Member::loggedIn(), 'multiple' => TRUE ) ) );
		$form->add( new Checkbox( 'altcontact_billing', $existing ? $existing->billing : FALSE ) );
		if ( $values = $form->values() )
		{
			if ( $existing )
			{
				$altContact = $existing;
				Customer::loggedIn()->log( 'alternative', array( 'type' => 'edit', 'alt_id' => $altContact->alt_id->member_id, 'alt_name' => $altContact->alt_id->name, 'purchases' => json_encode( $values['altcontact_purchases'] ?: array() ), 'billing' => $values['altcontact_billing'] ) );
			}
			else
			{
				$altContact = new AlternativeContact;
				$altContact->main_id = Customer::loggedIn();
				$altContact->alt_id = Member::load( $values['altcontact_email'], 'email' );
				Customer::loggedIn()->log( 'alternative', array( 'type' => 'add', 'alt_id' => $altContact->alt_id->member_id, 'alt_name' => $altContact->alt_id->name, 'purchases' => json_encode( $values['altcontact_purchases'] ?: array() ), 'billing' => $values['altcontact_billing'] ) );
			}
			$altContact->purchases = $values['altcontact_purchases'] ?: array();
			$altContact->billing = $values['altcontact_billing'];
			$altContact->save();

			Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives' ) );
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) );
		}
		else
		{
			Output::i()->output = $form;
		}
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
			$contact = AlternativeContact::constructFromData( Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', Customer::loggedIn()->member_id, Request::i()->id ) )->first() );
			$contact->delete();
			Customer::loggedIn()->log( 'alternative', array( 'type' => 'delete', 'alt_id' => $contact->alt_id->member_id, 'alt_name' => $contact->alt_id->name ) );
		}
		catch (UnderflowException ) {}

		Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives' ) );
	}
}