<?php
/**
 * @brief		Sidebar Widgets
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Nov 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Blocks\Block;
use IPS\cms\widgets\Blocks;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Widget;
use IPS\Widget\Area;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function explode;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sidebar Widgets
 */
class widgets extends Controller
{
	/**
	 * The widget class to use for configuration
	 *
	 * @var string
	 */
	protected string $widgetClass = Widget::class;

	protected string $pageApp = '';
	protected string $pageModule = '';
	protected string $pageController = '';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_manage_sidebar') )
		{
			Output::i()->error( 'no_permission_manage_sidebar', '2S172/1', 403, '' );
		}

		$this->pageApp = Request::i()->pageApp ?? '';
		$this->pageModule = Request::i()->pageModule ?? '';
		$this->pageController = Request::i()->pageController ?? '';
		if( isset( Request::i()->pageArea ) AND Request::i()->pageArea == Area::AREA_GLOBAL_FOOTER )
		{
			/* Re-assign values to global */
			$this->pageApp = 'global';
			$this->pageModule = 'global';
			$this->pageController = 'global';
		}
		
		parent::execute();
	}
	
	/**
	 * Build The Block List For Front End
	 *
	 * @return	void
	 */
	protected function getBlockList() : void
	{
		/* Initialize with Pages as the first in the list */
		$availableBlocks = array(
			'cms' => []
		);

		$favoriteIds = json_decode( Settings::i()->favorite_blocks, true );
		$favorites = [];
		$customBlocks = [];

		/* Loop through applications and get all available blocks */
		foreach( Application::enabledApplications() as $application )
		{
			if( !$application->canAccess() )
			{
				continue;
			}

			foreach( $application->getAvailableWidgets() as $block )
			{
				/* @var Widget $block */
				if ( !$block->isExecutableByApp( array( $this->pageApp, Area::AREA_SIDEBAR ) ) )
				{
					continue;
				}

				/* Is this widget hidden from the block list? */
				if( !$block::$showInBlockList )
				{
					continue;
				}

				if ( in_array( $block->key, $favoriteIds ) )
				{
					$favorites[$block->key] = $block;
				}

				if ( $block instanceof Blocks )
				{
					foreach ( new ActiveRecordIterator( Db::i()->select( "*", "cms_blocks" ), Block::class ) as $customBlock )
					{
						$title = $customBlock->_title;
						Member::loggedIn()->language()->parseOutputForDisplay( $title );
						$customBlocks[$title] = [$block, $customBlock];
						if ( in_array( $block->key . ":" . $title, $favoriteIds ) )
						{
							$favorites[$block->key . ":" . $title] = [$block, $customBlock];
						}
					}
				}

				$availableBlocks[ $application->directory ][] = $block;
			}
		}

		$favoritesSorted = [];
		foreach ( $favoriteIds as $id )
		{
			if ( isset( $favorites[$id] ) )
			{
				$favoritesSorted[$id] = $favorites[$id];
			}
		}
		Output::i()->output = Theme::i()->getTemplate( 'widgets' )->blockList( $availableBlocks, $favoritesSorted, $customBlocks );
	}
	
	/**
	 * Get Output For Adding A New Block
	 *
	 * @return	void
	 */
	protected function getBlock() : void
	{		
		$key = explode( "_", Request::i()->blockID );
		$area = null;
		$areaBlocks = null;

		/* @var Widget $widgetClass */
		$widgetClass = $this->widgetClass;

		foreach( $this->getAreasFromDatabase() as $item )
		{
			$blocks = $item->getAllWidgets();
			if( Request::i()->pageArea == $item->id )
			{
				$area = $item;
				$areaBlocks = $blocks;
			}

			foreach( $blocks as $block )
			{
				if( isset( $block['key'] ) AND $block['key'] == $key[2] AND $block['unique'] == $key[3] )
				{
					$config = ( isset( $block['configuration'] ) AND !empty( $block['configuration'] ) ) ? $block['configuration'] : $widgetClass::getConfiguration( $key[3] );
					$widget = Widget::load( Application::load( $block['app'] ), $block['key'], $block['unique'], $config, null, Request::i()->orientation, Request::i()->layout ?: '' );
					$widget->neverCache = true;
					break;
				}
			}
		}

		if ( !isset( $widget ) )
		{
			$config = json_decode( Request::i()->defaultConfiguration ?: '""', true  );
			$config = is_array( $config ) ? $config : ( ( isset( $key[3] ) and $key[3] )  ? $widgetClass::getConfiguration( $key[3] ) : array() );
			$widget = Widget::load( Application::load( $key[1] ), $key[2], ( $key[3] ?? '' ), $config, null, Request::i()->orientation );

			// it's possible we want to save it
			try
			{
				if ( Request::i()->createIfDoesNotExist and Request::i()->pageArea and is_array( $areaBlocks ) )
				{
					Session::i()->csrfCheck();
					$newBlock = [
						'app' => $key[1],
						'key' => $key[2],
						'unique' => $key[3],
						'configuration' => $config
					];
					$areaBlocks[] = $newBlock;

					if( $area === null )
					{
						$area = Area::create( Request::i()->pageArea, $areaBlocks );
					}
					else
					{
						$area->addWidget( $newBlock );
					}

					$this->saveArea( $area );
					Widget::deleteCaches();
				}
			}
			catch ( OutOfRangeException ) {}
		}

		$output = (string) $widget;
		Output::i()->output = ( $output ) ?:  Theme::i()->getTemplate( 'widgets', 'core', 'front' )->blankWidget( $widget );
	}
	
	/**
	 * Get Configuration
	 *
	 * @return	void
	 */
	protected function getConfiguration() : void
	{
		$key = explode( "_", Request::i()->block );

		/* @var Widget $widgetClass */
		$widgetClass = $this->widgetClass;

		try
		{
			$widgetMaster = Db::i()->select( '*', 'core_widgets', array( '`key`=? AND `app`=?', $key[2], $key[1] ) )->first();
		}
		catch ( UnderflowException $e ){}

		$blocks = [];

		/* @var Area $currentArea */
		$currentArea = $this->getAreasFromDatabase()[ Request::i()->pageArea ] ?? null;
		if( $currentArea !== null )
		{
			$blocks = $currentArea->getAllWidgets();
		}

		$widget	= NULL;
		$requestedBlock = $key[3] ?? '';
		if( !empty( $blocks ) )
		{
			foreach ( $blocks as $k => $block )
			{
				if ( $requestedBlock and (string) $k !== $requestedBlock )
				{
					continue;
				}

				if ( $block['key'] == $key[2] AND $block['unique'] == $key[3] )
				{
					$config = ( isset( $block['configuration'] ) AND !empty( $block['configuration'] ) ) ? $block['configuration'] : $widgetClass::getConfiguration( $key[3] );
					$widget = Widget::load( Application::load( $block['app'] ), $block['key'], $block['unique'], $config ?? [] );
					$widget->menuStyle = $widgetMaster['menu_style'] ?? 'menu';

					if ( isset( $widgetMaster ) and $widgetMaster['layouts'] === '*' and $widget->isCustomizableWidget() )
					{
						$widget->layouts = Area::$allowedWrapBehaviors;
					}
					else if ( !empty( $widgetMaster['layouts'] ) )
					{
						$widget->layouts = array_intersect( Area::$allowedWrapBehaviors, explode( ',', $widgetMaster['layouts'] ) );
					}
					else
					{
						$widget->layouts = isset( $widgetMaster['default_layout'] ) ? [ $widgetMaster['default_layout'] ] : ['wrap'];
					}
				}

				if( $widget !== NULL AND method_exists( $widget, 'configuration' ) )
				{
					$form = new Form( 'form', 'saveSettings' );
					if ( $widget->configuration( $form ) !== NULL )
					{
						if ( $values = $form->values() )
						{
							if ( method_exists( $widget, 'preConfig' ) )
							{
								$values = $widget->preConfig( $values );
							}

							/* Special advanced builder stuff */
							if( $widget->isBuilderWidget() )
							{
								if( isset( $values['widget_adv__background_custom_image'] ) and $values['widget_adv__background_custom_image'] )
								{
									$values['widget_adv__background_custom_image'] = (string) $values['widget_adv__background_custom_image'];
								}
							}

							$blocks[ $k ]['configuration'] = $values;
							$actualArea = $currentArea->addWidget( $blocks[ $k ] );
							$this->saveArea( $currentArea );
							Output::i()->json( [ 'blocks' => $blocks[$k], 'areaClasses'  => $actualArea->classes() ] );
						}

						Output::i()->output = $widget->configuration()->customTemplate( array( Theme::i()->getTemplate( 'widgets', 'core' ), 'formTemplate' ), $widget );
					}
				}
			}
		}
	}
	
	/**
	 * Reorder Blocks
	 *
	 * @return	void
	 */
	protected function saveOrder() : void
	{
		$newOrder = array();
		$seen     = array();
		$widgets = array();

		Session::i()->csrfCheck();

		$currentConfig = $this->getAreasFromDatabase()[ Request::i()->area ] ?? null;
		if( $currentConfig )
		{
			$widgets = $currentConfig->getAllWidgets();
		}

		/* Loop over the new order and merge in current blocks so we don't lose config */
		if ( isset ( Request::i()->order ) )
		{
			foreach ( Request::i()->order as $block )
			{
				$block = explode( "_", $block );
				$added = FALSE;
				foreach( $widgets as $widget )
				{
					if ( $widget['key'] == $block[2] and $widget['unique'] == $block[3] )
					{
						$seen[]     = $widget['unique'];
						$newOrder[] = $widget;
						$added = TRUE;
						break;
					}
				}

				if( !$added )
				{
					/* @var Widget $widgetClass */
					$widgetClass = $this->widgetClass;

					$newBlock = [
						'app' => $block[1],
						'key' => $block[2],
						'unique' => $block[3],
						'configuration' => $widgetClass::getConfiguration( $block[3] )
					];
					$seen[]     = $block[3];
					$newOrder[] = $newBlock;
				}
			}
		}

		/* Anything to update? */
		if ( count( $widgets ) > count( $newOrder ) )
		{
			/* No items left in area, or one has been removed */
			foreach( $widgets as $widget )
			{
				/* If we haven't seen this widget, it's been removed, so add to trash */
				if ( ! in_array( $widget['unique'], $seen ) )
				{
					Widget::trash( $widget['unique'], $widget );
				}
			}
		}

		/* Check core_widget_areas to ensure that the block wasn't added there */
		if ( isset( Request::i()->exclude ) and ! empty( Request::i()->exclude ) )
		{
			$bits = explode( "_", Request::i()->exclude );
			$this->_checkAndDeleteFromCoreWidgets( $bits[3], $seen );
		}

		/* Expire Caches so up to date information displays */
		Widget::deleteCaches();

		/* Overwrite the entire area */
		$newArea = Area::create( Request::i()->area, $newOrder );
		$this->saveArea( $newArea );
	}

	/**
	 * Remove the block from the page area
	 *
	 * @return void
	 */
	protected function removeBlock() : void
	{
		Session::i()->csrfCheck();
		try
		{
			$uniques = [];
			foreach ( Request::i()->blockIDs as $blockID )
			{
				$key = explode( '_', $blockID );
				$uniques[] = $key[3];
			}

			/* For deletion, only remove the blocks in the area. When moving a block from one area to another, the remove block call is separate */
			foreach ( $this->getAreasFromDatabase( Request::i()->area ?: null ) as $widgetArea )
			{
				foreach( $uniques as $unique )
				{
					$widgetArea->removeWidget( $unique );

					Db::i()->delete( 'core_widgets_config', [ 'id=?', $unique ] );
				}

				$this->saveArea( $widgetArea );
			}
		}
		catch ( OutOfRangeException ) {}

		Output::i()->json( [ 'message' => 'deleted' ], 201 );
	}



	/**
	 * @return void
	 */
	protected function saveWidgetTree() : void
	{
		/* First, load all the widgets in the page. It is possible they moved a widget from one area to another in the same page, so we need to copy the configuration from those areas */
		$existingWidgets = [];
		foreach ( $this->getAreasFromDatabase() as $row )
		{
			$existingWidgets = array_merge( $existingWidgets, $row->getAllWidgets() );
		}

		/* Clean the tree, sometimes there are duplicates */
		$tree = Area::cleanTreeData( json_decode( Request::i()->tree, true ) );

		/* Create a new area */
		$area = new Area( $tree, Request::i()->pageArea );
		$currentWidgets = $area->getAllWidgets();

		/* The tree data does not contain block configuration, so we put that back in */
		foreach ( $existingWidgets as $existing )
		{
			if ( $area->replaceWidget( $existing ) )
			{
				$currentWidgets[$existing['unique']] = $existing;
			}
		}

		$this->saveArea( $area );
		$output = [
			'message' => 'saved',
			'area_tree' => $area->toArray(),
		];

		if ( defined( 'PAGEBUILDER_DEV' ) and \PAGEBUILDER_DEV )
		{
			$output['area_tree'] = $area->toArray();
			$output['currentWidgets'] = $currentWidgets;
			$output['existingWidgets'] = $existingWidgets;
		}

		Output::i()->json( $output, 201 );
	}

	/**
	 * Get an array containing all the areas in this page
	 *
	 * @param 	string|null 		$area		The area to filter by; by default (null) it will get all areas	 *
	 * @return Area[]		Returns an array mapping the widget areas to the widgets in that area
	 */
	public function getAreasFromDatabase( ?string $area=null ) : array
	{
		return Area::getAreasFromDatabase( $this->pageApp, $this->pageModule, $this->pageController, $area );
	}

	/**
	 * Save an area to the database and link it to the page
	 *
	 * @param Area $area
	 * @return void
	 */
	public function saveArea( Area $area ) : void
	{
		$pageApp = Request::i()->pageApp;
		$pageModule = Request::i()->pageModule;
		$pageController = Request::i()->pageController;
		if ( $area->id == 'globalfooter' )
		{
			/* Re-assign values to global */
			$pageApp = 'global';
			$pageModule = 'global';
			$pageController = 'global';
		}

		/* If we have no content, clear this out entirely */
		if( !$area->hasWidgets() )
		{
			Db::i()->delete( 'core_widget_areas', ['area=? AND app=? and module=? and controller=?', $area->id, $pageApp, $pageModule, $pageController ] );
			return;
		}

		/* Store the widget configuration */
		foreach( $area->getAllWidgets() as $widget )
		{
			if( isset( $widget['configuration'] ) AND !empty( $widget['configuration'] ) )
			{
				Db::i()->replace( 'core_widgets_config', [
					'id' => $widget['unique'],
					'data' => json_encode( $widget['configuration'] )
				] );
			}
		}

		/* Does the area exist? */
		try
		{
			$row = Db::i()->select( '*', 'core_widget_areas', [ 'area=? and app=? and module=? and controller=?', $area->id, $pageApp, $pageModule, $pageController ] )->first();

			Db::i()->update( 'core_widget_areas', [
				'tree' => json_encode( $area->toArray( true, false ) )
			], ['area=? AND app=? and module=? and controller=?', $area->id, $pageApp, $pageModule, $pageController ] );
		}
		catch( UnderflowException )
		{
			Db::i()->insert( 'core_widget_areas', [
				'app' => $pageApp,
				'module' => $pageModule,
				'controller' => $pageController,
				'area' => $area->id,
				'widgets' => '[]',
				'tree' => json_encode( $area->toArray( true, false ) )
			]);
		}

		/* Clear caches */
		Widget::deleteCaches( null, $pageApp == 'global' ?: null );
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
		/* Placeholder function so that we can override this in the CMS builder */
		return false;
	}

	/**
	 * @return void
	 */
	protected function addFavorite()
	{
		if ( !isset( Request::i()->blockID ) )
		{
			Output::i()->json( [ "message" => "expected block id" ], 400 );
		}

		$favorites = json_decode( Settings::i()->favorite_blocks, true );
		if ( !in_array( Request::i()->blockID, $favorites ) )
		{
			$favorites[] = Request::i()->blockID;
		}
		Settings::i()->changeValues( [ "favorite_blocks" => json_encode( $favorites ) ] );
		Output::i()->json( [ "favorites" => $favorites ], 201 );
	}



	/**
	 * @return void
	 */
	protected function removeFavorite()
	{
		if ( !isset( Request::i()->blockID ) )
		{
			Output::i()->json( [ "message" => "expected block id" ], 400 );
		}

		$favorites = json_decode( Settings::i()->favorite_blocks, true );
		$favorites = array_filter( $favorites, function ($val) {
			return $val !== Request::i()->blockID;
		});
		Settings::i()->changeValues( [ "favorite_blocks" => json_encode( $favorites ) ] );
		Output::i()->json( [ "favorites" => $favorites ], 201 );
	}
}