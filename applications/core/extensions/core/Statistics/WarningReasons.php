<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		13 Dec 2022
 */

namespace IPS\core\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart as ParentClass;
use IPS\core\Warnings\Reason;
use IPS\DateTime;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class WarningReasons extends ParentClass
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_stats_warnings_reason';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart	= new Database( $url, 'core_members_warn_logs', 'wl_date', '', array(
			'isStacked' => TRUE,
			'backgroundColor' 	=> '#ffffff',
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		), 'LineChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'wl_reason', 'wl_date' ), 'warnings_reason' );
		$chart->setExtension( $this );

		$chart->groupBy = 'wl_reason';

		foreach( Reason::roots() as $reason )
		{
			$chart->addSeries(  Member::loggedIn()->language()->addToStack('core_warn_reason_' . $reason->id ), 'number', 'COUNT(*)', TRUE, $reason->id );
		}

		$chart->title = Member::loggedIn()->language()->addToStack('stats_warnings_title');
		$chart->availableTypes = array( 'LineChart', 'AreaChart', 'ColumnChart', 'BarChart' );
		$chart->extension = $this;
		
		$chart->tableInclude = array( 'wl_moderator', 'wl_member', 'wl_reason', 'wl_date' );
		$chart->tableParsers = array(
			'wl_moderator'	=> function( $val ) {
				return Theme::i()->getTemplate( 'global', 'core', 'admin' )->userLinkWithPhoto( Member::load( $val ) );
			},
			'wl_member'		=> function( $val ) {
				return Theme::i()->getTemplate( 'global', 'core', 'admin' )->userLinkWithPhoto( Member::load( $val ) );
			},
			'wl_reason'		=> function( $val ) {
				return Member::loggedIn()->language()->addToStack( Reason::$titleLangPrefix . $val );
			},
			'wl_date'	=> function( $val )
			{
				return (string) DateTime::ts( $val );
			}
		);
		
		return $chart;
	}
}