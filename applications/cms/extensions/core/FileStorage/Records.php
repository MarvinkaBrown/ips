<?php
/**
 * @brief		File Storage Extension: Records
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		11 April 2014
 */

namespace IPS\cms\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Databases;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use UnderflowException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Records
 */
class Records extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		$count = 0;
		
		$fields = array();
		foreach( Db::i()->select( '*', 'cms_database_fields', array( 'LOWER(field_type)=\'upload\'' ) ) as $field )
		{
			$fields[ $field['field_database_id'] ][] = $field['field_id'];
		}
		
		foreach( Databases::databases() as $id => $db )
		{
			$where = array( 'record_image IS NOT NULL' );
			
			if ( isset( $fields[ $id ] ) )
			{
				foreach( $fields[ $id ] as $field_id )
				{
					$where[] = 'field_' . $field_id . ' IS NOT NULL';
				}
			}
			
			$count += Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $id, implode( ' OR ', $where ) )->first();
		}
		
		return $count;
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	Underflowexception					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		if ( ! isset( Store::i()->cms_files_move_data ) )
		{
			foreach( Databases::databases() as $id => $database )
			{
				$data[ $id ] = 0;
			}
			
			Store::i()->cms_files_move_data = $data;
		}
		
		$data = Store::i()->cms_files_move_data;

		if ( ! is_countable( $data ) OR ! count( $data ) )
		{
			/* all done */
			unset( Store::i()->cms_files_move_data );
			
			throw new UnderflowException;
		}
		
		reset( $data );
		$databaseId = key( $data );
		$realOffset = $data[ $databaseId ];
		
		$done   = 0;
		$fields = array( 'record_image', 'record_image_thumb' );
		foreach( Db::i()->select( '*', 'cms_database_fields', array( 'field_database_id=? AND LOWER(field_type)=\'upload\'', $databaseId ) ) as $field )
		{
			$fields[] = 'field_' . $field['field_id'];
		}
		
		$conditions = array();
		
		foreach( $fields as $field )
		{
			$conditions[] = $field . ' IS NOT NULL';
		}
		
		$where = array( implode( ' OR ', $conditions ) );

		foreach( Db::i()->select( '*', 'cms_custom_database_' . $databaseId, $where, 'primary_id_field ASC', array( $realOffset, 1 ) ) as $row )
		{
			foreach( $fields as $field )
			{
				if ( isset( $row[ $field ] ) and ! empty( $row[ $field ] ) )
				{
					if ( mb_strstr( $row[ $field ], ',' ) )
					{
						$files = explode( ',', $row[ $field ] );
					}
					else
					{
						$files = array( $row[ $field ] );
					}
					
					$save		= FALSE;
					$fixed		= FALSE;
					
					foreach( $files as $file )
					{
						try
						{
							$check = File::get( $oldConfiguration ?: 'cms_Records', $file )->move( $storageConfiguration );
							
							if ( (string) $file != $check )
							{
								$fixed  = TRUE;
								$save[] = $check;
							}
							else
							{
								$save[]  = $file;
							}
						}
						catch( Exception $e )
						{
							$save[] = $file;
						}
					}
					
					if ( $fixed )
					{
						Db::i()->update( 'cms_custom_database_' . $databaseId, array( $field => implode( ',', $save ) ), array( 'primary_id_field=?', $row['primary_id_field'] ) );
					}
				}
			}
			
			$done++;
		}
		
		if ( $done )
		{
			$data[ $databaseId ] = $realOffset + 1;
		}
		else
		{
			/* Assume all done */
			unset( $data[ $databaseId ] );
		}
		
		if ( ! count( $data ) )
		{
			if ( isset( Store::i()->cms_files_move_data ) )
			{
				unset( Store::i()->cms_files_move_data );
			}
			
			throw new Underflowexception;
		}
		else
		{	
			Store::i()->cms_files_move_data = $data;
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
			foreach( Databases::databases() as $id => $db )
			{
				$theFile = Db::i()->select( '*', 'cms_custom_database_' . $id, array( 'record_image=? OR record_image_thumb=?', (string) $file, (string) $file ) )->first();

				return TRUE;
			}
		}
		catch ( UnderflowException $e )
		{
			/* Gather all the upload fields */
			$fields = array();
			foreach( Db::i()->select( '*', 'cms_database_fields', array( 'LOWER(field_type)=\'upload\'' ) ) as $field )
			{
				$fields[ $field['field_database_id'] ][] = $field['field_id'];
			}
			
			if ( is_array( $fields ) )
			{
				foreach( $fields as $databaseId => $data )
				{
					foreach( $data as $fieldId )
					{
						try
						{
							$record	= Db::i()->select( '*', 'cms_custom_database_' . $databaseId, 'field_' . $fieldId . " LIKE '%" . (string) $file . "%'" )->first();
				
							return TRUE;
						}
						catch ( UnderflowException $e )
						{
							/* Might be in another field */
						}
	
					}
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
		foreach( Databases::databases() as $databaseId => $database )
		{
			$fields = array( 'record_image', 'record_image_thumb' );
			foreach( Db::i()->select( '*', 'cms_database_fields', array( 'field_database_id=? AND LOWER(field_type)=\'upload\'', $databaseId ) ) as $field )
			{
				$fields[] = 'field_' . $field['field_id'];
			}
			
			$where = array();
			
			foreach( $fields as $field )
			{
				$where[] = array( $field . ' IS NOT NULL' );
			}
	
			foreach( Db::i()->select( '*', 'cms_custom_database_' . $databaseId, $where, 'primary_id_field ASC' ) as $row )
			{
				foreach( $fields as $field )
				{
					if ( isset( $row[ $field ] ) and ! empty( $row[ $field ] ) )
					{
						File::get( 'cms_Records', $row[ $field ] )->delete();
					}
				}
			}
		}
	}
}