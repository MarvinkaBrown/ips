<?php
/**
 * @brief		ACP Notification: New Registration Requires Admin Validation
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		2 Jul 2018
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: New Registration Requires Admin Validation
 */
class NewRegValidate extends AdminNotification
{
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'members';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 3;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 2;
	
	/**
	 * Get queue HTML
	 *
	 * @return	string
	 */
	public static function queueHtml() : string
	{
		$users = array();
		
		foreach (
			Db::i()->select(
				"*",
				'core_validating',
				array( 'user_verified=?', TRUE ),
				'entry_date asc',
				array( 0, 12 )
			)->join(
					'core_members',
					'core_validating.member_id=core_members.member_id'
			) as $user
		)
		{
			$users[ $user['member_id'] ] = Member::constructFromData( $user );
		}
		
		if ( count( $users ) )
		{
			return Theme::i()->getTemplate( 'notifications', 'core', 'admin' )->adminValidations( $users );
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return $member->hasAcpRestriction( 'core', 'members' );
	}
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_NewRegValidate';
	}
	
	/**
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional(): bool
	{
		return TRUE;
	}
	
	/**
	 * Is this type of notification might recur (controls what options will be available for the email setting)
	 *
	 * @return	bool
	 */
	public static function mayRecur(): bool
	{
		return TRUE;
	}
			
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{
		$others = Db::i()->select( 'COUNT(*)', 'core_validating', array( 'user_verified=?', TRUE ) )->first();
		$names = array();
		foreach (
			Db::i()->select(
				"*",
				'core_validating',
				array( 'user_verified=?', TRUE ),
				'entry_date asc',
				array( 0, 2 )
			)->join(
					'core_members',
					'core_validating.member_id=core_members.member_id'
			) as $user
		)
		{
			$names[ $user['member_id'] ] = htmlentities( $user['name'], ENT_DISALLOWED, 'UTF-8', FALSE );
			$others--;
		}
		if ( $others )
		{
			$names[] = Member::loggedIn()->language()->addToStack( 'and_x_others', FALSE, array( 'pluralize' => array( $others ) ) );
		}
		
		return Member::loggedIn()->language()->addToStack( 'new_users_need_admin_validation', FALSE, array( 'pluralize' => array( count( $names ) ), 'sprintf' => array( Member::loggedIn()->language()->formatList( $names ) ) ) );
	}
	
	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle(): ?string
	{
		try
		{
			return DateTime::ts( Db::i()->select( 'entry_date', 'core_validating', array( 'user_verified=?', TRUE ), 'entry_date asc', 1 )->first() )->relative();
		}
		catch ( UnderflowException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('admin_dashboard.js', 'core') );
		
		return static::queueHtml();
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		return static::SEVERITY_OPTIONAL;
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
		return static::STYLE_WARNING;
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url
	 */
	public function link(): Url
	{
		return Url::internal( 'app=core&module=members&controller=members&filter=members_filter_validating' );
	}
}