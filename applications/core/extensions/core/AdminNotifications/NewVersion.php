<?php
/**
 * @brief		ACP Notification: New Version Available
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Jun 2018
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Theme;
use function defined;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: New Version Available
 */
class NewVersion extends AdminNotification
{	
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'important';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 1;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 1;

	/**
	 * @brief	Temporarily store the upgrade data
	 */
	public ?array $_details = null;
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_NewVersion';
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		if ( CIC AND IPS::isManaged() )
		{
			return FALSE;
		}
		
		return $member->hasAcpRestriction( 'core', 'overview', 'upgrade_manage' );
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
	 * Is this a security update?
	 *
	 */
	public function __construct()
	{
		$this->_details = Application::load('core')->availableUpgrade( TRUE );
		parent::__construct();
	}
	
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return ! empty( $this->_details['security'] ) ? Member::loggedIn()->language()->addToStack( 'dashboard_version_info_security', FALSE, array( 'sprintf' => array( $this->_details['version'] ) ) ) : Member::loggedIn()->language()->addToStack( 'dashboard_version_info', FALSE, array( 'sprintf' => array( $this->_details['version'] ) ) );
	}
	
	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle(): ?string
	{
		return Member::loggedIn()->language()->addToStack( 'regular_update', FALSE, array( 'sprintf' => array( DateTime::ts( $this->_details['released'] )->relative( DateTime::RELATIVE_FORMAT_LOWER ) ) ) );
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		return Theme::i()->getTemplate( 'notifications', 'core', 'global' )->newVersion( $this->_details );
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		return static::SEVERITY_CRITICAL;
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
		return ! empty( $this->_details['security'] ) ? static::STYLE_ERROR : static::STYLE_INFORMATION;
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return Url::internal( 'app=core&module=system&controller=upgrade&_new=1', 'admin' );
	}

	/**
	 * Should this notification dismiss itself?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @return	bool
	 */
	public function selfDismiss(): bool
	{
		return empty( $this->_details['version'] );
	}
}