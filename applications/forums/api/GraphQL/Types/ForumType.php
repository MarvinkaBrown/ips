<?php
/**
 * @brief		GraphQL: Forum Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\DateTime;
use IPS\forums\Forum;
use IPS\Login;
use IPS\Member;
use IPS\Node\Api\GraphQL\NodeType;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ForumType for GraphQL API
 */
class ForumType extends NodeType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $nodeClass	= '\IPS\forums\Forum';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'forums_Forum';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'A forum';

	/*
	 * @brief 	Follow data passed in to FollowType resolver
	 */
	protected static array $followData = array('app' => 'forums', 'area' => 'forum');

	/**
	 * Return the fields available in this type
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		$defaultFields = parent::fields();
		$forumFields = array(
			'solvedEnabled' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($forum) {
					return ( $forum->forums_bitoptions['bw_solved_set_by_member'] || $forum->forums_bitoptions['bw_solved_set_by_moderator'] );
				}
			],
			'lastPostAuthor' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::member(),
				'resolve' => function ($forum, $args) {
					return self::lastPostAuthor( $forum, $args );
				}
			],
			'lastPostDate' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($forum, $args) {
					return self::lastPostDate( $forum, $args );
				}
			],
			'featureColor' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($forum) {
					return $forum->feature_color;
				}
			],
			'isRedirectForum' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($forum) {
					return (bool) $forum->redirect_on;
				}
			],
			'redirectHits' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($forum) {
					return $forum->redirect_on ? (int) $forum->redirect_hits : NULL;
				}
			],
			'passwordProtected' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($forum) {
					return $forum->password !== NULL;
				}
			],
			'passwordRequired' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($forum) {
					if( $forum->password === NULL || Member::loggedIn()->inGroup( explode( ',', $forum->password_override ) ) )
					{
						return FALSE;
					}

					return TRUE;
				}
			]
		);

		// Duplicate fields that have different names in forums
		$forumFields['topicCount'] = $defaultFields['itemCount'];
		$forumFields['postCount'] = $defaultFields['commentCount'];
		$forumFields['subforums'] = $defaultFields['children'];
		$forumFields['topics'] = $defaultFields['items'];

		// Remove duplicated fields
		unset( $defaultFields['itemCount'] );
		unset( $defaultFields['commentCount'] );
		unset( $defaultFields['children'] );
		unset( $defaultFields['items'] );

		return array_merge( $defaultFields, $forumFields );		
	}

	/**
	 * Get the item type that goes with this node type
	 *
	 * @return	ObjectType
	 */
	public static function getItemType(): ObjectType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::topic();
	}

	/**
	 * Check for a password before returning items
	 *
	 * @param Model $forum
	 * @param array $args    Arguments passed to this resolver
	 * @return    int|ActiveRecordIterator
	 */
	protected static function items( Model $forum, array $args): int|ActiveRecordIterator
	{
		// If there's no password or our group is excluded, just continue
		if( $forum->password === NULL || Member::loggedIn()->inGroup( explode( ',', $forum->password_override ) ) )
		{
			return parent::items( $forum, $args );
		}

		if( !isset( $args['password'] ) || !Login::compareHashes( md5( $forum->password ), md5( $args['password'] ) ) )
		{
			throw new SafeException( 'INCORRECT_PASSWORD', 'GQL/0002/1', 403 );
		}
		
		return parent::items( $forum, $args );
	}

	/**
	 * Resolve last post author field
	 *
	 * @param 	Forum $forum
	 * @param 	array $args 	Arguments passed to this resolver
	 * @return	Member|null
	 */
	protected static function lastPostAuthor( Forum $forum, array $args) : Member|null
	{
		$lastComment = $forum->lastPost();

		if( $lastComment )
		{
			return $lastComment['author'];
		}

		return NULL;
	}

	/**
	 * Resolve last post date field
	 *
	 * @param Forum $forum
	 * @param array $args    Arguments passed to this resolver
	 * @return int|DateTime|null
	 */
	protected static function lastPostDate( Forum $forum, array $args ) : int|DateTime|null
	{
		$lastComment = $forum->lastPost();

		if( $lastComment )
		{
			return $lastComment['date'];
		}

		return NULL;
	}
}
