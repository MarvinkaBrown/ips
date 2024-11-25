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

use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Reaction;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\NotificationsAbstract;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Notification\Inline;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class MyStuff extends NotificationsAbstract
{	
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( ?Member $member = NULL ): array
	{
		return array(
			'core_MyStuff' => array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'quote', 'mention', 'embed', 'my_solution', 'mine_solved', 'approved_content' ),
				'title'				=> 'notifications__core_MyStuff',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_MyStuff_desc',
				'default'			=> array( 'inline' ),
				'disabled'			=> array()
			),
			'core_Reactions' => array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'new_likes' ),
				'title'				=> 'notifications__core_Likes',
				'showTitle'			=> TRUE,
				'description'		=> 'notifications__core_Likes_desc',
				'default'			=> array( 'inline' ),
				'disabled'			=> array()
			),
		);
	}
	
	/**
	 * Parse notification: mention
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
	public function parse_mention( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = ( $notification->item instanceof Comment ) ? $notification->item->item() : $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}
		
		$comment = $notification->item_sub ?: $item;
		if ( !$comment )
		{
			throw new OutOfRangeException;
		}
		
		$quoters	= $this->_getNamesFromExtra( $notification, $comment );
		$count		= count( $quoters );
		$quoters	= Member::loggedIn()->language()->formatList( $quoters );
		
		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__new_mention', FALSE, array(
				'pluralize'									=> array( $count ),
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $quoters, mb_strtolower( $item->indefiniteArticle() ), $item->mapped('title') )
			) ),
			'url'		=> $comment instanceof Item ? $item->url() : $comment->url('find'),
			'content'	=> $comment->truncated(),
			'author'	=> $comment->author(),
			'unread'	=> (bool) ( $item->unread() ),
		);
	}
	
	/**
	 * Parse notification for mobile: mention
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content		$content			The content
	 * @return	array
	 */
	public static function parse_mobile_mention( Lang $language, Content $content ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		$idField = $item::$databaseColumnId;

		return array(
			'title'			=> $language->addToStack( 'notification__new_mention_title', FALSE, array(
				'pluralize'		=> array(1),
				'htmlsprintf'	=> array(
					mb_strtolower( $item->indefiniteArticle( $language ) ),
				)
			) ),
			'body'			=> $language->addToStack( 'notification__new_mention', FALSE, array(
				'pluralize'		=> array( 1 ),
				'htmlsprintf'	=> array(
					$language->formatList( array( $content->author()->name ) ),
					mb_strtolower( $content->indefiniteArticle( $language ) ),
					$item->mapped('title')
				)
			) ),
			'data'	=> array(
				'url'		=> (string) $content->url(),
				'author'	=> $content->author(),
				'grouped'	=> $language->addToStack( 'notification__new_mention_grouped', FALSE, array(
					'htmlsprintf'	=> array(
						mb_strtolower( $item->indefiniteArticle( $language ) ),
						$item->mapped('title')
					)
				) ),
				'groupedTitle' => $language->addToStack( 'notification__new_mention_title', FALSE, array(
					// Pluralized on the client
					'htmlsprintf'	=> array(
						mb_strtolower( $item->indefiniteArticle( $language ) ),
					)
				) ),
				// No need for groupedUrl - just go to the most recent mention
			),
			'tag' => md5( 'mention' . get_class( $item ) . $item->$idField ), // Group mention notifications by content item
			'channelId'	=> 'my-content',
		);
	}
	
	/**
	 * Parse notification: quote
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
	public function parse_quote( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = ( $notification->item instanceof Comment ) ? $notification->item->item() : $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}
		
		$comment = $notification->item_sub ?: $item;
		if ( !$comment )
		{
			throw new OutOfRangeException;
		}
		
		$quoters	= $this->_getNamesFromExtra( $notification, $comment );
		$count		= count( $quoters );
		$quoters	= Member::loggedIn()->language()->formatList( $quoters );
		
		return array(
				'title'		=> Member::loggedIn()->language()->addToStack( 'notification__new_quote', FALSE, array(
					'pluralize' 								=> array( $count ),
					( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $quoters, mb_strtolower( $item->indefiniteArticle() ), $item->mapped('title') )
				) ),
				'url'		=> $comment instanceof Item ? $item->url() : $comment->url('find'),
				'content'	=> $comment->truncated(),
				'author'	=> $comment->author(),
				'unread'	=> (bool) ( $item->unread() ),
		);
	}
	
	/**
	 * Parse notification for mobile: quote
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content		$content			The content
	 * @return	array
	 */
	public static function parse_mobile_quote( Lang $language, Content $content ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		$idField = $item::$databaseColumnId;

		return array(
			'title'			=> $language->addToStack( 'notification__new_quote_title', FALSE, array(
				'htmlsprintf'	=> array(
					mb_strtolower( $item->indefiniteArticle( $language ) ),
				)
			) ),
			'body'		=> $language->addToStack( 'notification__new_quote', FALSE, array(
				'pluralize'		=> array( 1 ),
				'htmlsprintf'	=> array(
					$language->formatList( array( $content->author()->name ) ),
					mb_strtolower( $content->indefiniteArticle( $language ) ),
					$item->mapped('title')
				)
			) ),
			'data'		=> array(
				'url'		=> (string) $content->url(),
				'author'	=> $content->author(),
				'grouped'	=> $language->addToStack( 'notification__new_quote_grouped', FALSE, array(
					'htmlsprintf'	=> array(
						mb_strtolower( $item->indefiniteArticle( $language ) ),
						$item->mapped('title')
					)
				) ),
				// No need for groupedUrl - just go to the most recent quote
			),
			'tag' => md5( 'quote' . get_class( $item ) . $item->$idField ), // Group quote notifications by content item
			'channelId'	=> 'my-content',
		);
	}
	
	/**
	 * Parse notification: new_likes
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
	public function parse_new_likes( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$comment = $notification->item;
		
		if ( !$comment )
		{
			throw new OutOfRangeException;
		}

		$item = ( $comment instanceof Item ) ? $comment : $comment->item();
		$idColumn = $comment::$databaseColumnId;
		
		$between = time();
		try
		{
			/* Is there a newer notification for this item? */
			$between = Db::i()->select( 'sent_time', 'core_notifications', array( '`member`=? AND item_id=? AND item_class=? AND sent_time>? AND notification_key=?', Member::loggedIn()->member_id, $comment->$idColumn, get_class( $comment ), $notification->sent_time->getTimestamp(), $notification->notification_key ) )->first();
		}
		catch( UnderflowException $e ) {}
		
		$likers = Db::i()->select( 'DISTINCT member_id, rep_date', 'core_reputation_index', array( 'app=? AND type=? AND rep_date>=? AND rep_date<? AND type_id=?', $comment::$application, $comment::reactionType(), $notification->sent_time->getTimestamp(), $between, $comment->$idColumn ), 'rep_date desc' );

		$names	= array();
		$first	= array();
		foreach( $likers AS $member )
		{
			if( empty( $first ) )
			{
				$first = $member;
			}

			if ( count( $names ) > 2 )
			{
				$names[] = Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( count( $likers ) - 3 ) ) );
				break;
			}
			$names[] = Member::load( $member['member_id'] )->name;
		}

		if( empty( $first ) )
		{
			throw new OutOfRangeException;
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( Reaction::isLikeMode() ? 'notification__new_likes' : 'notification__new_react', FALSE, array(
				'pluralize' 								=> array( count( $likers ) ),
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array(
					( Member::loggedIn()->group['gbw_view_reps'] ) ? Member::loggedIn()->language()->formatList( $names ) : Member::loggedIn()->language()->pluralize( Member::loggedIn()->language()->get( Reaction::isLikeMode() ? 'notifications_user_count_like' : 'notifications_user_count_react' ), array( count( $likers ) ) ),
					mb_strtolower( $comment->indefiniteArticle() ) 
				)
			) ) . ' ' . $item->mapped('title'),
			'url'		=> ( $comment instanceof Comment ) ? $comment->url('find') : $comment->url(),
			'content'	=> $comment->truncated(),
			'author'	=> ( Member::loggedIn()->group['gbw_view_reps'] ) ? Member::load( $first['member_id'] ) : new Member
		);
	}

	/**
	 * Parse notification: parse_rest_new_likes
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
	public function parse_rest_new_likes( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$comment = $notification->item;

		if ( !$comment )
		{
			throw new OutOfRangeException;
		}

		$item = ( $comment instanceof Item ) ? $comment : $comment->item();
		$idColumn = $comment::$databaseColumnId;

		$between = time();
		try
		{
			/* Is there a newer notification for this item? */
			$between = Db::i()->select( 'sent_time', 'core_notifications', array( '`member`=? AND item_id=? AND item_class=? AND sent_time>? AND notification_key=?', Member::loggedIn()->member_id, $comment->$idColumn, get_class( $comment ), $notification->sent_time->getTimestamp(), $notification->notification_key ) )->first();
		}
		catch( UnderflowException $e ) {}

		$likers = Db::i()->select( 'DISTINCT member_id, rep_date', 'core_reputation_index', array( 'app=? AND type=? AND rep_date>=? AND rep_date<? AND type_id=?', $comment::$application, $comment::reactionType(), $notification->sent_time->getTimestamp(), $between, $comment->$idColumn ), 'rep_date desc' );

		$names	= array();
		$first	= array();
		foreach( $likers AS $member )
		{
			if( empty( $first ) )
			{
				$first = $member;
			}

			if ( count( $names ) > 2 )
			{
				$names[] = Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( count( $likers ) - 3 ) ) );
				break;
			}
			$names[] = Member::load( $member['member_id'] )->name;
		}

		if( empty( $first ) )
		{
			throw new OutOfRangeException;
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( Reaction::isLikeMode() ? 'notification__new_likes' : 'notification__new_react', FALSE, array(
					'pluralize' 								=> array( count( $likers ) ),
					( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array(
						Member::loggedIn()->language()->formatList( $names ),
						mb_strtolower( $comment->indefiniteArticle() )
					)
				) ) . ' ' . $item->mapped('title'),
			'url'		=> ( $comment instanceof Comment ) ? $comment->url('find') : $comment->url(),
			'content'	=> $comment->truncated(),
			'author'	=>  Member::load( $first['member_id'] )
		);
	}

	/**
	 * Parse notification for mobile: new_likes
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content		$content			The content
	 * @param	Member		$liker			The member reacting to the content
	 * @return	array
	 */
	public static function parse_mobile_new_likes( Lang $language, Content $content, Member $liker ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		$idField = $item::$databaseColumnId;
		$lang = Reaction::isLikeMode() ? 'notification__new_likes' : 'notification__new_react';

		return array(
			'title'			=> $language->addToStack( $lang . '_title', FALSE, array(
				'pluralize'		=> array(1),
				'htmlsprintf'	=> array(
					mb_strtolower( $item->indefiniteArticle( $language ) )
				)
			) ),
			'body'			=> $language->addToStack( $lang, FALSE, array(
				'pluralize'		=> array( 1 ),
				'htmlsprintf'	=> array(
					( $content->author()->group['gbw_view_reps'] ) ? $language->formatList( array( $liker->name ) ) : $language->pluralize(
						$language->get( Reaction::isLikeMode() ? 'notifications_user_count_like' : 'notifications_user_count_react' ),
						array( 1 )
					),
					mb_strtolower( $content->indefiniteArticle( $language ) )
				)
			) ) . ' ' . $item->mapped('title'),
			'data'		=> array(
				'url'		=> (string) $content->url(),
				'author'	=> $liker,
				'grouped'	=> $language->addToStack( $lang . '_grouped', FALSE, array(
					'htmlsprintf'	=> array(
						mb_strtolower( $content->indefiniteArticle( $language ) )
					)
				) ),
				'groupedTitle'	=> $language->addToStack( $lang . '_title', FALSE, array(
					// Pluralized on the client
					'htmlsprintf'	=> array(
						mb_strtolower( $item->indefiniteArticle( $language ) )
					)
				) ),
				// No need for groupedUrl - just go to the most recent thing
			),
			'tag' => md5( 'likes' . get_class( $item ) . $item->$idField ), // Group quote notifications by content item
			'channelId'	=> 'my-content',
		);
	}

	/**
	 * Parse notification: embed
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
	public function parse_embed( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = ( $notification->item instanceof Comment ) ? $notification->item->item() : $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}

		$comment = $notification->item_sub ?: $item;
		if ( !$comment )
		{
			throw new OutOfRangeException;
		}

		$embeds	= $this->_getNamesFromExtra( $notification, $comment );
		$count	= count( $embeds );
		$embeds	= Member::loggedIn()->language()->formatList( $embeds );

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__new_embed', FALSE, array( 'pluralize' => array( $count ), ( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( $embeds, mb_strtolower( $item->indefiniteArticle() ), $item->mapped('title') ) ) ),
			'url'		=> $comment instanceof Item ? $item->url() : $comment->url('find'),
			'content'	=> $comment->truncated(),
			'author'	=> $comment->author(),
			'unread'	=> (bool) ( $item->unread() ),
		);
	}
	
	/**
	 * Parse notification for mobile: embed
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content		$content			The content that was posted (with the embed in it)
	 * @return	array
	 */
	public static function parse_mobile_embed( Lang $language, Content $content ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		$idField = $item::$databaseColumnId;

		return array(
			'title'			=> $language->addToStack( 'notification__new_embed_title' ),
			'body'		=> $language->addToStack( 'notification__new_embed', FALSE, array(
				'pluralize'		=> array( 1 ),
				'htmlsprintf'	=> array(
					$language->formatList( array( $content->author()->name ) ),
					mb_strtolower( $content->indefiniteArticle( $language ) ),
					$item->mapped('title')
				)
			) ),
			'data'		=> array(
				'url'		=> (string) $content->url(),
				'author'	=> $content->author(),
				'grouped'	=> $language->addToStack( 'notification__new_embed_grouped', FALSE, array(
					'htmlsprintf'	=> array(
						mb_strtolower( $item->indefiniteArticle( $language ) ),
						$item->mapped('title')
					)
				) ),
				// No need for groupedUrl - just go to the most recent thing
			),
			'tag' => md5( 'embed' . get_class( $item ) . $item->$idField ), // Group embed notifications by content item
			'channelId'	=> 'my-content',
		);
	}

	/**
	 * Parse notification: mine_solved
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
		 'title'		=> "Mark has replied to A Topic",	// The notification title
		 'url'			=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
		 'content'		=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
		 													// 	 explains what the notification is about - just include any appropriate content.
		 													// 	 For example, if the notification is about a post, set this as the body of the post.
		 'author'		=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_mine_solved( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}

		$commentClass = $item::$commentClass;
		$comment = $commentClass::loadAndCheckPerms( $notification->item_sub_id );

		$name = ( IPS::classUsesTrait( $comment, 'IPS\Content\Anonymous' ) AND $comment->isAnonymous() ) ? Member::loggedIn()->language()->addToStack( 'post_anonymously_placename' ) : $comment->author()->name;

		/* Unread? */
		$unread = false;
		if ( $item->timeLastRead() instanceof DateTime )
		{
			$unread = ( $item->timeLastRead()->getTimestamp() < $notification->updated_time->getTimestamp() );
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__minesolved', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array(
					$name,
					mb_strtolower( $item->definiteArticle() ),
					$item->mapped('title')
				)
			) ),
			'url'		=> $comment->url('find'),
			'content'	=> $comment->content(),
			'author'	=> $comment->author(),
			'unread'	=> $unread
		);
	}

	/**
	 * Parse notification for mobile: mine_solved
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content	$content		The content
	 * @return	array
	 */
	public static function parse_mobile_mine_solved( Lang $language, Content $content ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		$name = ( IPS::classUsesTrait( $content, 'IPS\Content\Anonymlous' ) AND $content->isAnonymous() ) ? Member::loggedIn()->language()->addToStack( 'post_anonymously_placename' ) : $content->author()->name;
		
		return array(
			'title'			=> $language->addToStack( 'notification__minesolved_title', FALSE, array(
				'htmlsprintf'	=> array(
					mb_strtolower( $item->definiteArticle() ),
				)
			) ),
			'body'			=> $language->addToStack( 'notification__minesolved', FALSE, array(
				'htmlsprintf'	=> array(
					$name,
					mb_strtolower( $item->definiteArticle() ),
					$item->mapped('title')
				)
			) ),
			'data'		=> array(
				'url'		=> (string) ( $content instanceof Comment ) ? $content->url('find') : $content->url(),
				'author'	=> $content->author()
			),
			'channelId'	=> 'my-content',
		);
	}

	/**
	 * Parse notification: my_solution
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 return array(
		 'title'		=> "Mark has replied to A Topic",	// The notification title
		 'url'			=> \IPS\Http\Url::internal( ... ),	// The URL the notification should link to
		 'content'		=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
		 													// 	 explains what the notification is about - just include any appropriate content.
		 													// 	 For example, if the notification is about a post, set this as the body of the post.
		 'author'		=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_my_solution( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}

		$commentClass = $item::$commentClass;
		$comment = $commentClass::loadAndCheckPerms( $notification->item_sub_id );

		/* Unread? */
		$unread = false;
		if ( $item->timeLastRead() instanceof DateTime )
		{
			$unread = ( $item->timeLastRead()->getTimestamp() < $notification->updated_time->getTimestamp() );
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__mysolution', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array(
					$item->mapped('title')
				)
			) ),
			'url'		=> $comment->url('find'),
			'content'	=> $comment->content(),
			'author'	=> $comment->author(),
			'unread'	=> $unread
		);
	}

	/**
	 * Parse notification for mobile: my_solution
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content	$content		The content
	 * @return	array
	 */
	public static function parse_mobile_my_solution( Lang $language, Content $content ) : array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();
		
		return array(
			'title'			=> $language->addToStack( 'notification__mysolution_title', FALSE, array(
				'htmlsprintf'	=> array(
					mb_strtolower( $item->indefiniteArticle() ),
				)
			) ),
			'body'			=> $language->addToStack( 'notification__mysolution', FALSE, array(
				'htmlsprintf'	=> array(
					$item->mapped('title')
				)
			) ),
			'data'		=> array(
				'url'		=> (string) ( $content instanceof Comment ) ? $content->url('find') : $content->url(),
				'author'	=> $content->author()
			),
			'channelId'	=> 'my-content',
		);
	}

	/**
	 * Parse notification: approved
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
	public function parse_approved_content( Inline $notification, bool $htmlEscape=TRUE ) : array
	{
		$item = ( $notification->item instanceof Comment ) ? $notification->item->item() : $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}

		$comment = $notification->item_sub ?: $item;
		if ( !$comment )
		{
			throw new OutOfRangeException;
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__approved_content', FALSE, array( ( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( mb_strtolower( $comment->definiteArticle() ), $item->mapped('title') ) ) ),
			'url'		=> $comment instanceof Item ? $item->url() : $comment->url('find'),
			'content'	=> $comment->truncated(),
			'author'	=> $comment->author(),
			'unread'	=> (bool) ( $item->unread() ),
		);
	}

	/**
	 * Parse notification for mobile: approved
	 *
	 * @param	Lang		$language		The language that the notification should be in
	 * @param	Content	$content		The content
	 * @return	array
	 */
	public static function parse_mobile_approved_content( Lang $language, Content $content ): array
	{
		$item = ( $content instanceof Item ) ? $content : $content->item();

		return array(
			'title'			=> $language->addToStack( 'notification__approved_content_title' ),
			'body'			=> $language->addToStack( 'notification__approved_content', FALSE, array(
				'htmlsprintf'	=> array(
					mb_strtolower( $content->definiteArticle( $language ) ),
					$item->mapped('title')
				)
			) ),
			'data'		=> array(
				'url'		=> (string) $content->url(),
				'author'	=> $content->author(),
				'grouped'	=> $language->addToStack( 'notification__approved_content_grouped', FALSE, array(
					'htmlsprintf'	=> array(
						mb_strtolower( $content->definiteArticle( $language, TRUE ) )
					)
				) ),
				'groupedTitle'	=> $language->addToStack( 'notification__approved_content_title', FALSE )
			),
			'tag' => md5( 'approved' . get_class( $item ) . $item::$title ), // Group notification by type
			'channelId'	=> 'my-content',
		);
	}

	/**
	 * Get the members from the notification extra data
	 *
	 * @param	Inline	$notification	The notification
	 * @param	Comment		$commentOrItem		The comment/Item
	 * @return	array
	 */
	protected function _getNamesFromExtra( Inline $notification, Item|Comment $commentOrItem ) : array
	{
		$members = array();

		if ( $notification->extra )
		{
			$memberIds = array_unique( $notification->extra );
			$andXOthers = NULL;
			if ( count( $memberIds ) > 3 )
			{
				$andXOthers = count( $memberIds ) - 2;
				array_splice( $memberIds, 2 );
			}

			$members = iterator_to_array( Db::i()->select( 'name', 'core_members', Db::i()->in( 'member_id', $memberIds ) ) );
			if ( $andXOthers )
			{
				$members[] = Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( $andXOthers ) ) );
			}

			/* If we don't have any quoters, it was a guest or a member who no longer exists */
			if( !count( $members ) )
			{
				$members = array($commentOrItem->author()->name );
			}
		}
		else
		{
			$members = array($commentOrItem->author()->name );
		}

		return $members;
	}
}