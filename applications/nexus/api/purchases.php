<?php
/**
 * @brief		Purchases API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		10 Dec 2015
 */

namespace IPS\nexus\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Application;
use IPS\Db;
use IPS\nexus\Purchase;
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
 * @brief	Purchases API
 */
class purchases extends Controller
{
	/**
	 * GET /nexus/purchases
	 * Get list of purchases
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only the members own purchases will be included
	 * @apiparam	string	customers			Comma-delimited list of customer IDs - if provided, only invoices belonging to those customers are returned. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int		active				If 1, only active purchases are returned. If 0, only inactive
	 * @apiparam	int		canceled			If 1, only canceled purchases are returned. If 0, only not canceled
	 * @apiparam	string	itemApp				If specified, only purchases with this application key are returned
	 * @apiparam	string	itemType			If specified, only purchases with this item type are returned
	 * @apiparam	int		itemId				If specified, only purchases with this item ID are returned
	 * @apiparam	int		parent				If specified, only purchases with which are children of the purchase with the ID specified are returned
	 * @apiparam	int		show				If 1, only purchases which show in the Admin CP are returned, if 0, only purchases which do not
	 * @apiparam	string	sortBy				What to sort by. Can be 'start' (for purchase date), 'expire' (for the epiry date) or do not specify for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page				Page number
	 * @apiparam	int		perPage				Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\nexus\Purchase>
	 * @return PaginatedResponse<Purchase>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();

		/* Get only the purchases from active applications */
		$where[] = array( "ps_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')" );

		/* Customers */
		if ( $this->member )
		{
			$where[] = array( 'ps_member=?', $this->member->member_id );
		}
		elseif ( isset( Request::i()->customers ) )
		{
			$where[] = array( Db::i()->in( 'ps_member', array_map( 'intval', array_filter( explode( ',', Request::i()->customers ) ) ) ) );
		}

		/* Status */
		if ( isset( Request::i()->active ) )
		{
			$where[] = array( 'ps_active=?', intval( Request::i()->active ) );
		}
		if ( isset( Request::i()->canceled ) )
		{
			$where[] = array( 'ps_cancelled=?', intval( Request::i()->canceled ) );
		}
		
		/* Item */
		if ( isset( Request::i()->itemApp ) )
		{
			$where[] = array( 'ps_app=?', Request::i()->itemApp );
		}
		if ( isset( Request::i()->itemType ) )
		{
			$where[] = array( 'ps_type=?', Request::i()->itemType );
		}
		if ( isset( Request::i()->itemId ) )
		{
			$where[] = array( 'ps_item_id=?', Request::i()->itemId );
		}
		
		/* Parent */
		if ( isset( Request::i()->parent ) )
		{
			$where[] = array( 'ps_parent=?', intval( Request::i()->parent ) );
		}
		
		/* Show */
		if ( isset( Request::i()->show ) )
		{
			$where[] = array( 'ps_show=?', intval( Request::i()->show ) );
		}
						
		/* Sort */
		if ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'start', 'expire' ) ) )
		{
			$sortBy = 'ps_' . Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'ps_id';
		}
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';
		
		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'nexus_purchases', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\nexus\Purchase',
			Db::i()->select( 'COUNT(*)', 'nexus_purchases', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
	
	/**
	 * GET /nexus/purchases/{id}
	 * Get information about a specific purchase
	 *
	 * @param		int		$id			ID Number
	 * @throws		2X310/1	INVALID_ID	The purchase ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\nexus\Purchase
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{			
			$object = Purchase::load( $id );
			if ( $this->member and !$object->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}
			
			return new Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException )
		{
			throw new Exception( 'INVALID_ID', '2X309/1', 404 );
		}
	}
	
	/**
	 * POST /nexus/purchases/{id}
	 * Update custom fields for a purchase
	 *
	 * @apiclientonly
	 * @apiparam	object	customFields	Values for custom fields
	 * @param		int		$id			ID Number
	 * @throws		2X309/1	INVALID_ID	The purchase ID does not exist
	 * @apireturn		\IPS\nexus\Purchase
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{			
			$purchase =  Purchase::load( $id );
		}
		catch ( OutOfRangeException )
		{
			throw new Exception( 'INVALID_ID', '2X309/1', 404 );
		}
		
		if ( isset( Request::i()->customFields ) )
		{
			$customFields = $purchase->custom_fields;
			foreach ( Request::i()->customFields as $k => $v )
			{
				$customFields[ $k ] = $v;
			}
			$purchase->custom_fields = $customFields;
		}
		
		$purchase->save();
		
		return new Response( 200, $purchase->apiOutput( $this->member ) );
	}
}