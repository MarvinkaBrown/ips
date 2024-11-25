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

use IPS\Api\Webhook;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Output;
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
class webhooksReference extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'webhooks_manage' );
		parent::execute();
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$webhooks = Webhook::getAvailableWebhooks();
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/api.css', 'core', 'admin' ) );
		Output::i()->output = Theme::i()->getTemplate( 'api' )->webhooks( $webhooks );



	}

	// Create new methods with the same name as the 'do' parameter which should execute it
}