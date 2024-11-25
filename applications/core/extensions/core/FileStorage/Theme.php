<?php
/**
 * @brief		File Storage Extension: Theme
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Sep 2013
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File;
use IPS\IPS;
use IPS\Member\Group;
use IPS\Output;
use IPS\Settings;
use IPS\Theme as ThemeClass;
use UnderflowException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Theme
 */
class Theme extends FileStorageAbstract
{
	/**
	 * Some file storage engines need to store a gzip version of some files that can be served to a browser gzipped
	 */
	public static array $storeGzipExtensions = array( 'css', 'js' );
	
	/**
	 * The configuration settings have been updated
	 *
	 * @return void
	 */
	public static function settingsUpdated() : void
	{
		/* Clear out CSS as custom URL may have changed */
		ThemeClass::deleteCompiledCss();
		
		/* Trash this JS */
		Output::clearJsFiles();
	}
	
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return 6; // While this isn't the number of files, it's the number of steps this will take to move them, which is all it's used for
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
		switch ( $offset )
		{
			case 0:
				foreach ( Group::groups() as $group )
				{
					if ( $group->g_icon )
					{
						try
						{
							$group->g_icon = (string) File::get( $oldConfiguration ?: 'core_Theme', $group->g_icon )->move( $storageConfiguration );
							$group->save();
						}
						catch( Exception $e )
						{
							/* Any issues are logged */
						}
					}
				}
				return;

			case 1:
				foreach ( Db::i()->select( '*', 'core_member_ranks' ) as $rank )
				{
					if ( $rank['icon'] )
					{
						try
						{
							Db::i()->update( 'core_member_ranks', array( 'icon' => (string) File::get( $oldConfiguration ?: 'core_Theme', $rank['icon'] )->move( $storageConfiguration ) ), array( 'id=?', $rank['id'] ) );
						}
						catch( Exception $e )
						{
							/* Any issues are logged */
						}
					}
				}

				unset( Store::i()->ranks );
				return;

			case 2:
				foreach ( Db::i()->select( '*', 'core_reputation_levels' ) as $rep )
				{
					try
					{
						if ( $rep['level_image'] )
						{
							Db::i()->update( 'core_reputation_levels', array( 'level_image' => (string) File::get( $oldConfiguration ?: 'core_Theme', $rep['level_image'] )->move( $storageConfiguration ) ), array( 'level_id=?', $rep['level_id'] ) );
						}
					}
					catch( Exception $e )
					{
						/* Any issues are logged */
					}
				}
				unset( Store::i()->reputationLevels );
				return;

			case 3:
				/* Move logos */
				foreach( ThemeClass::themes() as $id => $set )
				{
					$logos   = $set->logo;
					$changed = false;
					
					foreach( array( 'front', 'sharer', 'favicon' ) as $icon )
					{
						if ( isset( $logos[ $icon ] ) AND is_array( $logos[ $icon ] ) )
						{
							if ( ! empty( $logos[ $icon ]['url'] ) )
							{
								try
								{
									$logos[ $icon ]['url'] = (string) File::get( $oldConfiguration ?: 'core_Theme', $logos[ $icon ]['url'] )->move( $storageConfiguration );
									$changed = true;
								}
								catch( Exception $e )
								{
									/* Any issues are logged */
								}
							}
						}
					}
					
					if ( $changed === true )
					{
						$set->saveSet( array( 'logo' => $logos ) );
					}
				}
				
				/* All done */
				return;

			case 4:
				/* Trash old JS */
				try
				{
					File::getClass( $oldConfiguration ?: 'core_Theme' )->deleteContainer( 'javascript_global' );
				} catch( Exception $e ) { }
					
				foreach( Application::applications() as $key => $data )
				{
					try
					{
						File::getClass( $oldConfiguration ?: 'core_Theme' )->deleteContainer( 'javascript_' . $key );
					} catch( Exception $e ) { }
				}
				
				/* Trash this JS */
				Output::clearJsFiles();

				/* Trash CSS and images */
				foreach( ThemeClass::themes() as $id => $theme )
				{
					/* Remove files, but don't fail if we can't */
					try
					{
						File::getClass( $oldConfiguration ?: 'core_Theme' )->deleteContainer( 'set_resources_' . $theme->id );
						File::getClass( $oldConfiguration ?: 'core_Theme' )->deleteContainer( 'css_built_' . $theme->id );
					}
					catch( Exception $e ){}
				}
				
				/* Trash new CSS and images - but skip 1st party apps */
				foreach( Application::applications() as $app )
				{
					if( !in_array( $app->directory, IPS::$ipsApps ) )
					{
						ThemeClass::clearFiles( ThemeClass::TEMPLATES + ThemeClass::CSS + ThemeClass::IMAGES, $app->directory );
					}
				}
				
				return;
			
			case 5:
				$settings = array();
				foreach( Application::applications() AS $app )
				{
					$settings = array_merge( $settings, $app->uploadSettings() );
				}
				
				foreach( $settings AS $key )
				{
					if ( Settings::i()->$key )
					{
						try
						{
							File::get( $oldConfiguration ?: 'core_Theme', Settings::i()->$key )->move( $storageConfiguration );
						}
						catch( Exception $e ) {}
					}
				}
				
				throw new UnderflowException;

			default:
				/* Go away already */
				throw new UnderflowException;
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
		/* Is it a group icon? */
		foreach ( Group::groups() as $group )
		{
			if ( $group->g_icon == (string) $file )
			{
				return TRUE;
			}
		}

		/* Is it a rank icon? */
		foreach ( Db::i()->select( '*', 'core_member_ranks' ) as $rank )
		{
			if ( $rank['icon'] == (string) $file )
			{
				return TRUE;
			}
		}

		/* Is it a reputation level icon? */
		foreach ( Db::i()->select( '*', 'core_reputation_levels' ) as $rep )
		{
			if ( $rep['level_image'] == (string) $file )
			{
				return TRUE;
			}
		}

		/* Is it a skin image? */
		foreach ( Db::i()->select( '*', 'core_theme_resources' ) as $image )
		{
			if ( $image['resource_filename'] == (string) $file )
			{
				return TRUE;
			}
		}
		
		/* Is it JS? */
		if ( isset( Store::i()->javascript_map ) )
		{
			foreach( Store::i()->javascript_map as $app => $data )
			{
				foreach( Store::i()->javascript_map[ $app ] as $key => $js )
				{
					if ( $js == (string) $file )
					{
						return TRUE;
					}
				}
			}
		}

		/* Is it a skin logo image or CSS? */
		foreach( ThemeClass::themes() as $set )
		{
			foreach( array( 'front', 'sharer', 'favicon' ) as $icon )
			{
				if ( isset( $set->logo[ $icon ] ) AND is_array( $set->logo[ $icon ] ) )
				{
					if ( ! empty( $set->logo[ $icon ]['url'] ) AND $set->logo[ $icon ]['url'] == (string) $file )
					{
						return TRUE;
					}
				}
			}

			foreach( $set->css_map as $key => $css )
			{
				if ( $css == (string) $file )
				{
					return TRUE;
				}
			}
		}
		
		/* Setting? */
		$settings = array();
		foreach( Application::applications() AS $app )
		{
			$settings = array_merge( $settings, $app->uploadSettings() );
		}
		
		foreach( $settings AS $key )
		{
			if ( Settings::i()->$key AND Settings::i()->$key == (string) $file )
			{
				return TRUE;
			}
		}

		/* Not found? Then must not be valid */
		return FALSE;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		// It's not possible to delete the core application, and this would break the entire site, so let's not bother with this
	}
}