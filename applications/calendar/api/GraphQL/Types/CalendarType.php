<?php
/**
 * @brief		GraphQL: Blog Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\calendar\api\GraphQL\TypeRegistry;
use IPS\calendar\Calendar;
use IPS\Node\Api\GraphQL\NodeType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * CalendarType for GraphQL API
 */
class CalendarType extends NodeType
{
    /*
     * @brief 	The item classname we use for this type
     */
    protected static string $nodeClass	= Calendar::class;

    /*
     * @brief 	GraphQL type name
     */
    protected static string $typeName = 'calendar_Calendar';

    /*
     * @brief 	GraphQL type description
     */
    protected static string $typeDescription = 'A calendar';

    /*
     * @brief 	Follow data passed in to FollowType resolver
     */
    protected static array $followData = array('app' => 'calendar', 'area' => 'calendar');


    /**
     * Get the item type that goes with this node type
     *
     * @return	ObjectType
     */
    public static function getItemType(): ObjectType
	{
        return TypeRegistry::event();
    }
}

