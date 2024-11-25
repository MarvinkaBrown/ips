<?php
/**
 * @brief		GraphQL: Mark a forum read mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Nov 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\forums\api\GraphQL\Types\ForumType;
use IPS\forums\Forum;
use IPS\Node\Api\GraphQL\NodeMutator;
use OutOfRangeException;
use function defined;
use function intval;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mark forum read mutation for GraphQL API
 */
class MarkForumRead extends NodeMutator
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Mark a forum as read";

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
	 *
	 * @return ForumType
	 */
	public function type()  : ForumType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::forum();
	}

	/**
	 * Resolves this mutation
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	Forum
	 */
	public function resolve( mixed $val, array $args ) : Forum
	{
		try 
		{
			$forum = Forum::loadAndCheckPerms( intval( $args['id'] ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'INVALID_NODE', 'GQL/0004/2', 403 );
		}

		$this->_markRead( $forum );
		return $forum;
	}
}
