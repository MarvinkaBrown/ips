<?php
/**
 * @brief		File Storage Extension: Package Groups
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		5 May 2014
 */

namespace IPS\nexus\extensions\core\FileStorage;

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
 * File Storage Extension: Package Groups
 */
class PackageGroups extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'nexus_package_groups', array( 'pg_image<>?', '' ) )->first();
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
		$record = Db::i()->select( '*', 'nexus_package_groups', array( 'pg_image<>?', '' ), 'pg_id', array( $offset, 1 ) )->first();
		
		try
		{
			$file = File::get( $oldConfiguration ?: 'nexus_PackageGroups', $record['pg_image'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['pg_image'] )
			{
				Db::i()->update( 'nexus_package_groups', array( 'pg_image' => (string) $file ), array( 'pg_id=?', $record['pg_id'] ) );
			}
		}
		catch( Exception )
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
			Db::i()->select( '*', 'nexus_package_groups', array( 'pg_image=?', (string) $file ) )->first();
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
		foreach( Db::i()->select( '*', 'nexus_package_groups', array( 'pg_image<>?', '' ) ) as $product )
		{
			try
			{
				File::get( 'nexus_PackageGroups', $product['pg_image'] )->delete();
			}
			catch( Exception ){}
		}
	}
}