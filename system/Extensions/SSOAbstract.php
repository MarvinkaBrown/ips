<?php

/**
 * @brief        SsoAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        2/2/2024
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Session\Front;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class SSOAbstract
{
	/**
	 * Determine if the extension is currently enabled
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return true;
	}

	/**
	 * Returns an array of setting keys that will be overridden by this extension
	 * Example: post_before_registering might always return a value of 0
	 * Would return an array like [ 'post_before_registering' => 0 ]
	 *
	 * @return array
	 */
	public function overrideSettings(): array
	{
		return [];
	}

	/**
	 * Return URL guest should be redirected to for login
	 *
	 * @return Url|null
	 */
	public function loginUrl(): Url|null
	{
		return NULL;
	}

	/**
	 * URL user should be redirected on logout.
	 *
	 * @param Url $redirectUrl
	 * @return Url|null
	 */
	public function logoutUrl( Url $redirectUrl ): Url|null
	{
		return NULL;
	}

	/**
	 * Return URL user should visit to change display name
	 *
	 * @return Url|null
	 */
	public function displayNameChange(): Url|null
	{
		return NULL;
	}

	/**
	 * Custom logic executed on session init
	 *
	 * @param Front $session
	 * @return void
	 */
	abstract public function onSessionInit( Front $session ): void;

	/**
	 * Custom logic executed on session read
	 *
	 * @param Front $session
	 * @param string $result 	Initial result from the Session::read method
	 * @return string
	 */
	abstract public function onSessionRead( Front $session, string $result ): string;

	/**
	 * Does SSO support login as?
	 *
	 * @return bool
	 */
	public function supportsLoginAs(): bool
	{
		return FALSE;
	}
}