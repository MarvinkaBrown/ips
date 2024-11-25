<?php
/**
 * @brief		GraphQL: Forums queries
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL;
use IPS\forums\api\GraphQL\Queries\Forum;
use IPS\forums\api\GraphQL\Queries\Forums;
use IPS\forums\api\GraphQL\Queries\Post;
use IPS\forums\api\GraphQL\Queries\Topic;
use IPS\forums\api\GraphQL\Queries\Topics;
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
			'forums' => new Forums(),
			'forum' => new Forum(),
			'topics' => new Topics(),
			'topic' => new Topic(),
			'post' => new Post()
		];
	}
}
