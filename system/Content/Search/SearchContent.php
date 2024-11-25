<?php

/**
 * @brief        SearchContent
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/3/2023
 */

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content;
use function get_class;
use function is_string;
use function in_array;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class SearchContent
{
	/**
	 * @var array|null
	 */
	protected static ?array $_searchableClasses = null;

	/**
	 * Returns all searchable classes
	 *
	 * @return array
	 */
	public static function searchableClasses() : array
	{
		if( static::$_searchableClasses === null )
		{
			$classes = array();
			foreach( Application::allExtensions( 'core', 'SearchContent', false, null, null, false ) as $extension )
			{
				$classes = array_merge( $classes, $extension::supportedClasses() );
			}
			static::$_searchableClasses = $classes;
		}
		return static::$_searchableClasses;
	}

	/**
	 * Determines if the class is searchable
	 *
	 * @param string|object $object
	 * @return bool
	 */
	public static function isSearchable( string|object $object ) : bool
	{
		$class = is_string( $object ) ? $object : get_class( $object );
		return in_array( $class, static::searchableClasses() );
	}

	/**
	 * @var array
	 */
	protected static array $_extensions = array();

	/**
	 * Returns the extension that handles this class
	 *
	 * @param Content|string $object
	 * @param bool $init
	 * @return SearchContentAbstract|null
	 */
	public static function extension( Content|string $object, bool $init = TRUE ) : SearchContentAbstract|null
	{
		$classname = is_string( $object ) ? $object : get_class( $object );
		if( !isset( static::$_extensions[ $classname ] ) )
		{
			foreach( Application::allExtensions( 'core', 'SearchContent', false, null, null, false ) as $extension )
			{
				if( in_array( $classname, $extension::supportedClasses() ) )
				{
					static::$_extensions[ $classname ] = new $extension;
					break;
				}
			}
		}

		/* Make sure to set the extension with whatever object we are working with */
		if( isset( static::$_extensions[ $classname ] ) )
		{
			if( $object instanceof Content AND $init )
			{
				static::$_extensions[ $classname ]->setObject( $object );
			}

			return static::$_extensions[ $classname ];
		}

		return null;
	}
}