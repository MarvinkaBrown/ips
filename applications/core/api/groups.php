<?php
/**
 * @brief		Member Groups API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 Apr 2017
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Db;
use IPS\Member\Group;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member Groups API
 */
class groups extends Controller
{
	/**
	 * GET /core/groups
	 * Get list of groups
	 *
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\Member\Group>
	 * @return PaginatedResponse<Group>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();

		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'core_groups', $where, "g_id asc" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\Member\Group',
			Db::i()->select( 'COUNT(*)', 'core_groups', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}

	/**
	 * GET /core/groups/{id}
	 * Get information about a specific group
	 *
	 * @param		int		$id			ID Number
	 * @throws		1C358/1	INVALID_ID	The group ID does not exist
	 * @apireturn		\IPS\Member\Group
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			$group = Group::load( $id );
			if ( !$group->g_id )
			{
				throw new OutOfRangeException;
			}

			return new Response( 200, $group->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C358/1', 404 );
		}
	}

	/**
	 * DELETE /core/groups/{id}
	 * Deletes a group
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @throws		2C358/3	CANNOT_DELETE	The group can't be deleted
	 * @throws		1C358/2	INVALID_ID	The group ID does not exist
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$group = Group::load( $id );
			if ( !$group->g_id )
			{
				throw new OutOfRangeException;
			}

			if( !$group->canDelete() )
			{
				throw new Exception( 'CANNOT_DELETE', '2C358/3', 403 );
			}

			$group->delete();

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C358/2', 404 );
		}
	}
}