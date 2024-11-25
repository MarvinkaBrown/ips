<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Jan 2020
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class PruneLargeTable extends QueueAbstract
{
	/**
	 * @brief	Number of rows to prune at once
	 */
	const ROWS_TO_PRUNE = 10000;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		/* How many rows are there total, and how many will we be pruning? */
		$data['total']		= Db::i()->select( 'COUNT(*)', $data['table'] )->first();
		$data['count']		= Db::i()->select( 'COUNT(*)', $data['table'], $data['where'] )->first();
		$data['pruned']		= 0;

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
		if( isset( $data['deleteJoin'] ) )
		{
			$select = Db::i()->select( $data['deleteJoin']['column'], $data['deleteJoin']['table'], $data['deleteJoin']['where'], $data['deleteJoin']['column'] . ' ASC', $offset + static::ROWS_TO_PRUNE );

			$deleted = Db::i()->delete( $data['table'], $select, NULL, NULL, array( $data['deleteJoin']['outerColumn'], $data['deleteJoin']['column'] ), Db::i()->prefix . $data['table'] );
		}
		else
		{
			$deleted = Db::i()->delete( $data['table'], $data['where'], $data['orderBy'] ?? NULL, static::ROWS_TO_PRUNE );
		}

		/* Are we done? */
		if( !$deleted )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$data['pruned'] += $deleted;

		return $offset + static::ROWS_TO_PRUNE;
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
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'backgroundQueue_pruning_table', FALSE, array( 'sprintf' => Member::loggedIn()->language()->addToStack( 'prunetable_' . $data['setting'] ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['pruned'], 2 ) ) : 100 );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{
		/* If this was pruning follows, make sure to clear the follow count cache so it can rebuild */
		if( $data['setting'] == 'prune_follows' )
		{
			Db::i()->delete( 'core_follow_count_cache' );
		}
	}
}