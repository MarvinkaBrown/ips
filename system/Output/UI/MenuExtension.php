<?php

namespace IPS\Output\UI;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Menu\MenuItem;
use IPS\Member as MemberClass;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


abstract class MenuExtension
{
	/**
	 * Used to add additional links to the user account menu
	 * 
	 * @param string $position ( can be content, settings, logout )
	 * @return array<MenuItem>
	 */
	public function accountMenu( string $position = 'content' ): array
	{
		return [];
	}

	/**
	 * Used to add items to the mobile navigation Menu
	 *
	 * @param string $position (can be content, settings, logout)
	 * @return array<MenuItem>
	 */
	public function mobileMenu( string $position = 'content' ) : array
	{
		return [];
	}

	/**
	 * Used to add additional content to userbar
	 *
	 * @return string
	 */
	public function userNav(): string
	{
		return '';
	}

	/**
	 * Used to add content to the mobile navigation header
	 *
	 * @param string $position 			Where the code will be inserted (header/footer)
	 * @param string $iconLocation 		Where the main icons are currently located (header/footer)
	 * @return string
	 */
	public function mobileNav( string $position, string $iconLocation = 'footer' ) : string
	{
		return '';
	}

	/**
	 * Used to add items to the user profile menu
	 *
	 * @param MemberClass $member	Member whose profile is being viewed
	 * @return array<MenuItem>
	 */
	public function editProfileMenu( MemberClass $member ) : array
	{
		return [];
	}
}