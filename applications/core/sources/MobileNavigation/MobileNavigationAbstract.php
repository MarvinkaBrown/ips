<?php
/**
 * @brief		Abstract Mobile Navigation Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		12 Jun 2019
 */

namespace IPS\core\MobileNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mobile Navigation Extension: Custom Item
 */
abstract class MobileNavigationAbstract
{
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
	 * @param	int|null		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function parseConfiguration( array $configuration, ?int $id ) : array
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
	 * Constructor
	 *
	 * @param	array	$configuration	The configuration
	 * @param	int		$id				The ID number
	 * @param	string|array	$permissions	The permissions (* or comma-delimited list of groups)
	 */
	public function __construct( array $configuration, int $id, string|array $permissions )
	{
		$this->configuration = $configuration;
		$this->id = $id;
		$this->permissions = $permissions;
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
	 * @return	Url|string
	 */
	abstract public function link() : Url|string;

	/**
	 * Get icon
	 *
	 * @return	string|null
	 */
	public function icon() : ?string
	{
		return NULL;
	}
}