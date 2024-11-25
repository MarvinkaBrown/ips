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

use DateInterval;
use IPS\DateTime;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Keywords extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_activitystats_keywords';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		/* Determine minimum date */
		$minimumDate = NULL;

		if( Settings::i()->stats_keywords_prune )
		{
			$minimumDate = DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_keywords_prune . 'D' ) );
		}

		/* Draw a chart */
		$options = json_decode( Settings::i()->stats_keywords, true );

		if( !is_array( $options ) )
		{
			$options = array();
		}

		$chart = new Database(
			$url, 
			'core_statistics', 
			'time', 
			'', 
			array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'LineChart', 
			'daily', 
			array( 'start' => DateTime::create()->sub( new DateInterval( 'P90D' ) ), 'end' => DateTime::ts( time() ) ),
			array(),
			'',
			$minimumDate
		);
		$chart->setExtension( $this );
		$chart->where	= array( array( 'type=?', 'keyword' ) );
		$chart->groupBy	= 'value_4';

		if ( is_array( $options ) )
		{
			foreach( $options as $k => $v )
			{
				$chart->addSeries( $v, 'number', 'COUNT(*)' );
			}
		}

		$chart->title = Member::loggedIn()->language()->addToStack('keyword_usage_chart');
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );
		
		return $chart;
	}
}