<?php
/**
 * @brief		onlineusers Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Mar 2017
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Session\Store;
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
 * onlineusers Task
 */
class onlineusers extends Task
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
		/* We want the rows to have the same timestamp exactly */
		$time = time();

		$onlineGuests = 0;
		$onlineMembers = 0;

		try
		{
			$onlineGuests = Store::i()->getOnlineUsers( Store::ONLINE_GUESTS | Store::ONLINE_COUNT_ONLY, 'desc', NULL, NULL, TRUE );
			$onlineMembers = Store::i()->getOnlineUsers( Store::ONLINE_MEMBERS | Store::ONLINE_COUNT_ONLY, 'desc', NULL, NULL, TRUE );
		}
		catch( UnderflowException $e) {}

		Db::i()->insert( 'core_statistics', array(
			'type'		=> 'online_users',
			'time'		=> $time,
			'value_4'	=> 'guests',
			'value_1'	=> $onlineGuests
		)	);

		Db::i()->insert( 'core_statistics', array(
			'type'		=> 'online_users',
			'time'		=> $time,
			'value_4'	=> 'members',
			'value_1'	=> $onlineMembers
		)	);

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