<?php
/**
 * @brief		billingAgreements Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Dec 2015
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Db;
use IPS\Log;
use IPS\nexus\Customer\BillingAgreement;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use Throwable;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * billingAgreements Task
 */
class billingAgreements extends Task
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
	 * @throws    Task\Exception
	 */
	public function execute() : string|null
	{
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_next_cycle<?', time() ), 'ba_next_cycle ASC', 50 ), 'IPS\nexus\Customer\BillingAgreement' ) as $billingAgreement )
		{
			/* @var BillingAgreement $billingAgreement */
			try
			{
				/* Get the status? */
				if ( $billingAgreement->status() != $billingAgreement::STATUS_ACTIVE )
				{
					if ( $billingAgreement->status() == $billingAgreement::STATUS_CANCELED )
					{
						$billingAgreement->canceled = TRUE;
					}
					throw new DomainException("{$billingAgreement->id} - {$billingAgreement->status()}");
				}

				/* Get the term */
				$term = $billingAgreement->term();

				/* Check for a recent transaction */
				$billingAgreement->checkForLatestTransaction();
			}
			catch( UnderflowException ){}	// We intentionally ignore this because it's normal
			catch ( \IPS\Http\Url\Exception $e )
			{
				/* Just log the issue, but don't do anything here,.. this was probably a temporary issue so let the next call run this again */
				Log::log( $e, 'ba_sync_fail' );
			}
			catch ( Exception|Throwable $e )
			{
				Log::log( $e, 'ba_sync_fail' );

				$billingAgreement->next_cycle = NULL;
				$billingAgreement->save();
			}
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