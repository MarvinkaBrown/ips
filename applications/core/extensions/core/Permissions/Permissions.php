<?php
/**
 * @brief		Permissions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Apr 2013
 */

namespace IPS\core\extensions\core\Permissions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\PermissionsAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Permissions
 */
class Permissions extends PermissionsAbstract
{
	/**
	 * Get node classes
	 *
	 * @return	array
	 */
	public function getNodeClasses(): array
	{
		return array(
			'IPS\Application\Module'	=> NULL
		);
	}
}