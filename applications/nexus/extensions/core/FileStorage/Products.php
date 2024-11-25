<?php
/**
 * @brief		File Storage Extension: Product Images
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		5 May 2014
 */

namespace IPS\nexus\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Package Images
 */
class Products extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'nexus_package_images' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		$record = Db::i()->select( '*', 'nexus_package_images', array(), 'image_id', array( $offset, 1 ) )->first();

		try
		{
			$file = File::get( $oldConfiguration ?: 'nexus_Products', $record['image_location'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['image_location'] )
			{
				Db::i()->update( 'nexus_package_images', array( 'image_location' => (string) $file ), array( 'image_id=?', $record['image_id'] ) );
			}
		}
		catch( Exception )
		{
			/* Any issues are logged */
		}
	}
	
	/**
	 * Check if a file is valid
	 *
	 * @param	File|string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( File|string $file ): bool
	{
		try
		{
			Db::i()->select( '*', 'nexus_package_images', array( 'image_location=?', (string) $file ) )->first();
			return TRUE;
		}
		catch ( UnderflowException )
		{
			return FALSE;
		}
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( '*', 'nexus_package_images', "image_location IS NOT NULL" ) as $product )
		{
			try
			{
				File::get( 'nexus_Products', $product['image_location'] )->delete();
			}
			catch( Exception ){}
		}
	}
}