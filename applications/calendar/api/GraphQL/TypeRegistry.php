<?php
/**
 * @brief		GraphQL: Types registry
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api\GraphQL;
use IPS\calendar\api\GraphQL\Types\CalendarType;
use IPS\calendar\api\GraphQL\Types\CommentType;
use IPS\calendar\api\GraphQL\Types\EventType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}


class TypeRegistry
{
    /**
     * The calendar type instance
     * @var CalendarType
     */
    protected static CalendarType $calendar;

    /**
     * The event type instance
     * @var EventType
     */
    protected static EventType $event;

    /**
     * The event comment instance
     * @var CommentType
     */
    protected static CommentType $comment;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Defined to suppress static warnings
    }

    /**
     * @return CalendarType
     */
    public static function calendar() : CalendarType
    {
        return self::$calendar ?? (self::$calendar = new CalendarType());
    }

    /**
     * @return EventType
     */
    public static function event() : EventType
    {
        return self::$event ?? (self::$event = new EventType());
    }

    public static function comment() : CommentType
    {
        return self::$comment ?? ( self::$comment = new CommentType() );
    }
}