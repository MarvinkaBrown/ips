<?php
/**
 * @brief		File Storage Extension: Ranks
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		25 Mar 2022
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
 * File Storage Extension: Ranks
 */
class Ranks extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'core_member_ranks', 'icon IS NOT NULL' )->first();
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
		$rankData = Db::i()->select( '*', 'core_member_ranks', 'icon IS NOT NULL', 'id', array( $offset, 1 ) )->first();

		try
		{
			$file = File::get( $oldConfiguration ?: 'core_Ranks', $rankData['icon'] )->move( $storageConfiguration );

			if ( (string) $file != $rankData['icon'] )
			{
				Db::i()->update( 'core_member_ranks', array( 'icon' => (string) $file ), array( 'id=?', $rankData['id'] ) );
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
			$rankData	= Db::i()->select( '*', 'core_member_ranks', array( 'icon=?', (string) $file ) )->first();

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
		foreach( Db::i()->select( '*', 'core_member_ranks', 'icon IS NOT NULL' ) as $rankData )
		{
			try
			{
				File::get( 'core_member_ranks', $rankData['icon'] )->delete();
			}
			catch( Exception $e ){}
		}
	}
}