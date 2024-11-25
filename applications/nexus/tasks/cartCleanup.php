<?php
/**
 * @brief		cartCleanup Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	nexus
 * @since		17 Feb 2016
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\File;
use IPS\Session\Store;
use IPS\Task;
use IPS\Task\Exception;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * cartCleanup Task
 */
class cartCleanup extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	string|null	Message to log or NULL
	 * @throws	Exception
	 */
	public function execute() : string|null
	{
		$ids = array();
		
		/* Fetch active session IDs */
		foreach ( Db::i()->select( 'nexus_cart_uploads.id', 'nexus_cart_uploads', array( 'time<? AND ' . Db::i()->in( 'session_id', Store::i()->getSessionids(), true ), ( time() - 86400 ) ) ) as $id )
		{
			File::unclaimAttachments( 'nexus_Purchases', $id, NULL, 'cart' );
			$ids[] = $id;
		}
		
		Db::i()->delete( 'nexus_cart_uploads', Db::i()->in( 'id', $ids ) );
				
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
	public function cleanup() : void
	{
		
	}
}