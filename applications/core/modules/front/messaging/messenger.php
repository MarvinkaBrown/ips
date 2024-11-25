<?php
/**
 * @brief		Messenger
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Jul 2013
 */

namespace IPS\core\modules\front\messaging;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content\Controller;
use IPS\Content\Search\Mysql\Query;
use IPS\core\Alerts\Alert;
use IPS\core\Messenger\Conversation;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Notification;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Messenger
 */
class messenger extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\core\Messenger\Conversation';
	
	/**
	 * [Content\Controller]	FURL Base
	 */
	protected static string $furlBase = 'messenger';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{		
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C137/1', 403, '' );
		}
		
		if ( Member::loggedIn()->members_disable_pm == 2 )
		{
			Output::i()->error( 'messenger_disabled', '2C137/9', 403, '' );
		}

		Output::i()->sidebar['enabled'] = FALSE;

		if ( !Request::i()->isAjax() )
		{
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery-ui.js', 'core', 'interface' ) );
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js( 'jquery/jquery-touchpunch.js', 'core', 'interface' ) );
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js( 'front_model_messages.js', 'core' ) );
			Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_messages.js', 'core' ) );
			Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/messaging.css' ) );
		}
		
		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=messaging&controller=messenger', NULL, 'messaging' ), array(), 'loc_using_messenger' );
		
		parent::execute();
	}
	
	/**
	 * Messenger
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		$baseUrl = Url::internal( "app=core&module=messaging&controller=messenger", 'front', 'messaging' );
		$alert = NULL;

		/* Get folders */
		$folders = array( 'myconvo'	=> Member::loggedIn()->language()->addToStack('messenger_folder_inbox') );
		if ( Member::loggedIn()->pconversation_filters )
		{
			$folders = $folders + array_filter( json_decode( Member::loggedIn()->pconversation_filters, TRUE ) );
		}
		
		/* Are we looking at a specific folder? */
		if ( isset( $folders[ Request::i()->folder ] ) )
		{
			$baseUrl = $baseUrl->setQueryString( 'folder', Request::i()->folder );
			$folder = Request::i()->folder;
		}
		else
		{
			$folder = NULL;
		}
		
		/* What are our folder counts? */
		/* Note: The setKeyField and setValueField calls here were causing the folder counts to be incorrect (if you had two folders with 1 message in each, then both showed a count of 2) */
		$counts = iterator_to_array( Db::i()->select( 'map_folder_id, count(*) as count', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', Member::loggedIn()->member_id ), NULL, NULL, 'map_folder_id' ) );
		$folderCounts = array();
		foreach( $counts AS $k => $count )
		{
			$folderCounts[$count['map_folder_id']] = $count['count'];
		}
		
		/* Are we looking at a message? */
		$conversation = NULL;
		if ( Request::i()->id )
		{
			try
			{
				$conversation = Conversation::loadAndCheckPerms( Request::i()->id );

				/* If this message triggered a PM popup and we view it directly, it will no longer be marked as unread but msg_show_notification for
					the member will still be set, causing the next oldest PM to show in a popup erroneously.  If this is the latest unread PM, reset
					msg_show_notification now to prevent the popup from showing. */
				if( Member::loggedIn()->msg_show_notification )
				{
					$latestConversation = NULL;

					try
					{
						$latestConversation = Db::i()->select( 'map_topic_id', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0', Member::loggedIn()->member_id ), 'map_last_topic_reply DESC' )->first();
					}
					catch ( UnderflowException $e ) { }

					if( $latestConversation == $conversation->id )
					{
						Member::loggedIn()->msg_show_notification = FALSE;
						Member::loggedIn()->save();
					}
				}
				
				Db::i()->update( 'core_message_topic_user_map', array( 'map_read_time' => time(), 'map_has_unread' => 0 ), array( 'map_user_id=? AND map_topic_id=?', Member::loggedIn()->member_id, $conversation->id ) );
				Conversation::rebuildMessageCounts( Member::loggedIn() );

				if ( Request::i()->isAjax() and Request::i()->getRow )
				{
					$row = Db::i()->select(
						'core_message_topic_user_map.*, core_message_topics.*',
						'core_message_posts',
						array( 'mt_id=?', $conversation->id )
					)->join(
						'core_message_topics',
						'core_message_posts.msg_topic_id=core_message_topics.mt_id'
					)->join(
						'core_message_topic_user_map',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id AND core_message_topic_user_map.map_user_id=' . intval( Member::loggedIn()->member_id )
					)->first();
					$row['last_message'] = Conversation::load( $row['mt_id'] )->comments( 1, 0, 'date', 'desc' );
					$row['participants'] = Conversation::load( $row['mt_id'] )->participantBlurb();
										
					Output::i()->json( Theme::i()->getTemplate( 'messaging' )->messageListRow( $row, !Request::i()->_fromMenu, $folders ) );
				}

				Output::i()->title = $conversation->title;
				$baseUrl = $baseUrl->setQueryString( 'id', Request::i()->id );
				
				if ( isset( Request::i()->page ) and $conversation->replies )
				{
					$maxPages = ceil( $conversation->replies / $conversation::getCommentsPerPage() );
					if ( Request::i()->page > $maxPages )
					{
						Output::i()->redirect( $baseUrl->setPage( 'page', $maxPages ) );
					}
				}
				
				/* We check isset() on the map because checking message from a report means no map will be returned */
				if ( $folder === NULL AND isset( $conversation->map['map_folder_id'] ) )
				{
					$folder = $conversation->map['map_folder_id'];
				}
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2C137/2', 403, '' );
			}
			
			if ( isset( Request::i()->latest ) )
			{
				$maps = $conversation->maps();

				$message = NULL;
				if ( isset( $maps[ Member::loggedIn()->member_id ] ) and $maps[ Member::loggedIn()->member_id ]['map_read_time'] )
				{
					$message = $conversation->comments( 1, NULL, 'date', 'asc', NULL, NULL, DateTime::ts( $maps[ Member::loggedIn()->member_id ]['map_read_time'] ) );
				}

				/** if we don't have a unread comment, redirect to the last comment */
				if ( !$message )
				{
					$message = $conversation->comments( 1, NULL, 'date', 'desc', NULL, NULL );
				}

				if ( $message )
				{
					Output::i()->redirect( $message->url() );
				}
			}

			if( $conversation->alert )
			{
				try
				{
					$alert = Alert::load( $conversation->alert );

					if( ! $alert->forMember( Member::loggedIn() ) and $alert->author()->member_id != Member::loggedIn()->member_id )
					{
						$alert = NULL;
					}
				}
				catch ( OutOfRangeException $e ) {}
			}
		}
		
		/* Default folder */
		if ( $folder === NULL )
		{
			$folder = 'myconvo';
		}
		
		if ( !isset( Request::i()->id ) )
		{
			Output::i()->title = $folders[ $folder ];
		}
		
		/* Do we need a filter? */
		$where = array( array( 'core_message_topic_user_map.map_user_id=? AND core_message_topic_user_map.map_user_active=1', Member::loggedIn()->member_id ) );
		if ( Request::i()->filter == 'mine' )
		{
			$where[] = array( 'core_message_topic_user_map.map_is_starter=1' );
		}
		elseif(  Request::i()->filter == 'not_mine' )
		{
			$where[] = array( 'core_message_topic_user_map.map_is_starter=0' );
		}
		elseif(  Request::i()->filter == 'read' )
		{
			$where[] = array( 'core_message_topic_user_map.map_has_unread=0' );
		}
		elseif(  Request::i()->filter == 'not_read' )
		{
			$where[] = array( 'core_message_topic_user_map.map_has_unread=1' );
		}
		
		/* Add a folder filter, if we are not coming from the Messages menu */
		if ( !isset( Request::i()->_fromMenu ) )
		{
			if ( !is_null( $folder ) AND isset( $folders[ $folder ] ) )
			{
				$where[] = array( 'core_message_topic_user_map.map_folder_id=?', $folder );
			}
			else
			{
				$where[] = array( 'core_message_topic_user_map.map_folder_id=?', 'myconvo' );
			}
		}

		/* If we're searching, get the results */
		$perPage = 25;
		$iterator = array();
		
		if ( Request::i()->q )
		{
			$subQuery = Db::i()->select( 'map_topic_id', array( 'core_message_topic_user_map', 'core_message_topic_user_map' ), $where );
			$where = array( array( 'core_message_posts.msg_topic_id IN (?)', $subQuery ) );
			$query = array();
			$prefix = Db::i()->prefix;
			
			if ( isset( Request::i()->search ) and $searchValues = Request::i()->valueFromArray('search') )
			{
				if ( isset( $searchValues['topic'] ) or isset( $searchValues['post'] ) )
				{
					$fulltext = [];
					if ( isset( $searchValues['topic'] ) )
					{
						$fulltext[] = Query::matchClause( "core_message_topics.mt_title", Request::i()->q, '+', FALSE );
					}
					if ( isset( $searchValues['post'] ) )
					{
						$fulltext[] = Query::matchClause( "core_message_posts.msg_post", Request::i()->q, '+', FALSE );
					}

					$query[] = "core_message_posts.msg_id IN( SELECT msg_id FROM {$prefix}core_message_posts as core_message_posts LEFT JOIN {$prefix}core_message_topics as core_message_topics ON core_message_posts.msg_topic_id=core_message_topics.mt_id WHERE (" . implode( " OR ", $fulltext ) . ") )";
				}
				
				if ( isset( $searchValues['recipient'] ) )
				{
					$query[] = "core_message_posts.msg_topic_id IN ( SELECT sender_map.map_topic_id FROM {$prefix}core_message_topic_user_map AS sender_map WHERE sender_map.map_is_starter=1 AND sender_map.map_user_id IN ( SELECT member_id FROM {$prefix}core_members AS sm WHERE name LIKE '" . Db::i()->escape_string( Request::i()->q ) . "%' ) )";
				}
				if ( isset( $searchValues['sender'] ) )
				{
					$query[] = "core_message_posts.msg_topic_id IN ( SELECT receiver_map.map_topic_id FROM {$prefix}core_message_topic_user_map AS receiver_map WHERE receiver_map.map_is_starter=0 AND receiver_map.map_user_id IN ( SELECT member_id FROM {$prefix}core_members AS rm WHERE name LIKE '" . Db::i()->escape_string( Request::i()->q ) . "%') )";
				}
				
				if ( count( $query ) )
				{
					$where[] = array( '(' . implode( ' OR ', $query ) . ')' );
				}
			}

			/* Get a count */
			try
			{
				$count	= Db::i()->select( 'COUNT( DISTINCT( msg_topic_id ) )', 'core_message_posts', $where )
					->join(
						'core_message_topics',
						'core_message_posts.msg_topic_id=core_message_topics.mt_id'
					)
					->join(
						'core_message_topic_user_map',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					)
					->first();
			}
			catch( UnderflowException $e )
			{
				$count	= 0;
			}

			/* Performance: if count is 0, don't bother selecting ... it's a wasted query */
			if( $count )
			{
				/* Because of strict group by, we first need to select the ids, then grab those topics */
				$sortColumn	= ( in_array( Request::i()->sortBy, array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) ) ? Request::i()->sortBy : 'mt_last_post_time' );
				$iterator	= Db::i()->select(
						'core_message_posts.msg_topic_id',
						'core_message_posts',
						$where,
						$sortColumn . ' DESC',
						array( ( intval( Request::i()->listPage ?: 1 ) - 1 ) * $perPage, $perPage ),
						array( 'msg_topic_id', $sortColumn )
					)->join(
						'core_message_topics',
						'core_message_posts.msg_topic_id=core_message_topics.mt_id'
					)->join(
						'core_message_topic_user_map',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					);

				/* Get iterator */
				$iterator	= Db::i()->select(
						'core_message_topic_user_map.*, core_message_topics.*',
						'core_message_topic_user_map',
						array( 'map_topic_id IN(' . implode( ',', iterator_to_array( $iterator ) ) . ')' ),
						$sortColumn . ' DESC'
					)->join(
						'core_message_topics',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					);
			}
		}
		else
		{
			/* Get a count */
			$count = Db::i()->select( 'COUNT(*)', 'core_message_topic_user_map', $where )->first();

			/* Performance: if $count is 0, don't bother selecting ... it's a wasted query */
			if( $count )
			{
				if ( $alert = Alert::getAlertCurrentlyFilteringMessages() )
				{
					$where[] = array( 'core_message_topics.mt_alert=?', $alert->id );
				}

				/* Get iterator */
				$iterator	= Db::i()->select(
						'core_message_topic_user_map.*, core_message_topics.*',
						'core_message_topic_user_map',
						$where,
						( in_array( Request::i()->sortBy, array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) ) ? Request::i()->sortBy : 'mt_last_post_time' ) . ' DESC',
						array( ( intval( Request::i()->listPage ?: 1 ) - 1 ) * $perPage, $perPage )
					)->join(
						'core_message_topics',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					);
			}
		}

		/* Get the map data in one query to avoid querying it separately for each message */
		$messageIds	= array();
		$userIds	= array();
		$maps		= array();
		$results	= $count ? iterator_to_array( $iterator ) : array();

		foreach( $results as $row )
		{
			$messageIds[] = $row['mt_id'];
		}

		foreach( Db::i()->select( '*', 'core_message_topic_user_map', array( Db::i()->in( 'map_topic_id', $messageIds ) ) )->setKeyField( 'map_user_id' ) as $mapRow )
		{
			$maps[ $mapRow['map_topic_id'] ][ $mapRow['map_user_id'] ] = $mapRow;
			$userIds[ $mapRow['map_user_id'] ] = $mapRow['map_user_id'];
		}

		if( count( $userIds ) )
		{
			foreach( Db::i()->select( 'member_id, name', 'core_members', array( Db::i()->in( 'member_id', $userIds ) ) ) as $member )
			{
				if ( $member['member_id'] === Member::loggedIn()->member_id )
				{
					$member['name'] = Member::loggedIn()->language()->addToStack('participant_you_lower');
				}

				Conversation::$participantMembers[ $member['member_id'] ] = $member['name'];
			}
		}

		/* Build the message list */
		$conversations = array();

		foreach ( $results as $row )
		{
			Conversation::constructFromData( $row )->maps = $maps[ $row['mt_id'] ];

			$row['last_message'] = Conversation::load( $row['mt_id'] )->comments( 1, 0, 'date', 'desc' );
			$row['participants'] = Conversation::load( $row['mt_id'] )->participantBlurb();
			$conversations[ $row['mt_id'] ] = $row;
		}

		/* Note the last time we looked at the message list */
		Member::loggedIn()->msg_count_reset = time();
		Conversation::rebuildMessageCounts( Member::loggedIn() );

		/* Display */
		$listUrl = $baseUrl;
		$baseUrl = $baseUrl->setQueryString( '_list', 1 );

		if( isset( Request::i()->filter ) )
		{
			$baseUrl = $baseUrl->setQueryString( 'filter', Request::i()->filter );
		}

		if( isset( Request::i()->q ) )
		{
			$baseUrl = $baseUrl->setQueryString( 'q', Request::i()->q );
			$baseUrl = $baseUrl->setQueryString( 'search', Request::i()->search );
		}

		$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, ceil( $count / $perPage ), ( Request::i()->listPage ?: 1 ), $perPage, TRUE, 'listPage' );
		if ( Request::i()->isAjax() )
		{
			if( Request::i()->id )
			{
				Output::i()->output = Theme::i()->getTemplate('messaging')->conversation( $conversation, $folders, $alert );
			}
			elseif( Request::i()->q )
			{
				/* If we are viewing > page 1 we need to return HTML instead of an object, since the infinite scroll library will have taken over now and just wants HTML */
				if( Request::i()->listPage AND Request::i()->listPage > 1 )
				{
					Output::i()->sendOutput( Theme::i()->getTemplate('messaging')->messageListRows( $conversations, $pagination, FALSE, $folders ) );
				}
				else
				{
					Output::i()->json( array(
						'data' => Theme::i()->getTemplate('messaging')->messageListRows( $conversations, $pagination, FALSE, $folders ),
						'pagination' => $pagination
					) );
				}
			}
			elseif( Request::i()->overview )
			{
				Output::i()->json( array(
					'data' => Theme::i()->getTemplate('messaging')->messageListRows( $conversations, $pagination, !Request::i()->_fromMenu, $folders ),
					'pagination' => $pagination,
					'listBaseUrl' => $listUrl->setQueryString( array( 'sortBy' => Request::i()->sortBy, 'filter' => Request::i()->filter ) )->stripQueryString( 'id' )
				) );
			}
			else
			{
				Output::i()->sendOutput( Theme::i()->getTemplate('messaging')->messageListRows( $conversations, $pagination, TRUE, $folders ) );
			}
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate('messaging')->template( $folder, $folders, $folderCounts, $conversations, $pagination, $conversation, $baseUrl, ( $conversation ? 'messenger_convo' : 'messenger' ), ( Request::i()->sortBy ? htmlspecialchars( Request::i()->sortBy, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): 'mt_last_post_time' ), ( Request::i()->filter ? htmlspecialchars( Request::i()->filter, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ) : '' ), $alert );
		}

		return null;
	}
	
	/**
	 * Compose
	 *
	 * @return	void
	 */
	protected function compose() : void
	{
		Output::i()->title		= Member::loggedIn()->language()->addToStack('compose_new');
		if( Request::i()->alert )
		{
			try
			{
				$alert = Alert::load( Request::i()->alert );

				if( $alert->forMember( Member::loggedIn() ) )
				{
					$form = $alert->getNewConversationForm();
					Output::i()->title		= Member::loggedIn()->language()->addToStack('alert_modal_title', FALSE, array( 'sprintf' => array( $alert->title ) ) );
				}
			}
			catch ( OutOfRangeException $e ) {}
		}

		if ( empty( $form) )
		{
			$form = Conversation::create();
			$form->class = 'ipsForm--vertical ipsForm--compose-message';
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->output	= $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
		else
		{
			Output::i()->output	= Theme::i()->getTemplate('messaging')->submitForm( Member::loggedIn()->language()->addToStack('compose_new'), $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ) );
		}
	}
	
	/**
	 * Move messages form
	 *
	 * @return void
	 */
	protected function moveForm() : void
	{
		Session::i()->csrfCheck();
		
		$folders = json_decode( Member::loggedIn()->pconversation_filters, TRUE ) ?? array();
		array_walk( $folders, function( &$val )
		{
			$val = htmlspecialchars( $val, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8' );
		} );

		$form = new Form;
		$form->add( new Select( 'move_message_to', NULL, FALSE, array( 'options' => array( 'myconvo' => Member::loggedIn()->language()->addToStack('inbox') ) + $folders, 'parse' => 'normal' ) ) );
		
		if ( $values = $form->values() )
		{
			$ids = explode( ',', Request::i()->ids );
			foreach( $ids as $id )
			{
				try
				{
					$conversation = Conversation::loadAndCheckPerms( $id );
					$conversation->moveConversation( Request::i()->move_message_to );
				}
				catch ( OutOfRangeException $e )
				{
					continue;
				}
			}
			
			Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger&folder=' . Request::i()->move_message_to, 'front', 'messaging' ) );
		}
		
		Output::i()->title  = 'messenger_move';
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}
	
	/**
	 * Add Folder
	 *
	 * @return	void
	 */
	protected function addFolder() : void
	{
		Session::i()->csrfCheck();

		$form = new Form;
		$form->add( new Text( 'messenger_add_folder_name', NULL, TRUE ) );
		if ( $values = $form->values() )
		{
			$folders = json_decode( Member::loggedIn()->pconversation_filters, TRUE ) ?? array();
			$folders[] = $values['messenger_add_folder_name'];
			Member::loggedIn()->pconversation_filters = json_encode( $folders );
			Member::loggedIn()->save();

			if ( Request::i()->isAjax() )
			{
				$keys = array_keys( $folders );
				
				Output::i()->json( array(
					'folderName' => $values['messenger_add_folder_name'],
					'key' => array_pop( $keys )
				)	);
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
			}
		}
		
		$form->class = 'ipsForm--vertical ipsForm--add-inbox-folder';
		$formHtml = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );

		Output::i()->output = Output::i()->output = Theme::i()->getTemplate( 'messaging' )->folderForm( 'add', $formHtml );
	}
	
	/**
	 * Mark Folder Read
	 *
	 * @return	void
	 */
	protected function readFolder() : void
	{
		Session::i()->csrfCheck();
		Db::i()->update( 'core_message_topic_user_map', array( 'map_has_unread' => FALSE ), array( 'map_user_id=? AND map_folder_id=?', Member::loggedIn()->member_id, Request::i()->folder ) );
		Conversation::rebuildMessageCounts( Member::loggedIn() );
		Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
	}
	
	/**
	 * Delete Folder
	 *
	 * @return	void
	 */
	protected function deleteFolder() : void
	{
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();

		/* Make sure the user isn't playing with the URL to do strange things */
		$folders = json_decode( Member::loggedIn()->pconversation_filters, TRUE ) ?? array();

		if( Request::i()->folder == 'myconvo' OR !isset( $folders[ Request::i()->folder ] ) )
		{
			Output::i()->error( 'messenger_cannot_delete_folder', '3C137/C', 404, '' );
		}

		Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => FALSE, 'map_left_time' => time() ), array( 'map_user_id=? AND map_folder_id=?', Member::loggedIn()->member_id, Request::i()->folder ) );
		
		unset( $folders[ Request::i()->folder ] );
		Member::loggedIn()->pconversation_filters = json_encode( $folders );
		Member::loggedIn()->save();
		
		Conversation::rebuildMessageCounts( Member::loggedIn() );

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( $this->_getNewTotals() );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
		}
	}
	
	/**
	 * Empty Folder
	 *
	 * @return	void
	 */
	protected function emptyFolder() : void
	{
		Session::i()->csrfCheck();
		Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => FALSE, 'map_left_time' => time() ), array( 'map_user_id=? AND map_folder_id=?', Member::loggedIn()->member_id, Request::i()->folder ) );
		Conversation::rebuildMessageCounts( Member::loggedIn() );
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( $this->_getNewTotals() );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
		}
	}
	
	/**
	 * Rename Folder
	 *
	 * @return	void
	 */
	protected function renameFolder() : void
	{
		Session::i()->csrfCheck();

		$folders = json_decode( Member::loggedIn()->pconversation_filters, TRUE );
		
		$form = new Form;
		$form->add( new Text( 'messenger_add_folder_name', $folders[ Request::i()->folder ], TRUE ) );
		if ( $values = $form->values() )
		{
			$folders[ Request::i()->folder ] = $values['messenger_add_folder_name'];
			Member::loggedIn()->pconversation_filters = json_encode( $folders );
			Member::loggedIn()->save();

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array(
					'folderName' => $values['messenger_add_folder_name'],
					'key' => Request::i()->folder
				)	);
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
			}
		}
		
		$form->class = 'ipsForm--vertical ipsForm--rename-inbox-folder';
		$formHtml = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );

		Output::i()->output = Output::i()->output = Theme::i()->getTemplate( 'messaging' )->folderForm( 'rename', $formHtml );
	}
	
	/**
	 * Block a participant
	 *
	 * @return	void
	 */
	protected function blockParticipant() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$conversation = Conversation::loadAndCheckPerms( Request::i()->id );
			if ( $conversation->starter_id != Member::loggedIn()->member_id )
			{
				throw new BadMethodCallException;
			}
			$conversation->deauthorize( Member::load( Request::i()->member ), TRUE );

			if ( Request::i()->isAjax() )
			{
				$thisUser = array();

				foreach ( $conversation->maps( TRUE ) as $map )
				{
					if( $map['map_user_id'] == Request::i()->member )
					{
						$thisUser = $map;
					}
				}

				Output::i()->output = Output::i()->output = Theme::i()->getTemplate( 'messaging' )->participant( $thisUser, $conversation );
			}
			else
			{
				Output::i()->redirect( $conversation->url() );
			}
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'no_module_permission', '2C137/4', 403, '' );
		}
	}
	
	/**
	 * Unblock / Add a participant
	 *
	 * @return	void
	 */
	protected function addParticipant() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$conversation = Conversation::loadAndCheckPerms( Request::i()->id );
			
			$members = array();
			$ids = array();
			$failed = 0;
			
			if ( isset( Request::i()->member_names ) )
			{
				foreach ( explode( "\n", Request::i()->member_names ) as $name )
				{
					/* We have to html_entity_decode because the javascript code sends the encoded name here */
					$memberToAdd = Member::load( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ), 'name' );
					if ( $memberToAdd->member_id )
					{
						$members[] = $memberToAdd;
					}
					else
					{
						$failed++;
					}
				}
			}
			else
			{
				$memberToAdd = Member::load( Request::i()->member );
				if ( $memberToAdd->member_id )
				{
					$members[] = $memberToAdd;
				}
				else
				{
					$failed++;
				}
			}

			/* Check member limit */
			if ( Member::loggedIn()->group['g_max_mass_pm'] !== -1 AND $conversation->to_count + count( $members ) > Member::loggedIn()->group['g_max_mass_pm'] )
			{
				Output::i()->error( 'messenger_too_many_recipients', '3C137/D', 403, '' );
			}

			/* If we failed to load any members, error out if we're not ajaxing */
			if ( $failed > 0 && !Request::i()->isAjax() )
			{
				Output::i()->error( 'form_member_bad', '2C137/5', 403, '' );
			}

			$maps = $conversation->maps( TRUE );
			/* Authorize each of the members */
			foreach ( $members as $member )
			{
				if ( array_key_exists( $member->member_id, $maps ) and !$maps[ $member->member_id ]['map_user_active'] AND !$maps[ $member->member_id ]['map_user_banned'] )
				{
					throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('messenger_member_left', FALSE, array( 'sprintf' => array( $member->name ) ) ) );
				}
				
				if ( !$conversation::memberCanReceiveNewMessage( $member, Member::loggedIn(), 'new' ) )
				{
					throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack('meesnger_err_bad_recipient', FALSE, array( 'sprintf' => array( $member->name ) ) ) );
				}
				
				$maps = $conversation->authorize( $member );
				$ids[] = $member->member_id;

				$notification = new Notification( Application::load('core'), 'private_message_added', $conversation, array( $conversation, Member::loggedIn() ) );
				$notification->recipients->attach( $member );
				$notification->send();
			}

			/* Build the HTML for each new member */
			$memberHTML = array();

			foreach ( $maps as $map )
			{
				if( in_array( $map['map_user_id'], $ids ) )
				{
					$memberHTML[ $map['map_user_id'] ] = Theme::i()->getTemplate( 'messaging' )->participant( $map, $conversation );
				}
			}

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'members' => $memberHTML, 'failed' => $failed ) );
			}
			else
			{
				Output::i()->redirect( $conversation->url() );
			}
		}
		catch ( InvalidArgumentException $e )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array( 'error' => $e->getMessage() ), 403 );
			}
			else
			{
				Output::i()->error( $e->getMessage(), '2C137/B', 403, '' );
			}
		}
		catch ( LogicException $e )
		{
			Output::i()->error( 'no_module_permission', '2C137/5', 403, '' );
		}
	}
	
	/**
	 * Turn notifications on/off
	 *
	 * @return	void
	 */
	protected function notifications() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$conversation = Conversation::loadAndCheckPerms( Request::i()->id );
			Db::i()->update( 'core_message_topic_user_map', array( 'map_ignore_notification' => !Request::i()->status ), array( 'map_user_id=? AND map_topic_id=?', Member::loggedIn()->member_id, $conversation->id ) );
			Output::i()->redirect( $conversation->url() );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2C137/6', 403, '' );
		}
	}
	
	/**
	 * Move a conversation from one folder to another
	 *
	 * @return	void
	 */
	protected function move(): void
	{
		Session::i()->csrfCheck();

		try
		{
			$conversation = Conversation::loadAndCheckPerms( Request::i()->id );
			$conversation->moveConversation( Request::i()->to );
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( $this->_getNewTotals() );
			}
			else
			{
				Output::i()->redirect( $conversation->url() );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2C137/8', 403, '' );
		}
	}
	
	/**
	 * Leave the conversation
	 *
	 * @return	void
	 */
	protected function leaveConversation() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$ids = Request::i()->id;

			if ( !is_array( Request::i()->id ) )
			{
				$ids = array( $ids );
			}

			foreach ( $ids as $id )
			{
				try
				{
					$conversation = Conversation::loadAndCheckPerms( $id );
					$conversation->deauthorize( Member::loggedIn() );
				}
				catch ( Exception $e ) {}
			}

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array_merge( array( 'result' => 'success' ), $this->_getNewTotals() ) );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ) );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2C137/7', 403, '' );
		}
	}
	
	/**
	 * Enable Messenger
	 *
	 * @return	void
	 */
	protected function enableMessenger() : void
	{
		Session::i()->csrfCheck();
		Member::loggedIn()->members_disable_pm = 0;
		Member::loggedIn()->save();
		Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ), 'messenger_enabled' );
	}
	
	/**
	 * Disable Messenger
	 *
	 * @return	void
	 */
	protected function disableMessenger() : void
	{
		Session::i()->csrfCheck();
		Member::loggedIn()->members_disable_pm = 1;
		Member::loggedIn()->save();
		Output::i()->redirect( Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messaging' ), 'messenger_disabled' );
	}

	/**
	 * Get user conversation storage data
	 *
	 * @return	array
	 */
	protected function _getNewTotals() : array
	{
		/* Get folders */
		$folders = array( 'myconvo'	=> Member::loggedIn()->language()->addToStack('messenger_folder_inbox') );
		if ( Member::loggedIn()->pconversation_filters )
		{
			$folders = array_merge( $folders, json_decode( Member::loggedIn()->pconversation_filters, TRUE ) );
		}

		/* What are our folder counts? */
		$counts = iterator_to_array( Db::i()->select( 'map_folder_id, count(*) as _count', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', Member::loggedIn()->member_id ), NULL, NULL, 'map_folder_id' )->setKeyField( 'map_folder_id' )->setValueField( '_count' ) );
		
		/* Fill in for the empty folders */
		foreach ( $folders as $id => $name )
		{
			if( !isset( $counts[ $id ] ) )
			{
				$counts[ $id ] = 0;
			}
		}

		return array(
			'quotaText'		=> Member::loggedIn()->language()->addToStack( 'messenger_quota', FALSE, array( 'sprintf' => array( Member::loggedIn()->group['g_max_messages'] ), 'pluralize' =>  array( Member::loggedIn()->msg_count_total ) ) ),
			'quotaPercent'	=> Member::loggedIn()->group['g_max_messages'] ? ( ( 100 / Member::loggedIn()->group['g_max_messages'] ) * Member::loggedIn()->msg_count_total ) : 0,
			'totalMessages'	=> Member::loggedIn()->msg_count_total,
			'newMessages'	=> Member::loggedIn()->msg_count_new,
			'counts'		=> $counts
		);
	}

	/**
	 * Remove any messenger filters
	 *
	 * @return void
	 */
	protected function removeAlertFilter() : void
	{
		Alert::clearMessengerFilters();

		Output::i()->redirect( Url::internal('app=core&module=messaging&controller=messenger&overview=1') );
	}
}