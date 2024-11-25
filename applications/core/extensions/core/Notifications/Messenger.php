<?php
/**
 * @brief		Notification Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Apr 2013
 */

namespace IPS\core\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\Messenger\Conversation;
use IPS\core\Messenger\Message;
use IPS\Db;
use IPS\Extensions\NotificationsAbstract;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Notification\Inline;
use IPS\Settings;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class Messenger extends NotificationsAbstract
{
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( ?Member $member = NULL ): array
	{
		$return = array();
		
		$module = Module::get( 'core', 'messaging', 'front' );
		if ( $module->_enabled )
		{			
			if ( !$member or ( $member->canAccessModule( $module ) and $member->members_disable_pm != 2 ) )
			{
				$return['core_Messenger'] = array(
					'type'				=> 'standard',
					'notificationTypes'	=> array( 'new_private_message', 'private_message_added' ),
					'title'				=> 'notifications__core_Messenger',
					'showTitle'			=> FALSE,
					'description'		=> 'notifications__core_Messenger_desc',
					'default'			=> array( 'push', 'email' ),
					'disabled'			=> array( 'inline' ),
					'extra'				=> array(
						'popup'				=> array(
							'title'				=> 'show_pm_popup',
							'icon'				=> 'window-maximize',
							'value'				=> $member ? $member->members_bitoptions['show_pm_popup'] : NULL,
							'adminCanSetDefault'=> TRUE,
							'default'			=> Settings::i()->notification_prefs_popup,
						)
					)
				);
			}
		}
		
		return $return;
	}
	
	/**
	 * Save "extra" value
	 *
	 * @param	Member|NULL	$member	The member or NULL if this is the admin setting defaults
	 * @param	string				$key	The key
	 * @param	mixed				$value	The value
	 * @return	void
	 */
	public static function saveExtra( ?Member $member, string $key, mixed $value ) : void
	{
		switch ( $key )
		{
			case 'popup':
				if ( $member )
				{
					$member->members_bitoptions['show_pm_popup'] = $value;
				}
				else
				{
					Settings::i()->changeValues( array( 'notification_prefs_popup' => $value ) );
				}
				break;
		}
	}
	
	/**
	 * Reset "extra" value to the default for all accounts
	 *
	 * @return	void
	 */
	public static function resetExtra() : void
	{
		Db::i()->update( 'core_members', 'members_bitoptions2 = members_bitoptions2 ' . ( Settings::i()->notification_prefs_popup ? '|' : '&~' ) . Member::$bitOptions['members_bitoptions']['members_bitoptions2']['show_pm_popup'] );
	}
	
	/**
	 * Parse notification: new private message
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_new_private_message( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = $notification->item;

		if ( !$item )
		{
			throw new OutOfRangeException;
		}

		$idColumn = $item::$databaseColumnId;
		$commentClass = $item::$commentClass;

		try
		{
			$comment = $commentClass::loadAndCheckPerms( $notification->item_sub_id );
		}
		catch( OutOfRangeException $e )
		{
			return [];
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__new_private_message', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $comment->author()->name ) )
			),
			'url'		=> $item->url(),
			'content'	=> $comment->content(),
			'author'	=> Member::load( $comment->author()->member_id ),
		);
	}
	
	/**
	 * Parse notification for mobile: new_private_message
	 *
	 * @param	Lang					$language		The language that the notification should be in
	 * @param	Message	$message			The message
	 * @return	array
	 */
	public static function parse_mobile_new_private_message( Lang $language, Message $message ) : array
	{
		return array(
			'title'		=> $language->addToStack( 'notification__new_private_message_title', FALSE, array( 
				'pluralize' => array(1),
				'htmlsprintf' => array( $message->author()->name ) 
			) ),
			'body'		=> $language->addToStack( 'notification__new_private_message', FALSE, array( 'htmlsprintf' => array( $message->author()->name ) ) ),
			'data'		=> array(
				'url'		=> (string) $message->url(),
				'author'	=> $message->author(),
				'grouped'	=> $language->addToStack( 'notification__new_private_message_grouped' ), // Pluralized on the client
				'groupedTitle' => $language->addToStack( 'notification__new_private_message_title' ), // Pluralized on the client
				'groupedUrl' => Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) // For more than one message, go to messenger
			),
			'tag' => md5( 'new-personal-messages' ), // Group all new personal messages
			'channelId'	=> 'personal-messages',
		);
	}
	
	/**
	 * Parse notification: private message added
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_private_message_added( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = $notification->item;
	
		return array(
				'title'		=> Member::loggedIn()->language()->addToStack( 'notification__private_message_added', FALSE, array(
					( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $item->author()->name ) )
				),
				'url'		=> $item->url(),
				'content'	=> $item->content(),
				'author'	=> Member::load( $item->author()->member_id ),
		);
	}
	
	/**
	 * Parse notification for mobile: private_message_added
	 *
	 * @param	Lang						$language		The language that the notification should be in
	 * @param	Conversation	$conversation	The conversation the recipient was added to
	 * @param	Member						$added			The member that added the recipient to the conversation
	 * @return	array
	 */
	public static function parse_mobile_private_message_added( Lang $language, Conversation $conversation, Member $added ) : array
	{
		return array(
			'title'		=> $language->addToStack( 'notification__private_message_added_title', FALSE, array( 'pluralize' => array(1) ) ),
			'body'		=> $language->addToStack( 'notification__private_message_added', FALSE, array( 'htmlsprintf' => array( $added->name ) ) ),
			'data'		=> array(
				'url'		=> (string) $conversation->url(),
				'author'	=> $added
			),
			'tag' => md5( 'new-personal-messages-added' ), // Group all added-to personal messages
			'channelId'	=> 'personal-messages',
		);
	}
}