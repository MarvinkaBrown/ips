<?php
/**
 * @brief		GraphQL: Post Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\CommentType;
use IPS\Content\Comment;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PostType for GraphQL API
 */
class PostType extends CommentType
{
	/*
	 * @brief 	The item classname we use for this type
	 */
	protected static string $commentClass	= '\IPS\forums\Topic\Post';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'forums_Post';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'A post';

	/**
	 * Get the item type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	public static function getItemType(): ObjectType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::topic();
	}

	/**
	 * Return the fields available in this type
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		$defaultFields = parent::fields();
		$postFields = array(
			'topic' => [
				'type' => \IPS\forums\api\GraphQL\TypeRegistry::topic(),
				'resolve' => function ($post) {
					return $post->item();
				}
			],
			'answerVotes' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($post) {
					return $post->post_field_int;
				}
			],
			'isBestAnswer' => [
				'type' => TypeRegistry::boolean(),
				'description' => "Whether this post is the best answer in a question",
				'resolve' => function ($post) {
					return $post->post_bwoptions['best_answer'];
				}
			]
		);

		// Remove duplicated fields
		unset( $defaultFields['item'] );

		return array_merge( $defaultFields, $postFields );
	}

	/**
	 * Return the definite article, but without the item type
	 *
	 * @param Comment $post
	 * @param array $options
	 * @return    string
	 */
	public static function definiteArticleNoItem( Comment $post, array $options = array() ): string
	{
		$type = 'post_lc';
		return Member::loggedIn()->language()->addToStack( $type, FALSE, $options);
	}
}
