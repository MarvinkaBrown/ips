<?php
/**
 * @brief		Front Navigation Extension: Search
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Jun 2015
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Search
 */
class Search extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f002';

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('search');
	}
		
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return    bool
	 */
	public function canAccessContent(): bool
	{
		return Member::loggedIn()->canAccessModule( Module::get( 'core', 'search' ) );
	}
	
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('search');
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return Url::internal( "app=core&module=search&controller=search", 'front', 'search' );
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return Dispatcher::i()->application->directory === 'core' and Dispatcher::i()->module->key === 'search';
	}
}