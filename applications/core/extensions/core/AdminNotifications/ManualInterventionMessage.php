<?php
/**
 * @brief		ACP Notification Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		25 Aug 2022
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP  Notification Extension
 */
class ManualInterventionMessage extends AdminNotification
{
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'system';

	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 2;

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
		return 'acp_notification_ManualInterventionMessage';
	}

	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'app_manage' ) OR Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'plugins_view' );
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
		return Member::loggedIn()->language()->addToStack( 'method_check_fail' );
	}

	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		return Member::loggedIn()->language()->addToStack( 'advise_removal_of_php8_incompatible_code' );
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
		return static::DISMISSIBLE_UNTIL_RECUR;
	}

	/**
	 * Style
	 *
	 * @return	string
	 */
	public function style(): string
	{
		return $this->severity() === static::SEVERITY_HIGH ? static::STYLE_ERROR : static::STYLE_WARNING;
	}

	/**
	 * Quick link from popup menu
	 *
	 * @return	Url|null
	 */
	public function link(): Url|null
	{
		return Url::internal( 'app=core&module=support&controller=support&do=methodCheck' );
	}

	/**
	 * Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
	}

	/**
	 * Should this notification dismiss itself?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @return	bool
	 */
	public function selfDismiss(): bool
	{
		if( Db::i()->select( 'count(*)', 'core_applications', [ 'app_requires_manual_intervention=?', 1 ] )->first() )
		{
			return FALSE;
		}

		return TRUE;
	}
}