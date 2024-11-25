<?php
/**
 * @brief		cloud
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Oct 2022
 */

namespace IPS\core\modules\admin\smartcommunity;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * cloud
 */
class cloud extends Controller
{
	/**
	 * Cloud
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/enhancements.css', 'core', 'admin' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'menu__core_smartcommunity' );
		Output::i()->output = Theme::i()->getTemplate( 'smartcommunity' )->cloud();
	}
}