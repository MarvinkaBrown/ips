<?php
/**
 * @brief		Overview statistics extension: Online
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jan 2020
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Member;
use IPS\Session\Store;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Online
 */
class Online extends OverviewStatisticsAbstract
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
		return array( 'online' );
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
		return array( 'app' => 'core', 'title' => 'stats_overview_online', 'description' => 'stats_overview_online_desc', 'refresh' => 10 );
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
		$online = array();
		$seen   = array();
		$chart	= NULL;

		foreach( Store::i()->getOnlineUsers( Store::ONLINE_MEMBERS | Store::ONLINE_GUESTS, 'desc' ) as $row )
		{
			/* Only show if the application is still installed and enabled */
			if( !Application::appIsEnabled( $row['current_appcomponent'] ) )
			{
				continue;
			}

			$key = ( $row['member_id'] ?: $row['id'] );
			
			if ( ! isset( $seen[ $key ] ) )
			{
				$online[ $row['current_appcomponent'] ][ $key ] = $row['id'];
				$seen[ $key ] = true;
			}
		}
		
		$total = 0;
		foreach ( $online as $app => $data )
		{
			$total += count( $data );
		}

		foreach( $online as $app => $data )
		{
			$pieBarData[] = array(
				'name'			=>  Member::loggedIn()->language()->addToStack( '__app_' . $app ),
				'value'			=> count( $data ),
				'percentage'	=> round( ( count( $data ) / $total ) * 100, 2 )
			);
		}

		if( count( $pieBarData ) )
		{
			$chart = Theme::i()->getTemplate( 'global', 'core', 'global'  )->applePieChart( $pieBarData );
		}

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $total, NULL, $chart );
	}
}