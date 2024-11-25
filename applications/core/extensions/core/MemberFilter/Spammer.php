<?php
/**
 * @brief		Member Filter Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		22 Sep 2021
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
 * Member Filter Extension
 */
class Spammer extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
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
		$options = array( 'any' => 'any', 'spammer' => 'mf_spammer_spammer', 'notspammer' => 'mf_spammer_not_spammer' );

		return array(
			new Radio( 'mf_spammer', $criteria['spammer'] ?? 'any', FALSE, array( 'options' => $options ) ),
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
		return ( isset( $post['mf_spammer'] ) and in_array( $post['mf_spammer'], array( 'spammer', 'notspammer' ) ) ) ? array( 'spammer' => $post['mf_spammer'] ) : FALSE;
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( isset( $data['spammer'] ) )
		{
			switch ( $data['spammer'] )
			{
				case 'spammer':
					return array( Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ));

				case 'notspammer':
					return array( Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer', FALSE ) );

			}
		}

		return NULL;
	}
}