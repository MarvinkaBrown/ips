<?php
/**
 * @brief		referralcommission
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		30 Sep 2019
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * referralcommission
 */
class referralcommission extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\nexus\CommissionRule';

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'commission_rules_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		Output::i()->sidebar['actions'][] = array(
			'icon'	=> 'cog',
			'title'	=> 'commission_settings',
			'link'	=> Url::internal( "app=nexus&module=customers&controller=referrals&do=settings" )
		);

		parent::manage();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function settings() : void
	{
		$form = new Form;
		$form->add( new YesNo( 'nexus_com_rules', Settings::i()->nexus_com_rules, FALSE, array( 'togglesOff' => array( 'nexus_com_rules_alt' ) ), NULL, NULL, NULL, 'nexus_com_rules' ) );
		$form->add( new Translatable( 'nexus_com_rules_alt', NULL, FALSE, array( 'app' => 'nexus', 'key' => 'nexus_com_rules_val', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'nexus_com_rules_alt', 'attachIds' => array( NULL, NULL, 'nexus_com_rules_alt' ) ) ), NULL, NULL, NULL, 'nexus_com_rules_alt' ) );

		if ( $values = $form->values() )
		{
			Lang::saveCustom( 'nexus', 'nexus_com_rules_val', $values['nexus_com_rules_alt'] );
			unset( $values['nexus_com_rules_alt'] );

			$form->saveAsSettings( $values );
			
			Session::i()->log( 'acplog__referral_commission_settings_edited' );

			Output::i()->redirect( Url::internal('app=nexus&module=customers&controller=referrals'), 'saved' );
		}

		Output::i()->output = $form;

	}
}