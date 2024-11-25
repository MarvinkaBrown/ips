<?php
/**
 * @brief		File Storage Extension: Blog Entries (cover photos)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		30 May 2014
 */

namespace IPS\blog\extensions\core\FileStorage;

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
 * File Storage Extension: Blog Entries (cover photos)
 */
class Entries extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'blog_entries', 'entry_cover_photo IS NOT NULL' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int $offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param int $storageConfiguration	New storage configuration ID
	 * @param int|null $oldConfiguration		Old storage configuration ID
	 * @return	void
	 *@throws	UnderflowException					When file record doesn't exist. Indicating there are no more files to move
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		$record	= Db::i()->select( '*', 'blog_entries', 'entry_cover_photo IS NOT NULL', 'entry_id', array( $offset, 1 ) )->first();
		
		try
		{
			$file	= File::get( $oldConfiguration ?: 'blog_Entries', $record['entry_cover_photo'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['entry_cover_photo'] )
			{
				Db::i()->update( 'blog_entries', array( 'entry_cover_photo' => (string) $file ), array( 'entry_id=?', $record['entry_id'] ) );
			}
		}
		catch( Exception )
		{
			/* Any issues are logged and the \IPS\Db::i()->update not run as the exception is thrown */
		}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param File|string $file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( File|string $file ): bool
	{
		try
		{
			$record	= Db::i()->select( '*', 'blog_entries', array( 'entry_cover_photo=?', (string) $file ) )->first();

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
		foreach( Db::i()->select( '*', 'blog_entries', 'entry_cover_photo IS NOT NULL' ) as $blog )
		{
			try
			{
				File::get( 'blog_Entries', $blog['entry_cover_photo'] )->delete();
			}
			catch( Exception ){}
		}
	}
}