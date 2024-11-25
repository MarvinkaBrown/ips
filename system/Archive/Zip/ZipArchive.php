<?php
/**
 * @brief		ZipArchive Zip Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jul 2015
 */

namespace IPS\Archive\Zip;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Archive;
use IPS\Archive\Exception;
use IPS\Archive\Zip;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ZipArchive Zip Class
 */
class ZipArchive extends Zip
{
	/**
	 * @brief	ZipArchive Object
	 */
	protected ?\ZipArchive $zipArchive;
	
	/**
	 * Create object from local file
	 *
	 * @param string $path			Path to archive file
	 * @return	Archive
	 */
	public static function _fromLocalFile( string $path ): Archive
	{
		$object = new static;
		$object->zipArchive = new \ZipArchive;
		$open = $object->zipArchive->open( $path );
		if ( $open !== TRUE )
		{
			throw new Exception( $open, Exception::COULD_NOT_OPEN );
		}
		return $object;
	}
	
	/**
	 * Number of files
	 *
	 * @return	int
	 */
	public function numberOfFiles(): int
	{
		return $this->zipArchive->numFiles;
	}
	
	/**
	 * Get file name
	 *
	 * @param int $i	File number
	 * @return	string
	 * @throws	OutOfRangeException
	 */
	public function getFileName( int $i ): string
	{
		$info = $this->zipArchive->statIndex( $i );
		if ( $info === FALSE )
		{
			throw new OutOfRangeException;
		}
		
		return mb_substr( $info['name'], 0, mb_strlen( $this->containerName ) ) === $this->containerName ? mb_substr( $info['name'], mb_strlen( $this->containerName ) ) : $info['name'];
	}
	
	/**
	 * Get file contents
	 *
	 * @param int $i	File number
	 * @return	string
	 * @throws	OutOfRangeException
	 */
	public function getFileContents( int $i ): string
	{
		return $this->zipArchive->getFromIndex( $i );
	}
}