<?php
/**
 * @brief		File System Storage Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 May 2013
 */

namespace IPS\Data\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use DomainException;
use IPS\Data\Store;
use IPS\Helpers\Form\Text;
use IPS\Request;
use ParseError;
use UnderflowException;
use function defined;
use function file_put_contents;
use function function_exists;
use const IPS\IPS_FILE_PERMISSION;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\SUITE_UNIQUE_KEY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File System Storage Class
 */
class FileSystem extends Store
{
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
	 * Configuration
	 *
	 * @param	array	$configuration	Existing settings
	 * @return	array	\IPS\Helpers\Form\FormAbstract elements
	 */
	public static function configuration( array $configuration ): array
	{
		return array(
			'path'	=> new Text( 'datastore_filesystem_path', ( isset( $configuration['path'] ) ) ? rtrim( str_replace( '{root}', \IPS\ROOT_PATH, $configuration['path'] ), '/' ) : \IPS\ROOT_PATH . '/datastore', FALSE, array(), function( $val )
			{
				if ( Request::i()->datastore_method === 'FileSystem' )
				{
					if ( !is_dir( $val ) and is_writable( $val ) )
					{
						mkdir( $val );
						chmod( $val, IPS_FOLDER_PERMISSION );
						file_put_contents( $val . '/index.html', '' );
					}
					
					if ( !is_dir( $val ) or !is_writable( $val ) )
					{
						throw new DomainException( 'datastore_filesystem_path_err' );
					}
				}
			} )
		);
	}

	/**
	 * @brief	Storage Path
	 */
	public string $_path;
	
	/**
	 * Constructor
	 *
	 * @param	array	$configuration	Configuration
	 * @return	void
	 */
	public function __construct( array $configuration )
	{
		$this->_path = rtrim( str_replace( '{root}', \IPS\ROOT_PATH, $configuration['path'] ), '/' );

		/* Fallback for an invalid path */
		if( !$this->_path )
		{
			$this->_path = \IPS\ROOT_PATH . '/datastore';
		}
	}

	/**
	 * @brief	Cache
	 */
	protected static array $cache = array();

	/**
	 * Abstract Method: Get
	 *
	 * @param	string	$key	Key
	 * @return	string	Value from the _datastore
	 */
	public function get( string $key ): string
	{
		if ( !isset( static::$cache[ $key ] ) )
		{	
			if ( @filesize( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' ) )
			{
				try
				{
					static::$cache[ $key ] = require( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' );
				}
				catch ( ParseError $e )
				{
					throw new UnderflowException;
				}
			}
			else
			{
				throw new UnderflowException;
			}
		}

		return static::$cache[ $key ];
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param string $key	Key
	 * @param string $value	Value
	 * @return	bool
	 */
	public function set( string $key, string $value ): bool
	{
		$contents = <<<CONTENTS
<?php

return <<<'VALUE'
{$value}
VALUE;

CONTENTS;
		
		$result = (bool) @file_put_contents( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php', $contents, LOCK_EX );

		/* Sometimes LOCK_EX is unavailable and throws file_put_contents(): Exclusive locks are not supported for this stream.
			While we would prefer an exclusive lock, it would be better to write the file if possible. */
		if( !$result )
		{
			@unlink( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' );
			$result = (bool) @file_put_contents( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php', $contents );
		}

		@chmod( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php', IPS_FILE_PERMISSION );

		static::$cache[ $key ] = $value;

		/* Clear zend opcache if enabled */
		if ( function_exists( 'opcache_invalidate' ) )
		{
			@opcache_invalidate( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' );
		}

		return $result;
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function exists( string $key ): bool
	{
		if( isset( static::$cache[ $key ] ) )
		{
			return TRUE;
		}
		else
		{
			try
			{
				$this->get( $key );
				return TRUE;
			}
			catch ( UnderflowException $e )
			{
				return FALSE;
			}
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
		$return = false;
		if ( file_exists( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' ) )
		{
			$return = @unlink( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' );
		}

		if( array_key_exists( $key, static::$cache ) )
		{
			unset( static::$cache[ $key ] );
		}

		/* Clear zend opcache if enabled */
		if ( function_exists( 'opcache_invalidate' ) )
		{
			@opcache_invalidate( $this->_path . '/' . $key . '.' . SUITE_UNIQUE_KEY . '.php' );
		}
		
		return $return;
	}
	
	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @param	NULL|string	$exclude	Key to exclude (keep)
	 * @return	void
	 */
	public function clearAll( string $exclude=NULL ) : void
	{
		foreach ( new DirectoryIterator( $this->_path ) as $file )
		{			
			if ( !$file->isDot() and ( mb_substr( $file, -9 ) === '.ipsstore' or ( mb_substr( $file, -4 ) === '.php' and $file != $exclude . '.php' ) ) )
			{
				@unlink( $this->_path . '/' . $file );
			}
		}

		foreach( static::$cache as $key => $value )
		{
			if( $exclude === NULL OR $key != $exclude )
			{
				unset( static::$cache[ $key ] );
			}
		}

		/* Clear zend opcache if enabled - we call reset here since we're wiping multiple files
			and since this gets called as part of a general 'rebuild everything', a reset is 
			probably a good idea anyway */
		if ( function_exists( 'opcache_reset' ) )
		{
			@opcache_reset();
		}
	}
}