<?php
/**
 * @brief		GraphQL: Vote on an answer
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		02 Jan 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Db;
use IPS\forums\api\GraphQL\Types\PostType;
use IPS\forums\Topic\Post;
use IPS\Member;
use OutOfRangeException;
use UnderflowException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Vote on an answer mutation for GraphQL API
 */
class VoteAnswer
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Vote on an answer";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'id' => TypeRegistry::nonNull( TypeRegistry::id() ),
			'vote' => TypeRegistry::nonNull( \IPS\forums\api\GraphQL\TypeRegistry::vote() ),
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
			throw new SafeException( 'NO_POST', 'GQL/0009/1', 403 );
		}

		if( !$topic->can('read') )
		{
			throw new SafeException( 'INVALID_ID', 'GQL/0009/2', 403 );
		}

		if( !$post->canVote() )
		{
			throw new SafeException( 'CANNOT_VOTE', 'GQL/0009/4', 403 );
		}

		$rating = $args['vote'] == 'UP' ? 1 : -1;

		/* Have we already rated ? */
		try
		{
			Db::i()->delete( 'forums_answer_ratings', array( 'topic=? AND post=? AND `member`=?', $topic->tid, $post->pid, Member::loggedIn()->member_id ) );
		}
		catch ( UnderflowException $e ){}
		
		Db::i()->insert( 'forums_answer_ratings', array(
			'post'		=> $post->pid,
			'topic'		=> $topic->tid,
			'member'	=> Member::loggedIn()->member_id,
			'rating'	=> $rating,
			'date'		=> time()
		), TRUE );

		$post->post_field_int = (int) Db::i()->select( 'SUM(rating)', 'forums_answer_ratings', array( 'post=?', $post->pid ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$post->save();

		return $post;
	}
}
