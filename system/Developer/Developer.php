<?php
/**
 * @brief		Application Developer Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 October 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use IPS\Data\Cache;
use IPS\Data\Store;
use Whoops\Handler\PrettyPageHandler;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Developer class used for IN_DEV management
 */
class Developer
{
	
	/**
	 * @brief Array of directories that should always be present inside /dev
	 */
	protected static array $devDirs = array( 'css', 'email', 'html', 'img', 'js' );
	
	/**
	 * @brief Array of multitons
	 */
	protected static array $multitons = array();
	
	/**
	 * Synchronises development data between installations
	 *
	 * @return void
	 */
	public static function sync() : void
	{
		$updated	= FALSE;

		foreach (Application::applications() as $app )
		{
			$thisAppUpdated = static::load( $app->directory )->synchronize();
			$updated		= $updated ?: $thisAppUpdated;
		}

		if( $updated )
		{
			/* Update JS cache bust */
			Settings::i()->changeValues( array( 'javascript_updated' => time() ) );
		}
	}

	/**
	 * Stores objects
	 *
	 * @param string $app Application key
	 * @return Developer \IPS\Developer
	 */
	public static function load( string $app ): Developer
	{
		if ( ! isset( static::$multitons[ $app ] ) )
		{
			static::$multitons[ $app ] = new Developer( Application::load( $app ) );
		}
		
		return static::$multitons[ $app ];
	}
	
	/**
	 * @brief	Application
	 */
	protected ?Application $app;
	
	/**
	 * Constructor
	 *
	 * @param Application $app	The application the notification belongs to
	 * @return	void
	 */
	public function __construct( Application $app )
	{
		$this->app = $app;
	}

	/**
	 * @brief	Last updates
	 */
	protected static ?array $lastUpdates = NULL;
	
	/**
	 * Sync development data for an application
	 *
	 * @return void
	 */
	public function synchronize()
	{
		if ( static::$lastUpdates === NULL )
		{
			static::$lastUpdates = iterator_to_array( Db::i()->select( '*', 'core_dev' )->setKeyField('app_key') );
		}
		
		/* Get versions */
		$versions      = array_keys( json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/data/versions.json" ), TRUE ) );
		$latestVersion = array_pop( $versions );
		
		$updated = FALSE;

		/* A brand new app won't have a latest version */
		if( $latestVersion )
		{
			/* If we don't have a record for this app, assume we're up to date */
			if ( !isset( static::$lastUpdates[ $this->app->directory ] ) )
			{
				$content = NULL;

				if( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$latestVersion}/queries.json" ) )
				{
					$content = file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$latestVersion}/queries.json" );
				}

				Db::i()->insert( 'core_dev', array(
						'app_key'			=> $this->app->directory,
						'working_version'	=> $latestVersion,
						'last_sync'			=> time(),
						'ran'				=> $content,
				) );
			}
			/* Otherwise, do stuff */
			else
			{
				/* Database schema */
				if( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$latestVersion}/queries.json" ) )
				{
					if ( static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$latestVersion}/queries.json" ) )
					{
						/* Get schema file */
						$schema = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/data/schema.json" ), TRUE );
							
						/* Run queries for previous versions */
						if ( static::$lastUpdates[ $this->app->directory ]['working_version'] != $latestVersion )
						{
							/* Get all versions past the working version */
							$dirMatches = [];
							foreach (new DirectoryIterator( ROOT_PATH . "/applications/{$this->app->directory}/setup/" ) as $dir )
							{
								if ( $dir->isDir() and !$dir->isDot() and preg_match( '/^upg_(\d+)$/', $dir, $matches ) )
								{
									if ( (int) $matches[1] >= static::$lastUpdates[ $this->app->directory ]['working_version'] )
									{
										$dirMatches[] = (int) $matches[1];
									}
								}
							}

							/* Run through the *sorted* versions. Note DirectoryIterator sorts by last modified, but you want this to run in order of versions */
							asort( $dirMatches );
							foreach ( $dirMatches as $match )
							{
								if ( $match == static::$lastUpdates[ $this->app->directory ]['working_version'] )
								{
									if( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$match}/queries.json" ) )
									{
										$queries = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$match}/queries.json" ), TRUE );
										$localQueries = json_decode( static::$lastUpdates[ $this->app->directory ]['ran'], TRUE );
										foreach ( $queries as $q )
										{
											if ( is_array( $localQueries ) AND !in_array( $q, $localQueries ) )
											{
												$method = $q['method'];
												$params = $q['params'];
												Db::i()->$method( ...$params );
											}
										}
									}
								}
								else
								{
									if( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$match}/queries.json" ) )
									{
										$queries = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$match}/queries.json" ), TRUE );
										foreach ( $queries as $q )
										{
											try
											{
												$method = $q['method'];
												$params = $q['params'];
												Db::i()->$method( ...$params );
											}
											catch(Db\Exception $e )
											{
												/* If the issue is with a create table other than exists, we should just throw it */
												if ( $q['method'] == 'createTable' and ! in_array( $e->getCode(), array( 1007, 1050 ) ) )
												{
													throw $e;
												}

												/* Can't change a column as it doesn't exist */
												if ( $e->getCode() == 1054 )
												{
													if ( $q['method'] == 'changeColumn' )
													{
														if ( Db::i()->checkForTable( $q['params'][0] ) )
														{
															/* Does the column exist already? */
															if ( Db::i()->checkForColumn( $q['params'][0], $q['params'][2]['name'] ) )
															{
																/* Just make sure it's up to date */
																Db::i()->changeColumn( $q['params'][0], $q['params'][2]['name'], $q['params'][2] );
																continue;
															}
															else
															{
																/* The table exists, so lets just add the column */
																Db::i()->addColumn( $q['params'][0], $q['params'][2] );

																continue;
															}
														}
													}

													throw $e;
												}
												/* Can't rename a table as it doesn't exist */
												else if ( $e->getCode() == 1017 )
												{
													if ( $q['method'] == 'renameTable' )
													{
														if ( Db::i()->checkForTable( $q['params'][1] ) )
														{
															/* The table we are renaming to *does* exist */
															continue;
														}
													}

													throw $e;
												}
												/* If the error isn't important we should ignore it */
												else if( !in_array( $e->getCode(), array( 1007, 1008, 1050, 1051, 1060, 1061, 1062, 1091, 1146 ) ) )
												{
													throw $e;
												}
											}
										}
									}
								}
							}
			
							static::$lastUpdates[ $this->app->directory ]['ran'] = json_encode( array() );
						}
							
						/* Run queries for this version */
						$queries = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$latestVersion}/queries.json" ), TRUE );
						$localQueries = json_decode( static::$lastUpdates[ $this->app->directory ]['ran'], TRUE );
			
						if( is_array($queries) )
						{
							foreach ( $queries as $q )
							{
								if ( !is_array($localQueries) OR !in_array( $q, $localQueries ) )
								{
									/* Check if the table exists, as it may be an import */
									if ( $q['method'] === 'renameTable' and Db::i()->checkForTable( $q['params'][0] ) === FALSE )
									{
										if ( isset( $schema[ $q['params'][1] ] ) )
										{
											try
											{
												Db::i()->createTable( $schema[ $q['params'][1] ] );
											}
											catch (Db\Exception $e ) { }
										}
									}
									/* Run */
									else
									{
										try
										{
											$method = $q['method'];
											$params = $q['params'];
											Db::i()->$method( ...$params );
										}
										catch (Db\Exception $e ) { }
									}
								}
							}
						}
			
						$updated = TRUE;
					}
					else
					{
						$queries = json_decode( file_get_contents( ROOT_PATH . "/applications/{$this->app->directory}/setup/upg_{$latestVersion}/queries.json" ), TRUE );
					}
				}
		
				/* Check for missing tables or columns */
				if ( static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/schema.json" ) )
				{
					$this->app->installDatabaseSchema( TRUE );
						
					$updated = TRUE;
				}
		
				/* Settings */
				if ( static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/settings.json" ) )
				{
					$this->app->installSettings();
						
					$updated = TRUE;
				}
		
				/* Modules */
				if ( static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/modules.json" ) )
				{
					$this->app->installModules();
						
					$updated = TRUE;
				}
		
				/* Tasks */				
				if ( static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/tasks.json" ) )
				{
					$this->app->installTasks();
		
					$updated = TRUE;
				}
				
				/* Widgets */
				if ( static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/widgets.json" ) )
				{
					$this->app->installWidgets();
				
					$updated = TRUE;
				}

				/* Theme Editor Settings */
				if( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/data/themeeditor.json" ) and static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/themeeditor.json" ) )
				{
					$this->app->installThemeEditorSettings();

					$updated = TRUE;
				}

				/* Custom Templates */
				if( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/data/customtemplates.json" ) and static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/customtemplates.json" ) )
				{
					$this->app->installCustomTemplates();
					$updated = true;
				}
				
				/* ACP Search Keywords */
				if ( file_exists( ROOT_PATH . "/applications/{$this->app->directory}/data/acpsearch.json" ) AND static::$lastUpdates[ $this->app->directory ]['last_sync'] < filemtime( ROOT_PATH . "/applications/{$this->app->directory}/data/acpsearch.json" ) )
				{
					$this->app->installSearchKeywords();
						
					$updated = TRUE;
				}
				
				if ( method_exists( $this->app, 'developerSync' ) )
				{
					$devUpdated = $this->app->developerSync( static::$lastUpdates[ $this->app->directory ]['last_sync'] );
					$updated	= $updated ?: $devUpdated;
				}	
					
				/* Update record */
				if ( $updated === TRUE )
				{					
					Theme::load(DEFAULT_THEME_ID)->saveSet();
						
					Db::i()->update( 'core_dev', array(
						'working_version'	=> $latestVersion,
						'last_sync'			=> time(),
						'ran'				=> isset( $queries ) ? json_encode( $queries ) : array(),
					), array( 'app_key=?', $this->app->directory ) );

					Store::i()->clearAll();
					Cache::i()->clearAll();
				}
			}
		}

		return $updated;
	}

	/**
	 * Returns a link which will open the file in the IDE
	 * 
	 * @param string $filePath
	 * @param int $line
	 * @return string|bool|null
	 */
	public static function getIdeHref( string $filePath, int $line = 0): string|bool|null
	{
		return static::getWhoopsHandler()?->getEditorHref( $filePath, $line );
	}

	/**
	 * Returns the Whoops handler
	 *
	 * @return PrettyPageHandler|null
	 */
	protected static function getWhoopsHandler(): PrettyPageHandler|null
	{
		static $handler = NULL;
		if( !$handler and DEV_WHOOPS_EDITOR )
		{
			$handler = new PrettyPageHandler();
			$handler->setEditor(DEV_WHOOPS_EDITOR);
		}
		return $handler;
	}
}