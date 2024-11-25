<?php
/**
 * @brief		Blog Entry Category API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		4 Sep 2019
 */

namespace IPS\blog\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\blog\Entry\Category;
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
 * @brief	Blog Categories API
 */
class entrycategories extends NodeController
{

	/**
	 * Class
	 */
	protected string $class = 'IPS\blog\Entry\Category';

	/**
	 * GET /blog/entrycategories
	 * Get list of entry categories
	 *
	 * @apiparam	int		blog	ID of blog. Null for all.
	 * @apiparam	string	sortBy	What to sort by. Can be 'position' for category position or do not specify for ID
	 * @apiparam	string	sortDir	Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page	Page number
	 * @apiparam	int		perPage	Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\blog\Entry\Category>
	 * @return PaginatedResponse<Category>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();

		/* @var Category $class */
		$class = $this->class;

		if ( isset( Request::i()->ids ) )
		{
			$idField = $class::$databaseTable . '.' . $class::$databasePrefix . '.' . $class::$databaseColumnId;
			$where[] = array( Db::i()->in( $idField, array_map( 'intval', explode( ',', Request::i()->ids ) ) ) );
		}

		/* Blog */
		if ( isset( Request::i()->blog ) )
		{
			$where[] = array( 'entry_category_blog_id=?', intval( Request::i()->blog ) );
		}

		/* Sort */
		if ( isset( Request::i()->sortBy ) and Request::i()->sortBy == 'position' )
		{
			$sortBy = 'entry_category_' . Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'entry_category_id';
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';

		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'blog_entry_categories', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\blog\Entry\Category',
			Db::i()->select( 'COUNT(*)', 'blog_entry_categories', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}

	/**
	 * GET /blog/entrycategories/{id}
	 * Get information about a specific entry category
	 *
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\blog\Entry\Category
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		return $this->_view( $id );
	}

	/**
	 * DELETE /blog/entrycategories/{id}
	 * Delete an entry category
	 *
	 * @param		int		$id			ID Number
	 * @apireturn		void
	 * @throws		2B408/5	INVALID_ID		The category ID does not exist or the authorized user does not have permission to delete it
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		/* @var Category $class */
		$class = $this->class;

		try
		{
			$category = $class::load( $id );
			if ( !$category->canDelete( $this->member ) )
			{
				throw new Exception( 'INVALID_ID', '2B408/5', 404 );
			}
			$category->delete();

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2B408/6', 404 );
		}
	}

}