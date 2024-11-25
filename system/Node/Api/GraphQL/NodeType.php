<?php
/**
 * @brief		Base class for Nodes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Aug 2018
 */

namespace IPS\Node\Api\GraphQL;
use Exception;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\Fields\TagsEditField;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Item;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use function defined;
use function is_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base class for Nodes
 */
class NodeType extends ObjectType
{
	/**
	 * @brief 	The item classname we use for this type
	 * @var Model|string
	 */
	protected static string $nodeClass	= '\IPS\Node\Model';

	/*
	 * @brief 	GraphQL type name
	 */
	protected static string $typeName = 'core_Node';

	/*
	 * @brief 	GraphQL type description
	 */
	protected static string $typeDescription = 'A generic node';


	public function __construct()
	{
		$config = [
			'name' => static::$typeName,
			'description' => static::$typeDescription,
			'fields' => function () {
				return $this->fields();
			}
		];

		parent::__construct($config);
	}

	/**
	 * Get the fields that this type supports
	 *
	 * @return	array
	 */
	public function fields(): array
	{
		return array(
			'id' => [
				'type' => TypeRegistry::id(),
				'resolve' => function ($node) {
					return $node->_id;
				}
			],
			'name' => [
				'type' => TypeRegistry::string(),
				'resolve' => function ($node) {
					return $node->getTitleForLanguage( Member::loggedIn()->language() );
				}
			],
			'url' => [
				'type' => TypeRegistry::url(),
				'resolve' => function ($node) {
					return $node->url();
				}
			],
			'itemCount' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($node) {
					/* @var Item $contentItemClass */
					return static::$nodeClass::$contentItemClass::contentCount($node, TRUE, FALSE);
				}
			],
			'commentCount' => [
				'type' => TypeRegistry::int(),
				'resolve' => function ($node) {
					/* @var Item $contentItemClass */
					return static::$nodeClass::$contentItemClass::contentCount($node, FALSE, TRUE);
				}
			],
			'children' => [
				'type' => TypeRegistry::listOf( $this ),
				'resolve' => function ($node, $args, $context) {
					return static::children($node, $args, $context);
				}
			],
			'hasUnread' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($node) {
					/* @var Item $contentItemClass */
					return static::$nodeClass::$contentItemClass::containerUnread( $node );
				}
			],
			'items' => [
				'type' => TypeRegistry::listOf( static::getItemType() ),
				'args' => static::getItemType()::args(),
				'resolve' => function ($node, $args, $context) {
					return static::items($node, $args);
				}
			],
			'follow' => [
				'type' => TypeRegistry::follow(),
				'resolve' => function ($node) {
					if( isset( static::$followData ) && is_array( static::$followData ) ){
						return array_merge( static::$followData, array(
							'id' => $node->_id,
							'node' => $node,
							'nodeClass' => static::$nodeClass
						));
					}

					return NULL;
				}
			],
			'nodePermissions' => [
				'type' => new ObjectType([
					'name' => static::$typeName . '_nodePermissions',
					'fields' => static::getNodePermissionFields()
				]),
				'resolve' => function ($node) {
					return $node;
				}
			],
			'uploadPermissions' => [
				'type' => \IPS\core\api\GraphQL\TypeRegistry::attachmentPermissions(),
				'args' => [
					'postKey' => TypeRegistry::nonNull( TypeRegistry::string() ),
				],
				'resolve' => function( $node, $args, $context ) {
					return array( 'postKey' => $args['postKey'] );
				}
			],
			'tagPermissions' => TagsEditField::getDefinition(static::$typeName . '_tags')
		);
	}

	/**
	 * Get the comment type that goes with this item type
	 *
	 * @return	ObjectType
	 */
	public static function getItemType(): ObjectType
	{
		return \IPS\Content\Api\GraphQL\TypeRegistry::item();
	}

	/**
	 * Get the field config for the node permissions query
	 *
	 * @return	array
	 */
	public static function getNodePermissionFields(): array
	{
		return array(
			'canCreate' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($node, $args, $context) {
					return $node->can('add', Member::loggedIn(), FALSE);
				}
			],
			'itemsRequireApproval' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($node, $args, $context) {
					/* @var Item $contentItemClass */
					return static::$nodeClass::$contentItemClass::moderateNewItems( Member::loggedIn(), $node, FALSE );
				}
			],
			'commentsRequireApproval' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($node, $args, $context) {
					return null;
					//return static::$nodeClass::$contentItemClass::moderateNewComments( \IPS\Member::loggedIn(), FALSE );
				}
			],
			'reviewsRequireApproval' => [
				'type' => TypeRegistry::boolean(),
				'resolve' => function ($node, $args, $context) {
					return null;
					//return static::$nodeClass::$contentItemClass::moderateNewReviews( \IPS\Member::loggedIn(), FALSE );
				}
			]
		);
	}

	/**
	 * Resolve children field
	 *
	 * @param Model $node
	 * @param array $args
	 * @param array $context
	 * @return    array
	 */
	protected static function children( Model $node, array $args, array $context): array
	{
		return $node->children('view');
	}

	/**
	 * Resolve the topics field
	 *
	 * @param 	Model $node
	 * @param 	array $args 	Arguments passed to this resolver
	 * @return	ActiveRecordIterator|int
	 */
	protected static function items( Model $node, array $args): ActiveRecordIterator|int
	{
		try 
		{
			if( $args['orderBy'] === NULL )
			{
				$orderBy = $node->_sortBy;
			}
			else
			{
				/* @var Item $contentItemClass */
				/* @var array $databaseColumnMap */
				$orderBy = static::$nodeClass::$contentItemClass::$databaseColumnMap[ $args['orderBy'] ];
			}

			if( $args['orderBy'] === 'last_comment' )
			{
				$orderBy = is_array( $orderBy ) ? array_pop( $orderBy ) : $orderBy;
			}
		}
		catch (Exception $e)
		{
			$orderBy = 'title';
		}

		$where = array();

		/* @var Item $class */
		$class = static::$nodeClass::$contentItemClass;
		$sortBy = $class::$databaseTable . '.' . $class::$databasePrefix . "{$orderBy} {$args['orderDir']}";

		// Container
		$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['container'] . '=?', $node->_id );

		if( $args['honorPinned'] )
		{			
			$column = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['pinned'];
			$sortBy = "{$column} DESC, {$sortBy}";
		}

		$offset = max( $args['offset'], 0 );
		$limit = min( $args['limit'], 50 );

		return $class::getItemsWithPermission( $where, $sortBy, array( $offset, $limit ) );
	}
}