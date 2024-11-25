<?php
/**
 * @brief		File Storage Extension: PagesImages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		15 Dec 2023
 */

namespace IPS\cms\extensions\core\FileStorage;

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
 * File Storage Extension: PagesImages
 */
class PagesImages extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( 'COUNT(page_id)', 'cms_pages', 'page_meta_image is not null' )->first();
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
		$row = Db::i()->select( 'page_id,page_meta_image', 'cms_pages', 'page_meta_image is not null', 'page_id', [ $offset, 1 ] )->first();
		try
		{
			$file = File::get( $oldConfiguration ?: 'cms_PagesImages', $row['page_meta_image'] )->move( $storageConfiguration );
			if( (string) $file != $row['page_meta_image'] )
			{
				Db::i()->update( 'cms_pages', [ 'page_meta_image' => (string) $file ], [ 'page_id=?', $row['page_id'] ] );
			}
		}
		catch( Exception ){}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param	File|string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( File|string $file ): bool
	{
	    return (bool) Db::i()->select( 'count(page_id)', 'cms_pages', [ 'page_meta_image=?', (string) $file ] )->first();
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( 'page_meta_image', 'cms_pages', 'page_meta_image is not null', 'page_id' ) as $image )
		{
			try
			{
				File::get( 'cms_PagesImages', $image )->delete();
			}
			catch( Exception ){}
		}
	}
}