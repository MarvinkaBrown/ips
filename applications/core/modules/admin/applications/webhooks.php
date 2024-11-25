<?php
/**
 * @brief		webhooks
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		04 Nov 2021
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * webhooks
 */
class webhooks extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Api\Webhook';

	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;

	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'webhooks_manage' );

		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return void
	 */
	public function manage() : void
	{
		if ( ( new Bridge )->coreAdminWebhooks() )
		{
			parent::manage();
		}
	}

	/**
	 * Form to add/edit a forum
	 *
	 * @return void
	 */
	protected function form() : void
	{
		parent::form();

		if ( Request::i()->id )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('edit_webhook', FALSE, array( 'sprintf' => array( Output::i()->title ) ) );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('add_webhook');
		}
	}

	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$form = new Form;
		$form->add( new Interval( 'webhook_logs_success', Settings::i()->webhook_logs_success, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'always' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'webhook_logs_success' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__webhook_settings' );
			Output::i()->redirect( $this->url, 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('settings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'settings', $form, FALSE );
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		$response = parent::_getRootButtons();

			$settingsButton = [
				'title'		=> 'settings',
				'icon'		=> 'cog',
				'link'		=> $this->url->setQueryString('do', 'settings'),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
			];
			return array_merge( $response, [ 'settings' =>$settingsButton] );
	}

}