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

namespace IPS\forums\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\Member;
use IPS\Settings;
use function defined;
use const IPS\CIC2;
use function IPS\Cicloud\getForumArchiveDb;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Listener
 */
class Forums extends MemberListenerType
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
		Db::i()->update( 'forums_answer_ratings', array( 'member' => $member->member_id ), array( '`member`=?', $member2->member_id ), array(), NULL, Db::IGNORE );
		Db::i()->update( 'forums_question_ratings', array( 'member' => $member->member_id ), array( '`member`=?', $member2->member_id ), array(), NULL, Db::IGNORE );
		Db::i()->update( 'forums_view_method', array( 'member_id' => $member->member_id ), array( 'member_id=?', $member2->member_id ), array(), NULL, Db::IGNORE );

		if ( Settings::i()->archive_on )
		{
			/* Connect to the remote DB if needed */
			if ( CIC2 )
			{
				$archiveStorage = getForumArchiveDb();
			}
			else
			{
				$archiveStorage = ( !Settings::i()->archive_remote_sql_host ) ? Db::i() : Db::i( 'archive', array(
					'sql_host'		=> Settings::i()->archive_remote_sql_host,
					'sql_user'		=> Settings::i()->archive_remote_sql_user,
					'sql_pass'		=> Settings::i()->archive_remote_sql_pass,
					'sql_database'	=> Settings::i()->archive_remote_sql_database,
					'sql_port'		=> Settings::i()->archive_sql_port,
					'sql_socket'	=> Settings::i()->archive_sql_socket,
					'sql_tbl_prefix'=> Settings::i()->archive_sql_tbl_prefix,
					'sql_utf8mb4'	=> isset( Settings::i()->sql_utf8mb4 ) ? Settings::i()->sql_utf8mb4 : FALSE
				) );
			}
			$archiveStorage->update( 'forums_archive_posts', array( 'archive_author_id' => $member->member_id ), array( 'archive_author_id=?', $member2->member_id ) );
		}
	}

	/**
	 * Member is deleted
	 *
	 * @param	$member	Member	The member
	 * @return	void
	 */
	public function onDelete( Member $member ) : void
	{
		Db::i()->delete( 'forums_answer_ratings', array( '`member`=?', $member->member_id ) );
		Db::i()->delete( 'forums_question_ratings', array( '`member`=?', $member->member_id ) );
		Db::i()->delete( 'forums_view_method', array( 'member_id=?', $member->member_id ) );
	}
}