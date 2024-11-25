<?php
/**
 * @brief		File Storage Extension: Purchase Custom Fields
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		23 Feb 2015
 */

namespace IPS\nexus\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use IPS\nexus\Package\CustomField;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Purchase Custom Fields
 */
class PurchaseFields extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return Db::i()->select( 'COUNT(*)', 'nexus_package_fields', array( 'cf_type=?', 'upload' ) )->first();
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
		$customFields = CustomField::roots( NULL, NULL, array( 'cf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			$packages = array();
			foreach ( $customFields as $field )
			{
				if ( $field->packages )
				{
					$packages = array_merge( $packages, explode( ',', $field->packages ) );
				}
			}
			
			$purchase = Db::i()->select( '*', 'nexus_purchases', array(
				array( 'ps_app=?', 'nexus' ),
				array( 'ps_type=?', 'package' ),
				array( Db::i()->in( 'ps_item_id', array_unique( $packages ) ) )
			), 'ps_id', array( $offset, 1 ) )->first();
			
			$fieldValues = json_decode( $purchase['ps_custom_fields'], TRUE );
			foreach ( $fieldValues as $k => $v )
			{
				if ( array_key_exists( $k, $customFields ) )
				{
					try
					{
						$fieldValues[ $k ] = File::get( $oldConfiguration ?: 'nexus_PurchaseFields', $fieldValues[ $k ] )->move( $storageConfiguration );
					}
					catch( Exception )
					{
						/* Any issues are logged */
					}
				}
			}
			
			Db::i()->update( 'nexus_purchases', array( 'ps_custom_fields' => json_encode( $fieldValues ) ), array( 'ps_id=?', $purchase['ps_id'] ) );
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
		$customFields = CustomField::roots( NULL, NULL, array( 'cf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			foreach ( Db::i()->select( '*', 'nexus_purchases', array( "ps_custom_fields LIKE ?", "%" . str_replace( '\\', '\\\\\\', trim( json_encode( (string) $file ), '"' ) . "%" ) ) ) as $purchase )
			{
				$fieldValues = json_decode( $purchase['ps_custom_fields'], TRUE );
				foreach ( $customFields as $field )
				{
					if ( $fieldValues[ $field->id ] == (string) $file )
					{
						return TRUE;
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
		$customFields = CustomField::roots( NULL, NULL, array( 'cf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			$packages = array();
			foreach ( $customFields as $field )
			{
				if ( $field->packages )
				{
					$packages = array_merge( $packages, explode( ',', $field->packages ) );
				}
			}
			
			$where = array(
				array( 'ps_app=?', 'nexus' ),
				array( 'ps_type=?', 'package' ),
				array( Db::i()->in( 'ps_item_id', array_unique( $packages ) ) )
			);

			foreach( Db::i()->select( '*', 'nexus_purchases', $where ) as $purchase )
			{
				$fieldValues = json_decode( $purchase['ps_custom_fields'], TRUE );
				foreach ( $fieldValues as $k => $v )
				{
					if ( array_key_exists( $k, $customFields ) )
					{
						try
						{
							File::get( 'nexus_PurchaseFields', $fieldValues[ $k ] )->delete();
						}
						catch( Exception ){}
					}
				}
			}
		}
	}
}