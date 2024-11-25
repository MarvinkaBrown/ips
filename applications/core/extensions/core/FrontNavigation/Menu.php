<?php
/**
 * @brief		Front Navigation Extension: Dropdown Menu
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		30 Jun 2015
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\FrontNavigation;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use function defined;
use function json_decode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Dropdown Menu
 */
class Menu extends FrontNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('menu_custom_menu');
	}
	
	/**
	 * Allow multiple instances?
	 *
	 * @return    bool
	 */
	public static function allowMultiple(): bool
	{
		return TRUE;
	}
	
	/**
	 * Get configuration fields
	 *
	 * @param	array	$existingConfiguration	The existing configuration, if editing an existing item
	 * @param int|null $id						The ID number of the existing item, if editing
	 * @return    array
	 */
	public static function configuration(array $existingConfiguration, ?int $id = NULL ): array
	{
		return array(
			new Translatable( 'menu_custom_menu_title', NULL, TRUE, array( 'app' => 'core', 'key' => $id ? "menu_item_{$id}" : NULL ) ),
		);
	}
	
	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return    array
	 */
	public static function parseConfiguration( array $configuration, int $id ): array
	{		
		Lang::saveCustom( 'core', "menu_item_{$id}", $configuration['menu_custom_menu_title'] );
		unset( $configuration['menu_custom_menu_title'] );
		
		return $configuration;
	}
	
	/**
	 * Permissions can be inherited?
	 *
	 * @return    bool
	 */
	public static function permissionsCanInherit(): bool
	{
		return FALSE;
	}
		
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack( "menu_item_{$this->id}" );
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return NULL;
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		foreach ( $this->children() as $child )
		{
			if ( $child->active() )
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	/**
	 * @brief	Store child objects for re-use later
	 */
	protected ?array $children = NULL;
	
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return    array|null
	 */
	public function children( bool $noStore=FALSE ): array|null
	{
		if ( $this->children === NULL)
		{
			$this->children = array();
			$frontNavigation = FrontNavigation::frontNavigation( $noStore );

			/* If this is a root level item, don't return children, we'll use the subbars instead */
			if( isset( $frontNavigation[0][ $this->id ] ) )
			{
				$this->children = [];
			}
			elseif ( isset( $frontNavigation[ $this->id ] ) )
			{
				foreach ( $frontNavigation[ $this->id ] as $item )
				{
					if ( Application::appIsEnabled( $item['app'] ) )
					{
						$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
						if ( class_exists( $class ) )
						{
							$this->children[ $item['id'] ] = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'], $item['menu_types'], json_decode( (string) $item['icon'], TRUE ) );
						}
					}
				}
			}
		}

		return $this->children;
	}

	/**
	 * Can the currently logged in user see this menu item?
	 *
	 * @return	bool
	 */
	public function canView() : bool
	{
		/* If we have no children, don't show this, regardless of permissions */
		if( !count( $this->children() ) and !isset( FrontNavigation::i()->subBars()[ $this->id ] ) )
		{
			return false;
		}

		return parent::canView();
	}
	
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return    bool
	 */
	public function canAccessContent(): bool
	{
		foreach ( $this->children() as $child )
		{
			if ( $child->canView() )
			{
				return TRUE;
			}
		}

		/* If we're a root item we probably have sub-bars */
		$subbars = FrontNavigation::i()->subBars();
		if( isset( $subbars[ $this->id ] ) and count( $subbars[ $this->id ] ) )
		{
			foreach( $subbars[ $this->id ] as $subbar )
			{
				if( $subbar->canView() )
				{
					return true;
				}
			}
		}

		return FALSE;
	}
}