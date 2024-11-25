<?php
/**
 * @brief		Overview statistics extension: Contributors
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jan 2020
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Contributors
 */
class Contributors extends OverviewStatisticsAbstract
{
	/**
	 * @brief	Which statistics page (activity or user)
	 */
	public string $page	= 'user';

	/**
	 * Return the sub-block keys
	 *
	 * @note This is designed to allow one class to support multiple blocks, for instance using the ContentRouter to generate blocks.
	 * @return array
	 */
	public function getBlocks(): array
	{
		return array( 'contributors' );
	}

	/**
	 * Return block details (title and description)
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockDetails( ?string $subBlock = NULL ): array
	{
		/* Description can be null and will not be shown if so */
		return array( 'app' => 'core', 'title' => 'stats_overview_contributing_users', 'description' => 'stats_overview_contributing_users_desc', 'refresh' => 10 );
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	public function getBlock( array|string $dateRange = NULL, ?string $subBlock = NULL ): string
	{
		$classes		= Content::routedClasses( FALSE, TRUE );
		$unions			= array();
		$prevUnions		= array();
		$previousCount	= NULL;

		foreach( $classes as $class )
		{
			/* If the content item doesn't support tracking an author, skip it */
			if( !isset( $class::$databaseColumnMap['author'] ) )
			{
				continue;
			}

			$where = NULL;

			if( $dateRange !== NULL AND isset( $class::$databaseColumnMap['date'] ) )
			{
				if( is_array( $dateRange ) )
				{
					$where = array(
						array( $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' > ?', $dateRange['start']->getTimestamp() ),
						array( $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' < ?', $dateRange['end']->getTimestamp() ),
					);
				}
				else
				{
					$currentDate	= new DateTime;
					$interval = static::getInterval( $dateRange );
					$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
					$where = array( array( $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' > ? ', $initialTimestamp ) );

					$prevUnions[] = Db::i()->select( $class::$databasePrefix . $class::$databaseColumnMap['author'] . ' as member_id', $class::$databaseTable, array( array( $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) ) );
				}
			}

			$unions[] = Db::i()->select( $class::$databasePrefix . $class::$databaseColumnMap['author'] . ' as member_id', $class::$databaseTable, $where );
		}

		$count = Db::i()->union( $unions, NULL, NULL, NULL, NULL, 0, NULL, 'COUNT(DISTINCT(member_id))' )->first();

		if( $dateRange !== NULL AND !is_array( $dateRange ) )
		{
			$previousCount = Db::i()->union( $prevUnions, NULL, NULL, NULL, NULL, 0, NULL, 'COUNT(DISTINCT(member_id))' )->first();
		}

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $count, $previousCount );
	}
}