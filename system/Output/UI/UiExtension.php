<?php

namespace IPS\Output\UI;

/* To prevent PHP errors (extending class does not exist) revealing path */


use IPS\Application;
use IPS\Content;
use IPS\Node\Model;
use IPS\Patterns\Singleton;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class UiExtension extends Singleton
{
	/**
	 * @brief	Singleton Instances
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static ?Singleton $instance = NULL;

	/**
	 * @var array
	 */
	protected static array $extensions = [];

	/**
	 * Load all available extensions
	 *
	 * @param string $type
	 * @return void
	 */
	protected function loadExtensions( string $type ) : void
	{
		if( !isset( static::$extensions[ $type ] ) )
		{
			static::$extensions[ $type ] = [];
			foreach ( Application::allExtensions( 'core', 'UI' . $type, FALSE, 'core' ) as $key => $extension )
			{
				static::$extensions[$type][ $key ] = $extension;
			}
		}
	}

	/**
	 * Load extensions for this type
	 *
	 * @param string $type
	 * @return array
	 */
	protected function extensions( string $type ) : array
	{
		static::loadExtensions( $type );
		return static::$extensions[ $type ];
	}

	/**
	 * Checks all UI extensions to verify that the classes are supported
	 * Return a list of all unsupported classes, or NULL if all classes are valid
	 *
	 * @param Application $application
	 * @return array|null
	 */
	public static function unsupportedExtensions( Application $application ) : ?array
	{
		$return = [];
		foreach( $application->extensions( 'core', 'UINode', false ) as $ext )
		{
			/* @var Model $nodeClass */
			$nodeClass = $ext::$class ?? null;
			if( $nodeClass AND !$nodeClass::$canBeExtended )
			{
				$return[] = $nodeClass;
			}
		}

		return count( $return ) ? $return : null;
	}

	/**
	 * Run a UI extension method
	 *
	 * @param Content|Model|string $object
	 * @param string $method
	 * @param array|null $payload
	 * @return array
	 */
	public function run( Content|Model|string $object, string $method, ?array $payload=array() ) : array
	{
		$className = is_string( $object ) ? $object : get_class( $object );
        $baseClass = null;
		switch( $className )
		{
            case '':
            default:
                return [];

			case is_subclass_of( $className, Model::class ):

				/* If this node class cannot be extended, just return and do nothing */
				if( !$className::$canBeExtended )
				{
					return [];
				}

				$type = 'Node';
                $baseClass = Model::class;
				break;
			case is_subclass_of( $className, Content\Item::class ):
				$type = 'Item';
                $baseClass = Content\Item::class;
				break;
			case is_subclass_of( $className, Content\Review::class ):
				$type = 'Review';
                $baseClass = Content\Review::class;
				break;
			case is_subclass_of( $className, Content\Comment::class ):
				$type = 'Comment';
                $baseClass = Content\Comment::class;
				break;
		}

		$return = [];

        /* If we are working with an object (Item/Comment/Node), put it first in the parameter list */
		$params = is_array( $payload ) ? array_values( $payload ) : array();
		array_unshift( $params, is_string( $object ) ? null : $object );

		foreach( $this->extensions( $type ) as $extension )
		{
            /* Use subclass to account for classes in Pages */
			if( isset( $extension::$class ) AND $className != $extension::$class AND !is_subclass_of( $className, $extension::$class ) )
			{
                continue;
			}

            /* Block extension of base classes */
            if( $baseClass !== null AND $extension::$class == $baseClass )
            {
                continue;
            }

            if( $response = $extension->$method( ...$params ) )
			{
				if( is_array( $response ) )
				{
					$return = array_merge( $return, $response );
				}
				else
				{
					$return[] = $response;
				}
			}
		}

		return $return;
	}
}