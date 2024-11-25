<?php
/**
 * @brief		Dashboard extension: PendingActions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Sep 2014
 */

namespace IPS\nexus\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\DashboardAbstract;
use IPS\Member;
use IPS\nexus\Payout;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: PendingActions
 */
class PendingActions extends DashboardAbstract
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView(): bool
	{
		return  ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' )
		or
		Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_manage' )
		or
		Settings::i()->nexus_payout and Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_manage' ) );
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		/* Pending transactions *might* happen some weird way, so always get the count... but only show the count if we have fraud rules set up */
		$pendingTransactions = NULL;
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
		{
			$pendingTransactions = Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( Db::i()->in( 't_status', array( Transaction::STATUS_HELD, Transaction::STATUS_WAITING, Transaction::STATUS_REVIEW, Transaction::STATUS_DISPUTED ) ) ) )->first();
			if ( !$pendingTransactions and !Db::i()->select( 'COUNT(*)', 'nexus_fraud_rules' )->first() )
			{
				$pendingTransactions = NULL;
			}
		}

		/* And advertisements */
		$pendingAdvertisements = NULL;
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_manage' ) )
		{
			$pendingAdvertisements = Db::i()->select( 'COUNT(*)', 'core_advertisements', array( 'ad_active=-1' ) )->first();
			if ( !$pendingAdvertisements and !Db::i()->select( 'COUNT(*)', 'nexus_packages_ads' )->first() )
			{
				$pendingAdvertisements = NULL;
			}
		}
		
		/* Withdrawals will only be if enabled */
		$pendingWithdrawals = NULL;
		if ( Settings::i()->nexus_payout and Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_manage' ) )
		{
			$pendingWithdrawals = Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_status=?', Payout::STATUS_PENDING ) )->first();
		}
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'nexus', 'front' ) );
		
		return Theme::i()->getTemplate( 'dashboard', 'nexus' )->pendingActions( $pendingTransactions, $pendingWithdrawals, $pendingAdvertisements );
	}
}