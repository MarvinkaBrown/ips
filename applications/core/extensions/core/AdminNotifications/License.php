<?php
/**
 * @brief		ACP Notification: License will/has expired
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Jun 2018
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Theme;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: License will/has expired
 */
class License extends AdminNotification
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
	public static int $itemPriority = 3;
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_License';
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return $member->hasAcpRestriction( 'core', 'settings', 'licensekey_manage' );
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
		switch ( $this->extra )
		{
			case 'missing':
			case 'url':
				return Member::loggedIn()->language()->addToStack('license_error');
			case 'expireSoon':
				$licenseKeyData = IPS::licenseKey();
				if( !empty( $licenseKeyData ) and !isset( $licenseKeyData['expires'] ) )
				{
					/* Fail-safe in case the data is missing */
					return Member::loggedIn()->language()->addToStack('license_error');
				}
				return Member::loggedIn()->language()->addToStack( 'license_renewal_soon', FALSE, array( 'pluralize' => array( intval( DateTime::create()->diff( DateTime::ts( strtotime( $licenseKeyData['expires'] ), TRUE ) )->format('%r%a') ) ) ) );
			case 'expired':
				return Member::loggedIn()->language()->addToStack('license_expired');
		}

		return '';
	}
	
	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle(): ?string
	{
		switch ( $this->extra )
		{
			case 'missing':
			case 'url':
				return Member::loggedIn()->language()->addToStack('license_error_subtitle');
			default:
				return Member::loggedIn()->language()->addToStack('license_benefits_info');
		}
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		switch ( $this->extra )
		{
			case 'missing':
				return Member::loggedIn()->language()->addToStack('license_error_none');
			default:
				return Theme::i()->getTemplate( 'notifications', 'core', 'admin' )->licenseKey( $this->id, $this->extra );
		}
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		switch ( $this->extra )
		{				
			case 'expireSoon':
				return static::SEVERITY_HIGH;
			
			default:
				return static::SEVERITY_CRITICAL;
		}
	}
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	public function dismissible(): string
	{
		return static::DISMISSIBLE_NO;
	}
	
	/**
	 * Style
	 *
	 * @return	string
	 */
	public function style(): string
	{
		switch ( $this->extra )
		{
			case 'missing':
			case 'url':
				return static::STYLE_ERROR;
			case 'expired':
				return static::STYLE_WARNING;				
			case 'expireSoon':
				return static::STYLE_EXPIRE;
		}

		return '';
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return Url::internal( 'app=core&module=settings&controller=licensekey' );
	}
}