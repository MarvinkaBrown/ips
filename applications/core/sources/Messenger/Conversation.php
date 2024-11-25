<?php
/**
 * @brief		Personal Conversation Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Jul 2013
 */

namespace IPS\core\Messenger;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\Application\Module;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\core\Alerts\Alert;
use IPS\core\Reports\Report;
use IPS\core\Warnings\Warning;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_int;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Personal Conversation Model
 */
class Conversation extends Item
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_message_topics';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'mt_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[Content\Item]	Include the ability to search this content item in global site searches
	 */
	public static bool $includeInSiteSearch = FALSE;

	/**
	 * @brief	[Content\Item]	Include these items in trending content
	 */
	public static bool $includeInTrending = FALSE;

	/**
	 * @brief	[Content\Comment]	Icon
	 */
	public static string $icon = 'envelope';

	/**
	 * Should IndexNow be skipped for this item? Can be used to prevent that Private Messages,
	 * Reports and other content which is never going to be visible to guests is triggering the requests.
	 * @var bool
	 */
	public static bool $skipIndexNow = TRUE;

	/**
	 * @brief	Check posts per day limits? Useful for things that use the content system, but aren't necessarily content themselves.
	 */
	public static bool $checkPostsPerDay = FALSE;
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();
		Db::i()->delete( 'core_message_topic_user_map', array( 'map_topic_id=?', $this->id ) );
	}
	
	/* !\IPS\Content\Item */

	/**
	 * @brief	Title
	 */
	public static string $title = 'personal_conversation';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'title'				=> 'title',
		'date'				=> array( 'date', 'start_time', 'last_post_time' ),
		'author'			=> 'starter_id',
		'num_comments'		=> 'replies',
		'last_comment'		=> 'last_post_time',
		'first_comment_id'	=> 'first_msg_id',
	);
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'messaging';
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static string $formLangPrefix = 'messenger_';
	
	/**
	 * @brief	Comment Class
	 */
	public static ?string $commentClass = 'IPS\core\Messenger\Message';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static bool $firstCommentRequired = TRUE;
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( ?Model $container = NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Can a given member create this type of content?
	 *
	 * @param Member $member The member
	 * @param Model|null $container Container (e.g. forum) ID, if appropriate
	 * @param bool $showError If TRUE, rather than returning a boolean value, will display an error
	 * @return    bool
	 */
	public static function canCreate( Member $member, ?Model $container=NULL, bool $showError=FALSE ) : bool
	{
		/* Can we access the module? */
		if ( !parent::canCreate( $member, $container, $showError ) )
		{
			return FALSE;
		}
		
		/* We have to be logged in */
		if ( !$member->member_id )
		{
			if ( $showError )
			{
				Output::i()->error( 'no_module_permission_guest', '1C149/1', 403, '' );
			}
			
			return FALSE;
		}

		/* If this conversation is associated with an alert, skip the
		rest of the permission checks, the user should be able to reply */
		if ( isset( Request::i()->alert ) )
		{
			try
			{
				$alert = Alert::load( Request::i()->alert );

				if( $alert->forMember( Member::loggedIn() ) AND $alert->reply == 2 )
				{
					return TRUE;
				}
			}
			catch ( OutOfRangeException $e ) {}
		}
		
		/* Have we exceeded our limit for the day/minute? */
		if ( $member->group['g_pm_perday'] !== -1 )
		{
			$messagesSentToday = Db::i()->select( 'COUNT(*) AS count, MAX(mt_date) AS max', 'core_message_topics', array( 'mt_starter_id=? AND mt_date>?', $member->member_id, DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimeStamp() ) )->first();
			if ( $messagesSentToday['count'] >= $member->group['g_pm_perday'] )
			{
				$next = DateTime::ts( $messagesSentToday['max'] )->add( new DateInterval( 'P1D' ) );
				
				if ( $showError )
				{
					Output::i()->error( $member->language()->addToStack( 'err_too_many_pms_day', FALSE, array( 'pluralize' => array( $member->group['g_pm_perday'] ) ) ), '1C149/2', 429, '', array( 'Retry-After' => $next->format('r') ) );
				}
				
				return FALSE;
			}
		}
		if ( $member->group['g_pm_flood_mins'] !== -1 )
		{
			$messagesSentThisMinute = Db::i()->select( 'COUNT(*)', 'core_message_topics', array( 'mt_starter_id=? AND mt_date>?', $member->member_id, DateTime::create()->sub( new DateInterval( 'PT1M' ) )->getTimeStamp() ) )->first();
			if ( $messagesSentThisMinute >= $member->group['g_pm_flood_mins'] )
			{
				if ( $showError )
				{
					Output::i()->error( $member->language()->addToStack( 'err_too_many_pms_minute', FALSE, array( 'pluralize' => array( $member->group['g_pm_flood_mins'] ) ) ), '1C149/3', 429, '', array( 'Retry-After' => 3600 ) );
				}
				
				return FALSE;
			}
		}
		
		/* Is our inbox full? */
		if ( $member->group['g_max_messages'] !== -1 )
		{
			$messagesInInbox = Db::i()->select( 'COUNT(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', $member->member_id ) )->first();
			if ( $messagesInInbox > $member->group['g_max_messages'] )
			{
				if ( $showError )
				{
					Output::i()->error( 'err_inbox_full', '1C149/4', 403, '' );
				}
				
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Can Merge?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canMerge( Member|null $member=null ): bool
	{
		return FALSE;
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param Item|NULL $item The current item if editing or NULL if creating
	 * @param Model|null $container Container (e.g. forum) ID, if appropriate
	 * @return    array
	 */
	public static function formElements( Item|null $item=NULL, Model|null $container=NULL ): array
	{
		$return = array();
		foreach ( parent::formElements( $item, $container ) as $k => $v )
		{
			if ( $k == 'title' )
			{
 				if( !$item )
 				{
 					$member	= NULL;

 					if( Request::i()->to )
 					{
 						$member = Member::load( Request::i()->to );

 						if( !$member->member_id )
 						{
 							$member = NULL;
 						}
 					}

					$return['to'] = new FormMember( 'messenger_to', $member, TRUE, array( 'disabled' => (bool)Request::i()->alert, 'multiple' => ( Member::loggedIn()->group['g_max_mass_pm'] == -1 ) ? NULL : Member::loggedIn()->group['g_max_mass_pm'] ), function ( $members )
					{
						if ( is_array( $members ) )
						{
							foreach ( $members as $m )
							{
								if ( !$m instanceof Member OR !static::memberCanReceiveNewMessage( $m, Member::loggedIn(), 'new' ) )
								{
									throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('meesnger_err_bad_recipient', FALSE, array( 'sprintf' => array( ( $m instanceof Member ) ? $m->name : $m ) ) ) );
								}
							}
						}
						else
						{
							if ( !$members instanceof Member OR !$members->member_id OR !static::memberCanReceiveNewMessage( $members, Member::loggedIn(), 'new' ) )
							{
								throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('meesnger_err_bad_recipient', FALSE, array( 'sprintf' => array( ( $members instanceof Member ) ? $members->name : $members ) ) ) );
							}
						}
					} );
				}
			}
			else if ( $k == 'content' )
			{
				if( isset( Request::i()->to ) and Request::i()->to )
				{
					$v->options['autoSaveKey'] = 'newMessageTo-' . Request::i()->to;
				}
				else
				{
					/* Ensure that the autosave doesn't populate the editor witha previous PM which may be sent by accident */
					$v->options['autoSaveKey'] = 'newMessageTo-' . mt_rand();
				}
			}
				
			$return[ $k ] = $v;
		}

		if( Request::i()->alert )
		{
			unset( $return['title'] );
		}

		return $return;
	}
	
	
	/**
	 * Check if a member can receive new messages
	 *
	 * @param	Member	$member	The member to check
	 * @param	Member	$sender	The member sending the new message
	 * @param	string		$type	Type of message to check (new, reply)
	 
	 * @return	bool
	 */
	public static function memberCanReceiveNewMessage( Member $member, Member $sender, string $type='new' ) : bool
	{
		/* Messenger is hard disabled */
		if ( $member->members_disable_pm == 2 )
		{
			return FALSE;
		}
		else if ( $member->members_disable_pm == 1 )
		{
			/* We will allow moderators */
			return $sender->modPermissions() !== FALSE;
		}
		
		/* Group can not use messenger */
		if ( !$member->canAccessModule( Module::get( 'core', 'messaging' ) ) )
		{
			return FALSE;
		}
		
		/* Inbox is full */
		if ( ( $member->group['g_max_messages'] > 0 AND $member->msg_count_total >= $member->group['g_max_messages'] ) and !$sender->group['gbw_pm_override_inbox_full'] )
		{
			return FALSE;
		}
		
		/* Is being ignored */
		if ( $member->isIgnoring( $sender, 'messages' ) )
		{
			return FALSE;
		}
		
		
		return TRUE;
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param array $values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( array $values ): void
	{
		$this->maps = array();
		$this->to_count = ( $values['messenger_to'] instanceof Member ) ? 1 : count( $values['messenger_to'] );

		parent::processBeforeCreate( $values );
	}
				
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param Comment|null $comment	The first comment
	 * @param array $values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( ?Comment $comment, array $values ): void
	{
		/* Set the first message ID */
		$this->first_msg_id = $comment->id;
		$this->save();
		
		if ( is_array( $values['messenger_to'] ) )
		{
			$members = array_map( function( $member )
			{
				return $member->member_id;
			}, $values['messenger_to'] );
		}
		else
		{
			$members[] = $values['messenger_to']->member_id;
		}

		$members[]	= $this->starter_id;

		/* Authorize everyone */
		$this->authorize( $members );
		
		/* Run parent */
		parent::processAfterCreate( $comment, $values );
		
		/* Send the notification for the first message */
		$comment->sendNotifications();

		/* If this came from an alert dismiss the alert */
		if( Request::i()->alert )
		{
			try
			{
				$alert = Alert::load( Request::i()->alert );

				if( $alert->forMember( Member::loggedIn() ) )
				{
					$alert->dismiss();

					$this->alert = $alert->id;
					$this->save();
				}
			}
			catch ( Exception $e ){}
		}
	}

	/**
	 * Does a member have permission to access?
	 *
	 * @param Member|null $member The member to check for
	 * @return    bool
	 */
	public function canView( ?Member $member=null ): bool
	{
		$member = $member ?: Member::loggedIn();

		/* Is the user part of the conversation? */
		foreach ( $this->maps() as $map )
		{
			if ( $map['map_user_id'] === $member->member_id and $map['map_user_active'] )
			{
				return TRUE;
			}
		}
		
		/* Have we granted them temporary permission from the report center or a warning log? */
		if ( $member->modPermission('can_view_reports') )
		{
			/* If we are coming directly from a report, and the Report ID is different from what is stored in session, then we need to unset it so it can be reset */
			if ( isset( $_SESSION['report'] ) AND isset( Request::i()->_report ) AND Request::i()->_report != $_SESSION['report'] )
			{
				unset( $_SESSION['report'] );
			}
			
			$report = $_SESSION['report'] ?? ( isset( Request::i()->_report ) ? Request::i()->_report : NULL );
			if ( $report )
			{
				try
				{
					$report = Report::load( $report );
					if ( $report->class == 'IPS\core\Messenger\Message' and in_array( $report->content_id, iterator_to_array( Db::i()->select( 'msg_id', 'core_message_posts', array( 'msg_topic_id=?', $this->id ) ) ) ) )
					{
						$_SESSION['report'] = $report->id;
						return TRUE;
					}
				}
				catch ( OutOfRangeException $e ){ }
			}
		}
		if ( $member->modPermission('mod_see_warn') )
		{
			/* If we are coming directly from a warning, and the Warning ID is different from what is stored in session, then we need to unset it so it can be reset */
			if ( isset( $_SESSION['warning'] ) AND isset( Request::i()->_warning ) AND Request::i()->_warning != $_SESSION['warning'] )
			{
				unset( $_SESSION['warning'] );
			}
			
			$warning = $_SESSION['warning'] ?? ( isset( Request::i()->_warning ) ? Request::i()->_warning : NULL );
			if ( $warning )
			{
				try
				{
					$warning = Warning::load( $warning );
					
					if ( $warning->content_app == 'core' AND $warning->content_module == 'messaging-comment' AND $warning->content_id1 == $this->id )
					{
						$_SESSION['warning'] = $warning->id;
						return TRUE;
					}
				}
				catch( OutOfRangeException $e ) { }
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( ?Member $member=NULL ): bool
	{
		if( Member::loggedIn()->modPermission( 'can_view_reports' ) )
		{
			return TRUE; // Moderators who can manage reported content can "delete" conversations
		}
		
		return FALSE; // You don't delete a conversation. It gets deleted automatically when everyone has left.
	}
	
	/**
	 * Actions to show in comment multi-mod
	 *
	 * @param	Member|null	$member	Member (NULL for currently logged in member)
	 * @return	array
	 */
	public function commentMultimodActions( Member|null $member = NULL ): array
	{
		return array();
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( string|null $action=NULL ): Url
	{
		$_key	= $action ? md5( $action ) : NULL;

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = Url::internal( "app=core&module=messaging&controller=messenger&id={$this->id}", 'front', 'messenger_convo' );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/* !\IPS\core\Messenger\Conversation */
	
	/**
	 * Get the number of active participants
	 *
	 * @return	int
	 */
	public function get_activeParticipants() : int
	{
		return count( array_filter( $this->maps, function( $map )
		{
			return $map['map_user_active'];
		} ) );
	}
	
	/**
	 * Get the map for the current member
	 *
	 * @return	array
	 */
	public function get_map() : array
	{
		$maps = $this->maps();
		
		/* From a report? */
		if ( ( $_SESSION['report'] ?? ( isset( Request::i()->_report ) ? Request::i()->_report : NULL ) ) AND Member::loggedIn()->modPermission( 'can_view_reports' ) )
		{
			return array();
		}
		
		if ( isset( $maps[ Member::loggedIn()->member_id ] ) )
		{
			return $maps[ Member::loggedIn()->member_id ];
		}
		
		throw new OutOfRangeException;
	}
	
	/**
	 * Get the most recent unread conversation and dismiss the popup
	 *
	 * @param	bool	$dismiss	Whether or not to dismiss the popup for future page loads
	 * @return    Conversation|NULL
	 */
	public static function latestUnreadConversation( bool $dismiss = TRUE ) : ?Conversation
	{
		$return = NULL;
		$latestConversationMap = Db::i()->select( 'map_topic_id', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0', Member::loggedIn()->member_id ), 'map_last_topic_reply DESC' );

		try
		{
			$return = static::loadAndCheckPerms( $latestConversationMap->first() );
		}
		catch ( OutOfRangeException | UnderflowException $e ) { }
		
		if( $dismiss === TRUE )
		{
			Member::loggedIn()->msg_show_notification = FALSE;
			Member::loggedIn()->save();
		}

		return $return;
	}

	/**
	 * Get the most recent unread message and dismiss the popup
	 *
	 * @note	This is here and abstracted to account for database read/write separation where the conversation may be available, but not the message itself
	 * @return    Message|NULL
	 */
	public static function latestUnreadMessage() : ?Message
	{
		/* Get the latest conversation, but don't dismiss the notification yet */
		if( $conversation = static::latestUnreadConversation( FALSE ) )
		{
			/* Get the latest comment, which is what we will actually use in the template */
			if( $latestComment = $conversation->comments( 1, 0, 'date', 'desc' ) )
			{
				/* Ok we have what we need, NOW dismiss the notification */
				Member::loggedIn()->msg_show_notification = FALSE;
				Member::loggedIn()->save();

				return $latestComment;
			}
		}

		return NULL;
	}
	
	/**
	 * Recount the member's message counts
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public static function rebuildMessageCounts( Member $member ) : void
	{
		$total = Db::i()->select( 'count(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', $member->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$member->msg_count_total = $total;
		
		$new = Db::i()->select( 'count(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0 AND map_last_topic_reply>?', $member->member_id, $member->msg_count_reset ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$member->msg_count_new = $new;
		
		$member->save();
	}
	
	/**
	 * @brief	Maps cache
	 */
	public ?array $maps = NULL;
	
	/**
	 * Get maps
	 *
	 * @param 	boolean		$refresh 		Force maps to be refreshed?
	 * @return	array
	 */
	public function maps( bool $refresh = FALSE ) : array
	{
		if ( $this->maps === NULL || $refresh === TRUE )
		{
			$this->maps = iterator_to_array( Db::i()->select( '*', 'core_message_topic_user_map', array( 'map_topic_id=?', $this->id ) )->setKeyField( 'map_user_id' ) );
		}
		return $this->maps;
	}
	
	/**
	 * Grant a member access
	 *
	 * @param	Member|array	$members		The member(s) to grant access
	 * @return	bool|array
	 */
	public function authorize( Member|array $members ) : bool|array
	{
		$members = is_array( $members ) ? $members : array( $members );
		
		/* Go through each member */
		foreach ( $members as $member )
		{
			if ( is_int( $member ) )
			{
				$member = Member::load( $member );
			}
						
			$done = FALSE;
			
			/* If they already have a map, update it */
			foreach ( $this->maps() as $map )
			{
				if ( $map['map_user_id'] == $member->member_id )
				{
					$this->maps[ $member->member_id ]['map_user_active'] = TRUE;
					$this->maps[ $member->member_id ]['map_user_banned'] = FALSE;
					Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => 1, 'map_user_banned' => 0 ), array( 'map_user_id=? AND map_topic_id=?', $member->member_id, $this->id ) );
					$done = TRUE;
					break;
				}
			}

			/* If not, create one */
			if ( !$done )
			{
				/* Create map */
				$this->maps[ $member->member_id ] = array(
					'map_user_id'				=> $member->member_id,
					'map_topic_id'				=> $this->id,
					'map_folder_id'				=> 'myconvo',
					'map_read_time'				=> ( $member->member_id == $this->starter_id ) ? time() : 0,
					'map_user_active'			=> TRUE,
					'map_user_banned'			=> FALSE,
					'map_has_unread'			=> !( ( $member->member_id == $this->starter_id ) ),
					'map_is_system'				=> FALSE,
					'map_is_starter'			=> ( $member->member_id == $this->starter_id ),
					'map_left_time'				=> 0,
					'map_ignore_notification'	=> FALSE,
					'map_last_topic_reply'		=> time(),
				);
				Db::i()->insert( 'core_message_topic_user_map', $this->maps[ $member->member_id ] );
			}

			if ( $member->members_bitoptions['show_pm_popup'] and $this->author()->member_id != $member->member_id )
			{
				$member->msg_show_notification = TRUE;
				$member->save();
			}
			
			/* Note: emails for added participants are sent from controller, as this central method is called when conversation is first created also */

			/* Rebuild the user's counts */
			static::rebuildMessageCounts( $member );
		}
		
		/* Rebuild the participants of this conversation */
		$this->rebuildParticipants();
		
		return $this->maps;
	}
	
	/**
	 * Remove a member access
	 *
	 * @param	Member|array	$members	The member(s) to remove access
	 * @param	bool				$banned		User is being blocked by the conversation starter (as opposed to leaving voluntarily)?
	 * @return	void
	 */
	public function deauthorize( Member|array $members, bool $banned=FALSE ) : void
	{
		$members = is_array( $members ) ? $members : array( $members );
		foreach ( $members as $member )
		{
			unset( $this->maps[ $member->member_id ] );
			Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => 0, 'map_user_banned' => $banned ), array( 'map_user_id=? AND map_topic_id=?', $member->member_id, $this->id ) );
			Db::i()->delete( 'core_notifications', array( 'notification_key=? AND item_id = ? AND `member`=?', 'new_private_message', $this->id, $member->member_id ) );
			static::rebuildMessageCounts( $member );
		}
		$this->rebuildParticipants();
	}
	
	/**
	 * Rebuild participants
	 *
	 * @return	void
	 */
	public function rebuildParticipants() : void
	{
		$activeParticipants = 0;
		foreach ( $this->maps() as $map )
		{
			if ( $map['map_user_active'] )
			{
				$activeParticipants++;
			}
		}
		
		if ( $activeParticipants )
		{
			$this->to_count = $activeParticipants;
			$this->save();
		}
		else
		{
			$this->delete();
		}
	}

	/**
	 * @brief	Cache member data we've already looked up so we don't have to do it again
	 */
	public static array $participantMembers = array();
	
	/**
	 * @brief	Particpant blurb
	 */
	public ?string $participantBlurb = NULL;
	
	/**
	 * Get participant blurb
	 *
	 * @return	string
	 */
	public function participantBlurb() : string
	{
		if( $this->participantBlurb !== NULL )
		{
			return $this->participantBlurb;
		}

		$people = array();

		$memberIds = array_keys( $this->maps() );

		foreach( $memberIds as $_idx => $memberId )
		{
			if( isset( static::$participantMembers[ $memberId ] ) )
			{
				if ( $memberId === Member::loggedIn()->member_id )
				{
					$people[ $memberId ] = ( $memberId == $this->starter_id ) ? Member::loggedIn()->language()->addToStack('participant_you_upper') : Member::loggedIn()->language()->addToStack('participant_you_lower');
				}
				else
				{
					$people[ $memberId ] = static::$participantMembers[ $memberId ];
				}

				unset( $memberIds[ $_idx ] );
			}
		}

		if( count( $memberIds ) )
		{
			foreach( Db::i()->select( 'member_id, name', 'core_members', array( Db::i()->in( 'member_id', $memberIds ) ) ) as $member )
			{
				if ( $member['member_id'] === Member::loggedIn()->member_id )
				{
					$member['name'] = ( $member['member_id'] == $this->starter_id ) ? Member::loggedIn()->language()->addToStack('participant_you_upper') : Member::loggedIn()->language()->addToStack('participant_you_lower');
				}
				$people[ $member['member_id'] ] = $member['name'];
				static::$participantMembers[ $member['member_id'] ] = $member['name'];
			}
		}
		
		/* Move the starter to the front of the array */
		$starter = $people[ $this->starter_id ];
		unset( $people[ $this->starter_id ] );
		array_unshift( $people, $starter );
		unset( $starter );
		
		if ( count( $people ) == 1 )
		{
			$id   = key( $people );
			$name = array_pop( $people );
			$this->participantBlurb = Member::loggedIn()->member_id === $id ? Member::loggedIn()->language()->addToStack( 'participant_you_upper' ) : $name;
		}
		elseif ( count( $people ) == 2 )
		{
			$this->participantBlurb = Member::loggedIn()->language()->addToStack( 'participant_two', FALSE, array( 'sprintf' => $people ) );
		}
		else
		{
			$count = 0;
			$others = array();
			$sprintf = array();
			foreach( $people as $id => $name )
			{
				if ( $count > 1 )
				{
					$others[] = $name;
				}
				else
				{
					$sprintf[] = $name;
				}
				
				$count++;
			}

			$sprintf[] = Member::loggedIn()->language()->formatList( $others );
			$sprintf[] = count( $others );
			
			$this->participantBlurb = Member::loggedIn()->language()->addToStack( 'participant_three_plus', FALSE, array( 'pluralize' => array( count( $others ) ), 'sprintf' => $sprintf ) );
		}

		return $this->participantBlurb;
	}
	
	/**
	 * Move a message to a different folder
	 *
	 * @param	string				$to			Folder name
	 * @param	Member|null	$member	Member object, or null to use logged in member
	 * @return  void
	 * @throws OutOfRangeException
	 */
	public function moveConversation( string $to, ?Member $member=NULL ) : void
	{
		$member = ( $member != NULL ) ? $member : Member::loggedIn();
		
		if ( in_array( $to, array_merge( array( 'myconvo' ), array_keys( json_decode( $member->pconversation_filters, TRUE ) ) ) ) )
		{
			Db::i()->update( 'core_message_topic_user_map', array( 'map_folder_id' => $to ), array( 'map_user_id=? AND map_topic_id=?', $member->member_id, $this->id ) );
		}
		else
		{
			throw new OutOfRangeException;
		}
	}

	/**
	 * Build form to create
	 *
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param	Item|NULL	$item		Content item, e.g. if editing
	 * @return	Form
	 */
	protected static function buildCreateForm( Model $container=null, Item $item=NULL ): Form
	{
		$form = parent::buildCreateForm( $container, $item );

		try
		{
			$alert = Alert::load( Request::i()->alert );

			if( $alert->forMember( Member::loggedIn() ) )
			{
				$form->hiddenValues['alert'] = $alert->id;
				$form->hiddenValues['messenger_title'] = $alert->title;
			}
		}
		catch ( OutOfRangeException $e ) {}

		return $form;
	}
}