<?php
/**
 * @brief		GraphQL: Blog queries
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL;
use IPS\blog\api\GraphQL\Queries\Blog;
use IPS\blog\api\GraphQL\Queries\Blogs;
use IPS\blog\api\GraphQL\Queries\Entries;
use IPS\blog\api\GraphQL\Queries\Entry;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Blog queries for GraphQL API
 */
abstract class Query
{
    /**
     * Get the supported query types in this app
     *
     * @return	array
     */
    public static function queries(): array
    {
        return [
            'blogs' => new Blogs(),
            'blog' => new Blog(),
            'entries' => new Entries(),
           'entry' => new Entry(),
        ];
    }
}
