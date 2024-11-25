<?php
/**
 * @brief		Registration Stats
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 June 2013
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Dispatcher;
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
 * Registration Stats
 */
class registrationstats extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;
	
	/**
	 * Manage Members
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'registrations_manage', 'core', 'stats' );

		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_registrationstats');
		Output::i()->output = Chart::loadFromExtension( 'core', 'Registrations' )->getChart( Url::internal( 'app=core&module=stats&controller=registrationstats' ) );
	}
}
