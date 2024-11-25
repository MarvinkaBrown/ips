<?php
/**
 * @brief		Donations
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Jun 2014
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Donation\Goal;
use IPS\nexus\extensions\nexus\Item\Donation;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\Output;
use IPS\Request;
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
 * Donations
 */
class donations extends Controller
{		
	/**
	 * View List
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( isset( Request::i()->id ) and !Request::i()->isAjax() )
		{
			Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=donations', 'front', 'clientsdonations' ) );
		}
		
		Output::i()->breadcrumb[] = array( Url::internal( 'app=nexus&module=clients&controller=donations', 'front', 'clientsdonations' ), Member::loggedIn()->language()->addToStack('client_donations') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('client_donations');
		Output::i()->sidebar['enabled'] = FALSE;

		/* "Nowt 'ere lad, may as well knock it over tut 404" - Marc */
		if( count( Goal::roots() ) === 0 )
		{
			Output::i()->error( 'nexus_no_donations_found', '1X238/2', 404, '' );
		}

		Output::i()->output = Theme::i()->getTemplate('clients')->donations();
	}
	
	/**
	 * Make Donation
	 *
	 * @return	void
	 */
	public function donate() : void
	{
		if( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '1X238/3', 403, '' );
		}
		try
		{
			$goal = Goal::load( Request::i()->id );
		}
		catch ( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2X238/1', 404, '' );
		}
		
		$form = new Form( 'donate', 'donate' );
		$form->class = 'ipsForm--vertical ipsForm--donate';
		if ( !isset( Request::i()->noDesc ) and $desc = Member::loggedIn()->language()->get("nexus_donategoal_{$goal->id}_desc") )
		{
			$form->addMessage( $desc, '', FALSE );
		}

		/* Work out what our options are */
		$options = array();

		if( $goal->suggestions )
		{
			foreach ( json_decode( $goal->suggestions ) as $suggestion )
			{
				$options[ $suggestion ] = new Money( $suggestion, $goal->currency );
			}
		}

		if ( empty( $options ) )
		{
			$form->add( new Number( 'donate_amount', 0, TRUE, array( 'decimals' => TRUE, 'min' => 0.01 ), NULL, NULL, $goal->currency ) );
		}
		else
		{
			if( $goal->suggestions_open )
			{
				$options['x'] = Member::loggedIn()->language()->addToStack('other');
			}

			$form->add( new Radio( 'donate_amount', NULL, TRUE, array( 'options' => $options, 'parse' => 'normal', 'userSuppliedInput' => 'x' ) ) );
		}

		$form->add( new YesNo( 'donate_anonymous', FALSE ) );

		if ( $values = $form->values() )
		{
			if( !is_numeric( $values['donate_amount'] ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack( 'donation_invalid_amount' );
			}

			if( !$form->error )
			{
				$item = new Donation( Member::loggedIn()->language()->get( 'nexus_donategoal_' . $goal->_id ), new Money( $values['donate_amount'], $goal->currency ) );
				$item->id = $goal->_id;
				$item->extra = array( 'anonymous' => $values['donate_anonymous'] );

				$invoice = new Invoice;
				$invoice->member = Customer::loggedIn();
				$invoice->currency = $goal->currency;
				$invoice->addItem( $item );
				$invoice->return_uri = 'app=nexus&module=clients&controller=donations&thanks=1';
				$invoice->save();

				Output::i()->redirect( $invoice->checkoutUrl() );
			}
		}
		
		Output::i()->title = $goal->_title;
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
}