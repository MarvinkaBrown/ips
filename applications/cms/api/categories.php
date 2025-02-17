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

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\cms\Categories as CategoriesClass;
use IPS\cms\Databases;
use IPS\Node\Api\NodeController;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Pages Databases API
 */
class categories extends NodeController
{
	/**
	 * Class
	 */
	protected string $class = '';
	
	/**
	 * Get endpoint data
	 *
	 * @param	array	$pathBits	The parts to the path called
	 * @param	string	$method		HTTP method verb
	 * @return	array
	 * @throws	RuntimeException
	 */
	protected function _getEndpoint( array $pathBits, string $method = 'GET' ): array
	{
		if ( !count( $pathBits ) )
		{
			throw new RuntimeException;
		}
		
		$database = array_shift( $pathBits );
		if ( !count( $pathBits ) )
		{
			return array( 'endpoint' => 'index', 'params' => array( $database ) );
		}
		
		$nextBit = array_shift( $pathBits );
		if ( intval( $nextBit ) != 0 )
		{
			if ( count( $pathBits ) )
			{
				return array( 'endpoint' => 'item_' . array_shift( $pathBits ), 'params' => array( $database, $nextBit ) );
			}
			else
			{				
				return array( 'endpoint' => 'item', 'params' => array( $database, $nextBit ) );
			}
		}
				
		throw new RuntimeException;
	}

	/**
	 * GET /cms/categories/{database_id}
	 * Get list of database categories
	 *
	 * @param		int		$database			Database ID
	 * @apireturn		PaginatedResponse<IPS\cms\Categories>
	 * @return PaginatedResponse<CategoriesClass>
	 *@throws		2T306/2	DATABASE_DOES_NOT_USE_CATEGORIES		The database does not use categories
	 * @throws		2T306/1	INVALID_DATABASE							The database ID does not exist or the authorized user does not have permission to view it
	 */
	public function GETindex( int $database ): PaginatedResponse
	{
		/* Load database */
		try
		{
			$database = Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new OutOfRangeException;
			}	
			$this->class = 'IPS\cms\Categories' . $database->id;
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_DATABASE', '2T415/1', 404 );
		}	
		if ( !$database->use_categories )
		{
			throw new Exception( 'DATABASE_DOES_NOT_USE_CATEGORIES', '2T415/2', 404 );
		}
		
		/* Where clause */
		$where = array( 'category_database_id=?', $database->id );
		
		/* Return */
		return $this->_list( $where );
	}

	/**
	 * GET /cms/databases/{database_id}/{category_id}
	 * Get specific database
	 *
	 * @param int $database
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\cms\Categories
	 * @throws		2T306/3	INVALID_DATABASE		The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/4	INVALID_ID			The category ID does not exist or the authorized user does not have permission to view it
	 * @return Response
	 */
	public function GETitem( int $database, int $id ): Response
	{
		/* Load database */
		try
		{
			$database = Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new OutOfRangeException;
			}	
			$this->class = 'IPS\cms\Categories' . $database->id;
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_DATABASE', '2T415/3', 404 );
		}	
		
		/* Return */
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2T415/4', 404 );
		}
	}
}