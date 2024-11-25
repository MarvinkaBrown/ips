<?php
/**
 * @brief		File Storage Extension: Promote
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Feb 2017
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
 * File Storage Extension: Promote
 */
class Promote extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(promote_id)', 'core_content_promote', array( "promote_media != '[]'" ) )->first();
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
		$data = Db::i()->select( '*', 'core_content_promote', array( "promote_media != '[]'" ), 'promote_id', array( $offset, 1 ) )->first();
		$update = array();

		foreach( json_decode( $data['promote_media'], TRUE ) as $location )
		{
			if ( $location )
			{
				try
				{
					$update[] = (string) File::get( $oldConfiguration ?: 'core_Promote', $location )->move( $storageConfiguration );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
		}
		if ( $update )
		{
			Db::i()->update( 'core_content_promote', array( 'promote_media' => json_encode( $update ) ), array( 'promote_id=?', $data['promote_id'] ) );
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
		foreach( Db::i()->select( '*', 'core_content_promote', array( "promote_media!=?", '[]' ), 'promote_id' ) as $data )
		{
			foreach( json_decode( $data['promote_media'], TRUE ) as $location )
			{
				if ( (string) $file === $location )
				{
					return TRUE;
				}
			}
		}
		
		return FALSE;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( '*', 'core_content_promote', array( "promote_media!=?", '[]' ), 'promote_id' ) as $data )
		{
			foreach( json_decode( $data['promote_media'], TRUE ) as $location )
			{
				try
				{
					File::get( 'core_Promote', $location )->delete();
				}
				catch( Exception $e ){}
			}
		}
	}
}