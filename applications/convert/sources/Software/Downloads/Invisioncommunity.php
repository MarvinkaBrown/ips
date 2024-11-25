<?php

/**
 * @brief		Converter Invision Community Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		13 July 2021
 */

namespace IPS\convert\Software\Downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\convert\App;
use IPS\convert\Library;
use IPS\convert\Software;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Task;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function stristr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invision Downloads Converter
 */
class Invisioncommunity extends Software
{
	/**
	 * @brief    Whether the versions of IPS4 match
	 */
	public static bool $versionMatch = FALSE;

	/**
	 * @brief    Whether the database has been required
	 */
	public static bool $dbNeeded = FALSE;

	/**
	 * Constructor
	 *
	 * @param App $app The application to reference for database and other information.
	 * @param bool $needDB Establish a DB connection
	 * @return    void
	 * @throws    InvalidArgumentException
	 */
	public function __construct( App $app, bool $needDB = TRUE )
	{
		/* Set filename obscuring flag */
		Library::$obscureFilenames = FALSE;

		parent::__construct( $app, $needDB );

		if ( $needDB )
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
			catch( \IPS\Db\Exception $e ) {}

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
	 * Content we can convert from this software.
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		if ( !static::$versionMatch and static::$dbNeeded )
		{
			throw new \IPS\convert\Exception( 'convert_invision_mismatch' );
		}

		return array(
			'convertDownloadsCategories' => array(
				'table' => 'downloads_categories',
				'where' => NULL,
			),
			'convertDownloadsCfields' => array(
				'table' => 'downloads_cfields',
				'where' => NULL,
			),
			'convertDownloadsComments' => array(
				'table' => 'downloads_comments',
				'where' => NULL,
			),
			'convertDownloadsFiles' => array(
				'table' => 'downloads_files',
				'where' => NULL,
			),
			'convertDownloadsReviews' => array(
				'table' => 'downloads_reviews',
				'where' => NULL,
			),
			'convertAttachments' => array(
				'table' => 'core_attachments',
				'where' => NULL,
			)
		);
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
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array(
			'convertAttachments',
			'convertDownloadsFiles'
		);
	}

	/**
	 * Finish
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\downloads\File', 'count' => 0 ), 4, array( 'class' ) );
		Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\downloads\Category', 'count' => 0 ), 5, array( 'class' ) );
		Task::queue( 'convert', 'InvisionCommunityRebuildContent', array( 'app' => $this->app->app_id, 'class' => 'IPS\downloads\File', 'link' => 'downloads_files' ), 2, array( 'app', 'link', 'class' ) );
		Task::queue( 'convert', 'InvisionCommunityRebuildContent', array( 'app' => $this->app->app_id, 'class' => 'IPS\downloads\File\Review', 'link' => 'downloads_reviews'  ), 2, array( 'app', 'link', 'class' ) );
		Task::queue( 'convert', 'InvisionCommunityRebuildContent', array( 'app' => $this->app->app_id, 'class' => 'IPS\downloads\File\Comment', 'link' => 'downloads_comments'  ), 2, array( 'app', 'link', 'class' ) );

		return array( );
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
				$attachmentMap = $this->db->select( '*', 'core_attachments_map', array( 'attachment_id=? AND location_key=?', $row['attach_id'], 'downloads_Downloads' ) )->first();
			}
			catch( UnderflowException $e )
			{
				$libraryClass->setLastKeyValue( $row['attach_id'] );
				continue;
			}

			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'core_attachments' );
			$this->parent->unsetNonStandardColumns( $attachmentMap, 'core_attachments_map' );

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

	/**
	 * Get More Information
	 *
	 * @param string $method Method name
	 * @return    array|null
	 */
	public function getMoreInfo( string $method ): ?array
	{
		$return = array();

		switch ( $method )
		{
			case 'convertAttachments':
			case 'convertDownloadsFiles':
				Member::loggedIn()->language()->words["upload_path"] = Member::loggedIn()->language()->addToStack( 'convert_invision_upload_input' );
				Member::loggedIn()->language()->words["upload_path_desc"] = Member::loggedIn()->language()->addToStack( 'convert_invision_upload_input_desc' );
				$return[ $method ] = array(
					'upload_path' => array(
						'field_class' => 'IPS\\Helpers\\Form\\Text',
						'field_default' => $this->parent->app->_session['more_info']['convertEmoticons']['upload_path'] ?? NULL,
						'field_required' => TRUE,
						'field_extra' => array(),
						'field_hint' => Member::loggedIn()->language()->addToStack( 'convert_invision_upload_path' ),
						'field_validation' => function ( $value ) {
							if ( !@is_dir( $value ) )
							{
								throw new DomainException( 'path_invalid' );
							}
						},
					)
				);
				break;
		}

		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}

	/**
	 * Convert downloads category
	 *
	 * @return	void
	 */
	public function convertDownloadsCategories() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'cid' );

		foreach( $this->fetch( 'downloads_categories', 'cid'  ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'downloads_categories', 'downloads' );

			/* Add name after clearing other columns */
			$row['cname'] = $this->parent->getWord( 'downloads_category_' . $row['cid'] );
			$row['cdesc'] = $this->parent->getWord( 'downloads_category_' . $row['cid'] . '_desc' );

			$id = $libraryClass->convertDownloadsCategory( $row );

			if( !empty( $id ) )
			{
				/* Convert Follows */
				foreach ( $this->db->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', 'downloads', 'category', $row['cid'] ) ) as $follow )
				{
					/* Remove non-standard columns */
					$this->parent->unsetNonStandardColumns( $follow, 'core_follow', 'core' );

					/* Change follow data */
					$follow['follow_rel_id_type'] = 'downloads_categories';

					$libraryClass->convertFollow( $follow );
				}
			}

			$libraryClass->setLastKeyValue( $row['cid'] );
		}
	}

	/**
	 * Convert downloads fields
	 *
	 * @return	void
	 */
	public function convertDownloadsCfields() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'cf_id' );

		foreach( $this->fetch( 'downloads_cfields', 'cf_id'  ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'downloads_cfields', 'downloads' );

			/* Add name after clearing other columns */
			$row['cf_name'] = $this->parent->getWord( 'downloads_field_' . $row['cf_id'] );
			$row['cf_desc'] = $this->parent->getWord( 'downloads_field_' . $row['cid'] . '_desc' );

			$libraryClass->convertDownloadsCfield( $row );
			$libraryClass->setLastKeyValue( $row['cf_id'] );
		}
	}

	/**
	 * Convert comments
	 *
	 * @return	void
	 */
	public function convertDownloadsComments() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'comment_id' );

		foreach( $this->fetch( 'downloads_comments', 'comment_id' ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'downloads_comments', 'downloads' );

			$id = $libraryClass->convertDownloadsComment( $row );

			if( !empty( $id ) )
			{
				/* Reputation */
				foreach( $this->db->select( '*', 'core_reputation_index', array( "app=? AND type=? AND type_id=?", 'downloads', 'comment_id', $row['comment_id'] ) ) AS $rep )
				{
					/* Remove non-standard columns */
					$this->parent->unsetNonStandardColumns( $rep, 'core_reputation_index', 'core' );

					$libraryClass->convertReputation( $rep );
				}
			}

			$libraryClass->setLastKeyValue( $row['comment_id'] );
		}
	}

	/**
	 * Convert downloads file
	 *
	 * @return	void
	 */
	public function convertDownloadsFiles() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'file_id' );

		foreach( $this->fetch( 'downloads_files', 'file_id' ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'downloads_files', 'downloads' );

			$records = [];
			foreach( $this->db->select( '*', 'downloads_files_records', [ 'record_file_id=?', $row['file_id'] ] ) as $record )
			{
				$record['file_path'] = $this->app->_session['more_info']['convertDownloadsFiles']['upload_path'] . '/' . $record['record_location'];
				$records[] = $record;
			}

			$customFields = iterator_to_array( $this->db->select( '*', 'downloads_ccontent', [ 'file_id=?', $row['file_id'] ] ) );

			$id = $libraryClass->convertDownloadsFile( $row, $records, $customFields );

			if( !empty( $id ) )
			{
				/* Convert Follows */
				foreach ( $this->db->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', 'downloads', 'file', $row['file_id'] ) ) as $follow )
				{
					/* Remove non-standard columns */
					$this->parent->unsetNonStandardColumns( $follow, 'core_follow', 'core' );

					/* Change follow data */
					$follow['follow_rel_id_type'] = 'downloads_file';

					$libraryClass->convertFollow( $follow );
				}

				/* Convert Tags */
				foreach ( $this->db->select( '*', 'core_tags', array( 'tag_meta_app=? AND tag_meta_area=? AND tag_meta_id=?', 'downloads', 'downloads', $row['file_id'] ) ) as $tag )
				{
					/* Remove non-standard columns */
					$this->parent->unsetNonStandardColumns( $tag, 'core_tags', 'core' );

					$libraryClass->convertTag( $tag );
				}

				/* Reputation */
				foreach( $this->db->select( '*', 'core_reputation_index', array( "app=? AND type=? AND type_id=?", 'downloads', 'file_id', $row['file_id'] ) ) AS $rep )
				{
					/* Remove non-standard columns */
					$this->parent->unsetNonStandardColumns( $rep, 'core_reputation_index', 'core' );

					$libraryClass->convertReputation( $rep );
				}
			}

			$libraryClass->setLastKeyValue( $row['file_id'] );
		}
	}

	/**
	 * Convert reviews
	 *
	 * @return	void
	 */
	public function convertDownloadsReviews() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'comment_id' );

		foreach( $this->fetch( 'downloads_reviews', 'review_id' ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'downloads_reviews', 'downloads' );

			$id = $libraryClass->convertDownloadsReview( $row );

			if( !empty( $id ) )
			{
				/* Reputation */
				foreach( $this->db->select( '*', 'core_reputation_index', array( "app=? AND type=? AND type_id=?", 'downloads', 'review_id', $row['review_id'] ) ) AS $rep )
				{
					/* Remove non-standard columns */
					$this->parent->unsetNonStandardColumns( $rep, 'core_reputation_index', 'core' );

					$libraryClass->convertReputation( $rep );
				}
			}

			$libraryClass->setLastKeyValue( $row['review_id'] );
		}
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		$url = Request::i()->url();

		if( !stristr( $url->data[ Url::COMPONENT_PATH ], 'ic-merge-' . $this->app->_parent->app_id ) )
		{
			return NULL;
		}

		/* account for non-mod_rewrite links */
		$searchOn = stristr( $url->data[ Url::COMPONENT_PATH ], 'index.php' ) ? $url->data[ Url::COMPONENT_QUERY ] : $url->data[ Url::COMPONENT_PATH ];

		if( preg_match( '#/(category|file)/([0-9]+)-(.+?)#i', $searchOn, $matches ) )
		{
			$oldId	= (int) $matches[2];

			switch( $matches[1] )
			{
				case 'category':
					$class	= '\IPS\downloads\Category';
					$types	= array( 'downloads_categories' );
					break;

				case 'file':
					$class	= '\IPS\downloads\File';
					$types	= array( 'downloads_files' );
					break;
			}
		}

		if( isset( $class ) )
		{
			try
			{
				try
				{
					$data = (string) $this->app->getLink( $oldId, $types );
				}
				catch( OutOfRangeException $e )
				{
					$data = (string) $this->app->getLink( $oldId, $types, FALSE, TRUE );
				}
				$item = $class::load( $data );

				if( $item instanceof Content )
				{
					if( $item->canView() )
					{
						return $item->url();
					}
				}
				elseif( $item instanceof Model )
				{
					if( $item->can( 'view' ) )
					{
						return $item->url();
					}
				}
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}

		return NULL;
	}
}