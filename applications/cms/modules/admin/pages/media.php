<?php
/**
 * @brief		Pages Media Management
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		07 Jul 2015
 */

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\cms\Media as MediaClass;
use IPS\cms\Media\Folder;
use IPS\cms\Pages\Page;
use IPS\cms\Templates;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Tree\Tree;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * media
 */
class media extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = '\IPS\cms\Media\Folder';

	/**
	 * @var string[] 	Additional allowed FileTypes for the media field.
	 */
	public static array $additionalAllowedMediaFileTypes = array( 'pdf', 'svg', 'woff', 'woff2', 'ttf', 'eot' );

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'media_manage' );
		parent::execute();
	}

	/**
	* Get Root Buttons
	*
	* @return	array
	*/
	public function _getRootButtons(): array
	{
		$buttons   = array();

		if ( Member::loggedIn()->hasAcpRestriction( 'cms', 'pages', 'page_add' )  )
		{
			$buttons['add_folder'] = array(
				'icon'	=> 'folder-open',
				'title'	=> 'cms_add_media_folder',
				'link'	=> Url::internal( 'app=cms&module=pages&controller=media&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_add_media_folder') )
			);

			$buttons['add_page'] = array(
				'icon'	=> 'plus-circle',
				'title'	=> 'cms_add_media',
				'link'	=>  Url::internal( 'app=cms&module=pages&controller=media&subnode=1&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('cms_add_media') )
			);
		}

		return $buttons;
	}
	
	/**
	 * Delete many at once
	 *
	 * @return void
	 */
	public function deleteByFileIds() : void
	{
		Session::i()->csrfCheck();
		
		if ( isset( Request::i()->fileIds ) )
		{
			$ids = Request::i()->fileIds;
			
			if ( ! is_array( $ids ) )
			{
				$try = json_decode( $ids, TRUE );
				
				if ( ! is_array( $try ) )
				{
					$ids = array( $ids );
				}
				else
				{
					$ids = $try;
				}
			}
			
			if ( count( $ids ) )
			{
				MediaClass::deleteByFileIds( $ids );

				Session::i()->log( 'acplogs__cms_deleted_media', array( count( $ids ) => FALSE ) );
			}
		}
	}
	
	/**
	 * Show the pages tree
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$url = Url::internal( "app=cms&module=pages&controller=media" );

		/* Display the table */
		Output::i()->title  = Member::loggedIn()->language()->addToStack('menu__cms_pages_media');
		$output = new Tree( $url, 'menu__cms_pages_media',
			/* Get Roots */
			function () use ( $url )
			{
				$data = media::getRowsForTree( 0 );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					if( ( Request::i()->isAjax() && $row instanceof MediaClass ) || !Request::i()->isAjax() )
					{
						$rows[ $id ] = ( $row instanceof MediaClass) ? media::getItemRow( $row, $url ) : media::getFolderRow( $row, $url );
					}
				}

				if( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( json_encode( $rows ), 200, 'application/json' );
				}

				return $data;
			},
			/* Get Row */
			function ( $id, $root ) use ( $url )
			{
				if ( $root )
				{
					return media::getFolderRow( Folder::load( $id ), $url );
				}
				else
				{
					return media::getItemRow( MediaClass::load( $id ), $url );
				}
			},
			/* Get Row Parent ID*/
			function ()
			{
				return NULL;
			},
			/* Get Children */
			function ( $id ) use ( $url )
			{
				$rows = array();
				$data = media::getRowsForTree( $id );

				if ( ! isset( Request::i()->subnode ) )
				{
					foreach ( $data as $id => $row )
					{
						if( Request::i()->get == 'folders' && !( $row instanceof MediaClass ) )
						{
							$rows[ $id ] = media::getFolderRow( $row, $url );
						}
						elseif ( Request::i()->get == 'files' && $row instanceof MediaClass )
						{
							$rows[ $id ] = media::getItemRow( $row, $url );
						}

					}
				}

				if( Request::i()->isAjax() ){
					Output::i()->sendOutput( json_encode( $rows ), 200, 'application/json' );
				}

				return $rows;
			},
		   array( $this, '_getRootButtons' ),
		   TRUE,
		   FALSE,
		   FALSE
		);

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_media.js', 'cms' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'media/media.css', 'cms', 'admin' ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'media', 'cms', 'admin' )->media( $output );
		}
	}

	/**
	 * Replace an existing file
	 *
	 * @return void
	 */
	public function replace() : void
	{
		if( !isset( Request::i()->id ) OR !Request::i()->id )
		{
			Output::i()->error( 'missing_media_file', '3T334/2', 404, '' );
		}

		try
		{
			$media = MediaClass::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'missing_media_file', '3T334/3', 404, '' );
		}

		$form = new Form( 'form', 'upload' );
		$form->class = 'ipsForm--vertical ipsForm--replace-file ipsForm_noLabels';
			
		$form->add( new Upload( 'media_filename', NULL, FALSE, array( 'allowedFileTypes' => array_merge( File::$safeFileExtensions, static::$additionalAllowedMediaFileTypes ), 'obscure' => FALSE, 'storageExtension' => 'cms_Media', 'storageContainer' => 'pages_media', 'multiple' => FALSE, 'minimize' => FALSE ), NULL, NULL, NULL, 'media_filename' ) );

		if ( $values = $form->values() )
		{
			$existingFileExtension	= mb_substr( $media->filename_stored, mb_strrpos( $media->filename_stored, '.' ) + 1 );
			$newFileExtension		= mb_substr( $values['media_filename']->originalFilename, mb_strrpos( $values['media_filename']->originalFilename, '.' ) + 1 );

			/* If we have the same extension, we will just retain the same filename */
			if( $existingFileExtension == $newFileExtension )
			{
				$media->file_object		= (string) File::create( 'cms_Media', $media->filename_stored, $values['media_filename']->contents(), 'pages_media', TRUE, NULL, FALSE );
			}
			/* Otherwise we need to update the rest of the file info too */
			else
			{
				$media->is_image		= $values['media_filename']->isImage();
				$media->filename		= $values['media_filename']->originalFilename;
				$media->filename_stored	= $media->parent . '_' . $media->filename;
				$media->file_object		= (string) File::create( 'cms_Media', $media->filename_stored, $values['media_filename']->contents(), 'pages_media', TRUE, NULL, FALSE );
			}

			$media->setFullPath( ( $media->parent ? Folder::load( $media->parent )->path : '' ) );
			$media->save();
			
			/* Remove the original as we created a copy with a slightly altered filename */
			try
			{
				$values['media_filename']->delete();
			}
			catch( Exception $ex ) { }
			
			/* Wipe out included JS just in case we're using this media thing */
			Templates::deleteCompiledFiles();
			Page::deleteCachedIncludes();

			Session::i()->log( 'acplogs__cms_replaced_media', array( $values['media_filename']->originalFilename => FALSE ) );
			
			if ( Request::i()->isAjax() )
			{
				$url = Url::internal( "app=cms&module=pages&controller=media" );
				$data = media::getRowsForTree( $media->parent );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					if ( $row instanceof MediaClass )
					{
						$rows[ $id ] = media::getItemRow( $row, $url );
					}
				}

				Output::i()->sendOutput( json_encode( array( 'fileID' => $media->id, 'folderID' => $media->parent, 'rows' => $rows ) ), 200, 'application/json' );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=media' ) );
			}
		}

		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'upload', $form, FALSE );
	}


	/**
	 * Upload items
	 *
	 * @return void
	 */
	public function upload() : void
	{
		$form = new Form( 'form', 'upload' );
		$form->class = 'ipsForm--vertical ipsForm--upload-items ipsForm_noLabels';

		$extensions = array_merge( File::$safeFileExtensions, static::$additionalAllowedMediaFileTypes, Image::supportedExtensions() );
		sort( $extensions );
		$form->add( new Upload( 'media_filename', NULL, FALSE, array( 'allowedFileTypes' => array_values( array_unique( $extensions ) ), 'obscure' => FALSE, 'storageExtension' => 'cms_Media', 'storageContainer' => 'pages_media', 'multiple' => true, 'minimize' => FALSE ), NULL, NULL, NULL, 'media_filename' ) );
			
		if ( ! isset( Request::i()->media_parent ) and ! Request::i()->media_parent )
		{
			$form->add( new Node( 'media_parent', 0, FALSE, array(
				'class'    => '\IPS\cms\Media\Folder',
				'zeroVal'  => 'node_no_parent'
			) ) );
		}
		else
		{
			$form->hiddenValues['media_parent_inline'] = Request::i()->media_parent;
		}
		
		if ( $values = $form->values() )
		{
			$parent = 0;
			$count = 0;

			if ( isset( $values['media_parent_inline'] ) AND $values['media_parent_inline'] )
			{
				$parent = $values['media_parent_inline'];
			}
			else
			{
				if ( isset( $values['media_parent'] ) AND ( ! empty( $values['media_parent'] ) OR $values['media_parent'] === 0 ) )
				{
					$parent = ( $values['media_parent'] === 0 ) ? 0 : $values['media_parent']->id;
				}
			}

			foreach( $values['media_filename'] as $media )
			{
				$filename = $media->originalFilename;
	
				$prefix = $parent . '_';
	
				if ( mb_strstr( $filename, $prefix ) )
				{
					$filename = mb_substr( $filename, mb_strlen( $prefix ) );
				}

				$new = new MediaClass;
				$new->filename        = $filename;
				$new->filename_stored = $parent . '_' . $filename;
				$new->is_image        = $media->isImage();
				$new->parent          = $parent;
				$new->added           = time();
				$new->file_object     = (string) $media;
				$new->save();
				
				$new->setFullPath( ( $parent ? Folder::load( $parent )->path : '' ) );
				$new->save();
				
				$count++;
			}
			
			/* Wipe out included JS just in case we're using this media thing */
			Templates::deleteCompiledFiles();
			Page::deleteCachedIncludes();

			Session::i()->log( 'acplogs__cms_uploaded_media', array( $count => FALSE ) );
			
			if ( Request::i()->isAjax() )
			{
				$url = Url::internal( "app=cms&module=pages&controller=media" );
				$data = media::getRowsForTree( $parent );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					if ( $row instanceof MediaClass )
					{
						$rows[ $id ] = media::getItemRow( $row, $url );
					}
				}

				Output::i()->sendOutput( json_encode( array( 'count' => $count, 'folderID' => $parent, 'rows' => $rows ) ), 200, 'application/json' );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=cms&module=pages&controller=media' ) );
			}
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'upload', $form, FALSE );
	}
	
	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = array();
		$url  = Url::internal( "app=cms&module=pages&controller=media" );

		/* Get results */
		$items   = MediaClass::search( 'media_filename', Request::i()->input, 'media_filename' );

		/* Convert to HTML */
		foreach ( $items as $id => $result )
		{
			$rows[ $id ] = $this->getItemRow( $result, $url );
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( json_encode( $rows ), 200, 'application/json' );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
		}		
	}

	/**
	 * Returns a JSON object with some file information
	 *
	 * @return	void
	 */
	protected function getFileInfo() : void
	{
		try
		{
			$row = MediaClass::load( Request::i()->id );
			$fileObject = File::get( 'cms_Media', $row->file_object );
		}
		catch( OutOfRangeException $ex )
		{
			return;
		}
		
		/* Make a human-readable size */
		$filesize = Filesize::humanReadableFilesize( $fileObject->filesize() );
		Member::loggedIn()->language()->parseOutputForDisplay( $filesize );

		$output = array( 
			'fileSize' => $filesize,
			'dimensions' => NULL
		);

		/* If this is an image we'll also show the dimensions */
		if( $row->is_image )
		{
			$dimensions = $fileObject->getImageDimensions();
			$output['dimensions'] = $dimensions[0] . ' x ' . $dimensions[1];
		}

		Output::i()->sendOutput( json_encode( $output ), 200, 'application/json' );
	}

	/**
	 * Return HTML for a page row
	 *
	 * @param MediaClass $item	Row data
	 * @param object $url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getItemRow( MediaClass $item, object $url ): string
	{
		return Theme::i()->getTemplate( 'media', 'cms', 'admin' )->fileListing( $url, $item );
	}

	/**
	 * Return HTML for a folder row
	 *
	 * @param MediaClass|Folder $folder	Row data
	 * @param Url $url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getFolderRow( MediaClass|Folder $folder, Url $url ): string
	{
		return Theme::i()->getTemplate( 'media', 'cms', 'admin' )->folderRow( $url, $folder );
	}


	/**
	 * Fetch rows of folders/pages
	 *
	 * @param int $folderId		Parent ID to fetch from
	 * @return array
	 */
	public static function getRowsForTree( int $folderId=0 ) : array
	{
		try
		{
			if ( $folderId === 0 )
			{
				$folders = Folder::roots();
			}
			else
			{
				$folders = Folder::load( $folderId )->children( NULL, NULL, FALSE );
			}
		}
		catch( OutOfRangeException $ex )
		{
			$folders = array();
		}

		$media = MediaClass::getChildren( $folderId );

		return Folder::munge( $folders, $media );
	}

}