<?php
/**
 * @brief		5.0.0 Alpha 3 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		02 Jul 2024
 */

namespace IPS\core\setup\upg_5000003;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Alpha 3 Upgrade Code
 */
class Upgrade
{
	/**
	 * Remove CommunityHive Widget
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\Widget::deprecateWidget('communityhive', 'core' );

		return TRUE;
	}

	public function step2() : bool|array
	{
		if( Db::i()->checkForColumn( 'core_widget_areas', 'template' ) )
		{
			Db::i()->dropColumn( 'core_widget_areas', 'template' );
		}

		return true;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}