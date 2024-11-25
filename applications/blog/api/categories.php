<?php
/**
 * @brief		Blog Categories API
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
use IPS\blog\Category;
use IPS\Db;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Node\Api\NodeController;
use IPS\Node\Model;
use IPS\Request;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Blog Blogs API
 */
class categories extends NodeController
{

	/**
	 * Class
	 */
	protected string $class = 'IPS\blog\Category';

	/**
	 * GET /blog/categories
	 * Get list of blog categories
	 *
     * @apiparam	string	ids 	Comma-delimited list of category ids
	 * @apiparam	string	sortBy	What to sort by. Can be 'count_entries' for number of entries, 'last_edate' for last entry date or do not specify for ID
	 * @apiparam	string	sortDir	Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page	Page number
	 * @apiparam	int		perPage	Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\blog\Category>
	 * @return PaginatedResponse<Category>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = $this->_globalWhere();

		/* Sort */
		if ( isset( Request::i()->sortBy ) and Request::i()->sortBy == 'position' )
		{
			$sortBy = 'category_' . Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'category_id';
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';

		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'blog_categories', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\blog\Category',
			Db::i()->select( 'COUNT(*)', 'blog_categories', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}

	/**
	 * GET /blog/categories/{id}
	 * Get information about a specific blog category
	 *
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\blog\Category
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		return $this->_view( $id );
	}

	/**
	 * POST /blog/categories
	 * Create a blog category
	 *
	 * @apiclientonly
	 * @reqapiparam	string		name				The category name
	 * @apiparam	int|null	parent				The ID number of the parent the category should be created in. NULL for root.
	 * @apiparam	int			position			The category position
	 * @apireturn		\IPS\blog\Category
	 * @throws		1B408/1		NO_TITLE			A name for the category must be supplied
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		if ( !Request::i()->name )
		{
			throw new Exception( 'NO_TITLE', '1B408/1', 400 );
		}

		return new Response( 201, $this->_create()->apiOutput( $this->member ) );
	}

	/**
	 * POST /blog/categories/{id}
	 * Edit a blog category
	 *
	 * @apiclientonly
	 * @reqapiparam	string		name				The category name
	 * @apiparam	int|null	parent				The ID number of the parent the category should be created in. NULL for root.
	 * @apiparam	int			position			The category position
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\blog\Category
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		/* @var Category $class */
		$class = $this->class;
		$category = $class::load( $id );

		return new Response( 200, $this->_createOrUpdate( $category )->apiOutput( $this->member ) );
	}

	/**
	 * DELETE /blog/categories/{id}
	 * Delete a blog category
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @apireturn		void
	 * @return Response
	 * @throws		2B408/3	INVALID_ID		The blog category ID does not exist or the authorized user does not have permission to delete it
	 */
	public function DELETEitem( int $id ): Response
	{
		/* @var Category $class */
		$class = $this->class;

		try
		{
			$category = $class::load( $id );
			$category->delete();

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2B408/3', 404 );
		}
	}

	/**
	 * Create or update node
	 *
	 * @param	Model	$node				The node
	 * @return	Model
	 */
	protected function _createOrUpdate( Model $node ): Model
	{
		if ( isset( Request::i()->name ) )
		{
			Lang::saveCustom( 'blog', "blog_category_{$node->id}", Request::i()->name );

			$node->seo_name = Friendly::seoTitle( Request::i()->name );
		}

		$node->parent = (int) Request::i()->parent?: Category::$databaseColumnParentRootValue;

		return parent::_createOrUpdate( $node );
	}
}