<?php
/**
 * @brief		5.0.0 Alpha 14 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Sep 2024
 */

namespace IPS\core\setup\upg_5000022;

use IPS\Data\Store;
use IPS\Db;
use IPS\Login\Handler;
use IPS\Task\Queue\OutOfRangeException;
use UnderflowException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Alpha 14 Upgrade Code
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
		/* Remove orphaned data from plugins.
		At this point, the plugin-related columns have been removed,
		but the app columns would all be null. */
		Db::i()->delete( 'core_widgets', [ 'app is null' ] );
		Db::i()->delete( 'core_tasks', [ 'app is null'] );
		Db::i()->delete( 'core_sys_conf_settings', [ 'conf_app is null' ] );

		/* Remove sitemap setting if there's one */
		Db::i()->delete( 'core_sys_conf_settings', "conf_key IN( 'sitemap_last_ping' )" );

		return TRUE;
	}

	/**
	 * Handle custom login handlers
	 *
	 * @return bool|array
	 */
	public function step2() : bool|array
	{
		/* Disable any login handlers that are not returned by the default method */
		$validHandlers = Handler::handlerClasses();
		Db::i()->update( 'core_login_methods', [ 'login_enabled' => 0 ], Db::i()->in( 'login_classname', $validHandlers, true ) );

		/* Make sure we still have one handler enabled */
		try
		{
			$enabled = Db::i()->select( '*', 'core_login_methods', [ 'login_enabled=?', 1 ] )->first();
		}
		catch( UnderflowException )
		{
			/* If we have nothing enabled, enable the default internal login handler */
			Db::i()->update( 'core_login_methods', [ 'login_enabled' => 1 ], [ 'login_classname=?', 'IPS\Login\Handler\Standard' ] );
		}

		/* Drop the cache */
		try
		{
			unset( Store::i()->loginMethods );
		}
		catch( OutOfRangeException ){}

		return true;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}