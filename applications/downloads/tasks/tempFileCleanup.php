<?php
/**
 * @brief		tempFileCleanup Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		10 Oct 2013
 */

namespace IPS\downloads\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tempFileCleanup Task
 */
class tempFileCleanup extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws    Task\Exception
	 */
	public function execute() : mixed
	{
		foreach ( Db::i()->select( '*', 'downloads_files_records', array( 'record_file_id=0 AND record_time<?', DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() ) ) as $file )
		{
			try
			{
				File::get( $file['record_type'] === 'upload' ? 'downloads_Files' : 'downloads_Screenshots', $file['record_location'] )->delete();
			}
			catch ( Exception $e ) { }

			if( $file['record_thumb'] )
			{
				try
				{
					File::get( 'downloads_Screenshots', $file['record_thumb'] )->delete();
				}
				catch ( Exception $e ) { }
			}

			if( $file['record_no_watermark'] )
			{
				try
				{
					File::get( 'downloads_Screenshots', $file['record_no_watermark'] )->delete();
				}
				catch ( Exception $e ) { }
			}
		}
		
		Db::i()->delete( 'downloads_files_records', array( 'record_file_id=0 AND record_time<?', DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() ) );
		
		Db::i()->delete( 'downloads_sessions', array( 'dsess_start<?', DateTime::create()->sub( new DateInterval( 'PT6H' ) )->getTimestamp() ) );
		
		return NULL;
	}
}