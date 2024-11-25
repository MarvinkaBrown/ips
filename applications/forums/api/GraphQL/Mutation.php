<?php
/**
 * @brief		GraphQL: Forums mutations
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL;
use IPS\forums\api\GraphQL\Mutations\CreateTopic;
use IPS\forums\api\GraphQL\Mutations\MarkForumRead;
use IPS\forums\api\GraphQL\Mutations\MarkTopicRead;
use IPS\forums\api\GraphQL\Mutations\MarkTopicSolved;
use IPS\forums\api\GraphQL\Mutations\PostReaction;
use IPS\forums\api\GraphQL\Mutations\ReplyTopic;
use IPS\forums\api\GraphQL\Mutations\ReportPost;
use IPS\forums\api\GraphQL\Mutations\RevokePostReport;
use IPS\forums\api\GraphQL\Mutations\SetBestAnswer;
use IPS\forums\api\GraphQL\Mutations\VoteAnswer;
use IPS\forums\api\GraphQL\Mutations\VoteInPoll;
use IPS\forums\api\GraphQL\Mutations\VoteQuestion;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forums mutationss GraphQL API
 */
abstract class Mutation
{
	/**
	 * Get the supported query types in this app
	 *
	 * @return	array
	 */
	public static function mutations() : array
	{
		return [
			'createTopic' => new CreateTopic(),
			'replyTopic' => new ReplyTopic(),
			'postReaction' => new PostReaction(),
			'markForumRead' => new MarkForumRead(),
			'markTopicRead' => new MarkTopicRead(),
			'markTopicSolved' => new MarkTopicSolved(),
			'voteInPoll' => new VoteInPoll(),
			'voteQuestion' => new VoteQuestion(),
			'voteAnswer' => new VoteAnswer(),
			'setBestAnswer' => new SetBestAnswer(),
			'reportPost' => new ReportPost(),
			'revokePostReport' => new RevokePostReport(),
		];
	}
}
