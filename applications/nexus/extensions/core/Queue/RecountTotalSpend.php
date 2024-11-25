<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		08 Aug 2018
 */

namespace IPS\nexus\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\nexus\Customer;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RecountTotalSpend extends QueueAbstract
{

	/**
	 * @brief	Number of records to process per cycle
	 */
	public int $perCycle = REBUILD_QUICK;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = (int) Db::i()->select( 'count(*)', 'nexus_customers' )->first();

		if( $data['count'] == 0 )
		{
			return null;
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
		$last	= null;

		/* Loop over members to fix them */
		foreach( Db::i()->select( '*', 'nexus_customers', array( 'member_id > ?', $offset ), 'member_id ASC', array( 0, $this->perCycle ) ) as $customer )
		{
			$last = $customer['member_id'];
			$data['completed']++;

			try
			{
				$customer = Customer::load( $customer['member_id'] );
				$customer->recountTotalSpend();
			}
			catch( Exception ) { }
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
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'recounting_total_spend' ), 'complete' => $data['count'] ? ( round(  100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}
}