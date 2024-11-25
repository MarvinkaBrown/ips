<?php
/**
 * @brief		GraphQL: Forum query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api\GraphQL\Queries;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\calendar\api\GraphQL\Types\CalendarType;
use IPS\calendar\Calendar as CalendarClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Calendar query for GraphQL API
 */
class Calendar
{
    /*
     * @brief 	Query description
     */
    public static string $description = "Returns a calendar";

    /*
     * Query arguments
     */
    public function args(): array
    {
        return array(
            'id' => TypeRegistry::nonNull( TypeRegistry::id() ),
        );
    }

    /**
     * Return the query return type
     */
    public function type(): CalendarType
    {
        return \IPS\calendar\api\GraphQL\TypeRegistry::calendar();
    }

    /**
     * Resolves this query
     *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @param array $info
     * @return	CalendarClass
     */
    public function resolve( mixed $val, array$args, array $context, array $info): CalendarClass
    {
        $calendar = CalendarClass::load( $args['id'] );

        if( !$calendar->can( 'view', $context['member'] ) )
        {
            throw new OutOfRangeException;
        }
        return $calendar;
    }
}
