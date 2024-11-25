<?php
/**
 * @brief		Zip Archive Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jul 2015
 */

namespace IPS\Archive;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Archive;
use function defined;
use function extension_loaded;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Zip Archive Class
 */
abstract class Zip extends Archive
{
	/**
	 * Create object from local file
	 *
	 * @param string $path			Path to archive file
	 * @param string $containerName	The root folder name which should be ignored (with trailing slash)
	 * @return    static
	 */
	public static function fromLocalFile( string $path, string $containerName = '' ): static
	{
		if ( extension_loaded( 'Zip' ) )
		{
			$object = Zip\ZipArchive::_fromLocalFile( $path );
		}
		else
		{
			$object = Zip\PclZip::_fromLocalFile( $path );
		}
		
		$object->containerName = $containerName;
		return $object;
	}
}