<?php
/**
 * @brief		GraphQL: Settings query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\core\api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\SettingsType;
use IPS\Settings as SettingsClass;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings query for GraphQL API
 */
class Settings
{

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns values of system settings (subject to the setting being exposed by the API)";

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
	public function type() : SettingsType
	{
		return TypeRegistry::settings();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	SettingsClass
	 */
	public function resolve( mixed $val, array $args, array $context ) : SettingsClass
	{
		return SettingsClass::i();
	}
}
