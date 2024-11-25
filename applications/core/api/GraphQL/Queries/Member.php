<?php
/**
 * @brief		GraphQL: Member query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\MemberType;
use IPS\Member as MemberClass;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member query for GraphQL API
 */
class Member
{

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Return a member";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'id' => TypeRegistry::id(),
			'loggedIn' => TypeRegistry::boolean()
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : MemberType
	{
		return \IPS\core\api\GraphQL\TypeRegistry::member();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	MemberClass|null
	 */
	public function resolve( mixed $val, array $args, array $context ) : ?MemberClass
	{
		if( isset( $args['loggedIn'] ) ){
			return MemberClass::loggedIn();
		} elseif ( isset( $args['id'] ) ){
			return MemberClass::load( $args['id'] );
		}

		return null;
	}
}
