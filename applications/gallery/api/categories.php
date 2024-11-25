<?php
/**
 * @brief		Gallery Categories API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		20 Feb 2020
 */

namespace IPS\gallery\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Db;
use IPS\gallery\Category;
use IPS\Node\Api\NodeController;
use IPS\Request;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Gallery Categories API
 */
class categories extends NodeController
{

	/**
	 * Class
	 */
	protected string $class = 'IPS\gallery\Category';

	/**
	 * GET /gallery/categories
	 * Get list of gallery categories
	 *
	 * @apiparam	string	sortBy	What to sort by. Can be 'count_imgs' for number of entries, 'last_img_date' for last image date or do not specify for ID
	 * @apiparam	string	sortDir	Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page	Page number
	 * @apiparam	int		perPage	Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\gallery\Category>
	 * @return PaginatedResponse<Category>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();

		/* Sort */
		if ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'count_imgs', 'last_img_date' ) ) )
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
			Db::i()->select( '*', 'gallery_categories', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\gallery\Category',
			Db::i()->select( 'COUNT(*)', 'gallery_categories', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}

	/**
	 * GET /gallery/categories/{id}
	 * Get information about a specific gallery category
	 *
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\gallery\Category
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		return $this->_view( $id );
	}
}