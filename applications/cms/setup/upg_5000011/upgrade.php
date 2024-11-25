<?php
/**
 * @brief		5.0.0 Alpha 8 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		31 Jul 2024
 */

namespace IPS\cms\setup\upg_5000011;

use IPS\cms\Databases;
use IPS\Db;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Alpha 8 Upgrade Code
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
		/* Add the offset field for each existing database */
		foreach( Databases::databases() as $db )
		{
			if( !Db::i()->checkForColumn( 'cms_custom_database_' . $db->id, 'record_image_offset' ) )
			{
				Db::i()->addColumn( 'cms_custom_database_' . $db->id, [
					'name' => 'record_image_offset',
					'type' => 'INT',
					'default' => 0
				] );
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}