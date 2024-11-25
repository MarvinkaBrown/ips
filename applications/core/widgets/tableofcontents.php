<?php
/**
 * @brief		tableofcontents Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Jan 2024
 */

namespace IPS\core\widgets;

use IPS\Application;
use IPS\cms\modules\front\pages\page;
use IPS\Content\Controller;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use OutOfBoundsException;
use OutOfRangeException;
use Throwable;
use UnderflowException;
use IPS\Widget\StaticCache;
use IPS\Widget;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tableofcontents Widget
 */
class tableofcontents extends StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'tableofcontents';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';

	/**
	 * This key is used to select the correct items; set it using static::setKey()
	 * @var string|int|null
	 */
	protected static string|int|null $_tocKey;


	/**
	 * Set a key for the current table of contents so they are unique per id/page
	 *
	 * @param string|int|null $key
	 * @return void
	 */
	public static function setKey( string|int|null $key=null ) :void
	{
		static::$_tocKey = $key;
	}


	/**
	 * @return bool
	 */
	public function canEdit() : bool
	{
		$controller = Dispatcher::i()->dispatcherController;
		if ( $controller instanceof Controller )
		{
			try
			{
				$item = $controller::loadItem();
				if ( $item?->author()?->member_id === Member::loggedIn()->member_id and Member::loggedIn()->member_id )
				{
					return true;
				}
			}
			catch ( Throwable ) {}
		}
		return Dispatcher::i()->application instanceof Application AND Dispatcher::i()->application->canManageWidgets();
	}

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );

		$data = Dispatcher::i()->getLocationData();
		if ( @$data['app'] === 'cms' and @$data['module'] === 'pages' and @$data['controller'] === 'page' and $page = page::getPage() )
		{
			@$data['id'] = $page->id;
		}
		$location = static::getLocationHash( @$data['app'], @$data['module'], @$data['controller'], @$data['id'] );
		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( Member::loggedIn()->language()->id . "_" . $orientation) . ( $this->canEdit() ? '_canEdit_' : '_cannotEdit_' )  . '_' . $location;
	}

	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init() : void
	{


		// Use this to perform any set up and to assign a template that is not in the following format:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
		// If you are creating a plugin, uncomment this line:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'plugins', 'core', 'global' ), $this->key ) );
		// And then create your template at located at plugins/<your plugin>/dev/html/tableofcontents.phtml
		
		
		parent::init();
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );
		$form->addMessage( "block_tableofcontents__info" );
 		return $form;
 	} 
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
	public function preConfig( array $values ): array
	{
		return $values;
	}

	/**
	 * Get a hash for a location; This method is used to keep the results consistent
	 *
	 * @param string|null $app
	 * @param string|null $module
	 * @param string|null $controller
	 * @param string|null $else
	 * @return string
	 */
	public static function getLocationHash( ?string $app=null, ?string $module=null, ?string $controller=null, ?string $else=null ) : string
	{
		return md5( json_encode( [ $app, $module, $controller, $else ] ) );
	}

	/**
	 * Get the items inside the table of contents widget. If the widget was never configured, it will return null.
	 *
	 * @param array $data
	 *
	 * @return array|null
	 * @static
	 */
	public static function getItems( array $data ) : array|null
	{
		try
		{
			$where = [ [ '`app`=? AND `module`=? AND `controller`=?', $data['app'], $data['module'], $data['controller'] ] ];
			if ( isset( $data['key'] ) )
			{
				$where[] = [ '`key`=?', $data['key'] ];
			}
			$row = Db::i()->select( '*', 'core_table_of_contents', $where )->first();
			$items = json_decode( $row['contents'], true );

			/* Fail fast here. This should always json_decode to an array */
			if ( !is_array( $items ) )
			{
				throw new OutOfBoundsException;
			}

			return $items;
		}
		catch ( UnderflowException ) {}

		return null;
	}

	/**
	 * Get the location data from the current request
	 *
	 * @return array
	 */
	public static function getLocationData() : array
	{
		if ( Widget\Request::i() instanceof Widget\Request )
		{
			$data['app'] = Request::i()->pageApp;
			$data['module'] = Request::i()->pageModule;
			$data['controller'] = Request::i()->pageController;
			$data['id'] = Widget\Request::i()->id ?? null;

			if ( isset( $data['app'] ) and isset( $data['module'] ) and isset( $data['controller'] ) )
			{
				try
				{
					if ( Request::i()->pageID and $page = \IPS\cms\Pages\Page::load( Request::i()->pageID ) )
					{
						$data['key'] = $page->id;
					}
				}
				catch ( UnderflowException|OutOfRangeException ) {}

				return $data;
			}
		}
		$data = Dispatcher::i()->getLocationData();
		if ( $data['app'] === 'cms' and $data['module'] === 'pages' and $data['controller'] === 'page' and $page = page::getPage() )
		{
			$data['key'] = (string) $page->id;
		}
		else if ( isset( static::$_tocKey ) )
		{
			$data['key'] = (string) static::$_tocKey;
		}
		else if ( isset( $data['id'] ) )
		{
			$data['key'] = (string) $data['id'];
		}

		return $data;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$data = static::getLocationData();
		Output::i()->addCssFiles( '/framework/table-of-contents.css', 'core', 'global' );
		return $this->output( static::getItems( $data ), $this->canEdit() );
		// Use $this->output( $foo, $bar ); to return a string generated by the template set in init() or manually added via $widget->template( $callback );
		// Note you MUST route output through $this->output() rather than calling \IPS\Theme::i()->getTemplate() because of the way widgets are cached
	}
}