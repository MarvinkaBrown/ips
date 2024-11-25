<?php
/**
 * @brief		GraphQL: Vote in poll mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Dec 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use GraphQL\Type\Definition\InputObjectType;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\Topic;
use IPS\Poll\Api\GraphQL\PollMutator;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Vote in poll mutation for GraphQL API
 */
class VoteInPoll extends PollMutator
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic';

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Vote in a poll in a topic";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'itemID' => TypeRegistry::nonNull( TypeRegistry::id() ),
			'poll' => TypeRegistry::listOf( 
				new InputObjectType([
					'name' => 'core_PollQuestionInput',
					'fields' => [
						'id' => TypeRegistry::id(),
						'choices' => TypeRegistry::listOf( TypeRegistry::int() )
					]
				])
			)
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
		/* Get topic */
		try
		{
			$topic = Topic::loadAndCheckPerms( $args['itemID'] );
			$poll = $topic->getPoll();
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'INVALID_TOPIC', 'GQL/0006/1', 403 );
		}
		
		if( !$topic->can('read') || $poll === NULL )
		{
			throw new SafeException( 'NO_POLL', 'GQL/0006/2', 403 );
		}

		$this->_vote( $poll, $args['poll'] );

		return $topic;
	}
}
