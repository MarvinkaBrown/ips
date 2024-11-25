<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		26 July 2017
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task\Queue\OutOfRangeException;
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
class RecountClubMembers extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = Db::i()->select( 'count(id)', 'core_clubs' )->first();

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
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$last = NULL;

		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_clubs', array( "id>?", $offset ), "id ASC", array( 0, REBUILD_NORMAL ) ), 'IPS\Member\Club' ) AS $club )
		{
			$club->recountMembers();
			$last = $club->id;
			$data['completed']++;
		}

		if( $last === NULL )
		{
			throw new OutOfRangeException;
		}

		return $last;
	}

	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array	Text explaining task and percentage complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' =>  Member::loggedIn()->language()->addToStack( 'queue_recounting_club_members' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}
}