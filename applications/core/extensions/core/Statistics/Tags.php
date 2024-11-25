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
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Tags extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_activitystats_tags';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database( $url, 'core_tags', 'tag_added', '', array(
				'isStacked'			=> FALSE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'AreaChart',
			'daily',
			array( 'start' => DateTime::create()->sub( new DateInterval( 'P1M' ) ), 'end' => 0 )
		);
		$chart->setExtension( $this );
		$chart->groupBy			= 'tag_text';
		$chart->availableTypes	= array( 'AreaChart', 'ColumnChart', 'BarChart', 'PieChart' );

		$where = $chart->where;
		$where[] = array( "tag_added>?", 0 );
		if ( $chart->start )
		{
			$where[] = array( "tag_added>?", $chart->start->getTimestamp() );
		}
		if ( $chart->end )
		{
			$where[] = array( "tag_added<?", $chart->end->getTimestamp() );
		}
		
		/* Only get visible tags */
		$where[] = array( 'tag_aai_lookup IN(?)', Db::i()->select( 'tag_perm_aai_lookup', 'core_tags_perms', [ 'tag_perm_visible=1' ] ) );

		foreach( Db::i()->select( 'tag_text', 'core_tags', $where, NULL, NULL, array( 'tag_text' ) ) as $tag )
		{
			$chart->addSeries( $tag, 'number', 'COUNT(*)', TRUE, $tag );
		}
		
		return $chart;
	}
}