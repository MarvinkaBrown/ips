<?php
/**
 * @brief		5.0.0 Alpha 16 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		23 Sep 2024
 */

namespace IPS\cms\setup\upg_5000024;

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
		/* Make sure the assignment ID field is there */
		foreach( Databases::databases() as $db )
		{
			if( !Db::i()->checkForColumn( 'cms_custom_database_' . $db->id, 'record_assignment_id' ) )
			{
				Db::i()->addColumn( 'cms_custom_database_' . $db->id, [
					'name' => 'record_assignment_id',
					'type' => 'BIGINT',
					'length' => 20,
					'default' => 0,
					'unsigned' => false
				] );
			}

			/* Populate existing assignments */
			foreach( Db::i()->select( '*', 'core_assignments', [ 'assign_item_class=?', 'IPS\cms\Records' . $db->id ] ) as $row )
			{
				Db::i()->update( 'cms_custom_database_' . $db->id, [ 'record_assignment_id' => $row['assign_id'] ], [ 'primary_id_field=?', $row['assign_item_id'] ] );
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}