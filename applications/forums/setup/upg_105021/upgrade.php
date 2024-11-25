<?php
/**
 * @brief		4.5.0 Beta 5 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		25 Jun 2020
 */

namespace IPS\forums\setup\upg_105021;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use function defined;
use function IPS\Cicloud\getForcedArchiving;
use const IPS\CIC2;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.5.0 Beta 5 Upgrade Code
 */
class Upgrade
{
	/**
	 * Auto-enable archiving on CIC2 if appropriate
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* Auto-enable post archiving if the community is CIC2 and has more than 255K posts */
		if ( CIC2 AND getForcedArchiving() )
		{
			/* Make sure archiving is on */
			Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'archive' ) );
		}

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Adjusting archiving options";
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}