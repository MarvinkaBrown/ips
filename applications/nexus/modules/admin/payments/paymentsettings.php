<?php
/**
 * @brief		Payment Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		26 Mar 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Payment Settings
 */
class paymentsettings extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Call
	 *
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$tabs = array();
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'currencies_manage' ) )
		{
			$tabs['currencies'] = 'currencies';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'gateways_manage' ) )
		{
			$tabs['gateways'] = 'payment_methods';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'tax_manage' ) )
		{
			$tabs['tax'] = 'tax_rates';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'checkout_settings' ) )
		{
			$tabs['checkoutsettings'] = 'checkout_settings';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'fraud_manage' ) )
		{
			$tabs['fraud'] = 'anti_fraud_rules';
		}
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'email_copies_settings' ) )
		{
			$tabs['emails'] = 'notification_copies';
		}

		if ( isset( Request::i()->tab ) and isset( $tabs[ Request::i()->tab ] ) )
		{
			$activeTab = Request::i()->tab;
		}
		else
		{
			$_tabs = array_keys( $tabs ) ;
			$activeTab = array_shift( $_tabs );
		}
		
		$classname = 'IPS\nexus\modules\admin\payments\\' . $activeTab;
		$class = new $classname;
		$class->url = Url::internal("app=nexus&module=payments&controller=paymentsettings&tab={$activeTab}");
		$class->execute();
		
		if ( $method !== 'manage' or Request::i()->isAjax() )
		{
			return;
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('payment_settings');
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, Output::i()->output, Url::internal( "app=nexus&module=payments&controller=paymentsettings" ) );
	}
}