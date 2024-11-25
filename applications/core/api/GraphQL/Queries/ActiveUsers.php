<?php
/**
 * @brief		GraphQL: ActiveUsers query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\Application\Module;
use IPS\core\api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\ActiveUsersType;
use IPS\Member;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * OurPicks query for GraphQL API
 */
class ActiveUsers
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns active user information";

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
	public function type() : ActiveUsersType
	{
		return TypeRegistry::activeUsers();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @return	mixed
	 */
	public function resolve( mixed $val ) : mixed
	{
		if ( !Member::loggedIn()->canAccessModule( Module::get( 'core', 'online', 'front' ) ) )
		{
			return NULL;
		}

		// There's no real value here, we're just passing on to the next resolver in the chain
		return $val;
	}
}
