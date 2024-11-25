<?php
/**
 * @brief		weeklyCleanup Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		27 Nov 2023
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * weeklyCleanup Task
 */
class weeklyCleanup extends Task
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
		/* If we are currently pruning any large tables via a bg task, find out so we don't try to prune them normally here as well. The bg task should finish first. */
		$currentlyPruning = array();

		foreach( Db::i()->select( '*', 'core_queue', array( '`key`=?', 'PruneLargeTable' ) ) as $pruneTask )
		{
			$data = json_decode( $pruneTask['data'], true );

			$currentlyPruning[] = $data['table'];
		}

		/* Delete old follows */
		if ( Settings::i()->prune_follows AND !in_array( 'core_follow', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_follow', array( 'follow_app!=? AND follow_area!=? AND follow_member_id IN(?)', 'core', 'member', Db::i()->select( 'member_id', 'core_members', array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_follows . 'D' ) )->getTimestamp() ) ) ) );

			/* And clear the cache so it can rebuild */
			Db::i()->delete( 'core_follow_count_cache' );
		}

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