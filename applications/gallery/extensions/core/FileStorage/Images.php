<?php
/**
 * @brief		File Storage Extension: Images
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Images
 */
class Images extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'gallery_images' )->first();
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
		$image	= Db::i()->select( '*', 'gallery_images', array(), 'image_id', array( $offset, 1 ) )->first();
		$update	= array();

		if( $image['image_small_file_name'] )
		{
			try
			{
				$file = File::get( $oldConfiguration ?: 'gallery_Images', $image['image_small_file_name'] )->move( $storageConfiguration );
				$update['image_small_file_name']	= (string) $file;
			}
			catch( Exception )
			{
				/* Any issues are logged */
			}
		}

		if( $image['image_masked_file_name'] )
		{
			if( $image['image_masked_file_name'] == $image['image_small_file_name'] AND isset( $update['image_small_file_name'] ) )
			{
				$update['image_masked_file_name']	= $update['image_small_file_name'];
			}
			else
			{
				try
				{
					$file = File::get( $oldConfiguration ?: 'gallery_Images', $image['image_masked_file_name'] )->move( $storageConfiguration );
					$update['image_masked_file_name']	= (string) $file;
				}
				catch( Exception )
				{
					/* Any issues are logged */
				}
			}
		}

		if( $image['image_original_file_name'] )
		{
			if( $image['image_original_file_name'] == $image['image_masked_file_name'] AND isset( $update['image_masked_file_name'] ) )
			{
				$update['image_original_file_name']	= $update['image_masked_file_name'];
			}
			else
			{
				try
				{
					$file = File::get( $oldConfiguration ?: 'gallery_Images', $image['image_original_file_name'] )->move( $storageConfiguration );
					$update['image_original_file_name']	= (string) $file;
				}
				catch( Exception )
				{
					/* Any issues are logged */
				}
			}
		}
		
		if ( count( $update ) )
		{
			foreach( $update as $k => $v )
			{
				if ( $v == $image[ $k ] )
				{
					unset( $update[ $k ] );
				}
			}
			
			if ( count( $update ) )
			{
				Db::i()->update( 'gallery_images', $update, array( 'image_id=?', $image['image_id'] ) );
			}
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
			$record	= Db::i()->select( '*', 'gallery_images', array( 'image_masked_file_name=? OR image_original_file_name=? OR image_small_file_name=?', (string) $file, (string) $file, (string) $file ) )->first();

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
		foreach( Db::i()->select( '*', 'gallery_images' ) as $image )
		{
			foreach( array( 'image_masked_file_name', 'image_original_file_name', 'image_small_file_name' ) as $size )
			{
				if( $image[ $size ] )
				{
					try
					{
						File::get( 'gallery_Images', $image[ $size ] )->delete();
					}
					catch( Exception ){}
				}
			}
		}
	}
}