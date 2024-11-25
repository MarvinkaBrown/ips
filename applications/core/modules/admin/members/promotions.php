<?php
/**
 * @brief		promotions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		04 Apr 2017
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Dispatcher;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * promotions
 */
class promotions extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\Member\GroupPromotion';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'promotions_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		Output::i()->output = Theme::i()->getTemplate('forms')->blurb( Application::appIsEnabled('nexus') ? 'group_promotion_blurb_commerce' : 'group_promotion_blurb' );
		parent::manage();
	}
}