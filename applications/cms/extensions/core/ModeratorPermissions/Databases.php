<?php
/**
 * @brief		Moderator Permissions: Databases
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		30 April 2014
 */

namespace IPS\cms\extensions\core\ModeratorPermissions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Databases as DatabasesClass;
use IPS\Extensions\ModeratorPermissionsAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions: Databases
 */
class Databases extends ModeratorPermissionsAbstract
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
		/* Preload words */
		DatabasesClass::databases();
		
		$return = array(
			'can_content_revisions_content'		=> 'YesNo',
			'can_content_edit_record_slugs'		=> "YesNo",
			'can_content_edit_meta_tags'		=> "YesNo",
			'can_content_view_others_records'	=> "YesNo"
		);
		
		if ( Application::appIsEnabled('forums') )
		{
			$return['can_copy_topic_database'] = 'YesNo';
		}
		
		return $return;
	}
}