<?php
/**
 * @brief		File Storage Extension: Icons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		26 Mar 2024
 */

namespace IPS\downloads\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Icons
 */
class Icons extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(cid)', 'downloads_categories', 'cicon is not null' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	UnderflowException					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		$row = Db::i()->select( 'cid,cicon', 'downloads_categories', 'cicon is not null', 'cid', [ 0, 1 ] )->first();
		$test = json_decode( $row['cicon'], true );
		if( !$test )
		{
			try
			{
				$file = File::get( $oldConfiguration ?: 'downloads_Icons', $row['cicon'] )->move( $storageConfiguration );
				if( (string) $file != $row['cicon'] )
				{
					Db::i()->update( 'downloads_categories', [ 'cicon' => (string) $file ], [ 'cid=?', $row['cid'] ] );
				}
			}
			catch( OutOfRangeException ){}
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
		return (bool) Db::i()->select( 'count(cid)', 'downloads_categories', [ 'cicon=?', (string) $file ] )->first();
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( 'cicon', 'downloads_categories', [ 'cicon is not null' ] ) as $icon )
		{
			$icon = json_decode( $icon, true );
			if( !$icon )
			{
				try
				{
					File::get( 'downloads_Icons', $icon )->delete();
				}
				catch( OutOfRangeException ){}
			}
		}
	}
}