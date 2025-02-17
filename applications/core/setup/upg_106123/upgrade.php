<?php
/**
 * @brief		4.6.5.1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		26 Jul 2021
 */

namespace IPS\core\setup\upg_106123;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Search\Index;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.6.5.1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Rebuild Elastic Search index
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		if ( isset( Settings::i()->search_method ) AND Settings::i()->search_method === 'elastic' )
		{
			Index::i()->rebuild();
		}

		return TRUE;
	}
}