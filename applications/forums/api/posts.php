<?php
/**
 * @brief		Posts API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		7 Dec 2015
 */

namespace IPS\forums\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Content\Api\CommentController;
use IPS\Db;
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
 * @brief	Posts API
 */
class posts extends CommentController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic\Post';
	
	/**
	 * GET /forums/posts
	 * Get list of posts
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only posts the authorized user can view will be included
	 * @apiparam	string	forums			Comma-delimited list of forum IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only posts by those members are returned
	 * @apiparam	int		hasBestAnswer	If 1, only posts from topics with a best answer are returned, if 0 only without
	 * @apiparam	int		hasPoll			If 1, only posts from  topics with a poll are returned, if 0 only without
	 * @apiparam	int		locked			If 1, only posts from  topics which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only posts which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		pinned			If 1, only posts from  topics which are pinned are returned, if 0 only not pinned
	 * @apiparam	int		featured		If 1, only posts from  topics which are featured are returned, if 0 only not featured
	 * @apiparam	int		archived		If 1, only posts from  topics which are archived are returned, if 0 only not archived
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\forums\Topic\Post>
	 * @return PaginatedResponse<Post>
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
	 * GET /forums/posts/{id}
	 * View information about a specific post
	 *
	 * @param		int		$id			ID Number
	 * @throws		1F295/4	INVALID_ID	The post ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\forums\Topic\Post
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			/* @var Post $class */
			$class = $this->class;
			if ( $this->member )
			{
				$object = $class::loadAndCheckPerms( $id, $this->member );
			}
			else
			{
				$object = $class::load( $id );
			}
			
			return new Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1F295/4', 404 );
		}
	}

	/**
	 * POST /forums/posts/{id}/react
	 * Add a reaction
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		id			ID of the reaction to add
	 * @apiparam	int     author      ID of the member reacting
	 * @apireturn		\IPS\forums\Topic\Post
	 * @throws		1S425/2		NO_REACTION	The reaction ID does not exist
	 * @throws		1S425/3		NO_AUTHOR	The author ID does not exist
	 * @throws		1S425/4		REACT_ERROR	Error adding the reaction
	 * @throws		1S425/5		INVALID_ID	Object ID does not exist
	 * @note		If the author has already reacted to this content, any existing reaction will be removed first
	 * @return Response
	 */
	public function POSTitem_react( int $id ): Response
	{
		return $this->_reactAdd( $id );
	}

	/**
	 * DELETE /forums/posts/{id}/react
	 * Delete a reaction
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int     author      ID of the member who reacted
	 * @apireturn		\IPS\forums\Topic\Post
	 * @throws		1S425/6		NO_AUTHOR	The author ID does not exist
	 * @throws		1S425/7		REACT_ERROR	Error adding the reaction
	 * @throws		1S425/8		INVALID_ID	Object ID does not exist
	 * @note		If the author has already reacted to this content, any existing reaction will be removed first
	 * @return Response
	 */
	public function DELETEitem_react( int $id ): Response
	{
		return $this->_reactRemove( $id );
	}

	/**
	 * POST /forums/posts
	 * Create a post
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authenticated user has permission to hide content).
	 * @reqapiparam	int			topic				The ID number of the topic the post should be created in
	 * @reqapiparam	int			author				The ID number of the member making the post (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		post				The post content as HTML (e.g. "<p>This is a post.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	datetime	date				The date/time that should be used for the topic/post post date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string		ip_address			The IP address that should be stored for the topic/post. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	bool		anonymous		If 1, the item will be posted anonymously.
	 * @throws		1F295/1		NO_TOPIC	The topic ID does not exist
	 * @throws		1F295/2		NO_AUTHOR	The author ID does not exist
	 * @throws		1F295/3		NO_POST		No post was supplied
	 * @throws		2F294/A		NO_PERMISSION	The authorized user does not have permission to reply to that topic
	 * @throws		3F295/C		NO_ANON_PERMISSION	The topic is set for anonymous posting, but the author does not have permission to post anonymously
	 * @apireturn		\IPS\forums\Topic\Post
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* Get topic */
		try
		{
			$topic = Topic::load( Request::i()->topic );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'NO_TOPIC', '1F295/1', 403 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$topic->canComment( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F294/A', 403 );
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
					throw new Exception( 'NO_AUTHOR', '1F295/2', 403 );
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
					throw new Exception( 'NO_AUTHOR', '1F295/2', 400 );
				}
			}
		}

		/* Check anonymous posting */
		if ( isset( Request::i()->anonymous ) and $author->member_id )
		{
			if ( ! $topic->container()->canPostAnonymously( 0, $author ) )
			{
				throw new Exception( 'NO_ANON_PERMISSION', '3F295/C', 403 );
			}
		}
		
		/* Check we have a post */
		if ( !Request::i()->post )
		{
			throw new Exception( 'NO_POST', '1F295/3', 403 );
		}
		
		/* Do it */
		return $this->_create( $topic, $author, 'post' );
	}
	
	/**
	 * POST /forums/posts/{id}
	 * Edit a post
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authenticated user has permission to hide content).
	 * @param		int			$id				ID Number
	 * @apiparam	int			author			The ID number of the member making the post (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string		author_name		If author is 0, the guest name that should be used
	 * @apiparam	string		post			The post content as HTML (e.g. "<p>This is a post.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	int			hidden			1/0 indicating if the topic should be hidden
	 * @apiparam	bool		anonymous		If 1, the item will be posted anonymously.
	 * @throws		2F295/6		INVALID_ID					The post ID does not exist or the authorized user does not have permission to view it
	 * @throws		2F295/7		NO_AUTHOR					The author ID does not exist
	 * @throws		1F295/8		CANNOT_HIDE_FIRST_POST		You cannot hide or unhide the first post in a topic. Hide/unhide the topic itself instead.
	 * @throws		1F295/9		CANNOT_AUTHOR_FIRST_POST	You cannot change the author for the first post in a topic. Change the author on the topic itself instead.
	 * @throws		2F295/A		NO_PERMISSION				The authorized user does not have permission to edit the post
	 * @apireturn		\IPS\forums\Topic\Post
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			/* Load */
			$post = Post::load( $id );
			if ( $this->member and !$post->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}
			if ( $this->member and !$post->canEdit( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F295/A', 403 );
			}
			
			/* Check */
			if ( $post->isFirst() )
			{
				if ( isset( Request::i()->hidden ) )
				{
					throw new Exception( 'CANNOT_HIDE_FIRST_POST', '1F295/8', 403 );
				}
				if ( isset( Request::i()->author ) )
				{
					throw new Exception( 'CANNOT_AUTHOR_FIRST_POST', '1F295/9', 403 );
				}
			}
			
			/* Do it */
			try
			{
				return $this->_edit( $post, 'post' );
			}
			catch ( InvalidArgumentException $e )
			{
				throw new Exception( 'NO_AUTHOR', '2F295/7', 400 );
			}
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2F295/6', 404 );
		}
	}
		
	/**
	 * DELETE /forums/posts/{id}
	 * Deletes a post
	 *
	 * @param		int			$id							ID Number
	 * @throws		1F295/5		INVALID_ID					The post ID does not exist
	 * @throws		1F295/B		CANNOT_DELETE_FIRST_POST	You cannot delete the first post in a topic. Delete the topic itself instead.
	 * @throws		2F295/B		NO_PERMISSION				The authorized user does not have permission to delete the post
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			/* @var Post $class */
			$class = $this->class;
			$object = $class::load( $id );
			if ( $object->isFirst() )
			{
				throw new Exception( 'CANNOT_DELETE_FIRST_POST', '1F295/B', 403 );
			}
			if ( $this->member and !$object->canDelete( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F295/B', 403 );
			}
			$object->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1F295/5', 404 );
		}
	}

	/**
	 * POST /forums/posts/{id}/report
	 * Reports a post
	 *
	 * @param       int         $id             ID Number
	 * @apiparam	int			author			ID of the member reporting
	 * @apiparam	int			report_type		Report type (0 is default and is for letting CMGR team know, more options via core_automatic_moderation_types)
	 * @apiparam	string		message			Optional message
	 * @throws		1S425/B		NO_AUTHOR			The author ID does not exist
	 * @throws		1S425/C		REPORTED_ALREADY	The member has reported this item in the past 24 hours
	 * @apireturn		\IPS\forums\Topic\Post
	 * @return Response
	 */
	public function POSTitem_report( int $id ): Response
	{
		return $this->_report( $id );
	}
}