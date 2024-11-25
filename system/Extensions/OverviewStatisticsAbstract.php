<?php

/**
 * @brief        OverviewStatisticsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/20/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class OverviewStatisticsAbstract
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
	abstract public function getBlocks(): array;

	/**
	 * Return block details (title and description)
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	abstract public function getBlockDetails( ?string $subBlock = NULL ): array;

	/**
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	abstract public function getBlock( array|string|null $dateRange = NULL, ?string $subBlock = NULL ): string;

	/**
	 * Calculate interval for charts
	 *
	 * @param int|null $dateRange
	 * @return DateInterval|null
	 */
	protected static function getInterval( ?int $dateRange ) : ?DateInterval
	{
		switch( $dateRange )
		{
			case '7':
				return new DateInterval( 'P7D' );

			case '30':
				return new DateInterval( 'P1M' );

			case '90':
				return new DateInterval( 'P3M' );

			case '180':
				return new DateInterval( 'P6M' );

			case '365':
				return new DateInterval( 'P1Y' );
		}

		return null;
	}
}