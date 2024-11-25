<?php
/**
 * @brief		Singleton Pattern
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Patterns;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Iterator;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Singleton Pattern
 */
class Singleton implements Iterator
{
	/**
	 * @brief	Singleton Instances
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static ?Singleton $instance = NULL;

	/**
	 * Get instance
	 *
	 * @return	static
	 */
	public static function i(): static
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		return static::$instance;
	}
	
	/**
	 * @brief	Data Store
	 */
	protected ?array $data = array();

	/**
	 * Magic Method: Get
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( mixed $key ) :mixed
	{	
		if( !isset( $this->data[ $key ] ) )
		{
			return NULL;
		}
		
		return $this->data[ $key ];
	}
	
	/**
	 * Magic Method: Set
	 *
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( mixed $key, mixed $value ) :void
	{
		$this->data[ $key ] = $value;
	}
	
	/**
	 * Magic Method: Isset
	 *
	 * @param	mixed	$key	Key
	 * @return	bool
	 */
	public function __isset( mixed $key ) :bool
	{
		return isset( $this->data[ $key ] );
	}
	
	/**
	 * Magic Method: Unset
	 *
	 * @param	mixed	$key	Key
	 * @return	void
	 */
	public function __unset( mixed $key ) :void
	{
		unset( $this->data[ $key ] );
	}
	
	/**
	 * Iterator: Rewind
	 *
	 * @return	void
	 */
	public function rewind(): void
	{
        reset( $this->data );
    }
    
    /**
     * Iterator: Current
     *
     * @return	mixed
     */
    public function current(): mixed
	{
        return current( $this->data );
    }
    
    /**
     * Iterator: Key
     *
     * @return	mixed
     */
    public function key(): mixed
	{
        return key( $this->data );
    }
    
    /**
     * Iterator: Next
     *
     * @return	void
     */
    public function next() :void
    {
       next( $this->data );
    }

    /**
     * Iterator: Valid
     *
     * @return	bool
     */
    public function valid() :bool
    {
    	return key( $this->data ) !== null;
    }
}