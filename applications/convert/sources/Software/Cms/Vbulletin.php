<?php

/**
 * @brief		Converter vBulletin 4.x Pages Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\cms\Databases;
use IPS\convert\App;
use IPS\convert\Software;
use IPS\convert\Software\Core\Vbulletin as VbulletinClass;
use IPS\convert\Software\Exception;
use IPS\Db;
use IPS\Member;
use IPS\Task;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * vBulletin Pages Converter
 */
class Vbulletin extends Software
{
	/**
	 * @brief	The schematic for vB3 and vB4 is similar enough that we can make specific concessions in a single converter for either version.
	 */
	protected static ?bool $isLegacy				= NULL;

	/**
	 * @brief	Cached article content type
	 */
	protected static mixed $_articleContentType	= NULL;

	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "vBulletin CMS (4.x only)";
	}
	
	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "vbulletin";
	}

	/**
	 * Constructor
	 *
	 * @param	App	$app	The application to reference for database and other information.
	 * @param	bool				$needDB	Establish a DB connection
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public function __construct( App $app, bool $needDB=TRUE )
	{
		parent::__construct( $app, $needDB );

		/* Is this vB3 or vB4? */
		try
		{
			if ( static::$isLegacy === NULL AND $needDB )
			{
				$version = $this->db->select('value', 'setting', array("varname=?", 'templateversion'))->first();

				if (mb_substr($version, 0, 1) == '3') {
					static::$isLegacy = TRUE;
				} else {
					static::$isLegacy = FALSE;
				}
			}

			/* If this is vB4, what is the content type ID for the cms? */
			if ( static::$_articleContentType === NULL AND ( static::$isLegacy === FALSE OR is_null( static::$isLegacy ) ) AND $needDB )
			{
				try
				{
					static::$_articleContentType = $this->db->select( 'contenttypeid', 'contenttype', array( "class=?", 'Article' ) )->first();
				}
				catch( UnderflowException $e )
				{
					static::$_articleContentType = 24; # default
				}
			}

		}
		catch( \Exception $e ) {}
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertCmsBlocks'				=> array(
				'table'								=> 'cms_widget',
				'where'								=> NULL,
			),
			'convertCmsPages'				=> array(
				'table'								=> 'page',
				'where'								=> NULL,
			),
			'convertCmsDatabases'			=> array(
				'table'								=> 'database',
				'where'								=> NULL,
			),
			'convertCmsDatabaseCategories'	=> array(
				'table'								=> 'cms_category',
				'where'								=> NULL,
			),
			'convertCmsDatabaseRecords'		=> array(
				'table'								=> 'cms_article',
				'where'								=> NULL,
			),
			'convertAttachments'			=> array(
 				'table'								=> 'attachment',
 				'where'								=> array( "contenttypeid=?", static::$_articleContentType )
		 			)
		);
	}

	/**
	 * Count Source Rows for a specific step
	 *
	 * @param string $table		The table containing the rows to count.
	 * @param string|array|NULL $where		WHERE clause to only count specific rows, or NULL to count all.
	 * @param bool $recache	Skip cache and pull directly (updating cache)
	 * @return    integer
	 * @throws	\IPS\convert\Exception
	 */
	public function countRows( string $table, string|array|null $where=NULL, bool $recache=FALSE ): int
	{
		switch( $table )
		{
			case 'cms_widget':
				try
				{
					$blocksWeCanConvert = array();
					foreach( $this->db->select( 'widgettypeid', 'cms_widgettype', array( Db::i()->in( 'class', array( 'Rss', 'Static' ) ) ) ) AS $typeid )
					{
						$blocksWeCanConvert[] = $typeid;
					}
					return $this->db->select( 'COUNT(*)', 'cms_widget', array( $this->db->in( 'widgettypeid', $blocksWeCanConvert ) ) )->first();
				}
				catch( \Exception $e )
				{
					throw new \IPS\convert\Exception( sprintf( Member::loggedIn()->language()->get( 'could_not_count_rows' ), $table ) );
				}
			
			case 'page':
			case 'database':
				return 1;

			default:
				return parent::countRows( $table, $where, $recache );
		}
	}

	/**
	 * Requires Parent
	 *
	 * @return    boolean
	 */
	public static function requiresParent(): bool
	{
		return TRUE;
	}
	
	/**
	 * Possible Parent Conversions
	 *
	 * @return    array|null
	 */
	public static function parents(): ?array
	{
		return array( 'core' => array( 'vbulletin' ) );
	}

	/**
	 * Pre-process content for the Invision Community text parser
	 *
	 * @param	string			$post post
	 * @param	string|null		$className Classname passed by post-conversion rebuild
	 * @param	int|null		$contentId ID passed by post-conversion rebuild
	 * @return	string			The converted post
	 */
	public static function fixPostData( string $post, ?string $className=null, ?int $contentId=null ): string
	{
		return VbulletinClass::fixPostData( $post, $className, $contentId );
	}

	/**
	 * Get Setting Value - useful for global settings that need to be translated to group or member settings
	 *
	 * @param	string	$key	The setting key
	 * @return	mixed
	 */
	protected function _setting( string $key ) : mixed
	{
		if ( isset( $this->settingsCache[$key] ) )
		{
			return $this->settingsCache[$key];
		}
		
		try
		{
			$setting = $this->db->select( 'value, defaultvalue', 'setting', array( "varname=?", $key ) )->first();
			
			if ( $setting['value'] )
			{
				$this->settingsCache[$key] = $setting['value'];
			}
			else
			{
				$this->settingsCache[$key] = $setting['defaultvalue'];
			}
		}
		catch( UnderflowException $e )
		{
			/* If we failed to find it, we probably will fail again on later attempts */
			$this->settingsCache[$key] = NULL;
		}
		
		return $this->settingsCache[$key];
	}

	/**
	 * Convert CMS blocks
	 *
	 * @return	void
	 */
	public function convertCmsBlocks() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'widgetid' );
		
		/* We CAN bring over some blocks, like static widgets */
		$blocksWeCanConvert = array();
		$rssTypeId			= NULL;
		foreach( $this->db->select( 'widgettypeid, class', 'cms_widgettype', array( Db::i()->in( 'class', array( 'Rss', 'Static' ) ) ) ) AS $type )
		{
			if ( $type['class'] == 'Rss' )
			{
				$rssTypeId = $type['widgettypeid'];
			}
			$blocksWeCanConvert[] = $type['widgettypeid'];
		}
		
		foreach( $this->fetch( 'cms_widget', 'widgetid', array( Db::i()->in( 'widgettypeid', $blocksWeCanConvert ) ) ) AS $block )
		{
			$config = array();

			foreach( $this->db->select( 'name, value', 'cms_widgetconfig', array( "widgetid=?", $block['widgetid'] ) ) as $_c )
			{
				$config[ $_c['name'] ] = $_c['value'];
			}
			
			$info = array(
				'block_id'				=> $block['widgetid'],
				'block_name'			=> $block['title'],
				'block_description'		=> $block['description'],
				'block_plugin'			=> ( $block['widgettypeid'] == $rssTypeId ) ? 'Rss' : NULL,
				'block_plugin_config'	=> ( $block['widgettypeid'] == $rssTypeId ) ? array(
					'block_rss_import_title'	=> $block['title'],
					'block_rss_import_url'		=> $config['url'],
					'block_rss_import_number'	=> $config['max_items'],
					'block_rss_import_cache'	=> 30,
					'block_type'				=> 'plugin',
					'block_editor'				=> 'html',
					'block_plugin'				=> 'Rss',
					'block_plugin_app'			=> 'cms',
					'template_params'			=> '',
					'type'						=> 'plugin',
					'plugin_app'				=> 'cms',
				) : NULL,
				'block_content'			=> ( $block['widgettypeid'] != $rssTypeId AND !empty( $config['statichtml'] ) ) ? $config['statichtml'] : NULL,
			);
			
			$libraryClass->convertCmsBlock( $info );
			
			$libraryClass->setLastKeyValue( $block['widgetid'] );
		}
	}
	
	/**
	 * Create a CMS page
	 *
	 * @return	void
	 */
	public function convertCmsPages() : void
	{
		$this->getLibrary()->convertCmsPage( array(
			'page_id'		=> 1,
			'page_name'		=> 'vBulletin Articles',
		) );
		
		throw new Exception;
	}
	
	/**
	 * Create a database
	 *
	 * @return	void
	 */
	public function convertCmsDatabases() : void
	{
		$convertedForums = FALSE;
		try
		{
			$this->app->checkForSibling( 'forums' );
			
			$convertedForums = TRUE;
		}
		catch( OutOfRangeException $e ) {}
		$this->getLibrary()->convertCmsDatabase( array(
			'database_id'				=> 1,
			'database_name'				=> 'vBulletin Articles',
			'database_sln'				=> 'article',
			'database_pln'				=> 'articles',
			'database_scn'				=> 'Article',
			'database_pcn'				=> 'Articles',
			'database_ia'				=> 'an article',
			'database_record_count'		=> $this->db->select( 'COUNT(*)', 'cms_article' )->first(),
			'database_tags_enabled'		=> 1,
			'database_forum_record'		=> ( $convertedForums ) ? 1 : 0,
			'database_forum_comments'	=> ( $convertedForums ) ? 1 : 0,
			'database_forum_delete'		=> ( $convertedForums ) ? 1 : 0,
			'database_forum_prefix'		=> ( $convertedForums ) ? 'Article: ' : '',
			'database_forum_forum'		=> ( $convertedForums ) ? $this->_setting( 'vbcmsforumid' ) : 0,
			'database_page_id'			=> 1,
		), array(
			array(
				'field_id'				=> 1,
				'field_type'			=> 'Text',
				'field_name'			=> 'Title',
				'field_key'				=> 'article_title',
				'field_required'		=> 1,
				'field_user_editable'	=> 1,
				'field_position'		=> 1,
				'field_display_listing'	=> 1,
				'field_display_display'	=> 1,
				'field_is_title'		=> TRUE,
			),
			array(
				'field_id'				=> 2,
				'field_type'			=> 'Editor',
				'field_name'			=> 'Content',
				'field_key'				=> 'article_content',
				'field_required'		=> 1,
				'field_user_editable'	=> 1,
				'field_position'		=> 2,
				'field_display_listing'	=> 0,
				'field_display_display'	=> 1,
				'field_is_content'		=> TRUE
			)
		) );
		
		throw new Exception;
	}

	/**
	 * Convert CMS database categories
	 *
	 * @return	void
	 */
	public function convertCmsDatabaseCategories() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'categoryid' );
		
		foreach( $this->fetch( 'cms_category', 'categoryid' ) AS $row )
		{
			$libraryClass->convertCmsDatabaseCategory( array(
				'category_id'			=> $row['categoryid'],
				'category_database_id'	=> 1,
				'category_name'			=> $row['category'],
				'category_desc'			=> $row['description'],
				'category_position'		=> $row['catleft'],
				'category_fields'		=> array( 'article_title', 'article_content' )
			) );
			
			$libraryClass->setLastKeyValue( $row['categoryid'] );
		}
	}
	
	/**
	 * Convert CMS database records
	 *
	 * @return	void
	 */
	public function convertCmsDatabaseRecords() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'contentid' );

		$cmsCategories = iterator_to_array( $this->db->select( 'category, categoryid', 'cms_category' )->setKeyField( 'categoryid' )->setValueField( 'category' ) );

		foreach( $this->fetch( 'cms_article', 'contentid' ) AS $row )
		{
			try
			{
				$node		= $this->db->select( '*', 'cms_node', array( "contenttypeid=? AND contentid=?", static::$_articleContentType, $row['contentid'] ) )->first();
				$nodeinfo	= $this->db->select( '*', 'cms_nodeinfo', array( "nodeid=?", $node['nodeid'] ) )->first();
			}
			catch( UnderflowException $e )
			{
				$libraryClass->setLastKeyValue( $row['contentid'] );
				continue;
			}
			

			$categories	= iterator_to_array( $this->db->select( 'categoryid', 'cms_nodecategory', array( "nodeid=?", $node['nodeid'] ) ) );

			if( !count( $categories ) )
			{
				/* Create one */
				try
				{
					$this->app->getLink( '__orphan__', 'cms_database_categories' );
					$categories = array( '__orphan__' );
				}
				catch ( OutOfRangeException $e )
				{
					$libraryClass->convertCmsDatabaseCategory( array(
						'category_id' => '__orphan__',
						'category_database_id' => 1,
						'category_name' => "vBulletin Articles",
						'category_fields' => array( 'article_title', 'article_content' )
					) );

					$categories = array( '__orphan__' );
				}
			}
			
			$keywords = array();
			foreach( explode( ',', $nodeinfo['keywords'] ) AS $word )
			{
				$keywords[] = trim( $word );
			}

			// First category
			$category = array_shift( $categories );
			
			$id = $libraryClass->convertCmsDatabaseRecord( array(
				'record_id'					=> $row['contentid'],
				'record_database_id'		=> 1,
				'member_id'					=> $node['userid'],
				'rating_real'				=> $nodeinfo['ratingtotal'],
				'rating_hits'				=> $nodeinfo['ratingnum'],
				'rating_value'				=> $nodeinfo['rating'],
				'record_locked'				=> ( $node['comments_enabled'] ) ? 0 : 1,
				'record_views'				=> $nodeinfo['viewcount'],
				'record_allow_comments'		=> $node['comments_enabled'],
				'record_saved'				=> $node['publishdate'],
				'record_updated'			=> $node['lastupdated'],
				'category_id'				=> $category,
				'record_approved'			=> ( $node['hidden'] ) ? -1 : 1,
				'record_static_furl'		=> $node['url'],
				'record_meta_keywords'		=> $keywords,
				'record_meta_description'	=> $nodeinfo['description'],
				'record_topicid'			=> $nodeinfo['associatedthreadid'],
				'record_publish_date'		=> $node['publishdate'],
			), array(
				1 => $nodeinfo['title'],
				2 => $row['pagetext']
			) );

			/* Need to know database for tag conversion */
			try
			{
				$database = $this->app->getLink( 1, 'cms_databases' );
			}
			catch( OutOfRangeException $e )
			{
				/* Cannot find it, we can't convert tags */
				$libraryClass->setLastKeyValue( $row['contentid'] );
			}

			/* Convert extra unassigned categories as tags */
			$convertedTags = array();
			if( count( $categories ) )
			{
				foreach( $categories as $key )
				{
					if( isset( $convertedTags[ $cmsCategories[ $key ] ] ) )
					{
						continue;
					}

					$libraryClass->convertTag( array(
						'tag_meta_app'			=> 'cms',
						'tag_meta_area'			=> "records{$database}",
						'tag_meta_parent_id'	=> $category,
						'tag_meta_id'			=> $row['contentid'],
						'tag_text'				=> $cmsCategories[ $key ],
						'tag_member_id'			=> $node['userid'],
						'tag_added'             => $node['publishdate'],
						'tag_prefix'			=> 0,
						'tag_meta_link'			=> 'cms_custom_database_' . $database,
						'tag_meta_parent_link'	=> 'cms_database_categories',
					) );

					if( $id )
					{
						$convertedTags[ $cmsCategories[ $key ] ] = $cmsCategories[ $key ];
					}
				}
			}

			/* Convert normal article tags */
			$tags = $this->db->select( '*', 'tagcontent', array( "contenttypeid=? AND contentid=?", static::$_articleContentType, $row['contentid'] ) )
				->join( 'tag', 'tagcontent.tagid=tag.tagid');

			foreach( $tags AS $tag )
			{
				if( isset( $convertedTags[ $tag['tagtext'] ] ) OR ( $tag['canonicaltagid'] > 0 AND $tag['canonicaltagid'] != $tag['tagid'] ) )
				{
					continue;
				}

				$id = $libraryClass->convertTag( array(
					'tag_meta_app'			=> 'cms',
					'tag_meta_area'			=> "records{$database}",
					'tag_meta_parent_id'	=> $category,
					'tag_meta_id'			=> $tag['contentid'],
					'tag_text'				=> $tag['tagtext'],
					'tag_member_id'			=> $tag['userid'],
					'tag_added'             => $node['publishdate'],
					'tag_prefix'			=> 0,
					'tag_meta_link'			=> 'cms_custom_database_' . $database,
					'tag_meta_parent_link'	=> 'cms_database_categories',
				) );

				if( $id )
				{
					$convertedTags[ $tag['tagtext'] ] = $tag['tagtext'];
				}
			}
			
			$libraryClass->setLastKeyValue( $row['contentid'] );
		}
	}

	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		foreach( Db::i()->select( 'ipb_id', 'convert_link', array( 'type=? AND app=?', 'cms_databases', $this->app->app_id ) ) as $database )
		{
			Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\cms\Categories' . $database, 'count' => 0 ), 5, array( 'class' ) );
			Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\cms\Records'. $database ), 3, array( 'class' ) );
			Task::queue( 'convert', 'RebuildTagCache', array( 'app' => $this->app->app_id, 'link' => 'cms_custom_database_' . $database, 'class' => 'IPS\cms\Records' . $database ), 3, array( 'app', 'link', 'class' ) );

			try
			{
				Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'cms_custom_database_' . $database, 'class' => 'IPS\cms\Records' . $database ), 2, array( 'app', 'link', 'class' ) );
			}
			catch ( OutOfRangeException $e ) {}
		}

		return array( "f_recount_cms_categories", "f_rebuild_cms_tags" );
	}

	/**
	 * Convert attachments
	 *
	 * @return	void
	 */
	public function convertAttachments() : void
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'attachmentid' );

		$where			= NULL;
		$column			= NULL;

		if ( static::$isLegacy === FALSE OR is_null( static::$isLegacy ) )
		{
			$where			= array( "contenttypeid=?", static::$_articleContentType );
			$column			= 'contentid';
			$table			= 'attachment';
		}

		foreach( $this->fetch( $table, 'attachmentid', $where ) as $attachment )
		{
			try
 			{
				$vbRecordId = $this->db->select( 'contentid', 'cms_node', array( "nodeid=? AND contenttypeid=?", $attachment[ $column ], static::$_articleContentType ) )->first();
			}
			catch( UnderflowException $e )
			{
				/* Log this so that it's easier to diagnose */
				$this->app->log( 'attachment_vbcms_missing_parent', __METHOD__, App::LOG_WARNING, $attachment['attachmentid'] );

				/* If the record is missing, there isn't much we can do. */
				$libraryClass->setLastKeyValue( $attachment['attachmentid'] );
				continue;
			}

			if ( static::$isLegacy === FALSE OR is_null( static::$isLegacy ) )
			{
				$filedata = $this->db->select( '*', 'filedata', array( "filedataid=?", $attachment['filedataid'] ) )->first();
			}
			else
			{
				$filedata				= $attachment;
				$filedata['filedataid']	= $attachment['attachmentid'];
			}

			$info = array(
				'attach_id'			=> $attachment['attachmentid'],
				'attach_file'		=> $attachment['filename'],
				'attach_date'		=> $attachment['dateline'],
				'attach_member_id'	=> $attachment['userid'],
				'attach_hits'		=> $attachment['counter'],
				'attach_ext'		=> $filedata['extension'],
				'attach_filesize'	=> $filedata['filesize'],
			);

			if ( $this->app->_session['more_info']['convertAttachments']['file_location'] == 'database' )
			{
				/* Simples! */
				$data = $filedata['filedata'];
				$path = NULL;
			}
			else
			{
				$data = NULL;
				$path = implode( '/', preg_split( '//', $filedata['userid'], -1, PREG_SPLIT_NO_EMPTY ) );
				$path = rtrim( $this->app->_session['more_info']['convertAttachments']['file_location'], '/' ) . '/' . $path . '/' . $attachment['filedataid'] . '.attach';
			}

			/* Do some re-jiggery on the post itself to make sure attachment displays */
			/* The database is hardcoded to 1 while the conversion, so we have to use 1 here */
			$dbId = $this->app->getLink( 1, 'cms_databases' );
			$dbName = "cms_custom_database_" . $dbId;

			/* Get the database object */
			$ipsDb = Databases::load( $dbId );

			$map = array(
				'id1'		=> $vbRecordId,
				'id2'		=> 2,
				'id2_type'	=> 'cms_database_fields',
				'id3'		=> 1
			);

			$attach_id = $libraryClass->convertAttachment( $info, $map, $path, $data );

			try
			{
				$recordId = $this->app->getLink( $vbRecordId, 'cms_custom_database_' . $dbId );

				$post = Db::i()->select( 'field_' . $ipsDb->field_content, $dbName, array( "primary_id_field=?", $recordId ) )->first();

				if ( preg_match( "/\[ATTACH([^\]]+?)?\]" . $attachment['attachmentid'] . "\[\/ATTACH\]/i", $post ) )
				{
					$post = preg_replace( "/\[ATTACH([^\]]+?)?\]" . $attachment['attachmentid'] . "\[\/ATTACH\]/i", '[attachment=' . $attach_id . ':name]', $post );
					Db::i()->update( $dbName, array( 'field_' . $ipsDb->field_content => $post ), array( "primary_id_field=?", $recordId ) );
				}
			}
			catch( OutOfRangeException $e ) { }

			$libraryClass->setLastKeyValue( $attachment['attachmentid'] );
		}
	}

	/**
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array( 'convertAttachments' );
	}

	/**
	 * Get More Information
	 *
	 * @param string $method	Conversion method
	 * @return    array|null
	 */
	public function getMoreInfo( string $method ): ?array
	{
		$return = array();
		switch( $method )
		{
			case 'convertAttachments':
				$return['convertAttachments'] = array(
					'file_location' => array(
						'field_class'			=> 'IPS\\Helpers\\Form\\Radio',
						'field_default'			=> 'database',
						'field_required'		=> TRUE,
						'field_extra'			=> array(
							'options'				=> array(
								'database'				=> Member::loggedIn()->language()->addToStack( 'conv_store_database' ),
								'file_system'			=> Member::loggedIn()->language()->addToStack( 'conv_store_file_system' ),
							),
							'userSuppliedInput'	=> 'file_system',
						),
						'field_hint'			=> NULL,
						'field_validation'	=> function( $value ) { if ( $value != 'database' AND !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
					)
				);
				break;
		}

		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}
}