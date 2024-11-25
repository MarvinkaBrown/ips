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

namespace IPS\blog\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog as BlogClass;
use IPS\Db;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\Member;
use IPS\Settings;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Listener
 */
class Blog extends MemberListenerType
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
		Db::i()->update( 'blog_blogs', array( 'blog_member_id' => $member->member_id ), array( 'blog_member_id=?', $member2->member_id ) );

		foreach( BlogClass::loadByOwner( $member2 ) as $blog )
		{
			/* Only do this if the blog is not a group blog */
			if ( !$blog->groupblog_ids )
			{
				$blog->member_id	= $member->member_id;
				$blog->save();
			}
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
		foreach( BlogClass::loadByOwner( $member ) as $blog )
		{
			/* We only want to do this if the blog is owned by the member - loadByOwner() also returns Blogs that are assigned to member groups */
			if ( !$blog->groupblog_ids )
			{
				/* Delete blog entries & blog */
				Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => 'IPS\blog\Blog', 'id' => $blog->id, 'deleteWhenDone' => TRUE ) );
			}
		}
	}

	/**
	 * Member is flagged as spammer
	 *
	 * @param	$member	Member	The member
	 * @return	void
	 */
	public function onSetAsSpammer( Member $member ) : void
	{
		$actions = explode( ',', Settings::i()->spm_option );

		/* Hide or delete */
		if ( in_array( 'unapprove', $actions ) or in_array( 'delete', $actions ) )
		{
			foreach( BlogClass::loadByOwner( $member ) as $blog )
			{
				if( !$blog->groupblog_ids )
				{
					$blog->disabled = TRUE;
					$blog->save();

					if( in_array( 'delete', $actions ) )
					{
						Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => 'IPS\blog\Blog', 'id' => $blog->id, 'deleteWhenDone' => TRUE ), 3 );
					}
				}
			}
		}
	}
}