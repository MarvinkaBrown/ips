<?php
/**
 * @brief		Pending Actions Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		19 Sep 2014
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Member;
use IPS\nexus\Payout;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Pending Actions Widget
 */
class pendingActions extends Widget
{
	/**
	 * @brief	Options
	 */
	protected static array $options = array(
		'transactions'	=> 'pending_transactions',
		'withdrawals'	=> 'pending_widthdrawals',
		'ads'			=> 'pending_advertisements',
	);
	
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'pendingActions';
	
	/**
	 * @brief	App
	 */
	public string $app = 'nexus';

	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init(): void
	{
		if ( !isset( $this->configuration['pendingActions_stuff'] ) )
		{
			$this->configuration['pendingActions_stuff'] = array_keys( static::$options );
		}
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'nexus', 'front' ) );
		parent::init();
	}
		
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

 		$form->add( new CheckboxSet( 'pendingActions_stuff', $this->configuration['pendingActions_stuff'], TRUE, array( 'options' => static::$options ) ) );
 		
 		return $form;
 	} 
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if ( !Member::loggedIn()->isAdmin() )
		{
			return '';
		}
		
		/* Pending transactions *might* happen some weird way, so always get the count... but only show the count if we have fraud rules set up */
		$pendingTransactions = NULL;
		if ( in_array( 'transactions', $this->configuration['pendingActions_stuff'] ) and Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
		{
			$pendingTransactions = Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_status=?', Transaction::STATUS_HELD ) )->first();
			if ( !$pendingTransactions and !Db::i()->select( 'COUNT(*)', 'nexus_fraud_rules' )->first() )
			{
				$pendingTransactions = NULL;
			}
		}

		/* And advertisements */
		$pendingAdvertisements = NULL;
		if ( in_array( 'ads', $this->configuration['pendingActions_stuff'] ) and Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_manage' ) )
		{
			$pendingAdvertisements = Db::i()->select( 'COUNT(*)', 'core_advertisements', array( 'ad_active=-1' ) )->first();
			if ( !$pendingAdvertisements and !Db::i()->select( 'COUNT(*)', 'nexus_packages_ads' )->first() )
			{
				$pendingAdvertisements = NULL;
			}
		}
		
		/* Withdrawals will only be if enabled */
		$pendingWithdrawals = NULL;
		if ( in_array( 'withdrawals', $this->configuration['pendingActions_stuff'] ) and Settings::i()->nexus_payout and Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_manage' ) )
		{
			$pendingWithdrawals = Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_status=?', Payout::STATUS_PENDING ) )->first();
		}
				
		return $this->output( $pendingTransactions, $pendingWithdrawals, $pendingAdvertisements );
	}
}