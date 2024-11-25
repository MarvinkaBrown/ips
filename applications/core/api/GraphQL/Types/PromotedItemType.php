<?php
/**
 * @brief		GraphQL: PromotedItemType group Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Review;
use IPS\Member;
use IPS\Node\Model;
use function count;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PromotedItemType for GraphQL API
 */
class PromotedItemType extends ObjectType
{
	/**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_PromotedItem',
			'description' => 'Promoted Item',
			'fields' => function () {
				return [
					'id' => [
						'type' => TypeRegistry::id(),
						'description' => 'Promote ID',
						'resolve' => function ($item) {
							return $item->id;
						}
					],
					'addedBy' => [
						'type' => \IPS\core\api\GraphQL\TypeRegistry::member(),
						'description' => 'Who promoted this item',
						'resolve' => function ($item) {
							return Member::load( $item->added_by );
						}
					],
					'images' => [
						'type' => TypeRegistry::listOf( TypeRegistry::string() ),
						'description' => 'Photos attached to the promoted item',
						'resolve' => function ($item) {
							$images = array();

							if( count( $item->imageObjects() ) )
							{
								foreach( $item->imageObjects() as $file )
								{
									$images[] = (string) $file->url;
								}
							}

							return $images;
						}
					],
					'title' => [
						'type' => TypeRegistry::string(),
						'description' => 'Promoted title',
						'resolve' => function ($item) {
							return $item->ourPicksTitle;
						}
					],
					'url' => [
						'type' => TypeRegistry::url(),
						'description' => 'URL to original item',
						'resolve' => function ($item) {
							return $item->object()->url();
						}
					],
					'item' => [
						'type' => new UnionType([
							'name' => 'core_PromotedItem_TypeUnion',
							'types' => [
								Content\Api\GraphQL\TypeRegistry::comment(),
								Content\Api\GraphQL\TypeRegistry::item(),
								\IPS\Node\Api\GraphQL\TypeRegistry::node()
							],
							'resolveType' => function ($item) {
								if ( $item instanceof Comment )
								{
									return Content\Api\GraphQL\TypeRegistry::comment();
								}
								else if ( $item instanceof Item )
								{
									return Content\Api\GraphQL\TypeRegistry::item();
								}
								else if( $item instanceof Model )
								{
									return \IPS\Node\Api\GraphQL\TypeRegistry::node();
								}
								return null;
							}
						]),
						'description' => 'The original item',
						'resolve' => function ($item) {
							return $item->object();
						}
					],
					'itemType' => [
						'type' => TypeRegistry::eNum([
							'name' => 'core_PromotedItem_Type',
							'values' => ['COMMENT', 'REVIEW', 'ITEM', 'NODE']
						]),
						'description' => "What kind of content is this item?",
						'resolve' => function ($item) {
							if ( $item->object() instanceof Comment )
							{
								return 'COMMENT';
							}
							else if ( $item->object() instanceof Review )
							{
								return 'REVIEW';
							}
							else if ( $item->object() instanceof Content )
							{
								return 'ITEM';
							}
							else if ( $item->object() instanceof Model )
							{
								return 'NODE';
							}

							return NULL;
						}
					],
					'description' => [
						'type' => TypeRegistry::string(),
						'description' => 'Promoted blurb provided by staff',
						'resolve' => function ($item) {
							$text = trim( $item->getText(false) );

							if( $text ){
								return $text;
							}

							return NULL;
						}
					],
					'timestamp' => [
						'type' => TypeRegistry::int(),
						'description' => 'Timestamp for when item was promoted',
						'resolve' => function ($item) {
							return $item->sent;
						}
					],
					'reputation' => [
						'type' => TypeRegistry::reputation(),
						'resolve' => function ($item) {
							return $item->objectReactionClass;
						}
					],
					'dataCount' => [
						'type' => new ObjectType([
							'name' => 'core_ourPicks_dataCount',
							'fields' => [
								'count' => [
									'type' => TypeRegistry::int(),
									'resolve' => function ($array) {
										return $array['count'];
									}
								],
								'words' => [
									'type' => TypeRegistry::string(),
									'resolve' => function ($array) {
										return $array['words'];
									}
								]
							]
						]),
						'resolve' => function ($item) {
							if( $item->objectDataCount ){
								return $item->objectDataCount;
							}

							return NULL;
						}
					]
				];
			}
		];

		parent::__construct($config);
	}
}