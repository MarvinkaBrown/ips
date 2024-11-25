<?php
/**
 * @brief		GraphQL: Reply to topic mutation
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
use IPS\Content\Comment;
use IPS\forums\api\GraphQL\Types\PostType;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
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
 * Reply to topic mutation for GraphQL API
 */
class ReplyTopic extends CommentMutator
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic\Post';

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Create a new post";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'topicID'		=> TypeRegistry::nonNull( TypeRegistry::id() ),
			'content'		=> TypeRegistry::nonNull( TypeRegistry::string() ),
			'replyingTo'	=> TypeRegistry::id(),
			'postKey'		=> TypeRegistry::string()
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
		/* Get topic */
		try
		{
			$topic = Topic::loadAndCheckPerms( $args['topicID'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_TOPIC', '1F295/1_graphl', 403 );
		}
		
		/* Get author */
		if ( !$topic->canComment( Member::loggedIn() ) )
		{
			throw new SafeException( 'NO_PERMISSION', '2F294/A_graphl', 403 );
		}
		
		/* Check we have a post */
		if ( !$args['content'] )
		{
			throw new SafeException( 'NO_POST', '1F295/3_graphl', 403 );
		}

		$originalPost = NULL;

		if( isset( $args['replyingTo'] ) )
		{
			try
			{
				$originalPost = Post::loadAndCheckPerms( $args['replyingTo'] );
			}
			catch ( OutOfRangeException $e )
			{
				// Just ignore it
			}			
		}
		
		/* Do it */
		return $this->_createComment( $args, $topic, $args['postKey'] ?? NULL, $originalPost );
	}
}
