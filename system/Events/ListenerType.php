<?php

/**
 * @brief        ListenerAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/18/2023
 */

namespace IPS\Events;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\Events\ListenerType\ContentListenerType;
use IPS\Events\ListenerType\InvoiceListenerType;
use IPS\Events\ListenerType\NodeListenerType;
use IPS\Events\ListenerType\PackageListenerType;
use IPS\Events\ListenerType\MemberListenerType;
use OutOfRangeException;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class ListenerType
{
	public function __construct(){}

	/**
	 * @brief	[Required] The class that is handled by this listener
	 * @var string
	 */
	public static string $class;

	/**
	 * @brief	Determine whether this listener requires an explicitly set class
	 * 			Example: MemberListeners are always for \IPS\Member, but ContentListeners
	 * 			will require a specific class.
	 * @var bool
	 */
	public static bool $requiresClassDeclaration = FALSE;

	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array();

	/**
	 * Check if this listener supports this particular object
	 *
	 * @param string $object
	 * @return bool
	 */
	public static function supportsObject( string $object ) : bool
	{
		foreach( static::$supportedBaseClasses as $class )
		{
			if( $object == $class )
			{
				return TRUE;
			}

			/* If we require a class declaration, then we are working with
			base classes, like \IPS\Content\Item, which will not necessarily be
			a direct match to the base */
			if( static::$requiresClassDeclaration AND is_subclass_of( $object, $class ) )
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Return the class that is extended by this listener
	 *
	 * @return string
	 */
	public static function getExtendedClass() : string
	{
		if( static::$requiresClassDeclaration )
		{
			return static::$class;
		}

		return static::$supportedBaseClasses[0];
	}

	/**
	 * Return all listeners
	 *
	 * @return array
	 */
	public static function allListeners() : array
	{
		try
		{
			return Store::i()->listeners;
		}
		catch( OutOfRangeException ){}

		$listeners = [];
		foreach( Application::allListeners() as $listenerClass )
		{
			$extendedClass = $listenerClass::getExtendedClass();
			if( !isset( $listeners[ $extendedClass ] ) )
			{
				$listeners[ $extendedClass ] = [];
			}
			$listeners[ $extendedClass ][] = $listenerClass;
		}

		Store::i()->listeners = $listeners;
		return $listeners;
	}

	/**
	 * Return all available listener types
	 *
	 * @return array
	 */
	public static function listenerTypes() : array
	{
		return array(
            'ContentListenerType' => ContentListenerType::class,
			'InvoiceListenerType' => InvoiceListenerType::class,
			'MemberListenerType' => MemberListenerType::class,
			'NodeListenerType' => NodeListenerType::class,
			'PackageListenerType' => PackageListenerType::class,
		);
	}
}