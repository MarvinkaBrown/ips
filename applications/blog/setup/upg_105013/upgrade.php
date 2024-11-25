<?php
/**
 * @brief		4.5.0 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blogs
 * @since		01 Aug 2019
 */

namespace IPS\blog\setup\upg_105013;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\blog\Blog;
use IPS\blog\Category;
use IPS\Db;
use IPS\Lang;
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
	 * Set up categories
	 *
	 * @return	bool|array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$category = new Category;
		$category->seo_name = 'general';
		$category->save();

		Lang::saveCustom( 'blog', "blog_category_{$category->id}", "General" );

		return TRUE;
	}
	
	/**
	 * Convert RSS imports over
	 *
	 * @return	bool|array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2() : bool|array
	{
		/* Make sure we have the table first */
		if( !Db::i()->checkForTable('blog_rss_import') )
		{
			return TRUE;
		}

		/* Move over blog_rss_import to core_rss_import */
		foreach( Db::i()->select( '*', 'blog_rss_import' ) as $rss )
		{
			try
			{
				$blog = Blog::load( $rss['rss_blog_id'] );
			}
			catch( Exception $e )
			{
				continue;
			}
			
			$newImportId = Db::i()->insert( 'core_rss_import', array(
				'rss_import_enabled' => 1,
				'rss_import_title' => $blog->titleForLog(),
				'rss_import_url' => $rss['rss_url'],
				'rss_import_auth_user' => $rss['rss_auth_user'],
				'rss_import_auth_pass' => $rss['rss_auth_pass'],
				'rss_import_class' => 'IPS\\blog\\Entry',
				'rss_import_node_id' => $rss['rss_blog_id'],
				'rss_import_member' => $rss['rss_member'],
				'rss_import_time' => 0,
				'rss_import_last_import' => $rss['rss_last_import'],
				'rss_import_showlink' => $rss['rss_import_show_link'] ?? '',
				'rss_import_topic_pre' => '',
				'rss_import_auto_follow' => 0,
				'rss_import_settings' => json_encode( array(
					'tags' => $rss['rss_tags']
				) )
			) );
			
			/* Prevent multiple runs from breaking */
			try 
			{
				Db::i()->delete( 'core_rss_imported', array( 'rss_imported_import_id=?', $newImportId ) );
				
				Db::i()->query( "INSERT INTO " . Db::i()->prefix . "core_rss_imported
					(rss_imported_guid, rss_imported_content_id, rss_imported_import_id )
					( SELECT a.rss_imported_guid, a.rss_imported_entry_id, {$newImportId} FROM " . Db::i()->prefix . "blog_rss_imported a)" );
			}
			catch( Exception $e ) { }
		}

		return TRUE;
	}
	
	/**
	 * Finish
	 *
	 * @return	bool|array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish() : bool|array
	{
		/* Delete old language strings */
		Db::i()->delete( 'core_tasks', array( '`key`=?', 'blogrssimport' ) );

		/* Delete old RSS Table */
		if( Db::i()->checkForTable('blog_rss_import') )
		{
			Db::i()->dropTable( array( 'blog_rss_import', 'blog_rss_imported' ) );
		}

		return TRUE;
	}
}