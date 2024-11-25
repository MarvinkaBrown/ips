<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		22 Apr 2022
 */

namespace IPS\nexus\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription\Package;
use OutOfRangeException;
use UnderflowException;
use function defined;
use const IPS\REBUILD_NORMAL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class FixMissingSubscriptionPurchases extends QueueAbstract
{
	/**
	 * @brief Number of subscriptions to check per cycle
	 */
	public int $batch = REBUILD_NORMAL;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		try
		{
			$data['count'] = Db::i()->select( 'count(sub_id)', 'nexus_member_subscriptions' )->first();
		}
		catch( UnderflowException )
		{
			throw new OutOfRangeException;
		}

		if( $data['count'] == 0 )
		{
			return NULL;
		}

		$data['completed'] = 0;

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
		$last = null;

		foreach( Db::i()->select( '*', 'nexus_member_subscriptions', [ 'sub_id>?', $offset ], NULL, $this->batch ) as $subscription )
		{
			if( $subscription['sub_purchase_id'] )
			{
				try
				{
					Purchase::load( $subscription['sub_purchase_id' ] );

					/* Nothing to do here */
					$data['completed']++;
					$last = $subscription['sub_id'];
					continue;
				}
				catch( OutOfRangeException ) { }
			}
			try
			{
				$package = Package::load( $subscription['sub_package_id'] );

				if( !$subscription['sub_added_manually'] AND $subscription['sub_renews'] )
				{
					$term = $package->renewalTerm( Customer::load( $subscription['sub_member_id'] )->defaultCurrency() );
					$termData = $term->getTerm();
				}

				/* Create purchase manually - we don't want to trigger any on* methods */
				$purchaseId = Db::i()->insert( 'nexus_purchases', [
					'ps_member'             => $subscription['sub_member_id'],
					'ps_name'               => Customer::load( $subscription['sub_member_id'] )->language()->get( $package::$titleLangPrefix . $subscription['sub_package_id'] ),
					'ps_active'             => $subscription['sub_active'],
					'ps_cancelled'          => $subscription['sub_cancelled'],
					'ps_start'              => $subscription['sub_start'],
					'ps_expire'             => $subscription['sub_added_manually'] ? 0 : $subscription['sub_expire'],
					'ps_renewals'           => $termData['term'] ?? 0,
					'ps_renewal_price'      => $term->cost->amount ?? 0,
					'ps_renewal_unit'       => $termData['unit'] ?? '',
					'ps_app'                => 'nexus',
					'ps_type'               => 'subscription',
					'ps_item_id'            => $subscription['sub_package_id'],
					'ps_renewal_currency'   => $term->cost->currency ?? '',
					'ps_original_invoice'   => $subscription['sub_invoice_id'],
					'ps_tax'                => isset( $term->tax ) ? $term->tax->id : 0
				]);

				/* update sub with purchase id */
				Db::i()->update( 'nexus_member_subscriptions', [ 'sub_purchase_id' => $purchaseId ], [ 'sub_id=?', $subscription['sub_id'] ] );
			}
			catch ( OutOfRangeException )
			{}


			$data['completed']++;
			$last = $subscription['sub_id'];
		}

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		return $last;
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
		return array( 'text' =>  Member::loggedIn()->language()->addToStack( 'nexus_queue_missing_sub_purchases' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}
}