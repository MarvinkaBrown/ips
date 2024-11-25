<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Commerce
 * @since		26 Jan 2023
 */

namespace IPS\nexus\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
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
class Sales extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'nexus_reports_purchases';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database(
			$url,
			'nexus_purchases',
			'ps_start',
			'',
			array(
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			)
		);
		$chart->setExtension( $this );
		$chart->where[] = array( 'ps_app=? AND ps_type=?', 'nexus', 'package' );
		$chart->groupBy = 'ps_item_id';
		$chart->tableInclude	= array( 'ps_id', 'ps_member', 'ps_name', 'ps_start', 'ps_expire' );
		$chart->tableParsers	= array( 
			'ps_member' => function( $val ) {
				return Theme::i()->getTemplate('global', 'nexus')->userLink( Member::load( $val ) );
			},
			'ps_start'	=> function( $val ) {
				return DateTime::ts( $val );
			},
			'ps_expire'	=> function( $val ) {
				return $val ? DateTime::ts( $val ) : '';
			}
		);
		
		$packages = array();
		foreach ( Db::i()->select( 'p_id', 'nexus_packages' ) as $packageId )
		{
			$packages[ $packageId ] = Member::loggedIn()->language()->get( 'nexus_package_' . $packageId );
		}
		
		asort( $packages );
		foreach ( $packages as $id => $name )
		{
			$chart->addSeries( $name, 'number', 'COUNT(*)', TRUE, $id );
		}
		
		return $chart;
	}
}