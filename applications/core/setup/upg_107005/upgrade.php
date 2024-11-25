<?php
/**
 * @brief		4.7.0 Beta 4 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		25 May 2022
 */

namespace IPS\core\setup\upg_107005;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\DataLayer;
use IPS\core\DataLayer\Handler;
use IPS\Data\Store;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.0 Beta 4 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
	{
		/* Remove cached configuration for Data Layer and Data Layer Handlers */
		DataLayer::i()->clearCachedConfiguration();
		$key = Handler::$handlerCacheKey;
		unset( Store::i()->$key );

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}