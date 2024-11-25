<?php
/**
 * @brief		5.0.0 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		31 May 2023
 */

namespace IPS\core\setup\upg_500001;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content;
use IPS\Content\Search\Index;
use IPS\core\AdminNotification;
use IPS\core\Advertisement;
use IPS\Db;
use IPS\Http\Url;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Upgrade Code
 */
class Upgrade
{
	/**
	 * Charts Menu
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Try and make sure My Charts is added to the top of the stats menu */
		foreach(Db::i()->select( '*', 'core_acp_tab_order' ) AS $order )
		{
			$data = json_decode( $order['data'], true );
			
			if ( isset( $data['stats'] ) )
			{
				foreach( $data['stats'] AS $k => $v )
				{
					if ( $v === '' OR $v === 'core_overview' )
					{
						unset( $data['stats'][ $k ] );
					}
				}
				
				array_unshift( $data['stats'], '', 'core_overview' ); // This looks weird but each entry in the order has a blank entry at the beginning, so preserve that.
			}
			
			Db::i()->update( 'core_acp_tab_order', array( 'data' => json_encode( $data ) ), array( "`id`=?", $order['id'] ) );
		}
		
		/* Set a flag to force ACP order cookies to be reset */
		/* The setting won't have been inserted yet at this point */
		Db::i()->replace( 'core_sys_conf_settings', array(
			'conf_key'		=> 'acp_menu_cookie_rebuild',
			'conf_value'	=> '1',
			'conf_default'	=> '0',
			'conf_app'		=> 'core'
		)	);

		return TRUE;
	}

	public function step2() : bool|array
	{
		/* Clean up status updates */
		Index::i()->removeClassFromSearchIndex( 'IPS\core\Statuses\Status' );
		Index::i()->removeClassFromSearchIndex( 'IPS\core\Statuses\Reply' );

		return TRUE;
	}


	/**
	 * Social promote / featured merging
	 */
	public function step3() : bool|array
	{
		/* And drop the internal flag, no longer necessary */
		try
		{
			/* Delete all social promotions that were not promoted internally */
			Db::i()->delete( 'core_content_promote', array( 'promote_internal=?', 0 ) );

			Db::i()->dropColumn( 'core_content_promote', 'promote_internal' );
		}
		catch( \Exception $e ) { }

		/* Now merge in the core_content_featured table which is for an easy achievements rebuild */
		$prefix = Db::i()->prefix;
		Db::i()->query( "INSERT INTO `{$prefix}core_content_promote` ( `promote_class`, `promote_class_id`, `promote_text`, `promote_media`, `promote_added`, `promote_added_by`, `promote_images`, `promote_form_data`, `promote_hide`, `promote_author_id`, `promote_short_link` ) SELECT `feature_content_class`, `feature_content_id`, '' as `promote_text`, '' as `promote_media`, feature_date, 0, '' AS `promote_images`, '' as `promote_form_data`, 0, feature_content_author, '' FROM `{$prefix}core_content_featured`" );

		/* And now drop the core_content_featured table - or should we wait until a 5.0.x release? */
		// Db::i()->dropTable('core_content_featured');

		return TRUE;
	}

	public function step4() : bool|array
	{
		/* Set the container ID for records in the approval queue */
		$rows = iterator_to_array(
			Db::i()->select( '*', 'core_approval_queue' )
		);

		foreach( $rows as $row )
		{
			$containerId = null;
			$class = $row['approval_content_class'];
			if( is_subclass_of( $class, 'IPS\Content\Comment' ) )
			{
				$itemClass = $class::$itemClass;
				if( isset( $itemClass::$databaseColumnMap['container'] ) )
				{
					try
					{
						$containerId = Db::i()->select( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'], $class::$databaseTable, array( $class::$databasePrefix . $class::$databaseColumnId . '=?', $row['approval_content_id'] ) )
							->join( $itemClass::$databaseTable, $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['item'] . '=' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnId )
							->first();
					}
					catch( UnderflowException ){}
				}
			}
			elseif( isset( $class::$databaseColumnMap['container'] ) )
			{
				try
				{
					$containerId = Db::i()->select( $class::$databasePrefix . $class::$databaseColumnMap['container'], $class::$databaseTable, array( $class::$databasePrefix . $class::$databaseColumnId . '=?', $row['approval_content_id'] ) )->first();
				}
				catch( UnderflowException ){}
			}

			if( $containerId )
			{
				Db::i()->update( 'core_approval_queue', array( 'approval_container_id' => $containerId ), array( 'approval_id=?', $row['approval_id'] ) );
			}
		}

		return TRUE;
	}

	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step5() : bool|array
	{
		/* Kill null tags */
		Db::i()->delete( 'core_tags', [ 'tag_text is null' ] );
		Db::i()->delete( 'core_tags_perms', [ 'tag_perm_aap_lookup not in (?)', Db::i()->select( 'tag_aap_lookup', 'core_tags' ) ] );
		Db::i()->delete( 'core_tags_cache', [ 'tag_cache_key not in (?)', Db::i()->select( 'tag_aai_lookup', 'core_tags' ) ] );

		$tagsToKeep = [];
		if( isset( $_SESSION['upgrade_options']['core']['200000']['open_tags'] ) )
		{
			if( $_SESSION['upgrade_options']['core']['200000']['open_tags'] == 'convert' )
			{
				/* Get the top 25 tags */
				$topTags = iterator_to_array(
					Db::i()->select( 'count(tag_id) as total, tag_text', 'core_tags', [ 'tag_text is not null' ], 'total desc', array( 0, 25 ), 'tag_text' )
						->setKeyField( 'tag_text' )
						->setValueField( 'total' )
				);

				$tagsToKeep = array_keys( $topTags );
			}
			elseif( $_SESSION['upgrade_options']['core']['200000']['open_tags'] == 'all' )
			{
				$tagsToKeep = iterator_to_array(
					Db::i()->select( 'distinct tag_text', 'core_tags', [ 'tag_text is not null' ] )
				);
			}
		}
		else
		{
			/* If we did not have this setting, then we have closed tagging, so we should convert all tags */
			$tagsToKeep = iterator_to_array(
				Db::i()->select( 'distinct tag_text', 'core_tags', [ 'tag_text is not null' ] )
			);
		}

		/* Check node-level tags */
		if( isset( $_SESSION['upgrade_options']['core']['200000']['node_tags'] ) )
		{
			$nodeTagMapping = [
				'cms' => [ 'table' => 'cms_databases', 'field' => 'database_tags_predefined' ],
				'downloads' => [ 'table' => 'downloads_categories', 'field' => 'ctags_predefined' ],
				'forums' => [ 'table' => 'forums_forums', 'field' => 'tag_predefined' ],
				'gallery' => [ 'table' => 'gallery_categories', 'field' => 'category_preset_tags' ]
			];

			if( $_SESSION['upgrade_options']['core']['200000']['node_tags'] == 'convert' )
			{
				foreach( $nodeTagMapping as $app => $data )
				{
					if( !Db::i()->checkForTable( $data['table'] ) )
					{
						continue;
					}

					foreach( Db::i()->select( $data['field'], $data['table'] ) as $tagList )
					{
						if( $tagList )
						{
							$tagsToKeep = array_merge( $tagsToKeep, explode( ",", $tagList ) );
						}
					}
				}
			}
		}

		/* If we have no tags to keep, just wipe the tables */
		if( !count( $tagsToKeep ) )
		{
			Db::i()->delete( 'core_tags' );
			Db::i()->delete( 'core_tags_perms' );
			Db::i()->delete( 'core_tags_cache' );
		}
		else
		{
			/* Queue for converting to new tags */
			$tagsToKeep = array_unique( $tagsToKeep );
			sort( $tagsToKeep );
			Task::queue( 'core', 'ConvertOpenTags', [ 'tags' => $tagsToKeep ], 4 );
		}

		return TRUE;
	}

	public function step6() : bool|array
	{
		/* send a notification if custom ad locations are present */

		$defaultLocations = [];
		$hasCustomLocations = FALSE;

		/* Now grab ad location extensions */
		foreach( Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
		{
			$result = $extension->getSettings( [] );

			$defaultLocations = array_merge( $defaultLocations, $result[ 'locations' ] );
		}

		$iterator = new ActiveRecordIterator( Db::i()->select( '*', 'core_advertisements' ), Advertisement::class );

		foreach( $iterator as $record )
		{
			$locations = explode( ',', $record->location );
			$customLocations = array_diff( $locations, array_keys( $defaultLocations ) );
			if( !empty( $customLocations ) )
			{
				$hasCustomLocations = TRUE;
				break;
			}
		}
		if( $hasCustomLocations )
		{
			AdminNotification::send( 'core', 'Custom', '', FALSE, NULL, FALSE,[
			'title' => 'Custom Ad Locations were removed',
			'body' => "Custom Ad Locations were removed in Invision Community 5.<br>Please <a href=" . Url::internal( 'app=core&module=promotion&controller=advertisements') . ">review</a> your advertisement and choose new locations if necessary."] );

		}

		return true;
	}

	public function step7() : bool|array
	{
		Task::queue( 'core', 'UpdateWidgetAreas', array(), 3 );
		return TRUE;
	}

	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}