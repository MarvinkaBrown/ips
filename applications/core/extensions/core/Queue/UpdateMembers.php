<?php
/**
 * @brief		Background Task: Update all members at once
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Jun 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Update all members at once
 */
class UpdateMembers extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $perCycle = 50000;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = Db::i()->select( 'COUNT(*)', 'core_members' )->first();
		$data['done'] = 0;
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
		/* Make sure there's stuff to update */
		if( ! isset( $data['update'] ) or ! count( $data['update'] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$lastId = ( isset( $data['lastId'] ) ) ? $data['lastId'] : 0;
		$maxId = NULL;
		
		try
		{
			$maxId = Db::i()->select( 'member_id', 'core_members', 'member_id > ' . $lastId, 'member_id ASC', array( $this->perCycle - 1, 1 ) )->first();
		}
		catch( UnderflowException $e ) { }
		
		$realMaxId = Db::i()->select( 'MAX(member_id)', 'core_members' )->first();
		
		if ( ! $maxId )
		{
			if ( $lastId < $realMaxId )
			{
				$maxId = $realMaxId;
			}
			else
			{
				/* All done really then */
				throw new \IPS\Task\Queue\OutOfRangeException;
			}
		}
		
		if ( $maxId )
		{
			$update = NULL;
			$firstKey = key( $data['update'] );
			
			if ( count( $data['update'] ) == 1 and is_numeric( $firstKey ) )
			{
				$update = $data['update'][0];
			}
			else
			{
				$update = $data['update'];
			}
			
			if ( ! $update )
			{
				throw new \IPS\Task\Queue\OutOfRangeException;
			}
			
			Db::i()->update( 'core_members', $update, array( 'member_id >= ? and member_id <= ?', $lastId, $maxId ) );
			
			$data['lastId'] = $maxId;
			$data['done'] += $this->perCycle;
			return $lastId;
		}
		else
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
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
		$fields = NULL;
		$firstKey = key( $data['update'] );
		
		if ( count( $data['update'] ) == 1 and is_numeric( $firstKey ) )
		{
			$fields = mb_substr( $data['update'][0], 0, mb_strpos( $data['update'][0], '=' ) );
		}
		else
		{
			$fields = implode( ',', array_keys( $data['update'] ) );
		}
	
		return array( 'text' => Member::loggedIn()->language()->addToStack('mass_member_update', FALSE, array( 'sprintf' => array( $fields ) ) ), 'complete' => $data['done'] ? ( round( 100 / $data['done'] * $data['count'], 2 ) ) : 0 );
	}
}