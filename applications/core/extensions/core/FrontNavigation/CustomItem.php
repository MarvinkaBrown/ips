<?php
/**
 * @brief		Front Navigation Extension: Custom Item
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Core
 * @since		21 Jan 2015
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang;
use IPS\Member;
use IPS\Request;
use function defined;
use function urldecode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Custom Item
 */
class CustomItem extends FrontNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('menu_custom_item');
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
		$currentUrl = NULL;

		if ( isset( $existingConfiguration['menu_custom_item_url'] ) )
		{
			$existingConfiguration['menu_custom_item_url'] = urldecode( $existingConfiguration['menu_custom_item_url'] );
			
			if ( isset( $existingConfiguration['internal'] ) )
			{
				$currentUrl = (string) Url::internal( $existingConfiguration['menu_custom_item_url'], 'front', $existingConfiguration['internal'], $existingConfiguration['seoTitles'] ?? array() );
			}
			else
			{
				$currentUrl = $existingConfiguration['menu_custom_item_url'];
			}
		}
		
		return array(
			new Translatable( 'menu_custom_item_link', NULL, NULL, array( 'app' => 'core', 'key' => $id ? "menu_item_{$id}" : NULL ), function( $val )
			{
				if ( !trim( $val[ Lang::defaultLanguage() ] ) )
				{
					throw new InvalidArgumentException('form_required');
				}
			} ),
			new \IPS\Helpers\Form\Url( 'menu_custom_item_url', $currentUrl, NULL, array(), function( $val )
			{
				if ( isset( Request::i()->menu_manager_extension ) and Request::i()->menu_manager_extension === 'core_CustomItem' and empty( $val ) )
				{
					throw new InvalidArgumentException('form_required');
				}
			} ),
			new YesNo( 'menu_custom_item_target_blank', $existingConfiguration['menu_custom_item_target_blank'] ?? FALSE )
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
		$baseUrl = Url::internal('', 'front');
		
		if ( $configuration['menu_custom_item_url'] instanceof Friendly )
		{
			$configuration['internal'] = $configuration['menu_custom_item_url']->seoTemplate;
			$configuration['seoTitles'] = $configuration['menu_custom_item_url']->seoTitles;
			$configuration['menu_custom_item_url'] = http_build_query( $configuration['menu_custom_item_url']->hiddenQueryString + $configuration['menu_custom_item_url']->queryString, '', '&' );
		}
		else
		{
			$configuration['menu_custom_item_url'] = (string) $configuration['menu_custom_item_url'];
		}
				
		Lang::saveCustom( 'core', "menu_item_{$id}", $configuration['menu_custom_item_link'] );
		unset( $configuration['menu_custom_item_link'] );
		
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
		if ( isset( $this->configuration['menu_custom_item_url'] ) and ( $this->configuration['menu_custom_item_url'] or isset( $this->configuration['internal'] ) ) )
		{
			if ( isset( $this->configuration['internal'] ) )
			{
				return Url::internal( urldecode( $this->configuration['menu_custom_item_url'] ), 'front', $this->configuration['internal'], $this->configuration['seoTitles'] ?? array() );
			}
			else
			{
				return Url::external( urldecode( $this->configuration['menu_custom_item_url'] ) );
			}
		}
		else
		{
			return '#';
		}		
	}
	
	/**
	 * Get target (e.g. '_blank')
	 *
	 * @return	string
	 */
	public function target() : string
	{
		if ( isset( $this->configuration['menu_custom_item_target_blank'] ) and $this->configuration['menu_custom_item_target_blank'] )
		{
			return '_blank';
		}
		else
		{
			return '';
		}		
	}

	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return false;
	}
}