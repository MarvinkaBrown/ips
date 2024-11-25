<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Forums
 * @since		26 Jan 2023
 */

namespace IPS\forums\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\forums\Forum;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class PercentageSolved extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'forums_stats_solved_percentage';
	
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
		
		/* We can't retrieve any stats prior to the new tracking being implemented */
		try
		{
			$oldestLog = Db::i()->select( 'MIN(time)', 'core_statistics', array( 'type=?', 'solved' ) )->first();
		
			if( !$minimumDate OR $oldestLog < $minimumDate->getTimestamp() )
			{
				$minimumDate = DateTime::ts( $oldestLog );
			}
		}
		catch( UnderflowException $e )
		{
			/* We have nothing tracked, set minimum date to today */
			$minimumDate = DateTime::create();
		}
		
		$chart = new Database( $url, 'core_statistics', 'time', '', array(
			'isStacked' => FALSE,
			'backgroundColor' => '#ffffff',
			'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
			'lineWidth' => 1,
			'areaOpacity' => 0.4
			),
			'AreaChart',
			'daily',
			array('start' => $minimumDate, 'end' => 0),
			array(),
		);
		$chart->setExtension( $this );

		$chart->description = Member::loggedIn()->language()->addToStack( 'solved_stats_chart_desc' );
		$chart->availableTypes = array('AreaChart', 'ColumnChart', 'BarChart');
		$chart->enableHourly = FALSE;
		$chart->groupBy = 'value_1';
		$chart->title = Member::loggedIn()->language()->addToStack('forums_solved_stats_percentage');
		$chart->where[] = array( "type=?", 'solved' );
		
		foreach( $validForumIds = $this->getValidForumIds() as $forumId => $forum )
		{
			$chart->addSeries( $forum->_title, 'number', '( SUM(value_3) / SUM(value_2) ) * 100', TRUE, $forumId );
		}
		
		return $chart;
	}
	
	/**
	 * Get valid forum IDs to protect against bad data when a forum is removed
	 *
	 * @return array
	 */
	protected function getValidForumIds() : array
	{
		$validForumIds = [];
		
		foreach( Db::i()->select( 'value_1', 'core_statistics', [ 'type=?', 'solved' ], NULL, NULL, 'value_1' ) as $forumId )
		{
			try
			{
				$validForumIds[ $forumId ] = Forum::load( $forumId );
			}
			catch( Exception $e ) { }
		}
		
		return $validForumIds;
	}
}