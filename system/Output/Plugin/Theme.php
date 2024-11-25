<?php
/**
 * @brief		Template Plugin - Theme Setting
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\cloud\extensions\core\AccountSettings\Expert;
use IPS\Log;
use IPS\Theme as SystemTheme;
use IPS\Theme\Editor\Setting;
use OutOfRangeException;
use function defined;
use function mb_substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Theme settings
 */
class Theme
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
	 * @return	mixed	Code to eval
	 * @note	Using this plugin to call the sharer logos or favicon is deprecated and will be removed in a future version
	 */
	public static function runPlugin( string $data, array $options ): mixed
	{
		switch( $data )
		{
			case 'headerHtml':
				return "\IPS\Theme::i()->getHeaderAndFooter( 'header' )";

			case 'footerHtml':
				return "\IPS\Theme::i()->getHeaderAndFooter( 'footer' )";

			case 'logo_front':
				return "\IPS\Theme::i()->logo_front";

			case 'logo_height':
				return ( isset( SystemTheme::i()->logo['front']['height'] ) ) ? '\IPS\Theme::i()->logo[\'front\'][\'height\']' : 100;

			case 'custom_css_for_output':
				return "\IPS\Theme::i()->getCustomCssForOutput()";

			case 'custom_css':
				return "\IPS\Theme::i()->custom_css";

			case "scheme":
				return "\IPS\Theme::i()->getCurrentCSSScheme()";

			case 'id':
				return "\IPS\Theme::i()->id";
			default:
				try
				{
					if ( mb_substr( $data, 0, 4 ) === 'css.' or mb_substr( $data, 0, 4 ) === 'var.' )
					{
						$variable = mb_substr( $data, 4 );
						$cssVariables = SystemTheme::i()->getCssVariables();

						if( array_key_exists( $variable, $cssVariables ) )
						{
							return "\IPS\Theme::i()->getParsedCssVariableFromKey( '{$variable}' )";
						}
						return '';
					}
					else if ( mb_substr( $data, 0, 5 ) === 'view.' )
					{
						$variable = mb_substr( $data, 5 );
						return "\IPS\Theme::i()->getLayoutValue('{$variable}')";
					}

					return ( array_key_exists( $data, SystemTheme::i()->settings ) ) ? "\IPS\Theme::i()->settings['{$data}']" : '';
				}
				catch( InvalidArgumentException $e )
				{
					Log::log( $e, 'template_error' );
				}
				return "";
		}
	}
}