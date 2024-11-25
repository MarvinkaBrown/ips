<?php
/**
 * @brief		Test Cache Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		7 Jan 2015
 */

namespace IPS\Data\Cache;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use IPS\Data\Cache;
use IPS\DateTime;
use IPS\Helpers\Form\Text;
use function defined;
use function file_put_contents;
use const IPS\IPS_FILE_PERMISSION;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Test Cache Class
 */
class Test extends Cache
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
		return array( 'path' => new Text( 'datastore_test_path', ( isset( $configuration['path'] ) ) ? $configuration['path'] : '', FALSE ) );
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
		$this->_path = rtrim( $configuration['path'], '/' );
	}

	/**
	 * Abstract Method: Get
	 *
	 * @param string $key	Key
	 * @return	string	Value from the _datastore
	 */
	protected function get( string $key ): string
	{
		return file_get_contents( $this->_path . '/' . $key . '.txt' );
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
		$return = file_put_contents( $this->_path . '/' . $key . '.txt', $value );
		chmod( $this->_path . '/' . $key . '.txt', IPS_FILE_PERMISSION );
		return $return;
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	protected function exists( string $key ): bool
	{
		return file_exists( $this->_path . '/' . $key . '.txt' );
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param string $key	Key
	 * @return	bool
	 */
	protected function delete( string $key ): bool
	{
		return @unlink( $this->_path . '/' . $key . '.txt' );
	}
	
	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll() : void
	{
		parent::clearAll();
		foreach ( new DirectoryIterator( $this->_path ) as $file )
		{			
			if ( !$file->isDot() and $file != 'index.html' )
			{
				@unlink( $this->_path . '/' . $file );
			}
		}
	}
}