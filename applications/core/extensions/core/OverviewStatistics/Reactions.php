<?php
/**
 * @brief		Overview statistics extension: Reactions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Jan 2020
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Reaction;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Member;
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
 * @brief	Overview statistics extension: Reactions
 */
class Reactions extends OverviewStatisticsAbstract
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
		return array( 'reactions' );
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
		return array( 'app' => 'core', 'title' => 'stats_overview_reactions', 'description' => null, 'refresh' => 10 );
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
					array( 'rep_date > ?', $dateRange['start']->getTimestamp() ),
					array( 'rep_date < ?', $dateRange['end']->getTimestamp() ),
				);
			}
			else
			{
				$currentDate	= new DateTime;
				$interval = static::getInterval( $dateRange );
				$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
				$where = array( array( 'rep_date > ?', $initialTimestamp ) );

				$previousCount = Db::i()->select( 'COUNT(*)', 'core_reputation_index', array( array( 'rep_date BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) ) )->first();
			}
		}

		/* Figure out what reactions we have */
		$reactions	= array();
		$total		= 0;
		$chart		= NULL;

		foreach( Reaction::roots( NULL ) as $reaction )
		{
			$reactions[ 'reaction_title_' . $reaction->id ] = 0;
		}

		foreach( Db::i()->select( 'COUNT(*) as total, reaction', 'core_reputation_index', $where, NULL, NULL, 'reaction' ) as $result )
		{
			$reactions['reaction_title_' . $result['reaction'] ] = $result['total'];
			$total += $result['total'];
		}

		foreach( $reactions as $title => $value )
		{
			if( $value > 0 )
			{
				$pieBarData[] = array(
					'name' =>  Member::loggedIn()->language()->addToStack( $title ),
					'value' => $value,
					'percentage' => round( ( $value / $total ) * 100, 2 )
				);
			}
		}

		if( count( $pieBarData ) )
		{
			$chart = Theme::i()->getTemplate( 'global', 'core', 'global'  )->applePieChart( $pieBarData );
		}

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $total, $previousCount, $chart );
	}
}