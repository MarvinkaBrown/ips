<?php
/**
 * @brief		4.7.15 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Nov 2023
 */

namespace IPS\core\setup\upg_107730;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member\Group;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.15 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
			$group = Group::load( Settings::i()->guest_group );
			$group->g_mod_post_unit = 0;
			$group->save();


		return TRUE;
	}

	/**
	 * Set the cache key
	 *
	 * @return true
	 */
	public function step2()
	{
		$manifest = json_decode( Settings::i()->manifest_details, TRUE );

		if( !isset( $manifest['cache_key'] ) )
		{
			$manifest['cache_key'] = time();
		}

		Settings::i()->changeValues( array( 'manifest_details' => json_encode( $manifest ) ) );

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}