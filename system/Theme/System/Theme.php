<?php
/**
 * @brief		Basic template support
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jun 2013
 */

namespace IPS\Theme\System;
	
/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use function defined;
use const IPS\DEFAULT_THEME_ID;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Basic themes
 */
class Theme extends \IPS\Theme\Dev\Theme
{
	/**
	 * @brief	Template Classes
	 */
	 protected array $templates = array();
	 
	 /**
	 * Get currently logged in member's theme
	 *
	 * @return	Theme
	 */
	public static function i(): Theme
	{
		if ( static::$memberTheme === null )
		{
			static::themes();
			static::$memberTheme = new self;
					
			if ( Dispatcher::i()->controllerLocation === 'front' )
			{
				/* Add in the default theme properties (_data array, etc) */
				foreach( static::$multitons[ DEFAULT_THEME_ID ] as $k => $v )
				{
					static::$memberTheme->$k = $v;
				}
			}
		}
		
		return static::$memberTheme;
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
		if ( $app === NULL )
		{
			$app = Dispatcher::i()->application->directory;
		}
		
		return new Template( $app, $location, $group );
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 * @param string $app			Application Key
	 * @param string|null $location		Location
	 * @param string|null $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( string $app, string $location=null, string $path=null ): string
	{
		return rtrim( \IPS\ROOT_PATH . "/applications/{$app}/data/html/{$location}/{$path}", '/' ) . '/';
	}
	
	/**
	 * Returns the namespace for the template class
	 * @return string
	 */
	protected static function _getTemplateNamespace(): string
	{
		return 'IPS\\Theme\\Basic\\';
	}

}