<?php

/**
 * @brief        NotificationsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/20/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class NotificationsAbstract
{
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	abstract public static function configurationOptions( ?Member $member = NULL ): array;

	/**
	 * Save "extra" value
	 *
	 * @param	Member|NULL	$member	The member or NULL if this is the admin setting defaults
	 * @param	string		$key	The key
	 * @param	bool		$value	The value
	 * @return	void
	 */
	public static function saveExtra( ?Member $member, string $key, bool $value ) : void
	{
		// You can perform extra processing here to save values in a specific manner if needed
	}

	/**
	 * Disable all "extra" values for a particular type
	 *
	 * @param	Member|NULL	$member	The member or NULL if this is the admin setting defaults
	 * @param	string		$method	The method type
	 * @return	void
	 */
	public static function disableExtra( ?Member $member, string $method ) : void
	{
		// You can disable extra values for a given type, such as if a setting means the notification preference is irrelevant
	}

	/**
	 * Reset "extra" value to the default for all accounts
	 *
	 * @return	void
	 */
	public static function resetExtra() : void
	{
		// Any extra processing you may need to perform when resetting all members to default notification options.
		// Typically you won't have to do anything here unless you have custom settings
	}
}