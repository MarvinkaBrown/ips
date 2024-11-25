<?php
/**
 * @brief		Media Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		15 Jan 2014
 */

namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Media\Folder;
use IPS\Db;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Media Model
 */
class Media extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'cms_media';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'media_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array('media_full_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static string $parentNodeColumnId = 'parent';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static string $parentNodeClass = 'IPS\cms\Media\Folder';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnOrder = 'filename';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static bool $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static bool $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Title
	 */
	public static string $nodeTitle = 'page';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 array(
	 'app'		=> 'core',				// The application key which holds the restrictrions
	 'module'	=> 'foo',				// The module key which holds the restrictions
	 'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 'add'			=> 'foo_add',
	 'edit'			=> 'foo_edit',
	 'permissions'	=> 'foo_perms',
	 'delete'		=> 'foo_delete'
	 ),
	 'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
			'app'		=> 'cms',
			'module'	=> 'pages',
			'prefix' 	=> 'media_'
	);

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->parent     = 0;
		$this->full_path  = '';
	}

	/**
	 * @return string
	 */
	public function get_file_icon() : string
	{
		$extension = strtolower( substr( $this->file_object, strrpos( $this->file_object, '.' ) + 1 ) );
		return File::$fileIconMap[ $extension ] ?? '';
	}
	
	/**
	 * Resets a media path
	 *
	 * @param 	int 	$folderId	Folder ID to reset
	 * @return	void
	 */
	public static function resetPath( int $folderId ) : void
	{
		try
		{
			$path = Folder::load( $folderId )->path;
		}
		catch ( OutOfRangeException $ex )
		{
			throw new OutOfRangeException;
		}
	
		$children = static::getChildren( $folderId );
	
		foreach( $children as $id => $obj )
		{
			$obj->setFullPath( $path );
		}
	}
	
	/**
	 * Get all children of a specific folder.
	 *
	 * @param	INT 	$folderId		Folder ID to fetch children from
	 * @return	array
	 */
	public static function getChildren( int $folderId=0 ) : array
	{
		$children = array();
		foreach( Db::i()->select( '*', static::$databaseTable, array( 'media_parent=?', intval( $folderId ) ), 'media_filename ASC' ) as $child )
		{
			$children[ $child[ static::$databasePrefix . static::$databaseColumnId ] ] = static::load( $child[ static::$databasePrefix . static::$databaseColumnId ] );
		}
	
		return $children;
	}
	
	/**
	 * Delete media by file ids
	 *
	 * @param	array	$ids	Array of IDs to remove
	 * @return	void
	 */
	public static function deleteByFileIds( array $ids=array() ) : void
	{
		foreach( $ids as $id )
		{
			try
			{
				static::load( $id )->delete();
			}
			catch( Exception $ex ) { }
		}
	}
	
	/**
	 * Get URL
	 *
	 * @return Url|string|null object
	 */
	function url(): Url|string|null
	{
		return (string)File::get( 'cms_Media', $this->file_object )->url;
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		try
		{
			if ( $this->file_object )
			{
				File::get( 'cms_Media', $this->file_object )->delete();
			}
		}
		catch( Exception $ex ) { }

		parent::delete();
	}

	/**
	 * [Node] Get buttons to display in tree
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );
		$delete  = NULL;

		if ( isset( $buttons['add' ] ) )
		{
			unset( $buttons['add'] );
		}

		if ( isset( $buttons['delete'] ) )
		{
			$delete = $buttons['delete'];
			unset( $buttons['delete'] );
		}

		if ( isset( $buttons['copy' ] ) )
		{
			unset( $buttons['copy'] );
		}

		$buttons['key'] = array(
			'icon'	=> 'file-code',
			'title'	=> 'cms_media_key',
			'link'	=> Url::internal( 'app=cms&module=pages&controller=media&do=key&id=' . $this->id ),
			'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_media_key') )
		);

		if ( $this->is_image )
		{
			$buttons['preview'] = array(
				'icon'	=> 'search',
				'title'	=> 'cms_media_preview',
				'link'	=> Url::internal( 'app=cms&module=pages&controller=media&do=preview&id=' . $this->id ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_media_preview') )
			);
		}

		if ( $delete )
		{
			$buttons['delete'] = $delete;
		}

		return $buttons;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @note	This is not used currently. See \IPS\cms\modules\admin\media.php upload()
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		/* Build form */
		$form->add( new Upload( 'media_filename', ( ( $this->filename ) ? File::get( 'cms_Media', $this->file_object ) : NULL ), FALSE, array( 'obscure' => FALSE, 'maxFileSize' => 5, 'storageExtension' => 'cms_Media', 'storageContainer' => 'pages_media' ), NULL, NULL, NULL, 'media_filename' ) );
			
		$form->add( new Node( 'media_parent', $this->parent ?: 0, FALSE, array(
			'class'    => '\IPS\cms\Media\Folder',
			'zeroVal'  => 'node_no_parent'
		) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @note	This is not used currently. See \IPS\cms\modules\admin\media.php upload()
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if ( isset( $values['media_parent'] ) AND ( ! empty( $values['media_parent'] ) OR $values['media_parent'] === 0 ) )
		{
			$values['parent'] = ( $values['media_parent'] === 0 ) ? 0 : $values['media_parent']->id;
			unset( $values['media_parent'] );
		}
		
		if ( isset( $values['media_filename'] ) )
		{
			$filename = $values['media_filename']->originalFilename;

			if ( ! $this->_new and $this->file_object )
			{
				$prefix = $this->parent . '_';

				if ( mb_strstr( $filename, $prefix ) )
				{
					$filename = mb_substr( $filename, mb_strlen( $prefix ) );
				}
			}

			$values['filename']        = $filename;
			$values['filename_stored'] = $values['parent'] . '_' . $values['filename'];
			$values['is_image']        = $values['media_filename']->isImage();
			$values['file_object']     = (string) $values['media_filename'];

			unset( $values['media_filename'] );
		}

		if ( $this->_new )
		{
			$values['added'] = time();
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @note	This is not used currently. See \IPS\cms\modules\admin\media.php upload()
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		$this->setFullPath( ( $this->parent ? Folder::load( $this->parent )->path : '' ) );
		$this->save();
	}

	/**
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName() : string
	{
		return $this->full_path;
	}
	
	/**
	 * Resets a folder path
	 *
	 * @param	string	$path	Path to reset
	 * @return	void
	 */
	public function setFullPath( string $path ) : void
	{
		$this->full_path = trim( $path . '/' . $this->filename, '/' );
		$this->save();
	}

	/**
	 * Removes folders that are empty
	 *
	 * @return void
	 */
	public static function removeEmptyFolders() : void
	{
		$folders    = iterator_to_array( Db::i()->select( 'DISTINCT(media_parent)', 'cms_media', array( 'media_parent > 0' ) ) );
		$allFolders = iterator_to_array( Db::i()->select( '*', 'cms_media_folders' )->setKeyField( 'media_folder_id' ) );

		foreach( $folders as $id )
		{
			if ( isset( $allFolders[ $id ] ) )
			{
				$currentParent = $allFolders[ $id ]['media_folder_parent'];
				$try = 0;
				while( $currentParent )
				{
					if ( $try++ > 50 )
					{
						/* Prevent broken associations from preventing execution */
						break;
					}

					if ( ! in_array( $currentParent, $folders ) )
					{
						$folders[] = $currentParent;
					}

					$currentParent = $allFolders[ $currentParent ]['media_folder_parent'];
				}
			}
		}

		Db::i()->delete( 'cms_media_folders', array( Db::i()->in( 'media_folder_id', array_values( $folders ), TRUE ) ) );
	}

	/**
	 * Create new media file from a disk file. If the file exists and is unchanged, it will not be updated
	 *
	 * @param   string      $path       File path (/folder/file.txt)
	 * @param   string      $contents   File contents
	 * @return  int|null         ID of existing media or of new media
	 */
	public static function createMedia( string $path, string $contents ): ?int
	{
		try
		{
			$test = static::load( $path, 'media_full_path' );

			$test->file_object = File::create( 'cms_Media', $test->filename_stored, $contents, 'pages_media', TRUE, NULL, FALSE );
			$test->save();

			return $test->id;
		}
		catch( RuntimeException $ex )
		{
			try
			{
				File::get( 'cms_Media', $path );
			}
			catch( Exception $x )
			{
				/* File doesn't exist already */
				throw $ex;
			}
		}
		catch( OutOfRangeException $ex )
		{
			/* It doesn't exist */
			$exploded = explode( '/', $path );
			$filename = array_pop( $exploded );
			$folderId = 0;

			if ( count( $exploded ) )
			{
				$testDir = trim( implode( '/', $exploded ), '/' );
				try
				{
					$test = Folder::load( $testDir, 'media_folder_path' );

					/* Yep */
					$folderId = $test->id;
				}
				catch( OutOfRangeException $ex )
				{
					$testDir  = '';
					foreach( $exploded as $dir )
					{
						$testDir = trim( $testDir . '/' . $dir, '/' );

						try
						{
							$test     = Folder::load( $testDir, 'media_folder_path' );
							$folderId = $test->id;
						}
						catch( OutOfRangeException $ex )
						{
							$folder = new Folder;
							$folder->parent = $folderId;
							$folder->name   = $dir;
							$folder->path   = $testDir;
							$folder->save();
							$folderId = $folder->id;
						}
					}
				}
			}

			$media = new Media;
			$media->parent          = $folderId;
			$media->filename        = $filename;
			$media->added           = time();
			$media->full_path       = $path;
			$media->filename_stored = $folderId . '_' . $filename;
			$media->file_object     = File::create( 'cms_Media', $media->filename_stored, $contents, 'pages_media', TRUE, NULL, FALSE );
			$media->is_image        = $media->file_object->isImage();
			$media->save();

			return $media->id;
		}

		return null;
	}
}