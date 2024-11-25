<?php
/**
 * @brief		Template Plugin - Pages: Page Url
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		27 May 2015
 */

namespace IPS\cms\extensions\core\OutputPlugins;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Pages\Page;
use IPS\Extensions\OutputPluginsAbstract;
use IPS\Http\Url;
use OutOfRangeException;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Content: Block
 */
class Pageurl extends OutputPluginsAbstract
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = FALSE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string|array		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): string|array
	{
		if ( is_numeric( $data ) )
		{
			try
			{
				$url = Page::load( $data )->url();
			}
			catch( OutOfRangeException $ex )
			{
				$url = NULL;
			}
		}
		else
		{
			$data = ltrim( $data );
			$url = Url::internal( 'app=cms&module=pages&controller=page&path=' . $data, 'front', 'content_page_path', array( $data ) );
		}
		
		return "'" . $url . "'";
	}
}