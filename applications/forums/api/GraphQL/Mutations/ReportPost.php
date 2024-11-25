<?php
/**
 * @brief		GraphQL: Report a post mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Jun 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\CommentMutator;
use IPS\Content\Comment;
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
 * Report post mutation for GraphQL API
 */
class ReportPost extends CommentMutator
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic\Post';

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Report a post";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'id'		        => TypeRegistry::nonNull( TypeRegistry::id() ),
			'reason'		    => [
				'type' => TypeRegistry::int(),
				'defaultValue' => 0
			],
			'additionalInfo'    => TypeRegistry::string()
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
	public function resolve( mixed $val, array $args, array $context, array $info ) : Comment
	{
		/* Get post */
		try
		{
			$post = Post::loadAndCheckPerms( $args['id'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_POST', '1F295/1_graphl', 403 );
		}
		
		/* Do it */
		return $this->_reportComment( $args, $post );
	}
}
