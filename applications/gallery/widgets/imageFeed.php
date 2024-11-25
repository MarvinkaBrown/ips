<?php
/**
 * @brief		Image Feed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		22 Jun 2015
 */

namespace IPS\gallery\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Widget;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Feed Widget
 */
class imageFeed extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'imageFeed';
	
	/**
	 * @brief	App
	 */
	public string $app = 'gallery';

	/**
	 * @brief Class
	 */
	protected static string $class = 'IPS\gallery\Image';
	
	/**
	 * Initialize widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'gallery', 'front' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'gallery.css', 'gallery', 'front' ) );

		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_global.js', 'gallery' ) );

		parent::init();
	}
}