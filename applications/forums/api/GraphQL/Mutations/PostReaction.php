<?php
/**
 * @brief		GraphQL: React to a post mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\CommentMutator;
use IPS\forums\api\GraphQL\Types\PostType;
use IPS\forums\Topic\Post;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * React to post mutation for GraphQL API
 */
class PostReaction extends CommentMutator
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic\Post';

	/*
	 * @brief 	Query description
	 */
	public static string $description = "React to a post";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'postID' => TypeRegistry::nonNull( TypeRegistry::id() ),
			'reactionID' => TypeRegistry::int(),
			'removeReaction' => TypeRegistry::boolean()
		];
	}

	/**
	 * Return the mutation return type
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
	 * @return	Post
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : Post
	{
		/* Get topic */
		try
		{
			$post = Post::loadAndCheckPerms( $args['postID'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_POST', '1F295/1_graphl', 403 );
		}

		/* Do it */
		if( isset( $args['removeReaction'] ) && $args['removeReaction'] )
		{
			$this->_unreactComment( $post );
		}
		else
		{
			$this->_reactComment( $args['reactionID'], $post );
		}

		return $post;
	}
}
