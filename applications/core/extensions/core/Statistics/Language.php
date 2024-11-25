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

use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Language extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_stats_preferences_theme';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart	= new Chart;
		$counts = iterator_to_array( Db::i()->select( 'language, COUNT(member_id) as count', 'core_members', array( "language > ?", 0), NULL, NULL, "language" )->setKeyField( 'language' ) );

		$chart	= new Database( $url, 'core_members', 'language', '', array(
			'isStacked'			=> FALSE,
			'backgroundColor' 	=> '#ffffff',
			'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		),
			'PieChart',
			'monthly',
			array( 'start' => 0, 'end' => 0 ),
			array(),
			'skin' );
		$chart->where = array( "language > ?", 0);

		/* We need to make sure the language exists - otherwise apply the count to the default language. */
		$rows = [];
		foreach( $counts as $id => $lang )
		{
			try
			{
				$l = Lang::load( $id );
				if ( !isset( $rows[ $id ] ) )
				{
					$rows[ $id ] = array( 'title' => $l->title, 'count' => 0 );
				}
				$rows[ $id ]['count'] += $lang['count'];
			}
			catch( OutOfRangeException $e )
			{
				if ( !isset( $rows[ Lang::defaultLanguage() ] ) )
				{
					$rows[ Lang::defaultLanguage() ] = array( 'title' => Lang::load( Lang::defaultLanguage() )->title, 'count' => 0 );
				}
				$rows[ Lang::defaultLanguage() ]['count'] += $lang['count'];
			}
		}
		
		/* Now add the rows to the chart */
		foreach( $rows AS $row )
		{
			$chart->addRow( array( 'key' => $row['title'], 'value' => $row['count'] ) );
		}
		
		$chart->title = Member::loggedIn()->language()->addToStack('stats_language_title');
		$chart->availableTypes = array( 'PieChart' );

		return $chart;
	}
}