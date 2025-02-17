<?php
/**
 * @brief		albums Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		24 Mar 2017
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
 * featuredAlbums Widget
 */
class albums extends Widget
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'albums';
	
	/**
	 * @brief	App
	 */
	public string $app = 'gallery';
		

	
	/**
	 * @brief Class
	 */
	protected static string $class = 'IPS\gallery\Album\Item';

	/**
	 * @brief	Moderator permission to generate caches on [optional]
	 */
	protected array $moderatorPermissions	= array( 'can_view_hidden_content', 'can_view_hidden_gallery_album' );

	/**
	 * Initialize widget
	 *
	 * @return	null
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'gallery', 'front' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'gallery.css', 'gallery', 'front' ) );
		parent::init();
	}
}