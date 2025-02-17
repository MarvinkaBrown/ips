<?php
/**
 * @brief		4.7.10 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		15 May 2023
 */

namespace IPS\gallery\setup\upg_107683;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\gallery\Album;
use IPS\Task;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.10 Upgrade Code
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
		try
		{
			Db::i()->select( '*', 'gallery_albums', [ 'album_type!=?', Album::AUTH_TYPE_PUBLIC ], NULL, 1 )->first();
			Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\gallery\Album\Item' ), 5, TRUE );
		}
		catch( UnderflowException $e ) {}

		return TRUE;
	}
}