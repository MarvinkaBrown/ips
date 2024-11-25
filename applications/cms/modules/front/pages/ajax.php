<?php

/**
 * @brief		ajax
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		13 Dec 2023
 */

namespace IPS\cms\modules\front\pages;

use IPS\cms\Categories;
use IPS\cms\Pages\Folder;
use IPS\cms\Pages\Page;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use IPS\Output;
use IPS\Request;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ajax
 */
class ajax extends \IPS\cms\modules\admin\pages\ajax
{
	/**
	 * Load navigation items
	 *
	 * @return void
	 */
	protected function loadItems() : void
	{
		if( Request::i()->navType == 'folder' )
		{
			try
			{
				$folder = Folder::load( Request::i()->container );
				$items = iterator_to_array(
					new ActiveRecordIterator(
						Db::i()->select( '*', 'cms_pages', [ 'page_folder_id=?', $folder->id ] ),
					Page::class
					)
				);
				Output::i()->sendOutput( Theme::i()->getTemplate( 'widgets', 'cms', 'front' )->folderNavigationItems( $items ) );
			}
			catch( OutOfRangeException ){}
		}
		else
		{
			/* @var Categories $containerClass */
			$containerClass = 'IPS\cms\Categories' . Request::i()->navId;
			try
			{
				$container = $containerClass::load( Request::i()->container );
				Output::i()->sendOutput( Theme::i()->getTemplate( 'widgets', 'cms', 'front' )->databaseNavigationItems( $container->getContentItems( null, null ) ) );
			}
			catch( OutOfRangeException ){}
		}
	}
}