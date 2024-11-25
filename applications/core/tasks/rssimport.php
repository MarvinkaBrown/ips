<?php
/**
 * @brief		rssimport Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		05 Feb 2014
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\core\Rss\Import;
use IPS\DateTime;
use IPS\Db;
use IPS\Task;
use IPS\Task\Exception;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * rssimport Task
 */
class rssimport extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return    mixed    Message to log or NULL
	 * @throws    Exception
	 * @throws \Exception
	 */
	public function execute() : mixed
	{
		$timeCheck = new DateTime;
		$timeCheck->sub( new DateInterval( 'PT10M' ) );

		$this->runUntilTimeout(function() use ( $timeCheck ) {
			try
			{
				$feed = Import::constructFromData( Db::i()->select( '*', 'core_rss_import', array( 'rss_import_enabled=1 AND rss_import_last_import<?', $timeCheck->getTimestamp() ), 'rss_import_last_import ASC', 1 )->first() );
				$feed->run();
			}
			/* There's nothing more left to process */
			catch ( UnderflowException $e )
			{
				/* Check to see if there are any feeds.. If no feed, we can disable the task */
				if( !Db::i()->select( 'count(rss_import_id)', 'core_rss_import', array( 'rss_import_enabled=1' ), NULL, 1 )->first() )
				{
					Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'rssimport' ) );
				}

				/* No further processing needed */
				return FALSE;
			}
			/* Any other exception means an error which should be logged */
			catch ( \Exception $e )
			{
				/* If there is an error, we need to log it but the error should not prevent other feeds from importing */
				if ( isset( $feed ) AND ( $feed instanceof Import ) )
				{
					$feed->last_import = time();
					$feed->save();
				}
				throw new Exception( $this, $e->getMessage() );
			}

			/* Run again to see if there's anything left */
			return TRUE;
		});

		return null;
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
	public function cleanup()
	{
		
	}
}