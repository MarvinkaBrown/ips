<?php
/**
 * @brief		File Storage Extension: Categories
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		18 Dec 2023
 */

namespace IPS\cms\extensions\core\FileStorage;

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
 * File Storage Extension: Categories
 */
class Categories extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( 'count(category_id)', 'cms_database_categories', 'category_image is not null' )->first();
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
		$row = Db::i()->select( 'category_id,category_image', 'cms_database_categories', 'category_image is not null', 'category_id', array( $offset, 1 ) )->first();
		try
		{
			$file = File::get( $oldConfiguration ?: 'cms_Categories', $row['category_image'] )->move( $storageConfiguration );
			if( (string) $file != $row['category_image'] )
			{
				Db::i()->update( 'cms_database_categories', [ 'category_image' => (string) $file ], [ 'category_id=?', $row['category_id']] );
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
	    return (bool) Db::i()->select( 'count(category_id)', 'cms_database_categories', [ 'category_image=?', (string) $file ] )->first();
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( 'category_image', 'cms_database_categories', 'category_image is not null', 'category_id' ) as $image )
		{
			try
			{
				File::get( 'cms_Categories', $image )->delete();
			}
			catch( OutOfRangeException ){}
		}
	}
}