<?php
/**
 * @brief		advertStats Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		03 Apr 2023
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use IPS\Task\Exception;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * advertStats Task
 */
class advertStats extends Task
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
		$inserts = [];
		$time = time();

		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_advertisements' ), '\IPS\core\Advertisement' ) as $advert )
		{
			$inserts[] = [
				'type' => 'advert',
				'value_1' => $advert->id,
				'value_2' => $advert->daily_impressions,
				'value_3' => $advert->daily_clicks,
				'time'    => $time
			];
		}

		if( count( $inserts ) )
		{
			Db::i()->insert( 'core_statistics', $inserts );
		}

		Db::i()->update( 'core_advertisements', [ 'ad_daily_impressions' => 0, 'ad_daily_clicks' => 0 ] );

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