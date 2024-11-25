<?php
/**
 * @brief		Personal Conversations API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Dec 2017
 * @note		We intentionally have not added any way to fetch messages to match the built in privacy functionality
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\Response;
use IPS\core\Messenger\Conversation;
use IPS\core\Messenger\Message;
use IPS\DateTime;
use IPS\Member;
use IPS\Request;
use IPS\Text\Parser;
use OutOfRangeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Personal Conversations API
 */
class messages extends Controller
{
	/**
	 * POST /core/messages
	 * Create a new personal conversation
	 *
	 * @apiclientonly
	 * @apiparam	int		from			User ID conversation is from
	 * @apiparam	array	to				One or more user IDs conversation is sent to
	 * @apiparam	string	title			Conversation title
	 * @apiparam	string	body			Conversation body
	 * @throws		1C374/2	INVALID_SENDER			Sender was not supplied or is invalid
	 * @throws		1C374/3	INVALID_RECIPIENT		No recipients were supplied
	 * @throws		1C374/4	INVALID_RECIPIENT		One or more recipients are invalid
	 * @throws		1C374/5	MISSING_TITLE_OR_BODY	The title and/or body of the conversation were not supplied
	 * @apireturn		int		Conversation ID
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* Make sure there is a valid sender */
		if ( !isset( Request::i()->from ) OR !Member::load( (int) Request::i()->from )->member_id )
		{
			throw new Exception( 'INVALID_SENDER', '1C374/2', 404 );
		}

		/* Verify there are recipients and all the recipients are valid */
		if( !isset( Request::i()->to ) OR !is_array( Request::i()->to ) OR !count( Request::i()->to ) )
		{
			throw new Exception( 'INVALID_RECIPIENT', '1C374/3', 404 );
		}
		else
		{
			foreach( Request::i()->to as $to )
			{
				if( !Member::load( (int) $to )->member_id )
				{
					throw new Exception( 'INVALID_RECIPIENT', '1C374/4', 404 );
				}
			}
		}

		/* Make sure we have a title and body */
		if( !isset( Request::i()->title ) OR !isset( Request::i()->body ) )
		{
			throw new Exception( 'MISSING_TITLE_OR_BODY', '1C374/5', 404 );
		}

		/* Create the conversation */
		$item = Conversation::createItem( Member::load( (int) Request::i()->from ), Request::i()->ipAddress(), DateTime::create() );
		$item->title	= Request::i()->title;
		$item->to_count	= count( Request::i()->to );
		$item->save();

		/* Create the first message */
		$postContents = Parser::parseStatic( Request::i()->body, NULL, Member::load( (int)Request::i()->from ), 'core_Messaging' );

		/** @var Message $commentClass */
		$commentClass = $item::$commentClass;
		$post = $commentClass::create( $item, $postContents, TRUE, NULL, NULL, Member::load( (int) Request::i()->from ), DateTime::create() );
		
		$item->first_msg_id = $post->id;
		$item->save();

		/* Authorize sender and recipients */
		$item->authorize( array_map( function( $member ) { return (int) $member; }, array_merge( array( Request::i()->from ), Request::i()->to ) ) );

		/* Send notifications */
		$post->sendNotifications();

		return new Response( 201, $item->id );
	}

	/**
	 * POST /core/messages/{id}
	 * Add a reply to a personal conversation
	 *
	 * @apiclientonly
	 * @apiparam	string	body			Message body
	 * @apiparam	int		from			Person responding to message (must be part of conversation)
	 * @param		int		$id				ID Number
	 * @throws		1C374/6	INVALID_ID		The personal conversation ID does not exist
	 * @throws		1C374/7	INVALID_SENDER	The sender ID supplied was not valid
	 * @throws		1C374/8	SENDER_NO_PERMISSON	The sender supplied does not have permmission to reply to the conversation
	 * @apireturn		bool
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$message = Conversation::load( $id );

			/* Make sure we have a member, and the member is authorized to reply */
			if( !isset( Request::i()->from ) OR !Member::load( (int) Request::i()->from )->member_id )
			{
				throw new Exception( 'INVALID_SENDER', '1C374/7', 404 );
			}

			if( !$message->canView( Member::load( (int) Request::i()->from ) ) )
			{
				throw new Exception( 'SENDER_NO_PERMISSON', '1C374/8', 403 );
			}

			/* Create the reply */
			$postContents = Parser::parseStatic( Request::i()->body, NULL, Member::load( (int)Request::i()->from ), 'core_Messaging' );

			$commentClass = $message::$commentClass;
			$post = $commentClass::create( $message, $postContents, TRUE, NULL, NULL, Member::load( (int) Request::i()->from ), DateTime::create() );

			/* Send notifications */
			$post->sendNotifications();

			return new Response( 200, TRUE );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C374/6', 404 );
		}
	}
	
	/**
	 * DELETE /core/messages/{id}
	 * Deletes a personal conversation
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @throws		1C292/2	INVALID_ID	The personal conversation ID does not exist
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$message = Conversation::load( $id );
			$message->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C374/1', 404 );
		}
	}
}