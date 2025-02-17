<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Events
 * @since		26 Jan 2023
 */

namespace IPS\calendar\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Rsvp extends Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'calendar_stats_rsvp';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return \IPS\Helpers\Chart
	 */
	public function getChart( Url $url ): \IPS\Helpers\Chart
	{
		$chart	= new Database( $url, 'calendar_event_rsvp', 'rsvp_date', '', array(
			'isStacked' => FALSE,
			'backgroundColor' 	=> '#ffffff',
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		) );
		$chart->setExtension( $this );
		
		$chart->groupBy = 'rsvp_response';
		
		foreach( array( 0,1,2 ) as $response )
		{
			$chart->addSeries( Member::loggedIn()->language()->addToStack('calendar_stats_rsvp_response_' . $response ), 'number', 'COUNT(*)', FALSE, $response );
		
		}
		$chart->title = Member::loggedIn()->language()->addToStack('calendar_stats_rsvp_title');
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );
		
		return $chart;
	}
}