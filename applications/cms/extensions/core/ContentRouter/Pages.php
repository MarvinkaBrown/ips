<?php
/**
 * @brief		Content Router extension: Pages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		15 Dec 2017
 */

namespace IPS\cms\extensions\core\ContentRouter;

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
 * @brief	Content Router extension: Pages
 */
class Pages extends ContentRouterAbstract
{
	/**
	 * Constructor
	 *
	 * @param	Member|Group|NULL	$memberOrGroup		If checking access, the member/group to check for, or NULL to not check access
	 * @return	void
	 */
	public function __construct( Group|Member $memberOrGroup = NULL )
	{
		if ( $memberOrGroup === NULL or $memberOrGroup->canAccessModule( Module::get( 'cms', 'pages', 'front' ) ) )
		{
			$this->classes[] = 'IPS\cms\Pages\PageItem';
		}
	}
}