<?php
/**
 * @brief		Store Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		05 May 2014
 */

namespace IPS\nexus\modules\admin\store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\nexus\Form\Money;
use IPS\Output;
use IPS\Session;
use IPS\Settings as SettingsClass;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Store Settings
 */
class settings extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		parent::execute();
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$giftVouchers = array();
		foreach ( SettingsClass::i()->nexus_gift_vouchers ? ( json_decode( SettingsClass::i()->nexus_gift_vouchers, TRUE ) ?: array() ) : array() as $voucher )
		{
			$amounts = array();
			foreach ( $voucher as $currency => $amount )
			{
				$amounts[ $currency ] = new \IPS\nexus\Money( $amount, $currency );
			}
			$giftVouchers[] = $amounts;
		}
		
		$groups = array();
		foreach ( Group::groups( FALSE, FALSE ) as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
						
		$form = new Form;
		$form->addTab('nexus_store_display');
		$form->addHeader('nexus_store_prices');
		$form->add( new YesNo( 'nexus_show_tax', SettingsClass::i()->nexus_show_tax ) );
		$form->add( new Translatable( 'nexus_tax_explain', NULL, FALSE, array( 'app' => 'nexus', 'key' => 'nexus_tax_explain_val', 'placeholder' => Member::loggedIn()->language()->addToStack('nexus_tax_explain_placeholder') ) ) );
		$form->add( new Radio( 'nexus_show_renew_option_savings', SettingsClass::i()->nexus_show_renew_option_savings, FALSE, array( 'options' => array(
			'none'		=> 'nexus_show_renew_option_savings_none',
			'amount'	=> 'nexus_show_renew_option_savings_amount',
			'percent'	=> 'nexus_show_renew_option_savings_percent',
		) ) ) );
		$form->addHeader( 'nexus_store_index' );
		$form->add( new Custom( 'nexus_store_new', explode( ',', SettingsClass::i()->nexus_store_new ), FALSE, array(
			'getHtml'	=> function( $field )
			{
				return Theme::i()->getTemplate( 'store' )->storeIndexProductsSetting( 'nexus_store_new_field', $field->name, $field->value );
			}
		) ) );
		$form->add( new Custom( 'nexus_store_popular', explode( ',', SettingsClass::i()->nexus_store_popular ), FALSE, array(
			'getHtml'	=> function( $field )
			{
				return Theme::i()->getTemplate( 'store' )->storeIndexProductsSetting( 'nexus_store_popular_field', $field->name, $field->value );
			}
		) ) );

		$form->addHeader( 'nexus_stock' );
		$form->add( new YesNo( 'nexus_show_stock', SettingsClass::i()->nexus_show_stock ) );
		$form->addTab( 'nexus_purchase_settings' );
		$form->add( new YesNo( 'nexus_reg_force', SettingsClass::i()->nexus_reg_force, FALSE ) );
		$form->add( new Money( 'nexus_minimum_order', json_decode( SettingsClass::i()->nexus_minimum_order, TRUE ) ) );
		$form->add( new CheckboxSet( 'cm_protected', explode( ',', SettingsClass::i()->cm_protected ), FALSE, array( 'options' => $groups, 'multiple' => TRUE ) ) );
		$form->addTab('nexus_gift_vouchers');
		$form->add( new Stack( 'nexus_gift_vouchers', $giftVouchers, FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\Money' ) ) );
		$form->add( new YesNo( 'nexus_gift_vouchers_free', SettingsClass::i()->nexus_gift_vouchers_free ) );
		
		if ( $values = $form->values() )
		{
			$giftVouchers = array();
			foreach ( $values['nexus_gift_vouchers'] as $voucher )
			{
				$gvValues = array();
				foreach ( $voucher as $currency => $amount )
				{
					$gvValues[ $currency ] = $amount->amount;
				}
				$giftVouchers[] = $gvValues;
			}
			$values['nexus_gift_vouchers'] = json_encode( $giftVouchers );
			
			Lang::saveCustom( 'nexus', "nexus_tax_explain_val", $values['nexus_tax_explain'] );
			unset( $values['nexus_tax_explain'] );
			
			$values['cm_protected'] = implode( ',', $values['cm_protected'] );
			$values['nexus_store_popular'] = implode( ',', $values['nexus_store_popular'] );
			$values['nexus_store_new'] = implode( ',', $values['nexus_store_new'] );
			
			$values['nexus_minimum_order'] = $values['nexus_minimum_order'] ? json_encode( $values['nexus_minimum_order'] ) : '';

			$form->saveAsSettings( $values );
			
			Session::i()->log( 'acplogs__nexus_store_settings' );
		}
		Output::i()->title = Member::loggedIn()->language()->addToStack('store_settings');
		Output::i()->output = $form;
	}
}