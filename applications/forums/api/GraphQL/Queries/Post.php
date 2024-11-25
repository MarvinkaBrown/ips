<?php
/**
 * @brief		GraphQL: Post query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Queries;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\forums\api\GraphQL\Types\PostType;
use IPS\forums\Topic\Post as PostClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Post query for GraphQL API
 */
class Post
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a post";

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
	 * @return PostType
	 */
	public function type() : PostType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::post();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @param 	array $context 	Context values
	 * @param	array $info
	 * @return	PostClass
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : PostClass
	{
		$post = PostClass::loadAndCheckPerms( $args['id'] );

		if( !$post->item()->canView() )
		{
			throw new OutOfRangeException;
		}
		return $post;
	}
}
