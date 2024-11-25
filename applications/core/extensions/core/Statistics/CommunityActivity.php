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
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Callback;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class CommunityActivity extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_activitystats_communityactivity';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
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
			'weekly',
			array( 'start' => DateTime::create()->sub( new DateInterval( 'P90D' ) ), 'end' => DateTime::ts( time() ) )
		);
		$chart->setExtension( $this );
		$chart->addSeries( Member::loggedIn()->language()->addToStack('activity'), 'number', FALSE );
		$chart->title = Member::loggedIn()->language()->addToStack('member_activity_timeperiod');
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );
		
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
		/* Get results */
		$results = array();
		
		if ( Settings::i()->search_method == 'mysql' )
		{
			$results = array_replace_recursive( $results, $this->getSqlResults( 'core_search_index', 'index_date_updated', 'index_author', $chart ) );
		}
		else
		{
			foreach ( Application::allExtensions( 'core', 'ContentRouter' ) as $contentRouter )
			{
				foreach ( $contentRouter->classes as $class )
				{
					if ( isset( $class::$databaseColumnMap['author'] ) )
					{
						$results = array_replace_recursive( $results, $this->getSqlResults(
							$class::$databaseTable,
							$class::$databasePrefix . ( $class::$databaseColumnMap['updated'] ?? $class::$databaseColumnMap['date'] ),
							$class::$databasePrefix . $class::$databaseColumnMap['author'],
							$chart
						) );
					}
				}
			}
		}
		$results = array_replace_recursive( $results, $this->getSqlResults( 'core_reputation_index', 'rep_date', 'member_id', $chart ) );
		$results = array_replace_recursive( $results, $this->getSqlResults( 'core_follow', 'follow_added', 'follow_member_id', $chart ) );

		/* Reformat now */
		$finalResults = array();

		foreach( $results as $date => $members )
		{
			$finalResults[ $date ] = array( 'time' => $date, Member::loggedIn()->language()->get('activity') => count( $members ) );
		}

		return $finalResults;
	}

	/**
	 * Get SQL query/results
	 *
	 * @note Consolidated to reduce duplicated code
	 * @param	string	$table	Database table
	 * @param	string	$date	Date column
	 * @param	string	$author	Author column
	 * @param	object	$chart	Chart
	 * @return	array
	 */
	protected function getSqlResults( string $table, string $date, string $author, object $chart ) : array
	{
		/* What's our SQL time? */
		switch ( $chart->timescale )
		{
			case 'daily':
				$timescale = '%Y-%c-%e';
				break;
			
			case 'weekly':
				$timescale = '%x-%v';
				break;
				
			case 'monthly':
				$timescale = '%Y-%c';
				break;
		}

		$results	= array();
		$where		= array( array( "{$date}>?", 0 ) );
		$where[]	= array( "{$author}>?", 0 );
		if ( $chart->start )
		{
			$where[] = array( "{$date}>?", $chart->start->getTimestamp() );
		}
		if ( $chart->end )
		{
			$where[] = array( "{$date}<?", $chart->end->getTimestamp() );
		}

		/* First we need to get search index activity */
		$fromUnixTime = "FROM_UNIXTIME( IFNULL( {$date}, 0 ) )";
		if ( !$chart->timezoneError and Member::loggedIn()->timezone and in_array( Member::loggedIn()->timezone, DateTime::getTimezoneIdentifiers() ) )
		{
			$fromUnixTime = "CONVERT_TZ( {$fromUnixTime}, @@session.time_zone, '" . Db::i()->escape_string( Member::loggedIn()->timezone ) . "' )";
		}

		$stmt = Db::i()->select( "DATE_FORMAT( {$fromUnixTime}, '{$timescale}' ) AS time, {$author}", $table, $where, 'time ASC', NULL, array( $author, 'time' ) );

		foreach( $stmt as $row )
		{
			$results[ $row['time'] ][ $row[ $author ] ] = 1;
		}

		return $results;
	}
}