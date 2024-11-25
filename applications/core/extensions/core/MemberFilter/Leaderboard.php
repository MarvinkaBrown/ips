<?php
/**
 * @brief		Member filter extension: Won member of the day
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Apr 2017
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Radio;
use IPS\Member;
use LogicException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member filter: Won member of the day
 */
class Leaderboard extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail', 'group_promotions' ) );
	}

	/**
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		$options = array( 'any' => 'any', 'active' => 'mf_leaderboard_active', 'inactive' => 'mf_leaderboard_inactive' );

		return array(
			new Radio( 'mf_leaderboard', $criteria['leaderboard'] ?? 'any', FALSE, array( 'options' => $options ) ),
		);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return    array|bool            False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ): array|bool
	{
		return ( isset( $post['mf_leaderboard'] ) and in_array( $post['mf_leaderboard'], array( 'active', 'inactive' ) ) ) ? array( 'leaderboard' => $post['mf_leaderboard'] ) : FALSE;
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause", ...binds )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if( isset( $data['leaderboard'] ) )
		{
			$leaderBoardSelect = Db::i()->select( 'leader_member_id', 'core_reputation_leaderboard_history', 'leader_position=1', NULL, NULL, NULL, NULL, Db::SELECT_DISTINCT );
			return ( $data['leaderboard'] == 'active' ) ? array( 'core_members.member_id IN(' . $leaderBoardSelect . ')' ) : array( 'core_members.member_id NOT IN(' . $leaderBoardSelect . ')' );
		}

		return NULL;
	}

	/**
	 * Determine if a member matches specified filters
	 *
	 * @note	This is only necessary if availableIn() includes group_promotions
	 * @param	Member	$member		Member object to check
	 * @param	array 		$filters	Previously defined filters
	 * @param	object|NULL	$object		Calling class
	 * @return	bool
	 */
	public function matches( Member $member, array $filters, ?object $object=NULL ) : bool
	{
		/* If we aren't filtering by this, then any member matches */
		if( !isset( $filters['leaderboard'] ) OR !$filters['leaderboard'] )
		{
			return TRUE;
		}

		$result = (bool) Db::i()->select( 'COUNT(*)', 'core_reputation_leaderboard_history', array( 'leader_position=? AND leader_member_id=?', 1, $member->member_id ) )->first();

		return ( $filters['leaderboard'] == 'active' ) ? $result : !$result;
	}
}