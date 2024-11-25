<?php
/**
 * @brief		Template Plugin - Font size
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		9 Apr 2020
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Font size
 * @note this is still required as the ACP relies on this
 */
class Fontsize
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
		$number = 14;

		/* Create the array for the ACP based on v4 font-sizes @todo review this! */
		$sizes = [
			'font_base' => 16,
			'font_large' => 18,
			'font_medium' => 14,
			'font_small' => 13,
			'font_x_large' => 20,
			'font_x_small' => 12,
			'font_2x_large' => 24,
			'font_3x_large' => 30,
			'font_4x_large' => 36,
			'font_size' => 100,
		];

		// Is this a theme setting or a number?
		if( preg_match('/^[0-9]+?$/', $data ) )
		{
			$number = intval( $data );
		} 
		else if ( isset( $sizes[ 'font_' . $data ] ) )
		{
			$number = $sizes[ 'font_' . $data ];
		}

		$scale = $sizes['font_size'];

		// Should we be scaling?
		if( $scale !== 100 && ( !isset( $options['scale'] ) || $options['scale'] !== false ) )
		{
			$number = round( $scale * ( $number / 100 ), 1 );
		}

        // e.g. 100 * 0.14 = 14px
        return '"' . number_format( $number, 1 ) . 'px"';
	}
}