<?php
/**
 * @brief		File Storage Extension: Events
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		13 Jan 2014
 */

namespace IPS\calendar\extensions\core\FileStorage;

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
 * File Storage Extension: Events
 */
class Events extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'calendar_events', 'event_cover_photo IS NOT NULL' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int $offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param int $storageConfiguration	New storage configuration ID
	 * @param int|null $oldConfiguration		Old storage configuration ID
	 * @return	void
	 *@throws	Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		$record	= Db::i()->select( '*', 'calendar_events', 'event_cover_photo IS NOT NULL', 'event_id', array( $offset, 1 ) )->first();
		
		try
		{
			$file	= File::get( $oldConfiguration ?: 'calendar_Events', $record['event_cover_photo'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['event_cover_photo'] )
			{
				Db::i()->update( 'calendar_events', array( 'event_cover_photo' => (string) $file ), array( 'event_id=?', $record['event_id'] ) );
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
			$record	= Db::i()->select( '*', 'calendar_events', array( 'event_cover_photo=?', (string) $file ) )->first();

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
		foreach( Db::i()->select( '*', 'calendar_events', 'event_cover_photo IS NOT NULL' ) as $event )
		{
			try
			{
				File::get( 'calendar_Events', $event['event_cover_photo'] )->delete();
			}
			catch( Exception $e ){}
		}
	}
}