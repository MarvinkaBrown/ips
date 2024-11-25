<?php
/**
 * @brief		Member ACP Profile - Content Statistics Tab: Downloads
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Nov 2017
 */

namespace IPS\downloads\extensions\core\MemberACPProfileContentTab;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\Block;
use IPS\DateTime;
use IPS\Db;
use IPS\downloads\File;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Http\Useragent;
use IPS\Member;
use IPS\Output\Plugin\Filesize;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member ACP Profile - Content Statistics Tab: Downloads
 */
class Downloads extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$fileCount = Db::i()->select( 'COUNT(*)', 'downloads_files', array( 'file_submitter=?', $this->member->member_id ) )->first();
		$diskspaceUsed = Db::i()->select( 'SUM(file_size)', 'downloads_files', array( 'file_submitter=?', $this->member->member_id ) )->first();
		$numberOfDownloads = Db::i()->select( 'COUNT(*)', 'downloads_downloads', array( 'dmid=?', $this->member->member_id ) )->first();
		$totalDownloads = Db::i()->select( 'COUNT(*)', 'downloads_downloads' )->first();
		$bandwidthUsed = Db::i()->select( 'SUM(dsize)', 'downloads_downloads', array( 'dmid=?', $this->member->member_id ) )->first();

		$allFiles = Db::i()->select( 'COUNT(*)', 'downloads_files' )->first();
		$totalFileSize = Db::i()->select( 'SUM(file_size)', 'downloads_files' )->first();
		$totalDownloadSize = Db::i()->select( 'SUM(dsize)', 'downloads_downloads' )->first();

		return (string) Theme::i()->getTemplate( 'stats', 'downloads' )->information( $this->member, Theme::i()->getTemplate( 'global', 'core' )->definitionTable( array(
			'files_submitted'		=>
				Member::loggedIn()->language()->addToStack('downloads_stat_of_total', FALSE, array( 'sprintf' => array(
						Member::loggedIn()->language()->formatNumber( $fileCount ),
						Member::loggedIn()->language()->formatNumber( ( ( $allFiles ? ( 100 / $allFiles ) : 0 ) * $fileCount ), 2 ) ) )
				),
			'diskspace_used'		=>
				Member::loggedIn()->language()->addToStack('downloads_stat_of_total', FALSE, array( 'sprintf' => array(
						Filesize::humanReadableFilesize( $diskspaceUsed ),
						Member::loggedIn()->language()->formatNumber( ( ( $totalFileSize ? ( 100 / $totalFileSize ) : 0 ) * $diskspaceUsed ), 2 ) ) )
				),
			'average_filesize_downloads'		=>
				Member::loggedIn()->language()->addToStack('downloads_stat_average', FALSE, array( 'sprintf' => array(
						Filesize::humanReadableFilesize( Db::i()->select( 'AVG(file_size)', 'downloads_files', array( 'file_submitter=?', $this->member->member_id ) )->first() ),
						Filesize::humanReadableFilesize( Db::i()->select( 'AVG(file_size)', 'downloads_files' )->first() ) ))
				),
			'number_of_downloads'	=>
				Member::loggedIn()->language()->addToStack('downloads_stat_of_total_link', FALSE, array( 'htmlsprintf' => array(
						Member::loggedIn()->language()->formatNumber( $numberOfDownloads ),
						Member::loggedIn()->language()->formatNumber( ( ( $totalDownloads ? ( 100 / $totalDownloads ) : 0 ) * $numberOfDownloads ), 2 ),
						Theme::i()->getTemplate( 'stats', 'downloads' )->link( $this->member, 'downloads', 'downloads' ) ) )
				),
			'downloads_bandwidth_used'		=>
				Member::loggedIn()->language()->addToStack('downloads_stat_of_total_link', FALSE, array( 'htmlsprintf' => array(
						Filesize::humanReadableFilesize( $bandwidthUsed ),
						Member::loggedIn()->language()->formatNumber( ( ( $totalDownloadSize ? ( 100 / $totalDownloadSize ) : 0 ) * $bandwidthUsed ), 2 ),
						Theme::i()->getTemplate( 'stats', 'downloads' )->link( $this->member, 'bandwidth', 'bandwidth_use' ) ) )
				)
		) ) );
	}
	
	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		switch ( Request::i()->chart )
		{
			case 'downloads':
				$downloadsChart = new Database( Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\downloads\\extensions\\core\\MemberACPProfileContentTab\\Downloads&id={$this->member->member_id}&chart=downloads&_graph=1" ), 'downloads_downloads', 'dtime', '', array(
						'backgroundColor' 	=> '#ffffff',
						'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
						'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
						'lineWidth'			=> 1,
						'areaOpacity'		=> 0.4
					), 'ColumnChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'dfid', 'dtime', 'dsize', 'dua', 'dip' ) );
				$downloadsChart->where[] = array( 'dmid=?', $this->member->member_id );
				$downloadsChart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart', 'Table' );
				$downloadsChart->tableParsers = array(
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
				$downloadsChart->addSeries( Member::loggedIn()->language()->addToStack('downloads'), 'number', 'COUNT(*)', FALSE );
				$output = ( Request::i()->isAjax() and isset( Request::i()->_graph ) ) ? (string) $downloadsChart : Theme::i()->getTemplate( 'stats', 'downloads' )->graphs( (string) $downloadsChart );
			break;
		
			case 'bandwidth_use':
				$bandwidthChart = new Database( Url::internal( "app=core&module=members&controller=members&do=editBlock&block=IPS\\downloads\\extensions\\core\\MemberACPProfileContentTab\\Downloads&id={$this->member->member_id}&chart=bandwidth_use&_graph=1" ), 'downloads_downloads', 'dtime', '', array(
						'vAxis' => array( 'title' => '(' . Member::loggedIn()->language()->addToStack( 'filesize_raw_k' ) . ')' ),
						'backgroundColor' 	=> '#ffffff',
						'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
						'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
						'lineWidth'			=> 1,
						'areaOpacity'		=> 0.4 
					) );
				$bandwidthChart->where[] = array( 'dmid=?', $this->member->member_id );
				$bandwidthChart->addSeries( Member::loggedIn()->language()->addToStack('bandwidth_use'), 'number', 'ROUND((SUM(dsize)/1024),2)', FALSE );
				$output = ( Request::i()->isAjax() and isset( Request::i()->_graph ) ) ? (string) $bandwidthChart : Theme::i()->getTemplate( 'stats', 'downloads' )->graphs( (string) $bandwidthChart );
			break;
		}
		
		return $output;
	}
}