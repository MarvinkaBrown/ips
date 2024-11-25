<?php
/**
 * @brief		GraphQL: Leave a PM conversation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Oct 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Mutations\Messenger;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\MessengerConversationType;
use IPS\core\Messenger\Conversation;
use IPS\Member;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Leave conversation mutation for GraphQL API
 */
class LeaveConversation
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Leave a PM conversation";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'id' => TypeRegistry::nonNull( TypeRegistry::id() )
		];
	}

	/**
	 * Return the mutation return type
	 */
	public function type() : MessengerConversationType
	{
		return \IPS\core\Api\GraphQL\TypeRegistry::messengerConversation();
	}

	/**
	 * Resolves this mutation
	 *
	 * @param 	mixed $val	Value passed into this resolver
	 * @param 	array $args	Arguments
	 * @return	mixed
	 */
	public function resolve( mixed $val, array $args ) : mixed
	{
		if( !Member::loggedIn()->member_id )
		{
			throw new SafeException( 'NOT_LOGGED_IN', 'GQL/0003/1', 403 );
		}

		try
		{
            $conversation = Conversation::loadAndCheckPerms( $args['id'] );
            $conversation->deauthorize( Member::loggedIn() );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_MESSAGE', '1F294/2_graphl', 400 );
		}

		return NULL;
	}
}
