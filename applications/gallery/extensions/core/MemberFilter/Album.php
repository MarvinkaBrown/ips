<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		23 Mar 2017
 */

namespace IPS\gallery\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Db\Select;
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
 * Member Filter Extension
 */
class Album extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param string $area Area to check (bulkmail, group_promotions, automatic_moderation)
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
		$options = array( 'any' => 'any', 'yes' => 'yes', 'no' => 'no' );

		return array(
			new Radio( 'mf_has_album', $criteria['has_album'] ?? 'any', FALSE, array( 'options' => $options ) ),
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
		if( isset( $post['mf_has_album'] ) and in_array( $post['mf_has_album'], array( 'yes', 'no' ) ) )
		{
			return array( 'has_album' => $post['mf_has_album'] );
		}

		return FALSE;
	}

	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if( isset( $data['has_album'] ) )
		{
			return ( $data['has_album'] == 'yes' ) ? array( "gallery_albums.album_id IS NOT NULL" ) : array( "gallery_albums.album_id IS NULL" );
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
	public function queryCallback( array $data, Select $query ) : void
	{
		if( isset( $data['has_album'] ) )
		{
			$query->join( 'gallery_albums', "core_members.member_id=gallery_albums.album_owner_id" );
		}
	}

	/**
	 * Determine if a member matches specified filters
	 *
	 * @param Member $member
	 * @param array $filters
	 * @param object|null $object
	 * @note	This is only necessary if availableIn() includes group_promotions
	 * @return	bool
	 */
	public function matches( Member $member, array $filters, ?object $object = NULL ): bool
	{
		/* If we aren't filtering by this, then any member matches */
		if( !isset( $filters['has_album'] ) OR !$filters['has_album'] )
		{
			return TRUE;
		}

		$result = (bool) Db::i()->select( 'COUNT(*)', 'gallery_albums', array( 'album_owner_id=?', $member->member_id ) )->first();

		return ( $filters['has_album'] == 'no' ) ? !$result : $result;
	}
}