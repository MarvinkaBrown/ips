<?php
/**
 * @brief		Topics API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		4 Dec 2015
 */

namespace IPS\forums\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Content\Api\ItemController;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\Db;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
use IPS\Member;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Topics API
 */
class topics extends ItemController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic';
	
	/**
	 * GET /forums/topics
	 * Get list of topics
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only topics the authorized user can view will be included
	 * @apiparam	string	forums			Comma-delimited list of forum IDs
	 * @apiparam	string	ids			    Comma-delimited list of topic IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only topics started by those members are returned
	 * @apiparam	int		hasBestAnswer	If 1, only topics with a best answer are returned, if 0 only without
	 * @apiparam	int		hasPoll			If 1, only topics with a poll are returned, if 0 only without
	 * @apiparam	int		locked			If 1, only topics which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only topics which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		pinned			If 1, only topics which are pinned are returned, if 0 only not pinned
	 * @apiparam	int		featured		If 1, only topics which are featured are returned, if 0 only not featured
	 * @apiparam	int		archived		If 1, only topics which are archived are returned, if 0 only not archived
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title', 'updated' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\forums\Topic>
	 * @return PaginatedResponse<Topic>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Init */
		$where = array();
		
		/* Has best answer */
		if ( isset( Request::i()->hasBestAnswer ) )
		{
			if ( Request::i()->hasBestAnswer )
			{
				$where[] = array( "topic_answered_pid>0" );
			}
			else
			{
				$where[] = array( "topic_answered_pid=0" );
			}
		}
		
		/* Archived */
		if ( isset( Request::i()->archived ) )
		{
			if ( Request::i()->archived )
			{
				$where[] = array( Db::i()->in( 'topic_archive_status', array( Topic::ARCHIVE_DONE, Topic::ARCHIVE_WORKING, Topic::ARCHIVE_RESTORE ) ) );
			}
			else
			{
				$where[] = array( Db::i()->in( 'topic_archive_status', array( Topic::ARCHIVE_NOT, Topic::ARCHIVE_EXCLUDE ) ) );
			}
		}
		
		/* Return */
		return $this->_list( $where, 'forums' );
	}

	/**
	 * GET /forums/topics/{id}
	 * View information about a specific topic
	 *
	 * @param		int		$id				ID Number
	 * @throws		2F294/9	INVALID_ID		The topic ID does not exist
	 * @apireturn		\IPS\forums\Topic
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2F294/9', 404 );
		}
	}
		
	/**
	 * GET /forums/topics/{id}/posts
	 * Get posts in a topic
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only posts which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		1F294/1	INVALID_ID	The topic ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		PaginatedResponse<IPS\forums\Topic\Post>
	 * @return PaginatedResponse<Post>
	 */
	public function GETitem_posts( int $id ): PaginatedResponse
	{
		try
		{
			return $this->_comments( $id, 'IPS\forums\Topic\Post' );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1F294/1', 404 );
		}
	}
	
	/**
	 * Create or update topic
	 *
	 * @param	Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	Item
	 */
	protected function _createOrUpdate( Item $item, string $type='add' ): Item
	{
		/* Open/Close time */
		if ( Request::i()->open_time )
		{
			$item->topic_open_time = ( new \DateTime( Request::i()->open_time ) )->getTimestamp();
		}
		if ( Request::i()->close_time )
		{
			$item->topic_close_time = ( new \DateTime( Request::i()->close_time ) )->getTimestamp();
		}

		/* Do we have a poll to attach? */
		$this->_createOrUpdatePoll( $item, $type );
		
		/* Pass up */
		return parent::_createOrUpdate( $item, $type );
	}
		
	/**
	 * POST /forums/topics
	 * Create a topic
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock topics).
	 * @reqapiparam	int			forum				The ID number of the forum the topic should be created in
	 * @apiparam	int			author				The ID number of the member creating the topic (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		title				The topic title
	 * @reqapiparam	string		post				The post content as HTML (e.g. "<p>This is a post.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	string		prefix				Prefix tag
	 * @apiparam	string		tags				Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime	date				The date/time that should be used for the topic/post post date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string		ip_address			The IP address that should be stored for the topic/post. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int			locked				1/0 indicating if the topic should be locked
	 * @apiparam	datetime	open_time			When the topic should be unlocked from
	 * @apiparam	datetime	close_time			When the topic should be locked from
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int			pinned				1/0 indicating if the topic should be pinned
	 * @apiparam	int			featured			1/0 indicating if the topic should be featured
	 * @apiparam	string		poll_title			Poll title (to create a poll)
	 * @apiparam	int		    poll_public			1/0 indicating if the poll is public
	 * @apiparam	int		    poll_only			1/0 indicating if this a poll-only topic
	 * @apiparam	array		poll_options		Array of objects with keys 'title' (string), 'answers' (array of objects with key 'value' set to the choice) and 'multichoice' (int 1/0)
	 * @throws		1F294/2		NO_FORUM		The forum ID does not exist
	 * @throws		1F294/3		NO_AUTHOR		The author ID does not exist
	 * @throws		1F294/5		NO_TITLE		No title was supplied
	 * @throws		1F294/4		NO_POST			No post was supplied
	 * @throws		2F294/C		NO_PERMISSION	The authorized user does not have permission to create a topic in that forum
	 * @throws		3F294/D		NO_ANON_PERMISSION	The topic is set for anonymous posting, but the author does not have permission to post anonymously
	 * @apireturn		\IPS\forums\Topic
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* Get forum */
		try
		{
			$forum = Forum::load( Request::i()->forum );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'NO_FORUM', '1F294/2', 400 );
		}
		if ( !$forum->sub_can_post )
		{
			throw new Exception( 'NO_PERMISSION', '2F294/C', 403 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$forum->can( 'add', $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F294/C', 403 );
			}
			$author = $this->member;
		}
		else
		{
			if ( Request::i()->author )
			{
				$author = Member::load( Request::i()->author );
				if ( !$author->member_id )
				{
					throw new Exception( 'NO_AUTHOR', '1F294/3', 400 );
				}
			}
			else
			{
				if ( (int) Request::i()->author === 0 )
				{
					$author = new Member;
					$author->name = Request::i()->author_name;
				}
				else 
				{
					throw new Exception( 'NO_AUTHOR', '1F294/3', 400 );
				}
			}
		}

		/* Check anonymous posting */
		if ( isset( Request::i()->anonymous ) and $author->member_id )
		{
			if ( ! $forum->canPostAnonymously( 0, $author ) )
			{
				throw new Exception( 'NO_ANON_PERMISSION', '3F294/D', 403 );
			}
		}
		
		/* Check we have a title and a post */
		if ( !Request::i()->title )
		{
			throw new Exception( 'NO_TITLE', '1F294/5', 400 );
		}
		if ( !Request::i()->post )
		{
			throw new Exception( 'NO_POST', '1F294/4', 400 );
		}
		
		/* Do it */
		return new Response( 201, $this->_create( $forum, $author )->apiOutput( $this->member ) );
	}
	
	/**
	 * POST /forums/topics/{id}
	 * Edit a topic
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock topics).
	 * @apiparam	int			forum				The ID number of the forum the topic should be created in
	 * @apiparam	int			author				The ID number of the member creating the topic (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @apiparam	string		title				The topic title
	 * @apiparam	string		post				The post content as HTML (e.g. "<p>This is a post.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	string		prefix				Prefix tag
	 * @apiparam	string		tags				Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime	date				The date/time that should be used for the topic/post post date. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string		ip_address			The IP address that should be stored for the topic/post. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int			locked				1/0 indicating if the topic should be locked
	 * @apiparam	datetime	open_time			When the topic should be unlocked from
	 * @apiparam	datetime	close_time			When the topic should be locked from
	 * @apiparam	int			hidden				1/0 indicating if the topic should be hidden
	 * @apiparam	int			pinned				1/0 indicating if the topic should be pinned
	 * @apiparam	int			featured			1/0 indicating if the topic should be featured
	 * @apiparam	string		poll_title			Poll title (to create a poll)
	 * @apiparam	int		    poll_public			1/0 indicating if the poll is public
	 * @apiparam	int		    poll_only			1/0 indicating if this a poll-only topic
	 * @apiparam	array		poll_options		Array of objects with keys 'title' (string), 'answers' (array of objects with key 'value' set to the choice) and 'multichoice' (int 1/0)
	 * @param		int		$id				ID Number
	 * @throws		2F294/6		INVALID_ID		The topic ID does not exist or the authorized user does not have permission to view it
	 * @throws		1F294/7		NO_FORUM		The forum ID does not exist or the authorized user does not have permission to post in it
	 * @throws		1F294/8		NO_AUTHOR		The author ID does not exist
	 * @throws		2F294/A		NO_PERMISSION	The authorized user does not have permission to edit the topic
	 * @throws		1F294/E		INVALID_DATE	The date is invalid
	 * @apireturn		\IPS\forums\Topic
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$topic = Topic::load( $id );
			if ( $this->member and !$topic->can( 'read', $this->member ) )
			{
				throw new OutOfRangeException;
			}
			if ( $this->member and !$topic->canEdit( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F294/A', 403 );
			}
			
			/* New forum */
			if ( isset( Request::i()->forum ) and Request::i()->forum != $topic->forum_id and ( !$this->member or $topic->canMove( $this->member ) ) )
			{
				try
				{
					$newForum = Forum::load( Request::i()->forum );
					if ( $this->member and !$newForum->can( 'add', $this->member ) )
					{
						throw new OutOfRangeException;
					}
					
					$topic->move( $newForum );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_FORUM', '1F294/7', 400 );
				}
			}
			
			/* New author */
			if ( !$this->member and isset( Request::i()->author ) )
			{				
				/* Just renaming the guest */
				if ( !$topic->starter_id and ( !isset( Request::i()->author ) or !Request::i()->author ) and isset( Request::i()->author_name ) )
				{
					$topic->starter_name = Request::i()->author_name;
					
					if ( $firstPost = $topic->comments( 1, 0, 'date', 'asc' ) )
					{
						$firstPost->author_name = Request::i()->author_name;
					}
				}
				
				/* Actually changing the author */
				else
				{
					try
					{
						$member = Member::load( Request::i()->author );
						if ( !$member->member_id )
						{
							throw new OutOfRangeException;
						}
						
						$topic->changeAuthor( $member );
					}
					catch ( OutOfRangeException $e )
					{
						throw new Exception( 'NO_AUTHOR', '1F294/8', 400 );
					}
				}
			}
		
			/* Do we have a date? */
			if ( isset( Request::i()->date ) ) {

				try  
				{
					$date = new DateTime( Request::i()->date );
				}
				catch( \Exception $e )
				{
					throw new Exception( 'INVALID_DATE', '1F294/E', 400 );
				}

				/* Do we have a first comment? */ 
				if ( $commentObj = $topic->firstComment() ) 
				{
					try  
					{
						/* @var array $databaseColumnMap */
						$commentClass = $topic::$commentClass;
						$field = $commentClass::$databaseColumnMap['date'];

						$commentObj->$field = $date->getTimestamp();
						$commentObj->save();
					}
					catch( \Exception $e ) {}
				}

			}

			/* Everything else */
			$this->_createOrUpdate( $topic, 'edit' );
		
			/* Save and return */
			$topic->save();
			return new Response( 200, $topic->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2F294/6', 404 );
		}
	}
	
	/**
	 * DELETE /forums/topics/{id}
	 * Delete a topic
	 *
	 * @param		int			$id				ID Number
	 * @throws		1F294/5		INVALID_ID		The topic ID does not exist
	 * @throws		2F294/B		NO_PERMISSION	The authorized user does not have permission to delete the topic
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$item = Topic::load( $id );
			if ( $this->member and !$item->canDelete( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F294/B', 404 );
			}
			
			$item->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1F294/5', 404 );
		}
	}
}