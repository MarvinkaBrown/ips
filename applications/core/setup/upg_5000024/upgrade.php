<?php
/**
 * @brief		5.0.0 Alpha 16 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		02 Oct 2024
 */

namespace IPS\core\setup\upg_5000024;

use IPS\Db;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Alpha 16 Upgrade Code
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
		/* Previous alpha builds removed this column, so add it if it doesn't exist */
		if( !Db::i()->checkForColumn( 'core_groups', 'g_topic_rate_setting' ) )
		{
			Db::i()->addColumn( 'core_groups', [
				'name' => 'g_topic_rate_setting',
				'type' => 'SMALLINT',
				'unsigned' => false,
				'default' => 0
			]);
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}