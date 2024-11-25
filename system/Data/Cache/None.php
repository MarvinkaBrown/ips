<?php
/**
 * @brief		Dummy Cache Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Sept 2013
 */

namespace IPS\Data\Cache;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dummy Storage Class
 */
class None extends Cache
{
	/**
	 * Constructor
	 *
	 * @param	array	$configuration	Configuration
	 * @return	void
	 * @note	Overridden for performance reasons
	 */
	public function __construct( array $configuration )
	{
	}

	/**
	 * Magic Method: Get
	 *
	 * @param string $key	Key
	 * @return	string	Value from the _datastore
	 * @throws	OutOfRangeException
	 * @note	Overridden for performance reasons
	 */
	public function __get( string $key ): string
	{
		throw new OutOfRangeException;
	}

	/**
	 * Magic Method: Set
	 *
	 * @param string $key	Key
	 * @param string $value	Value
	 * @return	void
	 * @note	Overridden for performance reasons
	 */
	public function __set( string $key, mixed $value )
	{
	}

	/**
	 * Magic Method: Isset
	 *
	 * @param string $key	Key
	 * @return	bool
	 * @note	Overridden for performance reasons
	 */
	public function __isset( string $key ): bool
	{
		return FALSE;
	}

	/**
	 * Magic Method: Unset
	 *
	 * @param string $key	Key
	 * @return	void
	 * @note	Overridden for performance reasons
	 */
	public function __unset( string $key )
	{
	}

	/**
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported(): bool
	{
		return TRUE;
	}
	
	/**
	 * Abstract Method: Get
	 *
	 * @param string $key	Key
	 * @return	string	Value from the _datastore
	 */
	protected function get( string $key ): string
	{
		throw new RuntimeException;
	}
	
	/**
	 * Get value using cache method if available or falling back to the database
	 *
	 * @param string $key	Key
	 * @param bool $fallback	Use database if no caching method is available?
	 * @return	mixed
	 * @throws	OutOfRangeException
	 */
	public function getWithExpire( string $key, bool $fallback=FALSE ): mixed
	{
		if ( $fallback )
		{
			try
			{
				return json_decode( Db::i()->select( 'cache_value', 'core_cache', array( 'cache_key=? AND cache_expire>?', $key, time() ) )->first(), TRUE );
			}
			catch ( UnderflowException $e )
			{
				throw new OutOfRangeException;
			}
		}
		else
		{
			throw new OutOfRangeException;
		}
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param string $key	Key
	 * @param string $value	Value
	 * @return	bool
	 */
	protected function set( string $key, string $value ): bool
	{
		return FALSE;
	}
	
	/**
	 * Store value using cache method if available or falling back to the database
	 *
	 * @param string $key		Key
	 * @param	mixed			$value		Value
	 * @param	DateTime	$expire		Expiration if using database
	 * @param bool $fallback	Use database if no caching method is available?
	 * @return	bool
	 */
	public function storeWithExpire(string $key, mixed $value, DateTime $expire, bool $fallback=FALSE): bool
	{
		if ( $fallback )
		{
			Db::i()->replace( 'core_cache', array(
				'cache_key'		=> $key,
				'cache_value'	=> json_encode( $value ),
				'cache_expire'	=> $expire->getTimestamp()
			) );

			$this->_data[ $key ] = $value;
			$this->_exists[ $key ] = $key;

			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	protected function exists( string $key ): bool
	{
		return FALSE;
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	protected function delete( string $key ): bool
	{
		return TRUE;
	}
	
	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll() : void
	{
		parent::clearAll();
		try
		{
			Db::i()->delete( 'core_cache' );
		}
		catch ( Exception $e ){}
	}
}