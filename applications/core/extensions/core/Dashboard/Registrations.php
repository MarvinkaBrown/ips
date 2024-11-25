<?php
/**
 * @brief		Dashboard extension: Registrations
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Jul 2013
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Extensions\DashboardAbstract;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Registrations
 */
class Registrations extends DashboardAbstract
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView(): bool
	{
		return Member::loggedIn()->hasAcpRestriction( 'core' , 'members', 'registrations_manage' );
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		/* We can use the registration stats controller for this */
		$chart = Chart::loadFromExtension( 'core', 'Registrations' )->getChart( Url::internal( 'app=core&module=stats&controller=registrationstats' ) );
		$chart->showFilterTabs = FALSE;
		$chart->showIntervals = FALSE;
		$chart->showDateRange = FALSE;
		
		/* Output */
		return Theme::i()->getTemplate( 'dashboard' )->registrations( $chart );
	}
}