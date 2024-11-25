<?php
/**
 * @brief		Redis Engine Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Sept 2017
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Session\Store;
use IPS\Text\Encrypt;
use Redis as PHPRedis;
use RedisException;
use function array_slice;
use function count;
use function defined;
use function stristr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis Cache Class
 *
 * @mixin PHPRedis
 */
class Redis
{
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	Connections Store
	 * @var   \Redis[]
	 */
	protected static array $connections = array();
	
	/**
	 * @brief	Default expiration for keys in seconds
	 */
	protected static int $ttl = 604800; #7 days
	
	/**
	 * @brief Log what redis is up to
	 */
	public static array $log = array();
	
	/**
	 * @brief Prefix
	 */
	public ?string $prefix = NULL;
	
	/**
	 * @brief Unpack the config once
	 */
	protected static ?array $config = NULL;
	
	/**
	 * Writes made
	 */
	protected static array $writes = array();

	/**
	 * @var array
	 */
	protected static array $connectionAttempts = [];
	
	/**
	 * Get instance
	 *
	 * @param array|null $configuration	Configuration to use (NULL to use \IPS\REDIS_CONFIG or \IPS\CACHE_CONFIG)
	 * @param string|null $identifier		Identifier (to support multiple instances)
	 * @return	Redis
	 */
	public static function i( array $configuration=NULL, string $identifier=NULL ) : Redis
	{
		if ( static::$config === NULL )
		{
			$config = ( defined( '\IPS\REDIS_CONFIG' ) and REDIS_CONFIG !== NULL ) ? REDIS_CONFIG : CACHE_CONFIG;
			static::$config = $configuration ?: json_decode( $config, true );
		}

		$identifier = $identifier ?: '_MAIN';
		
		if ( ! isset( static::$multitons[ $identifier ] ) )
		{
			static::$multitons[ $identifier ] = new static;
			
			/* Set the prefix with the most obvious comment in the world */
			static::$multitons[ $identifier ]->prefix = SUITE_UNIQUE_KEY . '_';
		}
		
		/* Return */
		return static::$multitons[ $identifier ];
	}	
		
	/**
	 * Destructor
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ( DEBUG_LOG and count( static::$writes ) > 50 )
		{ 
			$slice = implode( ', ', array_slice( static::$writes, 0, 50 ) );
			Log::debug( "Large number of Redis writes: " . $slice, 'redis_writes' );
		}
	}
	
	/**
	 * @brief	Chosen reader for this session
	 */
	protected static ?int $reader = NULL;
	
	/**
	 * Connect to Redis
	 *
	 * @param string|null $identifier		Identifier
	 * @return	PHPRedis
	 * @throws	RedisException	If connection cannot be established
	 * @throws	BadMethodCallException	If Redis PHP extension is not installed
	 */
	public function connection( string $identifier=NULL ): PHPRedis
	{
		if ( ! class_exists('Redis') )
		{
			throw new BadMethodCallException;
		}
		
		$useConfig = NULL;
		if ( isset( static::$config['write'] ) )
		{
			/* We have multiple servers for read and one for write */
			if ( $identifier === 'write' )
			{
				$useConfig = static::$config['write'];
			}
			else if ( $identifier === 'read' )
			{
				if ( static::$reader === NULL )
				{
					static::$reader = rand( 0, count( static::$config['read'] ) - 1 );
				}
				
				$useConfig = static::$config['read'][ static::$reader ];
				$identifier = 'read' . static::$reader;
			}
			else
			{
				/* Set up the writer first as the default server */
				$identifier = 'write';
				$useConfig = static::$config['write'];
			}
		}
		else
		{
			/* We have only passed through one server */
			$identifier = 'single';
			$useConfig = static::$config;
		}

		if ( ! isset( static::$connections[ $identifier ] ) )
		{
			try
			{
				static::$connections[ $identifier ] = new PHPRedis;

				/* Track connection requests */
				if ( ! isset( static::$connectionAttempts[ $identifier ] ) )
				{
					static::$connectionAttempts[ $identifier ] = 0;
				}

				/* Prevent too many connection attempts per request */
				if( static::$connectionAttempts[ $identifier ] >= \IPS\REDIS_MAX_CONNECTION_ATTEMPTS )
				{
					throw new \RedisException('CANNOT_CONNECT_TOO_MANY_ATTEMPTS');
				}

				static::$connectionAttempts[ $identifier ]++;

				/* PHP Redis uses many PHP internals to connect, and these can throw ErrorException when they fail but we want a consistent exception */
				if( @static::$connections[ $identifier ]->connect( $useConfig['server'], $useConfig['port'], 1, '', 500 ) === FALSE ) # Connect with a 1 second timeout, and 500ms between connection attempts
				{
					unset( static::$connections[ $identifier ] );
					throw new RedisException('CANNOT_CONNECT');
				}
				else
				{
					if( isset( $useConfig['password'] ) and $useConfig['password'] )
					{
						if( static::$connections[ $identifier ]->auth( $useConfig['password'] ) === FALSE )
						{
							unset( static::$connections[ $identifier ] );
							throw new RedisException;
						}
					}
				}
				
				if( static::$connections[ $identifier ] !== NULL )
				{
					static::$connections[ $identifier ]->setOption( PHPRedis::OPT_SERIALIZER, PHPRedis::SERIALIZER_NONE );
					static::$connections[ $identifier ]->setOption( PHPRedis::OPT_PREFIX, $this->prefix );
				}
				
				/* If connection times out, connect can return TRUE and we won't know until our next attempt to talk to the server,
					so we should ping now to verify we were able to connect successfully */
				static::$connections[ $identifier ]->ping();
				
				if (REDIS_LOG)
				{
					static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "Redis connected (" . $identifier . ' ' . $useConfig['server'] . ")"  );
				}

				if ( count( static::$connections ) === 1 ) # Only set the shutdown function if this is the first connection so we don't get multiple shutdown functions
				{
					register_shutdown_function( function( $object ){
						try
						{
							/* First we have to make sure sessions have written */
							if( Store::i() instanceof Store\Redis)
							{
								session_write_close();
							}

							foreach( static::$connections as $key => $connection )
							{
								$connection->close();
							}
							
							/* Reset stored connections so they can be re-connected correctly if tasks run after this shutdown proceses */
							static::$connections = array();
						}
						catch( RedisException $e ){}
					}, $this );
				}
			}
			catch( RedisException $e )
			{
				/* Unset this connection */
				if ( isset( static::$connections[ $identifier ] ) )
				{
					unset( static::$connections[ $identifier ] );
				}
				
				$this->resetConnection( $e );
			}
		}

		if( !isset( static::$connections[ $identifier ] ) )
		{
			throw new RedisException('CANNOT_CONNECT');
		}
		
		return static::$connections[ $identifier ];
	}
	
	/**
	 * Call methods
	 *
	 * @param string $method	Method
	 * @param	mixed	$args	Arguments
	 * @return	mixed
	 */
	public function __call( string $method, mixed $args )
	{
		if ( method_exists( 'Redis', $method ) )
		{
			$type = ( stristr( $method, 'get' ) or stristr( $method, 'RevRange' ) ) ? 'read' : 'write';
			$return = $this->connection( $type )->$method( ...$args );
			
			if ( $type === 'write' and count( $args ) )
			{
				static::$writes[] = $args[0];
			}
			
			if ( REDIS_LOG and count( $args ) )
			{
				if ( preg_match( '#^[a-f0-9]{32}_str__#', $args[0] ) )
				{
					$args[0] =  preg_replace( '#^[a-f0-9]{32}_str__#', '[DATASTORE] ', $args[0] );
				}
				
				static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "({$type}) {$method} " . $args[0], json_encode( $args ) );
			}
			
			return $return;
		}
		return null;
	}
	
	/**
	 * Add one or more members to a sorted set or update its score if it already exists
	 * Overloaded here so it can add a TTL to prevent permanent keys
	 *
	 * @param string $key	Key
	 * @param float $score	Score
	 * @param string $value	Value
	 * @param int|null $ttl	TTL in seconds
	 * @return int	1 if the element is added. 0 otherwise.
	 */
	public function zAdd( string $key, float $score, string $value, int $ttl=NULL ): int
	{
		$return = $this->connection('write')->zAdd( $this->key( $key ), $score, $value );
		
		$this->connection('write')->expire( $this->key( $key ), ( $ttl ?: static::$ttl ) );
		
		if (REDIS_LOG)
		{
			static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "(write) zAdd " . $key . " = " . $return, json_encode( $value ) );
		}
		
		return $return;
	}
	
	/**
	 * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast. NULL values are stored as empty strings.
	 * Overloaded here so it can add a TTL to prevent permanent keys
	 *
	 * @param string $key	Key
	 * @param array $value	Value
	 * @param int|null $ttl	TTL in seconds
	 * @return	boolean
	 */
	public function hMSet( string $key, array $value, int $ttl=NULL ): bool
	{
		$return = $this->connection('write')->hMSet( $this->key( $key ), $value );
		
		$this->connection('write')->expire( $this->key( $key ), ( $ttl ?: static::$ttl ) );
		
		if (REDIS_LOG)
		{
			static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "(write) hMSet " . $key . " = " . $return, json_encode( $value ) );
		}
		
		return $return;
	}
	
	/**
	 * Set the string value in argument as value of the key, with a time to live
	 * Overloaded here so it can be logged
	 *
	 * @param string $key	Key
	 * @param int|null $ttl	TTL in seconds
	 * @param string $value	Value
	 * @return	boolean
	 */
	public function setEx( string $key, ?int $ttl, string $value ): bool
	{
		$return = $this->connection('write')->setEx( $this->key( $key ), $ttl, $value );
		
		if (REDIS_LOG)
		{
			static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "(write) setEx " . $key . "  = " . $return, json_encode( $value ) );
		}
		
		return $return;
	}
	
	/**
	 * Sort the elements in a list, set or sorted set.
	 * Overloaded here so we can adjust the key
	 *
	 * @param string $key		Key
	 * @param array $options	Options: array(key => value, ...) - optional
	 * @return	array|int
	 */
	public function sort( string $key, array $options=array() ): array|int
	{
		$return = $this->connection('write')->sort( $this->key( $key ), $options );
		
		if ( isset( $options['store'] ) )
		{
			$this->connection('write')->expire( $this->key( $options['store'] ), ( isset( $options['ttl'] ) and $options['ttl'] ) ? $options['ttl'] : static::$ttl );
		}
		
		if (REDIS_LOG)
		{
			static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "(write) sort " . $key, json_encode( $return ) );
		}
		
		return $return;
	}
	
	/**
	 * Returns the whole hash, as an array of strings indexed by strings.
	 * Overloaded here so it can be logged
	 *
	 * @param string $key	Key
	 * @return	array
	 */
	public function hGetAll( string $key ): array
	{
		/* Make sure we read */
		$return = $this->connection('read')->hGetAll( $this->key( $key ) );
		
		if (REDIS_LOG)
		{
			static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "(read) hGetAll " . $key, json_encode( $return ) );
		}
		
		return $return;
	}
	
	/**
	 * Publish a message to Redis PubSub
	 * 
	 * @param 	string 		$key		The message key
	 * @param mixed  $value 		The payload
	 * @param 	boolean		$encrypted	Should the payload be encrypted? Has no effect in non-CiC environments.
	 */
	public function publish(string $key, mixed $value=array(), bool $encrypted=FALSE ) :int
	{
		return $this->connection('write')->publish( $key, $this->encode($value, $encrypted) );
	}

	/**
	 * Increments the score of a member from a sorted set by a given amount.
	 * Overloaded here so it can be logged and a ttl set
	 *
	 * @param string $key	Key
	 * @param int $inc	Value to increment
	 * @param string $value	Value
	 * @param int|null $ttl	TTL in seconds
	 * @return	boolean
	 */
	public function zIncrBy( string $key, int $inc, string $value, int $ttl=NULL ): bool
	{
		$return = $this->connection('write')->zIncrBy( $this->key( $key ), $inc, $value );
		$this->connection('write')->expire( $this->key( $key ), ( $ttl ?: static::$ttl ) );
		
		if (REDIS_LOG)
		{
			static::$log[ sprintf( '%.4f', microtime(true) ) ] = array( 'redis', "(write) zIncrBy " . $key . "  = " . $return, json_encode( $value ) );
		}
		
		return $return;
	}
	
	/**
	 * Strip prefixes from keys as PHP redis will handle this
	 *
	 * @param string $key	Key
	 * @return	string
	 */
	protected function key( string $key ): string
	{
		if ( $this->prefix )
		{
			if ( mb_substr( $key, 0, mb_strlen( $this->prefix ) ) == $this->prefix )
			{
				return str_replace( $this->prefix, '', $key );
			}
		}
		
		return $key;
	}
		
	/**
	 * Encode
	 *
	 * @param	mixed	$value	Value
	 * @param bool $encryptIfCic Encrypt for CiC
	 * @return	string
	 */
	public function encode(mixed $value, bool $encryptIfCic=TRUE ): string
	{
		if ( CIC && $encryptIfCic )
		{
			return Encrypt::fromPlaintext( json_encode( $value ) )->tag();
		}
		else
		{
			return json_encode( $value );
		}
	}
	
	/**
	 * Decode
	 *
	 * @param	mixed	$value	Value
	 * @return	mixed
	 */
	public function decode( mixed $value ): mixed
	{
		if (CIC)
		{
			$decoded = json_decode( Encrypt::fromTag( $value )->decrypt(), TRUE );
			
			if( $decoded === NULL )
			{
				throw new RedisException('DECODE_ERROR');
			}
		}
		else
		{
			$decoded = json_decode( $value, TRUE );
		}
		
		return $decoded;
	}
	
	/**
	 * Reset connection
	 *
	 * @param	RedisException|NULL	$e	If this was called as a result of an exception, log that to the debug log
	 * @return void
	 */
	public function resetConnection( RedisException $e = NULL ) : void
	{
		$message = '';

		if ( $e !== NULL )
		{
			$message = $e->getMessage();
			Log::debug( $e, 'redis_exception' );
		}

		static::$multitons = array();
		
		if (REDIS_LOG)
		{
			static::$log[ microtime() ] = array( 'redis', "Redis connections reset " . $message );
		}
	}
	
	/**
	 * Is Redis working?
	 *
	 * @return	bool
	 */
	public function test(): bool
	{
		return (bool) count( static::$connections );
	}

	/**
	 * Debug method to fetch all keys.
	 *
	 * @warning    Never use this in production, as it can expose sensitive data!
	 * @param string $pattern Pattern (* to fetch all)
	 * @param boolean $keyNamesOnly Return names only
	 * @return    array
	 * @throws RedisException
	 */
	public function debugGetKeys( string $pattern='*', bool $keyNamesOnly=FALSE ): array
	{
		$this->connection('write')->setOption( PHPRedis::OPT_SCAN, PHPRedis::SCAN_RETRY );
		
		$return = array();
		$iterator = NULL;
		while( $keys = $this->connection('write')->scan( $iterator, $this->prefix . $this->key( $pattern ) ) )
		{
			if ( $keyNamesOnly)
			{
				$return = array_merge( $return, $keys );
			}
			else
			{
				foreach( $keys as $key )
				{
					$key = $this->key( $key );
					$type = $this->connection('write')->type( $key );
					$ttl = $this->ttl( $key );

					switch( $type )
					{
						case PHPRedis::REDIS_STRING:
							if ( mb_stristr( $key, '_pg__page_' ) )
							{
								$return[ $key . ' (TTL: ' . $ttl . ')' ] =  @gzdecode( Encrypt::fromCipher(  $this->get( $key ) )->decrypt() );
							}
							else
							{
								$return[ $key . ' (TTL: ' . $ttl . ')' ] = Redis::i()->decode( $this->connection('write')->get( $key ) );
							}
						break;
						case PHPRedis::REDIS_ZSET:
							$return[ $key . ' (TTL: ' . $ttl . ')' ] = $this->connection('write')->zRange( $key, 0, -1, TRUE );
						break;
						case PHPRedis::REDIS_HASH:
							$return[ $key . ' (TTL: ' . $ttl . ')' ] = $this->connection('write')->hGetAll( $key );
							if ( isset( $return[ $key . ' (TTL: ' . $ttl . ')' ]['data'] ) )
							{
								$return[ $key . ' (TTL: ' . $ttl . ')' ]['data'] = Redis::i()->decode( $return[ $key . ' (TTL: ' . $ttl . ')' ]['data'] );
							}
						break;
						case PHPRedis::REDIS_LIST:
							$return[ $key . ' (TTL: ' . $ttl . ')' ] = $this->connection('write')->lRange( $key, 0, -1 );
						break;
					}
				}
			}
		}
		
		return $return;
	}

	/**
	 * A quick and consistent way to see if Redis can be used
	 *
	 * @return bool
	 */
	static public function isEnabled(): bool
	{
		return class_exists('Redis') and REDIS_ENABLED and REDIS_CONFIG;
	}
}