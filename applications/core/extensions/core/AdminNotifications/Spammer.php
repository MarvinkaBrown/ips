<?php
/**
 * @brief		ACP Notification: Member Flagged as Spammer
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Jul 2018
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Member;
use IPS\Theme;
use UnderflowException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: Member Flagged as Spammer
 */
class Spammer extends AdminNotification
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
	public static int $itemPriority = 5;
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_Spammer';
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
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional(): bool
	{
		return TRUE;
	}
	
	/**
	 * The default value for if this notification shows in the notification center
	 *
	 * @return	bool
	 */
	public static function defaultValue() : bool
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
	 * WHERE clause to use against core_acp_notifications_preferences for fetching members to email
	 *
	 * @param mixed $extraForEmail		Any additional information specific to this instance which is used for the email but not saved
	 * @return    array
	 */
	public function emailWhereClause( mixed $extraForEmail ): array
	{
		/* Most notifications only send one email until the admin has "dealt" with it, but since this
			type of notification cannot be "dealt" with, we need to send an email every time rather
			than just the first time this notification occurs. */
		return array( "email='once'" );
	}
	
	/**
	 * Get the date/time that we need to use for the cutoff
	 *
	 * @return	DateTime|NULL
	 */
	public function cutoff() : ?DateTime
	{
		try
		{
			return DateTime::ts( Db::i()->select( 'time', 'core_acp_notifcations_dismissals', array( 'notification=? AND `member`=?', $this->id, Member::loggedIn()->member_id ) )->first() );
		}
		catch ( UnderflowException $e )
		{
			return NULL;
		}
	}
			
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{
		$where = array( Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) );
		if ( $cutoff = $this->cutoff() )
		{
			$where[] = array( 'joined>?', $cutoff->getTimestamp() );
		}
		
		
		$count = Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
		$others = $count;
		$names = array();
		foreach (
			Db::i()->select(
				"*",
				'core_members',
				$where,
				'joined desc',
				array( 0, 6 )
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
		
		return Member::loggedIn()->language()->addToStack( 'user_flagged_as_spammer', FALSE, array( 'pluralize' => array( $count ), 'sprintf' => array( Member::loggedIn()->language()->formatList( $names ) ) ) );
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		$limit = 6;
		$users = array();
		
		$where = array( Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) );
		if ( $cutoff = $this->cutoff() )
		{
			$where[] = array( 'joined>?', $cutoff->getTimestamp() );
		}
		$more = Db::i()->select( 'COUNT(*)', 'core_members', $where )->first() - $limit + 1;
		
		foreach (
			Db::i()->select(
				'*',
				'core_members',
				$where,
				'joined desc',
				array( 0, ( $more === 1 ) ? $limit : ( $limit - 1 ) )
			) as $user
		)
		{
			$users[ $user['member_id'] ] = array( 'member' => Member::constructFromData( $user ), 'blurb' => '' );
			
			foreach ( Db::i()->select( '*', 'core_member_history', array( "log_member=? AND log_type='account'", $user['member_id'] ), 'log_date DESC', 50 ) as $row )
			{
				if ( $jsonValue = json_decode( $row['log_data'], TRUE ) and isset( $jsonValue['type'] ) and $jsonValue['type'] == 'spammer' and $jsonValue['set'] ?? $jsonValue['legacy']['set'] )
				{
					if ( isset( $jsonValue['actions'] ) )
					{
						$flagActions = array();
						if ( in_array( 'delete', $jsonValue['actions'] ) )
						{
							$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_delete');
						}
						elseif ( in_array( 'unapprove', $jsonValue['actions'] ) )
						{
							$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_unapprove');
						}
						if ( in_array( 'ban', $jsonValue['actions'] ) )
						{
							$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_ban');
						}
						elseif ( in_array( 'disable', $jsonValue['actions'] ) )
						{
							$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_disable');
						}
						
						if ( count( $flagActions ) )
						{
							$users[ $user['member_id'] ]['blurb'] = Member::loggedIn()->language()->addToStack( 'user_flagged_as_spammer_subtitle_with_actions', FALSE, array( 'sprintf' => array(
								DateTime::ts( $row['log_date'] )->relative(),
								Member::load( $row['log_by'] )->name,
								Member::loggedIn()->language()->formatList( $flagActions )
							) ) );
						}
						else
						{
							$users[ $user['member_id'] ]['blurb'] = Member::loggedIn()->language()->addToStack( 'user_flagged_as_spammer_subtitle', FALSE, array( 'sprintf' => array( DateTime::ts( $row['log_date'] )->relative(), Member::load( $row['log_by'] )->name ) ) );
						}
					}
					else
					{					
						$users[ $user['member_id'] ]['blurb'] = Member::loggedIn()->language()->addToStack( 'user_flagged_as_spammer_subtitle', FALSE, array( 'sprintf' => array( DateTime::ts( $row['log_date'] )->relative(), Member::load( $row['log_by'] )->name ) ) );
					}
				}
			}
		}
				
		if ( count( $users ) )
		{
			return Theme::i()->getTemplate( 'notifications', 'core', 'admin' )->spammer( $users, $this, $more );
		}
		else
		{
			return '';
		}
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
		return static::DISMISSIBLE_UNTIL_RECUR;
	}
}