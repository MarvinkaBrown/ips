<?php
/**
 * @brief		Front Navigation Extension: Pages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		01 Jul 2015
 */

namespace IPS\cms\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Categories;
use IPS\cms\Databases\Dispatcher;
use IPS\cms\Pages\Page;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Db;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Translatable;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfBoundsException;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Pages
 */
class Pages extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f15c';

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('menu_content_page');
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
	 * @param array $existingConfiguration The existing configuration, if editing an existing item
	 * @param int|null $id The ID number of the existing item, if editing
	 * @return    array
	 */
	public static function configuration(array $existingConfiguration, ?int $id = NULL ): array
	{
		$pages = array();
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'cms_pages' ), 'IPS\cms\Pages\Page' ) as $page )
		{
			$pages[ $page->id ] = $page->full_path;
		}
		
		return array(
			new Select( 'menu_content_page', $existingConfiguration['menu_content_page'] ?? NULL, NULL, array( 'options' => $pages ), NULL, NULL, NULL, 'menu_content_page' ),
			new Radio( 'menu_title_page_type', $existingConfiguration['menu_title_page_type'] ?? 0, NULL, array( 'options' => array( 0 => 'menu_title_page_inherit', 1 => 'menu_title_page_custom' ), 'toggles' => array( 1 => array( 'menu_title_page' ) ) ), NULL, NULL, NULL, 'menu_title_page_type' ),
			new Translatable( 'menu_title_page', NULL, NULL, array( 'app' => 'cms', 'key' => $id ? "cms_menu_title_{$id}" : NULL ), NULL, NULL, NULL, 'menu_title_page' ),
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
		if ( $configuration['menu_title_page_type'] )
		{
			Lang::saveCustom( 'cms', "cms_menu_title_{$id}", $configuration['menu_title_page'] );
		}
		else
		{
			Lang::deleteCustom( 'cms', "cms_menu_title_{$id}" );
		}
		
		unset( $configuration['menu_title_page'] );
		
		return $configuration;
	}
		
	/**
	 * Can access?
	 *
	 * @return    bool
	 */
	public function canView(): bool
	{
		if ( $this->permissions )
		{
			if ( $this->permissions != '*' )
			{
				return Member::loggedIn()->inGroup( explode( ',', $this->permissions ) );
			}
			
			return TRUE;
		}
		
		/* Inherit from page */
		$store = Page::getStore();

		if ( isset( $store[ $this->configuration['menu_content_page'] ] ) )
		{
			if ( $store[ $this->configuration['menu_content_page'] ]['perm'] != '*' )
			{
				return Member::loggedIn()->inGroup( explode( ',', $store[ $this->configuration['menu_content_page'] ]['perm'] ) );
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		if ( $this->configuration['menu_title_page_type'] )
		{
			return Member::loggedIn()->language()->addToStack( "cms_menu_title_{$this->id}" );
		}
		else
		{
			$page = Page::load( $this->configuration['menu_content_page'] );
			
			if( $database = $page->getDatabase() and $database->pageTitle() )
			{
				return $database->pageTitle();
			}
			else
			{
				return Member::loggedIn()->language()->addToStack( "cms_page_{$this->configuration['menu_content_page']}" );
			}	
		}
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		$store = Page::getStore();
		
		if ( isset( $store[ $this->configuration['menu_content_page'] ] ) )
		{
			return Url::external( $store[ $this->configuration['menu_content_page'] ]['url'] );
		}
		
		/* Fall back here */
		return Page::load( $this->configuration['menu_content_page'] )->url();
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		$page = Page::$currentPage;
		if ( !$page )
		{
			return false;
		}

		try
		{
			/* @var $categoryClass Categories */
			if ( $db = $page->getDatabase() and $db->allow_club_categories and $categoryClass = '\\IPS\\cms\\Categories' . $db->id and Dispatcher::i()->categoryId and $categoryClass::load( Dispatcher::i()->categoryId )->club() )
			{
				return false;
			}
		}
		catch ( OutOfRangeException|OutOfBoundsException ) {}

		return $page->id == $this->configuration['menu_content_page'];
	}
}