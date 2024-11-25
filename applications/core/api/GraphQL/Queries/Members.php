<?php
/**
 * @brief        GraphQL: Members query
 * @author        <a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) 2001 - 2016 Invision Power Services, Inc.
 * @license        http://www.invisionpower.com/legal/standards/
 * @package        IPS Community Suite
 * @since        26 01 2023
 * @version        SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;

use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\MemberType;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use function defined;
use function in_array;
use function is_int;

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ).' 403 Forbidden' );
	exit;
}

/**
 * Members query for GraphQL API
 */
class Members
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = 'Returns a list of members';

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
		'members' => TypeRegistry::listOf( TypeRegistry::int() ),
		'offset' => [
			'type' => TypeRegistry::int(),
			'defaultValue' => 0
		],
		'limit' => [
			'type' => TypeRegistry::int(),
			'defaultValue' => 25
		],
		'orderBy' => [
			'type' => TypeRegistry::eNum( [
									  'name' => 'member_order_by',
									  'description' => 'Fields on which topics can be sorted',
									  'values' => MemberType::getOrderByOptions()
									  ] ),
			'defaultValue' => NULL // will use default sort option
		],
		'orderDir' => [
		'type' => TypeRegistry::eNum( [
									  'name' => 'member_order_dir',
									  'description' => 'Directions in which items can be sorted',
									  'values' => [ 'ASC', 'DESC' ]
									  ] ),
		'defaultValue' => 'DESC'
		],

		);
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<MemberType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::member() );
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val   Value passed into this resolver
	 * @param array $args   Arguments
	 * @param array $context   Context values
	 * @param array $info
	 * @return    ActiveRecordIterator
	 */
	public function resolve( mixed $val, array $args, array $context, array $info ) : ActiveRecordIterator
	{
		$where = [];
		$sortBy = ( isset( $args['orderBy'] ) and in_array( $args['orderBy'], MemberType::getOrderByOptions() ) ) ? $args['orderBy'] : 'member_id';
		$sortDir = ( isset( $args['orderDir'] ) and in_array( mb_strtolower( $args['orderDir'] ), array( 'asc', 'desc' ) ) ) ? $args['orderDir'] : 'desc';
		$limit =( isset( $args['orderDir'] ) and is_int( $args['limit'] ) ) ? $args['limit'] : 25;

		$query = Db::i()->select( '*', 'core_members', $where, "{$sortBy} {$sortDir}", $limit );
		return new ActiveRecordIterator( $query, 'IPS\Member' );
	}
}
