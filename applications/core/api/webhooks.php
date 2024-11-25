<?php
/**
 * @brief		Webhooks API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Feb 2020
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\Response;
use IPS\Api\Webhook;
use IPS\Http\Url;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Webhooks API
 */
class webhooks extends Controller
{
	/**
	 * POST /core/webhooks
	 * Create a webhook
	 *
	 * @apiclientonly
	 * @reqapiparam	array	events	List of events to subscribe to
	 * @reqapiparam	string	url		URL to send webhook to
	 * @apiparam	string	content_header	The content type for the request.
	 * @apireturn		\IPS\Api\Webhook
	 * @throws		1C293/1	NO_EVENTS	No events were specified
	 * @throws		1C293/2	INVALID_URL	The URL specified was not valid
	 * @return Response
	 */
	public function POSTindex(): Response
	{				
		$events = Request::i()->events;
		if ( !$events )
		{
			throw new Exception( 'NO_EVENTS', '1C293/1', 400 );
		}
		
		try
		{
			$url = new Url( Request::i()->url );
		}
		catch ( Url\Exception $e )
		{
			throw new Exception( 'INVALID_URL', '1C293/2', 400 );
		}
		
		$webhook = new Webhook;
		$webhook->api_key = $this->apiKey;
		$webhook->events = $events;
		$webhook->filters = ( Request::i()->filters ?: array() );
		$webhook->url = $url;
		if( Request::i()->content_header )
		{
			$webhook->content_type = Request::i()->content_header;
		}
		$webhook->save();
		
		return new Response( 201, $webhook->apiOutput() );
	}
	
	/**
	 * DELETE /core/webhooks/{id}
	 * Deletes a webhook
	 *
	 * @apiclientonly
	 * @param		int		$id					ID Number
	 * @apireturn		void
	 * @throws		1C293/3	INVALID_ID		The ID provided does not match any webhook
	 * @throws		3C293/4	WRONG_API_KEY	The API key making this request is not the API key that created the webhook
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$webhook = Webhook::load( $id );
			
			if ( $webhook->api_key != $this->apiKey )
			{
				throw new Exception( 'WRONG_API_KEY', '3C293/4', 403 );
			}

			$webhook->delete();

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C293/3', 404 );
		}
	}
}