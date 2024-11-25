<?php
/**
 * @brief		File Storage Extension: Customer Fields
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		29 Aug 2014
 */

namespace IPS\nexus\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use IPS\nexus\Customer\CustomField;
use Underflowexception;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Customer Fields
 */
class Customer extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		$where = array();
		foreach ( CustomField::roots( NULL, NULL, array( 'f_type=?', 'Upload' ) ) as $field )
		{
			$where[] = "field_{$field->id}<>''";
		}
		if ( !count( $where ) )
		{
			return 0;
		}
		
		return Db::i()->select( 'COUNT(*)', 'nexus_customers', implode( ' OR ', $where ) )->first();
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
		$ids = array();
		$where = array();
		foreach ( CustomField::roots( NULL, NULL, array( 'f_type=?', 'Upload' ) ) as $field )
		{
			$ids[] = $field->id;
			$where[] = "field_{$field->id}<>''";
		}
		if ( !count( $where ) )
		{
			throw new Underflowexception;
		}
		
		$customer = Db::i()->select( '*', 'nexus_customers', implode( ' OR ', $where ) )->first();
		$update = array();
		foreach ( $ids as $id )
		{
			try
			{
				$update[ 'field_' . $id ] = (string) File::get( $oldConfiguration ?: 'nexus_Customer', $update[ 'field_' . $id ] )->move( $storageConfiguration );
			}
			catch( Exception )
			{
				/* Any issues are logged */
			}
		}
		
		if ( count( $update ) )
		{
			foreach( $update as $k => $v )
			{
				if ( $update[ $k ] == $customer[ $k ] )
				{
					unset( $update[ $k ] );
				}
			}
			
			if ( count( $update ) )
			{
				Db::i()->update( 'nexus_customers', $update, array( 'member_id=?', $customer['member_id'] ) );
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
		$_where = array();
		foreach ( CustomField::roots( NULL, NULL, array( 'f_type=?', 'Upload' ) ) as $field )
		{
			$_where[] = "field_{$field->id}=?";
		}
		if ( !count( $_where ) )
		{
			return FALSE;
		}
		
		$where = array_fill( 0, count( $_where ), (string) $file );
		array_unshift( $where, implode( ' OR ', $_where ) );
		
		return (bool) Db::i()->select( 'COUNT(*)', 'nexus_customers', $where )->first();
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		$ids = array();
		$where = array();
		foreach ( CustomField::roots( NULL, NULL, array( 'f_type=?', 'Upload' ) ) as $field )
		{
			$ids[] = $field->id;
			$where[] = "field_{$field->id}<>''";
		}
		if ( !count( $where ) )
		{
			return;
		}
		
		foreach ( Db::i()->select( '*', 'nexus_customers', implode( ' OR ', $where ) ) as $customer )
		{
			foreach ( $ids as $id )
			{
				try
				{
					File::get( 'nexus_Customer', $customer[ 'field_' . $id ] )->delete();
				}
				catch( Exception ){}
			}
		}
	}
}