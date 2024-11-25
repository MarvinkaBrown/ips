<?php
/**
 * @brief		4.5.0 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		15 Oct 2019
 */

namespace IPS\forums\setup\upg_105013;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Settings;
use IPS\Task;
use UnderflowException;
use function defined;
use const IPS\CIC;

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
	 * ...
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* Make sure we have the table first */
		if( !Db::i()->checkForTable('forums_rss_import') )
		{
			return TRUE;
		}

		/* Move over forums_rss_import to core_rss_import */
		foreach( Db::i()->select( '*', 'forums_rss_import' ) as $rss )
		{
			$newImportId = Db::i()->insert( 'core_rss_import', array(
				'rss_import_enabled' => $rss['rss_import_enabled'],
				'rss_import_title' => $rss['rss_import_title'],
				'rss_import_url' => $rss['rss_import_url'],
				'rss_import_auth_user' => $rss['rss_import_auth_user'],
				'rss_import_auth_pass' => $rss['rss_import_auth_pass'],
				'rss_import_class' => 'IPS\\forums\\Topic',
				'rss_import_node_id' => $rss['rss_import_forum_id'],
				'rss_import_member' => $rss['rss_import_mid'],
				'rss_import_time' => $rss['rss_import_time'],
				'rss_import_last_import' => $rss['rss_import_last_import'],
				'rss_import_showlink' => $rss['rss_import_showlink'],
				'rss_import_topic_pre' => $rss['rss_import_topic_pre'],
				'rss_import_auto_follow' => $rss['rss_import_auto_follow'],
				'rss_import_settings' => json_encode( array(
					'topic_open' => $rss['rss_import_topic_open'],
					'topic_hide' => $rss['rss_import_topic_hide']
				) )
			) );
			
			try 
			{
				/* Prevent multiple runs from breaking */
				Db::i()->delete( 'core_rss_imported', array( 'rss_imported_import_id=?', $newImportId ) );
				
				Db::i()->query( "INSERT INTO " . Db::i()->prefix . "core_rss_imported
					(rss_imported_guid, rss_imported_content_id, rss_imported_import_id )
					( SELECT a.rss_imported_guid, a.rss_imported_tid, {$newImportId} FROM " . Db::i()->prefix . "forums_rss_imported a WHERE a.rss_imported_impid={$rss['rss_import_id']})" );
			}
			catch( Exception $e ) { }
		}

		return TRUE;
	}
	
	/**
	 * Finish
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
	{
		/* Delete old tasks */
		Db::i()->delete( 'core_tasks', array( 'app=? and `key`=?', 'forums', 'rssimport' ) );
		
		/* Delete old template */
		Db::i()->delete( 'core_theme_templates', array( 'template_app=? and template_name=?', 'forums', 'importPreview' ) );
		
		/* Delete old language strings */
		Db::i()->delete( 'core_sys_lang_words', array( 'word_app=? and ' . Db::i()->in( 'word_key', array(
			'r__rss',
			'r__rss_manage',
			'r__rss_add',
			'r__rss_edit',
			'r__rss_delete',
			'r__rss_run',
			'task__rssimport',
			'rssimport_task_error',
		 ) ), 'forums' ) );

		/* Delete old RSS Table */
		if( Db::i()->checkForTable('forums_rss_import') )
		{
			Db::i()->dropTable( array( 'forums_rss_import', 'forums_rss_imported' ) );
		}
		
		/* Kick off solve index build */
		Task::queue( 'forums', 'RebuildSolvedIndex', array(), 4 );
		
		/* Insert forums_Cards storage extension */
		$settings = json_decode( Settings::i()->upload_settings, TRUE );
		
		try
		{
			if ( CIC )
			{
				$configurationId = Db::i()->select( 'id', 'core_file_storage', array( "method=?", 'Amazon' ), "id ASC" )->first();
			}
			else
			{
				$configurationId = Db::i()->select( 'id', 'core_file_storage', array( "method=?", 'FileSystem' ), "id ASC" )->first();
			}
		}
		catch( UnderflowException $e )
		{
			$configurationId = Db::i()->select( 'id', 'core_file_storage', NULL, "id ASC" )->first();
		}
		
		$settings['filestorage__forums_Cards'] = $configurationId;
		Settings::i()->changeValues( array( 'upload_settings' => json_encode( $settings ) ) );

		return TRUE;
	}
}