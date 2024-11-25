<?php

/**
 * @brief        Event
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/18/2023
 */

namespace IPS\Events;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Dispatcher;
use IPS\Log;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Event
{
	public function __construct(){}

	/**
	 * @brief	Store all listeners for each class type
	 * @var array
	 */
	protected static array $_listeners = array();

	/**
	 * Fire an event and call all listener methods
	 *
	 * @param string 		$event
	 * @param object 		$object
	 * @param array|null 	$payload
	 * @return void
	 */
	public static function fire( string $event, object $object, ?array $payload = NULL ) : void
	{
		/* Don't fire any events during an upgrade */
		if( Dispatcher::hasInstance() AND Dispatcher::i()->controllerLocation === 'setup' )
		{
			return;
		}

		$params = is_array( $payload ) ? array_values( $payload ) : array();
		foreach( static::loadListeners( $object ) as $listener )
		{
			if( method_exists( $listener, $event ) )
			{
                /* Don't log unless we have something to run */
                Log::debug(  print_r( array_merge(['event' => $event], ['listener' => $listener::class ], ['payload' => $payload]) , TRUE ), 'event_fire' );

				try
				{
					$listener->$event( $object, ...$params );
				}
				catch( Exception $e )
				{
					/* See the error if we're IN_DEV */
					if ( \IPS\IN_DEV )
					{
						throw $e;
					}

					Log::log( "{$e->getMessage()} exception in " . get_class( $listener ), 'event_fire' );
				}
			}
		}
	}

	/**
	 * Load listeners for a particular object
	 *
	 * @param object $object
	 * @return array
	 */
	protected static function loadListeners( object $object ) : array
	{
		$class = get_class( $object );

		if( !isset( static::$_listeners[ $class ] ) )
		{
			/* No listening on cloud apps */
			if( static::isCloudClass( $class ) )
			{
				static::$_listeners[ $class ] = [];
				return [];
			}

			$allListeners = ListenerType::allListeners();
			if( isset( $allListeners[ $class ] ) )
			{
				static::$_listeners = [];
				foreach( $allListeners[ $class ] as $listener )
				{
					if( class_exists( $listener ) )
					{
						static::$_listeners[ $class ][] = new $listener;
					}
				}
			}

			/* Check for underlying classes. This is mainly used for Pages,
			which doesn't have pre-determined classnames. */
			foreach( $allListeners as $extendedClass => $listners )
			{
				if( is_subclass_of( $class, $extendedClass ) )
				{
					foreach( $listners as $listener )
					{
						if( class_exists( $listener ) )
						{
							static::$_listeners[ $class ][] = new $listener;
						}
					}
				}
			}
		}

		return static::$_listeners[ $class ] ?? array();
	}

	/**
	 * Check if this is part of the cloud app
	 *
	 * @param string $class
	 * @return bool
	 */
	protected static function isCloudClass( string $class ) : bool
	{
		if( is_subclass_of( $class, Item::class ) AND $class::$application == 'cloud' )
		{
			return true;
		}

		if( is_subclass_of( $class, Comment::class ) )
		{
			$itemClass = $class::$itemClass;
			if( $itemClass::$application == 'cloud' )
			{
				return true;
			}
		}

		return false;
	}
}