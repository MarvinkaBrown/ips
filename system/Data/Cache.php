<?php
/**
 * @brief		Abstract Storage Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 May 2013
 */

namespace IPS\Data;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Cache\Exception;
use IPS\Data\Cache\None;
use IPS\DateTime;
use IPS\Settings;
use IPS\Text\Encrypt;
use OutOfRangeException;
use function count;
use function defined;
use function function_exists;
use function in_array;
use function is_array;
use const IPS\CACHE_CONFIG;
use const IPS\CACHE_METHOD;
use const IPS\CACHING_LOG;
use const IPS\TEST_CACHING;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Storage Class
 */
abstract class Cache extends AbstractData
{
	/**
	 * @brief	Instance
	 */
	protected static ?Cache $instance = NULL;

	/**
	 * @brief	Caches already retrieved this instance
	 */
	protected array $cache	= array();
	
	/**
	 * @brief	Log
	 */
	public array $log	= array();

	/**
	 * Available Cache Store Methods
	 * - MUST always return a 'None' option
	 *
	 * @return  array
	 */
	public static function availableMethods(): array
	{
		$return = [
			'None'  => 'IPS\Data\Cache\None',
			'Redis' => 'IPS\Data\Cache\Redis',
		];

		if( TEST_CACHING )
		{
			$return['Test'] = 'IPS\Data\Cache\Test';
		}

		return $return;
	}

	/**
	 * Get instance
	 *
	 * @return    Cache|null
	 */
	public static function i(): ?Cache
	{
		if( static::$instance === NULL )
		{
			$classname = '\IPS\Data\Cache\None';
			if( isset( static::availableMethods()[ CACHE_METHOD ] ) AND class_exists( static::availableMethods()[ CACHE_METHOD ] ) )
			{
				$classname = static::availableMethods()[ CACHE_METHOD ];
			}

			if ( $classname::supported() )
			{
				try
				{
					static::$instance = new $classname( json_decode( CACHE_CONFIG, TRUE ) );
				}
				catch( Exception $e )
				{
					static::$instance = new None( array() );
				}
			}
			else
			{
				static::$instance = new None( array() );
			}
		}
		
		return static::$instance;
	}
	
	/**
	 * Needs cache key check with this storage engine to maintain integrity
	 *
	 * @return boolean
	 */
	public function checkKeys(): bool
	{
		return true;
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
	public function storeWithExpire( string $key, mixed $value, DateTime $expire, bool $fallback=FALSE ): bool
	{
		$value = array( 'value' => $value, 'expires' => $expire->getTimestamp() );
		
		if ( CACHING_LOG )
		{
			$this->log[ microtime(true) ] = array( 'set', $key, json_encode( $value, JSON_PRETTY_PRINT ), var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
		}
		
		if ( $this->set( $key, $this->encode( $value ), $expire ) )
		{
			$this->_data[ $key ] = $value;
			$this->_exists[ $key ] = $key;

			return TRUE;
		}

		return FALSE;
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
		if ( !isset( $this->$key ) )
		{
			throw new OutOfRangeException;
		}
		
		$data = $this->$key;
		if( count( $data ) and isset( $data['value'] ) and isset( $data['expires'] ) )
		{
			/* Is it expired? */
			if( $data['expires'] AND time() < $data['expires'] )
			{
				return $data['value'];
			}
			else
			{
				unset( $this->$key );
				throw new OutOfRangeException;
			}
		}
		else
		{
			unset( $this->$key );
			throw new OutOfRangeException;
		}
	}
	
	/**
	 * Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll() : void
	{
		/* cacheKeys stored md5 hashes of the correct value, and the cache
			is only used if the value it returns matches the hash, so clearing
			this out invalidates all caches, even if the caching engine
			does not allow us to actually clear them */
		Store::i()->cacheKeys = array();
	}
	
	/**
	 * Encryption key
	 *
	 * @return	string
	 */
	protected function _encryptionKey(): string
	{
		$password = Settings::i()->sql_pass;
		if ( function_exists( 'openssl_digest' ) and in_array( 'sha256', openssl_get_md_methods() ) )
		{
			$password = openssl_digest( $password, 'sha256', TRUE );
		}
		return $password;
	}
		
	/**
	 * Encode
	 *
	 * @param	mixed	$value	Value
	 * @return	string
	 */
	protected function encode( mixed $value ): string
	{
		return Encrypt::fromPlaintext( json_encode( $value ) )->tag();
	}
	
	/**
	 * Decode
	 *
	 * @param	mixed	$value	Value
	 * @return	mixed
	 */
	protected function decode( mixed $value ): mixed
	{
		return json_decode( Encrypt::fromTag( $value )->decrypt(), TRUE );
	}
	
	/**
	 * Magic Method: Isset
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	public function __isset( string $key ): bool
	{
		if ( parent::__isset( $key ) )
		{
			return ( is_array( $this->$key ) or $this->$key === 0 ) ? true : (bool) $this->$key;
		}
		return FALSE;
	}
}