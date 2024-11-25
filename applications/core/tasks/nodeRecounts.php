<?php
/**
 * @brief		nodeRecounts Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Oct 2024
 */

namespace IPS\core\tasks;

use BadMethodCallException;
use Exception;
use IPS\Task;
use IPS\Task\Exception as TaskException;
use IPS\Data\Store;
use IPS\Node\Model;
use IPS\Redis;
use OutOfRangeException;
use RedisException;
use function array_key_exists;
use function is_array;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * nodeRecounts Task
 */
class nodeRecounts extends Task
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
	 * @throws	TaskException
	 */
	public function execute() : mixed
	{
		$allKeys = [];
		try
		{
			if( ! Redis::isEnabled() )
			{
				throw new BadMethodCallException;
			}

			$allKeys = Redis::i()->hGetAll('nodeSyncTimes');
		}
		catch( BadMethodCallException | RedisException )
		{
			try
			{
				$key = 'nodeSyncTimes';
				$allKeys = Store::i()->$key;
			}
			catch( OutOfRangeException ){}
		}

		if ( ! is_array( $allKeys ) or ! count( $allKeys ))
		{
			return null;
		}

		/* Now sort the keys by value, which is the timestamp */
		asort($allKeys);

		/* Now get the top 50 keys, which will the oldest timestamps */
		$allKeys = array_slice( $allKeys, 0, 50 );
		foreach( $allKeys as $classAndId => $time )
		{
			$classAndIdArray = explode('_', $classAndId);
			$class = $classAndIdArray[0];
			$id = $classAndIdArray[1];

			try
			{
				/* @var $class Model */
				$node = $class::load($id);

				/* Recount items, comments, reviews and the last comment data */
				$node->runScheduledRebuild();
			}
			catch( Exception $e )
			{
				continue;
			}

			/* Now remove the key */
			$node->clearUpdateTime();
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
	public function cleanup() : void
	{
		
	}
}