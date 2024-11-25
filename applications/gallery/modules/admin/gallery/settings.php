<?php
/**
 * @brief		Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Mar 2014
 */

namespace IPS\gallery\modules\admin\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\WidthHeight;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings as SettingsClass;
use IPS\Task;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class settings extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		parent::execute();
	}

	/**
	 * Manage settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = $this->_getForm();

		if ( $values = $form->values() )
		{
			$this->_saveSettingsForm( $form, $values );

			Output::i()->redirect( Url::internal( 'app=gallery&module=gallery&controller=settings' ), 'saved' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('settings');
		Output::i()->output = $form;
	}

	/**
	 * Build and return the settings form
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	Form
	 */
	protected function _getForm(): Form
	{
		$form = new Form;

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_settings.js', 'gallery', 'admin' ) );
		$form->attributes['data-controller'] = 'gallery.admin.settings.settings';
		$form->hiddenValues['rebuildWatermarkScreenshots'] = Request::i()->rebuildWatermarkScreenshots ?: 0;

		$form->addTab( 'basic_settings' );
		$form->addHeader( 'gallery_images' );
		$form->addHtml( Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'gallery_dims_explanation', true, true )) ;
		$large	= ( isset( SettingsClass::i()->gallery_large_dims ) ) ? explode( 'x', SettingsClass::i()->gallery_large_dims ) : array( 1600, 1200 );
		$small	= ( isset( SettingsClass::i()->gallery_small_dims ) ) ? explode( 'x', SettingsClass::i()->gallery_small_dims ) : array( 240, 240 );
		$form->add( new WidthHeight( 'gallery_large_dims', $large, TRUE, array( 'resizableDiv' => FALSE ) ) );
		$form->add( new WidthHeight( 'gallery_small_dims', $small, TRUE, array( 'resizableDiv' => FALSE ) ) );
		$form->add( new YesNo( 'gallery_use_square_thumbnails', SettingsClass::i()->gallery_use_square_thumbnails ) );
		$form->add( new YesNo( 'gallery_use_watermarks', SettingsClass::i()->gallery_use_watermarks, FALSE, array( 'togglesOn' => array( 'gallery_watermark_path', 'gallery_watermark_images' ) ) ) );
		$form->add( new Upload( 'gallery_watermark_path', SettingsClass::i()->gallery_watermark_path ? File::get( 'core_Theme', SettingsClass::i()->gallery_watermark_path ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Theme' ), NULL, NULL, NULL, 'gallery_watermark_path' ) );
		$form->add( new CheckboxSet( 'gallery_watermark_images',
			SettingsClass::i()->gallery_watermark_images ? explode( ',', SettingsClass::i()->gallery_watermark_images ) : array(),
			FALSE,
			array(
				'multiple'			=> TRUE,
				'options'			=> array( 'large' => 'gallery_watermark_large', 'small' => 'gallery_watermark_small' ),
			),
			NULL,
			NULL,
			NULL,
			'gallery_watermark_images'
		) );

		$form->addHeader( 'gallery_bandwidth' );
		$form->add( new YesNo( 'gallery_detailed_bandwidth', SettingsClass::i()->gallery_detailed_bandwidth ) );
		$form->add( new Interval( 'gallery_bandwidth_period', SettingsClass::i()->gallery_bandwidth_period, FALSE, array( 'valueAs' => Interval::HOURS, 'min' => NULL, 'unlimited' => -1 ), NULL, NULL, NULL, 'gallery_bandwidth_period' ) );

		$form->addHeader( 'gallery_options' );
		$form->add( new YesNo( 'gallery_rss_enabled', SettingsClass::i()->gallery_rss_enabled ) );

		if( GeoLocation::enabled() )
		{
			$form->add( new YesNo( 'gallery_maps_default', SettingsClass::i()->gallery_maps_default ) );
		}

		$form->add( new YesNo( 'gallery_nsfw', SettingsClass::i()->gallery_nsfw ) );

		$form->addTab( 'gallery_overview_settings' );
		$form->addHeader( 'gallery_featured_images' );
		$form->add( new YesNo( 'gallery_overview_show_carousel', SettingsClass::i()->gallery_overview_show_carousel, FALSE, array( 'togglesOn' => array( 'gallery_overview_carousel_count', 'gallery_overview_carousel_type' ) ) ) );
		$options = array(
			'featured'		=> 'gallery_overview_carousel_featured',
			'new'		=> 'gallery_overview_carousel_new'
		);
		$form->add( new Radio( 'gallery_overview_carousel_type', SettingsClass::i()->gallery_overview_carousel_type, TRUE, array( 'options'	=> $options ), NULL, NULL, NULL, 'gallery_overview_carousel_type' ) );
		$form->add( new Number( 'gallery_overview_carousel_count', SettingsClass::i()->gallery_overview_carousel_count, FALSE, array(), NULL, NULL, NULL, 'gallery_overview_carousel_count' ) );

		$form->addHeader( 'gallery_overview_recent_comments' );
		$form->add( new YesNo( 'gallery_show_recent_comments', SettingsClass::i()->gallery_show_recent_comments, FALSE, array() ) );

		$form->addHeader('gallery_overview_categories');
		$form->add( new YesNo( 'gallery_overview_show_categories', SettingsClass::i()->gallery_overview_show_categories, FALSE, array() ) );

		$form->addHeader( 'gallery_overview_recent_updated_albums' );
		$form->add( new YesNo( 'gallery_show_recent_updated_albums', SettingsClass::i()->gallery_show_recent_updated_albums, FALSE, array( 'togglesOn' => array( 'gallery_recent_updated_albums_count' ) ) ) );
		$form->add( new Number( 'gallery_recent_updated_albums_count', SettingsClass::i()->gallery_recent_updated_albums_count, FALSE, array(), NULL, NULL, NULL, 'gallery_recent_updated_albums_count' ) );

		$form->addHeader( 'gallery_overview_new_images' );
		$form->add( new YesNo( 'gallery_show_new_images', SettingsClass::i()->gallery_show_new_images, FALSE, array( 'togglesOn' => array( 'gallery_new_images_count' ) ) ) );
		$form->add( new Number( 'gallery_new_images_count', SettingsClass::i()->gallery_new_images_count, FALSE, array(), NULL, NULL, NULL, 'gallery_new_images_count' ) );

		return $form;
	}

	/**
	 * Save the settings form
	 *
	 * @param Form 	$form		The Form Object
	 * @param array 				$values		Values
	 */
	protected function _saveSettingsForm( Form $form, array $values ) : void
	{
		$form->saveAsSettings( array(
			'gallery_large_dims'			=> implode( 'x', $values['gallery_large_dims'] ),
			'gallery_small_dims'			=> implode( 'x', $values['gallery_small_dims'] ),
			'gallery_use_square_thumbnails'	=> $values['gallery_use_square_thumbnails'],
			'gallery_watermark_path'		=> (string)  $values['gallery_watermark_path'],
			'gallery_detailed_bandwidth'	=> $values['gallery_detailed_bandwidth'],
			'gallery_bandwidth_period'		=> $values['gallery_bandwidth_period'],
			'gallery_rss_enabled'			=> $values['gallery_rss_enabled'],
			'gallery_watermark_images'		=> implode( ',', $values['gallery_watermark_images'] ),
			'gallery_use_watermarks'		=> $values['gallery_use_watermarks'],
			'gallery_maps_default'			=> $values['gallery_maps_default'] ?? 0,
			'gallery_nsfw'					=> $values['gallery_nsfw'],
			'gallery_overview_show_carousel'			=> $values['gallery_overview_show_carousel'],
			'gallery_overview_carousel_type'			=> $values['gallery_overview_carousel_type'],
			'gallery_overview_carousel_count'			=> $values['gallery_overview_carousel_count'],
			'gallery_show_recent_comments'				=> $values['gallery_show_recent_comments'],
			'gallery_overview_show_categories'			=> $values['gallery_overview_show_categories'],
			'gallery_show_recent_updated_albums'		=> $values['gallery_show_recent_updated_albums'],
			'gallery_recent_updated_albums_count'		=> $values['gallery_recent_updated_albums_count'],
			'gallery_show_new_images'					=> $values['gallery_show_new_images'],
			'gallery_new_images_count'					=> $values['gallery_new_images_count'],
		) );

		Session::i()->log( 'acplogs__gallery_settings' );

		if( $values['rebuildWatermarkScreenshots'] )
		{
			Db::i()->delete( 'core_queue', array( '`app`=? OR `key`=?', 'gallery', 'RebuildGalleryImages' ) );
			Task::queue( 'gallery', 'RebuildGalleryImages', array( ), 2 );
		}
	}
}