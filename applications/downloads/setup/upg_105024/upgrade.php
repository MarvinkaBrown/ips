<?php
/**
 * @brief		4.5.0 Beta 7 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		08 Jul 2020
 */

namespace IPS\downloads\setup\upg_105024;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.5.0 Beta 7 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	array|bool	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : array|bool
	{
		Db::i()->query( "DELETE `" . Db::i()->prefix . "downloads_files_notify` FROM `" . Db::i()->prefix . "downloads_files_notify` LEFT JOIN `" . Db::i()->prefix . "downloads_files` ON (`" . Db::i()->prefix . "downloads_files`.file_id=`" . Db::i()->prefix . "downloads_files_notify`.notify_file_id AND `" . Db::i()->prefix . "downloads_files`.file_submitter=`" . Db::i()->prefix . "downloads_files_notify`.notify_member_id) WHERE `" . Db::i()->prefix . "downloads_files`.file_id IS NOT NULL" );

		return TRUE;
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle() : string
	{
		return "Cleaning up Downloads author notifications";
	}
}