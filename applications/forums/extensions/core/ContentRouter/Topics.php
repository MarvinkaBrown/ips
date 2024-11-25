<?php
/**
 * @brief		Content Router extension: Topics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		15 Jan 2014
 */

namespace IPS\forums\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
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
 * @brief	Content Router extension: Topics
 */
class Topics extends ContentRouterAbstract
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
		if ( $member === NULL or $member->canAccessModule( Module::get( 'forums', 'forums', 'front' ) ) )
		{
			$this->classes[] = 'IPS\forums\Topic';
		}
	}
}