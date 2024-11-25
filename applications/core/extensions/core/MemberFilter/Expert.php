<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		05 Aug 2023
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db\Select;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Radio;
use IPS\Member;
use IPS\Platform\Bridge;
use LogicException;
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
class Expert extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		if( !Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			return false;
		}

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
		if( !Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			return [];
		}

		$options = array( 'any' => 'any', 'expert' => 'mf_expert_expert', 'notexpert' => 'mf_expert_not_expert' );

		return array(
			new Radio( 'mf_expert', $criteria['expert'] ?? 'any', FALSE, array( 'options' => $options ) ),
		);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return    array|bool            False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ) : array|bool
	{
		return ( isset( $post['mf_expert'] ) and in_array( $post['mf_expert'], array( 'expert', 'notexpert' ) ) ) ? array( 'expert' => $post['mf_expert'] ) : FALSE;

	}

	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( isset( $data['expert'] ) )
		{
			switch ( $data['expert'] )
			{
				case 'expert':
					return array( "( e.member_id>0 )" );
					
				case 'notexpert':
					return array( "( e.member_id IS NULL )" );
					
			}
		}

		return NULL;
	}

	/**
	 * Callback for member retrieval database query
	 * Can be used to set joins
	 *
	 * @param array $data	The array returned from the save() method
	 * @param	Select	$query	The query
	 * @return	void
	 */
	public function queryCallback( array $data, Select $query ): void
	{
		if( isset( $data['expert'] ) )
		{
			$query->join( [ 'core_expert_users', 'e' ], "core_members.member_id=e.member_id" );
		}

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
	public function matches( Member $member, array $filters, ?object $object=NULL ): bool
	{
		if( !Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			return false;
		}

		return $member->isExpert();
	}
}