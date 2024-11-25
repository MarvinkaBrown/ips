<?php
/**
 * @brief		GraphQL: Stats query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Sept 2020
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\core\api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\StatsType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stats query for GraphQL API
 */
class Stats
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns community stats";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array();
	}

	/**
	 * Return the query return type
	 */
	public function type() : StatsType
	{
		return TypeRegistry::stats();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	mixed
	 */
	public function resolve( mixed $val, array $args, array $context ) : mixed
	{
		return $val;
	}
}
