<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Events
 * @since		30 Jan 2024
 */

namespace IPS\calendar\extensions\core\MemberFilter;

use IPS\calendar\Event;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Item;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use LogicException;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Filter Extension
 */
class Rsvp extends MemberFilterAbstract
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
	 * @param	array	$criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		return array(
			new YesNo( 'mf_rsvp', $criteria['rsvp'] ?? null, false, array( 'togglesOn' => array( 'mf_rsvp_events' ) ) ),
			new Item( 'mf_rsvp_events', $criteria['rsvp_events'] ?? -1, false, array( 'class' => Event::class, 'maxItems' => null ), null, null, null, 'mf_rsvp_events' )
		);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return	array|bool			False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ) : array|bool
	{
		if( isset( $post['mf_rsvp'] ) and $post['mf_rsvp'] )
		{
			$events = [];
			if( is_array( $post['mf_rsvp_events'] ) )
			{
				foreach( $post['mf_rsvp_events'] as $event )
				{
					$events[] = $event->id;
				}
			}

			return [
				'rsvp' => true,
				'rsvp_events' => count( $events ) ? $events : -1
			];
		}

		return FALSE;
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param	array				$data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if( isset( $data['rsvp'] ) and $data['rsvp'] )
		{
			$where = [
				[ 'rsvp_response<>?', Event::RSVP_NO ]
			];

			if( isset( $data['events'] ) and $data['events'] != -1 )
			{
				$where[] = [ Db::i()->in( 'rsvp_event_id', $data['events'] ) ];
			}

			return $where;
		}

		return NULL;
	}
	
	/**
	 * Callback for member retrieval database query
	 * Can be used to set joins
	 *
	 * @param	array			$data	The array returned from the save() method
	 * @param	Select	$query	The query
	 * @return	void
	 */
	public function queryCallback( array $data, Select $query ) : void
	{
		if( isset( $data['rsvp'] ) and $data['rsvp'] )
		{
			$query->join( 'calendar_event_rsvp', "core_members.member_id=calendar_event_rsvp.rsvp_member_id" );
		}
	}

	/**
	 * Determine if a member matches specified filters
	 *
	 * @note	This is only necessary if availableIn() includes group_promotions
	 * @param	Member  	$member		Member object to check
	 * @param	array 		$filters	Previously defined filters
	 * @param	object|NULL	$object		Calling class
	 * @return	bool
	 */
	public function matches( Member $member, array $filters, ?object $object=NULL ): bool
	{
		if( !isset( $filters['rsvp'] ) or !$filters['rsvp'] )
		{
			return true;
		}

		$where = [
			[ 'rsvp_member_id=?', $member->member_id ]
		];

		if( isset( $filters['events'] ) and $filters['events'] !== -1 )
		{
			$where[] = [ Db::i()->in( 'rsvp_event_id', $filters['events'] ) ];
		}

		return (bool) Db::i()->select( 'count(rsvp_id)', 'calendar_event_rsvp', $where )->first();
	}
}