<?php
/**
 * @brief		Content Router extension: Blog
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		04 Mar 2014
 */

namespace IPS\blog\extensions\core\ContentRouter;

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
 * @brief	Content Router extension: Entries
 */
class Blog extends ContentRouterAbstract
{	
	/**
	 * @brief	Owned Node Classes
	 */
	public array $ownedNodes = array( 'IPS\blog\Blog' );
	
	/**
	 * @brief	Can be shown in similar content
	 */
	public bool $similarContent = TRUE;
	
	/**
	 * Constructor
	 *
	 * @param Group|Member|null $member		If checking access, the member/group to check for, or NULL to not check access
	 * @return	void
	 */
	public function __construct( Group|Member $member = NULL )
	{
		if ( $member === NULL or $member->canAccessModule( Module::get( 'blog', 'blogs', 'front' ) ) )
		{
			$this->classes[] = 'IPS\blog\Entry';
		}
	}
}