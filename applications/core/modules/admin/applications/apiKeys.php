<?php
/**
 * @brief		API Keys
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		03 Dec 2015
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * api
 */
class apiKeys extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Api\Key';
	
	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = TRUE;
	
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
		Dispatcher::i()->checkAcpPermission( 'api_manage' );
		parent::execute();
	}
	
	/**
	 * View List (checks endpoints are available on https)
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( ( new Bridge )->core_admin_applications_apiKeys() )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'api_keys_blurb', true, true );
			parent::manage();
		}
	}
}