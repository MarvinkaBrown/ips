<?php

/**
 * @brief		Converter XenForoRm Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\convert\App;
use IPS\convert\Software;
use IPS\convert\Software\Exception;
use IPS\Db;
use IPS\Task;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Xenforo (resource manager) Pages Converter
 */
class Xenfororm extends Software
{
	/**
	 * @brief	The similarities between XF1 and XF2 are close enough that we can use the same converter
	 */
	public static ?bool $isLegacy = NULL;

	/**
	 * @brief	XF2 Has prefixes on RM tables
	 */
	public static string $tablePrefix = '';

	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "XenForo Resource Manager Articles (1.5.x/2.0.x/2.1.x/2.2.x)";
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

		if ( $needDB )
		{
			try
			{
				/* Is this XF1 or XF2 */
				if ( static::$isLegacy === NULL )
				{
					$version = $this->db->select( 'MAX(version_id)', 'xf_template', array( Db::i()->in( 'addon_id', array( 'XF', 'XenForo' ) ) ) )->first();

					if ( $version < 2000010 )
					{
						static::$isLegacy = TRUE;
					}
					else
					{
						static::$tablePrefix = 'rm_';
						static::$isLegacy = FALSE;
					}
				}
			}
			catch( \Exception $e ) {} # If we can't query, we won't be able to do anything anyway
		}
	}
	
	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "xenfororm";
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertCmsDatabases'			=> array(
				'table'							=> 'cms_databases',
				'where'							=> NULL,
			),
			'convertCmsDatabaseCategories'	=> array(
				'table'							=> ( static::$isLegacy ? 'xf_resource_category' : 'xf_rm_category' ),
				'where'							=> array( "allow_fileless=?", 1 )
			),
			'convertCmsDatabaseRecords'		=> array(
				'table'							=> 'xf_' . static::$tablePrefix . 'resource',
				'where'							=> static::$isLegacy ? array( "is_fileless=?", 1 ) : array( 'resource_type=?', 'fileless' )
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
			case 'cms_databases':
				return 1;
				
			default:
				return parent::countRows( $table, $where, $recache );
		}
	}
	
	/**
	 * Uses Prefix
	 *
	 * @return    bool
	 */
	public static function usesPrefix(): bool
	{
		return FALSE;
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
		return array( 'core' => array( 'xenforo' ) );
	}

	/**
	 * Convert CMS databases
	 *
	 * @return	void
	 */
	public function convertCmsDatabases() : void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass->convertCmsDatabase( array(
			'database_id'			=> 1,
			'database_name'			=> "Resources",
			'database_sln'			=> 'resource',
			'database_pln'			=> 'resources',
			'database_scn'			=> 'Resource',
			'database_pcn'			=> 'Resources',
			'database_ia'			=> 'a resource',
		), array(
			array(
				'field_id'				=> 1,
				'field_name'			=> 'Title',
				'field_type'			=> 'Text',
				'field_key'				=> 'resource_title',
				'field_required'		=> 1,
				'field_position'		=> 1,
				'field_display_listing'	=> 1,
				'field_is_title'		=> 1,
			),
			array(
				'field_id'				=> 2,
				'field_name'			=> 'Tag Line',
				'field_type'			=> 'Text',
				'field_key'				=> 'resource_tagline',
				'field_required'		=> 1,
				'field_position'		=> 2,
			),
			array(
				'field_id'				=> 3,
				'field_name'			=> 'Content',
				'field_type'			=> 'Editor',
				'field_key'				=> 'resource_content',
				'field_required'		=> 1,
				'field_position'		=> 3,
				'field_is_content'		=> 1,
			)
		) );
		
		/* Throw an exception here to tell the library that we're done with this step */
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
		
		$libraryClass::setKey( 'resource_category_id' );
		
		foreach( $this->fetch( ( static::$isLegacy ? 'xf_resource_category' : 'xf_rm_category' ), 'resource_category_id', array( 'allow_fileless=?', 1 ) ) AS $row )
		{
			$forumoverride	= 0;
			$forumid		= 0;
			if ( $row['thread_node_id'] )
			{
				$forumoverride	= 1;
				$forumid		= $row['thread_node_id'];
			}
			
			$info = array(
				'category_id'			=> $row['resource_category_id'],
				'category_database_id'	=> 1,
				'category_name'			=> $row['category_title'] ?? $row['title'],
				'category_parent_id'	=> $row['parent_category_id'],
				'category_position'		=> $row['display_order'],
				'category_fields'		=> array( 'resource_title', 'resource_tagline', 'resource_content' ),
			);
			
			$libraryClass->convertCmsDatabaseCategory( $info );
			
			$libraryClass->setLastKeyValue( $row['resource_category_id'] );
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
		
		$libraryClass::setKey( 'resource_id' );
		
		foreach( $this->fetch( 'xf_' . static::$tablePrefix . 'resource', 'resource_id', static::$isLegacy ? array( "is_fileless=?", 1 ) : array( 'resource_type=?', 'fileless' ) ) AS $row )
		{
			$post = $this->db->select( 'message, post_date', 'xf_' . static::$tablePrefix . 'resource_update', array( "resource_id=?", $row['resource_id'] ), "post_date DESC" )->first();
			
			switch( $row['resource_state'] )
			{
				case 'visible':
					$approved = 1;
					break;
				
				case 'moderated':
					$approved = 0;
					break;
				
				case 'deleted':
					$approved = -1;
					break;
			}
			
			$info = array(
				'record_id'				=> $row['resource_id'],
				'record_database_id'	=> 1,
				'member_id'				=> $row['user_id'],
				'record_saved'			=> $row['resource_date'],
				'record_updated'		=> $post['post_date'],
				'category_id'			=> $row['resource_category_id'],
				'record_approved'		=> $approved,
				'record_topicid'		=> $row['discussion_thread_id'],
				'record_publish_date'	=> $row['resource_date'],
			);
			
			$fields = array( 1 => $row['title'], 2 => $row['tag_line'], 3 => $post['message'] );
			
			$libraryClass->convertCmsDatabaseRecord( $info, $fields );
			
			$libraryClass->setLastKeyValue( $row['resource_id'] );
		}
	}

	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		try
		{
			$database = $this->app->getLink( 1, 'cms_databases' );
			Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\cms\Categories' . $database, 'count' => 0 ), 5, array( 'class' ) );
			Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'cms_custom_database_' . $database, 'class' => 'IPS\cms\Records' . $database ), 2, array( 'app', 'link', 'class' ) );

			return array( "f_recount_cms_categories", "f_rebuild_cms_tags" );
		}
		catch( OutOfRangeException $e )
		{
			return array();
		}
	}
}