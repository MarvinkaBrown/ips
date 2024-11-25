<?php
/**
 * @brief		Pages Databases API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		21 Feb 2020
 */

namespace IPS\cms\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\cms\Databases as DatabasesClass;
use IPS\Node\Api\NodeController;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Pages Databases API
 */
class databases extends NodeController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\cms\Databases';

	/**
	 * GET /cms/databases
	 * Get list of databases
	 *
	 * @apiclientonly
	 * @apireturn		PaginatedResponse<IPS\cms\Databases>
	 * @return PaginatedResponse<DatabasesClass>
	 */
	public function GETindex() : PaginatedResponse
	{
		return $this->_list();
	}

	/**
	 * GET /cms/databases/{id}
	 * Get specific database
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\cms\Databases
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		return $this->_view( $id );
	}
}