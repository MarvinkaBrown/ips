<?php
/**
 * @brief		[Front] Page Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		25 Feb 2014
 */

namespace IPS\cms\modules\front\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Blocks\Block;
use IPS\cms\Pages\Page;
use IPS\cms\Widget;
use IPS\cms\widgets\Blocks;
use IPS\core\modules\front\system\widgets;
use IPS\Db;
use IPS\Output;
use IPS\Request;
use IPS\Widget\Area;
use OutOfRangeException;
use Throwable;
use UnderFlowException;
use function count;
use function defined;
use function in_array;
use function var_export;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class builder extends widgets
{
	/**
	 * The widget class to use for configuration
	 *
	 * @var string
	 */
	protected string $widgetClass = Widget::class;

	/**
	 * Reorder Blocks
	 *
	 * @return	void
	 */
	protected function saveOrder() : void
	{
		parent::saveOrder();
		$page = Page::load( Request::i()->pageID );
		$page->postSaveArea();
	}
	
	/**
	 * Sometimes the widgets end up in the core table. We haven't really found out why this happens. It happens very rarely.
	 * It may be that the CMS JS mixin doesn't load so the core ajax URLs are used (system/widgets.php) and not the cms widget (page/builder.php).
	 * This method ensures that any widgets in the core table are removed
	 *
	 * @param string $uniqueId	The unique key of the widget (eg: wzsj1233)
	 * @param array $widgets	Current widgets (eg from core_widget_areas.widgets (json decoded))
	 * @return	bool				True if something removed, false if not
	 */
	protected function _checkAndDeleteFromCoreWidgets( string $uniqueId, array $widgets ): bool
	{
		if ( ! in_array( $uniqueId, $widgets ) )
		{
			/* This widget hasn't been seen, so it isn't in the cms table */
			try
			{
				$cmsWidget = Db::i()->select( '*', 'core_widget_areas', array( 'app=? and module=? and controller=? and area=?', 'cms', 'pages', 'page', Request::i()->area ) )->first();
				$cmsWidgets = json_decode( $cmsWidget['widgets'], TRUE );
				$newWidgets = array();
				
				foreach( $cmsWidgets as $item )
				{
					if ( $item['unique'] !== $uniqueId )
					{
						$newWidgets[] = $item;
					}
				}
				
				/* Anything to save? */
				if ( count( $newWidgets ) )
				{
					Db::i()->replace( 'core_widget_areas', array( 'app' => 'cms', 'module' => 'pages', 'controller' => 'page', 'widgets' => json_encode( $newWidgets ), 'area' => Request::i()->area ) );
				}
				else
				{
					/* Just remove the entire row */
					Db::i()->delete( 'core_widget_areas', array( 'app=? and module=? and controller=? and area=?', 'cms', 'pages', 'page', Request::i()->area ) );
				}
				
				return TRUE;
			}
			catch( UnderFlowException $ex )
			{
				/* Well, it isn't there either... */
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Get an array containing all the areas in this page
	 *
	 * @param 	string|null 		$area		The area to filter by; by default (null) it will get all areas
	 *
	 * @return Area[]		Returns an array mapping the widget areas to the widgets in that area
	 */
	public function getAreasFromDatabase( ?string $area=null ) : array
	{
		$page = Page::load( Request::i()->pageID );
		return $page->getAreasFromDatabase( $area );
	}

	/**
	 * Save an area to the database and link it to the page
	 *
	 * @param Area $area
	 * @return void
	 */
	public function saveArea( Area $area ) : void
	{
		$page = Page::load( Request::i()->pageID );
		$page->saveArea( $area );

		/* Clear caches */
		Widget::deleteCaches();
	}

	/**
	 * Output the content of a custom block
	 *
	 * @return void
	 */
	public function getCustomBlockContent() : void
	{
		try
		{
			$block = Block::load( Request::i()->id );

			try
			{
				$widget = $block->widget();
			}
			catch ( OutOfRangeException )
			{
				$widget = new Blocks( "", ['cms_widget_custom_block' => $block->key] );
			}
			Output::i()->json( [ "content" => (string) $widget, "configuration" => $widget->dataAttributes(), 'block_key' => $block->key ], 200 );
		}
		catch ( UnderFlowException|OutOfRangeException $e )
		{
			Output::i()->json( [ "message" => "could not find block" ], 404 );
		}
		catch ( Throwable $e )
		{
			if ( \IPS\IN_DEV )
			{
				Output::i()->json( [ "error" => var_export( $e, true ) ], 500 );
			}
		}
	}
}