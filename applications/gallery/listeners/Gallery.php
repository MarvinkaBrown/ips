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

namespace IPS\gallery\listeners;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Events\ListenerType\MemberListenerType;
use IPS\gallery\Album;
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
class Gallery extends MemberListenerType
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
		Db::i()->update( 'gallery_albums', array( 'album_owner_id' => $member->member_id ), array( 'album_owner_id=?', $member2->member_id ) );
		Db::i()->update( 'gallery_bandwidth', array( 'member_id' => $member->member_id ), array( 'member_id=?', $member2->member_id ) );
		Db::i()->update( 'gallery_images_uploads', array( 'upload_member_id' => $member->member_id ), array( 'upload_member_id=?', $member2->member_id ) );

		foreach( Album::loadByOwner( $member2 ) as $album )
		{
			$album->owner_id	= $member->member_id;
			$album->save();
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
		Db::i()->delete( 'gallery_bandwidth', array( 'member_id=?', $member->member_id ) );
		Db::i()->delete( 'gallery_images_uploads', array( 'upload_member_id=?', $member->member_id ) );

		foreach( Album::loadByOwner( $member ) as $album )
		{
			//$album->delete();
			$album->owner_id = 0;
			$album->save();
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
			foreach( Album::loadByOwner( $member ) as $album )
			{
				$album->hidden = -1;
				$album->save();

				if( in_array( 'delete', $actions ) )
				{
					Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => 'IPS\gallery\Album', 'id' => $album->id, 'deleteWhenDone' => TRUE ), 3 );
				}
			}
		}
	}
}