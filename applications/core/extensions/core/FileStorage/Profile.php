<?php
/**
 * @brief		File Storage Extension: Profile
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Sep 2013
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Profile
 */
class Profile extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( 'MAX(member_id)', 'core_members', array( "NULLIF(pp_cover_photo, '') IS NOT NULL OR NULLIF(pp_main_photo, '') IS NOT NULL" ) )->first();
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
		$memberData = Db::i()->select( '*', 'core_members', array( "( NULLIF(pp_cover_photo, '') IS NOT NULL OR NULLIF(pp_main_photo, '') IS NOT NULL )" ), 'member_id', array( $offset, 1 ) )->first();
		$update = array();
		
		foreach( array( 'pp_cover_photo', 'pp_main_photo', 'pp_thumb_photo' ) as $location )
		{
			if ( $memberData[ $location ] )
			{
				try
				{
					$update[ $location ] = (string) File::get( $oldConfiguration ?: 'core_Profile', $memberData[ $location ] )->move( $storageConfiguration );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
		}
		
		if ( count( $update ) )
		{
			foreach( $update as $k => $url )
			{
				if ( $url == $memberData[ $k ] )
				{
					/* No need to update the database */
					unset( $update[ $k ] );
				}	
			}
			
			if ( count( $update ) )
			{
				Db::i()->update( 'core_members', $update, array( 'member_id=?', $memberData['member_id'] ) );
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
		try
		{
			$photo	= Db::i()->select( '*', 'core_members', array( 'pp_cover_photo=? OR pp_main_photo=? OR pp_thumb_photo=?', (string) $file, (string) $file, (string) $file ) )->first();

			return TRUE;
		}
		catch ( UnderflowException $e )
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
		foreach( Db::i()->select( '*', 'core_members', 'pp_cover_photo IS NOT NULL OR pp_main_photo IS NOT NULL OR pp_thumb_photo IS NOT NULL' ) as $member )
		{
			try
			{
				if( $member['pp_cover_photo'] )
				{
					File::get( 'core_Profile', $member['pp_cover_photo'] )->delete();
				}
				
				if ( mb_substr( $member['pp_photo_type'], 0, 5 ) === 'sync-' or $member['pp_photo_type'] === 'custom' or $member['pp_photo_type'] === 'letter' )
				{
					if( $member['pp_main_photo'] )
					{
						File::get( 'core_Profile', $member['pp_main_photo'] )->delete();
					}
					
					if( $member['pp_thumb_photo'] )
					{
						File::get( 'core_Profile', $member['pp_thumb_photo'] )->delete();
					}
				}
			}
			catch( Exception $e ){}
		}
	}
}