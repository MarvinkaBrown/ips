<?php
/**
 * @brief		File Storage Extension: Reaction
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Feb 2017
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
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
 * File Storage Extension: Reaction
 */
class Reaction extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( 'COUNT(*)', 'core_reactions', ['reaction_use_custom=0'] )->first();
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
		$reaction = Db::i()->select( '*', 'core_reactions', ['reaction_use_custom=0'], 'reaction_id', array( $offset, 1 ) )->first();

		try
		{
			$url = (string) File::get( $oldConfiguration ?: 'core_Reaction', $reaction['reaction_icon'] )->move( $storageConfiguration );
		}
		catch( Exception $e )
		{
			/* Any issues are logged */
		}

		if ( !empty( $url ) AND $url != $reaction['reaction_icon'] )
		{
			Db::i()->update( 'core_reactions', array( 'reaction_icon' => $url ), array( 'reaction_id=?', $reaction['reaction_id'] ) );
			unset( Store::i()->reactions );
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
			Db::i()->select( '*', 'core_reactions', ["reaction_icon=?", (string) $file] )->first();
			
			return TRUE;
		}
		catch( UnderflowException $e )
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
		foreach( Db::i()->select( '*', 'core_reactions', ['reaction_use_custom=0'] ) AS $row )
		{
			File::get( 'core_Reaction', $row['reaction_icon'] )->delete();
		}
	}
}