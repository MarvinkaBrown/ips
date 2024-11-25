<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		26 Jan 2023
 */

namespace IPS\core\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Badge;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Badges extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_stats_badges_type';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database( $url, 'core_member_badges', 'datetime', '', array(
			'isStacked' => FALSE,
			'backgroundColor' => '#ffffff',
			'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
			'lineWidth' => 1,
			'areaOpacity' => 0.4
			),
			'AreaChart',
			'daily',
			array('start' => 0, 'end' => 0),
			array(),
			'type'
		);
		$chart->setExtension( $this );

		$chart->title = Member::loggedIn()->language()->addToStack( 'stats_badges_title' );
		$chart->availableTypes = array('AreaChart', 'ColumnChart', 'BarChart');
		$chart->enableHourly = FALSE;

		$chart->groupBy = 'badge';

		foreach ( Badge::roots() as $badge )
		{
			$chart->addSeries( $badge->_title, 'number', 'COUNT(*)', TRUE, $badge->id );
		}

		$chart->title = Member::loggedIn()->language()->addToStack( 'stats_badges_title' );
		$chart->availableTypes = array('AreaChart', 'ColumnChart', 'BarChart');
		$chart->showIntervals = TRUE;
		
		return $chart;
	}
}