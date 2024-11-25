<?php
/**
 * @brief		Theme Media Management
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		18 Jan 2024
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use UnderflowException;
use function array_merge;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function json_encode;
use function ltrim;
use function mt_rand;
use function time;

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
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'theme_sets_manage' );
		parent::execute();
	}

	/**
	 * Delete many at once
	 *
	 * @return void
	 */
	public function deleteByFileIds() : void
	{
		Session::i()->csrfCheck();

		$setId = intval( Request::i()->set_id );

		try
		{
			$set = Theme::load( $setId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T334/G', 403, '' );
		}

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
				foreach( $ids as $id )
				{
					try
					{
						$current = Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_id=?', $set->_id, $id ) )->first();
					}
					catch( UnderflowException $ex )
					{
						/* Just ignore it ssssh */
					}

					/* Don't delete master resources */
					if ( $current['resource_set_id'] > 0 )
					{
						try
						{
							File::get( 'core_Theme', $current['resource_filename'] )->delete();
						}
						catch( Exception $ex ) { }

						Db::i()->delete( 'core_theme_resources', array( 'resource_id=?', $id ) );
					}

					Session::i()->log( 'acplog__theme_resource_deleted', array( $current['resource_filename'] => FALSE ) );
				}

				/* Delete widget caches */
				Widget::deleteCaches();

				$set->buildResourceMap();
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
		$setId = intval( Request::i()->set_id );

		try
		{
			$set = Theme::load( $setId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T334/4', 403, '' );
		}

		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('theme_custom_resources_page_title', FALSE, array( 'sprintf' => array( $set->_title ) ) );
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=customization&controller=themes" ), 'menu__core_customization_themes' );
		Output::i()->breadcrumb[] = array( NULL, Output::i()->title );

		/* Get the resources */
		$media = [];
		foreach( Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_user_edited=1', $setId ) ) as $row )
		{
			$media[ $row['resource_id'] ] = media::getItemRow( array_merge( $row, [ 'url' => $set->resource( ltrim( $row['resource_path'], '/' ) . $row['resource_name'], $row['resource_app'], $row['resource_location'] ) ] ) );
		}

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_customization.js', 'core' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/media.css', 'core', 'admin' ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( json_encode( $media ), 200, 'application/json' );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'customization', 'core', 'admin' )->media( $media, $set );
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

		$setId = intval( Request::i()->set_id );

		try
		{
			$set = Theme::load( $setId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T334/C', 403, '' );
		}

		try
		{
			$row = Db::i()->select( '*', 'core_theme_resources', array( 'resource_id=? and resource_set_id=? and resource_user_edited=1', Request::i()->id, $setId ) )->first();
		}
		catch( Exception $ex )
		{
			Output::i()->error( 'missing_media_file', '3T334/3', 404, '' );
		}

		$form = new Form( 'form', 'upload' );
		$form->class = 'ipsForm--vertical ipsForm--replace-file ipsForm_noLabels';

		$form->add( new Upload( 'media_filename', null, true, array( 'obscure' => false, 'multiple' => false, 'maxFileSize' => 1.2, 'storageExtension' => 'core_Theme', 'storageContainer' => 'set_resources_' . $set->_id ), NULL, NULL, NULL, 'core_theme_resource_filename' ) );

		if ( $values = $form->values() )
		{
			$save = $row;
			unset( $save['resource_id'] );
			$save['resource_data'] = $values['media_filename']->contents();
			$save['resource_added'] = time();
			$save['resource_filename'] = (string) $values['media_filename'];

			$existingFileExtension	= mb_substr( $row['resource_name'], mb_strrpos( $row['resource_name'], '.' ) + 1 );
			$newFileExtension		= mb_substr( $values['media_filename']->originalFilename, mb_strrpos( $values['media_filename']->originalFilename, '.' ) + 1 );

			/* File extention changed? Change the name */
			if( $existingFileExtension !== $newFileExtension )
			{
				$save['resource_name'] = $values['media_filename']->originalFilename;
			}

			Session::i()->log( 'acplog__theme_resource_edited', array( $save['resource_filename'] => FALSE ) );
			Db::i()->update( 'core_theme_resources', $save, array( 'resource_id=?', $row['resource_id'] ) );

			/* Do any clean up */
			$this->postUpload( $set );

			if ( Request::i()->isAjax() )
			{
				$media = [];
				foreach( Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_user_edited=1', $set->id ) ) as $row )
				{
					$media[ $row['resource_id'] ] = media::getItemRow( array_merge( $row, [ 'url' => $set->resource( ltrim( $row['resource_path'], '/' ) . $row['resource_name'], $row['resource_app'], $row['resource_location'] ) ] ) );
				}

				Output::i()->sendOutput( json_encode( array( 'fileID' => $row['resource_id'], 'rows' => $media ) ), 200, 'application/json' );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=media&set_id=' . $set->id ) );
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
		$setId = intval( Request::i()->set_id );

		try
		{
			$set = Theme::load( $setId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T334/A', 403, '' );
		}

		$form = new Form( 'form', 'upload' );
		$form->class = 'ipsForm--vertical ipsForm--upload-items ipsForm_noLabels';

		$form->add( new Upload( 'media_filename', null, true, array( 'obscure' => false, 'multiple' => true, 'maxFileSize' => 1.2, 'storageExtension' => 'core_Theme', 'storageContainer' => 'set_resources_' . $set->_id ), NULL, NULL, NULL, 'core_theme_resource_filename' ) );

		if ( $values = $form->values() )
		{
			$count = 0;

			foreach( $values['media_filename'] as $media )
			{
				$save = array(
					'resource_app'	       => 'core',
					'resource_location'    => 'front',
					'resource_path' 	   => '/custom/',
					'resource_set_id'      => $set->_id,
					'resource_user_edited' => 1,
					'resource_name'		   => $media->originalFilename,
					'resource_added' 	   => time(),
					'resource_filename'    => (string) $media,
					'resource_data'		   => $media->contents()
				);

				try
				{
					Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_path=? and resource_location=? and resource_app=? and resource_name=?', $set->_id, $save['resource_path'], $save['resource_location'], $save['resource_app'], $save['resource_name'] ) )->first();

					$ext = mb_substr( $save['resource_name'], ( mb_strrpos( $save['resource_name'], '.' ) + 1 ) );
					$save['resource_name'] = mb_substr( $save['resource_name'], 0, ( mb_strrpos( $save['resource_name'], '.' ) ) ) . '_' . mt_rand() . '.' . $ext;
				}
				catch( UnderflowException $ex ) { }

				Session::i()->log( 'acplog__theme_resource_added', array( $save['resource_filename'] => FALSE ) );
				Db::i()->insert( 'core_theme_resources', $save );
				
				$count++;
			}

			/* Do any clean up */
			$this->postUpload( $set );
			
			if ( Request::i()->isAjax() )
			{
				$media = [];
				foreach( Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_user_edited=1', $set->id ) ) as $row )
				{
					$media[ $row['resource_id'] ] = media::getItemRow( array_merge( $row, [ 'url' => $set->resource( ltrim( $row['resource_path'], '/' ) . $row['resource_name'], $row['resource_app'], $row['resource_location'] ) ] ) );
				}

				Output::i()->sendOutput( json_encode( array( 'count' => $count, 'rows' => $media ) ), 200, 'application/json' );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=media&set_id=' . $set->id ) );
			}
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'upload', $form, FALSE );
	}

	/**
	 * Do stuff after an upload / replace
	 *
	 * @param Theme $set
	 * @return void
	 */
	protected function postUpload( Theme $set ) : void
	{
		$set->buildResourceMap();

		/* Delete widget caches */
		Widget::deleteCaches();

		/* Resource may be used in CSS */
		File::getClass('core_Theme')->deleteContainer( 'css_built_' . $set->id );
		$set->css_map = array();
		$set->css_updated = time();
		$set->save();

		foreach( $set->children() as $child )
		{
			File::getClass('core_Theme')->deleteContainer( 'css_built_' . $child->id );
			$child->css_map = array();
			$child->css_updated = time();
			$child->save();
		}
	}

	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$setId = intval( Request::i()->set_id );

		try
		{
			$set = Theme::load( $setId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T334/E', 403, '' );
		}

		$url = Url::internal( "app=core&module=customization&controller=media&set_id=" . $setId );
		$media = array();

		/* Get results */
		foreach( Db::i()->select( '*', 'core_theme_resources', array( "resource_set_id=? and resource_user_edited=1 and resource_name LIKE CONCAT( '%', ?, '%' )", $setId, Request::i()->input ) ) as $row )
		{
			$media[] = media::getItemRow( array_merge( $row, [ 'url' => $set->resource( ltrim( $row['resource_path'], '/' ) . $row['resource_name'], $row['resource_app'], $row['resource_location'] ) ] ) );
		}

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( json_encode( $media ), 200, 'application/json' );
		}
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $media, $url );
		}		
	}

	/**
	 * Returns a JSON object with some file information
	 *
	 * @return	void
	 */
	protected function getFileInfo() : void
	{
		$setId = intval( Request::i()->set_id );

		try
		{
			$set = Theme::load( $setId );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '3T334/B', 403, '' );
		}

		try
		{
			$row  = Db::i()->select( '*', 'core_theme_resources', array( 'resource_id=? and resource_set_id=? and resource_user_edited=1', Request::i()->id, $setId ) )->first();
			$fileObject = File::get( 'core_Theme', $row['resource_filename'] );
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
		if( $fileObject->isImage() )
		{
			$dimensions = $fileObject->getImageDimensions();
			$output['dimensions'] = $dimensions[0] . ' x ' . $dimensions[1];
		}

		Output::i()->sendOutput( json_encode( $output ), 200, 'application/json' );
	}

	/**
	 * Return HTML for a page row
	 *
	 * @param array $item	Row data
	 * @return	string	HTML
	 */
	public static function getItemRow( array $item ): string
	{
		$item['file_type'] = 'other';
		$ext = mb_strtolower( mb_substr( $item['resource_name'], mb_strrpos( $item['resource_name'], '.' ) + 1 ) );
		if ( in_array( $ext, array_merge( [ 'svg' ], Image::supportedExtensions() ) ) )
		{
			$item['file_type'] = 'image';
		}
		else if ( in_array( $ext, [ 'txt', 'htm', 'html', 'css', 'js' ] ) )
		{
			$item['file_type'] = 'text';
		}
		else if ( in_array( $ext, [ 'woff', 'woff2', 'ttf', 'eot' ] ) )
		{
			$item['file_type'] = 'font';
		}

		return Theme::i()->getTemplate( 'customization', 'core', 'admin' )->mediaFileListing( $item );
	}
}