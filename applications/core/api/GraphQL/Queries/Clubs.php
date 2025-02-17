<?php
/**
 * @brief		GraphQL: Clubs query
 * @author		<a href='https://invisioncommunity.com/'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2023 Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Cloud
 * @since       09 February 2023
 */

namespace IPS\core\api\GraphQL\Queries;

use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\ClubType;
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
 * Club query for GraphQL API
 */
class Clubs
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = 'Returns a list of clubs';

 	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
		'clubs' => TypeRegistry::listOf( TypeRegistry::int() ),
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
									  'name' => 'clubs_order_by',
									  'description' => 'Fields on which topics can be sorted',
									  'values' => ClubType::getOrderByOptions()
									  ] ),
			'defaultValue' => NULL // will use default sort option
		],
		'orderDir' => [
		'type' => TypeRegistry::eNum( [
									  'name' => 'clubs_order_dir',
									  'description' => 'Directions in which items can be sorted',
									  'values' => [ 'ASC', 'DESC' ]
									  ] ),
		'defaultValue' => 'DESC'
		],

		);
	}



	/**
	 * Return the query return type
	 */
	public function type(): ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::club() );
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return    ActiveRecordIterator[\IPS\Member\Club]
	 */
	public function resolve( mixed $val, array $args, array $context ): ActiveRecordIterator
	{
		$where = [];
		$sortBy = ( isset( $args['orderBy'] ) and in_array( $args['orderBy'], ClubType::getOrderByOptions() ) ) ? $args['orderBy'] : 'name';
		$sortDir = ( isset( $args['orderDir'] ) and in_array( mb_strtolower( $args['orderDir'] ), array( 'asc', 'desc' ) ) ) ? $args['orderDir'] : 'desc';
		$limit =( isset( $args['orderDir'] ) and is_int( $args['limit'] ) ) ? $args['limit'] : 25;

		$query = Db::i()->select( '*', 'core_clubs', $where, "{$sortBy} {$sortDir}", $limit );
		return new ActiveRecordIterator( $query, 	'\IPS\Member\Club' );
	}
}
