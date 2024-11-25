<?php
/**
 * @brief		Personal Conversation Message Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Jul 2013
 */

namespace IPS\core\Messenger;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Application\Module;
use IPS\Content\Comment;
use IPS\Content\EditHistory;
use IPS\Content\Item;
use IPS\Content\Reportable;
use IPS\DateTime;
use IPS\Db;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Platform\Bridge;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Personal Conversation Message
 */
class Message extends Comment
{
	use Reportable,
		EditHistory;
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_message_posts';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'msg_';
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[Content\Comment]	Title
	 */
	public static string $title = 'personal_conversation_message';
	
	/**
	 * @brief	[Content\Comment]	Icon
	 */
	public static string $icon = 'envelope';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static string $itemClass = 'IPS\core\Messenger\Conversation';
	
	/**
	 * @brief	[Content\Comment]	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'		=> 'topic_id',
		'date'		=> 'date',
		'content'	=> 'post',
		'author'	=> 'author_id',
		'ip_address'=> 'ip_address',
		'first'		=> 'is_first_post'
	);
	
	/**
	 * @brief	[Content\Comment]	Language prefix for forms
	 */
	public static string $formLangPrefix = 'messenger_';
	
	/**
	 * @brief	[Content\Comment]	The ignore type
	 */
	public static string $ignoreType = 'messages';
	
	/**
	 * Should this comment be ignored?
	 * Override so that the person who starts the conversation sees all messages - if you send a
	 * message to someone, you're always going to want to be able to see their replies.
	 *
	 * @param Member|null $member	The member to check for - NULL for currently logged in member
	 * @return	bool
	 */
	public function isIgnored( Member $member=null ): bool
	{
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}
		
		if ( $this->item()->author()->member_id == $member->member_id )
		{
			return FALSE;
		}
		
		return parent::isIgnored( $member );
	}

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Create comment
	 *
	 * @param Item $item The content item just created
	 * @param string $comment The comment
	 * @param bool $first Is the first comment?
	 * @param string|null $guestName If author is a guest, the name to use
	 * @param bool|null $incrementPostCount Increment post count? If NULL, will use static::incrementPostCount()
	 * @param Member|null $member The author of this comment. If NULL, uses currently logged in member.
	 * @param DateTime|null $time The time
	 * @param string|null $ipAddress The IP address or NULL to detect automatically
	 * @param int|null $hiddenStatus NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @param int|null $anonymous NULL for no value, 0 or 1 for a value (0=no, 1=yes)
	 * @return Message|null
	 */
	public static function create( Item $item, string $comment, bool $first=false, string|null $guestName=null, bool|null $incrementPostCount= null, Member|null $member= null, DateTime|null $time= null, string|null $ipAddress= null, int|null $hiddenStatus= null, int|null $anonymous= null ): static|null
	{
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}
		
		$comment = parent::create( $item, $comment, $first, $guestName, $incrementPostCount, $member, $time, $ipAddress, $hiddenStatus, $anonymous );
		
		/* Mark unread for this person */
		Db::i()->update( 'core_message_topic_user_map', array( 'map_has_unread' => FALSE, 'map_read_time' => time() ), array( 'map_user_id=? AND map_topic_id=?', $member->member_id, $item->id ) );
		
		return $comment;
	}
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications(): void
	{
		/* Update topic maps for other participants */
		Db::i()->update( 'core_message_topic_user_map', array( 'map_has_unread' => 1, 'map_last_topic_reply' => time() ), array( 'map_topic_id=? AND map_user_id!=?', $this->item()->id, $this->author()->member_id ) );
		
		/* Update topic map for this author */
		Db::i()->update( 'core_message_topic_user_map', array( 'map_has_unread' => 0, 'map_last_topic_reply' => time(), 'map_read_time' => time() ), array( 'map_topic_id=? AND map_user_id=?', $this->item()->id, $this->author()->member_id ) );
			
		$notification = new Notification( Application::load('core'), 'new_private_message', $this->item(), array( $this ) );
		$messengerModule = Module::get( 'core', 'messaging', 'front' );

		$memberIds = [];
		foreach ( $this->item()->maps() as $map )
		{
			if ( $map['map_user_id'] !== $this->author()->member_id and $map['map_user_active'] and !$map['map_ignore_notification'] )
			{
				$member = Member::load( $map['map_user_id'] );
				/* skip this for members which can't or don't want to use the messenger or for deleted users */
				if ( $member->members_disable_pm == 2 or !$member->canAccessModule( $messengerModule ) or !$member->member_id )
				{
					continue;
				}

				$memberIds[] = $member->member_id;
				Conversation::rebuildMessageCounts( $member );
				
				$notification->recipients->attach( $member );
				
				if ( $member->members_bitoptions['show_pm_popup'] )
				{
					$member->msg_show_notification = TRUE;
					$member->save();
				}
			}
		}

		/* Send the notification event on cloud where we don't use long polling */
		if ( Bridge::i()->featureIsEnabled( 'realtime' ) and count( $memberIds ) )
		{
			Bridge::i()->publishRealtimeEvent( 'notifications_available', location: $memberIds, locationAsMember: true );
		}

		$notification->send();
	}
	
	/**
	 * Move Comment to another item
	 *
	 * @param	Item	$item	The item to move this comment to
	 * @param bool $skip	Skip rebuilding new/old content item data (used for multimod where we can do it in one go after)
	 * @return	void
	 */
	public function move( Item $item, bool $skip=FALSE ): void
	{
		/* Make sure all active participants in the old conversation are in the new one */
		$activeParticipants = array_keys( array_filter( $this->item()->maps( TRUE ), function ( $map ) {
			return $map['map_user_active'];
		} ) );
		
		$item->authorize( $activeParticipants );
		
		parent::move( $item );
	}
}