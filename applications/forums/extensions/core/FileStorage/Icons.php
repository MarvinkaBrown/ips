<?php
/**
 * @brief		File Storage Extension: Icons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		06 Feb 2014
 */

namespace IPS\forums\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use IPS\forums\Forum;
use UnderflowException;
use function defined;
use function json_decode;
use function json_last_error;
use const JSON_ERROR_NONE;

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
		return Db::i()->select( 'COUNT(*)', 'forums_forums', 'icon IS NOT NULL' )->first();
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
		$forum = Forum::constructFromData( Db::i()->select( '*', 'forums_forums', 'icon IS NOT NULL', 'id', array( $offset, 1 ) )->first() );
		
		try
		{
			json_decode( $forum->icon );
			if ( json_last_error() !== JSON_ERROR_NONE )
			{
				$forum->icon = File::get( $oldConfiguration ?: 'forums_Icons', $forum->icon )->move( $storageConfiguration );
				$forum->save();
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
			Db::i()->select( 'id', 'forums_forums', array( 'icon=?', $file ) )->first();
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
		foreach( Db::i()->select( '*', 'forums_forums', "icon IS NOT NULL" ) as $forum )
		{
			try
			{
				File::get( 'forums_Icons', $forum['icon'] )->delete();
			}
			catch( Exception $e ){}
		}
	}
}