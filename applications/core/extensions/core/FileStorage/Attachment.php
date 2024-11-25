<?php
/**
 * @brief		File Storage Extension: Attachment
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Sep 2013
 */

namespace IPS\core\extensions\core\FileStorage;

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
 * File Storage Extension: Attachment
 */
class Attachment extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( 'MAX(attach_id)', 'core_attachments' )->first();
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
		$attachment = Db::i()->select( '*', 'core_attachments', null, 'attach_id', array( $offset, 1 ) )->first();

		try
		{
			$file = File::get( $oldConfiguration ?: 'core_Attachment', $attachment['attach_location'] )->move( $storageConfiguration );
	
			$thumb = NULL;
			if ( $attachment['attach_thumb_location'] )
			{
				$thumb = File::get( $oldConfiguration ?: 'core_Attachment', $attachment['attach_thumb_location'] )->move( $storageConfiguration );
			}
			
			if ( (string) $file != $attachment['attach_location'] or (string) $thumb != $attachment['attach_thumb_location'] )
			{
				Db::i()->update( 'core_attachments', array( 'attach_location' => (string) $file, 'attach_thumb_location' => (string) $thumb ), array( 'attach_id=?', $attachment['attach_id'] ) );
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
			$attachment	= Db::i()->select( '*', 'core_attachments', array( 'attach_location=? OR attach_thumb_location=?', (string) $file, (string) $file ) )->first();

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
		foreach( Db::i()->select( '*', 'core_attachments', 'attach_location IS NOT NULL' ) as $attachment )
		{
			try
			{
				File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
			}
			catch( Exception $e ){}
		}
	}
}