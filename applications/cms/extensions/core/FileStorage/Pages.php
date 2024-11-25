<?php
/**
 * @brief		File Storage Extension: Pages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		20 October 2014
 */

namespace IPS\cms\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Pages\Page;
use IPS\Db;
use IPS\Db\Exception;
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
 * File Storage Extension: CMS Pages
 */
class Pages extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return 1; # Number of steps needed to clear/move files
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
		/* Just remove page object data so it will rebuild on the next iteration */
		Page::deleteCachedIncludes( NULL, $oldConfiguration );
		
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
		$bits = explode( '/', (string) $file );
		$name = array_pop( $bits );

		try
		{
			foreach( Db::i()->select( '*', 'cms_templates', array( "template_file_object LIKE '%" . Db::i()->escape_string( $name ) . "%'") ) as $template )
			{
				$fileObject = File::get( 'core_Theme', $template['template_file_object'] );

				if( $fileObject->url == (string) $file )
				{
					return TRUE;
				}
			}
			
			return FALSE;
		}
		catch( Exception $e )
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
		Page::deleteCachedIncludes();
	}
}