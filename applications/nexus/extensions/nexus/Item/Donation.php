<?php
/**
 * @brief		Donation
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Jun 2014
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Donation\Goal;
use IPS\nexus\Invoice;
use IPS\nexus\Invoice\Item\Charge;
use IPS\Notification;
use IPS\Settings;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Donation
 */
class Donation extends Charge
{
	/**
	 * @brief	Application
	 */
	public static string $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static string $type = 'donation';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'money-bill';
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'donation';
	
	/**
	 * @brief	Can use coupons?
	 */
	public static bool $canUseCoupons = FALSE;
	
	/**
	 * @brief	Can use account credit?
	 */
	public static bool $canUseAccountCredit = FALSE;
	
	/**
	 * On Paid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @return    void
	 */
	public function onPaid( Invoice $invoice ): void
	{
		try
		{
			$goal = Goal::load( $this->id );
			$goalAmount = new Number( str_replace( Member::loggedIn()->Language()->locale['decimal_point'], '.', (string) $goal->current ) );
			$goalAmount = $goalAmount->add( $this->price->amount );
			$goal->current = (string) $goalAmount;

			/* Have we reached the goal? */
			if( $goal->goal and !$goal->goal_reached and $goal->current >= $goal->goal )
			{
				$notification = new Notification( Application::load( 'core' ), 'donation_goal_reached', $goal, [ $goal ] );

				foreach ( $goal->donors() as $member )
				{
					$notification->recipients->attach( $member );
				}
				$notification->send();

				$goal->goal_reached = 1;
			}

			$goal->save();

			if( !$this->extra['anonymous'] )
			{
				$invoice->member->achievementAction( 'nexus', 'Donation', $goal );
			}


		}
		catch ( Exception ) {}
		
		Db::i()->insert( 'nexus_donate_logs', array(
			'dl_goal'	=> $this->id,
			'dl_member'	=> $invoice->member->member_id,
			'dl_amount'	=> $this->price->amount,
			'dl_invoice'=> $invoice->id,
			'dl_date'	=> time(),
			'dl_anon'	=> $this->extra['anonymous'],
		) );
	}
	
	/**
	 * On Unpaid
	 *
	 * @param	Invoice	$invoice	The invoice
	 * @param	string				$status		Status
	 * @return    void
	 */
	public function onUnpaid( Invoice $invoice, string $status ): void
	{
		
	}
	
	/**
	 * Requires Billing Address
	 *
	 * @return	bool
	 * @throws	DomainException
	 */
	public function requiresBillingAddress(): bool
	{
		return in_array( 'donation', explode( ',', Settings::i()->nexus_require_billing ) );
	}
}