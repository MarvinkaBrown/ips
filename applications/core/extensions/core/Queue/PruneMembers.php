<?php
/**
 * @brief		Background Task: Prune members
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 May 2016
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use OutOfBoundsException;
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
 * Background Task: Prune members
 */
class PruneMembers extends QueueAbstract
{

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = $this->getQuery( 'COUNT(*)', $data )->first();

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['skip_ids'] = array();

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
		/* Skip accounts that we couldn't remove previously */
		if( count( $data['skip_ids'] ) )
		{
			$data['where'] = array_merge( $data['where'], array( Db::i()->in( 'core_members.member_id', $data['skip_ids'], TRUE ) ) );
		}

		$total	= $this->getQuery( 'COUNT(*)', $data )->first();

		if ( !$total )
		{
			if( !empty( $data['group'] ) )
			{
				$cacheKey = 'groupMembersCount_' . $data['group'];
				unset( Store::i()->$cacheKey );
			}

			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$select	= $this->getQuery( 'core_members.*', $data, TRUE );

		foreach( $select AS $row )
		{
			try
			{
				$member = Member::constructFromData( $row );

				if ( $member->member_id == Member::loggedIn()->member_id OR ( $member->isAdmin() AND !Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) ) )
				{
					throw new OutOfBoundsException;
				}

				$member->delete();
			}
			catch( Exception $e )
			{
				$data['skip_ids'][] = $row['member_id'];
			}

			$offset++;
		}

		return $offset;
	}

	/**
	 * Return the query
	 *
	 * @param	string	$select		What to select
	 * @param	array	$data		Queue data
	 * @param	bool|int	$applyLimit	Whether or not to apply the limit
	 * @return	Select
	 */
	protected function getQuery( string $select, array $data, int|bool $applyLimit=FALSE ) : Select
	{
		return Db::i()->select( $select, 'core_members', $data['where'], 'core_members.member_id ASC', $applyLimit ? array( 0, REBUILD_SLOW ) : array() )
			->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' )
			->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id')
			->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
			->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );
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
		$text = Member::loggedIn()->language()->addToStack('pruning_members', FALSE );

		return array( 'text' => $text, 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}
}