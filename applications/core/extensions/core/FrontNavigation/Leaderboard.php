<?php
/**
 * @brief		Front Navigation Extension: Leaderboard
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8th Nov 2016
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Leaderboard
 */
class Leaderboard extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f091';

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('leaderboard_title');
	}
		
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return    bool
	 */
	public function canAccessContent(): bool
	{
		return Settings::i()->reputation_leaderboard_on and Member::loggedIn()->canAccessModule( Module::get( 'core', 'discover' ) ) and Settings::i()->reputation_enabled;
	}
	
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('leaderboard_title');
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		switch ( Settings::i()->reputation_leaderboard_default_tab )
		{
			default:
			case 'leaderboard':
				return Url::internal( "app=core&module=discover&controller=popular&tab=leaderboard", 'front', 'leaderboard_leaderboard' );

			case 'history':
				return Url::internal( "app=core&module=discover&controller=popular&tab=history", 'front', 'leaderboard_history' );

			case 'members':
				return Url::internal( "app=core&module=discover&controller=popular&tab=members", 'front', 'leaderboard_members' );

		}
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return Dispatcher::i()->application->directory === 'core' and Dispatcher::i()->module->key === 'discover' and Dispatcher::i()->controller == 'popular';
	}
}