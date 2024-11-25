<?php
/**
 * @brief		Moderator Permissions: General
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Jul 2013
 */

namespace IPS\core\extensions\core\ModeratorPermissions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\ModeratorPermissionsAbstract;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions: General
 */
class General extends ModeratorPermissionsAbstract
{
	/**
	 * Get Permissions
	 *
	 * @param array $toggles
	 * @code
	 	return array(
	 		'key'	=> 'YesNo',	// Can just return a string with type
	 		'key'	=> array(	// Or an array for more options
	 			'YesNo'				// Type
	 			array( ... )		// Options (as defined by type's class)
	 			'prefix',			// Prefix
	 			'suffix'			// Suffix
	 		),
	 		...
	 	);
	 * @endcode
	 * @return	array
	 */
	public function getPermissions( array $toggles ): array
	{
		$return = array();

		if ( Settings::i()->ignore_system_on )
		{
			$return['can_moderator_be_ignored'] = 'YesNo';
		}
		
		$return['can_manage_sidebar'] = 'YesNo';
		$return['can_use_ip_tools']	= 'YesNo';
		
		return $return;
	}
}