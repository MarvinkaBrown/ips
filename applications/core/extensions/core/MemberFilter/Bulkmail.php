<?php
/**
 * @brief		Member filter extension: Bulk mail filter
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		31 July 2018
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

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
 * @brief	Member filter: Bulk mail filter
 */
class Bulkmail extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param string $area Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'group_promotions' ) );
	}

	/**
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		$options = array( 'any' => 'any', 'on' => 'member_filter_bulk_mail_on', 'off' => 'member_filter_bulk_mail_off' );

		return array(
			new Radio( 'member_filter_bulk_mail', $criteria['bulk_mail'] ?? 'any', FALSE, array( 'options' => $options ) ),
		);
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
		if( !isset( $filters['bulk_mail'] ) OR ! $filters['bulk_mail'] )
		{
			return TRUE;
		}

		switch ( $filters['bulk_mail'] )
		{
			case 'on':
				return $member->allow_admin_mails;

			case 'off':
				return ! $member->allow_admin_mails;

		}

		/* If we are still here, then there wasn't an appropriate operator (maybe they selected 'any') so return true */
		return TRUE;
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
		return ( isset( $post['member_filter_bulk_mail'] ) and in_array( $post['member_filter_bulk_mail'], array( 'on', 'off' ) ) ) ? array( 'bulk_mail' => $post['member_filter_bulk_mail'] ) : FALSE;
	}

	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL	Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( isset( $data['bulk_mail'] ) )
		{
			switch ( $data['bulk_mail'] )
			{
				case 'on':
					return array( "allow_admin_mails=1" );

				case 'off':
					return array( "allow_admin_mails=0" );

			}
		}

		return NULL;
	}
}
