<?php
/**
 * @brief		{version_human} Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		14 Aug 2024
 */

namespace IPS\core\setup\upg_5000015;

use IPS\core\FrontNavigation;
use IPS\Db;
use IPS\Data\Store;
use IPS\Lang;
use IPS\Member;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * {version_human} Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Modify root-level menu items that have children */
		$rootLevelItems = iterator_to_array(
			Db::i()->select( '*', 'core_menu', [ 'parent is null or parent=?', 0 ] )
		);
		foreach( $rootLevelItems as $root )
		{
			/* Does this have children? */
			$children = iterator_to_array(
				Db::i()->select( '*', 'core_menu', [ 'parent=?', $root['id'] ] )
			);

			if( count( $children ) )
			{
				Db::i()->update( 'core_menu', [
					'app' => 'core',
					'extension' => 'Menu',
					'config' => '[]',
					'permissions' => null
				], [ 'id=?', $root['id'] ] );

				/* Create the language string */
				$class = 'IPS\\' . $root['app'] . '\extensions\core\FrontNavigation\\' . $root['extension'];
				if( class_exists( $class ) )
				{
					/* @var FrontNavigation\FrontNavigationAbstract $class */
					$obj = new $class( json_decode( (string) $root['config'], true ), $root['id'], $root['permissions'], $root['menu_types'], json_decode( (string) $root['icon'], true ), $root['parent'] );
					$title = $obj->title();
					Member::loggedIn()->language()->parseOutputForDisplay( $title );
					Lang::saveCustom( 'core', 'menu_item_' . $root['id'], $title );
				}

				foreach( $children as $child )
				{
					Db::i()->update( 'core_menu', [
						'is_menu_child' => ( $child['app'] == 'core' and $child['extension'] == 'Menu' ) ? 0 : 1
					], [ 'id=?', $child['id'] ] );
				}
			}
		}

		/* Drop the cache */
		try
		{
			unset( Store::i()->frontNavigation );
		}
		catch( OutOfRangeException ){}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}