<?php
/**
 * @brief		Clear temporary uploads task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		18 Mar 2014
 */

namespace IPS\gallery\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\Lang;
use IPS\Task;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clear temporary uploads task
 */
class clearUploads extends Task
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
		$cutoff		= DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp();
		$sessions	= array();
		$messages   = array();
		$errorLang  = Lang::load( Lang::defaultLanguage() )->get( 'gallery_clear_uploads_error' );

		foreach( Db::i()->select( '*', 'gallery_images_uploads', array( 'upload_date < ?', $cutoff ) ) as $upload )
		{
			try
			{
				$file = File::get( 'gallery_Images', $upload['upload_location'] );
				$file->delete();
			}
			catch ( Exception $e )
			{
				$messages[] = sprintf( $errorLang, $file ?? '', $e->getMessage() );
			}

			$sessions[ $upload['upload_session'] ]	= $upload['upload_session'];
		}

		if( count( $sessions ) )
		{
			Db::i()->delete( 'gallery_images_uploads', array( array( "upload_session IN('" . implode( "','", $sessions ) . "')" ) ) );
		}

		return !empty( $messages ) ? implode( '; ', $messages ) : NULL;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup() : void
	{
		
	}
}