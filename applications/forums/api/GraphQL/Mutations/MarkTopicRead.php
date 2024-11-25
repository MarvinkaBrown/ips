<?php
/**
 * @brief		GraphQL: Mark a topic read mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Nov 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\ItemMutator;
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\Topic;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mark topic read mutation for GraphQL API
 */
class MarkTopicRead extends ItemMutator
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Mark a topic as read";

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
	 * @reutrn TopicType
	 */
	public function type() : TopicType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::topic();
	}

	/**
	 * Resolves this mutation
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	Topic
	 */
	public function resolve( mixed $val, array $args ) : Topic
	{
		try
		{
			$topic = Topic::loadAndCheckPerms( $args['id'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_TOPIC', 'GQL/0005/1', 400 );
		}

		if( !$topic->can('read') )
		{
			throw new SafeException( 'INVALID_ID', 'GQL/0005/2', 403 );
		}

		$this->_markRead( $topic );
		return $topic;
	}
}
