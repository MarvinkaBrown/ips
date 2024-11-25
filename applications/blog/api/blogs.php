<?php

/**
 * @brief		Blog Blogs API
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
use IPS\Db;
use IPS\Node\Api\NodeController;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Blog Blogs API
 */
class blogs extends NodeController
{

	/**
	 * Class
	 */
	protected string $class = 'IPS\blog\Blog';

	/**
	 * GET /blog/blogs
	 * Get list of blogs
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only blogs that are not disabled and not belonging to a particular club or social group will be included
	 * @apiparam	string	ids 	Comma-delimited list of blog ids
	 * @apiparam	string	owners	Comma-delimited list of member IDs - if provided, only blogs owned by those members are returned (can be used in conjection with groups or set to 0 to exclude member-created blogs)
	 * @apiparam	string	groups	Comma-delimited list of group IDs - if provided, only blogs owned by those groups are returned (can be used in conjection with members or set to 0 to exclude group blogs)
	 * @apiparam	int		pinned	If 1, only blogs which are pinned are returned, if 0 only not pinned
	 * @apiparam	string	sortBy	What to sort by. Can be 'count_entries' for number of entries, 'last_edate' for last entry date or do not specify for ID
	 * @apiparam	string	sortDir	Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page	Page number
	 * @apiparam	int		perPage	Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<\IPS\blog\Blog>
	 * @return		PaginatedResponse<Blog>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = $this->_globalWhere();

		/* Owners */
		if ( isset( Request::i()->owners ) and isset( Request::i()->groups ) )
		{
			$where[] = array( '( ' . Db::i()->in( 'blog_member_id', array_filter( explode( ',', Request::i()->owners ) ) ) . ' OR ' . Db::i()->findInSet( 'blog_groupblog_ids', array_filter( explode( ',', Request::i()->groups ) ) ) . ' )' );
		}
		elseif ( isset( Request::i()->owners ) )
		{
			$where[] = array( '( ' . Db::i()->in( 'blog_member_id', array_filter( explode( ',', Request::i()->owners ) ) ) . ' OR blog_groupblog_ids<>? )', '' );
		}
		elseif ( isset( Request::i()->groups ) )
		{
			$where[] = array( '( blog_member_id>0 OR ' . Db::i()->findInSet( 'blog_groupblog_ids', array_filter( explode( ',', Request::i()->groups ) ) ) . ' )' );
		}
		
		/* Pinned */
		if ( isset( Request::i()->pinned ) )
		{
			$where[] = array( 'blog_pinned=?', intval( Request::i()->pinned ) );
		}
		
		/* Permission */
		if ( $this->member )
		{
			$where[] = array( 'blog_disabled=0 AND blog_social_group IS NULL AND blog_club_id IS NULL' );
		}
	
		/* Sort */
		if ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'count_entries', 'last_edate' ) ) )
		{
			$sortBy = 'blog_' . Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'blog_id';
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'blog_blogs', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\blog\Blog',
			Db::i()->select( 'COUNT(*)', 'blog_blogs', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
	
	/**
	 * GET /blog/blogs/{id}
	 * Get information about a specific blog
	 *
	 * @param		int		$id			ID Number
	 * @throws		2B302/1	INVALID_ID	The blog ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\blog\Blog
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		return $this->_view( $id );
	}

	/**
	 * DELETE /blog/blogs/{id}
	 * Delete a blog
	 *
	 * @param		int		$id			ID Number
	 * @apireturn		void
	 * @return Response
	 * @throws		2B302/2	INVALID_ID		The blog ID does not exist or the authorized user does not have permission to view it
	 * @throws		2B302/3	NO_PERMISSION	The authorized user does not have permission to delete the blog
	 */
	public function DELETEitem( int $id ): Response
	{
		/* @var Blog $class */
		$class = $this->class;
		
		try
		{
			$blog = $this->member ? $class::loadAndCheckPerms( $id ) : $class::load( $id );
			if ( !$blog->canDelete() )
			{
				throw new Exception( 'INVALID_ID', '2B302/3', 404 );
			}
			$blog->delete();

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2B302/2', 404 );
		}
	}
}