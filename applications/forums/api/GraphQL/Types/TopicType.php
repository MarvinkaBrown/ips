<?php
/**
 * @brief		GraphQL: Topic Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Types;
use Exception;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\ItemType;
use IPS\forums\Topic\Post;
use IPS\Member;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * TopicType for GraphQL API
 */
class TopicType extends ItemType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $itemClass	= '\IPS\forums\Topic';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'forums_Topic';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'A topic';

	/*
	 * @brief 	Follow data passed in to FollowType resolver
	 */
	protected static array $followData = array('app' => 'forums', 'area' => 'topic');

	/**
	 * Return the fields available in this type
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		// Extend our fields with topic-specific stuff
		$defaultFields = parent::fields();
		$topicFields = array(
			'forum' => [
				'type' => \IPS\forums\api\GraphQL\TypeRegistry::forum(),
				'resolve' => function ($item) {
					return $item->container();
				}
			],
			'isArchived' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($topic) {
					return (bool) $topic->isArchived();
				}
			],
			'isHot' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($topic) {
					foreach( $topic->stats(FALSE) as $k => $v )
					{
						if( in_array( $k, $topic->hotStats ) )
						{
							return TRUE;
						}
					}
					
					return FALSE;
				}
			],

			/* SOLVED STUFF */
			'isSolved' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($topic) {
					return (bool) $topic->isSolved();
				}
			],
			'solvedId' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($topic) {
					if( $topic->isSolved() ){
						return $topic->mapped('solved_comment_id');
					}
					return 0;
				}
			],
			'canMarkSolved' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($topic) {
					return $topic->canSolve();
				}
			],
			'solvedComment' => [
				'type' => \IPS\forums\api\GraphQL\TypeRegistry::post(),
				'resolve' => function ($topic) {
					if( !$topic->isSolved() )
					{
						return NULL;
					}
					
					try 
					{
						return Post::load( $topic->mapped('solved_comment_id') );
					}
					catch (Exception $err)
					{
						return NULL;
					}
				}
			],
			'hasBestAnswer' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($topic) {
					return !!$topic->topic_answered_pid;
				}
			],
			'bestAnswerID' => [
				'type' => TypeRegistry::id(),
				'resolve' => function ($topic) {
					return $topic->topic_answered_pid;
				}
			],
			'canSetBestAnswer' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($topic) {
					return $topic->canSetBestAnswer();
				}
			]
		);

		// Questions get their comment count reduced by one in the model, but for the API
		// we'll reverse that change to keep topics & questions consistent.
		$topicFields['postCount'] = $defaultFields['commentCount'];
		$topicFields['postCount']['resolve'] = function ($topic, $args) {
			if( $args['includeHidden'] )
			{
				return $topic->commentCount();
			}

			return $topic->mapped('num_comments');
		};		

		// Duplicate fields that have different names in topics
		//$topicFields['forum'] = $defaultFields['container'];
		$topicFields['posts'] = $defaultFields['comments'];
		$topicFields['lastPostAuthor'] = $defaultFields['lastCommentAuthor'];
		$topicFields['lastPostDate'] = $defaultFields['lastCommentDate'];

		// Remove duplicated fields
		unset( $defaultFields['container'] );
		unset( $defaultFields['comments'] );
		unset( $defaultFields['commentCount'] );
		unset( $defaultFields['lastCommentAuthor'] );
		unset( $defaultFields['lastCommentDate'] );

		return array_merge( $defaultFields, $topicFields );
	}

	public static function args(): array
	{
		return array_merge( parent::args(), array(
			'password' => [
				'type' => TypeRegistry::string()
			]
		));
	}

	/**
	 * Return item permission fields.
	 * Here we adjust the resolver for the commentInformation field to check whether this is
	 * a poll-only topic.
	 *
	 * @return	array
	 */
	public static function getItemPermissionFields(): array
	{
		$defaultFields = parent::getItemPermissionFields();
		$existingResolver = $defaultFields['commentInformation']['resolve'];

		$defaultFields['commentInformation']['resolve'] = function ($topic, $args, $context) use ( $existingResolver ) {
			if( $topic->canComment( Member::loggedIn(), FALSE ) && ( $topic->getPoll() and $topic->getPoll()->poll_only ) )
			{
				return 'topic_poll_can_comment';
			}
			else
			{
				return $existingResolver($topic, $args, $context);
			}
		};

		return $defaultFields;
	}

	/**
	 * Return the available sorting options
	 *
	 * @return	array
	 */
	public static function getOrderByOptions(): array
	{
		$defaultArgs = parent::getOrderByOptions();
		return array_merge( $defaultArgs, array('last_comment', 'num_comments', 'views', 'author_name', 'last_comment_name', 'date', 'votes') );
	}

	/**
	 * Get the comment type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	protected static function getCommentType(): ObjectType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::post();
	}
}
