<?php
/**
 * @brief		GraphQL: Gallery queries
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Feb 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\api\GraphQL;
use IPS\gallery\api\GraphQL\Queries\Image;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forums queries for GraphQL API
 */
abstract class Query
{
	/**
	 * Get the supported query types in this app
	 *
	 * @return	array
	 */
	public static function queries() : array
	{
		return [
			'image' => new Image(),
		];
	}
}
