<?php
/**
 * @brief		Pages Page Item Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		15 Dec 2017
 */

namespace IPS\cms\Pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Item;
use IPS\Content\ViewUpdates;
use IPS\Http\Url;
use IPS\Node\Model;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package Item Model
 */
class PageItem extends Item
{
	use ViewUpdates;

	/**
	 * @brief	Application
	 */
	public static string $application = 'cms';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'pages';
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'cms_pages';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'page_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
			
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'views'	=> 'views'
	);
	
	/**
	 * @brief	Title
	 */
	public static string $title = 'cms_page';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'file';
	
	/**
	 * @brief	Include In Sitemap
	 */
	public static bool $includeInSitemap = FALSE;
	
	/**
	 * @brief	Can this content be moderated normally from the front-end (will be FALSE for things like Pages and Commerce Products)
	 */
	public static bool $canBeModeratedFromFrontend = FALSE;
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		return array( 'page_id', 'page_folder_id', 'page_full_path', 'page_default' );
	}

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Get URL
	 *
	 * @param string|null $action Action
	 * @return    Url
	 */
	public function url( ?string $action=NULL ): Url
	{
		return Page::constructFromData( $this->_data )->url();
	}
}