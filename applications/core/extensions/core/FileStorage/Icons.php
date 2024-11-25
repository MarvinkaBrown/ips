<?php
/**
 * @brief		File Storage Extension: Icons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		01 Aug 2018
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Icons
 */
class Icons extends FileStorageAbstract
{
	/**
	 * @var int     Cache icons for 3mo
	 */
	public static int $cacheControlTtl = 7776000; // 3 mo

	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		$count	= Settings::i()->icons_favicon ? 1 : 0;

		if( Settings::i()->icons_sharer_logo )
		{
			$count	+= count( json_decode( Settings::i()->icons_sharer_logo, true ) );
		}

		if( Settings::i()->icons_homescreen )
		{
			$count	+= count( json_decode( Settings::i()->icons_homescreen, TRUE ) );
		}

		if( Settings::i()->icons_apple_startup )
		{
			$count	+= count( json_decode( Settings::i()->icons_apple_startup, TRUE ) );
		}

		if( Settings::i()->icons_mask_icon )
		{
			$count	+= 1;
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
		if( Settings::i()->icons_favicon )
		{
			try
			{
				File::get( $oldConfiguration ?: 'core_Icons', Settings::i()->icons_favicon )->move( $storageConfiguration );
			}
			catch( Exception $e )
			{
				/* Any issues are logged */
			}
		}

		if( Settings::i()->icons_sharer_logo )
		{
			$logos	= json_decode( Settings::i()->icons_sharer_logo, true );

			foreach( $logos as $logo )
			{
				try
				{
					File::get( $oldConfiguration ?: 'core_Icons', $logo )->move( $storageConfiguration );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
		}

		if( Settings::i()->icons_homescreen )
		{
			$homeScreen = json_decode( Settings::i()->icons_homescreen, TRUE );

			foreach( $homeScreen as $key => $logo )
			{
				try
				{
					File::get( $oldConfiguration ?: 'core_Icons', ( $key == 'original' ) ? $logo : $logo['url'] )->move( $storageConfiguration );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
		}

		if( Settings::i()->icons_apple_startup )
		{
			$apple = json_decode( Settings::i()->icons_apple_startup, TRUE );

			foreach( $apple as $key => $logo )
			{
				try
				{
					File::get( $oldConfiguration ?: 'core_Icons', ( $key == 'original' ) ? $logo : $logo['url'] )->move( $storageConfiguration );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
		}

		if( Settings::i()->icons_mask_icon )
		{
			try
			{
				File::get( $oldConfiguration ?: 'core_Icons', Settings::i()->icons_mask_icon )->move( $storageConfiguration );
			}
			catch( Exception $e )
			{
				/* Any issues are logged */
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
		if( Settings::i()->icons_favicon AND $file == Settings::i()->icons_favicon )
		{
			return TRUE;
		}

		if( Settings::i()->icons_sharer_logo )
		{
			$logos	= json_decode( Settings::i()->icons_sharer_logo, true );

			if( in_array( $file, $logos ) )
			{
				return TRUE;
			}
		}

		if( Settings::i()->icons_homescreen )
		{
			foreach( json_decode( Settings::i()->icons_homescreen, TRUE ) as $key => $logo )
			{
				if( ( $key == 'original' AND $file == $logo ) OR ( $key != 'original' AND $file == $logo['url'] ) )
				{
					return TRUE;
				}
			}
		}

		if( Settings::i()->icons_apple_startup )
		{
			foreach( json_decode( Settings::i()->icons_apple_startup, TRUE ) as $key => $logo )
			{
				if( ( $key == 'original' AND $file == $logo ) OR ( $key != 'original' AND $file == $logo['url'] ) )
				{
					return TRUE;
				}
			}
		}

		if( Settings::i()->icons_mask_icon AND $file == Settings::i()->icons_mask_icon )
		{
			return TRUE;
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
		if( Settings::i()->icons_favicon )
		{
			try
			{
				File::get( 'core_Icons', Settings::i()->icons_favicon )->delete();
			}
			catch( OutOfRangeException $e ){}
		}

		if( Settings::i()->icons_sharer_logo )
		{
			foreach( json_decode( Settings::i()->icons_sharer_logo, true ) as $logo )
			{
				try
				{
					File::get( 'core_Icons', $logo )->delete();
				}
				catch( OutOfRangeException $e ){}
			}
		}

		if( Settings::i()->icons_homescreen )
		{
			foreach( json_decode( Settings::i()->icons_homescreen, TRUE ) as $key => $logo )
			{
				try
				{
					File::get( 'core_Icons', ( $key == 'original' ) ? $logo : $logo['url'] )->delete();
				}
				catch( OutOfRangeException $e ){}
			}
		}

		if( Settings::i()->icons_apple_startup )
		{
			foreach( json_decode( Settings::i()->icons_apple_startup, TRUE ) as $key => $logo )
			{
				try
				{
					File::get( 'core_Icons', ( $key == 'original' ) ? $logo : $logo['url'] )->delete();
				}
				catch( OutOfRangeException $e ){}
			}
		}

		if( Settings::i()->icons_mask_icon )
		{
			try
			{
				File::get( 'core_Icons', Settings::i()->icons_mask_icon )->delete();
			}
			catch( OutOfRangeException $e ){}
		}

		Settings::i()->changeValues( array( 'icons_favicon' => NULL, 'icons_sharer_logo' => NULL, 'icons_homescreen' => NULL, 'icons_apple_startup' => NULL, 'icons_mask_icon' => NULL ) );
	}
}