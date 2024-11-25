<?php
/**
 * @brief		Cancel billing agreements before removing the payment method
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		14 Jan 2020
 */

namespace IPS\nexus\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use IPS\nexus\Customer\BillingAgreement;
use IPS\nexus\Gateway;
use OutOfRangeException;
use function count;
use function defined;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Cancel billing agreements before removing the payment method
 */
class DeletePaymentMethod extends QueueAbstract
{
	/**
	 * @brief	Number of records to process per cycle
	 */
	public int $perCycle = REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = Db::i()->select( 'COUNT(*)', 'nexus_billing_agreements', array( 'ba_method=? AND ba_canceled=?', $data['id'], 0 ) )->first();

		if( $data['count'] == 0 )
		{
			return NULL;
		}

		$data['done']		= 0;
		$data['failures']	= array();

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$agreements = Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_method=? AND ba_id>?', $data['id'], $offset ), 'ba_id ASC', $this->perCycle );
		$lastId = 0;
		
		foreach( $agreements as $billingAgreement )
		{
			try
			{					
				BillingAgreement::constructFromData( $billingAgreement )->cancel();
			}
			catch ( Exception $e )
			{
				/* Store the billing agreement ID so we can notify the admin once we're done */
				$data['failures'][] = $billingAgreement['ba_id'];

				Log::log( $e, 'delete-nexus-ba' );
			}

			$lastId = $billingAgreement['ba_id'];
		}
		
		if( $lastId == 0 )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$data['done'] += $this->perCycle;

		return $lastId;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' => Member::loggedIn()->language()->addToStack('delete_gateway_with_bas'), 'complete' => $data['done'] ? ( round( 100 / $data['done'] * $data['count'], 2 ) ) : 0  );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param array $data
	 * @param bool $processed
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{
		$_data = json_decode( $data['data'], true );

		/* Send notification about any failures */
		if( count( $_data['failures'] ) )
		{
			AdminNotification::send( 'nexus', 'ConfigurationError', json_encode( $_data['failures'] ), FALSE );
		}

		/* And delete the gateway itself */
		Gateway::load( $_data['id'] )->delete();
	}
}