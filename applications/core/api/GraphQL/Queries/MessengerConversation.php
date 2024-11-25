<?php
/**
 * @brief		GraphQL: Messenger conversation query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Sep 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\MessengerConversationType;
use IPS\core\Messenger\Conversation;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Messenger conversation query for GraphQL API
 */
class MessengerConversation
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a messenger conversation";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'id' => TypeRegistry::nonNull( TypeRegistry::id() )
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : MessengerConversationType
	{
		return \IPS\core\api\GraphQL\TypeRegistry::messengerConversation();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @param 	array $context 	Context values
	 * @param	array $info
	 * @return	Conversation
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : Conversation
	{
		try
		{
			$conversation = Conversation::loadAndCheckPerms( $args['id'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_MESSAGE', '1F294/2', 400 );
		}

		return $conversation;
	}
}
