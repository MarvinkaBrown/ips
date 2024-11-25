<?php
/**
 * @brief		ACP Notification Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		11 Dec 2023
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
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
class Custom extends AdminNotification
{	
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'system';
	
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
		return 'acp_notification_Custom';
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return true;
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
		return $this->additionalData['title'];
	}

	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle() : ?string
	{
		return isset( $this->additionalData['subtitle'] )? $this->additionalData['subtitle'] : NULL;
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		return $this->additionalData['body'];
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		return isset( $this->additionalData['severity'] ) ? $this->additionalData['severity'] : static::SEVERITY_NORMAL;
	}
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	public function dismissible(): string
	{
		return static::DISMISSIBLE_PERMANENT;
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return isset( $this->additionalData['link'] ) ? UrL::internal( $this->additionalData['link'] ) : parent::link();
	}

	/**
	 * Send Notification
	 *
	 * @param	string				$app				Application key
	 * @param	string				$extension			Extension key
	 * @param	string|null			$extra				Any additional information which persists if the notification is resent
	 * @param	bool|null			$resend				If an existing notification exists, it will be bumped / resent
	 * @param	mixed				$extraForEmail		Any additional information specific to this instance which is used for the email but not saved
	 * @param	bool|Member	$bypassEmail		If TRUE, no email will be sent, regardless of admin preferences - or if a member object, that admin will be skipped. Should only be used if the action is initiated by an admin making an email unnecessary
	 * @param	array				$additionalData		Any additional data to save to the notification
	 * @return	void
	 */
	public static function send( string $app, string $extension, ?string $extra = NULL, ?bool $resend = TRUE, mixed $extraForEmail = NULL, bool|Member $bypassEmail = FALSE, array $additionalData = [] ) : void
	{
		/* Set the mail subject to match the title */
		Member::loggedIn()->language()->words['mailsub__core_acp_notification_Custom'] = $additionalData['title'] ?? '';

		parent::send( $app, $extension, $extra, $resend, $extraForEmail, $bypassEmail );
	}
}