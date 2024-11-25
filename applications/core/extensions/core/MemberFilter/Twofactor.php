<?php
/**
 * @brief		Member filter extension: Two Factor Authentication
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Mar 2017
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Radio;
use LogicException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member filter: Two Factor Authentication
 */
class Twofactor extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail', 'passwordreset' ) );
	}

	/**
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		$options = array( 'any' => 'any', 'active' => 'mf_two_factor_active', 'inactive' => 'mf_two_factor_inactive' );

		return array(
			new Radio( 'mf_two_factor', $criteria['two_factor'] ?? 'any', FALSE, array( 'options' => $options ) ),
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
		return ( isset( $post['mf_two_factor'] ) and in_array( $post['mf_two_factor'], array( 'active', 'inactive' ) ) ) ? array( 'two_factor' => $post['mf_two_factor'] ) : FALSE;
	}

	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( isset( $data['two_factor'] ) )
		{
			switch ( $data['two_factor'] )
			{
				case 'active':
					return array( "mfa_details IS NOT NULL AND mfa_details !=  '[]' " );

				case 'inactive':
					return array( "( mfa_details IS NULL OR mfa_details = '[]')" );

			}
		}

		return NULL;
	}
}