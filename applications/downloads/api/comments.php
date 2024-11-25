<?php
/**
 * @brief		Downloads File Comments API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		10 Dec 2015
 */

namespace IPS\downloads\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Content\Api\CommentController;
use IPS\downloads\File;
use IPS\downloads\File\Comment;
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
 * @brief	Downloads File Comments API
 */
class comments extends CommentController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\downloads\File\Comment';
	
	/**
	 * GET /downloads/comments
	 * Get list of comments
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only comments the authorized user can view will be included
	 * @apiparam	string	categories		Comma-delimited list of category IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only comments started by those members are returned
	 * @apiparam	int		locked			If 1, only comments from events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured		If 1, only comments from  events which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\downloads\File\Comment>
	 * @return PaginatedResponse<Comment>
	 */
	public function GETindex(): PaginatedResponse
	{
		return $this->_list();
	}
	
	/**
	 * GET /downloads/comments/{id}
	 * View information about a specific comment
	 *
	 * @param		int		$id			ID Number
	 * @throws		2D304/1	INVALID_ID	The comment ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\downloads\File\Comment
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			/* @var Comment $class */
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
			throw new Exception( 'INVALID_ID', '2D304/1', 404 );
		}
	}
	
	/**
	 * POST /downloads/comments
	 * Create a comment
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authenticated user has permission to hide content).
	 * @reqapiparam	int			file				The ID number of the file the comment is for
	 * @reqapiparam	int			author				The ID number of the member making the comment (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		content				The comment content as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	datetime	date				The date/time that should be used for the comment date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string		ip_address			The IP address that should be stored for the comment. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	bool		anonymous			If 1, the item will be posted anonymously.
	 * @throws		2D304/2		INVALID_ID	The comment ID does not exist
	 * @throws		1D304/3		NO_AUTHOR	The author ID does not exist
	 * @throws		1D304/4		NO_CONTENT	No content was supplied
	 * @throws		2D304/8		NO_PERMISSION		The authorized user does not have permission to comment on that file
	 * @apireturn		\IPS\downloads\File\Comment
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* Get file */
		try
		{
			$file = File::load( Request::i()->file );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2D304/2', 403 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$file->canComment( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2D304/8', 403 );
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
					throw new Exception( 'NO_AUTHOR', '1D304/3', 404 );
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
					throw new Exception( 'NO_AUTHOR', '1D304/3', 400 );
				}
			}
		}
		
		/* Check we have a post */
		if ( !Request::i()->content )
		{
			throw new Exception( 'NO_CONTENT', '1D304/4', 403 );
		}
		
		/* Do it */
		return $this->_create( $file, $author );
	}
	
	/**
	 * POST /downloads/comments/{id}
	 * Edit a comment
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authenticated user has permission to hide content).
	 * @param		int			$id				ID Number
	 * @apiparam	int			author			The ID number of the member making the comment (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string		author_name		If author is 0, the guest name that should be used
	 * @apiparam	string		content			The comment content as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	int			hidden				1/0 indicating if the comment should be hidden
	 * @apiparam	bool		anonymous			If 1, the item will be posted anonymously.
	 * @throws		2D304/5		INVALID_ID			The comment ID does not exist or the authorized user does not have permission to view it
	 * @throws		1D304/6		NO_AUTHOR			The author ID does not exist
	 * @throws		2D304/9		NO_PERMISSION		The authorized user does not have permission to edit the comment
	 * @apireturn		\IPS\downloads\File\Comment
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			/* Load */
			$comment = Comment::load( $id );
			if ( $this->member and !$comment->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}
			if ( $this->member and !$comment->canEdit( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2D304/9', 403 );
			}
						
			/* Do it */
			try
			{
				return $this->_edit( $comment );
			}
			catch ( InvalidArgumentException $e )
			{
				throw new Exception( 'NO_AUTHOR', '1D304/6', 400 );
			}
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2D304/5', 404 );
		}
	}
		
	/**
	 * DELETE /downloads/comments/{id}
	 * Deletes a comment
	 *
	 * @param		int			$id			ID Number
	 * @throws		2D304/7		INVALID_ID		The comment ID does not exist
	 * @throws		2D304/A		NO_PERMISSION	The authorized user does not have permission to delete the comment
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			/* @var Comment $class */
			$class = $this->class;
			$object = $class::load( $id );
			if ( $this->member and !$object->canDelete( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2D304/A', 403 );
			}
			$object->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2D304/7', 404 );
		}
	}

	/**
	 * POST /downloads/comments/{id}/react
	 * Add a reaction
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		id			ID of the reaction to add
	 * @apiparam	int     author      ID of the member reacting
	 * @apireturn		\IPS\downloads\File\Comment
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
	 * DELETE /downloads/comments/{id}/react
	 * Delete a reaction
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int     author      ID of the member who reacted
	 * @apireturn		\IPS\downloads\File\Comment
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
	 * POST /downloads/comments/{id}/report
	 * Reports a comment
	 *
	 * @param       int         $id             ID Number
	 * @apiparam	int			author			ID of the member reporting
	 * @apiparam	int			report_type		Report type (0 is default and is for letting CMGR team know, more options via core_automatic_moderation_types)
	 * @apiparam	string		message			Optional message
	 * @throws		1S425/B		NO_AUTHOR			The author ID does not exist
	 * @throws		1S425/C		REPORTED_ALREADY	The member has reported this item in the past 24 hours
	 * @apireturn		\IPS\downloads\File\Comment
	 * @return Response
	 */
	public function POSTitem_report( int $id ): Response
	{
		return $this->_report( $id );
	}
}