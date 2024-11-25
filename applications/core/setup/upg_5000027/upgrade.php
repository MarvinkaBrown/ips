<?php
/**
 * @brief		5.0.0 Beta 2 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		11 Oct 2024
 */

namespace IPS\core\setup\upg_5000027;

use IPS\Application;
use IPS\Db;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Beta 2 Upgrade Code
 */
class Upgrade
{
	/**
	 * Remove any invalid core menu rows for the core application, in our case the Promote extension
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		foreach( Application::applications() as $app )
		{
			$current = array_keys( $app->extensions( 'core', 'FrontNavigation', false ) );

			Db::i()->delete( 'core_menu', [
			[ 'app=?', $app->directory ],
			[ Db::i()->in( 'extension', $current, true ) ]
			] );
		}
		
		return TRUE;
	}

	public function step2() : bool|array
	{
		/* Remove old-style language strings for theme editor categories and settings */
		Db::i()->delete( 'core_sys_lang_words', "word_key regexp '^themeeditor\\_var\\_\\d'" );
		Db::i()->delete( 'core_sys_lang_words', "word_key regexp '^themeeditor\\_cat\\_\\d'" );

		return true;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}