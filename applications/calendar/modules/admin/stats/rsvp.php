<?php
/**
 * @brief		rsvp
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		10 Sep 2021
 */

namespace IPS\calendar\modules\admin\stats;

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
 * rsvp
 */
class rsvp extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'rsvp_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$chart = Chart::loadFromExtension( 'calendar', 'Rsvp' )->getChart( Url::internal( "app=calendar&module=stats&controller=rsvp" ) );

		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__calendar_stats_rsvp');
		Output::i()->output = (string) $chart;
	}
	
	// Create new methods with the same name as the 'do' parameter which should execute it
}