<?php
/**
 * @brief		Overview statistics extension: Clubs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Jan 2020
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Clubs
 */
class Clubs extends OverviewStatisticsAbstract
{
	/**
	 * @brief	Which statistics page (activity or user)
	 */
	public string $page	= 'activity';

	/**
	 * Return the sub-block keys
	 *
	 * @note This is designed to allow one class to support multiple blocks, for instance using the ContentRouter to generate blocks.
	 * @return array
	 */
	public function getBlocks(): array
	{
		if( Settings::i()->clubs )
		{
			return array( 'clubs', 'joins' );
		}

		return array();
	}

	/**
	 * Return block details (title and description)
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockDetails( string $subBlock = NULL ): array
	{
		/* Description can be null and will not be shown if so */
		if( $subBlock == 'joins' )
		{
			return array( 'app' => 'core', 'title' => 'stats_overview_clubjoins', 'description' => 'stats_overview_clubjoins_desc', 'refresh' => 60 );
		}
		else
		{
			return array( 'app' => 'core', 'title' => 'stats_overview_clubs', 'description' => null, 'refresh' => 60 );
		}
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	public function getBlock( array|string $dateRange = NULL, string $subBlock = NULL ): string
	{
		if( $subBlock == 'joins' )
		{
			return $this->_showJoins( $dateRange );
		}
		else
		{
			return $this->_showClubs( $dateRange );
		}
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @return	string
	 */
	public function _showJoins( array|string|null $dateRange = NULL ) : string
	{
		$where			= NULL;
		$previousCount	= NULL;

		if( $dateRange !== NULL )
		{
			if( is_array( $dateRange ) )
			{
				$where = array(
					array( 'joined > ?', $dateRange['start']->getTimestamp() ),
					array( 'joined < ?', $dateRange['end']->getTimestamp() ),
				);
			}
			else
			{
				$currentDate	= new DateTime;
				$interval = static::getInterval( $dateRange );
				$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
				$where = array( array( 'joined > ?', $initialTimestamp ) );

				$previousCount = Db::i()->select( 'COUNT(*)', 'core_clubs_memberships', array( array( 'joined BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) ) )->first();
			}
		}

		$count = Db::i()->select( 'COUNT(*)', 'core_clubs_memberships', $where )->first();

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $count, $previousCount );
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @return	string
	 */
	public function _showClubs( array|string|null $dateRange = NULL ) : string
	{
		/* Init Chart */
		$pieBarData = array();

		/* Add Rows */
		$where			= NULL;
		$previousCount	= NULL;

		if( $dateRange !== NULL )
		{
			if( is_array( $dateRange ) )
			{
				$where = array(
					array( 'created > ?', $dateRange['start']->getTimestamp() ),
					array( 'created < ?', $dateRange['end']->getTimestamp() ),
				);
			}
			else
			{
				$currentDate	= new DateTime;
				$interval = static::getInterval( $dateRange );
				$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
				$where = array( array( 'created > ?', $initialTimestamp ) );

				$previousCount = Db::i()->select( 'COUNT(*)', 'core_clubs', array( array( 'created BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) ) )->first();
			}
		}

		$total = 0;
		$chart = NULL;

		foreach( Db::i()->select( 'COUNT(*) as total, `type`', 'core_clubs', $where, NULL, NULL, 'type' ) as $result )
		{
			$pieBarData[] = array(
				'name' =>  Member::loggedIn()->language()->addToStack('club_type_' . $result['type'] ),
				'value' => $result['total'],
				'percentage' => 0
			);

			$total += $result['total'];
		}

		// Add percentages
		foreach( $pieBarData as &$segment )
		{
			$segment['percentage'] = round( ( $segment['value'] / $total ) * 100, 2 );
		}

		if( count( $pieBarData ) )
		{
			$chart = Theme::i()->getTemplate( 'global', 'core', 'global'  )->applePieChart( $pieBarData );
		}

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $total, $previousCount, $chart );
	}
}