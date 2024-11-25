<?php
/**
 * @brief		File Storage Extension: ProfileField
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Aug 2014
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
 * File Storage Extension: ProfileField
 */
class ProfileField extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		$count = 0;
		foreach( Db::i()->select( '*', 'core_pfields_data', array( 'pf_type=?', 'Upload' ) ) AS $field )
		{
			$count += Db::i()->select( 'COUNT(*)', 'core_pfields_content', array( "field_{$field['pf_id']}<>? OR field_{$field['pf_id']} IS NOT NULL", '' ) )->first();
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
		if( !Db::i()->select( 'COUNT(*)', 'core_pfields_data', array( 'pf_type=?', 'Upload' ) )->first() )
		{
			throw new Underflowexception;
		}

		foreach( Db::i()->select( '*', 'core_pfields_data', array( 'pf_type=?', 'Upload' ) ) AS $field )
		{
			$cfield	= Db::i()->select( '*', 'core_pfields_content', array( "field_{$field['pf_id']}<>? OR field_{$field['pf_id']} IS NOT NULL", '' ), 'member_id', array( $offset, 1 ) )->first();
			
			try
			{
				$file = File::get( $oldConfiguration ?: 'core_ProfileField', $cfield[ 'field_' . $field['pf_id'] ] )->move( $storageConfiguration );
				
				if ( (string) $file !== $cfield[ 'field_' . $field['pf_id'] ] )
				{
					Db::i()->update( 'core_pfields_content', array( "field_{$field['pf_id']}=?", (string) $file ), array( 'member_id=?', $cfield['member_id'] ) );
				}
			}
			catch( Exception $e )
			{
				/* Any issues are logged and the \IPS\Db::i()->update not run as the exception is thrown */
			}
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
		$valid = FALSE;
		foreach( Db::i()->select( '*', 'core_pfields_data', array( 'pf_type=?', 'Upload' ) ) AS $field )
		{
			try
			{
				Db::i()->select( '*', 'core_pfields_content', array( "field_{$field['pf_id']}=?", (string) $file ) )->first();
				
				$valid = TRUE;
				break;
			}
			catch( UnderflowException $e ) {}
		}
		
		return $valid;
	}

	/**
	 * Delete all stored files
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		$fieldIds = iterator_to_array( Db::i()->select( 'pf_id', 'core_pfields_data', array( 'pf_type=?', 'Upload' ) ) );
		foreach( Db::i()->select( '*', 'core_pfields_content' ) as $row )
		{
			foreach( $fieldIds as $fieldId )
			{
				if( $row['field_' . $fieldId] !== null )
				{
					try
					{
						File::get( 'core_ProfileField', $row['field_' . $fieldId ] )->delete();
					}
					catch( Exception ){}
				}
			}
		}
	}
}