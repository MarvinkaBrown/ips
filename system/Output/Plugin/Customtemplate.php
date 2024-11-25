<?php
/**
 * @brief		Custom Template Plugin - Template
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 April 2023
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function array_keys;
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
class Customtemplate
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = FALSE;
	
	/**
	 * Run the plug-in
	 *
	 * @param string $data	  The initial data from the tag
	 * @param array $options    Array of options
	 * @return	array		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): array
	{
		$params = ( in_array( 'params', array_keys( $options ) ) ) ? $options['params'] : '';
		$hook = ( in_array( 'hook', array_keys( $options ) ) ) ? $options['hook'] : '';

		if( mb_strpos( $data, '$' ) === 0 )
		{
			$data = '{' . $data . '}';
		}

		return [ 'return' => "\\IPS\\Theme\\CustomTemplate::getCustomTemplatesForHookPoint( \"{$data}\", \"{$hook}\", [ {$params} ] )" ];
	}
}