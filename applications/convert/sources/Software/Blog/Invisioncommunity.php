<?php

/**
 * @brief		Converter Invisioncommunnity Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Blog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Application;
use IPS\convert\App;
use IPS\convert\Library;
use IPS\convert\Software;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Member;
use IPS\Task;
use UnderflowException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Invisioncommunity extends Software
{
	/**
	 * @brief 	Whether the versions of IPS4 match
	 */
	public static bool $versionMatch = FALSE;

	/**
	 * @brief 	Whether the database has been required
	 */
	public static bool $dbNeeded = FALSE;

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
		/* Set filename obscuring flag */
		Library::$obscureFilenames = FALSE;

		parent::__construct( $app, $needDB );

		if( $needDB )
		{
			static::$dbNeeded = TRUE;

			try
			{
				$version = $this->db->select( 'app_version', 'core_applications', array( 'app_directory=?', 'core' ) )->first();

				/* We're matching against the human version since the long version can change with patches */
				if ( $version == Application::load( 'core' )->version )
				{
					static::$versionMatch = TRUE;
				}
			}
			catch( DbException $e ) {}

			/* Get parent sauce */
			$this->parent = $this->app->_parent->getSource();
		}
	}
	
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return 'Invision Community (' . Application::load( 'core' )->version . ')';
	}

	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "invisioncommunity";
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
		return array( 'core' => array( 'invisioncommunity' ) );
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|NULL
	 * @note	NULL indicates this converter cannot be used yet, useful to move redirect scripts into the 4.x framework
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertBlogCategories'			=> array(
				'table'		=> 'blog_categories',
				'where'		=> NULL
			),
			'convertBlogs'					=> array(
				'table'		=>'blog_blogs',
				'where'		=> NULL
			),
			'convertBlogEntryCategories'	=> array(
				'table'		=> 'blog_entry_categories',
				'where'		=> NULL
			),
			'convertBlogEntries'			=> array(
				'table'		=> 'blog_entries',
				'where'		=> NULL
			),
			'convertBlogComments'			=> array(
				'table'		=> 'blog_comments',
				'where'		=> NULL
			),
			'convertAttachments'			=> array(
				'table'		=> 'core_attachments_map',
				'where'		=> array( Db::i()->in( 'location_key', array( 'blog_Blogs', 'blog_Entries' ) ) )
			)
		);
	}
	
	/**
	 * Can we convert passwords from this software.
	 *
	 * @return    boolean
	 */
	public static function loginEnabled(): bool
	{
		return FALSE;
	}
	
	/**
	 * Can we convert settings?
	 *
	 * @return    boolean
	 */
	public static function canConvertSettings(): bool
	{
		return FALSE;
	}
	
	/**
	 * Settings Map
	 *
	 * @return    array
	 */
	public function settingsMap(): array
	{
		return array();
	}
	
	/**
	 * Settings Map Listing
	 *
	 * @return    array
	 */
	public function settingsMapList(): array
	{
		return array();
	}
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to start this conversion
	 *
	 * @return    string|NULL
	 */
	public static function getPreConversionInformation(): ?string
	{
		return NULL;
	}
	
	/**
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array(
			'convertAttachments'
		);
	}
	
	/**
	 * Get More Information
	 *
	 * @param string $method	Method name
	 * @return    array|null
	 */
	public function getMoreInfo( string $method ): ?array
	{
		$return = array();

		switch( $method )
		{
			case 'convertAttachments':
				Member::loggedIn()->language()->words["upload_path"] = Member::loggedIn()->language()->addToStack( 'convert_invision_upload_input' );
				Member::loggedIn()->language()->words["upload_path_desc"] = Member::loggedIn()->language()->addToStack( 'convert_invision_upload_input_desc' );
				$return[ $method ] = array(
					'upload_path'				=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> $this->parent->app->_session['more_info']['convertEmoticons']['upload_path'] ?? NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> Member::loggedIn()->language()->addToStack('convert_invision_upload_path'),
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
					)
				);
				break;
		}

		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		/* Content Rebuilds */
		Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\blog\Entry\Category', 'count' => 0 ), 4, array( 'class' ) );
		Task::queue( 'convert', 'InvisionCommunityRebuildContent', array( 'app' => $this->app->app_id, 'link' => 'blog_entries', 'class' => 'IPS\blog\Entry' ), 2, array( 'app', 'link', 'class' ) );
		Task::queue( 'convert', 'InvisionCommunityRebuildContent', array( 'app' => $this->app->app_id, 'link' => 'blog_comments', 'class' => 'IPS\blog\Entry\Comment' ), 2, array( 'app', 'link', 'class' ) );
		Task::queue( 'convert', 'RebuildTagCache', array( 'app' => $this->app->app_id, 'link' => 'blog_entries', 'class' => 'IPS\blog\Entry' ), 3, array( 'app', 'link', 'class' ) );
		
		return array( "f_blog_entries_rebuilding", "f_entry_comments_rebuilding", "f_blogs_recounting", "f_entry_tags_cache" );
	}
	
	/**
	 * Convert Blog Categories
	 *
	 * @return	void
	 */
	public function convertBlogCategories() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'category_id' );
		foreach( $this->fetch( 'blog_categories', 'category_id' ) AS $row )
		{
			$this->parent->unsetNonStandardColumns( $row, 'blog_categories', 'blog' );
			$row['category_name'] = $this->parent->getWord( "blog_category_{$row['category_id']}" );
			$row['category_description'] = $this->parent->getWord( "blog_category_{$row['category_id']}_desc" );
			
			$libraryClass->convertBlogCategory( $row );
			$libraryClass->setLastKeyValue( $row['category_id'] );
		}
	}
	
	/**
	 * Convert Blogs
	 *
	 * @return	void
	 */
	public function convertBlogs() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'blog_id' );
		foreach( $this->fetch( 'blog_blogs', 'blog_id' ) AS $row )
		{
			$this->parent->unsetNonStandardColumns( $row, 'blog_blogs', 'blog' );
			$row['blog_name'] = $this->parent->getWord( "blogs_blog_{$row['blog_id']}" );
			$socialgroup = NULL;
			if ( $row['blog_social_group'] )
			{
				try
				{
					$socialgroup = iterator_to_array( $this->db->select( 'member_id', 'core_sys_social_group_members', array( "group_id=?", $row['blog_social_group'] ) ) );
				}
				catch( UnderflowException ) { }
			}
			
			$cover = NULL;
			if ( $row['blog_cover_photo'] )
			{
				$cover = $this->app->_session['more_info']['convertAttachments']['upload_path'] . '/' . $row['blog_cover_photo'];
			}
			
			$libraryClass->convertBlog( $row, $socialgroup, $cover );
			
			/* Follows */
			foreach( $this->db->select( '*', 'core_follow', array( "follow_app=? AND follow_area=? AND follow_rel_id=?", 'blog', 'blog', $row['blog_id'] ) ) AS $follow )
			{
				$this->parent->unsetNonStandardColumns( $follow, 'core_follow', 'core' );
				$libraryClass->convertFollow( $follow );
			}
			
			$libraryClass->setLastKeyValue( $row['blog_id'] );
		}
	}
	
	/**
	 * Convert Blog Entry Categories
	 *
	 * @return	void
	 */
	public function convertBlogEntryCategories() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'entry_category_id' );
		foreach( $this->fetch( 'blog_entry_categories', 'entry_category_id' ) AS $row )
		{
			$this->parent->unsetNonStandardColumns( $row, 'blog_entry_categories', 'blog' );
			$libraryClass->convertBlogEntryCategory( $row );
			$libraryClass->setLastKeyValue( $row['entry_category_id'] );
		}
	}
	
	/**
	 * Convert Blog Entries
	 *
	 * @return	void
	 */
	public function convertBlogEntries() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'entry_id' );
		foreach( $this->fetch( 'blog_entries', 'entry_id' ) AS $row )
		{
			$this->parent->unsetNonStandardColumns( $row, 'blog_entries', 'blog' );
			
			$cover = NULL;
			if ( $row['entry_cover_photo'] )
			{
				$cover = $this->app->_session['more_info']['convertAttachments']['upload_path'] . '/' . $row['entry_cover_photo'];
			}
			
			$libraryClass->convertBlogEntry( $row );
			
			/* Follows */
			foreach( $this->db->select( '*', 'core_follow', array( "follow_app=? AND follow_area=? AND follow_rel_id=?", 'blog', 'entry', $row['entry_id'] ) ) AS $follow )
			{
				$this->parent->unsetNonStandardColumns( $follow, 'core_follow', 'core' );
				$libraryClass->convertFollow( $follow );
			}
			
			/* Tags */
			foreach( $this->db->select( '*', 'core_tags', array( "tag_meta_app=? AND tag_meta_area=? AND tag_meta_id=?", 'blog', 'blogs', $row['entry_id'] ) ) AS $tag )
			{
				$this->parent->unsetNonStandardColumns( $tag, 'core_tags', 'core' );
				$libraryClass->convertTag( $tag );
			}
			
			/* Reputation */
			foreach( $this->db->select( '*', 'core_reputation_index', array( "app=? AND type=? AND type_id=?", 'blog', 'entry_id', $row['entry_id'] ) ) AS $rep )
			{
				$this->parent->unsetNonStandardColumns( $rep, 'core_reputation_index', 'core' );
				$libraryClass->convertReputation( $rep );
			}
			
			$libraryClass->setLastKeyValue( $row['entry_id'] );
		}
	}
	
	/**
	 * Convert Blog Comments
	 *
	 * @return	void
	 */
	public function convertBlogComments() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'comment_id' );
		
		foreach( $this->fetch( 'blog_comments', 'comment_id' ) AS $row )
		{
			$this->parent->unsetNonStandardColumns( $row, 'blog_comments', 'blog' );
			$libraryClass->convertBlogComment( $row );
			
			/* Reputation */
			foreach( $this->db->select( '*', 'core_reputation_index', array( "app=? AND type=? AND type_id=?", 'blog', 'comment_id', $row['comment_id'] ) ) AS $rep )
			{
				$this->parent->unsetNonStandardColumns( $rep, 'core_reputation_index', 'core' );
				$libraryClass->convertReputation( $rep );
			}
			
			$libraryClass->setLastKeyValue( $row['comment_id'] );
		}
	}
	
	/**
	 * Convert attachments
	 *
	 * @return	void
	 */
	public function convertAttachments() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'attach_id' );

		foreach( $this->fetch( 'core_attachments', 'attach_id' ) AS $row )
		{
			try
			{
				$attachmentMap = $this->db->select( '*', 'core_attachments_map', array( 'attachment_id=? AND ' . Db::i()->in( 'location_key', array( 'blog_Blogs', 'blog_Entries' ) ), $row['attach_id'] ) )->first();
			}
			catch( UnderflowException $e )
			{
				$libraryClass->setLastKeyValue( $row['attach_id'] );
				continue;
			}

			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'core_attachments', 'core' );
			$this->parent->unsetNonStandardColumns( $attachmentMap, 'core_attachments_map', 'core' );

			/* Remap rows */
			$name = explode( '/', $row['attach_location'] );
			$row['attach_container'] = isset( $name[1] ) ? $name[0] : NULL;
			$thumbName = explode( '/', $row['attach_thumb_location'] );
			$row['attach_thumb_container'] = isset( $thumbName[1] ) ? $thumbName[0] : NULL;

			$filePath = $this->app->_session['more_info']['convertAttachments']['upload_path'] . '/' . $row['attach_location'];
			$thumbnailPath = $this->app->_session['more_info']['convertAttachments']['upload_path'] . '/' . $row['attach_thumb_location'];

			unset( $row['attach_file'] );

			$libraryClass->convertAttachment( $row, $attachmentMap, $filePath, NULL, $thumbnailPath );		
			$libraryClass->setLastKeyValue( $row['attach_id'] );
		}
	}
}