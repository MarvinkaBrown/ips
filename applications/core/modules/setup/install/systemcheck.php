<?php
/**
 * @brief		Installer: System Check
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Dec 2014
 */
 
namespace IPS\core\modules\setup\install;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Setup\Install;
use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Installer: System Check
 */
class systemcheck extends Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Clear previous session data */
		if( !isset( Request::i()->sessionCheck ) AND count( $_SESSION ) )
		{
			foreach( $_SESSION as $k => $v )
			{
				unset( $_SESSION[ $k ] );
			}
		}

		/* Store a session variable and then check it on the next page load to make sure PHP sessions are working */
		if( !isset( Request::i()->sessionCheck ) )
		{
			$_SESSION['sessionCheck'] = TRUE;
			Output::i()->redirect( Request::i()->url()->setQueryString( 'sessionCheck', 1 ) );
		}
		else
		{
			if( !isset( $_SESSION['sessionCheck'] ) OR !$_SESSION['sessionCheck'] )
			{
				Output::i()->error( 'session_check_fail', '5C348/1', 500, '' );
			}
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('healthcheck');
		Output::i()->output	= Theme::i()->getTemplate( 'global' )->healthcheck( Install::systemRequirements() );
	}
}