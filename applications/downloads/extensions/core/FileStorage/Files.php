<?php
/**
 * @brief		File Storage Extension: Files
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		08 Oct 2013
 */

namespace IPS\downloads\extensions\core\FileStorage;

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
 * File Storage Extension: Files
 */
class Files extends FileStorageAbstract
{
	/**
	 * Some file storage engines have the facility to upload private files that need specially signed URLs to download to prevent public access of protected files.
	 */
	public static bool $isPrivate = true;
	
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'downloads_files_records', array( 'record_type=?', 'upload' ) )->first();
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
		$record = Db::i()->select( '*', 'downloads_files_records', array( 'record_type=?', 'upload' ), 'record_id', array( $offset, 1 ) )->first();

		try
		{
			$file = File::get( $oldConfiguration ?: 'downloads_Files', $record['record_location'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['record_location'] )
			{
				Db::i()->update( 'downloads_files_records', array( 'record_location' => (string) $file ), array( 'record_id=?', $record['record_id'] ) );
			}
		}
		catch( Exception $e )
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
			$record	= Db::i()->select( '*', 'downloads_files_records', array( 'record_location=? AND record_type=?', (string) $file, 'upload' ) )->first();

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
		foreach( Db::i()->select( '*', 'downloads_files_records', "record_location IS NOT NULL and record_type='upload'" ) as $file )
		{
			try
			{
				File::get( 'downloads_Files', $file['record_location'] )->delete();
			}
			catch( Exception $e ){}
		}
	}
}