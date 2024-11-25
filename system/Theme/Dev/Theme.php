<?php
/**
 * @brief		IN_DEV Skin Set
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Apr 2013
 */

namespace IPS\Theme\Dev;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DirectoryIterator;
use InvalidArgumentException;
use IPS\Application;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Log;
use IPS\Login;
use IPS\Request;
use IPS\Settings;
use IPS\Theme as SystemTheme;
use IPS\Theme\Template;
use RuntimeException;
use function count;
use function defined;
use function file_put_contents;
use function in_array;
use function intval;
use function is_array;
use function is_dir;
use function is_string;
use function str_replace;
use function stristr;
use function strstr;
use const IPS\DEFAULT_THEME_ID;
use const IPS\DEV_DEBUG_CSS;
use const IPS\IPS_FOLDER_PERMISSION;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IN_DEV Skin set
 */
class Theme extends SystemTheme
{
	/**
	 * @brief	Template Classes
	 */
	protected array $templates = array();
	
	/**
	 * @brief	Stored plugins
	 */
	protected static array $plugins = array();

	/**
	 * Get all available hook points in the templates
	 *
	 * @param array|string|null $app
	 * @return array
	 */
	public function getHookPoints( array|string $app=null ): array
	{
		$hookPoints = [];
		foreach( Application::applications() as $appDir => $application )
		{
			if ( $app === NULL or ( in_array( $appDir, $app ) ) )
			{
				foreach( ['front', 'global' ] as $location )
				{
					if ( is_dir( static::_getHtmlPath( $appDir, $location ) ) )
					{
						foreach( new DirectoryIterator( static::_getHtmlPath( $appDir, $location ) ) as $file )
						{
							if ( $file->isDir() and mb_substr( $file->getFilename(), 0, 1 ) !== '.' )
							{
								foreach ( new DirectoryIterator( static::_getHtmlPath( $appDir, $location, $file->getFilename() ) ) as $template )
								{
									if ( !$template->isDir() and mb_substr( $template->getFilename(), -6 ) === '.phtml' )
									{
										$contents = file_get_contents( $template->getPathname() );
										if ( stristr( $contents, 'data-ips-hook' ) )
										{
											$path = $application->directory . '/' . $location . '/' . $file . '/' . str_replace( ".phtml", "", $template->getFilename() );
											$hookPoints[$path] = static::extractHookNames( $contents );
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return $hookPoints;
	}

	/**
	 * Get raw templates. Raw means HTML logic and variables are still in {{format}}
	 *
	 * @param array|string $app				Template app (e.g. core, forum)
	 * @param array|string $location			Template location (e.g. admin,global,front)
	 * @param array|string $group				Template group (e.g. login, share)
	 * @param int|null $returnType			Determines the content returned
	 * @return array
	 */
	public function getAllTemplates( array|string $app=array(), array|string $location=array(), array|string $group=array(), int $returnType=null ): array
	{
		$returnType = ( $returnType === null )  ? self::RETURN_ALL   : $returnType;
		$app        = ( is_string( $app )      AND $app != ''      ) ? array( $app )      : $app;
		$location   = ( is_string( $location ) AND $location != '' ) ? array( $location ) : $location;
		$group      = ( is_string( $group )    AND $group != ''    ) ? array( $group )    : $group;
		$where      = array();
		$templates  = array();
		
		if ( ! ( $returnType & static::RETURN_NATIVE ) )
		{
			return parent::getAllTemplates( $app, $location, $group, $returnType );
		}
		
		$fixedLocations = array( 'admin', 'front', 'global' );
		$results	    = array();
		
		foreach( Application::applications() as $appDir => $application )
		{
			if ( $app === NULL or ( in_array( $appDir, $app ) ) )
			{
				foreach( $fixedLocations as $_location )
				{
					if ( $location === NULL or ( in_array( $_location, $location ) ) ) # location?
					{
						foreach( new DirectoryIterator( static::_getHtmlPath( $appDir, $_location ) ) as $file )
						{
							if ( $file->isDir() AND mb_substr( $file->getFilename(), 0, 1 ) !== '.' )
							{
								if ( $group === NULL or ( in_array( $file->getFilename(), $group ) ) )
								{
									foreach( new DirectoryIterator( static::_getHtmlPath( $appDir, $_location, $file->getFilename() ) ) as $template )
									{
										if ( ! $template->isDir() AND mb_substr( $template->getFilename(), -6 ) === '.phtml' )
										{
											$results[] = str_replace( ".phtml", "", $template->getFilename() );
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		return array_unique( $results );
	}

	/**
	 * Get a template
	 *
	 * @param string $group				Template Group
	 * @param string|null $app				Application key (NULL for current application)
	 * @param string|null $location		    Template Location (NULL for current template location)
	 * @return    Template
	 */
	public function getTemplate( string $group, string $app=NULL, string $location=NULL ): Template
	{
		/* Do we have an application? */
		if( $app === NULL )
		{
			if ( !Dispatcher::hasInstance() )
			{
				throw new RuntimeException('NO_APP');
			}
			$app = Dispatcher::i()->application->directory;
		}
		
		/* How about a template location? */
		if( $location === NULL )
		{
			if ( !Dispatcher::hasInstance() )
			{
				throw new RuntimeException('NO_LOCATION');
			}
			$location = Dispatcher::i()->controllerLocation;
		}
		
		/* Get template */
		if ( !isset( $this->templates[ $app ][ $location ][ $group ] ) )
		{
			$class = 'Template';
			$class = static::_getTemplateNamespace() . $class;
			
			$this->templates[ $app ][ $location ][ $group ] = new $class( $app, $location, $group );
		}
		return $this->templates[ $app ][ $location ][ $group ];
	}

	/**
	 * Get CSS
	 *
	 * @param string $file		Filename
	 * @param string|null $app		Application
	 * @param string|null $location	Location (e.g. 'admin', 'front')
	 * @return	array		URLs to CSS files
	 */
	public function css( string $file, string $app=NULL, string $location=NULL ): array
	{
		$app      = $app      ?: Dispatcher::i()->application->directory;
		$location = $location ?: Dispatcher::i()->controllerLocation;

		if ( $location === 'interface' )
		{
			$path = ROOT_PATH . "/applications/{$app}/interface/{$file}";
		}
		else
		{
			$path = static::_getCssPath($app, $location, $file);
		}

		if ( isset( self::$buildGrouping['css'][ $app ][ $location ] ) AND is_array( self::$buildGrouping['css'][ $app ][ $location ] ) )
		{
			foreach( self::$buildGrouping['css'][ $app ][ $location ] as $buildPath )
			{
				if ( mb_substr( $file, 0, -4 ) == $buildPath )
				{
					$path = static::_getCssPath($app, $location, $buildPath);
					$file = $buildPath;
				}
			}
		}

		$return = array();

		if ( is_dir( $path ) )
		{
			$bits     = explode( '/', $path );
			$group = array_pop( $bits );
			$fileName = $group . '.css';

			/* Load css/location/folderName/folderName.css first */
			if ( is_file( $path . '/' . $fileName ) )
			{
				$return[] = str_replace( array( 'http://', 'https://' ), '//', Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . ( str_replace( ROOT_PATH . '/', '', static::_getCssPath( $app, $location, $file ) ) ) . $fileName;
			}

			$csses = array();
			foreach ( new DirectoryIterator( $path ) as $f )
			{
				if ( !$f->isDot() and mb_substr( $f, -4 ) === '.css' and $f->getFileName() != $fileName )
				{
					$csses[] = ( str_replace( ROOT_PATH . '/', '', static::_getCssPath( $app, $location, $group . '/' . $f->getFilename() ) ) );
				}
			}

			sort( $csses );

			if ( count( $csses ) )
			{
				if( DEV_DEBUG_CSS )
				{
					foreach( $csses as $cssFile )
					{
						$return[] = str_replace( array( 'http://', 'https://' ), '//', Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . $cssFile;
					}
				}
				else
				{
					$return[] = str_replace( array( 'http://', 'https://' ), '//', Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . implode( ',', $csses );
				}
			}
		}
		elseif ( file_exists( $path ) )
		{
			$return[] = str_replace( ROOT_PATH . '/', '', str_replace( array( 'http://', 'https://' ), '//', Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . $path );
		}

		return $return;
	}

	
	/**
	 * Get JS
	 *
	 * @param string $file		Filename
	 * @param string|null $app		Application
	 * @param string|null $location	Location (e.g. 'admin', 'front')
	 * @return	array		URL to JS files
	 */
	public function js( string $file, string $app=NULL, string $location=NULL ): array
	{
		$app      = $app      ?: Request::i()->app;
		$location = $location ?: Dispatcher::i()->controllerLocation;
		
		$return = array();
			if ( $location === 'interface' )
			{
				$path = ROOT_PATH . "/applications/{$app}/interface/{$file}";
			}
			else
			{
				$path = ROOT_PATH . "/applications/{$app}/dev/js/{$location}/{$file}";
			}
					
			if ( is_dir( $path ) )
			{
				$bits     = explode( '/', $path );
				$fileName = 'ips.' . array_pop( $bits ) . '.js';
				
				if ( is_file( $path .'/' . $fileName ) )
				{
					$return[] = Settings::i()->base_url . "/applications/{$app}/dev/js/{$location}/{$file}/{$fileName}";
				}

				foreach ( new DirectoryIterator( $path ) as $f )
				{
					if ( !$f->isDot() and mb_substr( $f, -3 ) === '.js' and $f->getFileName() != $fileName )
					{
						$return[] = Settings::i()->base_url . "/applications/{$app}/dev/js/{$location}/{$file}/{$f}";
					}
				}
			}
			else
			{			
				$return[] = str_replace( ROOT_PATH, Settings::i()->base_url, $path );
			}
		
		return $return;
	}
	
	/**
	 * Get Theme Resource (resource, font, theme-specific JS, etc)
	 *
	 * @param string $path		Path to resource
	 * @param string|null $app		Application key
	 * @param string|null $location	Location
	 * @param	bool		$noProtocol	Return URL without a protocol (protocol-relative)
	 * @return	Url|NULL		URL to resource
	 */
	public function resource( string $path, string $app=NULL, string $location=NULL, $noProtocol=FALSE ): ?Url
	{
		$baseUrl = Settings::i()->base_url;
		$app = $app ?: Dispatcher::i()->application->directory;
		$location = $location ?: Dispatcher::i()->controllerLocation;
		
		if ( $location === 'interface' )
		{
			return Url::internal( "applications/{$app}/interface/{$path}", 'interface', NULL, array(), Url::PROTOCOL_RELATIVE );
		}

		/* This is a custom resource uploaded via the ACP */
		if( in_array( $app, IPS::$ipsApps ) and mb_substr( $path, 0, 7 ) === 'custom/' )
		{
			return parent::resource( $path, $app, $location, $noProtocol );
		}

		$url = Url::createFromString( $baseUrl . str_replace( '\\', '/', str_replace( ROOT_PATH . '/', '', static::_getResourcePath( $app, $location, $path ) ) ), false );
		if( $noProtocol )
		{
			$url = $url->setScheme(NULL);
		}
		return $url;
	}
	
	/**
	 * (re)import HTML templates into the template DB
	 * 
	 * @param string $app	        Application Key
	 * @param int $id	        Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * 
	 * @return	void
	 */
	public static function importDevHtml( string $app, int $id ): void
	{
		/* Clear out existing template bits */
		Db::i()->delete( 'core_theme_templates', array( 'template_app=? AND ( template_set_id=? OR ( template_set_id=0 ) )', $app, $id ) );

		$themeLocations = Application::load( $app )->themeLocations;

		/* Get existing template bits to see if we need to import */
		if ( $id > 0 )
		{
			$currentTemplates = Theme::load( $id )->getAllTemplates( $app );
		}
		
		$path = static::_getHtmlPath( $app );

		if ( is_dir( $path ) )
		{
			foreach( new DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDot() || mb_substr( $location->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}
				
				if ( $location->isDir() )
				{
					if ( ! in_array( $location->getFilename(), $themeLocations ) )
					{
						continue;
					}

					foreach( new DirectoryIterator( $path . $location->getFilename() ) as $group )
					{
						if ( $group->isDot() || mb_substr( $group->getFilename(), 0, 1 ) === '.' )
						{
							continue;
						}
						
						if ( $group->isDir() )
						{
							foreach( new DirectoryIterator( $path . $location->getFilename() . '/' . $group->getFilename() ) as $file )
							{
								if ( $file->isDot() || mb_substr( $file->getFilename(), -6 ) !== '.phtml')
								{
									continue;
								}
				
								/* Get the content */
								$html   = file_get_contents( $path . $location->getFilename() . '/' . $group->getFilename() . '/' . $file->getFilename() );
								$params = array();
								
								/* Parse the header tag */
								preg_match( '/^<ips:template parameters="(.+?)?"(.+?)?\/>(\r\n?|\n)/', $html, $params );
								
								/* Strip it */
								$html = ( isset($params[0]) ) ? str_replace( $params[0], '', $html ) : $html;

								/* Enforce \n line endings */
								if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
								{
									$html = str_replace( "\r\n", "\n", $html );
								}
								
								$version = Application::load( $app )->long_version;
								$save = array(
									'set_id'	  => $id,
									'added_to'    => 0,
									'user_added'  => 0,
									'user_edited' => 0
								);
								
								/* If we're syncing designer mode, check for actual changes */
								$name = preg_replace( '/[^a-zA-Z0-9_]/', '', str_replace( '.phtml', '', $file->getFilename() ) );
								
								/* Prevent '.phtml' files creating empty template rows in the database */
								if ( empty( $name ) )
								{
									continue;
								}
								
								if ( $id > 0 )
								{
									if ( isset( $currentTemplates[ $app ][ $location->getFilename() ][ $group->getFilename() ][ $name ] ) )
									{
										if( Login::compareHashes( md5( trim( $html ) ), md5( trim( $currentTemplates[ $app ][ $location->getFilename() ][ $group->getFilename() ][ $name ]['template_content'] ) ) ) )
										{
											/* No change  */
											continue;
										}
										else
										{
											/* It has changed */
											$save['user_edited'] = $version;
										}
									}
									else
									{
										/* New template bit */
										$save['added_to']   = $id;
										$save['set_id']	    = 0;
										$save['user_added'] = 1;
									}
								}
								
								Db::i()->replace( 'core_theme_templates', array( 'template_set_id'      => $save['set_id'],
																					 'template_app'		    => $app,
																					 'template_location'    => $location->getFilename(),
																					 'template_group'       => $group->getFilename(),
																					 'template_name'	    => $name,
																					 'template_data'	    => ( isset( $params[1] ) ) ? $params[1] : '',
																					 'template_content'     => $html,
																					 'template_updated'     => time(),
																					 'template_version'	    => NULL ) );
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * (re)import CSS into the CSS DB
	 *
	 * @param string $app	Application Key
	 * @param int $id	Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * @return	void
	 */
	public static function importDevCss( string $app, int $id ): void
	{
		/* Clear out existing template bits */
		Db::i()->delete( 'core_theme_css', array( 'css_app=? AND ( css_set_id=? ) ', $app, $id ) );
		
		$master = array();
		
		/* Get existing template bits to see if we need to import */
		$currentCss = [];
		
		if ( $id > 0 )
		{
			$currentCss = Theme::load( $id )->getAllCss( $app );
		}
		
		$path = static::_getCssPath($app);
	
		if ( is_dir( $path ) )
		{
			foreach( new DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDot() OR mb_substr( $location->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}
	
				if ( $location->isDir() )
				{
					static::_importDevCss( $app, $id, $currentCss, $location->getFilename() );
				}
			}
		}
	}
	
	/**
	 * (re)import CSS into the CSS DB (Iterable)
	 *
	 * @param string $app		Application Key
	 * @param int $id			Theme set ID
	 * @param array $currentCss	Master CSS bits
	 * @param string $location	Location Folder Name
	 * @param string $path		Path
	 * @return	void
	 */
	protected static function _importDevCss( string $app, int $id, array $currentCss, string $location, string $path='/' ): void
	{
		$root = static::_getCssPath( $app, $location );
		
		foreach( new DirectoryIterator( $root . $path ) as $file )
		{
			if ( $file->isDot() OR mb_substr( $file->getFilename(), 0, 1 ) === '.' OR $file == 'index.html' )
			{
				continue;
			}
	
			if ( $file->isDir() )
			{
				static::_importDevCss( $app, $id, $currentCss, $location, $path . $file->getFilename() . '/' );
			}
			else
			{
				if ( mb_substr( $file->getFilename(), -4 ) !== '.css' )
				{
					continue;
				}

				/* Get the content */
				$css = file_get_contents( $root . $path . $file->getFilename() );
					
				/* Parse the header tag */
				preg_match( '#^/\*<ips:css([^>]+?)>\*/\n#', $css, $params );

				/* Strip it */
				if ( count( $params ) AND ! empty( $params[0] ) )
				{
					$css = str_replace( $params[0], '', $css );
				}

				$cssModule = '';
				$cssApp    = '';
				$cssPos    = 0;
				$cssHidden = 0;
				
				/* Tidy params */
				if ( count( $params ) AND ! empty( $params[1] ) )
				{
					preg_match_all( '#([\d\w]+?)=\"([^"]+?)"#i', $params[1], $items, PREG_SET_ORDER );
						
					foreach( $items as $id => $attr )
					{
						switch( trim( $attr[1] ) )
						{
							case 'module':
								$cssModule = trim( $attr[2] );
								break;
							case 'app':
								$cssApp = trim( $attr[2] );
								break;
							case 'position':
								$cssPos = intval( $attr[2] );
								break;
							case 'hidden':
								$cssHidden = intval( $attr[2] );
								break;
						}
					}
				}
			
				$trimmedPath = trim( $path, '/' );
				$finalPath   = ( ( ! empty( $trimmedPath ) ) ? $trimmedPath : '.' );
				$version     = Application::load( $app )->long_version;
				$save        = array(
					'set_id'	  => $id,
					'added_to'    => 0,
					'user_edited' => 0
				);

				/* Enforce \n line endings */
				if( mb_strtolower( mb_substr( PHP_OS, 0, 3 ) ) === 'win' )
				{
					$css = str_replace( "\r\n", "\n", $css );
				}
							
				/* If we're syncing designer mode, check for actual changes */
				if ( $id > 0 )
				{
					$css = str_replace( '/* No Content */', '', $css );
					if ( isset( $currentCss[ $app ][ $location ][ $finalPath ][ $file->getFilename() ] ) )
					{
						if( Login::compareHashes( md5( trim( $css ) ), md5( trim( $currentCss[ $app ][ $location ][ $finalPath ][ $file->getFilename() ]['css_content'] ) ) ) )
						{
							/* No change  */
							continue;
						}
						else
						{
							/* It has changed */
							$save['user_edited'] = $version;
							$save['added_to']   = $id;
							$save['set_id']	    = $id;
						}
					}
					else
					{
						/* New template bit */
						$save['added_to']   = $id;
						$save['set_id']	    = $id;
					}
				}
								
				Db::i()->insert( 'core_theme_css', array( 'css_set_id'    	 => $save['set_id'],
															   'css_app'		 => $app,
															   'css_location'  	 => $location,
															   'css_path'		 => $finalPath,
															   'css_name'	     => $file->getFilename(),
															   'css_attributes'  => '',
															   'css_content'	 => $css,
															   'css_modules'	 => $cssModule,
															   'css_position'	 => $cssPos,
															   'css_updated'   	 => time(),
															   'css_hidden'		 => $cssHidden ) );
			}
		}
	}
	
	/**
	 * Build Resourcess ready for non IN_DEV use
	 * 
	 * @param string $app	App (e.g. core, forum)
	 * @param int $id	Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * @return	void
	 */
	public static function importDevResources( string $app, int $id ): void
	{
		foreach( new DirectoryIterator( ROOT_PATH . '/applications/' ) as $dir )
		{
			if ( $dir->isDot() || mb_substr( $dir->getFilename(), 0, 1 ) === '.' || $dir == 'index.html')
			{
				continue;
			}

			if ( $app === null OR $app == $dir->getFilename() )
			{
				/* When we are building, removeResources() has already taken care of this */
				if( $id !== 0 )
				{
					Theme::deleteCompiledResources( $dir->getFilename(), null, null, null, $id );
				}
						
				Db::i()->delete( 'core_theme_resources', array( 'resource_app=? AND resource_set_id=?', $dir->getFilename(), $id ) );
				
				$path = static::_getResourcePath( $dir->getFilename() );
					
				if ( is_dir( $path ) )
				{
					foreach( new DirectoryIterator( $path ) as $location )
					{
						if ( $location->isDot() || mb_substr( $location->getFilename(), 0, 1 ) === '.' )
						{
							continue;
						}
							
						if ( $location->isDir() )
						{
							static::_importDevResources( $dir->getFilename(), $id, $location->getFilename() );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Build Resources ready for non IN_DEV use (Iterable)
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param string $app		Application Key
	 * @param int $id			Theme Set Id
	 * @param string $location	Location Folder Name
	 * @param string $path		Path
	 * @return	void
	 */
	public static function _importDevResources( string $app, int $id, string $location, string $path='/' ): void
	{
		$root   = static::_getResourcePath($app, $location);
		$master = array();
		$plugins = array();

		if ( $id )
		{
			foreach( Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=0 and resource_app=? and resource_location=? and resource_path=?', $app, $location, $path ) ) as $resource )
			{
				$master[ $resource['resource_name'] ] = md5( $resource['resource_data'] );
			}
		}

		foreach( new DirectoryIterator( $root . $path ) as $file )
		{
			if ( $file->isDot() || mb_substr( $file->getFilename(), 0, 1 ) === '.' || $file == 'index.html' )
			{
				continue;
			}
	
			if ( $file->isDir() )
			{
				static::_importDevResources( $app, $id, $location, $path . $file->getFilename() . '/' );
			}
			else
			{
				/* files larger than 1.5mb don't base64_encode() as they may not get stored in the resources_data column */
				if ( filesize( $root . $path . $file->getFilename() ) > ( 1.5 * 1024 * 1024 ) )
				{
					Log::log( $root . $path . $file->getFilename() . " too large to import", 'designers_mode_import' );
					continue;
				}
				
				$content = file_get_contents( $root . $path . $file->getFilename() );
				
				if ( ! base64_encode( $content ) )
				{
					Log::log( $root . $path . $file->getFilename() . " could not be saved correctly", 'designers_mode_import' );
					continue;
				}
				
				$custom   = 0;
				$name     = self::makeBuiltTemplateLookupHash($app, $location, $path) . '_' . $file->getFilename();
				$fileName = (string) File::create( 'core_Theme', $name, $content, 'set_resources_' . ( $id == 0 ? DEFAULT_THEME_ID : $id ), TRUE );
				
				if ( $id !== 0 AND ( !isset( $master[ $file->getFilename() ] ) or !Login::compareHashes( md5( $content ), $master[ $file->getFilename() ] ) ) )
				{
					$custom = 1;
				}

				Db::i()->insert( 'core_theme_resources', array(
						'resource_set_id'      => ( $id == 0 ? DEFAULT_THEME_ID : $id ),
						'resource_app'         => $app,
						'resource_location'    => $location,
						'resource_path'        => $path,
						'resource_name'        => $file->getFilename(),
						'resource_added'	   => time(),
						'resource_data'        => $content,
						'resource_filename'    => $fileName,
						'resource_user_edited' => $custom
				) );

				/* Store in master table */
				if ( $id == 0 )
				{
					Db::i()->insert( 'core_theme_resources', array(
	                     'resource_set_id'   => 0,
	                     'resource_app'      => $app,
	                     'resource_location' => $location,
	                     'resource_path'     => $path,
	                     'resource_name'     => $file->getFilename(),
	                     'resource_added'	  => time(),
	                     'resource_data'     => $content,
	                     'resource_filename' => ''
	                 ) );
				}
			}
		}
	}
	
	
	/**
	 * Writes the /application/{app}/dev/{container}/ directory
	 *
	 * @param string $app		 Application Directory
	 * @param string $container	 Container directory (e.g. html/css/resources)
	 * @return string	Path created
	 * @throws	RuntimeException
	 */
	protected static function _writeThemeContainerDirectory( string $app, string $container ): string
	{
		$dirToWrite = ROOT_PATH . "/applications/" . $app . '/dev/' . $container;
	
		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				throw new RuntimeException('core_theme_dev_cannot_make_dir,' . $dirToWrite);
			}
			else
			{
				@chmod( $dirToWrite, IPS_FOLDER_PERMISSION );
			}
		}
	
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			throw new RuntimeException('core_theme_dev_not_writeable,' . $dirToWrite);
		}
		
		/* Make sure root directory is CHMOD correctly */
		@chmod( ROOT_PATH . "/applications/" . $app . '/dev', 0777 );
	
		return $dirToWrite;
	}
	
	/**
	 * Writes the /application/{app}/dev/{container}/{path} directory
	 *
	 * @param string $app		Application Directory
	 * @param string $container	Location of path to create (e.g. admin, front)
	 * @param string $path		Path to create
	 * @return	string	Path created
	 * @throws	RuntimeException
	 */
	protected static function _writeThemePathDirectory( string $app, string $container, string $path ): string
	{
		$dirToWrite = ROOT_PATH . "/applications/" . $app . '/dev/' . $container . '/' . $path;
	
		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				throw new RuntimeException('core_theme_dev_cannot_make_dir,' . $dirToWrite);
			}
			else
			{
				@chmod( $dirToWrite, IPS_FOLDER_PERMISSION );
			}
		}
	
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			throw new RuntimeException('core_theme_dev_not_writeable,' . $dirToWrite);
		}
	
		return $dirToWrite;
	}

	/**
	 * Write skin resources
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param string $app		 Application Directory
	 * @return	void
	 * @throws	RuntimeException
	 */
	public static function exportResources( string $app ): void
	{
		try
		{
			self::_writeThemeContainerDirectory( $app, 'img' );
		}
		catch( RuntimeException $e )
		{
			throw new RuntimeException( $e->getMessage() );
		}
		
		foreach( Db::i()->select( '*', 'core_theme_resources', array( 'resource_app=? AND resource_set_id=?', $app, DEFAULT_THEME_ID ) )->setKeyField('resource_id') as $resourceId => $resource )
		{
			try
			{
				$pathToWrite = self::_writeThemePathDirectory( $app, 'img', $resource['resource_location'] );
			}
			catch( RuntimeException $e )
			{
				throw new RuntimeException( $e->getMessage() );
			}
				
			if ( $resource['resource_path'] != '/' )
			{
				$_path = '';
					
				foreach( explode( '/', trim( $resource['resource_path'], '/' ) ) as $dir )
				{
					$_path .= '/' . trim( $dir, '/' );
					
					try
					{
						$pathToWrite = self::_writeThemePathDirectory( $app, 'img', $resource['resource_location'] . $_path );
					}
					catch( RuntimeException $e )
					{
						throw new RuntimeException( $e->getMessage() );
					}
				}
			}

			try
			{
				if ( ! @file_put_contents( $pathToWrite . '/' . $resource['resource_name'], $resource['resource_data'] ) )
				{
					throw new RuntimeException('core_theme_dev_cannot_write_resource,' . $pathToWrite . '/' . $resource['resource_name']);
				}
				else
				{
					@chmod( $pathToWrite . '/' . $resource['resource_name'], 0777 );
				}
			}
			catch( InvalidArgumentException $e )
			{
				
			}
		}
	}
	
	/**
	 * Write CSS into the appropriate theme directory as plain text CSS ({resource="foo.png"} intact)
	 *
	 * @param string $app		 Application Directory
	 * @return	void
	 * @throws	RuntimeException
	 */
	public static function exportCss( string $app ): void
	{
		try
		{
			self::_writeThemeContainerDirectory( $app, 'css' );
		}
		catch( RuntimeException $e )
		{
			throw new RuntimeException( $e->getMessage() );
		}
		
		$css = static::master()->getAllCss();
	
		foreach( $css as $appDir => $appData )
		{
			foreach( $css[ $appDir ] as $location => $locationData )
			{
				try
				{
					$pathToWrite = self::_writeThemePathDirectory( $app, 'css', $location );
				}
				catch( RuntimeException $e )
				{
					throw new RuntimeException( $e->getMessage() );
				}
					
				foreach( $css[ $appDir ][ $location ] as $path => $pathData )
				{
					if ( $path != '.' )
					{
						$_path = $path;
	
						if ( strstr( $path, '/' ) )
						{
							$_path = '';
								
							foreach( explode( '/', $path ) as $dir )
							{
								$_path .= '/' . trim( $dir, '/' );
								
								try
								{
									$pathToWrite = self::_writeThemePathDirectory( $app, 'css', $location . $_path );
								}
								catch( RuntimeException $e )
								{
									throw new RuntimeException( $e->getMessage() );
								}
							}
						}
						else
						{
							try
							{
								$pathToWrite = self::_writeThemePathDirectory( $app, 'css', $location . '/' . $path );
							}
							catch( RuntimeException $e )
							{
								throw new RuntimeException( $e->getMessage() );
							}
						}
					}
						
					foreach( $css[ $appDir ][ $location ][ $path ] as $name => $data )
					{
						$params = array();
						$write  = '';
	
						if ( $data['css_hidden'] )
						{
							$params[] = 'hidden="1"';
						}
	
						if ( count( $params ) )
						{
							$write  .= '/*<ips:css ' . implode( ' ', $params ) . ' />*/' . "\n";
						}
	
						$write .= ( empty( $data['css_content'] ) ) ? '/* No Content */' : $data['css_content'];
							
						if ( ! @file_put_contents( $pathToWrite . '/' . $data['css_name'], $write ) )
						{
							throw new RuntimeException('core_theme_dev_cannot_write_css,' . $pathToWrite . '/' . $data['css_name']);
						}
						else
						{
							@chmod( $pathToWrite . '/' . $data['css_name'], 0777 );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Write templates into the appropriate theme directory as plain text templates ({{logic}} intact)
	 *
	 * @param string $app		 Application Directory
	 * @return	void
	 * @throws	RuntimeException
	 */
	public static function exportTemplates( string $app ): void
	{
		try
		{
			self::_writeThemeContainerDirectory( $app, 'html' );
		}
		catch( RuntimeException $e )
		{
			throw new RuntimeException( $e->getMessage() );
		}
		
		$templates = static::master()->getAllTemplates();
	
		foreach( $templates as $appDir => $appData )
		{
			foreach( $templates[ $app ] as $location => $locationData )
			{
				try
				{
					self::_writeThemePathDirectory( $app, 'html', $location );
				}
				catch( RuntimeException $e )
				{
					throw new RuntimeException( $e->getMessage() );
				}
					
				foreach( $templates[ $app ][ $location ] as $group => $groupData )
				{
					try
					{
						$pathToWrite = self::_writeThemePathDirectory( $app, 'html', $location . '/' . $group );
					}
					catch( RuntimeException $e )
					{
						throw new RuntimeException( $e->getMessage() );
					}
					
					foreach( $templates[ $app ][ $location ][ $group ] as $name => $data )
					{
						$write  = '<ips:template parameters="' . $data['template_data'] . '" />' . "\n";
						$write .= $data['template_content'];
	
						if ( ! @file_put_contents( $pathToWrite . '/' . $data['template_name'] . '.phtml', $write ) )
						{
							throw new RuntimeException('core_theme_dev_cannot_write_template,' . $pathToWrite . '/' . $data['css_name']);
						}
						else
						{
							@chmod( $pathToWrite . '/' . $data['template_name'] . '.phtml', 0777 );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Remove an entire theme directory
	 *
	 * @param string $dir		Path
	 * @return  void
	 */
	public static function removeThemeDirectory( string $dir ): void
	{
		if ( is_dir( $dir ) )
		{
			foreach ( new DirectoryIterator( $dir ) as $f )
			{
				if ( !$f->isDot() )
				{
					if ( $f->isDir() )
					{
						static::removeThemeDirectory( $f->getPathname() );
					}
					else
					{
						@unlink( $f->getPathname() );
					}
				}
			}

			$handle = opendir( $dir );
			closedir( $handle );
			rmdir( $dir );
		}	
	}
	
	/**
	 * Returns the namespace for the template class
	 *
	 * @return string
	 */
	protected static function _getTemplateNamespace(): string
	{
		return 'IPS\\Theme\\Dev\\';
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 *
	 * @param string $app			Application Key
	 * @param string|null $location		Location
	 * @param string|null $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( string $app, string $location=null, string $path=null ): string
	{
		return rtrim( ROOT_PATH . "/applications/{$app}/dev/html/{$location}/{$path}", '/' ) . '/';
	}
	
	/**
	 * Returns the path for the IN_DEV CSS file
	 *
	 * @param string $app			Application Key
	 * @param string|null $location		Location
	 * @param string|null $path			Path or Filename
	 * @return string
	 */
	protected static function _getCssPath( string $app, string $location=null, string $path=null ): string
	{
		/* This is the default, look for an actual CSS file */
		$path = rtrim( ROOT_PATH . "/applications/{$app}/dev/css/{$location}/{$path}", '/' ) . ( stristr( $path, '.css' ) ? '' : '/' );
		if( !file_exists( $path ) )
		{
			/* If the file doesn't exist, move one level up and return the directory */
			$bits = explode( "/", $path );
			array_pop( $bits );
			$path = implode( "/", $bits );
		}

		return $path;
	}
	
	/**
	 * Returns the path for the IN_DEV resource files
	 *
	 * @param string $app			Application Key
	 * @param string|null $location		Location
	 * @param string|null $path			Path or Filename
	 * @return string
	 */
	protected static function _getResourcePath( string $app, string $location=null, string $path=null ): string
	{
		return rtrim( ROOT_PATH . "/applications/{$app}/dev/resources/{$location}/{$path}", '/' ) . ( ( stristr( $path, '.' ) || stristr( $path, '{' ) ) ? '' : '/' );
	}
}