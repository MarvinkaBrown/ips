<?php
/**
 * @brief		Template Plugin - File
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 April 2015
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\File as SystemFile;
use IPS\Request;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - File
 */
class File
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = TRUE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): string
	{
		$extension = ( $options['extension'] ?? 'core_Attachment' );
		$scheme    = ( $options['scheme'] ?? NULL );
		$cacheBust = ( $options['cb'] ?? NULL );
		$schemeString = $cbString = '';

		if ( $scheme )
		{
			$fullScheme = ( Request::i()->isSecure() ) ? 'https' : 'http';
			$schemeString = ( $scheme == 'full' ) ? '->setScheme("' . $fullScheme .'")' : '->setScheme(NULL)';
		}

		if ( $cacheBust )
		{
			$cbString = "->setQueryString( 'v', " . $cacheBust . ")";
		}

		if ( $data instanceof SystemFile )
		{
			return "(string) " . $data . "->url" . $schemeString . $cbString;
		}
		
		if ( mb_substr( $extension, 0, 1 ) === '$' )
		{
			return "\\IPS\\File::get( {$extension}, " . $data . " )->url" . $schemeString . $cbString;
		}
		else
		{
			return "\\IPS\\File::get( \"{$extension}\", " . $data . " )->url" . $schemeString . $cbString;
		}
	}
}