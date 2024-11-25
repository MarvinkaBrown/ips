<?php
/**
 * @brief		Cards
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		08 May 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\nexus\Customer;
use IPS\nexus\Customer\CreditCard;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Cards
 */
class cards extends Controller
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
			Output::i()->error( 'no_module_permission_guest', '2X236/1', 403, '' );
		}
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards' ), Member::loggedIn()->language()->addToStack('client_cards') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_cards');
		Output::i()->sidebar['enabled'] = FALSE;
		
		if ( $output = MFAHandler::accessToArea( 'nexus', 'Cards', Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards' ) ) )
		{
			Output::i()->output = Theme::i()->getTemplate('clients')->cards( array() ) . $output;
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
		$cards = array();
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', Customer::loggedIn()->member_id ) ), 'IPS\nexus\Customer\CreditCard' ) as $card )
		{
			try
			{
				$cardData = $card->card;
				$cards[ $card->id ] = array(
					'id'				=> $card->id,
					'card_type'			=> $cardData->type,
					'card_number'		=> $cardData->lastFour ?: $cardData->number,
					'card_expire'		=> ( !is_null( $cardData->expMonth ) AND !is_null( $cardData->expYear ) ) ? str_pad( $cardData->expMonth , 2, '0', STR_PAD_LEFT ). '/' . $cardData->expYear : NULL
				);
			}
			catch ( Exception ) { }
		}
				
		Output::i()->output = Theme::i()->getTemplate('clients')->cards( $cards );
	}
	
	/**
	 * Add
	 *
	 * @return	void
	 */
	public function add() : void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'checkout.css', 'nexus' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_gateways.js', 'nexus', 'global' ) );
		$form = CreditCard::create( Customer::loggedIn(), FALSE );
		if ( $form instanceof CreditCard )
		{
			Customer::loggedIn()->log( 'card', array( 'type' => 'add', 'number' => $form->card->lastFour ) );
			Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards' ) );
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
	public function delete() : void
	{
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$card = CreditCard::load( Request::i()->id );
			if ( $card->member->member_id === Customer::loggedIn()->member_id )
			{
				$cardData = $card->card;

				$card->delete(); 
				Customer::loggedIn()->log( 'card', array( 'type' => 'delete', 'number' => $cardData->lastFour ) );
			}
		}
		catch ( Exception ) { }
		
		Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards' ) );
	}
}