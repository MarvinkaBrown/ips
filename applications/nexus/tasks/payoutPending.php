<?php
/**
 * @brief		payoutPending Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		06 Dec 2022
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\nexus\Gateway;
use IPS\nexus\Payout;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use IPS\Task\Exception;
use function defined;
use function method_exists;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * payoutPending Task
 */
class payoutPending extends Task
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
		$iterator = new ActiveRecordIterator( Db::i()->select( '*', 'nexus_payouts', array( 'po_status=? AND po_gw_id IS NOT NULL', Payout::STATUS_PROCESSING ) ), 'IPS\nexus\Payout' );

		if( !$iterator->count() )
		{
			$this->enabled = FALSE;
			$this->save();
			return NULL;
		}

		foreach( $iterator as $payout )
		{
			$payoutClass = Gateway::payoutGateways()[ $payout->gateway ];

			if( !method_exists( $payoutClass, 'checkStatus' ) )
			{
				continue;
			}

			if( $payoutClass::checkStatus( $payout->gw_id ) == Payout::STATUS_COMPLETE )
			{
				$payout->markCompleted();
			}
		}

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