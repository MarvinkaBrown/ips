<?php
/**
 * @brief		File Storage Extension: Clubs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		01 Mar 2017
 */

namespace IPS\core\extensions\core\FileStorage;

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
 * File Storage Extension: Clubs
 */
class Clubs extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'core_clubs', 'profile_photo IS NOT NULL' )->first() + Db::i()->select( 'COUNT(*)', 'core_clubs', 'cover_photo IS NOT NULL' )->first();
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
		$club = Db::i()->select( '*', 'core_clubs', null, 'id', array( $offset, 1 ) )->first();
		
		try
		{
			$update = array();
			
			foreach ( array( 'profile_photo', 'cover_photo' ) as $photoKey )
			{
				if ( ! empty( $club[ $photoKey ] ) )
				{
					$file = File::get( $oldConfiguration ?: 'core_Clubs', $club[$photoKey] )->move( $storageConfiguration );
				}

				if ( (string) $file != $club[ $photoKey ] )
				{
					$update[ $photoKey ] = (string) $file;
				}
			}
			
			if ( count( $update ) )
			{
				Db::i()->update( 'core_clubs', $update, array( 'id=?', $club['id'] ) );
			}
		}
		catch( Exception $e )
		{
			/* Any issues are logged and the \IPS\Db::i()->update not run as the exception is thrown */
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
			$club	= Db::i()->select( '*', 'core_clubs', array( 'profile_photo=? OR cover_photo=?', (string) $file, (string) $file ) )->first();

			return TRUE;
		}
		catch ( UnderflowException $e )
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
		foreach( Db::i()->select( '*', 'core_clubs', 'profile_photo IS NOT NULL or cover_photo IS NOT NULL' ) as $club )
		{
			foreach ( array( 'profile_photo', 'cover_photo' ) as $photoKey )
			{
				if ( $club[ $photoKey ] )
				{
					try
					{
						File::get( 'core_Clubs', $club[ $photoKey ] )->delete();
					}
					catch( Exception $e ){}
				}
			}
		}
	}
}