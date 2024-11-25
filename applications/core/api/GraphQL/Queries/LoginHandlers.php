<?php
/**
 * @brief		GraphQL: Login handler query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		30 Oct 2018
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\LoginType;
use IPS\Login;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login handler query for GraphQL API
 */
class LoginHandlers
{

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Return login handler data";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			
		);
	}

	/**
	 * Return the query return type
	 *
	 * @return ListOfType<LoginType>
	 */
	public function type() : ListOfType
	{
		return TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::login() );
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	array
	 */
	public function resolve( mixed $val, array $args ) : array
	{
		$login = new Login;
		return $login->buttonMethods();
	}
}