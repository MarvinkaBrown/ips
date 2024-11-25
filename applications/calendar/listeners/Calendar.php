<?php
/**
 * @brief		Member Listener
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		22 May 2023
 */

namespace IPS\calendar\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Listener
 */
class Calendar extends MemberListenerType
{
	/**
	 * Member is merged with another member
	 *
	 * @param	Member	$member		Member being kept
	 * @param	Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( Member $member, Member $member2 ) : void
	{
		Db::i()->update( 'calendar_event_rsvp', array( 'rsvp_member_id' => $member->member_id ), array( 'rsvp_member_id=?', $member2->member_id ), array(), NULL, Db::IGNORE );
		Db::i()->update( 'calendar_import_feeds', array( 'feed_member_id' => $member->member_id ), array( 'feed_member_id=?', $member2->member_id ) );
		Db::i()->update( 'calendar_events', array( 'event_approved_by' => $member->member_id ), array( 'event_approved_by=?', $member2->member_id ) );
		Db::i()->update( 'calendar_event_reminders', array( 'reminder_member_id' => $member->member_id ), array( 'reminder_member_id=?', $member2->member_id ) );
	}

	/**
	 * Member is deleted
	 *
	 * @param	$member	Member	The member
	 * @return	void
	 */
	public function onDelete( Member $member ) : void
	{
		Db::i()->delete( 'calendar_event_rsvp', array( 'rsvp_member_id=?', $member->member_id ) );
		Db::i()->update( 'calendar_import_feeds', array( 'feed_member_id' => 0 ), array( 'feed_member_id=?', $member->member_id ) );
		Db::i()->update( 'calendar_events', array( 'event_approved_by' => 0 ), array( 'event_approved_by=?', $member->member_id ) );
		Db::i()->delete( 'calendar_event_reminders', array( 'reminder_member_id=?', $member->member_id ) );
	}
}