<?php
/**
 * @brief		File Storage Extension: Screenshots
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
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Screenshots
 */
class Screenshots extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'downloads_files_records', array( 'record_type=?', 'ssupload' ) )->first();
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
		$record		= Db::i()->select( '*', 'downloads_files_records', array( 'record_type=?', 'ssupload' ), 'record_id', array( $offset, 1 ) )->first();
		$updates	= array();

		try
		{
			$file = File::get( $oldConfiguration ?: 'downloads_Screenshots', $record['record_location'] )->move( $storageConfiguration );

			if ( (string) $file != $record['record_location'] )
			{
				$updates['record_location'] = (string) $file;
			}
			
			$file = File::get( $oldConfiguration ?: 'downloads_Screenshots', $record['record_thumb'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['record_thumb'] )
			{
				$updates['record_thumb'] = (string) $file;
			}

			if( $record['record_no_watermark'] )
			{
				$file = File::get( $oldConfiguration ?: 'downloads_Screenshots', $record['record_no_watermark'] )->move( $storageConfiguration );
				
				if ( (string) $file != $record['record_no_watermark'] )
				{
					$updates['record_no_watermark'] = (string) $file;
				}
			}
		}
		catch( Exception $e )
		{
			/* Any issues are logged */
		}

		if( count( $updates ) )
		{
			Db::i()->update( 'downloads_files_records', $updates, array( 'record_id=?', $record['record_id'] ) );
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
			$fileName = (string) $file;
			$record	= Db::i()->select( '*', 'downloads_files_records', array( '( record_location=? OR record_thumb=? OR record_no_watermark=? ) AND record_type=?', $fileName, $fileName, $fileName, 'ssupload' ) )->first();

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
		foreach( Db::i()->select( '*', 'downloads_files_records', "record_location IS NOT NULL and record_type='ssupload'" ) as $screenshot )
		{
			try
			{
				File::get( 'downloads_Screenshots', $screenshot['record_location'] )->delete();
			}
			catch( Exception $e ){}

			if( $screenshot['record_thumb'] )
			{
				try
				{
					File::get( 'downloads_Screenshots', $screenshot['record_thumb'] )->delete();
				}
				catch( Exception $e ){}
			}

			if( $screenshot['record_no_watermark'] )
			{
				try
				{
					File::get( 'downloads_Screenshots', $screenshot['record_no_watermark'] )->delete();
				}
				catch( Exception $e ){}
			}
		}
	}
}