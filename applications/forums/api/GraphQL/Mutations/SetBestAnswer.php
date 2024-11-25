<?php
/**
 * @brief		GraphQL: Set a post as best answer
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		08 Jan 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use Exception;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
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
 * Set a post as best answer mutation for GraphQL API
 */
class SetBestAnswer
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Set a post as best answer";

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
	 * @return PostType
	 */
	public function type() : PostType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::post();
	}

	/**
	 * Resolves this mutation
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	Post
	 */
	public function resolve( mixed $val, array $args ) : Post
	{
		try
		{
			$post = Post::loadAndCheckPerms( $args['id'] );
			$topic = $post->item();
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_POST', 'GQL/0010/1', 403 );
		}

		if( !$topic->can('read') )
		{
			throw new SafeException( 'INVALID_ID', 'GQL/0010/2', 403 );
		}

		if( !$topic->canSetBestAnswer() )
		{
			throw new SafeException( 'NO_PERMISSION', 'GQL/0010/4', 403 );
		}

		// Do we have an existing best answer
		if ( $topic->topic_answered_pid )
		{
			try
			{
				$oldBestAnswer = Post::load( $topic->topic_answered_pid );
				$oldBestAnswer->post_bwoptions['best_answer'] = FALSE;
				$oldBestAnswer->save();
			}
			catch ( Exception $e ) {}
		}

		$post->post_bwoptions['best_answer'] = TRUE;
		$post->save();
		
		$topic->topic_answered_pid = $post->pid;
		$topic->save();

		return $post;
	}
}
