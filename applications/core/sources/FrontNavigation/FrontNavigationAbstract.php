<?php
/**
 * @brief		Abstract Front Navigation Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		30 Jun 2015
 */

namespace IPS\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\FrontNavigation;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use IPS\Theme;
use function defined;
use function in_array;
use function is_array;
use function json_decode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Custom Item
 */
abstract class FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon;

	/**
	 * @brief	Store sub item objects for re-use later
	 */
	protected ?array $subItems = NULL;
	
	/**
	 * Get sub items of an item
	 *
	 * @return	array
	 */
	public function subItems() : array
	{
		if ( $this->subItems === NULL )
		{
			$this->subItems = array();
			$frontNavigation = FrontNavigation::frontNavigation();
			
			if ( isset( $frontNavigation[ $this->id ] ) )
			{
				foreach ( $frontNavigation[ $this->id ] as $item )
				{
					$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
					if ( class_exists( $class ) )
					{
						$this->subItems[ $item['id'] ] = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'], $item['menu_types'], json_decode( (string) $item['icon'], TRUE ) );
					}
				}
			}
		}

		return $this->subItems;
	}

	/**
	 * Return the icon data for the attribute
	 * @return string
	 */
	public function getIconDataForAttribute(): string
	{
		if ( $this->icon and ! empty( $this->icon ) and is_array( $this->icon ) )
		{
			/* This is from the icon picker, so let's format a bit */
			$icon = $this->icon[0];

			if ( $icon['type'] === 'emoji' )
			{
				return $icon['raw'];
			}
			else if ( $icon['type'] === 'fa' )
			{
				return 'fa';
			}
		}

		return $this->getDefaultIcon();
	}

	/**
	 * Return the icon data for the attribute
	 * @return string
	 */
	public function getIconData(): string
	{
		if ( !empty( $this->icon[0]['raw'] ) )
		{
			return $this->icon[0]['raw'];
		}

		return "";
	}

	/**
	 * Return the default icon
	 *
	 * @return string
	 */
	public function getDefaultIcon(): string
	{
		return ( $this->defaultIcon ?? '\f1c5' );
	}

	/**
	 * Allow multiple instances?
	 *
	 * @return	bool
	 */
	public static function allowMultiple() : bool
	{
		return FALSE;
	}
	
	/**
	 * Get configuration fields
	 *
	 * @param	array	$existingConfiguration	The existing configuration, if editing an existing item
	 * @param	int|null		$id						The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function configuration( array $existingConfiguration, ?int $id = NULL ) : array
	{
		return array();
	}
	
	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function parseConfiguration( array $configuration, int $id ) : array
	{
		return $configuration;
	}
	
	/**
	 * @brief	The configuration
	 */
	protected array $configuration = [];
	
	/**
	 * @brief	The ID number
	 */
	public ?int $id = null;
	
	/**
	 * @brief	The permissions
	 */
	public array|string|null $permissions = null;

	/**
	 * @brief	The menu types
	 */
	public string $menuTypes = '';

	/**
	 * @brief	The icon data
	 */
	public array|null $icon = null;

	/**
	 * @brief	Parent ID
	 */
	public int $parent = 0;

	/**
	 * Constructor
	 *
	 * @param	array	$configuration	The configuration
	 * @param	int		$id				The ID number
	 * @param	string|null	$permissions	The permissions (* or comma-delimited list of groups)
	 * @param	string	$menuTypes		The menu types (either * or json string)
	 * @return	void
	 */
	public function __construct( array $configuration, int $id, string|null $permissions, string $menuTypes, array|null $icon, int|null $parent = 0 )
	{
		$this->configuration = $configuration;
		$this->id = $id;
		$this->permissions = $permissions;
		$this->menuTypes = $menuTypes;
		$this->icon = $icon;
		$this->parent = intval( $parent ); // Always make sure we have an integer, and not a null from the database
	}
	
	/**
	 * Permissions can be inherited?
	 *
	 * @return	bool
	 */
	public static function permissionsCanInherit() : bool
	{
		return TRUE;
	}
	
	/**
	 * Can this item be used at all?
	 * For example, if this will link to a particular feature which has been diabled, it should
	 * not be available, even if the user has permission
	 *
	 * @return	bool
	 */
	public static function isEnabled() : bool
	{
		return TRUE;
	}
	
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return	bool
	 */
	public function canAccessContent() : bool
	{
		return TRUE;
	}
	
	/**
	 * Can the currently logged in user see this menu item?
	 *
	 * @return	bool
	 */
	public function canView() : bool
	{
		if ( static::isEnabled() )
		{
			if ( $this->permissions === NULL ) // NULL indicates "Show this item to users who can access its content."
			{
				return $this->canAccessContent();
			}
			else
			{
				return $this->permissions == '*' ? TRUE : Member::loggedIn()->inGroup( explode( ',', $this->permissions ) );
			}
		}
		return FALSE;
	}
		
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	abstract public function title() : string;
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	abstract public function link() : Url|string|null;

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	abstract public static function typeTitle(): string;

	/**
	 * Get Attributes
	 *
	 * @return	string
	 */
	public function attributes() : string
	{
		return '';
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	abstract public function active() : bool;
		
	/**
	 * Is this item, or any of its child items, active?
	 *
	 * @param string|null $menuType 	header|sidebar|smallscreen
	 * @return	bool
	 */
	public function activeOrChildActive( ?string $menuType=null ) : bool
	{
		if( $menuType === null )
		{
			$menuType = ( Theme::i()->getLayoutValue( 'global_view_mode' ) == 'side' ) ? 'sidebar' : 'header';
		}

		if ( $this->isAvailableFor( $menuType ) and $this->active() )
		{
			return TRUE;
		}
		
		foreach ( $this->subItems() as $item )
		{
			/* @var FrontNavigationAbstract $item */
			if ( $item->isAvailableFor( $menuType ) and $item->active() )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array|null
	 */
	public function children( bool $noStore=FALSE ) : ?array
	{
		return NULL;
	}

	/**
	 * Is this item available for the specified type?
	 *
	 * @param string $type
	 * @return bool
	 */
	public function isAvailableFor( string $type ): bool
	{
		if ( $this->menuTypes === '*' )
		{
			return true;
		}

		if ( $json = json_decode( $this->menuTypes, true ) )
		{
			return in_array( $type, $json );
		}

		return false;
	}

	/**
	 * A super handy method to avoid lots of PHP code in templates
	 *
	 * @return boolean
	 */
	public function isSideBarItemCollapsed(): bool
	{
		if ( isset( Request::i()->cookie['collapsedNavigationPanels'] ) AND in_array( $this->id, json_decode( Request::i()->cookie['collapsedNavigationPanels'], true ) ) )
		{
			return true;
		}
		else if ( ! isset( Request::i()->cookie['collapsedNavigationPanels'] ) )
		{
			/* By default, we just want to collapse the sub-items, not the top level items */
			return $this->parent > 0;
		}

		return false;
	}
}