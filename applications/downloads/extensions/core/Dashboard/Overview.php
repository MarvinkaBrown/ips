<?php
/**
 * @brief		Dashboard extension: Overview
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		13 Dec 2013
 */

namespace IPS\downloads\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\downloads\File;
use IPS\Extensions\DashboardAbstract;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Overview
 */
class Overview extends DashboardAbstract
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView(): bool
	{
		return TRUE;
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		$oneMonthAgo = DateTime::create()->sub( new DateInterval( 'P1M' ) )->getTimestamp();
		
		/* Basic stats */
		$data = array(
			'total_disk_spaced'			=> (int) Db::i()->select( 'SUM(record_size)', 'downloads_files_records' )->first(),
			'total_files'				=> (int) Db::i()->select( 'COUNT(*)', 'downloads_files' )->first(),
			'total_views'				=> (int) Db::i()->select( 'SUM(file_views)', 'downloads_files' )->first(),
			'total_downloads'			=> (int) Db::i()->select( 'SUM(file_downloads)', 'downloads_files' )->first(),
			'total_bandwidth'			=> (int) Db::i()->select( 'SUM(dsize)', 'downloads_downloads' )->first(),
			'current_month_bandwidth'	=> (int) Db::i()->select( 'SUM(dsize)', 'downloads_downloads', array( 'dtime>?', $oneMonthAgo ) )->first(),
		);
		
		/* Specific files (will fail if no files yet) */
		try
		{
			$data['largest_file'] = File::constructFromData( Db::i()->select( '*', 'downloads_files', NULL, 'file_size DESC', 1 )->first() );
			$data['most_viewed_file'] = File::constructFromData( Db::i()->select( '*', 'downloads_files', NULL, 'file_views DESC', 1 )->first() );
			$data['most_downloaded_file'] = File::constructFromData( Db::i()->select( '*', 'downloads_files', NULL, 'file_downloads DESC', 1 )->first() );
		}
		catch ( Exception $e ) { }
		
		/* Display */
		return Theme::i()->getTemplate( 'dashboard', 'downloads' )->overview( $data );
	}
}