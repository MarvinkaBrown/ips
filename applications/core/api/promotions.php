<?php
/**
 * @brief		Promotions API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 June 2022
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\core\Feature;
use IPS\Db;
use IPS\Node\Api\NodeController;
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
 * @brief	Promotions API
 */
class promotions extends NodeController
{
    /**
     * GET /core/promotions
     * Get list of promoted items
     *
     * @apiparam	int		page			Page number
     * @apiparam	int		perPage			Number of results per page - defaults to 25
     * @apireturn		PaginatedResponse<IPS\core\Feature>
     * @throws		2C429/1	NO_PERMISSION	The current authorized user does not have permission to issue warnings and as such cannot view the list of warn reasons
	 * @return PaginatedResponse<Feature>
     */
    public function GETindex(): PaginatedResponse
    {
		$page		= isset( Request::i()->page ) ? Request::i()->page : 1;
		$perPage	= isset( Request::i()->perPage ) ? Request::i()->perPage : 25;

		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';

		$sortBy = ( isset( Request::i()->sortBy ) and in_array( mb_strtolower( Request::i()->sortBy ), array( 'promote_id', 'promote_scheduled' ) ) ) ? Request::i()->sortBy  :'promote_id';

		/* Check permissions */
        if( $this->member AND !$this->member->modPermission('mod_see_warn') )
        {
            throw new Exception( 'NO_PERMISSION', '2C429/1', 403 );
        }

		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'core_content_promote', array(), "{$sortBy} {$sortDir}" ),
			$page,
			'IPS\core\Feature',
			Db::i()->select( 'COUNT(*)', 'core_content_promote', array() )->first(),
			$this->member,
			$perPage
		);
    }

    /**
     * GET /core/promotions/{id}
     * Get specific promotion object
     *
     * @param		int		$id			ID Number
     * @throws		12C429/2	INVALID_ID	The promotion does not exist
     * @apireturn		\IPS\core\Feature
	 * @return Response
     */
    public function GETitem( int $id ): Response
    {
        try
        {
            $promotion = Feature::load( $id );

			/* Return */
			return new Response( 200, $promotion->apiOutput( $this->member ) );
        }
        catch ( OutOfRangeException $e )
        {
            throw new Exception( 'INVALID_ID', '2C429/2', 404 );
        }
    }
}