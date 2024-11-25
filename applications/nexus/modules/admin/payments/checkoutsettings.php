<?php
/**
 * @brief		Checkout Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		28 Mar 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Form\Money;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Checkout Settings
 */
class checkoutsettings extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'checkout_settings' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{	
		$requireBillingOptions = array();
		$requireBillingOptions['product'] = 'nexus_require_billing_package';
		if ( ( $giftVouchers = json_decode( Settings::i()->nexus_gift_vouchers, TRUE ) and count( $giftVouchers ) ) or Settings::i()->nexus_gift_vouchers_free )
		{
			$requireBillingOptions['giftvoucher'] = 'nexus_require_billing_gift';
		}
		if ( Db::i()->select( 'COUNT(*)', 'nexus_donate_goals' )->first() )
		{
			$requireBillingOptions['donation'] = 'nexus_require_billing_donation';
		}
		
		if ( Settings::i()->nexus_subs_enabled and Db::i()->select( 'COUNT(*)', 'nexus_member_subscription_packages' )->first() )
 		{
 			$requireBillingOptions['subscriptions'] = 'nexus_require_billing_subscriptions';
 		}

		$requireBillingOptions['other'] = 'nexus_require_billing_other';

		$form = new Form;
		$form->addHeader( 'checkout_settings' );
		$form->add( new CheckboxSet( 'nexus_require_billing', explode( ',', Settings::i()->nexus_require_billing ), FALSE, array( 'options' => $requireBillingOptions ) ) );
		$form->add( new YesNo( 'nexus_split_payments_on', Settings::i()->nexus_split_payments != -1, FALSE, array( 'togglesOn' => array( 'nexus_split_payments' ) ) ) );
		$form->add( new Money( 'nexus_split_payments', Settings::i()->nexus_split_payments ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_split_payments' ) );
		$form->add( new Radio( 'nexus_tac', Settings::i()->nexus_tac, FALSE, array(
			'options'	=> array(
				'none'		=> 'nexus_tac_none',
				'button'	=> 'nexus_tac_button',
				'checkbox'	=> 'nexus_tac_checkbox'
			),
			'toggles'	=> array(
				'button'	=> array( 'nexus_tac_link' ),
				'checkbox'	=> array( 'nexus_tac_link' )
			)
		) ) );
		$form->add( new Form\Url( 'nexus_tac_link', Settings::i()->nexus_tac_link, FALSE, array(), NULL, NULL, NULL, 'nexus_tac_link' ) );
		$form->addHeader( 'nexus_checkreg' );
		$form->addMessage( 'nexus_checkreg_desc' );
		$form->add( new YesNo( 'nexus_checkreg_usernames', Settings::i()->nexus_checkreg_usernames ) );
		$form->add( new YesNo( 'nexus_checkreg_captcha', Settings::i()->nexus_checkreg_captcha ) );
		if ( Settings::i()->reg_auth_type != 'none' )
		{
			$form->add( new YesNo( 'nexus_checkreg_validate', Settings::i()->nexus_checkreg_validate ) );
		}
		if ( $values = $form->values() )
		{
			$values['nexus_split_payments'] = $values['nexus_split_payments_on'] ? ( $values['nexus_split_payments'] == '*' ? 0 : json_encode( $values['nexus_split_payments'] ) ) : -1;
			unset( $values['nexus_split_payments_on'] );
			$values['nexus_require_billing'] = count( $values['nexus_require_billing'] ) ? implode( ',', $values['nexus_require_billing'] ) : 'none';
			$form->saveAsSettings( $values );
			
			Session::i()->log( 'acplogs__checkout_settings' );
			Output::i()->redirect( Url::internal( 'app=nexus&module=payments&controller=paymentsettings&tab=checkoutsettings' ) );
		}
		Output::i()->title = Member::loggedIn()->language()->addToStack('checkout_settings');
		Output::i()->output = $form;
	}
}