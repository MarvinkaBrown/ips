<?php
/**
 * @brief		GraphQL: API query controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Oct 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api\GraphQL;
use IPS\Api\GraphQL\Queries\ModuleAccess;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Core controller for GraphQL API
 * @todo maybe this shouldn't be a class since it only has a static method?
 */
abstract class AppQueries
{

	/**
	 * Get the supported query types in this app
	 *
	 * @param string $app
	 * @return    array
	 */
	public static function queries( string $app ): array
	{
		return [
			'moduleAccess' => new ModuleAccess($app)
		];
	}
}
