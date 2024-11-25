<?php
/**
 * @brief		File Storage Extension: ClubField
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		11 Aug 2017
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use OutOfRangeException;
use Throwable;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: ClubField
 */
class ClubField extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		$count = 0;
		foreach( Db::i()->select( '*', 'core_clubs_fields', array( "f_type=?", 'Upload' ) ) AS $row )
		{
			$count += Db::i()->select( 'COUNT(*)', 'core_clubs_fieldvalues', array( "field_{$row['f_id']}<>? AND field_{$row['f_id']} IS NOT NULL", '' ) )->first();
		}
		return $count;
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
		foreach( Db::i()->select( '*', 'core_clubs_fields', array( "f_type=?", 'Upload' ) ) AS $row )
		{
			foreach( Db::i()->select( '*', 'core_clubs_fieldvalues', array( "field_{$row['f_id']}<>? AND field_{$row['f_id']} IS NOT NULL", '' ) ) AS $field )
			{
				try
				{
					$moved = File::get( $oldConfiguration ?: 'core_ClubField', $field[ "field_{$row['f_id']}" ] )->move( $storageConfiguration );
					Db::i()->update( 'core_clubs_fieldvalues', array( "field_{$row['f_id']}" => (string) $moved ), array( "club_id=?", $field['club_id'] ) );
				}
				catch( Exception |Throwable $e ){}
			}
		}
		
		throw new UnderflowException;
	}
	
	/**
	 * Check if a file is valid
	 *
	 * @param	File|string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( File|string $file ): bool
	{
		foreach( Db::i()->select( '*', 'core_clubs_fields', array( "f_type=?", 'Upload' ) ) AS $row )
		{
			if ( Db::i()->select( 'COUNT(*)', 'core_clubs_fieldvalues', array( "field_{$row['f_id']}=?", (string) $file ) )->first() )
			{
				return TRUE;
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
		foreach( Db::i()->select( '*', 'core_clubs_fields', array( "f_type=?", 'Upload' ) ) AS $row )
		{
			foreach( Db::i()->select( '*', 'core_clubs_fieldvalues', array( "field_{$row['f_id']}<>? AND field_{$row['f_id']} IS NOT NULL" ) ) AS $field )
			{
				try
				{
					File::get( 'core_ClubField', $field[ "field_{$row['f_id']}" ] )->delete();
				}
				catch( OutOfRangeException $e ) {}
				
				Db::i()->update( 'core_clubs_fieldvalues', array( "field_{$row['f_id']}" => NULL ), array( "club_id=?", $field['club_id'] ) );
			}
		}
	}
}