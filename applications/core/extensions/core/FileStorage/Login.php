<?php
/**
 * @brief		File Storage Extension: Login Methods
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		31 May 2017
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
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
 * File Storage Extension: Login Methods
 */
class Login extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		$count = 0;
		foreach ( Db::i()->select( '*', 'core_login_methods' ) as $method )
		{
			$settings = json_decode( $method['login_settings'], TRUE );
			if ( isset( $settings['button_icon'] ) and $settings['button_icon'] )
			{
				$count++;
			}
			if ( isset( $settings['apple_key'] ) and $settings['apple_key'] )
			{
				$count++;
			}
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
		foreach ( Db::i()->select( '*', 'core_login_methods' ) as $method )
		{
			$settings = json_decode( $method['login_settings'], TRUE );
			if ( isset( $settings['button_icon'] ) and $settings['button_icon'] )
			{
				try
				{
					$settings['button_icon'] = (string) File::get( $oldConfiguration ?: 'core_Login', $settings['button_icon'] )->move( $storageConfiguration );
					Db::i()->update( 'core_login_methods', array( 'login_settings' => json_encode( $settings ) ), array( 'login_id=?', $method['login_id'] ) );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
			
			if ( isset( $settings['apple_key'] ) and $settings['apple_key'] )
			{
				try
				{
					$settings['apple_key'] = (string) File::get( $oldConfiguration ?: 'core_Login', $settings['apple_key'] )->move( $storageConfiguration );
					Db::i()->update( 'core_login_methods', array( 'login_settings' => json_encode( $settings ) ), array( 'login_id=?', $method['login_id'] ) );
				}
				catch( Exception $e )
				{
					/* Any issues are logged */
				}
			}
		}
		unset( Store::i()->loginMethods );
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
		foreach ( Db::i()->select( '*', 'core_login_methods' ) as $method )
		{
			$settings = json_decode( $method['login_settings'], TRUE );
			if ( isset( $settings['button_icon'] ) and $settings['button_icon'] == (string) $file )
			{
				return TRUE;
			}
			
			if ( isset( $settings['apple_key'] ) and $settings['apple_key'] == (string) $file )
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
		foreach ( Db::i()->select( '*', 'core_login_methods' ) as $method )
		{
			$settings = json_decode( $method['login_settings'], TRUE );
			if ( !empty( $settings['button_icon'] ) )
			{
				File::get( 'core_Login', $settings['button_icon'] )->delete();
			}
			if ( !empty( $settings['apple_key'] ) )
			{
				File::get( 'core_Login', $settings['apple_key'] )->delete();
			}
		}
	}
}