<?php
/**
 * @brief		icalendar Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		18 Dec 2013
 */

namespace IPS\calendar\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Icalendar as iCalendarClass;
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
 * icalendar Task
 */
class icalendar extends Task
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
	 * @throws	Exception
	 */
	public function execute() : mixed
	{
		try
		{
			$next	= Db::i()->select( '*', 'calendar_import_feeds', NULL, 'feed_last_run ASC', 1 )->first();

			if( $next['feed_id'] )
			{
				/* Refresh the feed */
				try
				{
					$count	= iCalendarClass::load( $next['feed_id'] )->refresh();
				}
				catch( \Exception $e )
				{
					throw new Exception( $this, array( 'task_' . $e->getMessage(), $next['feed_title'] ) );
				}
			}
		}
		catch( UnderflowException $e )
		{
			return NULL;
		}

		/* Task log */
		return NULL;
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