<?php
/**
 * @brief		Blog Entries API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		9 Dec 2015
 */

namespace IPS\blog\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\blog\Blog;
use IPS\blog\Entry;
use IPS\blog\Entry\Comment;
use IPS\Content\Api\ItemController;
use IPS\Content\Item;
use IPS\Member;
use IPS\Request;
use IPS\Text\Parser;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Blog Entries API
 */
class entries extends ItemController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\blog\Entry';
	
	/**
	 * GET /blog/entries
	 * Get list of entries
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only entries that are published and in blogs that are not disabled and not belonging to a particular club or social group will be included
	 * @apiparam	string	ids			    Comma-delimited list of entry IDs
	 * @apiparam	string	blogs			Comma-delimited list of blog IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only entries started by those members are returned
	 * @apiparam	int		locked			If 1, only entries which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only entries which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		pinned			If 1, only entries which are pinned are returned, if 0 only not pinned
	 * @apiparam	int		featured		If 1, only entries which are featured are returned, if 0 only not featured
	 * @apiparam	int		draft			If 1, only draft entries are returned, if 0 only published
	 * @apiparam	string	sortBy			What to sort by. Can be 'date' for creation date, 'title', 'updated' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\blog\Entry>
	 * @return PaginatedResponse<Entry>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();
		
		/* Permission */
		if ( $this->member )
		{
			$where[] = array( 'entry_status=? AND blog_disabled=0 AND blog_social_group IS NULL AND blog_club_id IS NULL', 'published' );
		}
		elseif ( isset( Request::i()->draft ) )
		{
			$where[] = array( 'entry_status=?', Request::i()->draft ? 'draft' : 'published' );
		}
				
		/* Return */
		return $this->_list( $where, 'blogs' );
	}
	
	/**
	 * GET /blog/entries/{id}
	 * View information about a specific blog entry
	 *
	 * @param		int		$id				ID Number
	 * @throws		2B300/A	INVALID_ID		The entry ID does not exist
	 * @apireturn		\IPS\blog\Entry
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
			throw new Exception( 'INVALID_ID', '2B300/A', 404 );
		}
	}

	/**
	 * GET /blog/entries/{id}/comments
	 * View comments on an entry
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @throws		2B300/1	INVALID_ID	The entry ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		PaginatedResponse<IPS\blog\Entry\Comment>
	 * @return PaginatedResponse<Comment>
	 */
	public function GETitem_comments( int $id ): PaginatedResponse
	{
		try
		{
			return $this->_comments( $id, 'IPS\blog\Entry\Comment' );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2B300/1', 404 );
		}
	}
	
	/**
	 * POST /blog/entries
	 * Create an entry
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock topics).
	 * @reqapiparam	int					blog			The ID number of the blog the entry should be created in
	 * @reqapiparam	int					author			The ID number of the member creating the entry (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @reqapiparam	string				title			The entry title
	 * @reqapiparam	string				entry			The entry content as HTML (e.g. "<p>This is a blog entry.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	bool				draft			If this is a draft
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date			The date/time that should be used for the entry date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string				ip_address		The IP address that should be stored for the entry/post. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int					locked			1/0 indicating if the entry should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned			1/0 indicating if the entry should be pinned
	 * @apiparam	int					featured		1/0 indicating if the entry should be featured
	 * @apiparam	int					category		The blog entry category
	 * @apiparam	string				poll_title		Poll title (to create a poll)
	 * @apiparam	int		            poll_public		1/0 indicating if the poll is public
	 * @apiparam	int		            poll_only		1/0 indicating if this a poll-only topic
	 * @apiparam	array		        poll_options	Array of objects with keys 'title' (string), 'answers' (array of objects with key 'value' set to the choice) and 'multichoice' (int 1/0)
	 * @apiparam	bool				anonymous		If 1, the item will be posted anonymously.
	 * @throws		1B300/2				NO_BLOG			The blog ID does not exist
	 * @throws		1B300/3				NO_AUTHOR		The author ID does not exist
	 * @throws		1B300/4				NO_TITLE		No title was supplied
	 * @throws		1B300/5				NO_CONTENT		No content was supplied
	 * @throws		1B300/A				NO_PERMISSION	The authorized user does not have permission to create an entry in that blog
	 * @apireturn		\IPS\blog\Entry
	 * @return Response
	 */
	public function POSTindex(): Response
	{		
		/* Get blog */
		try
		{
			$blog = Blog::load( Request::i()->blog );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'NO_BLOG', '1B300/2', 400 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$blog->can( 'add', $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '1B300/A', 403 );
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
					throw new Exception( 'NO_AUTHOR', '1B300/3', 400 );
				}
			}
			else
			{
				if ( (int) Request::i()->author === 0 )
				{
					$author = new Member;
				}
				else 
				{
					throw new Exception( 'NO_AUTHOR', '1B300/3', 400 );
				}
			}
		}
		
		/* Check we have a title and a description */
		if ( !Request::i()->title )
		{
			throw new Exception( 'NO_TITLE', '1B300/4', 400 );
		}
		if ( !Request::i()->entry )
		{
			throw new Exception( 'NO_CONTENT', '1B300/5', 400 );
		}
		
		/* Do it */
		return new Response( 201, $this->_create( $blog, $author )->apiOutput( $this->member ) );
	}
	
	/**
	 * POST /blog/entries/{id}
	 * Edit a blog entry
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock topics).
	 * @reqapiparam	int					blog			The ID number of the blog the entry should be created in
	 * @reqapiparam	int					author			The ID number of the member creating the entry (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @reqapiparam	string				title			The entry title
	 * @reqapiparam	string				entry			The entry content as HTML (e.g. "<p>This is a blog entry.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @apiparam	bool				draft			If this is a draft
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	string				ip_address		The IP address that should be stored for the entry/post. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked			1/0 indicating if the entry should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned			1/0 indicating if the entry should be pinned
	 * @apiparam	int					featured		1/0 indicating if the entry should be featured
	 * @apiparam	int					category		The blog entry category
	 * @apiparam	string				poll_title		Poll title (to create a poll)
	 * @apiparam	int		            poll_public		1/0 indicating if the poll is public
	 * @apiparam	int		            poll_only		1/0 indicating if this a poll-only topic
	 * @apiparam	array		        poll_options	Array of objects with keys 'title' (string), 'answers' (array of objects with key 'value' set to the choice) and 'multichoice' (int 1/0)
	 * @param		int		$id			ID Number
	 * @throws		2B300/6				INVALID_ID		The entry ID is invalid or the authorized user does not have permission to view it
	 * @throws		1B300/7				NO_BLOG			The blog ID does not exist or the authorized user does not have permission to post in it
	 * @throws		1B300/8				NO_AUTHOR		The author ID does not exist
	 * @throws		1B300/B				NO_PERMISSION	The authorized user does not have permission to edit that blog entry
	 * @apireturn		\IPS\blog\Entry
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$entry = Entry::load( $id );
			if ( $this->member and !$entry->can( 'read', $this->member ) )
			{
				throw new OutOfRangeException;
			}
			if ( $this->member and !$entry->canEdit( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '1B300/B', 403 );
			}
			
			/* New blog */
			if ( isset( Request::i()->blog ) and Request::i()->blog != $entry->blog_id and ( !$this->member or $entry->canMove( $this->member ) ) )
			{
				try
				{
					$newBlog = Blog::load( Request::i()->blog );
					if ( $this->member and !$newBlog->can( 'add', $this->member ) )
					{
						throw new OutOfRangeException;
					}
					
					$entry->move( $newBlog );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_BLOG', '1B300/7', 400 );
				}
			}
			
			/* New author */
			if ( !$this->member and isset( Request::i()->author ) )
			{				
				try
				{
					$member = Member::load( Request::i()->author );
					if ( !$member->member_id )
					{
						throw new OutOfRangeException;
					}
					
					$entry->changeAuthor( $member );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_AUTHOR', '1B300/8', 400 );
				}
			}
			
			/* Everything else */
			$this->_createOrUpdate( $entry, 'edit' );
			
			/* Save and return */
			$entry->save();
			return new Response( 200, $entry->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2B300/6', 404 );
		}
	}

	/**
	 * Create or update entry
	 *
	 * @param	Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	Item
	 */
	protected function _createOrUpdate( Item $item, string $type='add' ): Item
	{
		/* Is draft */
		if ( isset( Request::i()->draft ) )
		{
			$item->status = Request::i()->draft ? 'draft' : 'published';
		}
		
		/* Content */
		if ( isset( Request::i()->entry ) )
		{
			$entryContents = Request::i()->entry;
			if ( $this->member )
			{
				$entryContents = Parser::parseStatic( $entryContents, NULL, $this->member, 'blog_Entries' );
			}
			$item->content = $entryContents;
		}

		/* Do we have a poll to attach? */
		$this->_createOrUpdatePoll( $item, $type );

		/* Category */
		if ( isset( Request::i()->category ) )
		{
			$item->category_id = Request::i()->category;
		}
		
		/* Pass up */
		return parent::_createOrUpdate( $item, $type );
	}
		
	/**
	 * DELETE /blog/entries/{id}
	 * Delete an entry
	 *
	 * @param		int		$id			ID Number
	 * @throws		2B300/9	INVALID_ID		The entry ID does not exist
	 * @throws		2B300/C	NO_PERMISSION	The authorized user does not have permission to delete the entry
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$item = Entry::load( $id );
			if ( $this->member and !$item->canDelete( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2B300/C', 404 );
			}
			
			$item->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2B300/9', 404 );
		}
	}
}