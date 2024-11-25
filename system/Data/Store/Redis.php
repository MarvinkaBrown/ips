<?php
/**
 * @brief		Redis Storage Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 October 2017
 */

namespace IPS\Data\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Redis as RedisClass;
use RedisException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis Storage Class
 */
class Redis extends Store
{
	/**
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported(): bool
	{
		return class_exists('\\Redis');
	}
	
	/**
	 * Redis key
	 */
	protected string|false $_redisKey;
	
	/**
	 * Constructor
	 *
	 * @param	array	$configuration	Configuration to use
	 * @return	void
	 */
	public function __construct( array $configuration )
	{
		try
		{
			$writeConnection = RedisClass::i()->connection('write');
			$readConnection = RedisClass::i()->connection('read');

			if( !$writeConnection OR !$readConnection )
			{
				throw new RedisException;
			}

			$this->_redisKey = false;
		}
		catch( RedisException $e )
		{
			throw new Exception;
		}
	}
		
	/**
	 * Get random string used in the keys to identify this site compared to other sites
	 *
	 * @return  string|FALSE    Value from the _datastore; FALSE if key doesn't exist
	 */
	protected function _getRedisKey(): string|FALSE
	{
		if ( !$this->_redisKey )
		{
			/* Last access ensures that the data is not stale if we fail back to MySQL and then go back to Redis later */
			if ( !( $this->_redisKey = RedisClass::i()->get( 'redisKey_store' ) ) OR ! RedisClass::i()->get( 'redisStore_lastAccess' ) )
			{
				$this->_redisKey = md5( mt_rand() );
				RedisClass::i()->setex( 'redisKey_store', 604800, $this->_redisKey );
				RedisClass::i()->setex( 'redisStore_lastAccess', ( 3 * 3600 ), time() );
			}
		}

		return $this->_redisKey . '_str_';
	}
	
	/**
	 * @brief	Cache
	 */
	protected static array $cache = array();
	
	/**
	 * @brief	Already updated lastAccess?
	 */
	protected static bool $updatedLastAccess = FALSE;
	
	/**
	 * Abstract Method: Get
	 *
	 * @param   string          $key	Key
	 * @return  string|FALSE    Value from the _datastore; FALSE if key doesn't exist
	 */
	public function get( string $key ): string|FALSE
	{
		if( array_key_exists( $key, static::$cache ) )
		{
			return static::$cache[ $key ];
		}

		try
		{
			/* Set the last access time */
			if ( static::$updatedLastAccess === FALSE )
			{
				RedisClass::i()->setex( 'redisStore_lastAccess', ( 3 * 3600 ), time() );
				static::$updatedLastAccess = TRUE;
			}
			
			$return = RedisClass::i()->get( $this->_getRedisKey() . '_' . $key );
			
			if ( $return !== FALSE AND $decoded = RedisClass::i()->decode( $return ) )
			{
				static::$cache[ $key ] = $decoded;
				return static::$cache[ $key ];
			}
			else
			{
				throw new UnderflowException;
			}
		}
		catch( RedisException $e )
		{
			/* Do not reset the connection if the decode failed */
			if ( $e->getMessage() !== 'DECODE_ERROR' )
			{
				RedisClass::i()->resetConnection( $e );
			}

			throw new UnderflowException;
		}
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param	string			$key	Key
	 * @param	string			$value	Value
	 * @return	bool
	 */
	public function set( string $key, string $value ): bool
	{
		try
		{
			return RedisClass::i()->setex( $this->_getRedisKey() . '_' . $key, 604800, RedisClass::i()->encode( $value ) );
		}
		catch( RedisException $e )
		{
			RedisClass::i()->resetConnection( $e );

			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function exists( string $key ): bool
	{
		if( array_key_exists( $key, static::$cache ) )
		{
			return !( ( static::$cache[$key] === FALSE ) );
		}

		/* We do a get instead of an exists() check because it will cause the cache value to be fetched and cached inline, saving another call to the server */
		try
		{
			return !( ( $this->get( $key ) === FALSE ) );
		}
		catch ( UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function delete( string $key ): bool
	{		
		try
		{
			unset( static::$cache[ $key ] );
			return (bool) RedisClass::i()->del( $this->_getRedisKey() . '_' . $key );
		}
		catch( RedisException $e )
		{
			RedisClass::i()->resetConnection( $e );
			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Clear All
	 *
	 * @return	void
	 */
	public function clearAll() : void
	{
		try
		{
			$this->_redisKey = md5( mt_rand() );
			RedisClass::i()->setex( 'redisKey_store', 604800, $this->_redisKey );
		}
		catch( RedisException $e )
		{
			RedisClass::i()->resetConnection( $e );
		}
	}
	
	/**
	 * Test the datastore engine to make sure it's working
	 * Overloaded here to ensure that if we're using a cluster, there isn't a false error because of the delay with RW
	 *
	 * @return	bool
	 */
	public function test(): bool
	{
		return RedisClass::i()->test();
	}
}