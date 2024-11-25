<?php
/**
 * @brief		Sidebar Widget Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Nov 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use ErrorException;
use Exception;
use IPS\cms\Widget as CmsWidget;
use IPS\Data\Store;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Trbl;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member\Club;
use IPS\Platform\Bridge;
use IPS\Widget\Area;
use IPS\Widget\Builder;
use IPS\Widget\Customizable;
use OutOfRangeException;
use Throwable;
use UnderflowException;
use function count;
use function defined;
use function file_put_contents;
use function func_get_args;
use function get_class;
use function in_array;
use function is_array;
use function is_countable;
use function is_numeric;
use function is_string;
use function json_decode;
use function mb_substr;
use function strlen;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sidebar Widget Class
 */
abstract class Widget
{
	/**
	 * @brief	The number of widgets that can be expired per request (to prevent loads of rebuilds on a single request which would slow the page down). Deliberately hardcoded.
	 */
	protected static int $expirePerRequest = 1;
	
	/**
	 * @brief	Configuration
	 */
	public array $configuration = array();
	
	/**
	 * @brief	Access. Array of allowed apps that execute the widgets. Null for no restriction
	 */
	protected mixed $access = null;
	
	/**
	 * @brief	Custom template callback
	 */
	public mixed $template = null;
	
	/**
	 * @brief	Orientation
	 */
	protected ?string $orientation = null;

	/**
	 * @brief	Layout currently in use, calculated by the widget area
	 */
	public string $currentLayout = '';

	/**
	 * @var string
	 */
	public string $defaultLayout = '';
	
	/**
	 * @brief	Menu style
	 */
	public string $menuStyle = 'menu';

	/**
	 * @var array
	 */
	public array $layouts = array();
	
	/**
	 * @brief	Allow block to be reused
	 */
	public bool $allowReuse = false;

	/**
	 * @brief	Show the padding controls in the page builder
	 */
	public bool $allowCustomPadding = false;

	/**
	 * @brief	Unique key for this widget
	 */
	public string|int|null $uniqueKey = NULL;
	
	/**
	 * @brief	Prevent caching for this block
	 */
	public bool $neverCache = FALSE;

	/**
	 * @brief	Error language string key shown after the configuration
	 */
	public string $errorMessage = 'widget_blank_or_no_context';

	/**
	 * @brief	Set to true if this widget must be the only one in its area
	 */
	public bool $soloWidget = false;

	/**
	 * @brief	Set to false if this widget should be hidden from the block list
	 * 			in the Page Editor
	 */
	public static bool $showInBlockList = true;

	/**
	 * @brief	If a widget should not be dropped into a particular area (e.g. a database widget in the header), list those areas here
	 */
	public static array $disallowedAreas = [];

	/**
	 * These layouts are the default allowed "feed" layouts
	 *
	 * @var string[] $defaultFeedLayouts
	 */
	public static array $defaultFeedLayouts = array(
		"featured",
		"featured-carousel",
		"grid",
		"grid-carousel",
		"minimal",
		'minimal-carousel',
		"mini-grid",
		"mini-grid-carousel",
		"table",
		"table-carousel",
		"wallpaper",
		"wallpaper-carousel"
	);
	
	/**
	 * Constructor
	 *
	 * @param String $uniqueKey			Unique key for this specific instance
	 * @param	array				$configuration		Widget custom configuration
	 * @param array|string|null $access				Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation		Horizontal or vertical orientation
	 * @param string $layout		Current layout in use
	 * @return	void
	 */
	public function __construct( string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='' )
	{
		$this->configuration = $configuration;
		$this->orientation = $orientation;

		if( empty( $layout ) )
		{
			$layout = $this->defaultLayout ?? 'table';
		}

		$this->currentLayout = $layout;
		
		if ( $access !== null and is_string( $access ) )
		{
			$test = json_decode( $access, true );
			
			if ( is_array( $test ) AND count( $test ) )
			{
				$this->access = $test;
			}
		}
		else if ( is_array( $access ) AND count( $access ) )
		{
			$this->access = $access;
		}

		$this->init();

		$this->uniqueKey  = ( empty( $uniqueKey ) ) ? ( $this->key ?: mt_rand() ) : $uniqueKey;

		if ( !$this->hasConfiguration() )
		{
			$this->errorMessage = 'widget_blank_or_no_context_no_config';
		}
	}

	/**
	 * Initialise this widget
	 *
	 * @return void
	 * @throws ErrorException
	 */
	public function init(): void
	{
		if ( $this->app )
		{
			$this->template( array( Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
		}
	}


	/**
	 * Is this a block builder widget?
	 *
	 * @return bool
	 */
	public function isBuilderWidget() : bool
	{
		return in_array( Builder::class, class_implements( $this ) );
	}

	/**
	 * Can this widget be customized?
	 * @return bool
	 */
	public function isCustomizableWidget() : bool
	{
		return in_array( Customizable::class, class_implements( $this ) );
	}
	
	/**
	 * Constructor
	 *
	 * @param array|string $app	Application key (core,cms,gallery, etc)
	 * @return	bool
	 */
	public function isExecutableByApp( array|string $app ): bool
	{
        if ( ! Bridge::i()->pagesAllowDatabaseAccess() )
        {
            $databaseWidgets = [ 'Categories', 'Database', 'DatabaseFilters', 'RecordFeed' ];
            if ( $this->app === 'cms' and in_array( $this->key, $databaseWidgets ) )
            {
                return false;
            }
        }

		if ( $this->access === null or ( is_array( $this->access ) and ! count($this->access ) ) )
		{
			return true;
		}
		else
		{
			if ( is_string( $app ) )
			{
				$checkApps = array( $app );
			}
			else
			{
				$checkApps = $app;
			}
			
			foreach( $checkApps as $check )
			{
				if ( in_array( $check, $this->access ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Fetch the application for this widget
	 *
	 * @return    Application
	 */
	public function application(): Application
	{
		return Application::load( $this->app );
	}

	/**
	 * Fetch the title for this widget
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack( 'block_' . $this->key );
	}
	
	/**
	 * Fetch the description for this widget
	 *
	 * @return	string
	 */
	public function description(): string
	{
		return Member::loggedIn()->language()->addToStack( 'block_' . $this->key . '_desc' );
	}
	
	/**
	 * Set the template for this widget
	 *
	 * @param	mixed		$callback		Function to use for template callback
	 * @return	void
	 */
	public function template( mixed $callback ): void
	{
		$this->template = $callback;
	}
	
	/**
	 * Get Template Location
	 * Returns the template app/location/group/name params
	 * @return array
	 */
	public function getTemplateLocation(): array
	{
		$class = get_class( $this->template[0] );
		if ( $class === 'IPS\Theme\Dev\Template' )
		{
			$params = $this->template[0]->getParams();
		}
		else
		{
			$params = array( 'app' => $this->template[0]->template->app, 'location' => $this->template[0]->template->templateLocation, 'group' => $this->template[0]->template->templateName );
		}

		return array_merge( $params, array( 'name' => $this->template[1] ) );
	}

	/**
	 * Return any extra classes that should be added to the widget wrapper
	 *
	 * @return array
	 */
	public function getWrapperClasses() : array
	{
		$classes = [];

		if( $this->isBuilderWidget() )
		{
			$classes[] = 'ipsWidget--transparent';
		}

		return $classes;
	}

	/**
	 * Return all data attributes that will be placed on the widget container
	 * for block management
	 *
	 * @return array
	 */
	public function dataAttributes() : array
	{
		$return = [
			'blocktitle' => $this->title(),
			'blockID' => 'app_' . $this->app . '_' . $this->key . '_' . $this->uniqueKey,
			'blockErrorMessage' => Member::loggedIn()->language()->addToStack( $this->errorMessage ),
			'menuStyle' => $this->menuStyle ?? 'menu'
		];

		if( !empty( $this->allowReuse ) )
		{
			$return['allowReuse'] = true;
		}

		if( $this->hasConfiguration() )
		{
			$return['blockConfig'] = true;
		}

		if( $this->isBuilderWidget() )
		{
			$return['blockBuilder'] = true;
		}

		if( $this->soloWidget )
		{
			$return['widget-is-solo'] = true;
		}

		if( $this->isCustomizableWidget() )
		{
			$return['widget-customizable'] = true;
			$return['widget-layouts'] = implode( ",", $this->layouts );
		}

		if ( !empty( $this->getSearchTerms() ) )
		{
			$return['searchterms'] = implode( ',', $this->getSearchTerms() );
		}

		if( $this->allowCustomPadding )
		{
			$return['widget-paddingallowed']  = true;
		}

		if( count( static::$disallowedAreas ) )
		{
			$return['widget-disallowed-areas'] = implode( ",", static::$disallowedAreas );
		}

		return $return;
	}

	/**
	 * Get the current layout, or the first supported layout is not supported
	 *
	 * @return string
	 */
	public function getCurrentLayout() : string
	{
		if ( empty( $this->currentLayout ) or ( $this->isCustomizableWidget() and !in_array( $this->currentLayout, $this->getSupportedLayouts() ) ) )
		{
			$this->currentLayout = $this->defaultLayout ?: $this->getSupportedLayouts()[0];
		}

		return $this->currentLayout;
	}

	/** @var array */
	protected static array $baseWidgetConfig = [];
	protected static array $widgetConfigs = [];

	/**
	 * Get config data from an app, caching all to avoid loads of queries for widget heavy pages
	 *
	 * @param string $app
	 * @param string $key
	 * @return array
	 */
	protected static function getWidgetDataByKeyAndApp( string $app, string $key ): array
	{
		if ( ! count( static::$widgetConfigs ) )
		{
			foreach( Db::i()->select( '*', 'core_widgets' ) as $widget )
			{
				static::$widgetConfigs[ $widget['app'] ][ $widget['key'] ] = $widget;
			}
		}

		if ( isset( static::$widgetConfigs[ $app ][ $key ] ) )
		{
			return static::$widgetConfigs[ $app ][ $key ];
		}

		/* Still here? Oops */
		throw new UnderflowException;
	}

	/**
	 * Get the search terms allowed for this widget
	 *
	 * @return string[]
	 */
	public function getSearchTerms() : array
	{
		if ( !isset( static::$baseWidgetConfig[ $this::class ]['searchterms'] ) )
		{
			if ( !isset( static::$baseWidgetConfig[ $this::class ]['searchterms'] ) )
			{
				$class = trim( $this::class, '\\' );
				$components = explode( '\\', $class );
				if ( @$components[0] === 'IPS' and @$components[1] and $components[1] === strtolower( $components[1] ) and @$components[2] === 'widgets' )
				{
					$app = $components[1];
					$widget = $components[3];
					try
					{
						static::$baseWidgetConfig[ $this::class ] = static::getWidgetDataByKeyAndApp( $app, $widget );
					}
					catch ( UnderflowException )
					{
						return [];
					}
				}
			}
		}
		return is_string( @static::$baseWidgetConfig[ $this::class ][ 'searchterms' ] ) ? explode( ',', static::$baseWidgetConfig[ $this::class ][ 'searchterms' ] ) : [];
	}

	/**
	 * @return string[]
	 */
	public function getSupportedLayouts() : array
	{
		if ( !isset( static::$baseWidgetConfig[ $this::class ]['layouts'] ) OR !is_array( static::$baseWidgetConfig[ $this::class ]['layouts'] ) )
		{
			$layouts = ['table'];
			if ( $this->isCustomizableWidget() )
			{
				$class = trim( $this::class, '\\' );
				$components = explode( '\\', $class );
				if ( @$components[0] === 'IPS' and @$components[1] and $components[1] === strtolower( $components[1] ) and @$components[2] === 'widgets' )
				{
					$app = $components[1];
					$widget = $components[3];
					try
					{
						$baseConfig = static::getWidgetDataByKeyAndApp( $app, $widget );
					}
					catch ( UnderflowException )
					{
						$baseConfig = [];
					}

					if ( !isset( $baseConfig['layouts'] ) or empty( $baseConfig['layouts'] ) )
					{
						$layouts = Widget::$defaultFeedLayouts;
					}
					elseif ( $baseConfig['layouts'] === '*' )
					{
						$layouts = Area::$allowedWrapBehaviors;
					}
					else
					{
						$layouts = array_intersect( Area::$allowedWrapBehaviors, explode( ',', $baseConfig['layouts'] ) );
					}

					if( isset( $baseConfig['default_layout'] ) and $baseConfig['default_layout'] )
					{
						array_unshift( $layouts, $baseConfig['default_layout'] );
					}

					/* Make sure base layouts all have a carousel equivalent */
					foreach ( Area::$carouselToRegularLayouts as $carouselLayout => $regularLayouts )
					{
						if ( in_array( $carouselLayout, $layouts ) )
						{
							continue;
						}

						foreach ( $regularLayouts as $regularLayout )
						{
							if ( in_array( $regularLayout, $layouts ) )
							{
								$layouts[] = $carouselLayout;
								continue 2;
							}
						}
					}

					static::$baseWidgetConfig[ $this::class ] = $baseConfig;
				}
			}

			static::$baseWidgetConfig[ $this::class ]['layouts'] = array_values( $layouts );
		}

		return static::$baseWidgetConfig[ $this::class ]['layouts'];
	}

	/**
	 * Get HTML using the template (language strings not parsed)
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$currentLayout = $this->getCurrentLayout();

		$args = func_get_args();
		$args[] = str_replace( '-carousel', '', $currentLayout );
		$args[] = str_ends_with( $currentLayout, "-carousel" );

		$template = $this->template;

		$output = $template( ...$args );

		if( $this->isBuilderWidget() )
		{
			$config = array();
			foreach( $this->configuration as $key => $value )
			{
				if ( mb_substr( $key, 0, 12 ) === 'widget_adv__' )
				{
					$config[ mb_substr( $key, 12 ) ] = $value;
				}
			}
			
			$config['class'] = 'app_' . $this->app . '_' . $this->key . '_' . $this->uniqueKey;
			$config['style'] = static::buildInlineStyles( $config );
			
			return Theme::i()->getTemplate( 'widgets', 'core' )->builderWrapper( $output, $config );
		}
		else
		{
			return $output;
		}
	}

	/**
	 * Build inline styles
	 *
	 * @param array $config
	 * @return array
	 */
	public static function buildInlineStyles( array $config ): array
	{
		$css = array();

		if ( ! empty( $config['padding'] ) and $config['padding'] === 'custom' )
		{
			$css['padding'] = $config['padding_custom'][0] . 'px ' .  $config['padding_custom'][1] . 'px ' . $config['padding_custom'][2] . 'px ' . $config['padding_custom'][3] . 'px';
		}
		
		if ( isset( $config['background_custom'] ) and $config['background_custom'] == 'transparent' )
		{
			$css['background-color'] = 'transparent';
		}
		elseif ( isset( $config['background_custom'] ) and $config['background_custom'] == 'image' and ! empty( $config['background_custom_image'] ) )
		{
			$css['background-image'] = 'url("' . File::get( 'core_Attachment', $config['background_custom_image'] )->url . '")';
			$css['background-size'] = 'cover';
			$css['background-repeat'] = 'no-repeat';
			$css['background-position'] = 'center';
			$css['background-color'] = 'transparent';
		}
		elseif ( isset( $config['background_custom'] ) and $config['background_custom'] == 'custom' and ! empty( $config['background'] ) )
		{
			$css['background-color'] = $config['background'];
		}
		
		if ( ! empty( $config['fontcolor'] ) )
		{
			if ( isset( $config['fontcolor_custom'] ) and $config['fontcolor_custom'] == 'custom' )
			{
				$css['color'] = $config['fontcolor'];
			}
		}
		
		if ( ! empty( $config['fontsize'] ) )
		{
			if ( $config['fontsize'] === 'custom' )
			{
				$css['font-size'] = $config['fontsize_custom'] . 'px';
			}
		}
		
		if ( ! empty( $config['font'] ) and $config['font'] !== 'inherit' )
		{
			$fontWeight = 400;
			
			if ( mb_substr( $config['font'], -6 ) === ' black' )
			{
				$fontWeight = 900;
				$config['font'] = mb_substr( $config['font'], 0, -6 );
			}
			
			$css['font-family'] = '"' . $config['font'] . '"';
			$css['font-weight'] = $fontWeight;
		}
		
		if ( ! empty( $config['fontalign'] ) )
		{
			$css['text-align'] = $config['fontalign'];
		}
		
		$return = array();
		
		foreach( $css as $name => $rule )
		{
			$return[ $name ] = $name . ":" . $rule . ";";
		}

		return $return;
	}
	
	/**
	 * Efficient way to see if a widget has configuration
	 *
	 * @return bool
	 */
	public function hasConfiguration(): bool
	{
		return method_exists( $this, 'configuration' );
	}
	
	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete()
	{
		/* Does nothing by default but can be overridden */
	}
	
	/**
	 * Factory Method
	 *
	 * @param Application $parent				Widget application
	 * @param String $widgetKey			Widget key used to load class
	 * @param String $uniqueKey			Unique key for this specific instance
	 * @param array $configuration		Current configuration
	 * @param array|string|null $access				Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation		Horizontal or vertical orientation
	 * @param string	$layout		Current layout in use
	 * @return    Widget
	 * @throws	OutOfRangeException
	 */
	public static function load( Application $parent, string $widgetKey, string $uniqueKey, array $configuration=array(), array|string $access=null, string $orientation=null, string $layout='' ): Widget
	{
		/* If our parent is not enabled, do not attempt to use this widget */
		if ( !$parent->enabled )
		{
			throw new OutOfRangeException;
		}

		$class = '\IPS\\' . $parent->directory . '\widgets\\' . $widgetKey;
		$baseWidgetKey = $widgetKey;
		if ( class_exists( $class ) and in_array( "IPS\\Widget\\Polymorphic", class_implements( $class ) ) )
		{
			$baseWidgetKey = $class::getBaseKey();
		}

		/* Is this a valid widget? */
		if( !static::isValidWidget( $parent, $baseWidgetKey ) )
		{
			throw new OutOfRangeException;
		}
		
		/* Return */
		if ( class_exists( $class ) )
		{
			if ( ! empty( $configuration['widget_adv__custom'] ) )
			{
				Output::i()->headCss = Output::i()->headCss . "\n" . $configuration['widget_adv__custom'];
			}
		
			return new $class( $uniqueKey, $configuration, $access, $orientation, $layout );
		}
		
		throw new OutOfRangeException;
	}

	/**
	 * Make sure that the widget actually is supported by the app
	 *
	 * @param Application|string $app
	 * @param string $widgetKey
	 * @return bool
	 */
	public static function isValidWidget( Application|string $app, string $widgetKey ) : bool
	{
		/* Is this a valid widget? */
		try
		{
			$cache = Store::i()->widgets;
		}
		catch( OutOfRangeException )
		{
			static::appDefaults( $app );
			$cache = Store::i()->widgets;
		}

		$app = ( $app instanceof Application ) ? $app->directory : $app;

		$class = '\IPS\\' . $app . '\widgets\\' . $widgetKey;
		if ( class_exists( $class ) and in_array( "IPS\\Widget\\Polymorphic", class_implements( $class ) ) )
		{
			$widgetKey = $class::getBaseKey();
		}

		return array_key_exists( $widgetKey, $cache[ $app ] );
	}
	
	/**
	 * Widget Types
	 *
	 * @return	array
	 */
	public static function widgetTypes(): array
	{
		return array(
			'default'			=> '\IPS\Widget',
			'StaticCache'		=> '\IPS\Widget\StaticCache',
			'PermissionCache'	=> '\IPS\Widget\PermissionCache',
		);
	}
	
	/**
	 * Dev Table
	 *
	 * @param string $json				Path to JSON file
	 * @param Url $url				URL to page
	 * @param string $widgetDirectory	Directory where PHP files are stored
	 * @param string $subpackage			The value to use for the subpackage in the widget file's header
	 * @param string $namespace			The namespace for the widget file
	 * @param int|string $appKeyOrPluginId	If widget belongs to an application, it's key, or if a plugin, it's ID
	 * @return	string
	 */
	public static function devTable( string $json, Url $url, string $widgetDirectory, string $subpackage, string $namespace, int|string $appKeyOrPluginId ): string
	{
		if ( !file_exists( $json ) )
		{
			file_put_contents( $json, json_encode( array() ) );
		}
	
		switch ( Request::i()->widgetTable )
		{
			case 'form':

				/* Build the list of layouts that are allowed on a widget */
				$layouts = [];
				foreach( Area::$widgetOnlyLayouts as $wrapBehavior )
				{
					if( !str_ends_with( $wrapBehavior, '-carousel' ) )
					{
						$layouts[ $wrapBehavior ] = 'core_pagebuilder_wrap__' . $wrapBehavior;
					}
				}

				$current = NULL;
				if ( isset( Request::i()->key ) )
				{
					$widgets = json_decode( file_get_contents( $json ), TRUE );
					if ( array_key_exists( Request::i()->key, $widgets ) )
					{
						$current = array(
								'dev_widget_key'			=> Request::i()->key,
								'dev_widget_class'			=> $widgets[ Request::i()->key ]['class'],
								'dev_widget_restrict'		=> $widgets[ Request::i()->key ]['restrict'],
								'dev_widget_default_area'	=> isset($widgets[ Request::i()->key ]['default_area']) ? $widgets[ Request::i()->key ]['default_area'] : NULL,
								'dev_widget_allow_reuse'	=> isset($widgets[ Request::i()->key ]['allow_reuse'])  ? $widgets[ Request::i()->key ]['allow_reuse']  : 0,
								'dev_widget_menu_style'		=> isset($widgets[ Request::i()->key ]['menu_style'])   ? $widgets[ Request::i()->key ]['menu_style']   : 'menu',
								'dev_widget_layouts'		=> $widgets[ Request::i()->key ]['layouts'] ?? implode( ",", $layouts ),
								'dev_widget_searchterms'	=> is_string( @$widgets[ Request::i()->key ]['searchterms'] ) ? explode( ',', $widgets[ Request::i()->key ]['searchterms'] ) : [],
						        'dev_widget_embeddable'     => isset($widgets[ Request::i()->key ]['embeddable'])   ? $widgets[ Request::i()->key ]['embeddable']   : 1,
								'dev_widget_allow_padding'		=> isset( $widgets[ Request::i()->key ]['padding'] ) ? $widgets[ Request::i()->key]['padding'] : 0,
								'dev_widget_default_layout' => $widgets[ Request::i()->key ]['default_layout'] ?? null
						);
					}
					unset( $widgets );
				}
	
				$form = new Form;
				$form->add( new Text( 'dev_widget_key', $current ? $current['dev_widget_key'] : NULL, TRUE, array( 'maxLength' => 255, 'regex' => '/^[a-z][a-z0-9]*$/i' ), function( $val ) use ( $current )
				{
                    if( mb_strpos( $val, "_" ) !== FALSE )
                    {
                        throw new DomainException( 'dev_widget_key_err_alpha' );
                    }

					$where = array( array( '`key`=?', $val ) );
					if ( isset( $current['dev_widget_key'] ) )
					{
						$where[] = array( '`key`<>?', $current['dev_widget_key'] );
					}
						
					if ( Db::i()->select( 'count(*)', 'core_widgets', $where )->first() )
					{
                        throw new DomainException( 'dev_widget_key_err' );
					}
				} ) );
				
				$classes = array();
				foreach ( static::widgetTypes() as $key => $class )
				{
					$classes[ $class ] = $class;
					Member::loggedIn()->language()->words[ $class . '_desc' ] = Member::loggedIn()->language()->get( 'widget_class_' . $key );
				}
				$form->add( new Radio( 'dev_widget_class', ( is_array( $current ) ? $current['dev_widget_class'] : NULL ), TRUE, array( 'options' => $classes ) ) );
				
				$form->add( new CheckboxSet( 'dev_widget_restrict', ( is_array( $current ) and !empty( $current['dev_widget_restrict'] ) ) ? $current['dev_widget_restrict'] :  array( 'sidebar', 'cms' ), FALSE, array(
					'options' => array(
						'sidebar'	=> Member::loggedIn()->language()->addToStack('dev_widget_restrict_sidebar'),
						'cms'       => Member::loggedIn()->language()->addToStack('dev_widget_restrict_cms'),
					),
					'multiple' => true ) ) );
				
				$form->add( new Radio( 'dev_widget_default_area', ( is_array( $current ) ? ( $current['dev_widget_default_area'] ?: 'none' ) : 'none' ), FALSE, array(
						'options' => array(
								'none'		=> Member::loggedIn()->language()->addToStack('none'),
								'sidebar'	=> Member::loggedIn()->language()->addToStack('dev_widget_default_area_sidebar'),
								'header'	=> Member::loggedIn()->language()->addToStack('dev_widget_default_area_header'),
								'footer'	=> Member::loggedIn()->language()->addToStack('dev_widget_default_area_footer'),
								
						),
						'multiple' => false ) ) );
				
				$form->add( new Radio( 'dev_widget_menu_style', ( is_array( $current ) ? $current['dev_widget_menu_style'] : 'menu' ), FALSE, array(
					'options' => array(
						'menu'	=> Member::loggedIn()->language()->addToStack('dev_widget_menu_style_menu'),
						'modal'       => Member::loggedIn()->language()->addToStack('dev_widget_menu_style_modal'),
				) ) ) );


				$form->add( new Form\Stack( 'dev_widget_searchterms', $current['dev_widget_searchterms'] ?? null, false ) );

				$form->add( new CheckboxSet( 'dev_widget_layouts', ( is_array( $current ) AND $current['dev_widget_layouts'] ) ? explode( ",", $current['dev_widget_layouts'] ) : $layouts, false, array(
					'options' => $layouts,
					'noDefault' => true,
					'class' => 'widget_layouts__container'
				) ) );

				$form->add( new Select( 'dev_widget_default_layout', ( is_array( $current ) and $current['dev_widget_default_layout'] ) ? $current['dev_widget_default_layout'] : null, false, array(
					'options' => array( '' => '' ) + $layouts,
					'parse' => 'lang'
				) ) );
				
				$form->add( new YesNo( 'dev_widget_allow_reuse', ( is_array( $current ) ? $current['dev_widget_allow_reuse'] : 0 ) ) );

				$form->add( new YesNo( 'dev_widget_allow_padding', ( is_array( $current ) ? $current['dev_widget_allow_padding'] : 0 ) ) );

				/** @deprecated - This option will not be supported in a future version which is why it's hidden */
				$form->add( new YesNo( 'dev_widget_embeddable', ( is_array( $current ) ? $current['dev_widget_embeddable'] : 0 ), options: ["rowClasses" => ["ipsHide"]] ) );
				Member::loggedIn()->language()->words['dev_widget_embeddable'] = "(Deprecated) " . Member::loggedIn()->language()->get( "dev_widget_embeddable" );

				if ( $values = $form->values() )
				{
					/* Write PHP file */
					$widgetFile =  $widgetDirectory . "/{$values['dev_widget_key']}.php";
					if ( !file_exists( $widgetFile ) )
					{
						if ( !is_dir( $widgetDirectory ) )
						{
							mkdir( $widgetDirectory );
							chmod( $widgetDirectory, IPS_FOLDER_PERMISSION);
						}
	
						file_put_contents( $widgetFile, str_replace(
								array(
										'{key}',
										"{subpackage}\n",
										'{date}',
										'{namespace}',
										'{class}',
										'{appkey}',
								),
								array(
										$values['dev_widget_key'],
										( $subpackage != 'core' ) ? ( " * @subpackage\t" . $subpackage . "\n" ) : '',
										date( 'd M Y' ),
										$namespace,
										$values['dev_widget_class'],
										$appKeyOrPluginId
								),
								file_get_contents( ROOT_PATH . "/applications/core/data/defaults/Widget.txt" )
						) );
						chmod( $widgetFile, IPS_FILE_PERMISSION);
					}

					/* Figure out the layouts */
					$values['dev_widget_layouts'] = implode( ",", $values['dev_widget_layouts'] );
					$values['dev_widget_searchterms'] = implode( ",", $values['dev_widget_searchterms'] );

					/* Add to DB */
					$query = Db::i()->replace( 'core_widgets', array(
							'app'			=> $appKeyOrPluginId,
							'key'			=> $values['dev_widget_key'],
							'class'			=> $values['dev_widget_class'],
							'restrict'		=> ( ! count( $values['dev_widget_restrict'] ) ? FALSE : json_encode( array_values( $values['dev_widget_restrict'] ) ) ),
							'default_area'	=> ( $values['dev_widget_default_area'] === 'none' ) ? '' : $values['dev_widget_default_area'],
							'allow_reuse'	=> $values['dev_widget_allow_reuse'],
							'menu_style'    => $values['dev_widget_menu_style'],
							'layouts'		=> $values['dev_widget_layouts'],
							'searchterms'		=> $values['dev_widget_searchterms'],
					        'embeddable'    => $values['dev_widget_embeddable'],
							'padding'		=> $values['dev_widget_allow_padding'],
							'default_layout' => $values['dev_widget_default_layout']
					) );
					unset( Store::i()->widgets );
						
					/* Add to JSON file */
					$widgets = json_decode( file_get_contents( $json ), TRUE );
					$widgets[ $values['dev_widget_key'] ] = array(
						'class'    	   => $values['dev_widget_class'],
						'restrict' 	   => ( ! count( $values['dev_widget_restrict'] ) ? FALSE : array_values( $values['dev_widget_restrict'] ) ),
						'default_area' => ( $values['dev_widget_default_area'] === 'none' ) ? '' : $values['dev_widget_default_area'],
						'allow_reuse'  => $values['dev_widget_allow_reuse'],
						'menu_style'   => $values['dev_widget_menu_style'],
						'layouts'		=> $values['dev_widget_layouts'],
						'embeddable'   => $values['dev_widget_embeddable'],
						'padding'		=> $values['dev_widget_allow_padding'],
						'default_layout' => $values['dev_widget_default_layout'],
						'searchterms' => $values['dev_widget_searchterms']
					);
					
					Application::writeJson( $json, $widgets );
						
					/* Redirect */
					Output::i()->redirect( $url, 'saved' );
				}
	
				return $form;
	
			case 'delete':
				Session::i()->csrfCheck();

				$widgets = json_decode( file_get_contents( $json ), TRUE );
				if ( array_key_exists( Request::i()->key, $widgets ) )
				{
					unset( $widgets[ Request::i()->key ] );
					file_put_contents( $json, json_encode( $widgets, JSON_PRETTY_PRINT ) );
						
					if ( file_exists( $widgetDirectory . "/" . Request::i()->key . ".php" ) )
					{
						unlink( $widgetDirectory . "/" . Request::i()->key . ".php" );
					}
						
					Db::i()->delete( 'core_widgets', array( 'app=? AND `key`=?', $appKeyOrPluginId, Request::i()->key ) );
					unset( Store::i()->widgets );
				}
				Output::i()->redirect( $url, 'saved' );
					
			default:
	
				$data = array();
				foreach ( json_decode( file_get_contents( $json ), TRUE ) as $k => $json )
				{
					$data[ $k ] = array(
							'dev_widget_key'		=> $k,
							'dev_widget_class'		=> $json['class'],
							'dev_widget_restrict' 	=> $json['restrict'] === FALSE ? Member::loggedIn()->language()->addToStack('dev_widget_nowhere') : ( ( ( count( $json['restrict'] ) > 0 and count( $json['restrict'] ) !== 2 ) ? implode( ',', array_map( function($val ) { return Member::loggedIn()->language()->addToStack('dev_widget_restrict_'.$val); }, $json['restrict'] ) ) : Member::loggedIn()->language()->addToStack('everywhere') ) ),
							'dev_widget_area'		=> isset($json['default_area']) ? ( $json['default_area'] ? Member::loggedIn()->language()->addToStack( 'dev_widget_default_area_' . $json['default_area'] ) : Member::loggedIn()->language()->addToStack('none') ) : 'sidebar',
							'dev_widget_embeddable'	=> isset($json['embeddable'])   ? ( $json['embeddable'] ? '&#10003;' : '&#10007;' ) : '&#10007;'
					);
				}
	
				$table = new Custom( $data, $url );
				$table->quickSearch = 'dev_widget_key';
				$table->rootButtons = array(
						'add' => array(
								'icon'	=> 'plus',
								'title'	=> 'add',
								'link'	=> $url->setQueryString( 'widgetTable', 'form' ),
								'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('add') )
						)
				);
				$table->rowButtons = function( $row ) use ( $url, $widgetDirectory )
				{
					$buttons = [];

					$filePath = $widgetDirectory . "/" . $row['dev_widget_key'] . ".php";
					if( $ideLink = Developer::getIdeHref( $filePath ) )
					{
						$buttons['ide'] = [
						'icon'		=> 'fa-file-code',
						'title'		=> 'open_in_ide',
						'link'		=> $ideLink
						];
					};

					$buttons['edit'] = array(
									'icon'	=> 'pencil',
									'title'	=> 'edit',
									'link'	=> $url->setQueryString( 'widgetTable', 'form' )->setQueryString( 'key', $row['dev_widget_key'] ),
									'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('edit') )
							);
					$buttons['delete'] = array(
									'icon'	=> 'times-circle',
									'title'	=> 'delete',
									'link'	=> $url->setQueryString( 'widgetTable', 'delete' )->setQueryString( 'key', $row['dev_widget_key'] )->csrf(),
									'data'	=> array( 'delete' => '' )
							);
					return $buttons;
				};
	
				return $table;
					
		}
	}
	
	/**
	 * Get all cache keys (for all possible permissions, etc.)
	 *
	 * @param String|null $key				Widget key
	 * @param String|null $app				Parent application
	 * @return	array
	 * @note	This method does not take responsibility for checking if caches are expired
	 */
	public static function getCaches( string $key=NULL, string $app=NULL ): array
	{
		$caches = array();

		try
		{
			foreach(Db::i()->select( '*', 'core_widgets', static::_buildWhere( $key, $app ) ) as $widget )
			{
				if( $widget['caches'] )
				{
					$json = json_decode( $widget['caches'], TRUE );
					
					if ( ! is_array( $json ) )
					{
						return array();
					}
					
					foreach ( $json as $cKey => $time )
					{
						$caches[ $cKey ] = $time;
					}
				}
			}
			
		}
		catch ( UnderflowException $e )
		{
		}

		return $caches;
	}

	
	/**
	 * Delete caches
	 *
	 * @param String|null $key				Widget key
	 * @param String|null $app				Parent application
	 * @return	void
	 */
	public static function deleteCaches( string $key=NULL, string $app=NULL ) : void
	{
		if( Application::appIsEnabled( 'cms' ) )
		{
			CmsWidget::deleteCachesForBlocks( $key, $app );
		}

		$caches = static::getCaches( $key, $app );

		foreach ( $caches as $cacheKey => $time )
		{
			unset( Store::i()->$cacheKey );
		}

		Db::i()->update( 'core_widgets', array( 'caches' => NULL ), static::_buildWhere( $key, $app ) );
		unset( Store::i()->widgets );
	}

	/**
	 * Store this widget instance as trash incase we need to fetch the configuration
	 * when another column is updated due to a widget being moved from one column to another.
	 *
	 * @param string $uniqueId       Widget's Unique ID
	 * @param array $data           Widget Data
	 * @return  void
	 */
	public static function trash( string $uniqueId, array $data ) : void
	{
		Db::i()->delete( 'core_widget_trash', array( 'id=?', $uniqueId ) );
		Db::i()->insert( 'core_widget_trash', array(
			'id'    => $uniqueId,
		    'data'  => json_encode( $data ),
		    'date'  => time()
		) );
	}

	/**
	 * @var array
	 */
	protected static array $cachedConfiguration = [];

	/**
	 * Get and cache all configs to prevent one query per widget
	 * 
	 * @return array
	 */
	protected static function getAllConfigurations(): array
	{
		/* Try the json first */
		foreach( Db::i()->select( '*', 'core_widget_areas' ) as $item )
		{
			$widgets = json_decode( $item['widgets'], TRUE );
			foreach( $widgets as $widget )
			{
				static::$cachedConfiguration[ $widget['unique'] ] = $widget['configuration'];
			}
		}

		/* Now try the inline data as this may be more accurate */
		foreach( Db::i()->select( '*', 'core_widgets_config' ) as $widget )
		{
			static::$cachedConfiguration[ $widget['id'] ] = json_decode( $widget['data'], true );
		}

		return static::$cachedConfiguration;
	}
	/**
	 * Fetch the configuration for this unqiue ID. Looks in active tables and trash. When a widget is moved, saveOrder is called twice,
	 * once to remove the widget from column A and again to add it to column B. We store the widget removed from column A into the trash
	 * table.
	 *
	 * @param string $uniqueId   Widget's unique ID
	 * @return  array
	 */
	public static function getConfiguration( string $uniqueId ): array
	{
		if ( ! count( static::$cachedConfiguration ) )
		{
			static::getAllConfigurations();
		}

		/* Still here? rummage in the trash */
		if( !isset( static::$cachedConfiguration[ $uniqueId ] ) )
		{
			try
			{
				$widget = Db::i()->select( '*', 'core_widget_trash', array( 'id=?', $uniqueId ) )->first();
				$data = json_decode( $widget['data'], TRUE );
				if ( isset( $data['configuration'] ) )
				{
					static::$cachedConfiguration[ $uniqueId ] = $data['configuration'];
				}
			}
			catch( UnderflowException $ex )
			{
				static::$cachedConfiguration[ $uniqueId ] = [];
			}
		}

		return static::$cachedConfiguration[ $uniqueId ] ?? array();
	}	
	
	/**
	 * Get default widgets for an application
	 *
	 * @param Application|string $app	The application
	 * @return	array
	 */
	public static function appDefaults( Application|string $app ): array
	{
		$app = is_string( $app ) ? Application::load( $app ) : $app;
		if ( !isset( Store::i()->widgets ) )
		{
			$widgets = array();
			foreach( Db::i()->select( '*', 'core_widgets' ) as $row )
			{
				if ( $row['app'] )
				{
					$widgets[ $row['app'] ][ $row['key'] ] = $row;
				}
			}
			
			Store::i()->widgets = $widgets;
		}
		
		$return = array();
		if ( isset( Store::i()->widgets[ $app->directory ] ) )
		{
			foreach ( Store::i()->widgets[ $app->directory ] as $widget )
			{
				if ( $widget['default_area'] )
				{
					$return[] = $widget;
				}
			}
		}
		
		return $return;
	}

	/**
	 * @brief	Cached output to prevent rendering widget twice
	 */
	protected ?string $cachedOutput	= NULL;

	/**
	 * Return the widget output or an empty string if the widget shouldn't be returned on this page
	 *
	 * @return string
	 */
	protected function _render() : string
	{
		if( Settings::i()->clubs AND  isset( $this->configuration['clubs_visibility'] ) )
		{
			switch ( $this->configuration['clubs_visibility'] )
			{
				case 'all':
					return $this->render();
				case 'without_clubs':
					 return ( Club::userIsInClub() ) ? '' : $this->render();
				case 'only_clubs':
					return ( Club::userIsInClub() ) ?  $this->render() : '';
			}
		}
		return $this->render();
	}
	
	/**
	 * Convert the widget to HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		/* Wrap the whole thing in a try/catch because exceptions in __toString confuses PHP */
		try
		{
			/* Put the app check here as it needs to check the member's secondary groups but the PermissionCache only stores the primary group IDs */
			if ( $this->app )
			{
				if ( ! $this->application()->canAccess( Member::loggedIn() ) )
				{
					return '';
				}
			}
							
			/* If we're not caching (e.g. dynamic blocks in Pages), just return it */
			if ( $this->neverCache === TRUE )
			{
				return static::parseOutput( $this->_render() );
			}
						
			/* Otherwise, figure out what to display. Saved in $this->cachedOutput so if this is being used twice on the same page, we only do this once */
			if ( $this->cachedOutput === NULL )
			{
				/* Does this go in the store? Things like active users don't get stored, and if in developer or designer mode, nothing does */
				if ( isset( $this->cacheKey ) AND ( !isset( Request::i()->cookie['vle_editor'] ) or !Request::i()->cookie['vle_editor'] ) AND !IN_DEV)
				{		
					/* How long does the store last (in seconds)? */
					$expiration = Settings::i()->widget_cache_ttl;
					if ( isset( $this->cacheExpiration ) )
					{
						$expiration = $this->cacheExpiration;
					}
					
					/* If we have the TTL set to 0, don't bother with the store */
					if ( $expiration )
					{							
						/* Add/update in the store if it isn't there or it's expired */
						$cacheKey = $this->cacheKey;
						if ( !isset( Store::i()->$cacheKey ) or ( $widget = Store::i()->$cacheKey and $widget['built'] < ( time() - $expiration ) and static::$expirePerRequest-- ) )
						{
							/* The render() call below may take a long time to run for some widgets - we don't want lots of users to call
								it simultaneously, so save a blank widget for now. For a second or two (until we've built and stored
								the correct output which is done right after calling render) users will see nothing, which isn't ideal
								but is better than killing the server */
							Store::i()->$cacheKey = array( 'built' => time(), 'html' => '' );
							
							/* Render and store */
							$content = $this->_render();
							Member::loggedIn()->language()->parseOutputForDisplay( $content );
							Store::i()->$cacheKey = array( 'built' => time(), 'html' => $content ); // Corrects the blank output written above
							
							/* Log that cache key so if we need to delete all the caches for this widget later we have it */
							$caches = static::getCaches( $this->key, $this->app );
							
							foreach( $caches as $key => $timeBuilt )
							{
								if ( $key === $cacheKey )
								{
									continue;
								}
								
								if ( $timeBuilt < ( time() - $expiration ) )
								{
									if ( isset( Store::i()->$key ) )
									{
										unset( Store::i()->$key );
									}
			
									unset( $caches[ $key ] );
								}
							}
							
							$caches[ $cacheKey ] = time();
							Db::i()->update( 'core_widgets', array( 'caches' => json_encode( $caches ) ), static::_buildWhere( $this->key, $this->app ) );
						}
						
						/* Then use what the store has */
						$widget = Store::i()->$cacheKey;
						$this->cachedOutput = $widget['html'];
					}
				}
				
				/* If we still don't have anything, go ahead and render */
				if( $this->cachedOutput === NULL )
				{
					$this->cachedOutput = $this->_render();
				}
			}
			
			/* And render */
			return static::parseOutput( $this->cachedOutput );
		}
		catch ( Exception | Throwable $e )
		{
			IPS::exceptionHandler( $e );
		}

		return '';
	}

	/**
	 * Parse <time> tags to avoid caching with another's timezone
	 *
	 * @param string $output HTML code which may contain the tag
	 * @return string
	 */
	public static function parseOutput( string $output ): string
	{
		if ( mb_stristr( $output, '<time' ) )
		{
			$output = preg_replace_callback( '#<time([^>]+?)?>([^<]+?)</time>#i', function( $matches )
			{
				$time = NULL;
				if ( is_numeric( $matches[2] ) and strlen( $matches[2] ) === 10 )
				{
					$time = $matches[2];
				}
				else if ( preg_match( '#\s?datetime=[\'"]([^\'"]+?)[\'"]#', $matches[1], $dateTimeString ) )
				{
					if ( $dateTimeString[1] )
					{
						$time = strtotime( $dateTimeString[1] );
					}
				}

				if ( $time )
				{
					$options = array();

					preg_match_all( '#(\S+?)=["\'](.+?)["\']\s?#', $matches[1], $submatches, PREG_SET_ORDER );
					foreach( $submatches as $idx => $data )
					{
						$options[ str_replace( 'data-', '', $data[1] ) ] = $data[2];
					}

					$obj = DateTime::ts( $time );
					$val = $obj->html();

					if ( isset( $options['dateonly'] ) )
					{
						$val = $obj->localeDate();
					}
					else
					{
						if ( isset( $options['norelative'] ) )
						{
							$val = (string)$obj;
						}
					}

					return $val;
				}
			}, $output );
		}
		
		return $output;
	}
	
	/**
	 * Empty the widget trash
	 *
	 * @param int $seconds	Seconds old to remove
	 * @return	void
	 */
	public static function emptyTrash( int $seconds=86400 ) : void
	{
		$uniqueIds = static::getUniqueIds();

		foreach( Db::i()->select( '*', 'core_widget_trash', array( array( 'date < ?', time() - $seconds ) ) ) as $row )
		{
			$data = json_decode( $row['data'], TRUE );
			
			if ( ! empty( $data['app'] ) and ! empty( $data['key'] ) and ! empty( $data['unique'] ) )
			{
				/* Is this unique ID actually used elsewhere? Sometimes moving blocks around can add a row in the trash table with the same unique ID */
				if ( in_array( $data['unique'], $uniqueIds ) )
				{
					continue;
				}
				
				try
				{
					$widget = static::load( Application::load( $data['app'] ), $data['key'], $data['unique'], $data['configuration'] ?? NULL );
					$widget->delete();
				}
				catch( Exception $ex ) { }
			}
		}
		
		Db::i()->delete( 'core_widget_trash', array( 'date < ?', time() - $seconds ) );
	}

	/**
	 * Return an array of all areas that use this widget
	 *
	 * @param Application $app
	 * @param string $key
	 * @return array
	 */
	public static function usedWhere( Application $app, string $key ): array
	{
		$areas = [];
		foreach ( Db::i()->select( '*', 'core_widget_areas' ) as $row )
		{
			$data = json_decode( $row['widgets'], TRUE );

			if ( is_countable( $data ) AND count( $data ) )
			{
				foreach( $data as $widget )
				{
					if ( isset( $widget['unique'] ) and $widget['app'] === $app->directory and $widget['key'] === $key )
					{
						$areas[] = [
							'id'	=> $row['id'],
							'app'	=> $row['app'],
							'module' => $row['module'],
							'controller' => $row['controller'],
							'area'	=> $row['area']
						];
					}
				}
			}
		}

		return $areas;
	}

	/**
	 * Return unique IDs in use
	 *
	 * @return array
	 */
	public static function getUniqueIds(): array
	{
		$uniqueIds = array();
		foreach ( Db::i()->select( '*', 'core_widget_areas' ) as $row )
		{
			if( $row['widgets'] )
			{
				$data = json_decode( $row['widgets'], TRUE );

				if ( is_countable( $data ) )
				{
					foreach( $data as $widget )
					{
						if ( isset( $widget['unique'] ) )
						{
							$uniqueIds[] = $widget['unique'];
						}
					}
				}
			}

			if( $row['tree'] )
			{
				$area = new Area( json_decode( $row['tree'], true ), $row['area'] );
				foreach( $area->getAllWidgets() as $widget )
				{
					if( isset( $widget['unique'] ) )
					{
						$uniqueIds[] = $widget['unique'];
					}
				}
			}
		}

		return array_unique( $uniqueIds );
	}

	/**
	 * Build the where clause based on key, app
	 *
	 * @param string|null $key Key
	 * @param string|null $app Application
	 * @return    array
	 */
	protected static function _buildWhere( ?string $key, ?string $app ): array
	{
		$where = array();
		
		if( $key )
		{
			$where[] = array( '`key`=?', $key );
		}

		if( $app )
		{
			$where[] = array( 'app=?', $app );
		}
		
		return $where;
	}

	/**
	 * Specify widget configuration
	 *
	 * @param Form|null $form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		if ( $form === null )
		{
			$form = new Form;
		}

		/* Only show visibility options if we are editing from block manager as configuration is specific to each instance */
		if( Dispatcher::i()->controllerLocation == 'front' )
		{
			$form->add( new CheckboxSet( 'devices_to_show', $this->configuration['devices_to_show'] ?? array( 'Phone', 'Tablet', 'Desktop' ), FALSE, array( 'options' => array( 'Phone' => Member::loggedIn()->language()->addToStack( 'device_phone' ), 'Tablet' => Member::loggedIn()->language()->addToStack( 'device_tablet' ), 'Desktop' => Member::loggedIn()->language()->addToStack( 'device_desktop' ) ) ), NULL, NULL, NULL, 'devices_to_show' ) );
		}

		if( Settings::i()->clubs AND ( ( Request::i()->pageApp === 'core' AND  Request::i()->pageModule === 'clubs' ) OR  ( Request::i()->pageApp !== 'core' AND Request::i()->pageApp !== 'nexus'  ) ) )
		{
			$form->add( new Radio( 'clubs_visibility', $this->configuration['clubs_visibility'] ?? 'all', FALSE, array( 'options' => array( 'all' => 'everywhere', 'without_clubs' => 'without_clubs', 'only_clubs' => 'only_clubs')) ) );
		}

		if( $this->isBuilderWidget() )
		{
			Output::i()->linkTags['googlefonts'] = array( 'rel' => 'stylesheet', 'href' => "https://fonts.googleapis.com/css?family=Lato|Merriweather|Open+Sans|Raleway:400,900|Roboto:400,900&display=swap" );
			
			if ( ! isset( $this->configuration['widget_adv__custom'] ) and isset( Request::i()->block ) )
			{
				$customCss = '.' . Request::i()->block . " {\n\n}";
			}
			else
			{
				$customCss = $this->configuration['widget_adv__custom'] ?? '';
			}
			
			/* Box model */
			$form->add( new YesNo( 'widget_adv__border', $this->configuration['widget_adv__border'] ?? TRUE, FALSE ) );
			$form->add( new Select( 'widget_adv__padding', $this->configuration['widget_adv__padding'] ?? 'full', FALSE,
				array(
					'options' => array(
						'none' => 'widget_adv__padding_none',
						'half' => 'widget_adv__padding_half',
						'full' => 'widget_adv__padding_full',
						'custom' => 'custom',
					),
					'toggles'  => array(
						'custom'	=> array( 'padding_custom' )
					)
			) ) );
			
			$form->add( new Trbl( 'widget_adv__padding_custom', ( $this->configuration['widget_adv__padding_custom'] ?? 0 ), FALSE, array(), NULL, NULL, NULL, 'padding_custom' ) );
			
			/* Font size */
			$form->add( new Select( 'widget_adv__fontsize', $this->configuration['widget_adv__fontsize'] ?? 'i-font-size_1', FALSE,
				array(
					'options' => array(
						'inherit'    		   => 'widget_adv__inherit',
						'ipsTitle ipsTitle--h3'    => 'widget_adv__fontsize_pagetitle',
						'i-font-size_2'	   	   => 'widget_adv__fontsize_large',
						'i-font-size_1' 	   => 'widget_adv__fontsize_medium',
						'i-font-size_-1' 	   => 'widget_adv__fontsize_small',
						'custom'			   => 'custom',
					),
					'toggles'  => array(
						'custom'	=> array( 'font_custom' )
					)
			) ) );
			$form->add( new Number( 'widget_adv__fontsize_custom', ( $this->configuration['widget_adv__fontsize_custom'] ?? 12 ), FALSE, array(), NULL, NULL, 'px', 'font_custom' ) );
			
			/* Font face */
			$fontOptions = array( 'inherit' => 'widget_adv__inherit' );
			
			foreach( array( 'arial', 'helvetica', 'times new roman', 'courier', 'lato', 'merriweather', 'open sans', 'raleway', 'raleway black', 'roboto', 'roboto black' ) as $font )
			{
				$fontOptions[ ucfirst( $font ) ] = 'widget_adv__font_' . str_replace( ' ', '_', $font );
			}
			
			$form->add( new Select( 'widget_adv__font', $this->configuration['widget_adv__font'] ?? 'inherit', FALSE,
				array(
					'options' => $fontOptions
			) ) );
			
			$form->add( new Select( 'widget_adv__fontalign', $this->configuration['widget_adv__fontalign'] ?? 'left', FALSE,
				array(
					'options' => array(
						'left'      => 'widget_adv__fontalign_left',
						'center'    => 'widget_adv__fontalign_center',
						'right'	   	=> 'widget_adv__fontalign_right'
					)
			) ) );
			
			/* Font color */
			$form->add( new Select( 'widget_adv__fontcolor_custom', $this->configuration['widget_adv__fontcolor_custom'] ?? FALSE, FALSE,
				array(
					'options' => array(
						'inherit'    	 => 'widget_adv__inherit',
						'custom'		 => 'custom',
					),
					'toggles'  => array(
						'custom'	=> array( 'fontcolor_custom' )
					)
			), NULL, NULL, NULL, 'fontcolor_selector' ) );
			
			$form->add( new Color( 'widget_adv__fontcolor', $this->configuration['widget_adv__fontcolor'] ?? '', FALSE, array( 'swatches' => TRUE, 'rgba' => TRUE ), NULL, NULL, NULL, 'fontcolor_custom' ) );
			
			/* Background color */
			$form->add( new Select( 'widget_adv__background_custom', $this->configuration['widget_adv__background_custom'] ?? FALSE, FALSE,
				array(
					'options' => array(
						'inherit'    	 => 'widget_adv__inherit',
						'transparent'    => 'widget_adv__fontcolor_custom_transparent',
						'custom'		 => 'widget_adv__background_custom_color',
						'image'			 => 'widget_adv__background_custom_image',
					),
					'toggles'  => array(
						'custom'	=> array( 'background_custom' ),
						'image'	=> array( 'background_image', 'background_overlay' )
					)
			), NULL, NULL, NULL, 'background_selector' ) );
			
			$form->add( new Upload( 'widget_adv__background_custom_image', isset( $this->configuration['widget_adv__background_custom_image'] ) ? File::get( 'core_Attachment', $this->configuration['widget_adv__background_custom_image'] ) : '', FALSE, array(  'storageExtension' => 'core_Attachment', 'allowStockPhotos' => TRUE, 'image' => true ), NULL, NULL, NULL, 'background_image' ) );
			$form->add( new Color( 'widget_adv__background_custom_image_overlay', $this->configuration['widget_adv__background_custom_image_overlay'] ?? '', FALSE, array( 'swatches' => TRUE, 'rgba' => TRUE ), NULL, NULL, NULL, 'background_overlay' ) );

			$form->add( new Color( 'widget_adv__background', $this->configuration['widget_adv__background'] ?? '', FALSE, array( 'swatches' => TRUE, 'rgba' => TRUE ), NULL, NULL, NULL, 'background_custom' ) );

			$form->add( new Codemirror( 'widget_adv__custom', $customCss, FALSE, array( 'codeModeAllowedLanguages' => [ 'css' ] ) ) );
		}

		return $form;
	}

	/**
	 * Get the widget from the stored data
	 *
	 * @param 	array 	$data		The data stored for the widget, either in the core_widget_areas table or the cms_page_widget_areas table
	 *
	 * @return Widget|null
	 */
	public static function createWidgetFromStoredData( array $data ) : ?Widget
	{
		$config = ( isset( $data['configuration'] ) AND !empty( $data['configuration'] ) ) ? $data['configuration'] : static::getConfiguration( $data['unique'] );
		try
		{
			$widget = static::load( Application::load( $data['app'] ), $data['key'], $data['unique'], $config, $data['restrict'] ?? null, $data['orientation'] ?? 'horizontal', $data['layout'] ?? '' ); // todo wtf does orientation do?
			if ( $data['key'] === 'Database' and !\IPS\cms\Databases\Dispatcher::i()->databaseId )
			{
				$widget->render();
			}
			return $widget;
		}
		catch( OutOfRangeException )
		{
			/* The app might be disabled, so just skip it */
			return null;
		}
	}

	/**
	 * This method can be used to remove a widgets appearances in the CMS and Core App
	 *
	 * @param string $widgetkey		The widget key
	 * @param string $app			The app the widget belongs to
	 * @return bool
	 */
	final public static function deprecateWidget( string $widgetkey, string $app ): bool
	{
		$areas = array( 'core_widget_areas' );
		if ( Application::appIsEnabled('cms') )
		{
			$areas[] = 'cms_page_widget_areas';
		}

		foreach ( $areas as $table )
		{
			$widgetsColumn = $table == 'core_widget_areas' ? 'widgets' : 'area_widgets';
			foreach (Db::i()->select( '*', $table ) as $area )
			{
				$whereClause = $table == 'core_widget_areas' ? array( 'id=? AND area=?', $area['id'], $area['area'] ) : array( 'area_page_id=? AND area_area=?', $area['area_page_id'], $area['area_area'] );

				$widgets = json_decode( $area[ $widgetsColumn ], TRUE );
				$update = FALSE;

				foreach ( $widgets as $k => $widget )
				{
					if ( $widget['key'] == $widgetkey AND $app == $widget['app'] )
					{
						unset( $widgets[ $k ] );
						$update = TRUE;
					}

				}
				if ( $update )
				{
					Db::i()->update( $table, array( $widgetsColumn => json_encode( $widgets ) ), $whereClause );
				}
			}
		}
		return TRUE;
	}
}