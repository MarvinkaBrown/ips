<?php
/**
 * @brief		GraphQL: Topic query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Queries;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\Topic as TopicClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Topic query for GraphQL API
 */
class Topic
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a topic";

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
	 *
	 * @return TopicType
	 */
	public function type() : TopicType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::topic();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @param 	array $context 	Context values
	 * @param	array $info
	 * @return	TopicClass
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : TopicClass
	{
		try
		{
			$topic = TopicClass::loadAndCheckPerms( $args['id'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_TOPIC', '1F294/2_graphql', 400 );
		}

		if( !$topic->can('read') )
		{
			throw new SafeException( 'INVALID_ID', '2F294/9_graphql', 403 );
		}

		return $topic;
	}
}
