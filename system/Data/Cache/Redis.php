<?php
/**
 * @brief		Redis Cache Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Oct 2013
 */

namespace IPS\Data\Cache;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Text;
use IPS\Redis as RedisClass;
use IPS\Request;
use RedisException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis Cache Class
 */
class Redis extends Cache
{
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
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported(): bool
	{
		return class_exists('Redis');
	}

	/**
	 * Needs cache key check with this storage engine to maintain integrity
	 *
	 * @return boolean
	 */
	public function checkKeys(): bool
	{
		return false;
	}
	
	/**
	 * Configuration
	 *
	 * @param	array	$configuration	Existing settings
	 * @return	array	\IPS\Helpers\Form\FormAbstract elements
	 */
	public static function configuration( array $configuration ): array
	{
		return array(
			'server'	=> new Text( 'server_host', $configuration['server'] ?? '', FALSE, array( 'placeholder' => '127.0.0.1' ), function( $val )
			{
				if ( Request::i()->cache_method === 'Redis' and empty( $val ) )
				{
					throw new DomainException( 'datastore_redis_servers_err' );
				}
			}, NULL, NULL, 'redis_host' ),
			'port'		=> new Number( 'server_port', $configuration['port'] ?? NULL, FALSE, array( 'placeholder' => '6379' ), function( $val )
			{
				if ( Request::i()->cache_method === 'Redis' AND $val AND ( $val < 0 OR $val > 65535 ) )
				{
					throw new DomainException( 'datastore_redis_servers_err' );
				}
			}, NULL, NULL, 'redis_port' ),
			'password'	=> new Password( 'server_password', $configuration['password'] ?? '', FALSE, array(), NULL,  NULL, NULL, 'redis_password' ),
		);
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
			if ( !( $this->_redisKey = RedisClass::i()->get( 'redisKey' ) ) )
			{
				$this->_redisKey = md5( mt_rand() );
				RedisClass::i()->setex( 'redisKey', 604800, $this->_redisKey );
			}
		}
		
		return $this->_redisKey;
	}

	/**
	 * Abstract Method: Get
	 *
	 * @param string $key
	 * @return  string|FALSE    Value from the _datastore; FALSE if key doesn't exist
	 */
	protected function get( string $key ): string|FALSE
	{
		if( array_key_exists( $key, $this->cache ) )
		{
			return $this->cache[ $key ];
		}

		try
		{
			$this->cache[ $key ] = RedisClass::i()->get( $this->_getRedisKey() . '_' . $key );

			return $this->cache[ $key ];
		}
		catch( RedisException $e )
		{
			RedisClass::i()->resetConnection( $e );

			return FALSE;
		}
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param string $key	Key
	 * @param string $value	Value
	 * @param	DateTime|NULL	$expire	Expreation time, or NULL for no expiration
	 * @return	bool
	 */
	protected function set( string $key, string $value, DateTime $expire = NULL ): bool
	{
		try
		{
			if ( $expire )
			{
				return RedisClass::i()->setex( $this->_getRedisKey() . '_' . $key, $expire->getTimestamp() - time(), $value );
			}
			else
			{
				/* Set for 24 hours */
				return RedisClass::i()->setex( $this->_getRedisKey() . '_' . $key, 86400, $value );
			}
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
	 * @param string $key	Key
	 * @return	bool
	 */
	protected function exists( string $key ): bool
	{
		if( array_key_exists( $key, $this->cache ) )
		{
			return !( ( $this->cache[$key] === FALSE ) );
		}

		/* We do a get instead of an exists() check because it will cause the cache value to be fetched and cached inline, saving another call to the server */
		return !( ( $this->get( $key ) === FALSE ) );
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	protected function delete( string $key ): bool
	{		
		try
		{
			return (bool) RedisClass::i()->del( $this->_getRedisKey() . '_' . $key );
		}
		catch( RedisException $e )
		{
			RedisClass::i()->resetConnection( $e );
			return FALSE;
		}
	}

	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll() : void
	{
		parent::clearAll();
		
		$this->_redisKey = md5( mt_rand() );
		RedisClass::i()->setex( 'redisKey', 604800, $this->_redisKey );
	}
	
}