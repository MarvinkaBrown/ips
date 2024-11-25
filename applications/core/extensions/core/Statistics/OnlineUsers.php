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
use IPS\Helpers\Chart\Callback;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
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
class OnlineUsers extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_stats_onlineusers';
	
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

		if( Settings::i()->stats_online_users_prune )
		{
			$minimumDate = DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_online_users_prune . 'D' ) );
		}

		/* We can't retrieve any stats prior to the new tracking being implemented */
		try
		{
			$oldestLog = Db::i()->select( 'MIN(time)', 'core_statistics', array( 'type=?', 'online_users' ) )->first();

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

		$chart = new Callback(
			$url, 
			array( $this, 'getResults' ),
			'', 
			array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e', '#ea7963' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'AreaChart', 
			'none',
			array( 'start' => DateTime::ts( time() - ( 60 * 60 * 24 * 30 ) ), 'end' => DateTime::create() ),
			'',
			$minimumDate
		);
		$chart->setExtension( $this );

		$chart->addSeries( Member::loggedIn()->language()->addToStack('members'), 'number' );
		$chart->addSeries( Member::loggedIn()->language()->addToStack('guests'), 'number' );

		$chart->title = Member::loggedIn()->language()->addToStack('stats_onlineusers_title');
		$chart->availableTypes	= array( 'AreaChart', 'ColumnChart', 'BarChart' );
		$chart->showIntervals	= FALSE;
		
		return $chart;
	}
	
	/**
	 * Fetch the results
	 *
	 * @param	Callback	$chart	Chart object
	 * @return	array
	 */
	public function getResults( Callback $chart ) : array
	{
		$where = array( array( 'type=?', 'online_users' ), array( "time>?", 0 ) );

		if ( $chart->start )
		{
			$where[] = array( "time>?", $chart->start->getTimestamp() );
		}
		if ( $chart->end )
		{
			$where[] = array( "time<?", $chart->end->getTimestamp() );
		}

		$results = array();

		foreach( Db::i()->select( '*', 'core_statistics', $where, 'time ASC' ) as $row )
		{
			if( !isset( $results[ $row['time'] ] ) )
			{
				$results[ $row['time'] ] = array( 
					'time' => $row['time'], 
					Member::loggedIn()->language()->get('members') => 0,
					Member::loggedIn()->language()->get('guests') => 0
				);
			}

			if( $row['value_4'] == 'members' )
			{
				$results[ $row['time'] ][ Member::loggedIn()->language()->get('members') ] = $row['value_1'];
			}

			if( $row['value_4'] == 'guests' )
			{
				$results[ $row['time'] ][ Member::loggedIn()->language()->get('guests') ] = $row['value_1'];
			}
		}

		return $results;
	}
}