<?php
/**
 * @brief		Profile extension: galleryImages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		03 Nov 2022
 */

namespace IPS\gallery\extensions\core\Profile;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content\Filter;
use IPS\Extensions\ProfileAbstract;
use IPS\gallery\Image;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Profile extension: galleryImages
 */
class galleryImages extends ProfileAbstract
{
	/**
	 * Is there content to display?
	 *
	 * @return	bool
	 */
	public function showTab(): bool
	{
		$where = [
			[ 'image_member_id=?', $this->member->member_id ],
			[ 'image_album_id=?', 0 ]
		];
		$items = Image::getItemsWithPermission( $where, null, 1, 'read', Filter::FILTER_AUTOMATIC, 0, null, false, false,false,true );
		return $items > 0;
	}

	/**
	 * Display
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$table ="";
		foreach ( Application::load( 'gallery' )->extensions( 'core', 'ContentRouter' ) as $ext )
		{
			$table = $ext->customTableHelper( 'IPS\gallery\Image', $this->member->url()->setQueryString( 'tab', 'node_gallery_galleryImages'), array( array( 'image_member_id=? and image_album_id=0', $this->member->member_id ) ) );
		}

		return (string) $table;
	}
}