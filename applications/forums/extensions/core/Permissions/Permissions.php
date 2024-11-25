<?php
/**
 * @brief		Permissions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		16 Apr 2014
 */

namespace IPS\forums\extensions\core\Permissions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\PermissionsAbstract;
use IPS\forums\Forum;
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
			'IPS\forums\Forum' => function( $current, $group )
			{
				$rows = array();
				
				foreach( Forum::roots( NULL ) AS $root )
				{
					Forum::populatePermissionMatrix( $rows, $root, $group, $current );
				}
				
				return $rows;
			}
		);
	}

}