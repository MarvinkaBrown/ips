<?php

/**
 * @brief        Area
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        12/12/2023
 */

namespace IPS\Widget;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Lang;
use IPS\Member;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use function strrpos;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Area
{
	const AREA_HEADER = 'header';
	const AREA_FOOTER = 'footer';
	const AREA_SIDEBAR = 'sidebar';
	const AREA_GLOBAL_FOOTER = 'globalfooter';

	/**
	 * @var array|string[]
	 */
	public static array $reservedAreas = [
		'header', 'footer', 'sidebar', 'globalfooter'
	];

	/**
	 * @var string[]
	 */
	public static array $allowedWrapBehaviors = [
		'wrap',
		'carousel',
		'grid',
		'grid-carousel',
		'center',
		'columnLeft',
		'columnRight',
		'minimal',
		'minimal-carousel',
		'featured',
		'featured-carousel',
		'mini-grid',
		'mini-grid-carousel',
		'table',
		'table-carousel',
		'wallpaper',
		'wallpaper-carousel'
	];

	/**
	 * @var string[]
	 */
	public static array $widgetOnlyLayouts = [
		'grid',
		'grid-carousel',
		//'list',
		'minimal',
		'minimal-carousel',
		'featured',
		'featured-carousel',
		'mini-grid',
		'mini-grid-carousel',
		'table',
		'table-carousel',
		'wallpaper',
		'wallpaper-carousel'
	];

	/**
	 * These layouts don't get custom sizes
	 * @var string[]	$noSizeLayouts
	 */
	public static array $noSizeLayouts = ['table-carousel','stack','list','minimal', "table", "featured",'featured-carousel'];

	/**
	 * These layouts don't have any gaps when they are used for widgets
	 * @var string[]  $noGapLayouts
	 */
	public static array $noGapLayouts = ['grid', 'grid-carousel', 'table', 'table-carousel', 'minimal', 'mini-grid', 'mini-grid-carousel', 'featured', 'carousel'];

	/**
	 * These defaults are used instead of 300 when the size is unset and for new areas; Numbers are 'px'
	 * @var int[]
	 */
	public static array $defaultSizeOptions = [
		"mini-grid"=> 290,
		"grid"	=>	300,
		"carousel" => "100%",
		"table-carousel" => "100%",
		'featured' => '100%',
		'featured-carousel' => '100%'
	];

	/**
	 * Mapping of carousel layouts to their regular counterpart(s)
	 * @var array
	 */
	public static array $carouselToRegularLayouts = [
		"mini-grid-carousel" 	=> ["mini-grid"],
		"minimal-carousel" 		=> ["minimal"],
		"grid-carousel" 		=> ['grid'],
		'table-carousel' 		=> ['table'],
		'wallpaper-carousel' 	=> ['wallpaper'],
		'featured-carousel' 	=> ['featured']
	];


	/**
	 * @var array|string[]
	 */
	public static array $disallowedOptions = [
		'area',
		'shouldBeEditing',
	];

	/**
	 * @var string
	 */
	public string $id = 'col1';

	/**
	 * Array of child areas
	 *
	 * @var Area[]
	 */
	public array $children = [];

	/**
	 * Widgets in this area
	 *
	 * @var array
	 */
	public array $widgets = [];

	/**
	 * @var string
	 */
	public string $wrapBehavior = 'wrap';

	/**
	 * @var array
	 */
	protected array $options = [];

	/**
	 * @var array|null
	 */
	public ?array $parentData = null;

	/**
	 * @var int
	 */
	public int $depth = 0;

	/**
	 * @param array $data
	 * @param string $id
	 * @param Area|null $parent
	 */
	public function __construct( array $data, string $id, ?Area $parent=null )
	{
		$this->id = $id;
		$this->wrapBehavior = $data['wrapBehavior'] ?? 'wrap';

		if( isset( $data['otherOptions'] ) )
		{
			foreach( $data['otherOptions'] as $k => $v )
			{
				$this->setOption( $k, $v );
			}
		}

		if( $parent !== null )
		{
			$this->parentData = $parent->toArray( false );
			$this->parentData['styles'] = $parent->styles();
			$this->depth = $parent->depth + 1;
		}

		if( isset( $data['widgets'] ) and count( $data['widgets'] ) )
		{
			/* If this has widgets, set the orientation for this level */
			if( isset( $data['orientation'] ) )
			{
				$this->setOption( 'orientation', $data['orientation'] );
			}

			foreach( $data['widgets'] as $key => $widget )
			{
				$this->addWidget( $widget );
			}
		}

		if( isset( $data['children'] ) )
		{
			foreach( $data['children'] as $child )
			{
				$this->children[] = new Area( $child, $this->id, $this );
			}
		}

		/* This will set the wrap behavior to a compatible behavior based on the parent styles */
		$this->getWrapBehavior();
	}

	/**
	 * Convert the area to an array
	 *
	 * @param bool $addChildren
	 * @param bool $includeWidgetConfig
	 * @return array
	 */
	public function toArray( bool $addChildren=true, bool $includeWidgetConfig=true ) : array
	{
		$return = [
			'wrapBehavior' => $this->wrapBehavior,
			'orientation' => $this->orientation(),
			'otherOptions' => $this->options,
			'children' => [],
			'widgets' => $this->widgets,
			'areaClasses' => $this->classes()
		];

		/* Strip out the widget configuration, typically used in saving */
		if( !$includeWidgetConfig )
		{
			foreach( $return['widgets'] as $k => $v )
			{
				if( isset( $v['configuration'] ) )
				{
					unset( $return['widgets'][ $k ]['configuration'] );
				}
			}
		}

		if( $addChildren )
		{
			foreach( $this->children as $child )
			{
				$return['children'][] = $child->toArray( $addChildren, $includeWidgetConfig );
			}
		}

		return $return;
	}

	/**
	 * @return string
	 */
	public function __toString() : string
	{
		return Theme::i()->getTemplate( 'global', 'core', 'front' )->widgetArea( $this );
	}

	/**
	 * @return bool
	 */
	public function isEmpty() : bool
	{
		return !count( $this->children ) AND !count( $this->widgets );
	}

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return void
	 */
	public function setOption( string $option, mixed $value ) : void
	{
		if( $option == 'wrapBehavior' )
		{
			$this->wrapBehavior = $value;
		}
		/* Exclude any extra data that might be sent here */
		elseif( !in_array( $option, static::$disallowedOptions ) )
		{
			$this->options[ $option ] = $value;
		}
	}

	/**
	 * Determine the orientation for this area
	 *
	 * @return string
	 */
	public function orientation() : string
	{
		switch( $this->id )
		{
			case static::AREA_SIDEBAR:
				return 'vertical';

			case static::AREA_HEADER:
			case static::AREA_GLOBAL_FOOTER:
			case static::AREA_FOOTER:
				return 'horizontal';
		}

		if( $this->parentData === null )
		{
			return 'vertical';
		}

		if( isset( $this->options['orientation'] ) )
		{
			return $this->options['orientation'];
		}

		return $this->wrapBehavior == 'stack' ? 'vertical' : 'horizontal';
	}

	/**
	 * Determine the gap size for this area
	 *
	 * @return int
	 */
	public function gapSize() : int
	{
		return ( isset( $this->options['gapSize'] ) and is_numeric( $this->options['gapSize'] ) ) ? $this->options['gapSize'] : 20;
	}

	/**
	 * Check whether this area has a valid parent
	 *
	 * @return bool
	 */
	public function isRoot() : bool
	{
		return !isset( $this->parentData['wrapBehavior'] );
	}

	/**
	 * Check if this area is just a wrapper for a widget
	 *
	 * @return bool
	 */
	public function isWidget() : bool
	{
		return count( $this->widgets ) > 0;
	}

	/**
	 * Check if this area is neither the leaf nor the root
	 *
	 * @return bool
	 */
	public function areaIsInMiddle() : bool
	{
		return !$this->isRoot() and !$this->isWidget();
	}

	/**
	 * Whether we should treat this area as an "area widget". This means that it's a wrapper for 1 widget and the widget supports different layouts
	 *
	 * @return bool
	 */
	public function isAreaWidget() : bool
	{
		$considerArea = null;
		if ( $considerArea === null )
		{
			$considerArea = false;
			if ( $this->areaIsInMiddle() )
			{
				$considerArea = true;
			}
			else if ( $this->isWidget() and !$this->isRoot() )
			{
				try
				{
					$config = array_values( $this->widgets )[0];
					$widget = Widget::load( Application::load( $config['app'] ), $config['key'], $config['unique'], layout: $config['layout'] );
					$considerArea = $widget->isCustomizableWidget();
				}
				catch( OutOfRangeException ){}
			}
		}

		return $considerArea;
	}

	/**
	 * @return string
	 */
	public function getWrapBehavior() : string
	{
		if ( empty( $this->wrapBehavior ) )
		{
			$this->wrapBehavior = 'wrap';
		}

		if ( isset( $this->parentData['wrapBehavior'] ) and $this->parentData['wrapBehavior'] === 'carousel' and array_key_exists( $this->wrapBehavior, Area::$carouselToRegularLayouts ) )
		{
			foreach ( Area::$carouselToRegularLayouts[$this->wrapBehavior] as $possibility )
			{
				//todo we may need to load the widget's allowed layouts and filter, but for now let's just set it to the first non-carousel and break;
				$this->wrapBehavior = $possibility;
				break;
			}
		}

		return $this->wrapBehavior;
	}

	/**
	 * CSS classes that should be added to the area
	 *
	 * @return array
	 */
	public function classes() : array
	{
		$classList = [];

		if ( !$this->isWidget() and $this->isAreaWidget() )
		{
			$classList[] = 'cWidgetContainer--' . $this->getWrapBehavior();
		}

		if ( $this->areaIsInMiddle() )
		{
			$classList[] = 'cWidgetContainer--isGroup';
		}
		else if ( $this->isRoot() )
		{
			$classList[] = 'cWidgetContainer--main';
		}
		else if ( $this->isWidget() )
		{
			$classList[] = 'cWidgetContainer--isWidget';

			/* Are we hiding images? */
			if( isset( $this->options['showImages'] ) and !$this->options['showImages'] )
			{
				$classList[] = 'cWidgetContainer--noImages';
			}
			/* Grab the configuration from the first widget in this area.
			There should be only one widget at this point.
			We use this to get the responsive classes so that we can show//hide
			on the appropriate devices */
			$config = null;
			foreach( $this->widgets as $widget )
			{
				$config = $widget['configuration'] ?? Widget::getConfiguration( $widget['unique'] );
				if( isset( $config['devices_to_show'] ) )
				{
					foreach( array_diff( array( 'Phone', 'Tablet', 'Desktop' ), $config['devices_to_show'] ) as $device )
					{
						$classList[] = 'ipsResponsive_hide' . $device;
					}
				}
				break;
			}
		}

		return $classList;
	}

	/**
	 * @return array
	 */
	public function dataAttributes() : array
	{
		$return = [];
		$return['widget-layout'] = $this->getWrapBehavior();
		foreach( $this->options as $k => $v )
		{
			$k = strtolower( preg_replace( '([A-Z])', '-$0', $k ) );
			if ( $k === 'layout' )
			{
				continue;
			}
			$return[ 'widget-' . $k ] = $v;
		}

		if( in_array( $this->id, static::$reservedAreas ) )
		{
			$return['restrict-nesting'] = true;
		}


		return $return;
	}

	/**
	 * @return array
	 */
	public function styles() : array
	{
		if ( !$this->isAreaWidget() )
		{
			return [];
		}

		// make sure we determine the wrap behavior
		$this->getWrapBehavior();
		$varKey = $this->isWidget() ? 'widget' : 'block';

		$styles = [
			"--i-{$varKey}--gap" => ( ( $this->wrapBehavior === "carousel" or ( $varKey == "widget" and in_array( $this->wrapBehavior, Area::$noGapLayouts ) ) ) ? '0' : $this->gapSize() ) . 'px',
			"--i-{$varKey}--size" => $this->options['minSize'] ?? 300,
			"--i-{$varKey}--padding-block" => $this->options['paddingBlock'] ?? 0,
			"--i-{$varKey}--padding-inline" => $this->options['paddingInline'] ?? 0,
		];

		if ( isset( $this->options['fullWidthItems'] ) and $this->options['fullWidthItems'] and str_ends_with( $this->wrapBehavior, 'carousel' ) )
		{
			$styles["--i-{$varKey}--size"] = "100%";
		}
		else
		{
			if ( !in_array( $this->wrapBehavior, Area::$noSizeLayouts ) )
			{
				$styles["--i-{$varKey}--size"] = $this->options['minSize'] ?? Area::$defaultSizeOptions[ $this->wrapBehavior ] ?? 300;
			}
			else if ( array_key_exists( $this->wrapBehavior, Area::$defaultSizeOptions ) )
			{
				$styles["--i-{$varKey}--size"] = Area::$defaultSizeOptions[ $this->wrapBehavior ];
			}

			if ( array_key_exists( "--i-{$varKey}--size", $styles ) and $styles["--i-{$varKey}--size"] != 0 and is_numeric( $styles["--i-{$varKey}--size"] ) )
			{
				$styles["--i-{$varKey}--size"] .= "px";
			}
		}

		return $styles;
	}

	/**
	 * Create a new area
	 *
	 * @param string $id
	 * @param array $widgets
	 * @param array $options
	 * @return static
	 */
	public static function create( string $id, array $widgets=array(), array $options=array() ) : static
	{
		$obj = new static( [], $id );

		/* If this is a reaserved area, we don't allow nesting, so put the widgets straight in here */
		if( in_array( $id, static::$reservedAreas ) )
		{
			foreach( $options as $option => $value )
			{
				$obj->setOption( $option, $value );
			}

			foreach( $widgets as $widget )
			{
				$obj->addWidget( $widget );
			}
		}
		else
		{
			$child = new static( [
				'otherOptions' => [
					'gapSize' => 20
				]
			], $id, $obj );

			/* Set any custom options passed in */
			foreach( $options as $option => $value )
			{
				$child->setOption( $option, $value );
			}

			/* Add them individually so that they get cleaned up and defined properly */
			foreach( $widgets as $widget )
			{
				$child->addWidget( $widget );
			}

			$obj->addChild( $child );
		}

		return $obj;
	}

	/**
	 * Add a child area
	 *
	 * @param Area|null $area
	 * @return void
	 */
	public function addChild( ?Area $area=null ) : void
	{
		if( $area === null )
		{
			$this->children[] = new Area( [], $this->id, $this );
		}
		else
		{
			$this->children[] = $area;
		}
	}

	/**
	 * Add a widget to this area
	 *
	 * @param array $block
	 * @return Area
	 */
	public function addWidget( array $block ) : Area
	{
		foreach( [ 'area', 'areaId', 'contentRaw' ] as $field )
		{
			if( isset( $block[ $field ] ) )
			{
				unset( $block[ $field ] );
			}
		}

		if ( isset( $block['blockID'] ) )
		{
			$components = explode( '_', $block['blockID'] );
			if ( count($components) !== 4 )
			{
				unset( $block['blockID'] );
			}
			else
			{
				$block['app'] = $components[1];
				$block['key'] = $components[2];
				$block['unique'] = $components[3];
			}
		}

		if ( !isset( $block['blockID'] ) )
		{
			$block['blockID'] = 'app';
			foreach ( [ 'app', 'key', 'unique' ] as $field )
			{
				if( isset( $block[ $field ] ) )
				{
					$block['blockID'] .= '_' . $block[ $field ];
				}
			}
		}

		/* Make sure that the widget actually exists, otherwise ignore it.
		This can be an issue if a widget was added and now no longer exists in the system. */
		try
		{
			if( !Widget::isValidWidget( Application::load( $block['app'] ), $block['key'] ) )
			{
				return $this;
			}
		}
		catch( Exception )
		{
			return $this;
		}

		/* Can this widget be dropped into this area? */
		/* @var Widget $widgetClass */
		$widgetClass = 'IPS\\' . $block['app'] . '\\widgets\\' . $block['key'];
		if( class_exists( $widgetClass ) and in_array( $this->id, $widgetClass::$disallowedAreas ) )
		{
			return $this;
		}

		/* Load configuration */
		if ( empty( $block['configuration'] ) or !is_array( $block['configuration'] ) )
		{
			$block['configuration'] = [];
		}
		$block['configuration'] = array_merge( Widget::getConfiguration( $block['unique'] ), $block['configuration'] );

		if( !array_key_exists( 'config', $block ) )
		{
			$block['config'] = !empty( $block['configuration'] );
		}

		if( !isset( $block['title'] ) )
		{
			try
			{
				$block['title'] = ( Lang::defaultLanguage() ? Lang::load( Lang::defaultLanguage() ) : Member::loggedIn()->language() )->get( "block_" . $block['key'] );
			}
			catch ( Exception )
			{
				if( isset( $block['key'] ) )
				{
					$block['title'] = $block['key'];
				}
			}
		}

		if( !isset( $block['unique'] ) )
		{
			$block['unique'] = mt_rand();
		}

		/* If this has children and no widgets, then go one level lower */
		if( !count( $this->widgets ) AND count( $this->children ) )
		{
			foreach( $this->children as $child )
			{
				if ( isset( $child->getAllWidgets()[$block['unique']] ) )
				{
					return $child->addWidget( $block );
				}
			}

			return $this->children[0]->addWidget( $block );
		}

		/* Set layout and orientation */
		$block['layout'] = $this->wrapBehavior;
		$block['orientation'] = $this->orientation();

		/* If the widget already exists, update it */
		if( !$this->replaceWidget( $block ) )
		{
			$this->widgets[ $block['unique'] ] = $block;
		}
		return $this;
	}

	/**
	 * If the widget exists in this area, update it
	 *
	 * @param array $block
	 * @return bool
	 */
	public function replaceWidget( array $block ) : bool
	{
		$index = $this->findWidgetById( $block['unique'] );
		if( $index !== null )
		{
			foreach( [ 'configuration', 'restrict', 'app', 'key' ] as $field )
			{
				if( isset( $block[ $field ] ) )
				{
					$this->widgets[ $index ][ $field ] = $block[ $field ];
				}
			}
			return true;
		}

		/* Check if any child area already has this widget.
		The page builder works its way up the tree and saves each level,
		so there are multiple save calls each time a widget is modified.  */
		foreach( $this->children as $child )
		{
			if( $child->replaceWidget( $block ) )
			{
				return true;
			}
		}

		/* the widget is not here */
		return false;
	}

	/**
	 * Remove a widget from an area
	 *
	 * @param string $widgetId
	 * @return void
	 */
	public function removeWidget( string $widgetId ) : void
	{
		$index = $this->findWidgetById( $widgetId );
		if( $index !== null )
		{
			unset( $this->widgets[ $index ] );
		}
		else
		{
			foreach( $this->children as $child )
			{
				$child->removeWidget( $widgetId );
			}
		}
	}

	/**
	 * Find a widget in the array and return the index
	 *
	 * @param string $widgetId
	 * @return string|null
	 */
	protected function findWidgetById( string $widgetId ) : ?string
	{
		if( empty( $this->widgets ) )
		{
			return null;
		}

		foreach( $this->widgets as $index => $widget )
		{
			if ( empty( $widget['unique'] ) and isset( $widget['blockID'] ) )
			{
				$widget['unique'] = substr( $widget['blockID'], strrpos( $widget['blockID'], '_' ) + 1 );
			}

			if( $widget['unique'] == $widgetId )
			{
				return $index;
			}
		}

		return null;
	}

	/**
	 * Recursively load all widgets in the tree
	 *
	 * @return array
	 */
	public function getAllWidgets() : array
	{
		$widgets = [];
		foreach( $this->widgets as $widget )
		{
			$widget['configuration'] = $widget['configuration'] ?? Widget::getConfiguration( $widget['unique'] );
			$widget['orientation'] = $this->orientation();
			$widgets[ $widget['unique'] ] = $widget;
		}

		foreach( $this->children as $child )
		{
			foreach( $child->getAllWidgets() as $childWidget )
			{
				$widgets[ $childWidget['unique'] ] = $childWidget;
			}
		}
		return $widgets;
	}

	/**
	 * Check recursively if we have at least one widget present
	 *
	 * @return bool
	 */
	public function hasWidgets() : bool
	{
		if( count( $this->widgets ) )
		{
			return true;
		}

		foreach( $this->children as $child )
		{
			return $child->hasWidgets();
		}

		return false;
	}

	/**
	 * Total number of widgets that will be rendered in this area
	 *
	 * @return int
	 */
	public function totalVisibleWidgets() : int
	{
		$total = 0;

		if( $this->isWidget() and !empty( $this->getWidgetContent() ) )
		{
			$total++;
		}
		else
		{
			foreach( $this->children as $child )
			{
				$total += $child->totalVisibleWidgets();
			}
		}

		return $total;
	}

	/**
	 * @var string|null
	 */
	protected ?string $_widgetOutput = null;

	/**
	 * Render the widget output
	 *
	 * @return string|null
	 */
	public function getWidgetContent() : ?string
	{
		if( $this->isWidget() and $this->_widgetOutput === null )
		{
			foreach( $this->widgets as $widget )
			{
				$this->_widgetOutput = (string) ( Widget::createWidgetFromStoredData( $widget ) );
			}
		}

		return $this->_widgetOutput;
	}

	/**
	 * Clean the tree data and check for duplicate widgets
	 *
	 * @param array $tree
	 * @param array $widgetIds
	 * @return array
	 */
	public static function cleanTreeData( array $tree, array &$widgetIds=array() ) : array
	{
		/* Remove any duplicate widgets */
		if ( isset( $tree['widgets'] ) )
		{
			foreach( $tree['widgets'] as $k => $v )
			{
				$components = explode( '_', $v['blockID'] );

				/* Remove the widget if there's no unique identifier */
				if ( count( $components ) !== 4 )
				{
					unset( $tree['widgets'][$k] );
					continue;
				}

				/* If this is a duplicate widget, remove it from the array */
				$widgetKey = $components[3];
				if ( in_array( $widgetKey, $widgetIds ) )
				{
					unset( $tree['widgets'][ $k ] );
				}
				else
				{
					$widgetIds[] = $widgetKey;
				}
			}
		}

		if ( isset( $tree['otherOptions']['shouldBeEditing'] ) )
		{
			unset( $tree['otherOptions']['shouldBeEditing'] );
		}

		/* Loop through children and clean them */
		if ( isset( $tree['children'] ) )
		{
			foreach( $tree['children'] as $index => $child )
			{
				$child = static::cleanTreeData( $child, $widgetIds );

				/* If the child has no content, remove it */
				if( !count( $child['children'] ) AND !count( $child['widgets'] ) )
				{
					unset( $tree['children'][ $index ] );
				}
			}
		}

		return $tree;
	}


	/**
	 * Get all the areas from the database on a page
	 *
	 * @param string $app
	 * @param string $module
	 * @param string $controller
	 * @param ?string $area
	 *
	 * @return static[]
	 */
	public static function getAreasFromDatabase( string $app, string $module, string $controller, ?string $area ) : array
	{
		$areas = array();
		$where = [
			[ 'app=?', $app ],
			[ 'module=?', $module ],
			[ 'controller=?', $controller ]
		];

		if ( is_string( $area ) and $area )
		{
			$where[] = [ 'area=?', $area ];
		}

		foreach( Db::i()->select( '*', 'core_widget_areas', $where ) as $row )
		{
			if( $row['tree'] )
			{
				$areas[$row['area']] = new static( json_decode( $row['tree'], true ), $row['area'] );
			}
			elseif( $row['widgets'] )
			{
				$areas[$row['area']] = static::create( $row['area'], json_decode( $row['widgets'], true ) );
			}
		}

		return $areas;
	}
}