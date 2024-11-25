<?php
/**
 * @brief		Categories Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		24 Sept 2014
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Pages\Page;
use IPS\Http\Url;
use IPS\Widget\PermissionCache;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Categories Widget
 */
class Categories extends PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'Categories';
	
	/**
	 * @brief	App
	 */
	public string $app = 'cms';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* If we're not on a Pages page, return nothing */
		if( !Page::$currentPage )
		{
			return '';
		}

		/* Scope makes it possible for this block to fire before the main block which sets up the dispatcher */
		$db = NULL;
		if ( ! Dispatcher::i()->databaseId )
		{
			try
			{
				$db = Page::$currentPage->getDatabase()->id;
			}
			catch( Exception $ex )
			{

			}
		}
		else
		{
			$db = Dispatcher::i()->databaseId;
		}

		if ( ! Page::$currentPage->full_path or ! $db )
		{
			return '';
		}

		$url = Url::internal( "app=cms&module=pages&controller=page&path=" . Page::$currentPage->full_path, 'front', 'content_page_path', Page::$currentPage->full_path );

		return $this->output($url);
	}
}