<?php
/**
 * @brief		4.6.0 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Jun 2020
 */

namespace IPS\core\setup\upg_106100;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Rank;
use IPS\core\Achievements\Rule;
use IPS\core\ProfileFields\Field;
use IPS\core\ProfileFields\Group;
use IPS\core\Feature;
use IPS\core\Setup\Upgrade as UpgradeClass;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Notification;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use function call_user_func_array;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.6.0 Beta 1 Upgrade Code
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
		$json = <<<JSON
[
	{
	"method": "changeIndex",
        "params": [
		"core_search_index",
		"item",
				{
					"type": "key",
					"name": "item",
					"columns": [
					"index_class",
					"index_item_id",
					"index_is_last_comment"
				],
                "length": [
				null,
				null,
				null
			]
            }
        ]
    }
]
JSON;

		$queries = json_decode( $json, TRUE );

		foreach( $queries as $query )
		{
			try
			{
				$run = call_user_func_array( array( Db::i(), $query['method'] ), $query['params'] );
			}
			catch( DbException $e )
			{
				if( !in_array( $e->getCode(), array( 1007, 1008, 1050, 1060, 1061, 1062, 1091, 1051 ) ) )
				{
					throw $e;
				}
			}
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
		return "Optimizing search index";
	}
	
	/**
 	 * Reset the Emoji cache (we added new emoji support)
 	 *
 	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
 	 */
 	public function step2()
 	{
 		Settings::i()->changeValues( array( 'emoji_cache' => time() ) );
		 
		return TRUE;
 	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step2CustomTitle()
	{
		return "Resetting the emoji cache";
	}

	/**
 	 * Rebuild image proxy in report center comments
 	 *
 	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
 	 */
 	public function step3()
 	{
 		if( Db::i()->select( 'count(*)', 'core_rc_comments', array( Db::i()->like( 'comment', 'imageproxy.php' ) ) ) )
 		{
 			unset( Store::i()->currentImageProxyRebuild );
 			Task::queue( 'core', 'RebuildImageProxyNonContent', array( 'extension' => 'core_Reports' ), 4, array( 'extension' ) );
 		}

 		return TRUE;
 	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step3CustomTitle()
	{
		return "Removing image proxy from report center comments";
	}

	/**
 	 * Generate vapid keys for web push notifications
 	 *
 	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
 	 */
	public function step4()
	{
		/* It is likely this will be attempted before settings are created */
		$currentDefaults = iterator_to_array( Db::i()->select( '*', 'core_sys_conf_settings' )->setKeyField('conf_key')->setValueField('conf_default') );

		foreach ( array(
					  array( 'key' => 'vapid_public_key', 'default' => '' ),
					  array( 'key' => 'vapid_private_key', 'default' => '' ) ) as $setting )
		{
			if ( ! array_key_exists( $setting['key'], $currentDefaults ) )
			{
				Db::i()->insert( 'core_sys_conf_settings', array( 'conf_key' => $setting['key'], 'conf_value' => $setting['default'], 'conf_default' => $setting['default'], 'conf_app' => 'core' ), TRUE );
			}
		}

		/* Generate VAPID keys for web push notifications */
		try 
		{
			$vapid = Notification::generateVapidKeys();
			Settings::i()->changeValues( array( 'vapid_public_key' => $vapid['publicKey'], 'vapid_private_key' => $vapid['privateKey'] ) );
		}
		catch (Exception $ex)
		{
			Log::log( $ex, 'create_vapid_keys' );
		}

		return TRUE;
	}
 
	 /**
	  * Custom title for this step
	  *
	  * @return string
	  */
	 public function step4CustomTitle()
	 {
		 return "Generating keys for web push notifications";
	 }

	/**
	 * Populate new fields in the content_meta table for recommended posts
	 *
	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step5()
	{
		$perCycle = 500;
		$limit    = isset( Request::i()->extra ) ? Request::i()->extra : 0;
		$did      = 0;

		/* Make sure we have included Application.php files, since Pages has its own autoloader, we need to define it */
		Application::applications();

		foreach( Db::i()->select( '*', 'core_content_meta', [ 'meta_type=?', 'core_FeaturedComments' ], 'meta_id ASC', [ $limit, $perCycle ] ) as $row )
		{
			$did++;
			$class = $row['meta_class'];
			$data = json_decode( $row['meta_data'], TRUE );

			if ( ! empty( $data['comment'] ) )
			{
				try
				{
					$commentClass = $class::$commentClass;
					$comment = $commentClass::load( $data['comment'] );
					Db::i()->update( 'core_content_meta', [
						'meta_item_author' => $comment->author()->member_id,
						'meta_added' => $comment->mapped('date')
					],
						[ 'meta_id=?', $row['meta_id'] ] );
				}
				catch( Exception $e ){ }
			}
		}

		return ( $did ) ? ( $limit + $did ) : TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step5CustomTitle()
	{
		$limit = isset( Request::i()->extra ) ? Request::i()->extra : 0;
		return "Updating recommended content ({$limit} processed so far)";
	}

	/**
	 * Populate new fields in the core_social_promote table for promoted posts
	 *
	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step6()
	{
		$perCycle = 500;
		$limit    = isset( Request::i()->extra ) ? Request::i()->extra : 0;
		$did      = 0;

		foreach( Db::i()->select( '*', 'core_social_promote', NULL, 'promote_id ASC', [ $limit, $perCycle ] ) as $row )
		{
			$did++;

			if ( ! class_exists( $row['promote_class'] ) )
			{
				continue;
			}

			try
			{
				$promote = Feature::constructFromData( $row );
				$promote->author_id = $promote->objectAuthor->member_id;
				$promote->save();
			}
			catch( Exception $e ){ }
		}

		return ( $did ) ? ( $limit + $did ) : TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step6CustomTitle()
	{
		$limit = isset( Request::i()->extra ) ? Request::i()->extra : 0;
		return "Updating promoted content ({$limit} processed so far)";
	}

	/**
	 * Set up background tasks for adding extra data to featured columns
	 *
	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step7()
	{
		foreach ( Application::allExtensions( 'core', 'ContentRouter' ) as $extension )
		{
			foreach ( $extension->classes as $class )
			{
				if ( isset( $class::$databaseColumnMap['featured'] ) and isset( $class::$databaseColumnMap['author'] ) )
				{
					try
					{
						Task::queue( 'core', 'Upgrade46FeaturedContent', array( 'class' => $class ), 2 );
					}
					catch( Exception $e ) { }
				}
			}
		}

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step7CustomTitle()
	{
		return "Setting up tasks to update featured content";
	}
	
	/**
	 * Set up background tasks for adding extra data to featured columns
	 *
	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step8()
	{
		$core = Application::load('core');

		/* Manually installed badges are good for recognize system and aren't tied to rules */
		Badge::importXml( $core->getApplicationPath() . "/data/achievements/badges.xml" );
		
		if ( $_SESSION['upgrade_options']['core']['106000']["rule_option"] == 'new' )
		{
			Rule::importXml( $core->getApplicationPath() . "/data/achievements/rules.xml", TRUE );
			Rank::importXml( $core->getApplicationPath() . "/data/achievements/ranks.xml", 'wipe' );
		}
		else
		{
			Db::i()->insert( 'core_achievements_rules', [
				'action' => 'core_Comment',
				'filters' => NULL,
				'milestone' => 0,
				'points_subject' => 1,
				'points_other' => 0,
				'badge_subject' => 0,
				'badge_other' => 0,
				'enabled' => 1
			]);
			
			Db::i()->insert( 'core_achievements_rules', [
				'action' => 'core_NewContentItem',
				'filters' => NULL,
				'milestone' => 0,
				'points_subject' => 1,
				'points_other' => 0,
				'badge_subject' => 0,
				'badge_other' => 0,
				'enabled' => 1
			]);

			/* Reset the member's achievement points to the same number of posts to retain existing rank position */
			Db::i()->update( 'core_members', "achievements_points=member_posts" );

			/* Set the rebuild date so the badge times are not from 1970 */
			Settings::i()->changeValues( array( 'achievements_last_rebuilt' => time() ) );
		}

		return TRUE;
	}


	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step8CustomTitle()
	{
		return "Setting up ranks";
	}

	/**
	 * Set up background tasks for adding extra data to featured columns
	 *
	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step9()
	{
		$group = new Group;
		$group->save();

		Lang::saveCustom( 'core', "core_pfieldgroups_{$group->id}", 'Retained' );

		/* Create the about me profile field */
		$memberTitleField	= new Field;
		$memberTitleField->group_id		= $group->id;
		$memberTitleField->type			= "Text";
		$memberTitleField->content		= NULL;
		$memberTitleField->not_null		= FALSE;
		$memberTitleField->max_input	= 255;
		$memberTitleField->input_format	= NULL;
		$memberTitleField->search_type	= "loose";
		$memberTitleField->format		= NULL;
		$memberTitleField->admin_only	= FALSE;
		$memberTitleField->show_on_reg	= FALSE;
		$memberTitleField->member_edit	= FALSE;
		$memberTitleField->member_hide	= 'hide';

		try
		{
			$memberTitleField->save();
			Lang::saveCustom( 'core', 'core_pfield_' . $memberTitleField->id, "Member Title" );
			Lang::saveCustom( 'core', 'core_pfield_' . $memberTitleField->id . '_desc', "" );
			$_SESSION['106100-TITLE-FIELD'] = $memberTitleField->id;
		}
		catch( Exception $ex )
		{
			Log::log( $ex, 'upgrade' );
		}
		
		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step9CustomTitle()
	{
		return "Preserving member titles";
	}

	/**
	 * Move member titles
	 *
	 * @return	boolean	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step10()
	{
		if( empty( $_SESSION['106100-TITLE-FIELD'] ) )
		{
			return TRUE;
		}

		$toRun = UpgradeClass::runManualQueries( array( array(
			'table' => 'core_members',
			'query' => "UPDATE " . Db::i()->prefix . "core_pfields_content c INNER JOIN " . Db::i()->prefix . "core_members m ON m.member_id=c.member_id SET c.field_" . $_SESSION['106100-TITLE-FIELD'] . "=m.member_title WHERE LENGTH(m.member_title) > 0"
		) ) );

		if ( count( $toRun ) )
		{
			UpgradeClass::adjustMultipleRedirect( array( 1 => 'core', 'extra' => array( '_upgradeStep' => 11 ) ) );

			/* Queries to run manually */
			return array( 'html' => Theme::i()->getTemplate( 'forms' )->queries( $toRun, Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => Request::i()->mr ) ) ) );
		}

		return TRUE;
	}

	/**
	 * Finish step
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
	{
		if( isset( Settings::i()->search_method ) AND Settings::i()->search_method == 'mysql' )
		{
			Index::i()->rebuild();
		}

		return TRUE;
	}
}