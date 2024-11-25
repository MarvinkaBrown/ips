<?php
/**
 * @brief		Invoices API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		9 Dec 2015
 */

namespace IPS\nexus\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Db;
use IPS\nexus\Invoice;
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
 * @brief	Invoices API
 */
class invoices extends Controller
{
	/**
	 * GET /nexus/invoices
	 * Get list of invoices
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only the members own invoices will be included
	 * @apiparam	string	customers			Comma-delimited list of customer IDs - if provided, only invoices belonging to those customers are returned. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string	statuses			Comma-delimited list of statuses - if provided, only invoices with those statuses are returned - see invoice object for status keys
	 * @apiparam	string	sortBy				What to sort by. Can be 'date', 'paid' (for paid date), 'total', 'title' or do not specify for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page				Page number
	 * @apiparam	int		perPage				Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\nexus\Invoice>
	 * @return PaginatedResponse<Invoice>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();
		
		/* Customers */
		if ( $this->member )
		{
			$where[] = array( 'i_member=?', $this->member->member_id );
		}
		elseif ( isset( Request::i()->customers ) )
		{
			$where[] = array( Db::i()->in( 'i_member', array_map( 'intval', array_filter( explode( ',', Request::i()->customers ) ) ) ) );
		}
		
		/* Statuses */
		if ( isset( Request::i()->statuses ) )
		{
			$where[] = array( Db::i()->in( 'i_status', array_filter( explode( ',', Request::i()->statuses ) ) ) );
		}
				
		/* Sort */
		if ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'date', 'title', 'total' ) ) )
		{
			$sortBy = 'i_' . Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'i_id';
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'nexus_invoices', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\nexus\Invoice',
			Db::i()->select( 'COUNT(*)', 'nexus_invoices', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
	
	/**
	 * GET /nexus/invoices/{id}
	 * Get information about a specific invoice
	 *
	 * @param		int		$id			ID Number
	 * @throws		2X299/1	INVALID_ID	The invoice ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\nexus\Invoice
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			$object = Invoice::load( $id );
			if ( $this->member and !$object->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}
			
			return new Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException )
		{
			throw new Exception( 'INVALID_ID', '2X299/1', 404 );
		}
	}
}