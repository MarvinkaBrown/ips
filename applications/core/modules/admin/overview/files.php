<?php
/**
 * @brief		File Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 May 2013
 */

namespace IPS\core\modules\admin\overview;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Application;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function sprintf;
use const IPS\CIC;
use const IPS\ROOT_PATH;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NUMERIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Settings
 */
class files extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Manage Attachment Types
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* This method is also called via ACP Member View with a CSRF key inline */
		Output::i()->bypassCsrfKeyCheck = true;

		Dispatcher::i()->checkAcpPermission( 'files_view' );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('uploaded_files');
		
		Output::i()->sidebar['actions'] = array();

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'overview', 'files_settings' ) )
		{
			Output::i()->sidebar['actions']['settings'] = array(
				'icon'	=> 'cog',
				'link'	=> Url::internal( 'app=core&module=overview&controller=files&do=settings' ),
				'title'	=> 'storage_settings',
			);

			Output::i()->sidebar['actions']['images'] = array(
				'icon'	=> 'cog',
				'link'	=> Url::internal( 'app=core&module=overview&controller=files&do=imagesettings' ),
				'title'	=> 'image_settings',
			);
		}

		/*		@todo - This needs fixing but has been temporarily been disabled
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'overview', 'orphaned_files' ) )
		{
			\IPS\Output::i()->sidebar['actions']['orphaned'] = array(
				'icon'	=> 'cog',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=overview&controller=files&do=orphaned' ),
				'title'	=> 'orphaned_files',
				'data'	=> array( 'confirm' => '', 'confirmMessage' => \IPS\Member::loggedIn()->language()->addToStack('orphaned_files_confirm') )
			);
		}*/
		
		$table = new TableDb( 'core_attachments', Url::internal( 'app=core&module=overview&controller=files' ) );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'dashboard' ), 'fileTable' );
		$table->rowsTemplate = array( Theme::i()->getTemplate( 'dashboard' ), 'fileTableRows' );
		$table->filters = array(
			'images'	=> "attach_is_image=1",
			'files'		=> "attach_is_image=0",
		);

		$table->include = array( 'attach_id', 'attach_file', 'attach_filesize', 'attach_date', 'attach_member_id' );
		$table->rowClasses	= array( 'attach_file' => array( 'ipsTable_wrap' ) );

		if ( $table->filter !== 'images' )
		{
			$table->include[] = 'attach_hits';
		}
		$table->noSort = array( 'attach_type', 'attach_id' );
		$table->quickSearch = 'attach_file';
		$table->parsers = array(
			'attach_file'	=> function( $val, $row ) use ( $table )
			{
				if ( $row['attach_is_image'] and $table->filter === 'images' )
				{
					$url = Url::external( File::get( 'core_Attachment', $row['attach_location'] )->url );
					$alt = $row['attach_file'];
					return "<a href='{$url}' target='_blank' rel='noopener'><img src='{$url}' style='max-height:200px' alt='{$alt}'></a>";
				}
				else
				{
					$url = Url::external( Settings::i()->base_url . "applications/core/interface/file/attachment.php" )->setQueryString( 'id', $row['attach_id'] );
					if ( $row['attach_security_key'] )
					{
						$url = $url->setQueryString( 'key', $row['attach_security_key'] );
					}
					$val = htmlentities( $val, ENT_DISALLOWED, 'UTF-8', FALSE );
					return "<a href='{$url}' target='_blank' rel='noreferrer'>{$val}</a>";
				}
			},
			'attach_filesize' => function( $val )
			{
				if ( $val < 1024 )
				{
					return "{$val}B";
				}
				elseif ( $val < 1048576 )
				{
					return round( ( $val / 1024 ), 2 ) . 'KB';
				}
				elseif ( $val < 1073741824 )
				{
					return round( ( $val / 1048576 ), 2 ) . 'MB';
				}
				else
				{
					return round( ( $val / 1073741824 ), 2 ) . 'GB';
				}
			},
			'attach_date' => function( $val )
			{
				return DateTime::ts( $val );
			},
			'attach_hits' => function( $val, $row )
			{
				return $row['attach_is_image'] ? '' : $val;
			},
			'attach_member_id' => function( $val )
			{
				if ( $val == 0 )
				{
					return Member::load( $val )->name;
				}
				else
				{
					return "<a href='" . Url::internal( 'app=core&module=members&controller=members&do=view&id=' . $val ) . "'>" . htmlentities( Member::load( $val )->name, ENT_DISALLOWED, 'UTF-8', FALSE ) . '</a>';
			
				}
			}
		);
		$table->advancedSearch = array(
			'attach_file'		=> SEARCH_CONTAINS_TEXT,
			'attach_ext'		=> SEARCH_CONTAINS_TEXT,
			'attach_hits'		=> SEARCH_NUMERIC,
			'attach_date'		=> SEARCH_DATE_RANGE,
			'attach_member_id'	=> SEARCH_MEMBER,
			'attach_filesize'	=> SEARCH_NUMERIC,
		);
		$table->rowButtons = function( $row )
		{
			$buttons = array();
			$buttons['view'] = array(
				'icon'	=> 'search',
				'title'	=> 'attach_view_locations',
				'link'	=> Url::internal( "app=core&module=overview&controller=files&do=lookup&id={$row['attach_id']}" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $row['attach_file'] )
			);
			
			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'overview', 'files_delete' ) )
			{
				$buttons['delete'] = array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link'	=> Url::internal( "app=core&module=overview&controller=files&do=delete&id={$row['attach_id']}" ),
					'data'		=> array( 'delete' => '' ),
				);
			}
			
			return $buttons;
		};

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_files.js', 'core', 'admin' ) );
		Output::i()->output = (string) $table;
	}
	
	/**
	 * Lookup attachment locations
	 *
	 * @return	void
	 */
	public function lookup() : void
	{
		Dispatcher::i()->checkAcpPermission( 'files_view' );
		
		$loadedExtensions = array();
		$locations = array();
		
		foreach ( Db::i()->select( '*', 'core_attachments_map', array( 'attachment_id=?', intval( Request::i()->id ) ) ) as $map )
		{
			if ( !isset( $loadedExtensions[ $map['location_key'] ] ) )
			{
				$exploded = explode( '_', $map['location_key'] );
				try
				{
					$extensions = Application::load( $exploded[0] )->extensions( 'core', 'EditorLocations' );
					if ( isset( $extensions[ $exploded[1] ] ) )
					{
						$loadedExtensions[ $map['location_key'] ] = $extensions[ $exploded[1] ];
					}
				}
				catch ( OutOfRangeException $e ){ }
			}
			
			if ( isset( $loadedExtensions[ $map['location_key'] ] ) AND method_exists( $loadedExtensions[ $map['location_key'] ], 'attachmentLookup' ) )
			{
				try
				{
					if ( $url = $loadedExtensions[ $map['location_key'] ]->attachmentLookup( $map['id1'], $map['id2'], $map['id3'] ) )
					{
						$locations[] = $url;
					}
				}
				catch ( LogicException $e ) { }
			}
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( NULL, Theme::i()->getTemplate( 'members', 'core', 'global' )->attachmentLocations( $locations, FALSE ), TRUE, 'i-padding_3' );
	}
	
	/**
	 * Delete attachment
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		Dispatcher::i()->checkAcpPermission( 'files_delete' );

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$attachment = Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', Request::i()->id ) )->first();
			
			try
			{
				File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
				File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
			}
			catch ( Exception $e ) { }
			
			Db::i()->delete( 'core_attachments', array( 'attach_id=?', Request::i()->id ) );
			
			Session::i()->log( 'acplogs__file_deleted', array( $attachment['attach_file'] => FALSE ) );
		}
		catch ( UnderflowException $e ) { }
		
		Output::i()->redirect( Url::internal( "app=core&module=overview&controller=files" ) );
	}

	/**
	 * Image Settings
	 * 
	 * @return	void
	 */
	protected function imagesettings() : void
	{
		/* Init */
		Dispatcher::i()->checkAcpPermission( 'files_settings' );

		$form = new Form;

		$form->add( new Radio( 'image_suite', class_exists( 'Imagick', FALSE ) ? Settings::i()->image_suite : 'gd', TRUE, array(
			'options' => array( 'gd' => 'imagesuite_gd', 'imagemagick' => 'imagesuite_imagemagick' ),
			'toggles' => array( 'imagemagick' => array( 'image_jpg_quality', 'imagick_strip_exif' ), 'gd' => array( 'image_jpg_quality', 'image_png_quality_gd' ) ),
			'disabled'=> class_exists( 'Imagick', FALSE ) ? array() : array( 'imagemagick' )
		) ) );

		$form->add( new Number( 'image_jpg_quality', Settings::i()->image_jpg_quality, FALSE, array( 'min' => 0, 'max' => 100, 'range' => TRUE, 'step' => 1 ), NULL, NULL, NULL, 'image_jpg_quality' ) );
		$form->add( new Number( 'image_png_quality_gd', Settings::i()->image_png_quality_gd, FALSE, array( 'min' => 0, 'max' => 9, 'range' => TRUE, 'step' => 1 ), NULL, NULL, NULL, 'image_png_quality_gd' ) );

		$form->add( new YesNo( 'imagick_strip_exif', Settings::i()->imagick_strip_exif, FALSE, array(), NULL, NULL, NULL, 'imagick_strip_exif' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings();

			Session::i()->log( 'acplogs__image_settings_updated' );
			
			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=files&do=imagesettings' ), 'saved' );
		}

		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('image_settings');
		Output::i()->output = $form;
	}

	/**
	 * Upload Settings
	 * 
	 * @return	void
	 */
	protected function settings() : void
	{
		/* Init */
		Dispatcher::i()->checkAcpPermission( 'files_settings' );

		$totalConfigurations = (int) Db::i()->select( 'count(*)', 'core_file_storage' )->first();
		$cicConfigurations = 0;
		if( CIC )
		{
			$cicConfigurations = (int) Db::i()->select( 'count(*)', 'core_file_storage', array( "method=? AND configuration LIKE CONCAT( '%', ?, '%' )", 'Amazon', 'ips-cic-filestore' ) )->first();
		}

		/* If we have only one configuration, don't bother showing the settings tab */
		if( $totalConfigurations == 1 )
		{
			$tabs = [ 'configurations' => 'filestorage_configurations' ];
			$activeTab = 'configurations';
		}
		else
		{
			$tabs = [ 'settings' => 'filestorage_settings', 'configurations' => 'filestorage_configurations' ];
			$activeTab = Request::i()->tab ?? 'settings';
		}
				
		/* Settings form */
		if ( $activeTab === 'settings' )
		{
			$settings = json_decode( Settings::i()->upload_settings, TRUE );
			
			$configurations	= array();
			$cicDisabled	= FALSE;

			if( CIC )
			{
				$cicDisabled = array();
			}

			foreach ( Db::i()->select( '*', 'core_file_storage' ) as $row )
			{
				$handlers	= File::storageHandlers( $row );
				$classname	= $handlers[ $row['method'] ];
				$configurations[ $row['id'] ] = $classname::displayName( json_decode( $row['configuration'], TRUE ) );

				if( $row['method'] == 'FileSystem' AND CIC )
				{
					$cicDisabled[ $row['id'] ] = $row['id'];
				}
			}
			
			$form = new Form;
			$form->addMessage( 'filestorage_move_info' );
			foreach ( Application::allExtensions( 'core', 'FileStorage', FALSE, NULL, NULL, TRUE ) as $name => $obj )
			{
				$disabled = ( isset( $settings[ "filestorage__{$name}" ] ) and is_array( $settings[ "filestorage__{$name}" ] ) );
				$value    = NULL;
				
				if ( isset( $settings[ "filestorage__{$name}" ] ) )
				{
					if ( is_array( $settings[ "filestorage__{$name}" ] ) )
					{
						$copyOfSettings = $settings[ "filestorage__{$name}" ];
						$value = array_shift( $copyOfSettings );
					}
					else
					{
						$value = $settings[ "filestorage__{$name}" ];
					}
				}

				$toggles = array();

				foreach( $configurations as $id => $title )
				{
					if( $id != $value )
					{
						$toggles[ $id ] = array( 'filestorage_move' );
					}
				}

				$handlerDisabled = $cicDisabled;

				if( isset( $handlerDisabled[ $value ] ) )
				{
					unset( $handlerDisabled[ $value ] );
				}

				$form->add( new Select( 'filestorage__' . $name, (int) $value, TRUE, array( 'options' => $configurations, 'disabled' => $disabled ?: $handlerDisabled, 'toggles' => $toggles ) ) );
				
				if ( $disabled )
				{
					Member::loggedIn()->language()->words[ 'filestorage__' . $name . '_warning' ] = Member::loggedIn()->language()->addToStack( 'file_storage_move_in_progress' );
				}
			}

			$form->add( new YesNo( 'filestorage_move', TRUE, FALSE, array(), NULL, NULL, NULL, 'filestorage_move' ) );
						
			if ( $values = $form->values() )
			{
				/* Block moves to filesystem on CIC */
				foreach ( $values as $k => $v )
				{
					if ( isset( $settings[ $k ] ) AND !is_array( $settings[ $k ] ) )
					{
						if( $settings[ $k ] != $v AND $k != 'filestorage_move' AND CIC AND in_array( $v, $cicDisabled ) )
						{
							Output::i()->error( 'file_storage_cic_filesystem', '3C158/7', 403, '' );
						}
					}
				}

				if( isset( $values['filestorage_move'] ) AND $values['filestorage_move'] )
				{
					$rebuild = FALSE;

	                /* Queue theme first */
	                if( isset( $values['filestorage__core_Theme'] ) and $settings['filestorage__core_Theme'] != $values['filestorage__core_Theme'] )
	                {
	                    $rebuild = TRUE;
	                    $extension = new \IPS\core\extensions\core\FileStorage\Theme;
	                    
	                    Task::queue( 'core', 'MoveFiles', array( 'storageExtension' => 'filestorage__core_Theme', 'oldConfiguration' => $settings[ 'filestorage__core_Theme' ], 'newConfiguration' => $values[ 'filestorage__core_Theme' ], 'count' => $extension->count() ), 1 );
						
						/* Add to allowed storage methods so when moving files, we can accept old config or new config if move is in progress. Important: order is array(x, y) x is the new location (pos 0), y is the old location (pos 1)*/
						$values['filestorage__core_Theme'] = array( $values['filestorage__core_Theme'], $settings['filestorage__core_Theme'] );
	                }
					$totalCount = 0;
					foreach ( $values as $k => $v )
					{
						if ( isset( $settings[$k] ) AND !is_array( $settings[$k] ) )
						{
							if ( $settings[ $k ] != $v and $k != 'filestorage__core_Theme' AND $k != 'filestorage_move' )
							{
								/* Do we need to move files at all? */
								$configurations = File::getStore();
								$exploded = explode( '_', $k );
								$classname = "IPS\\{$exploded[2]}\\extensions\\core\\FileStorage\\{$exploded[3]}";
								$extension = new $classname;
								$currentClass = File::getClass( intval( $settings[ $k ] ) );
								$newClass = File::getClass( intval( $v ) );
								
								if ( ( isset( $configurations[ $v ] ) and isset( $configurations[ $settings[ $k ] ] ) ) and ( get_class( $currentClass ) == get_class( $newClass ) ) and ! $newClass::moveCheck( $newClass->configuration, $currentClass->configuration ) )
								{
									$rebuild = FALSE;
								}
								else
								{
									$rebuild = TRUE;
								}

								if ( $rebuild )
								{
									$count  = $extension->count();
									
									if ( $count )
									{
										$totalCount += $count;
										Task::queue( 'core', 'MoveFiles', array( 'storageExtension' => $k, 'oldConfiguration' => $settings[ $k ], 'newConfiguration' => $values[ $k ], 'count' => $count ), 2 );
										
										/* Add to allowed storage methods so when moving files, we can accept old config or new config if move is in progress. Important: order is array(x, y) x is the new location (pos 0), y is the old location (pos 1)*/
										$values[ $k ] = array( $v, $settings[ $k ] );
									}
								}
							}
						}
					}
					
					if( $rebuild )
					{
						Task::queue( 'core', 'DeleteMovedFiles', array( 'delete' => true, 'count' => $totalCount ), 5, array( 'delete' ) ); /* We use a key in the data array just to trigger the code that deletes duplicate tasks */
					}
				}

				/* Update the settings */
				Settings::i()->changeValues( array( 'upload_settings' => json_encode( $values ) ) );

				/* Clear guest page caches */
				Cache::i()->clearAll();

				Session::i()->log( 'acplogs__files_config_moved' );
				
				Output::i()->redirect( Url::internal('app=core&module=overview&controller=files&do=settings&tab=settings'), 'saved' );
			}
			
			$activeTabContents = $form;
		}
		/* Or configurations table */
		else
		{
			$table = new TableDb( 'core_file_storage', Url::internal( "app=core&module=overview&controller=files&do=settings&tab=configurations" ) );
			$table->include = array( 'filestorage_method' );
			$table->noSort = array( 'filestorage_method' );
			$table->mainColumn = 'filestorage_method';
			$table->parsers = array( 'filestorage_method' => function( $val, $row )
			{
				$handlers	= File::storageHandlers( $row );
				$classname	= $handlers[ $row['method'] ];
				$title		= $classname::displayName( json_decode( $row['configuration'], TRUE ) );

				if( CIC AND $row['method'] == 'FileSystem' )
				{
					return Theme::i()->getTemplate( 'dashboard' )->filesystemNotCic( $title );
				}

				return $title;
			} );

			/* If we already have a storage configuration, we cannot create any new ones */
			if( $totalConfigurations - $cicConfigurations == 0 )
			{
				$table->rootButtons = array( 'add' => array(
					'icon'	=> 'plus',
					'title'	=> 'add',
					'link'	=> Url::internal( "app=core&module=overview&controller=files&do=configurationForm" )
				) );
			}

			$settings = json_decode( Settings::i()->upload_settings, TRUE );
			$table->rowButtons = function( $row ) use ( $settings )
			{
				$buttons = array(
					'log'	=> array(
						'icon'	=> 'search',
						'title'	=> 'file_config_log_title',
						'link'	=> Url::internal( "app=core&module=overview&controller=files&do=configurationLog&id={$row['id']}" )
					),
					'move' => array(
						'icon' => 'arrow-right',
						'title' => 'files_type_move',
						'link' => Url::internal( "app=core&module=overview&controller=files&do=configurationMove&id={$row['id']}" )
					)
				);

				if( ( !isset( $settings['filestorage_move'] ) or !$settings['filestorage_move'] ) and !in_array( $row['id'], $settings ) )
				{
					$buttons['delete'] = array(
						'icon' => 'times-circle',
						'title' => 'delete',
						'link' => Url::internal( "app=core&module=overview&controller=files&do=configurationDelete&id={$row['id']}" )->csrf(),
						'data' => array( 'confirm' => '' )
					);
				}

				return $buttons;
			};
			
			$activeTabContents = $table;
		}

		/* Add a button for settings */
		Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
						'title'		=> 'settings',
						'icon'		=> 'cog',
						'link'		=> Url::internal( 'app=core&module=overview&controller=files&do=fileLogSettings' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('settings') )
				),
		);
		
		/* Display */
		if ( Request::i()->isAjax() and !isset( Request::i()->ajaxValidate ) )
		{
			Output::i()->output = $activeTabContents;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('storage_settings');

			if( count( $tabs ) == 1 )
			{
				Output::i()->output = $activeTabContents;
			}
			else
			{
				$message = '';
				if( $totalConfigurations - $cicConfigurations > 1 )
				{
					$message = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack( 'multiple_filestorage_deprecated' ), 'warning' );
				}

				Output::i()->output = $message . Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, Url::internal( "app=core&module=overview&controller=files&do=settings" ) );
			}
		}
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function fileLogSettings() : void
	{
		$form = new Form;
		$form->add( new Interval( 'file_log_pruning', Settings::i()->file_log_pruning, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'file_log_pruning' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__filelog_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=files&do=settings' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('filelog_settings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'filelog_settings', $form, FALSE );
	}

	protected function configurationMove() : void
	{
		/* Get existing */
		try
		{
			$current = Db::i()->select( '*', 'core_file_storage', array( 'id=?', intval( Request::i()->id ) ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C158/3', 404, '' );
		}

		$currentHandlerSettings = json_decode( $current['configuration'], TRUE );

		/* Check if we are in the middle of a move */
		foreach ( Db::i()->select( 'data', 'core_queue', array( '`key`=?', 'MoveFiles' ) ) as $data )
		{
			$data = json_decode( $data, TRUE );
			if ( $data['oldConfiguration'] == Request::i()->id )
			{
				Output::i()->error( 'file_storage_move_out', '1C158/3', 403, '' );
			}
			elseif ( $data['newConfiguration'] == Request::i()->id )
			{
				Output::i()->error( 'file_storage_move_in', '1C158/4', 403, '' );
			}
		}

		/* Do we already have more than one file storage location configured? */
		$configurations = [];
		$currentDisplayName = '';
		foreach ( Db::i()->select( '*', 'core_file_storage' ) as $row )
		{
			$handlers	= File::storageHandlers( $row );
			$classname	= $handlers[ $row['method'] ];

			if( $row['id'] == $current['id'] )
			{
				$currentDisplayName = $classname::displayName( json_decode( $row['configuration'], TRUE ) );
				continue;
			}

			$configurations[ $row['id'] ] = $classname::displayName( json_decode( $row['configuration'], TRUE ) );
		}

		$form = new Form( 'form', 'move' );
		$form->addHeader( sprintf( Member::loggedIn()->language()->get( 'filestorage_moveall_from' ), $currentDisplayName ) );
		if( count( $configurations ) )
		{
			$form->add( new Radio( 'files_move_to', null, true, [
				'options' => $configurations,
				'noDefault' => true,
				'disabled' => [ $current['id'] ]
			] ) );
		}
		else
		{
			/* if we only have one location, allow them to create another one */
			$handlers = array();
			$handlerSettings = array();
			$toggles = array();
			foreach ( File::storageHandlers( $current ) as $key => $class )
			{
				$handlers[ $key ] = 'filehandler__' . $key;
				foreach ( $class::settings( $currentHandlerSettings ) as $k => $v )
				{
					if ( is_array( $v ) )
					{
						$settingClass = '\IPS\Helpers\Form\\' . $v['type'];

						$default = isset( $currentHandlerSettings[ $k ] ) ? str_replace( '{root}', ROOT_PATH, $currentHandlerSettings[ $k ] ) : NULL;
						if ( isset( $v['default'] ) and !$default )
						{
							$default = str_replace( '{root}', ROOT_PATH, $v['default'] );
						}

						$handlerSettings[ $key ][ $k ] = new $settingClass( "filehandler__{$key}_{$k}", $default, FALSE, $v['options'] ?? array(), $v['validate'] ?? NULL, $v['prefix'] ?? NULL, $v['suffix'] ?? NULL, "{$key}_{$k}" );
					}
					else
					{
						$settingClass = '\IPS\Helpers\Form\\' . $v;
						$handlerSettings[ $key ][ $k ] = new $settingClass( "filehandler__{$key}_{$k}", $currentHandlerSettings[$k] ?? NULL, FALSE, array(), NULL, NULL, NULL, "{$key}_{$k}" );
					}
					$toggles[ $key ][ $k ] = "{$key}_{$k}";
				}
			}

			/* Build form */
			$form->add( new Radio( 'filestorage_method', $current ? $current['method'] : ( CIC ? 'Amazon' : 'FileSystem' ), TRUE, array( 'options' => $handlers, 'toggles' => $toggles ) ) );
			foreach ( $handlerSettings as $handlerKey => $settings )
			{
				foreach ( $settings as $setting )
				{
					$form->add( $setting );
				}
			}
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( isset( $values['files_move_to'] ) )
			{
				$newStorageId = $values['files_move_to'];
			}
			else
			{
				try
				{
					if ( isset( $toggles[ $values['filestorage_method'] ] ) )
					{
						foreach ( $toggles[ $values['filestorage_method'] ] as $k => $v )
						{
							$currentHandlerSettings[ $k ] = ( ROOT_PATH !== '/' ) ? str_replace( ROOT_PATH, '{root}', $values[ 'filehandler__' . $v ] ) : $values[ 'filehandler__' . $v ];
						}
					}

					$classname = File::storageHandlers( $current )[ $values['filestorage_method'] ];
					if ( method_exists( $classname, 'testSettings' ) )
					{
						$classname::testSettings( $currentHandlerSettings );
					}

					$existingWithSameConfig = false;
					/* Make sure there are no other configurations that are exactly the same */
					foreach( Db::i()->select( '*', 'core_file_storage', array( 'method=?', $values['filestorage_method'] ) ) as $existing )
					{
						if ( $current and $current['id'] == $existing['id'] )
						{
							continue;
						}

						$existingWithSameConfig = true;
						$existingConfiguration = json_decode( $existing['configuration'], true );
						foreach( $existingConfiguration as $k => $v )
						{
							$v = str_replace( ROOT_PATH, '{root}', $v );

							if ( array_key_exists( $k, $currentHandlerSettings ) )
							{
								if ( $v != $currentHandlerSettings[ $k ] )
								{
									$existingWithSameConfig = false;
								}
							}
						}
					}

					if ( $existingWithSameConfig )
					{
						/* Let's not allow this to be saved as someone can start a move to the same location, which will end up deleting the files */
						throw new DomainException( Member::loggedIn()->language()->addToStack( 'file_config_is_the_same_as_existing', FALSE ) );
					}

					/* Create the new location */
					$newStorageId = Db::i()->insert( 'core_file_storage', array(
						'method'		=> $values['filestorage_method'],
						'configuration'	=> json_encode( $currentHandlerSettings ),
					) );
					unset( Store::i()->storageConfigurations );

					/* Log the storage addition */
					Session::i()->log( 'acplogs__files_config_added' );
				}
				catch ( LogicException $e )
				{
					$msg = $e->getMessage();
					$form->error = Member::loggedIn()->language()->addToStack( $msg );
				}
			}

			if( isset( $newStorageId ) )
			{
				$settings = json_decode( Settings::i()->upload_settings, TRUE );
				$totalCount = 0;
				foreach ( $settings as $k => $v )
				{
					if ( $v == $current['id'] )
					{
						$exploded = explode( '_', $k );
						try
						{
							$classname = "IPS\\{$exploded[2]}\\extensions\\core\\FileStorage\\{$exploded[3]}";

							if( Application::appIsEnabled( $exploded[2] ) AND class_exists( $classname ) )
							{
								$extension = new $classname;
								$count = $extension->count();

								/* Don't bother with this task if there is nothing to move */
								if( $count > 0 )
								{
									$totalCount += $count;
									Task::queue( 'core', 'MoveFiles', array( 'storageExtension' => $k, 'oldConfiguration' => $v, 'newConfiguration' => $newStorageId, 'count' => $count ), 2 );
								}
							}

							$settings[ $k ] = $newStorageId;
						}
						catch( Exception $e ){}
					}
				}

				Settings::i()->changeValues( array( 'upload_settings' => json_encode( $settings ) ) );

				Task::queue( 'core', 'DeleteMovedFiles', array( 'delete' => true, 'count' => $totalCount, 'storageToDelete' => $current['id'] ), 5, array( 'delete' ) ); /* We use a key in the data array just to trigger the code that deletes duplicate tasks */

				Output::i()->redirect( Url::internal( "app=core&module=overview&controller=files&do=settings" ) );
			}
		}

		/* Display */
		$message = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack( 'filestorage_moveall_info' ), 'warning' );
		Output::i()->title = Member::loggedIn()->language()->addToStack('storage_settings');
		Output::i()->breadcrumb[] = array( Url::internal( "&app=core&module=overview&controller=files&do=settings&tab=configurations" ), 'filestorage_settings' );
		Output::i()->output = $message . $form;
	}
	
	/**
	 * Add/Edit Configuration
	 *
	 * @return	void
	 */
	protected function configurationForm() : void
	{
		/* Are we allowed to create a new location? */
		$totalConfigurations = (int) Db::i()->select( 'count(*)', 'core_file_storage' )->first();
		$cicConfigurations = 0;
		if( CIC )
		{
			$cicConfigurations = (int) Db::i()->select( 'count(*)', 'core_file_storage', array( "method=? AND configuration LIKE CONCAT( '%', ?, '%' )", 'Amazon', 'ips-cic-filestore' ) )->first();
		}
		if( $totalConfigurations - $cicConfigurations > 0 )
		{
			Output::i()->error( 'multiple_filestorage_error', '2C158/3', 403 );
		}

		/* Get handlers */
		$handlers = array();
		$handlerSettings = array();
		$toggles = array();
		foreach ( File::storageHandlers( NULL ) as $key => $class )
		{
			$handlers[ $key ] = 'filehandler__' . $key;
			foreach ( $class::settings() as $k => $v )
			{
				if ( is_array( $v ) )
				{
					$settingClass = '\IPS\Helpers\Form\\' . $v['type'];
					
					$default = NULL;
					if ( isset( $v['default'] ) and !$default )
					{
						$default = str_replace( '{root}', ROOT_PATH, $v['default'] );
					}
					
					$handlerSettings[ $key ][ $k ] = new $settingClass( "filehandler__{$key}_{$k}", $default, FALSE, $v['options'] ?? array(), $v['validate'] ?? NULL, $v['prefix'] ?? NULL, $v['suffix'] ?? NULL, "{$key}_{$k}" );
				}
				else
				{
					$settingClass = '\IPS\Helpers\Form\\' . $v;
					$handlerSettings[ $key ][ $k ] = new $settingClass( "filehandler__{$key}_{$k}", NULL, FALSE, array(), NULL, NULL, NULL, "{$key}_{$k}" );
				}
				$toggles[ $key ][ $k ] = "{$key}_{$k}";
			}
		}
		
		/* Build form */
		$form = new Form;
		$form->add( new Radio( 'filestorage_method', ( CIC ? 'Amazon' : 'FileSystem' ), TRUE, array( 'options' => $handlers, 'toggles' => $toggles ) ) );
		foreach ( $handlerSettings as $handlerKey => $settings )
		{
			foreach ( $settings as $setting )
			{
				$form->add( $setting );
			}
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$currentHandlerSettings = [];
			try
			{
				if ( isset( $toggles[ $values['filestorage_method'] ] ) )
				{
					foreach ( $toggles[ $values['filestorage_method'] ] as $k => $v )
					{
						$currentHandlerSettings[ $k ] = ( ROOT_PATH !== '/' ) ? str_replace( ROOT_PATH, '{root}', $values[ 'filehandler__' . $v ] ) : $values[ 'filehandler__' . $v ];
					}
				}

				$classname = File::storageHandlers( NULL )[ $values['filestorage_method'] ];
				if ( method_exists( $classname, 'testSettings' ) )
				{
					$classname::testSettings( $currentHandlerSettings );
				}
				
				$existingWithSameConfig = false;
				/* Make sure there are no other configurations that are exactly the same */
				foreach( Db::i()->select( '*', 'core_file_storage', array( 'method=?', $values['filestorage_method'] ) ) as $existing )
				{
					$existingWithSameConfig = true;
					$existingConfiguration = json_decode( $existing['configuration'], true );
					foreach( $existingConfiguration as $k => $v )
					{
						$v = str_replace( ROOT_PATH, '{root}', $v );
						
						if ( array_key_exists( $k, $currentHandlerSettings ) )
						{
							if ( $v != $currentHandlerSettings[ $k ] )
							{
								$existingWithSameConfig = false;
							}
						}
					}
				}

				if ( $existingWithSameConfig )
				{
					/* Let's not allow this to be saved as someone can start a move to the same location, which will end up deleting the files */
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'file_config_is_the_same_as_existing', FALSE ) );
				}

				$insertId = Db::i()->insert( 'core_file_storage', array(
					'method'		=> $values['filestorage_method'],
					'configuration'	=> json_encode( $currentHandlerSettings ),
				) );
				unset( Store::i()->storageConfigurations );

				/* Log the storage addition */
				Session::i()->log( 'acplogs__files_config_added' );

				Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=files&do=settings&tab=configurations' ), 'saved' );
			}
			catch ( LogicException $e )
			{
				$msg = $e->getMessage();
				$form->error = Member::loggedIn()->language()->addToStack( $msg );
			}
		}
		
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_files.js', 'core' ) );
		Output::i()->globalControllers[]  = 'core.admin.files.form';
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('storage_settings');
		Output::i()->output = $form;
	}

	/**
	 * Delete a file storage method
	 *
	 * @return void
	 */
	protected function configurationDelete() : void
	{
		Request::i()->confirmedDelete();

		try
		{
			$storage = Db::i()->select( '*', 'core_file_storage', [ 'id=?', Request::i()->id ] )->first();
		}
		catch( \UnderflowException )
		{
			Output::i()->error( 'node_error', '2C158/7', 404 );
		}

		$settings = json_decode( Settings::i()->upload_settings, TRUE );
		if( isset( $settings['filestorage_move'] ) and $settings['filestorage_move'] )
		{
			Output::i()->error( 'file_storage_delete_move_in_progress', '2C158/8' );
		}

		if( in_array( $storage['id'], $settings ) )
		{
			Output::i()->error( 'file_storage_delete_in_use', '2C158/9' );
		}

		Db::i()->delete( 'core_file_storage', [ 'id=?', Request::i()->id ] );

		Output::i()->redirect( Url::internal( "app=core&module=overview&controller=files&do=settings&tab=configurations" ) );
	}
	
	/**
	 * Determine if a move is needed (ajax method)
	 *
	 * @return void
	 */
	protected function checkMoveNeeded() : void
	{
		try
		{
			$current = Db::i()->select( '*', 'core_file_storage', array( 'id=?', intval( Request::i()->id ) ) )->first();
		}
		catch( UnderflowException $e )
		{
			Output::i()->json( array( 'needsMoving' => FALSE ) );
		}
		
		$currentConf = json_decode( $current['configuration'], TRUE );
		$needsMoving = FALSE;
		
		if ( in_array( intval( Request::i()->id ), json_decode( Settings::i()->upload_settings, TRUE ) ) )
		{
			foreach ( $currentConf as $k => $v )
			{
				$checkKey = 'filehandler__' . $current['method'] . '_' . $k;
				
				if ( isset( Request::i()->$checkKey ) )
				{
					$currentHandlerSettings[ $k ] = str_replace( ROOT_PATH, '{root}', Request::i()->$checkKey );
				}
			}

			$handlers	= File::storageHandlers( $current );
			$classname	= $handlers[ $current['method'] ];
			
			/* Do we really need to create and move? */
			$needsMoving = $classname::moveCheck( $currentHandlerSettings, $currentConf );
		}
		
		Output::i()->json( array( 'needsMoving' => (boolean) $needsMoving ) );
	}
	
	/**
	 * View logs for this configuration
	 *
	 * @return	void
	 */
	protected function configurationLog() : void
	{
		$method = Db::i()->select( '*', 'core_file_storage', array( 'id=?', intval( Request::i()->id ) ) )->first();
		$title  = Member::loggedIn()->language()->addToStack( 'file_config_log', FALSE, array( 'sprintf' => array( $method['method'] ) ) );

		/* Create the table */
		$table = new TableDb( 'core_file_logs', Url::internal( 'app=core&module=overview&controller=files&do=configurationLog&id=' . Request::i()->id ), array( array( 'log_configuration_id=?', Request::i()->id ) ) );
		$table->langPrefix  = 'files_';
		$table->title       = $title;
		$table->quickSearch = 'log_filename';
		$table->sortBy      = 'log_date';

		$table->filters		= array(
			'files_log_filter_error'   => array('log_type=?', 'error' ),
			'files_log_filter_move'    => array('log_type=?', 'move' )
		);

		$table->include = array( 'log_date', 'log_type', 'log_action', 'log_msg', 'log_filename' );

		$table->parsers = array(
			'log_filename' => function( $val, $row )
			{
				return ( ! empty( $row['log_container'] ) ? $row['log_container'] . '/' : '' ) . $val;
			},
			'log_date' => function( $val )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'log_type' => function( $val )
			{
				return Member::loggedIn()->language()->addToStack( 'files_type_' . $val );
			},
			'log_action' => function( $val )
			{
				return Member::loggedIn()->language()->addToStack( 'files_action_' . $val );
			}
		);

		/* Display */
		Output::i()->breadcrumb[] = array( Url::internal( "&app=core&module=overview&controller=files&do=settings&tab=configurations" ), 'filestorage_settings' );
		Output::i()->output = (string) $table;
		Output::i()->title  = $title;
	}

	/**
	 * Remove orphaned files
	 *
	 * @return	void
	 */
	protected function orphaned() : void
	{
		Session::i()->csrfCheck();
		
		foreach( Db::i()->select( '*', 'core_file_storage', NULL, 'id' ) as $row )
		{
			Task::queue( 'core', 'FindOrphanedFiles', array( 'configurationId' => $row['id'] ), 4, array( 'configurationId' ) );
		}
	
		Output::i()->redirect( Url::internal( "app=core&module=overview&controller=files" ), 'orphaned_files_tasks_added' );
	}

	/**
	 * Multimod
	 *
	 * @return	void
	 */
	protected function multimod() : void
	{
		Dispatcher::i()->checkAcpPermission( 'files_delete' );

		Session::i()->csrfCheck();

		if( !isset( Request::i()->multimod ) OR !is_array( Request::i()->multimod ) OR !count( Request::i()->multimod ) )
		{
			Output::i()->error( 'nothing_mm_selected', '2C158/6', 403, '' );
		}

		foreach ( Db::i()->select( '*', 'core_attachments', Db::i()->in( 'attach_id', array_keys( Request::i()->multimod ) ) ) as $attachment )
		{
			File::get( 'core_Attachment', $attachment['attach_location'] )->delete();

			if( $attachment['attach_thumb_location'] )
			{
				File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
			}

			Session::i()->log( 'acplogs__file_deleted', array( $attachment['attach_file'] => FALSE ) );
		}

		Db::i()->delete( 'core_attachments', Db::i()->in( 'attach_id', array_keys( Request::i()->multimod ) ) );

		$url = Url::internal( "app=core&module=overview&controller=files" );

		if( Request::i()->listResort )
		{
			$url = $url->setQueryString( array( 'listResort' => 1, 'sortby' => Request::i()->sortby, 'sortdirection' => Request::i()->sortdirection ) )->csrf();
		}
		Output::i()->redirect( $url, 'deleted' );
	}
}