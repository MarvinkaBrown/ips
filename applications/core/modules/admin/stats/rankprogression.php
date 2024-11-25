<?php
/**
 * @brief		rankprogression
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		27 Jun 2022
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use IPS\Http\Url;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * rankprogression
 */
class rankprogression extends Controller
{
	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'overview_manage' );
		parent::execute();
	}

	/**
	 * Points earned activity chart
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$chart = Chart::loadFromExtension( 'core', 'RankProgression' )->getChart( Url::internal( "app=core&module=stats&controller=rankprogression" ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_rankprogression');
		Output::i()->output = Theme::i()->getTemplate( 'stats' )->rankprogressionmessage();
		Output::i()->output .= $chart->render( 'ScatterChart', array(
			'is3D'	=> TRUE,
			'vAxis'	=> array( 'title' => Member::loggedIn()->language()->addToStack("core_stats_rank_progression_v") ),
			'legend' => 'none'
		) );
	}
}