<?php
/**
 * @brief		4.6.0 Beta 3 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		10 Jun 2021
 */

namespace IPS\core\setup\upg_106104;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Setup\Upgrade as UpgradeClass;
use IPS\Db;
use IPS\Http\Url;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.6.0 Beta 3 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* If we don't have the column, continue */
		if( !Db::i()->checkForColumn( 'core_members', 'badge_count' ) )
		{
			return TRUE;
		}

		$toRun = UpgradeClass::runManualQueries( array(
			array(
				'table' => 'core_members',
				'query' => " ALTER TABLE " . Db::i()->prefix . "core_members DROP COLUMN `badge_count`"
			)
		) );

		if ( count( $toRun ) )
		{
			UpgradeClass::adjustMultipleRedirect( array( 1 => 'core', 'extra' => array( '_upgradeStep' => 2 ) ) );

			/* Queries to run manually */
			return array( 'html' => Theme::i()->getTemplate( 'forms' )->queries( $toRun, Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => Request::i()->mr ) ) ) );
		}

		return TRUE;
	}
}