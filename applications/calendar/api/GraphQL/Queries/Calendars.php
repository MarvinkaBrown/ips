<?php
/**
 * @brief		GraphQL: Forums query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api\GraphQL\Queries;
use GraphQL\Type\Definition\ListOfType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\calendar\Calendar;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Forums query for GraphQL API
 */
class Calendars
{
    /*
     * @brief 	Query description
     */
    public static string $description = "Returns a list of calendars";

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
        return TypeRegistry::listOf( \IPS\calendar\api\GraphQL\TypeRegistry::calendar() );
    }

    /**
     * Resolves this query
     *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @param array $info
     * @return	array
     */
    public function resolve( mixed $val, array $args, array $context, array $info): array
    {
        Calendar::loadIntoMemory('view', $context['member']);
        return Calendar::roots();
    }
}
