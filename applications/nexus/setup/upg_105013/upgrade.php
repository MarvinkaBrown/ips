<?php
/**
 * @brief		4.5.0 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		19 May 2020
 */

namespace IPS\nexus\setup\upg_105013;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.5.0 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
 	 * Update time based settings
 	 *
 	 * @return	bool|array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
 	 */
 	public function step1() : bool|array
 	{
 		Settings::i()->changeValues( array( 'cm_invoice_generate' => Settings::i()->cm_invoice_generate * 24, 'cm_invoice_warning' => Settings::i()->cm_invoice_warning * 24 ) );
 		return TRUE;
 	}

 	/**
 	 * Custom title for this step
 	 *
 	 * @return string
 	 */
 	public function step1CustomTitle() : string
 	{
 		return "Updating Settings";
 	}
}