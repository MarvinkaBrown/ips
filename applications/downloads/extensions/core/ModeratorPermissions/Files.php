<?php
/**
 * @brief		Moderator Permissions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		09 Apr 2018
 */

namespace IPS\downloads\extensions\core\ModeratorPermissions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\ModeratorPermissionsAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions
 */
class Files extends ModeratorPermissionsAbstract
{
	/**
	 * Get Permissions
	 *
	 * @param array $toggles
	 * @code
	 	return array(
	 		'key'	=> 'YesNo',	// Can just return a string with type
	 		'key'	=> array(	// Or an array for more options
	 			'YesNo',			// Type
	 			array( ... ),		// Options (as defined by type's class)
	 			'prefix',			// Prefix
	 			'suffix',			// Suffix
	 		),
	 		...
	 	);
	 * @endcode
	 * @return	array
	 */
	public function getPermissions( array $toggles ): array
	{
		return array(
			'can_make_purchasable'		=> 'YesNo',
			'can_make_unpurchasable'	=> 'YesNo'
		);
	}
}