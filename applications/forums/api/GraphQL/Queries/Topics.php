<?php
/**
 * @brief		GraphQL: Topics query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Queries;
use Exception;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Db;
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use function count;
use function defined;
use function is_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Topics query for GraphQL API
 */
class Topics
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a list of topics";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'forums' => TypeRegistry::listOf( TypeRegistry::int() ),
			'offset' => [
				'type' => TypeRegistry::int(),
				'defaultValue' => 0
			],
			'limit' => [
				'type' => TypeRegistry::int(),
				'defaultValue' => 25
			],
			'orderBy' => [
				'type' => TypeRegistry::eNum([
					'name' => 'forums_fluid_order_by',
					'description' => 'Fields on which topics can be sorted',
					'values' => TopicType::getOrderByOptions()
				]),
				'defaultValue' => NULL // will use default sort option
			],
			'orderDir' => [
				'type' => TypeRegistry::eNum([
					'name' => 'forums_fluid_order_dir',
					'description' => 'Directions in which items can be sorted',
					'values' => [ 'ASC', 'DESC' ]
				]),
				'defaultValue' => 'DESC'
			],
			'honorPinned' => [
				'type' => TypeRegistry::boolean(),
				'defaultValue' => true
			]
		);
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<TopicType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\forums\api\GraphQL\TypeRegistry::topic() );
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @param 	array $context 	Context values
	 * @param	array $info
	 * @return	ActiveRecordIterator
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : ActiveRecordIterator
	{
		Forum::loadIntoMemory('view', Member::loggedIn() );

		$where = array( 'container' => array( array( 'forums_forums.password IS NULL' ) ) );
		$forumIDs = [];

		/* Are we filtering by forums? */
		if( isset( $args['forums'] ) && count( $args['forums'] ) )
		{
			foreach( $args['forums'] as $id )
			{
				$forum = Forum::loadAndCheckPerms( $id );
				$forumIDs[] = $forum->id;
			}

			if( count( $forumIDs ) )
			{
				$where['container'][] = array( Db::i()->in( 'forums_forums.id', array_filter( $forumIDs ) ) );
			}
		}

		/* Get sorting */
		try 
		{
			if( $args['orderBy'] === NULL )
			{
				$orderBy = 'last_post';
			}
			else
			{
				$orderBy = Topic::$databaseColumnMap[ $args['orderBy'] ];
			}

			if( $args['orderBy'] === 'last_comment' )
			{
				$orderBy = is_array( $orderBy ) ? array_pop( $orderBy ) : $orderBy;
			}
		}
		catch (Exception $e)
		{
			$orderBy = 'last_post';
		}

		$sortBy = Topic::$databaseTable . '.' . Topic::$databasePrefix . "{$orderBy} {$args['orderDir']}";
		$offset = max( $args['offset'], 0 );
		$limit = min( $args['limit'], 50 );

		/* Figure out pinned status */
		if ( $args['honorPinned'] )
		{
			$column = Topic::$databaseTable . '.' . Topic::$databasePrefix . Topic::$databaseColumnMap['pinned'];
			$sortBy = "{$column} DESC, {$sortBy}";
		}

		return Topic::getItemsWithPermission( $where, $sortBy, array( $offset, $limit ), 'read' );
	}
}
