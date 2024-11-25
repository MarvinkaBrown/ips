<?php

namespace IPS\nexus\extensions\core\UserMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Helpers\Menu\Link;
use IPS\Helpers\Menu\MenuItem;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output\UI\MenuExtension;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	UserMenu extension: Store
 */
class Store extends MenuExtension
{
	/**
	 * Used to add additional links to the user account menu
	 *
	 * @param string $position ( can be content, settings, logout )
	 * @return array<MenuItem>
	 */
	public function accountMenu( string $position = 'content' ): array
	{
		$return = [];
		if( $position == 'content' )
		{
			if( Settings::i()->nexus_subs_enabled )
			{
				$return[] = new Link( Url::internal( "app=nexus&module=subscriptions&controller=subscriptions", "front", "nexus_subscriptions" ), 'nexus_manage_subscriptions', icon: 'fa-solid fa-arrows-rotate', identifier: 'subscriptions' );
			}
		}
		return $return;
	}

	/**
	 * Used to add items to the mobile navigation Menu
	 *
	 * @param string $position ( can be content, settings, logout )
	 * @return array<MenuItem>
	 */
	public function mobileMenu( string $position = 'content' ) : array
	{
		$return = [];

		if( Settings::i()->nexus_subs_enabled and $position == 'content' )
		{
			$return[] = new Link( Url::internal( "app=nexus&module=subscriptions&controller=subscriptions", "front", "nexus_subscriptions" ), 'nexus_manage_subscriptions', "", icon: 'fa-solid fa-arrows-rotate', identifier: 'subscriptions' );
		}

		return $return;
	}

	/**
	 * Used to add additional content to userbar
	 *
	 * @return string
	 */
	public function userNav(): string
	{
		if( Member::loggedIn()->canAccessModule( Module::get( 'nexus', 'store' ) ) )
		{
			return Theme::i()->getTemplate( 'global', 'nexus' )->userNav();
		}

		return '';
	}

	/**
	 * Used to add content to the mobile navigation header
	 *
	 * @param string $position (header/footer)
	 * @param string $iconLocation
	 * @return string
	 */
	public function mobileNav( string $position, string $iconLocation = 'footer' ) : string
	{
		if( Member::loggedIn()->canAccessModule( Module::get( 'nexus', 'store' ) ) )
		{
			if( $position == 'footer' and $iconLocation == 'footer' )
			{
				return Theme::i()->getTemplate( 'global', 'nexus' )->mobileFooterBar();
			}
			elseif( $position == 'header' and $iconLocation == 'header' )
			{
				return Theme::i()->getTemplate( 'global', 'nexus' )->mobileHeaderBar();
			}
		}

		return "";
	}
}