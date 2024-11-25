<?php
/**
 * @brief		Gallery statistics widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		25 Mar 2014
 */

namespace IPS\gallery\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Filter;
use IPS\Db;
use IPS\gallery\Album\Item;
use IPS\gallery\Image;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\PermissionCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gallery statistics widget
 */
class galleryStats extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'galleryStats';
	
	/**
	 * @brief	App
	 */
	public string $app = 'gallery';

	/**
	 * @brief	Cache Expiration - 24h
	 */
	public int $cacheExpiration = 86400;
	
	/**
	 * Initialize widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'widgets.css', 'gallery', 'front' ) );

		parent::init();
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$stats = [];

		$approxRows = Image::databaseTableCount( TRUE );

		if ( $approxRows > 1000000 )
		{
			$stats['totalImages'] = $approxRows;
			$stats['totalComments'] = (int) Db::i()->query( "SHOW TABLE STATUS LIKE '" . Db::i()->prefix . "gallery_comments';" )->fetch_assoc()['Rows'];
		}
		else
		{
			$stats = Db::i()->select( 'COUNT(*) AS totalImages, SUM(image_comments) AS totalComments', 'gallery_images', [ "image_approved=?", 1 ] )->first();
		}

		$stats['totalAlbums'] = Item::databaseTableCount( TRUE );

		$latestImage = NULL;
		foreach ( Image::getItemsWithPermission( [], NULL, 1, 'read', Filter::FILTER_PUBLIC_ONLY ) as $latestImage )
		{
			break;
		}

		return $this->output( $stats, $latestImage );
	}
}