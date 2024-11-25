<?php
/**
 * @brief		File Storage Extension: Tags
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		29 Mar 2024
 */

namespace IPS\core\extensions\core\FileStorage;

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
 * File Storage Extension: Tags
 */
class Tags extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( 'count(tag_id)', 'core_tags_data', 'tag_cover_photo is not null' )->first();
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
		$row = Db::i()->select( 'tag_id,tag_cover_photo', 'core_tags_data', 'tag_cover_photo is not null', 'tag_id', [ 0, 1 ] )->first();
		try
		{
			$file = File::get( $oldConfiguration ?: 'core_Tags', $row['tag_cover_photo'] )->move( $storageConfiguration );
			if( (string) $file != $row['tag_cover_photo'] )
			{
				Db::i()->update( 'core_tags_data', [ 'tag_cover_photo' => (string) $file ], [ 'tag_id=?', $row['tag_id'] ] );
			}
		}
		catch( OutOfRangeException ){}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param	File|string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( File|string $file ): bool
	{
		return (bool) Db::i()->select( 'count(tag_id)', 'core_tags_data', [ 'tag_cover_photo=?', (string) $file ] )->first();
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( 'tag_cover_photo', 'core_tags_data', 'tag_cover_photo is not null', 'tag_id' ) as $image )
		{
			try
			{
				File::get( 'core_Tags', $image )->delete();
			}
			catch( OutOfRangeException ){}
		}
	}
}