<?php
/**
 * @brief		ACP Notification Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Commerce
 * @since		18 Jan 2023
 */

namespace IPS\nexus\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP  Notification Extension
 */
class Maxmind extends AdminNotification
{	
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'commerce';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 3;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 1;
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_Maxmind';
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return true;// $member->hasAcpRestriction( ... );
	}
	
	/**
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional(): bool
	{
		return FALSE;
	}
	
	/**
	 * Is this type of notification might recur (controls what options will be available for the email setting)
	 *
	 * @return	bool
	 */
	public static function mayRecur(): bool
	{
		return FALSE;
	}
			
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('acp_notification_nexus_maxmind');
	}

	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle(): ?string
	{
		return Member::loggedIn()->language()->addToStack( 'acp_notification_nexus_maxmind_sub', FALSE );
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		return Theme::i()->getTemplate( 'notifications', 'nexus', 'admin' )->maxmind();
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		return static::SEVERITY_HIGH;
	}
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	public function dismissible(): string
	{
		return static::DISMISSIBLE_TEMPORARY;
	}
	
	/**
	 * Style
	 *
	 * @return	string
	 */
	public function style(): string
	{
		return static::STYLE_ERROR;
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url|null
	 */
	public function link(): Url|null
	{
		return parent::link(); // \IPS\Http\Url::internal( '...' );
	}
}