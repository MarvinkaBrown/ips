<?php
/**
 * @brief		Front Navigation Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		30 Jun 2015
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Lang;
use IPS\Member;
use IPS\Request;
use OutOfRangeException;
use function count;
use function defined;
use function explode;
use function get_called_class;
use function intval;
use function json_decode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Custom Item
 */
class FrontNavigation
{
	/**
	 * @brief	Singleton Instances
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static ?FrontNavigation $instance = NULL;
	
	/**
	 * @brief	This is a hacky flag to indicate if active page is a club area so we know which tab to highlight in the menu
	 */
	public static bool $clubTabActive = FALSE;
	
	/**
	 * @brief	Store root objects for re-use later
	 */
	protected ?array $roots = NULL;
	
	/**
	 * @brief	Store subBars objects for later use
	 */
	protected ?array $subBars = NULL;
	
	/**
	 * Get instance
	 *
	 * @return    static|null
	 */
	public static function i() : static|null
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		return static::$instance;
	}

	/**
	 * Check to see if a single node extension is currently active, so we can choose to not duplicate highlighted tabs
	 *
	 * @param string $appKey
	 * @return bool
	 */
	public static function nodeExtensionIsActive( string $appKey ): bool
	{
		/* Checks to see if we have any single node extensions enabled, and if it is active so we don't duplicate tabs */
		foreach( static::frontNavigation() as $parent => $items )
		{
			foreach( $items as $item )
			{
				if ( $item['app'] === 'core' and $item['extension'] === 'Node' )
				{
					$config = json_decode( $item['config'], TRUE );

					if ( isset( $config['nodeClass'] ) )
					{
						$bits = explode( '\\', $config['nodeClass'] );

						if ( $bits[1] === $appKey )
						{
							/* It is a call to load a Node, but it should be cached from the navigation extension doing that already */
							try
							{
								$class = $config['nodeClass'];
								$node = $class::load( $config['id'] );
								if ( stristr( (string) Request::i()->url(), (string) $node->url() ) )
								{
									/* return true here.. or let it go around the foreach again for a check on another item */
									return true;
								}
							}
							catch( OutOfRangeException ) { }
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get data store
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public static function frontNavigation( bool $noStore=FALSE ) : array
	{
		if ( $noStore or !isset( Store::i()->frontNavigation ) )
		{
			$frontNavigation = array( 0 => array(), 1 => array() );
			$select = Db::i()->select( '*', 'core_menu', NULL, 'position' );
			if ( count( $select ) )
			{
				foreach ( $select as $item )
				{
					if ( Application::appIsEnabled( $item['app'] ) )
					{
						$frontNavigation[ intval( $item['parent'] ) ][ $item['id'] ] = $item;
					}
				}
			}
			if ( $noStore )
			{
				return $frontNavigation;
			}
			Store::i()->frontNavigation = $frontNavigation;
		}
		return Store::i()->frontNavigation;
	}
	
	/**
	 * Delete front navigation items by application
	 *
	 * @param	Application	$app	Application deleted
	 * @return	void
	 */
	public static function deleteByApplication( Application $app ) : void
	{
		foreach( Db::i()->select( '*', 'core_menu', array( array( 'extension=?', 'CustomItem' ) ) ) as $row )
		{
			$config = json_decode( $row['config'], TRUE );
		
			if ( isset( $config['menu_custom_item_url'] ) and $config['menu_custom_item_url'] and isset( $config['internal'] ) and $config['internal'] )
			{
				try
				{
					parse_str( $config['menu_custom_item_url'], $data );
					
					if ( ! empty( $data['app'] ) and $data['app'] === $app->directory )
					{
						Db::i()->delete( 'core_menu', array( 'id=?', $row['id'] ) );
					}
				}
				catch( Exception $e ) { }
			}
		}
		
		Db::i()->delete( 'core_menu', array( 'app=?', $app->directory ) );
		
		unset( Store::i()->frontNavigation );
	}
		
	/**
	 * Build default front navigation
	 *
	 * @param bool $newInstall
	 * @return	void
	 */
	public static function buildDefaultFrontNavigation( bool $newInstall=false ) : void
	{		
		Db::i()->delete( 'core_menu' );
		
		$position = 1;
				
		/* Browse */
		Db::i()->insert( 'core_menu', array(
			'id'			=> 1,
			'app'			=> 'core',
			'extension'		=> 'Menu',
			'config'		=> '[]',
			'position'		=> $position++,
			'parent'		=> NULL,
			'permissions'	=> '*',
			'icon'			=> json_encode( [ [
				'key' => 'bars-staggered:fas',
				'type' => 'fa',
				'raw' => '<i class="fa-solid fa-bars-staggered"></i>',
				'title' => 'bars-staggered',
				'html' => '\r\n<!-- theme_core_global_global_icon --><span class="ipsIcon ipsIcon--fa" data-label="bars-staggered" aria-hidden="true"><i class="fa-solid fa-bars-staggered"></i></span>'
			] ] )
		) );
		Lang::saveCustom( 'core', "menu_item_1", Member::loggedIn()->language()->get('default_menu_item_1') );

		/* Activity */
		Db::i()->insert( 'core_menu', array(
			'id'			=> 2,
			'app'			=> 'core',
			'extension'		=> 'Menu',
			'config'		=> '[]',
			'position'		=> $position++,
			'parent'		=> NULL,
			'permissions'	=> '*',
			'icon'			=> json_encode( [ [
				'key' => 'newspaper:far',
				'type' => 'fa',
				'raw' => '<i class="fa-regular fa-newspaper"></i>',
				'title' => 'newspaper',
				'html' => '\r\n<!-- theme_core_global_global_icon --><span class="ipsIcon ipsIcon--fa" data-label="newspaper (regular)" aria-hidden="true"><i class="fa-regular fa-newspaper"></i></span>'
			] ] )
		) );
		Lang::saveCustom( 'core', "menu_item_2", Member::loggedIn()->language()->get('default_menu_item_2') );
		
		/* Loop */
		$waiting = array();
		foreach ( Application::applications() as $app )
		{
			/* When we're installing (dispatcher: setup) appIsEnabled() returns false */
			if( ! $newInstall )
			{
				if ( !Application::appIsEnabled( $app->directory ) )
				{
					continue;
				}
			}

			$defaultNavigation = $app->defaultFrontNavigation();
			foreach ( $defaultNavigation as $type => $tabs )
			{
				foreach ( $tabs as $config )
				{
					switch ( $type )
					{
						case 'rootTabs':
							$parent = NULL;
							break;
						case 'browseTabs':
							$parent = 1;
							break;
						case 'activityTabs':
							$parent = 2;
							break;
					}

					$config['real_app'] = $app->directory;
					if ( !isset( $config['app'] ) )
					{
						$config['app'] = $app->directory;
					}

					if ( $type == 'browseTabsEnd' )
					{
						$waiting[] = $config;
					}
					else
					{
						static::insertMenuItem( $parent ?? null, $config, $position );
					}
				}
			}
		}
		foreach ( $waiting as $config )
		{
			static::insertMenuItem( 1, $config, $position );
		}
	}
	
	/**
	 * Insert a menu item
	 *
	 * @param	int|null		$parent			Parent ID
	 * @param	array	$config			Configuration
	 * @param	int		$position		Position
	 * @param	bool	$isMenuChild	Is item in a menu?
	 * @return	void
	 */
	public static function insertMenuItem( ?int $parent, array $config, int $position, bool $isMenuChild=FALSE ) : void
	{
		$insertedId = Db::i()->insert( 'core_menu', array(
			'app'			=> $config['app'],
			'extension'		=> $config['key'],
			'config'		=> json_encode( $config['config'] ?? array() ),
			'position'		=> ( $position + 1 ),
			'parent'		=> (int) $parent,
			'permissions'	=> NULL,
			'is_menu_child'	=> $isMenuChild,
			'icon'			=> $config['icon'] ?? null
		) );
		
		if ( isset( $config['title'] ) )
		{
			Lang::copyCustom( $config['real_app'], $config['title'], "menu_item_{$insertedId}" );
		}
		
		if ( isset( $config['children'] ) )
		{
			foreach ( $config['children'] as $childConfig )
			{
				$childConfig['real_app'] = $config['real_app'];
				if ( !isset( $childConfig['app'] ) )
				{
					$childConfig['app'] = $config['real_app'];
				}
						
				static::insertMenuItem( $insertedId, $childConfig, $position, $config['app'] == 'core' and $config['key'] == 'Menu' );
			}
		}
	}
	
	/**
	 * @brief	The active primary navigation bar
	 */
	public ?int $activePrimaryNavBar = NULL;
	
	/**
	 * Get roots
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function roots( bool $noStore=FALSE ) : array
	{
		if ( $this->roots === NULL )
		{
			$this->roots = array();
			$frontNavigation = static::frontNavigation( $noStore );
			$return = array();
			foreach ( $frontNavigation[0] as $item )
			{
				$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
				if ( class_exists( $class ) )
				{
					$object = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'], $item['menu_types'], json_decode( (string) $item['icon'], TRUE ), $item['parent'] );
					if ( !$this->activePrimaryNavBar )
					{
						$this->activePrimaryNavBar = $item['id'];
					}
					$this->roots[ $item['id'] ] = $object;
				}
			}
		}
	
		return $this->roots;
	}

	/**
	 * Get sub-bars
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function subBars( bool $noStore=FALSE ) : array
	{
		if ( $this->subBars === NULL )
		{
			$this->subBars = array();
			$frontNavigation = static::frontNavigation( $noStore );
			$parentIDs = array();
			// Changed so that empty sub bars don't add an array to their parent, allowing us to do \count( $subBars ) and figure
			// out if there's any to show.
			foreach ( $frontNavigation[0] as $item )
			{
				$parentIDs[] = $item['id'];
			}

			foreach ( $parentIDs as $i )
			{
				if ( isset( $frontNavigation[$i] ) )
				{
					foreach ( $frontNavigation[$i] as $item )
					{
						$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
						if ( class_exists( $class ) )
						{
							$this->subBars[ $item['parent'] ][ $item['id'] ] = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'], $item['menu_types'], json_decode( (string) $item['icon'], TRUE ), $item['parent'] );
						}
					}
				}
			}
		}

		return $this->subBars;
	}
}