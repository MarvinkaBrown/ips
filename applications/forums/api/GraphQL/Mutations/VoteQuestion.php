<?php
/**
 * @brief		GraphQL: Vote on a question
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
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\Topic;
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
 * Vote on a question mutation for GraphQL API
 */
class VoteQuestion
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Vote on a question";

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
	 * @return TopicType
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
			throw new SafeException( 'NO_TOPIC', 'GQL/0008/1', 400 );
		}

		if( !$topic->can('read') )
		{
			throw new SafeException( 'INVALID_ID', 'GQL/0008/2', 403 );
		}

		if( !$topic->canVote() )
		{
			throw new SafeException( 'CANNOT_VOTE', 'GQL/0008/4', 403 );
		}

		$rating = $args['vote'] == 'UP' ? 1 : -1;

		// If we have an existing vote, undo that
		$ratings = $topic->votes();
		if ( isset( $ratings[ Member::loggedIn()->member_id ] ) )
		{
			Db::i()->delete( 'forums_question_ratings', array( 'topic=? AND member=?', $topic->tid, Member::loggedIn()->member_id ) );
		}
		
		Db::i()->insert( 'forums_question_ratings', array(
			'topic'		=> $topic->tid,
			'forum'		=> $topic->forum_id,
			'member'	=> Member::loggedIn()->member_id,
			'rating'	=> $rating,
			'date'		=> time()
		), TRUE );
		
		/* Rebuild count */
		$topic->question_rating = Db::i()->select( 'SUM(rating)', 'forums_question_ratings', array( 'topic=?', $topic->tid ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$topic->save();

		return $topic;
	}
}
