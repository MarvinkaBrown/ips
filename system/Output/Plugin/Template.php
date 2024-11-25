<?php
/**
 * @brief		Template Plugin - Template
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Template
 */
class Template
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = FALSE;

	/**
	 * Run the plug-in
	 *
	 * @param string $data The initial data from the tag
	 * @param array $options Array of options
	 * @param string|null $functionName
	 * @param string $calledClass
	 * @return array|string Code to eval
	 */
	public static function runPlugin( string $data, array $options, ?string $functionName=NULL, string $calledClass='IPS\Theme' ): array|string
	{
		$params = ( in_array( 'params', array_keys( $options ) ) ) ? $options['params'] : '';
		if( mb_strpos( $data, '$' ) === 0 )
		{
			$data = '{' . $data . '}';
		}
		
		if ( isset( $options['object'] ) )
		{
			return $options['object'] . "->{$data}( {$params} )";
		}
		else
		{
			$app    = isset( $options['app'] ) ? "\"{$options['app']}\"" : '\IPS\Request::i()->app';
			if ( isset( $options['location'] ) )
			{
				$app .= ", '{$options['location']}'";
			}
			
			if ( isset( $options['themeClass'] ) )
			{
				$calledClass = $options['themeClass'];
			}	
			
			$it = array( 'return' => '\\' . $calledClass . "::i()->getTemplate( \"{$options['group']}\", {$app} )->{$data}( {$params} )" );
			
			if ( isset( $options['if'] ) )
			{
				$it['pre']  = "if ( " . Theme::expandShortcuts( $options['if'] ) . " ):\n";
				$it['post'] = "\nendif;";
			}
			
			return $it;
		}
	}
}