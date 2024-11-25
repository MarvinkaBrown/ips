<?php
/**
 * @brief		Content Router extension: Downloads
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		02 Dec 2013
 */

namespace IPS\downloads\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\downloads\File;
use IPS\Extensions\ContentRouterAbstract;
use IPS\Member;
use IPS\Member\Group;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Downloads
 */
class Files extends ContentRouterAbstract
{
	/**
	 * @brief	Can be shown in similar content
	 */
	public bool $similarContent = TRUE;
	
	/**
	 * Constructor
	 *
	 * @param	Member|Group|NULL	$member		If checking access, the member/group to check for, or NULL to not check access
	 * @return	void
	 */
	public function __construct( Member|Group $member = NULL )
	{
		if ( $member === NULL or $member->canAccessModule( Module::get( 'downloads', 'downloads', 'front' ) ) )
		{
			$this->classes[] = 'IPS\downloads\File';
		}

		if( $member !== NULL AND ( $member instanceof Member ) AND File::modPermission( 'unhide', $member ) )
		{
			$this->classes[] = 'IPS\downloads\File\PendingVersion';
		}
	}
}