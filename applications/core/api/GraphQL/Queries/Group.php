<?php
/**
 * @brief		GraphQL: Group query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\GroupType;
use IPS\Member\Group as GroupClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group query for GraphQL API
 */
class Group
{

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a member group";

	/*
	 * @brief 	Query arguments
	 */
	public function args(): array
	{
		return array(
			'id' => TypeRegistry::nonNull( TypeRegistry::id() )
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : GroupType
	{
		return \IPS\core\api\GraphQL\TypeRegistry::group();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	GroupClass
	 */
	public function resolve( mixed $val, array $args, array $context ) : GroupClass
	{
		try
		{
			return GroupClass::load( $args['id'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'INVALID_GROUP', '1invalid_group_graph', 400 );
		}
	}
}
