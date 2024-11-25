<?php
/**
 * @brief		File Storage Extension: Emoticons
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
use IPS\Settings;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Emoticons
 */
class Emoticons extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'core_emoticons' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	UnderflowException					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void							An offset integer to use on the next cycle, or nothing
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		$emoticon = Db::i()->select( '*', 'core_emoticons', array(), 'id', array( $offset, 1 ) )->first();
		
		try
		{
			$file = File::get( $oldConfiguration ?: 'core_Emoticons', $emoticon['image'] )->move( $storageConfiguration );

			$image_2x = NULL;
			if ( $emoticon['image_2x'] )
			{
				$image_2x = File::get( $oldConfiguration ?: 'core_Emoticons', $emoticon['image_2x'] )->move( $storageConfiguration );
			}

			if ( (string) $file != $emoticon['image'] or (string) $image_2x != $emoticon['image_2x'] )
			{
				Db::i()->update( 'core_emoticons', array( 'image' => (string) $file, 'image_2x' => (string) $image_2x ), array( 'id=?', $emoticon['id'] ) );
			}
			
			Settings::i()->changeValues( array( 'emoji_cache' => time() ) );
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
			$emoticon	= Db::i()->select( '*', 'core_emoticons', array( 'image=? or image_2x=?', (string) $file, (string) $file ) )->first();

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
		foreach( Db::i()->select( '*', 'core_emoticons', 'image IS NOT NULL' ) as $emoticon )
		{
			try
			{
				File::get( 'core_Emoticons', $emoticon['image'] )->delete();
				File::get( 'core_Emoticons', $emoticon['image_2x'] )->delete();
			}
			catch( Exception $e ){}
		}
	}
}