<?php
/**
 * @brief		Splash Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Jun 2013
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Splash Controller
 */
class splash extends Controller
{
	/**
	 * Splash
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( Member::loggedIn()->member_id )
		{
			if ( Member::loggedIn()->canAccessModule( Module::get( 'core', 'members', 'front' ) ) )
			{
				Output::i()->redirect( Member::loggedIn()->url() );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings', 'front', 'settings' ) );
			}
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
	}
}