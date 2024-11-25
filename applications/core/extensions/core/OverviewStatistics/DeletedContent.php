<?php
/**
 * @brief		Overview statistics extension: DeletedContent
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		23 Sep 2021
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: DeletedContent
 */
class DeletedContent extends OverviewStatisticsAbstract
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
		return array( 'deletedcontent' );
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
		return array( 'app' => 'core', 'title' => 'stats_deletedcontent_percent', 'description' => 'stats_percentagedeleted_desc', 'refresh' => 60, 'form' => false );
			
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
		if( !Settings::i()->dellog_retention_period )
		{
			$total = $deleted = $value = 0;
		}
		else
		{
			$total		= Db::i()->select( 'COUNT(*)', 'core_search_index', array( 'index_date_created > ?', time() - Settings::i()->dellog_retention_period ) )->first();
			$deleted	= Db::i()->select( 'COUNT(*)', 'core_deletion_log', array() )->first();
			$value = $total ? round( $deleted / $total * 100, 2 ) : 0;
		}

		return Theme::i()->getTemplate( 'activitystats', 'core' )->deletedPercentage( $value, $total, $deleted );
	}
}