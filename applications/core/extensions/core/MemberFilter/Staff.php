<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		17 Sep 2019
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Select;
use IPS\Member;
use LogicException;
use function array_keys;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Filter Extension
 */
class Staff extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'passwordreset', 'bulkmail' ) );
	}

	/** 
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		return array(
			new Select( 'mf_staff_type', $criteria['staff_type'] ?? 'all', FALSE, array(
				'options' => array(
					'members'		=> 'mf_members_only',
					'admins_mods'	=> 'mf_admins_mods_only',
					'admins'		=> 'mf_admins_only',
					'mods'			=> 'mf_mods_only'
				),
				'unlimited'	=> 'all'
			) )
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
		if ( $post['mf_staff_type'] === 'all' )
		{
			return FALSE;
		}
		
		return array( 'staff_type' => $post['mf_staff_type'] );
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( isset( $data['staff_type'] ) AND $data['staff_type'] !== 'all' )
		{
			$members	= array();
			$groups		= array();
			$not		= FALSE;
			
			if ( in_array( $data['staff_type'], array( 'admins', 'admins_mods', 'members' ) ) )
			{
				foreach( array_keys( Member::administrators()['m'] ) AS $mid )
				{
					$members[] = $mid;
				}
				
				foreach( array_keys( Member::administrators()['g'] ) AS $gid )
				{
					$groups[] = $gid;
				}
			}
			
			if ( in_array( $data['staff_type'], array( 'mods', 'admins_mods', 'members' ) ) )
			{
				/* If we don't have a datastore of moderator configuration, load that now */
				if ( !isset( Store::i()->moderators ) )
				{
					Store::i()->moderators = array(
						'm'	=> iterator_to_array( Db::i()->select( '*', 'core_moderators', array( 'type=?', 'm' ) )->setKeyField( 'id' ) ),
						'g'	=> iterator_to_array( Db::i()->select( '*', 'core_moderators', array( 'type=?', 'g' ) )->setKeyField( 'id' ) ),
					);
				}

				foreach( Store::i()->moderators['m'] AS $mid )
				{
					$members[] = $mid;
				}
				
				foreach( Store::i()->moderators['g'] AS $gid )
				{
					$groups[] = $gid;
				}
			}
			
			if ( $data['staff_type'] === 'members' )
			{
				$not = TRUE;
			}
			
			return array( "(" . Db::i()->in( 'core_members.member_id', $members, $not ) . " OR " . Db::i()->in( 'member_group_id', $groups, $not ) . " OR " . Db::i()->findInSet( 'mgroup_others', $groups, $not ) . ")" );
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
		return TRUE;
	}
}