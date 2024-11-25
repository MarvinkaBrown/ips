<?php
/**
 * @brief		GraphQL: OurPicks query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\PromotedItemType;
use IPS\core\Feature;
use function count;
use function defined;
use function is_int;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * OurPicks query for GraphQL API
 */
class OurPicks
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns promoted items";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'count' => TypeRegistry::int()
		);
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<PromotedItemType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::promotedItem() );
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	array|null
	 */
	public function resolve( mixed $val, array $args, array $context ) : ?array
	{
		$limit = 7;
		if( isset( $args['count'] ) && is_int( $args['count'] ) )
		{
			$limit = $args['count'];
		}

		$items = Feature::internalStream( $limit );
		if( !count( $items ) )
		{
			return NULL;
		}

		return $items;
	}
}
