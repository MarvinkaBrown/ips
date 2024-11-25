<?php
/**
 * @brief		Designer mode controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 Aug 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redirect
 */
class designermode extends Controller
{
	/**
	 * Something is wrong
	 *
	 * @return	void
	 */
	protected function missing() : void
	{
		Output::i()->error( Member::loggedIn()->language()->addToStack( 'theme_designers_mode_error_missing', FALSE, array( 'sprintf' => array( intval( Request::i()->id ) ) ) ), "4C370/1" );
	}
}