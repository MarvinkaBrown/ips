<?php
/**
 * @brief		Template Plugin - Theme Resource (image, font, theme-specific JS, etc)
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Data\Cache;
use IPS\IPS;
use IPS\Member;
use IPS\Theme;
use function array_pop;
use function count;
use function defined;
use function explode;
use function implode;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Theme Resource (image, font, theme-specific JS, etc)
 */
class Resource
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = TRUE;
	
	/**
	 * Run the plug-in
	 *
	 * @param string $data		The initial data from the tag
	 * @param array $options    Array of options
	 * @param string $context	The name of the calling function
	 * @return	string		Code to eval
	 */
	public static function runPlugin( string $data, array $options, string $context ): string
	{	
		$exploded = explode( '_', $context );
		$app      = ( $options['app'] ?? ( $exploded[1] ?? 'core' ) );
		$location = ( $options['location'] ?? ( $exploded[2] ?? 'front' ) );
		$noProtocol =  ( isset( $options['noprotocol'] ) ) ? $options['noprotocol'] : "false";

		if ( ( ! \IPS\IN_DEV or Application::areWeBuilding() ) and isset( $options['inCss'] ) and $options['inCss'] and $app and in_array( $app, IPS::$ipsApps ) )
		{
			/* We can use a relative path */
			$paths    = explode( '/', $data );
			$name     = array_pop( $paths );
			$path     = ( count( $paths ) ) ? ( '/' . implode( '/', $paths ) . '/' ) : '/';
			$hash = Theme::makeBuiltTemplateLookupHash( $app, $location, $path );

			if ( $location === 'interface' )
			{
				return '"../../applications/' . $app . '/interface/' . $data . '"';
			}

			return '"../resources/' . $app . '_' . $hash  . '_' . $name . '"';
		}

		return "\\IPS\\Theme::i()->resource( \"{$data}\", \"{$app}\", '{$location}', {$noProtocol} )";
	}
}