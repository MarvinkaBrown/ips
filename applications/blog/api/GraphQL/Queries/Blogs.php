<?php
/**
 * @brief		GraphQL: Blog query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\blog\Blog;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Blogs query for GraphQL API
 */
class Blogs
{
    /*
     * @brief 	Query description
     */
    public static string $description = "Returns a list of blogs";

    /*
     * Query arguments
     */
    public function args(): array
    {
        return array(
            'id' => TypeRegistry::id()
        );
    }

    /**
     * Return the query return type
     */
    public function type(): ListOfType
    {
        return TypeRegistry::listOf( \IPS\blog\api\GraphQL\TypeRegistry::blog() );
    }

    /**
     * Resolves this query
     *
     * @param 	mixed $val 	Value passed into this resolver
     * @param 	array $args 	Arguments
     * @param 	array $context 	Context values
	 * @param 	array $info
     * @return	array
     */
    public function resolve( mixed $val, array $args, array $context, array $info): array
    {
        Blog::loadIntoMemory('view', $context['member']);
        return Blog::roots();
    }
}
