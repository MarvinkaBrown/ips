<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Downloads
 * @since		26 Jan 2023
 */

namespace IPS\downloads\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\downloads\File;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Http\Useragent;
use IPS\Member;
use IPS\Output\Plugin\Filesize;
use IPS\Theme;
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
class Downloads extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'downloads_stats_downloads';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database( $url, 'downloads_downloads', 'dtime', '', array(
			'backgroundColor' 	=> '#ffffff',
			'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		), 'AreaChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'dmid', 'dfid', 'dtime', 'dsize', 'dua', 'dip' ) );
		$chart->setExtension( $this );
		
		$chart->addSeries( Member::loggedIn()->language()->addToStack('downloads'), 'number', 'COUNT(*)', FALSE );
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );
		
		$chart->tableParsers = array(
			'dmid'	=> function( $val )
			{
				$member = Member::load( $val );

				if( $member->member_id )
				{
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $member->url(), TRUE, $member->name );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'dfid'	=> function( $val )
			{
				try
				{
					$file = File::load( $val );
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->url(), TRUE, $file->name );
				}
				catch ( OutOfRangeException $e )
				{
					return Member::loggedIn()->language()->addToStack('deleted_file');
				}
			},
			'dtime'	=> function( $val )
			{
				return (string) DateTime::ts( $val );
			},
			'dsize'	=> function( $val )
			{
				return Filesize::humanReadableFilesize( $val );
			},
			'dua'	=> function( $val )
			{
				return (string) Useragent::parse( $val );
			},
			'dip'	=> function( $val )
			{
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( Url::internal( "app=core&module=members&controller=ip&ip={$val}&tab=downloads_DownloadLog" ), FALSE, $val );
			}
		);
		
		return $chart;
	}
}