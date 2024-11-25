<?php
/**
 * @brief		Submit File Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		08 Oct 2013
 */

namespace IPS\downloads\modules\front\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\core\FrontNavigation;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\downloads\Category;
use IPS\downloads\File;
use IPS\downloads\Form\LinkedScreenshots;
use IPS\File\Iterator;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Image;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Submit File Controller
 */
class submit extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_submit.js', 'downloads', 'front' ) );

		parent::execute();
	}

	/**
	 * Choose category
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = new Form( 'select_category', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--submit-file ipsForm--noLabels';
		$form->add( new Node( 'select_category', Request::i()->category ?? null, TRUE, array(
			'url'					=> Url::internal( 'app=downloads&module=downloads&controller=submit', 'front', 'downloads_submit' ),
			'class'					=> 'IPS\downloads\Category',
			'permissionCheck'		=> 'add',
			'clubs'					=> Settings::i()->club_nodes_in_apps
		) ) );

		if ( Member::loggedIn()->group['idm_bulk_submit'] )
		{
			$form->add( new YesNo( 'bulk', NULL, FALSE, array( 'label' => "bulk_upload_button" ) ) );
		}

		if ( $values = $form->values() )
		{
			$url = Url::internal( 'app=downloads&module=downloads&controller=submit&do=submit', 'front', 'downloads_submit' )->setQueryString( 'category', $values['select_category']->_id );
			if ( isset( $values['bulk'] ) AND $values['bulk'] )
			{
				$url = $url->setQueryString( 'bulk', '1' );
			}
			if( isset( Request::i()->_new ) )
			{
				$url = $url->setQueryString(array( '_new' => '1' ) );
			}
					
			Output::i()->redirect( $url );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'select_category' );
		Output::i()->output = Theme::i()->getTemplate( 'submit' )->categorySelector( $form );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'select_category' ) );
	}

	/**
	 * Submit files
	 *
	 * @return	void
	 */
	protected function submit() : void
	{		
		$steps = array();

		/**
		 * Step 1: Upload files
		 */
		$steps['upload_files'] = function( $data )
		{
			/* Get category data */
			try
			{
				$category = Category::loadAndCheckPerms( Request::i()->category );
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->redirect( Url::internal( 'app=downloads&module=downloads&controller=submit&_step=select_category', 'front', 'downloads_submit' ) );
			}

			if( !$category->can('add') OR !Member::loggedIn()->group['idm_max_size'] )
			{
				Output::i()->error( 'add_files_no_perm', '3D286/1', 403, '' );
			}

			$form = new Form( 'upload_files', 'continue' );

			$form->class = 'ipsForm--vertical ipsForm--select-file-category';
			$form->hiddenValues['category'] = $category->_id;
			$form->hiddenValues['postKey'] = Request::i()->postKey ?: md5( mt_rand() );

			/* Populate any existing records */
			$files = array();
			$screenshots = array();

			if ( isset( $data['files'] ) )
			{
				foreach ( $data['files'] as $url )
				{
					$files[] = \IPS\File::get( 'downloads_Files', $url );
				}
			}

			if ( isset( $data['screenshots'] ) )
			{
				foreach ( $data['screenshots'] as $url )
				{
					$screenshots[] = \IPS\File::get( 'downloads_Screenshots', $url );
				}
			}
			
			/* Add the fields */
			$maximums = array();

			if( $category->maxfile !== NULL AND !$category->club() )
			{
				$maximums[] = ( $category->maxfile / 1024 );
			}

			if( Member::loggedIn()->group['idm_max_size'] !== -1 )
			{
				$maximums[] = ( Member::loggedIn()->group['idm_max_size'] / 1024 );
			}

			$form->add( new Upload( 'files', $files, ( !Member::loggedIn()->group['idm_linked_files'] and !Member::loggedIn()->group['idm_import_files'] ), array( 'storageExtension' => 'downloads_Files', 'allowedFileTypes' => $category->types, 'maxFileSize' => ( ( count( $maximums ) ) ? min( $maximums ) : NULL ), 'multiple' => $category->multiple_files, 'minimize' => FALSE ) ) );

			if ( !isset( Request::i()->bulk ) )
			{
				if ( Member::loggedIn()->group['idm_linked_files'] )
				{
					$form->add( new Stack( 'url_files', $data['url_files'] ?? array(), FALSE, array( 'stackFieldType' => 'Url' ), array( 'IPS\downloads\File', 'blacklistCheck' ) ) );
				}

				if ( Member::loggedIn()->group['idm_import_files']  )
				{
					$form->add( new Stack( 'import_files', array(), FALSE, array( 'placeholder' => \IPS\ROOT_PATH ), function( $val )
					{
						if( $val and is_array( $val ) )
						{
							foreach ( $val as $file )
							{
								if ( is_dir( $file ) )
								{
									throw new DomainException( Member::loggedIn()->language()->addToStack('err_import_files_dir', FALSE, array( 'sprintf' => array( $file ) ) ) );
								}
								elseif ( !is_file( $file ) )
								{
									throw new DomainException( Member::loggedIn()->language()->addToStack('err_import_files', FALSE, array( 'sprintf' => array( $file ) ) ) );
								}
							}
						}
					} ) );
				}

				if ( $category->bitoptions['allowss'] )
				{
					$image = TRUE;
					if ( $category->maxdims and $category->maxdims != '0x0' )
					{
						$maxDims = explode( 'x', $category->maxdims );
						$image = array( 'maxWidth' => $maxDims[0], 'maxHeight' => $maxDims[1] );
					}
					$form->add( new Upload( 'screenshots', $screenshots, ( $category->bitoptions['reqss'] and !Member::loggedIn()->group['idm_linked_files'] ), array(
						'storageExtension'	=> 'downloads_Screenshots',
						'image'				=> $image,
						'maxFileSize'		=> $category->maxss ? ( $category->maxss / 1024 ) : NULL,
						'multiple'			=> TRUE,
						'template'			=> "downloads.submit.screenshot",
					) ) );
					if ( Member::loggedIn()->group['idm_linked_files'] )
					{
						$form->add( new LinkedScreenshots( 'url_screenshots', isset( $data['url_screenshots'] ) ? array( 'values' => $data['url_screenshots'] ) : array(), FALSE, array( 'stackFieldType' => 'Url' ), array( 'IPS\downloads\File', 'blacklistCheck' ) ) );
					}
				}

				/* Form Elements */
				foreach ( File::formElements( NULL, $category ) as $input )
				{
					$form->add( $input );
				}
				
				/* Version field (we only show this on create */
				if( $category->version_numbers )
				{
					$form->add( new Text( 'file_version', '1.0.0', $category->version_numbers == 2, array( 'maxLength' => 32 ) ) );
				}
			}

			if ( $values = $form->values() )
			{				
				/* Check */
				if ( empty( $values['files'] ) and empty( $values['url_files'] ) and empty( $values['import_files'] ) )
				{
					$form->error = Member::loggedIn()->language()->addToStack('err_no_files');
					return Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), TRUE, ( Member::loggedIn()->group['idm_bulk_submit'] && Request::i()->bulk ) );
				}
				elseif ( !$category->multiple_files AND is_array( $values['files'] ) AND ( count( $values['files'] ?? [] ) + count( $values['url_files'] ?? [] ) + count( $values['import_files'] ?? [] ) > 1 ) )
				{
					$form->error = Member::loggedIn()->language()->addToStack('err_too_many_files');
					return Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), TRUE, ( Member::loggedIn()->group['idm_bulk_submit'] && Request::i()->bulk ) );
				}
				if ( !isset( Request::i()->bulk ) && $category->bitoptions['reqss'] and empty( $values['screenshots'] ) and empty( $values['url_screenshots'] ) )
				{
					$form->error = Member::loggedIn()->language()->addToStack('err_no_screenshots');
					return Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), TRUE, ( Member::loggedIn()->group['idm_bulk_submit'] && Request::i()->bulk ) );
				}
												
				/* Get any records we had before in case we need to delete them */
				$existing = iterator_to_array( Db::i()->select( '*', 'downloads_files_records', array( 'record_post_key=?', Request::i()->postKey ) )->setKeyField( 'record_location' ) );
				
				/* Loop through the values we have */
				$k					= 0;
				$files				= array();
				$linkedFiles		= array();
				$screenshots		= array();
				$linkedScreenshots	= array();

				/* Files may not be an array since we have an option to limit to a single upload */

				if( $values['files'] !== NULL )
				{
					if ( !is_array( $values['files'] ) )
					{
						$values['files'] = [ $values['files'] ];
					}

					foreach ( $values['files'] as $file )
					{
						$files[ $k ] = (string) $file;
						if ( !isset( $existing[ (string) $file ] ) )
						{
							Db::i()->insert( 'downloads_files_records', array(
								'record_post_key' => isset( Request::i()->bulk ) ? md5( Request::i()->postKey . "-{$k}" ) : Request::i()->postKey,
								'record_type' => 'upload',
								'record_location' => (string) $file,
								'record_realname' => $file->originalFilename,
								'record_size' => $file->filesize(),
								'record_time' => time(),
							) );
						}
						$k++;
						unset( $existing[ (string) $file ] );
					}
				}
				if ( isset( $values['import_files'] ) )
				{
					\IPS\File::$copyFiles = TRUE;

					foreach ( $values['import_files'] as $path )
					{
						$file = \IPS\File::create( 'downloads_Files', mb_substr( $path, mb_strrpos( $path, DIRECTORY_SEPARATOR ) + 1 ), NULL, NULL, FALSE, $path );
						
						$files[ $k ] = (string) $file;
						if ( !isset( $existing[ (string) $file ] ) )
						{
							Db::i()->insert( 'downloads_files_records', array(
								'record_post_key'	=> isset( Request::i()->bulk ) ? md5( Request::i()->postKey . "-{$k}" ) : Request::i()->postKey,
								'record_type'		=> 'upload',
								'record_location'	=> (string) $file,
								'record_realname'	=> $file->originalFilename,
								'record_size'		=> $file->filesize(),
								'record_time'		=> time(),
							) );
						}
						$k++;
					}

					\IPS\File::$copyFiles = FALSE;
				}
				if ( isset( $values['url_files'] ) )
				{
					foreach ( $values['url_files'] as $url )
					{
						$linkedFiles[] = (string) $url;
						if ( !isset( $existing[ (string) $url ] ) )
						{
							Db::i()->insert( 'downloads_files_records', array(
								'record_post_key'	=> Request::i()->postKey,
								'record_type'		=> 'link',
								'record_location'	=> (string) $url,
								'record_realname'	=> NULL,
								'record_size'		=> 0,
								'record_time'		=> time(),
							) );
						}
						unset( $existing[ (string) $url ] );
					}
				}
				if ( isset( $values['screenshots'] ) )
				{
					foreach ( $values['screenshots'] as $_key => $file )
					{
						$screenshots[] = (string) $file;
						if ( !isset( $existing[ (string) $file ] ) )
						{
							$noWatermark = NULL;
							if ( Settings::i()->idm_watermarkpath )
							{
								try
								{
									$noWatermark = (string) $file;
									$watermark = Image::create( \IPS\File::get( 'core_Theme', Settings::i()->idm_watermarkpath )->contents() );
									$image = Image::create( $file->contents() );
									$image->watermark( $watermark );
									$file = \IPS\File::create( 'downloads_Screenshots', $file->originalFilename, $image );
								}
								catch ( Exception $e ) { }
							}
							
							Db::i()->insert( 'downloads_files_records', array(
								'record_post_key'		=> Request::i()->postKey,
								'record_type'			=> 'ssupload',
								'record_location'		=> (string) $file,
								'record_thumb'			=> (string) $file->thumbnail( 'downloads_Screenshots' ),
								'record_realname'		=> $file->originalFilename,
								'record_size'			=> $file->filesize(),
								'record_time'			=> time(),
								'record_no_watermark'	=> $noWatermark,
								'record_default'		=> ( Request::i()->screenshots_primary_screenshot AND Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0
							) );
						}
						unset( $existing[ (string) $file ] );
					}
				}
				if ( isset( $values['url_screenshots'] ) )
				{
					foreach ( $values['url_screenshots'] as $_key => $url )
					{
						$linkedScreenshots[] = (string) $url;
						if ( !isset( $existing[ (string) $url ] ) )
						{
							Db::i()->insert( 'downloads_files_records', array(
								'record_post_key'	=> Request::i()->postKey,
								'record_type'		=> 'sslink',
								'record_location'	=> (string) $url,
								'record_thumb'		=> NULL,
								'record_realname'	=> NULL,
								'record_size'		=> 0,
								'record_time'		=> time(),
								'record_default'	=> ( Request::i()->screenshots_primary_screenshot AND Request::i()->screenshots_primary_screenshot == $_key ) ? 1 : 0
							) );
						}
						unset( $existing[ (string) $url ] );
					}
				}
								
				/* Delete any that we don't have any more */
				foreach ( $existing as $location => $file )
				{
					try
					{
						\IPS\File::get( $file['record_type'] === 'upload' ? 'downloads_Files' : 'downloads_Screenshots', $location )->delete();
					}
					catch ( Exception $e ) { }

					if( $file['record_thumb'] )
					{
						try
						{
							\IPS\File::get( 'downloads_Screenshots', $file['record_thumb'] )->delete();
						}
						catch ( Exception $e ) { }
					}

					if( $file['record_no_watermark'] )
					{
						try
						{
							\IPS\File::get( 'downloads_Screenshots', $file['record_no_watermark'] )->delete();
						}
						catch ( Exception $e ) { }
					}
					
					Db::i()->delete( 'downloads_files_records', array( 'record_id=?', $file['record_id'] ) );
				}
				

				if ( !isset( Request::i()->bulk ) )
				{
					$file = File::createFromForm( array_merge( $data, $values, array( 'postKey' => Request::i()->postKey ) ), $category );
					$file->markRead();

					/* Redirect */
					if ( isset( $values['guest_email'] ) )
					{
						$url = Url::internal( 'app=core&module=system&controller=register', 'front', 'register' );
						$message = NULL;
					}
					elseif( $file->author()->member_id OR $file->canView() )
					{
						$url		= $file->url();
						$message	= ( isset( $values['import_files'] ) AND count( $values['import_files'] ) ) ? Member::loggedIn()->language()->addToStack('file_imported_removed') : NULL;
					}
					else
					{
						$url		= $category->url();
						$message	= Member::loggedIn()->language()->addToStack('file_requires_approval_g');
					}
					
					if ( Request::i()->isAjax() )
					{
						Output::i()->json( array( 'redirect' => (string) $url ) );
					}
					else
					{
						Output::i()->redirect( $url, $message );
					}
				}
				else
				{
					/* This is a bulk file, so we want to go on to the next step */
					return array( 'category' => $category->_id, 'postKey' => Request::i()->postKey, 'files' => $files, 'url_files' => $linkedFiles, 'screenshots' => $screenshots, 'url_screenshots' => $linkedScreenshots );
				}
			}
			
			$guestPostBeforeRegister = ( !Member::loggedIn()->member_id ) ? ( !$category->can( 'add', Member::loggedIn(), FALSE ) ) : FALSE;
			$modQueued = File::moderateNewItems( Member::loggedIn(), $category, $guestPostBeforeRegister );
			if ( $guestPostBeforeRegister or $modQueued )
			{
				$postingInformation = Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( $guestPostBeforeRegister, $modQueued, TRUE );
			}
			else
			{
				$postingInformation = NULL;
			}

			return Theme::i()->getTemplate( 'submit' )->submissionForm( $form, $category, $category->message('subterms'), TRUE, ( Member::loggedIn()->group['idm_bulk_submit'] && Request::i()->bulk ), $postingInformation );
		};

		/**
		 * Step 2: File information (for bulk uploads only)
		 */
		$steps['file_information'] = function ( $data )
		{
			/* Get Category */
			try
			{
				$category = Category::loadAndCheckPerms( $data['category'] );
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->redirect( Url::internal( 'app=downloads&module=downloads&controller=submit', 'front', 'downloads_submit' ) );
			}
			/* Init Form */
			$form = new Form( 'file_information', 'continue' );
			$existing = array();

			foreach ( $data['files'] as $key => $file )
			{
				/* Header */
				$file = \IPS\File::get( 'downloads_Files', $file );

				try
				{
					$displayName = Db::i()->select( 'record_realname', 'downloads_files_records', array( 'record_location=?', (string) $file ) )->first();
				}
				catch( UnderflowException $e )
				{
					$displayName = $file->originalFilename;
				}

				$form->addTab( $displayName );
				$form->addHeader( $displayName );
				
				/* Form Elements */
				foreach ( File::formElements( NULL, $category, "filedata_{$key}_" ) as $input )
				{
					Member::loggedIn()->language()->words[ $input->name ] = Member::loggedIn()->language()->addToStack( mb_substr( $input->name, mb_strlen( "filedata_{$key}_" ) ), FALSE );
					if ( !$input->value and in_array( $input->name, array( "filedata_{$key}_file_title", "filedata_{$key}_file_desc" ) ) )
					{
						$input->value = $displayName;
					}
					$form->add( $input );
				}
				
				/* Screenshots */
				if ( $category->bitoptions['allowss'] )
				{
					$existing[ $key ] = iterator_to_array( new Iterator( Db::i()->select( '*', 'downloads_files_records', array( 'record_post_key=? AND record_type=?', md5( "{$data['postKey']}-{$key}" ), 'ssupload' ) )->setValueField( function( $row ) { return $row['record_no_watermark'] ?: $row['record_location']; } )->setKeyField( function( $row ) { return $row['record_no_watermark'] ?: $row['record_location']; } ), 'downloads_Screenshots' ) );

					$image = TRUE;
					if ( $category->maxdims and $category->maxdims != '0x0' )
					{
						$maxDims = explode( 'x', $category->maxdims );
						$image = array( 'maxWidth' => $maxDims[0], 'maxHeight' => $maxDims[1] );
					}

					$form->add( new Upload( "screenshots_{$key}", $existing[ $key ], ( $category->bitoptions['reqss'] and !Member::loggedIn()->group['idm_linked_files'] ), array(
						'storageExtension'	=> 'downloads_Screenshots',
						'image'				=> $image,
						'maxFileSize'		=> $category->maxss ? ( $category->maxss / 1024 ) : NULL,
						'multiple'			=> TRUE
					) ) );
					Member::loggedIn()->language()->words[ "screenshots_{$key}" ] = Member::loggedIn()->language()->addToStack( 'screenshots', FALSE );
				}

									
				/* Version field */
				if( $category->version_numbers )
				{
					$form->add( new Text( "filedata_{$key}_file_version", '1.0.0', $category->version_numbers == 2, array( 'maxLength' => 32 ) ) );
					Member::loggedIn()->language()->words[ "filedata_{$key}_file_version" ] = Member::loggedIn()->language()->addToStack( 'file_version', FALSE );
				}
			}


			/* Handle Submissions */
			if ( $values = $form->values() )
			{
				if ( $category->bitoptions['allowss'] )
				{
					foreach ( $data['files'] as $key => $fileUrl )
					{
						/* Save Screenshots */
						foreach ( $values["screenshots_{$key}"] as $screenshot )
						{
							if ( !isset( $existing[ $key ][ (string) $screenshot ] ) )
							{
								$noWatermark = NULL;
								if ( Settings::i()->idm_watermarkpath )
								{
									$noWatermark = (string) $screenshot;
									$watermark = Image::create( \IPS\File::get( 'core_Theme', Settings::i()->idm_watermarkpath )->contents() );
									$image = Image::create( $screenshot->contents() );
									$image->watermark( $watermark );
									$screenshot = \IPS\File::create( 'downloads_Screenshots', $screenshot->originalFilename, $image );
								}
								
								Db::i()->insert( 'downloads_files_records', array(
									'record_post_key'		=> md5( "{$data['postKey']}-{$key}" ),
									'record_type'			=> 'ssupload',
									'record_location'		=> (string) $screenshot,
									'record_thumb'			=> (string) $screenshot->thumbnail( 'downloads_Screenshots' ),
									'record_realname'		=> $screenshot->originalFilename,
									'record_size'			=> $screenshot->filesize(),
									'record_time'			=> time(),
									'record_no_watermark'	=> $noWatermark
								) );
							}
							else
							{
								unset( $existing[ $key ][ (string) $screenshot ] );
							}
						}
						
						unset( $values["screenshots_{$key}"] );
					
						/* Delete any that we don't have any more */
						foreach ( $existing[ $key ]  as $location => $file )
						{
							try
							{
								$file->delete();
							}
							catch ( Exception $e ) { }
							
							Db::i()->delete( 'downloads_files_records', array( 'record_location=? OR record_no_watermark=?', (string) $file, (string) $file ) );
						}
					}
				}

				/* Create Files */
				foreach ( $data['files'] as $key => $fileUrl )
				{
					/* $values isn't going to work as is here */
					$save = array( 'postKey' => md5( "{$data['postKey']}-{$key}" ) );
					$customFields = [];
					$len = mb_strlen( "filedata_{$key}_" );
					foreach ( $values as $k => $v )
					{
						if ( mb_substr( $k, 0, $len ) == "filedata_{$key}_" )
						{
							$save[ mb_substr( $k, $len ) ] = $v;
						}
						elseif ( mb_substr( $k, 0, $len + 16 ) == "downloads_field_filedata_{$key}_" ) // That's a custom field, because the underscore after the language prefix is hardcoded
						{
							$customFields[] = mb_substr( $k, $len + 16 );
							$save[ 'downloads_field_' . mb_substr( $k, $len + 16 ) ] = $v;
						}
					}
					
					$file = File::createFromForm( $save, $category, FALSE );
					\IPS\File::claimAttachments( "filedata_{$key}_downloads-new-file", $file->id, NULL, 'desc' );
					foreach ( $customFields as $k )
					{
						\IPS\File::claimAttachments( md5( 'IPS\downloads\Field-filedata_' . $key . '_' . $k . '-new' ), $file->id, $k, 'fields' );
					}
					$file->markRead();
				}

				if ( Member::loggedIn()->moderateNewContent() OR File::moderateNewItems( Member::loggedIn(), $category ) )
				{
					File::_sendUnapprovedNotifications( $category );
				}
				else
				{
					File::_sendNotifications( $category );
				}
			
				/* Redirect */
				if ( Request::i()->isAjax() )
				{
					Output::i()->json( array( 'redirect' => (string) $category->url() ) );
				}
				else
				{
					Output::i()->redirect( $category->url() );
				}
			}

			return Theme::i()->getTemplate( 'submit' )->bulkForm( $form, $category );
		};


		/* Build Wizard */
		$url = Url::internal( 'app=downloads&module=downloads&controller=submit&do=submit', 'front', 'downloads_submit' );
		if ( isset( Request::i()->category ) and Request::i()->category )
		{
			$url = $url->setQueryString( 'category', Request::i()->category );
		}
		if ( isset( Request::i()->bulk ) and Request::i()->bulk  )
		{
			$url = $url->setQueryString( 'bulk', 1 );
		}
		$wizard = new Wizard( $steps, $url );
		$wizard->template = array( Theme::i()->getTemplate( 'submit' ), 'wizardForm' );
		
		/* Online User Location */
		Session::i()->setLocation( Url::internal( 'app=downloads&module=downloads&controller=submit', 'front', 'downloads_submit' ), array(), 'loc_downloads_adding_file' );
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack( isset( Request::i()->bulk ) ? 'submit_multiple_files' : 'submit_a_file' );
		if ( \IPS\IN_DEV )
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/moxie.js', 'core', 'interface' ) );
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/plupload.dev.js', 'core', 'interface' ) );
		}
		else
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'plupload/plupload.full.min.js', 'core', 'interface' ) );
		}
		
		$category = NULL;
		Output::i()->sidebar['enabled'] = FALSE;
		if ( isset( Request::i()->category ) )
		{
			try
			{
				$category = Category::loadAndCheckPerms( Request::i()->category );
				if ( $club = $category->club() )
				{
					FrontNavigation::$clubTabActive = TRUE;
					Output::i()->breadcrumb = array();
					Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
					Output::i()->breadcrumb[] = array( $club->url(), $club->name );
					Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
				}
			}
			catch ( OutOfRangeException $e ) { }
		}
		
		Output::i()->output = (string) $wizard;
		
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'submit_a_file' ) );
	}
}