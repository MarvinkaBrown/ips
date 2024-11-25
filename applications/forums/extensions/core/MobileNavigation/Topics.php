<?php
/**
 * @brief		Mobile Navigation Extension: Topics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		12 Jun 2019
 */

namespace IPS\forums\extensions\core\MobileNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\MobileNavigation\MobileNavigationAbstract;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mobile Navigation Extension: Topics
 */
class Topics extends MobileNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('mobilenavigation_Topics');
	}
		
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return	bool
	 */
	public function canAccessContent(): bool
	{
		return Member::loggedIn()->canAccessModule( Module::get( 'forums', 'forums', 'front' ) );
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('mobilenavigation_Topics');
	}
	
	/**
	 * Get Link
	 *
	 * @return	Url|string
	 */
	public function link(): Url|string
	{
		return Url::internal( "app=forums&module=forums&controller=index&method=fluid", 'front', 'forums' );
	}

	/**
	 * Return icon
	 *
	 * @return	string|null
	 */
	public function icon(): ?string
	{
		return 'COMMENTS';
	}
}