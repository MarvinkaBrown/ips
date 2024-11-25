<?php
/**
 * @brief		Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		09 Oct 2013
 */

namespace IPS\downloads\modules\admin\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Form\Money;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings as SettingsClass;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings
 */
class settings extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Dispatcher::i()->checkAcpPermission( 'settings_manage' );

		$form = $this->_getForm();

		if ( $values = $form->values(TRUE) )
		{
			$this->_saveSettingsForm( $form, $values, $redirectMessage );

			Session::i()->log( 'acplogs__downloads_settings' );
			Output::i()->redirect( Url::internal( 'app=downloads&module=downloads&controller=settings' ), $redirectMessage );

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

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_settings.js', 'downloads', 'admin' ) );
		$form->attributes['data-controller'] = 'downloads.admin.settings.settings';
		$form->hiddenValues['rebuildWatermarkScreenshots'] = Request::i()->rebuildWatermarkScreenshots ?: 0;

		$form->addTab( 'idm_landing_page' );
		$form->addHeader( 'featured_downloads' );
        $form->add( new YesNo( 'idm_show_featured', SettingsClass::i()->idm_show_featured, FALSE, array( 'togglesOn' => array( 'idm_featured_count' ) ) ) );
        $form->add( new Number( 'idm_featured_count', SettingsClass::i()->idm_featured_count, FALSE, array(), NULL, NULL, NULL, 'idm_featured_count' ) );

		$form->addHeader('browse_whats_new');
        $form->add( new YesNo( 'idm_show_newest', SettingsClass::i()->idm_show_newest, FALSE, array('togglesOn' => array( 'idm_newest_categories') ) ) );
        $form->add( new Node( 'idm_newest_categories', ( SettingsClass::i()->idm_newest_categories AND SettingsClass::i()->idm_newest_categories != 0 ) ? explode( ',', SettingsClass::i()->idm_newest_categories ) : 0, FALSE, array(
            'class' => 'IPS\downloads\Category',
            'zeroVal' => 'any',
            'multiple' => TRUE ), NULL, NULL, NULL, 'idm_newest_categories') );

		$form->addHeader('browse_highest_rated');
        $form->add( new YesNo( 'idm_show_highest_rated', SettingsClass::i()->idm_show_highest_rated, FALSE, array( 'togglesOn' => array( 'idm_highest_rated_categories' ) ) ) );
		$form->add( new Node( 'idm_highest_rated_categories', ( SettingsClass::i()->idm_highest_rated_categories AND SettingsClass::i()->idm_highest_rated_categories != 0 ) ? explode( ',', SettingsClass::i()->idm_highest_rated_categories ) : 0, FALSE, array(
			'class' => 'IPS\downloads\Category',
			'zeroVal' => 'any',
			'multiple' => TRUE ), NULL, NULL, NULL, 'idm_highest_rated_categories') );

		$form->addHeader('browse_most_downloaded');
        $form->add( new YesNo( 'idm_show_most_downloaded', SettingsClass::i()->idm_show_most_downloaded, FALSE, array( 'togglesOn' => array( 'idm_show_most_downloaded_categories' ) ) ) );
		$form->add( new Node( 'idm_show_most_downloaded_categories', ( SettingsClass::i()->idm_show_most_downloaded_categories AND SettingsClass::i()->idm_show_most_downloaded_categories != 0 ) ? explode( ',', SettingsClass::i()->idm_show_most_downloaded_categories ) : 0, FALSE, array(
			'class' => 'IPS\downloads\Category',
			'zeroVal' => 'any',
			'multiple' => TRUE ), NULL, NULL, NULL, 'idm_show_most_downloaded_categories') );


        $form->addTab( 'basic_settings' );
		$form->add( new Upload( 'idm_watermarkpath', SettingsClass::i()->idm_watermarkpath ? File::get( 'core_Theme', SettingsClass::i()->idm_watermarkpath ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Theme' ) ) );
		$form->add( new Stack( 'idm_link_blacklist', explode( ',', SettingsClass::i()->idm_link_blacklist ), FALSE, array( 'placeholder' => 'example.com' ) ) );
		$form->add( new YesNo( 'idm_antileech', SettingsClass::i()->idm_antileech ) );
		$form->add( new YesNo( 'idm_rss', SettingsClass::i()->idm_rss ) );

		if ( Application::appIsEnabled( 'nexus' ) )
		{
			$form->addTab( 'paid_file_settings' );
			$form->add( new YesNo( 'idm_nexus_on', SettingsClass::i()->idm_nexus_on, FALSE, array( 'togglesOn' => array( 'idm_nexus_tax', 'idm_nexus_percent', 'idm_nexus_transfee', 'idm_nexus_mincost', 'idm_nexus_gateways', 'idm_nexus_display' ) ) ) );
			$form->add( new Node( 'idm_nexus_tax', SettingsClass::i()->idm_nexus_tax ?:0, FALSE, array( 'class' => '\IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ), NULL, NULL, NULL, 'idm_nexus_tax' ) );
			$form->add( new Number( 'idm_nexus_percent', SettingsClass::i()->idm_nexus_percent, FALSE, array( 'min' => 0, 'max' => 100 ), NULL, NULL, '%', 'idm_nexus_percent' ) );
			$form->add( new Money( 'idm_nexus_transfee', json_decode( SettingsClass::i()->idm_nexus_transfee, TRUE ), FALSE, array(), NULL, NULL, NULL, 'idm_nexus_transfee' ) );
			$form->add( new Money( 'idm_nexus_mincost', json_decode( SettingsClass::i()->idm_nexus_mincost, TRUE ), FALSE, array(), NULL, NULL, NULL, 'idm_nexus_mincost' ) );
			$form->add( new Node( 'idm_nexus_gateways', ( SettingsClass::i()->idm_nexus_gateways ) ? explode( ',', SettingsClass::i()->idm_nexus_gateways ) : 0, FALSE, array( 'class' => '\IPS\nexus\Gateway', 'zeroVal' => 'no_restriction', 'multiple' => TRUE ), NULL, NULL, NULL, 'idm_nexus_gateways' ) );
			$form->add( new CheckboxSet( 'idm_nexus_display', explode( ',', SettingsClass::i()->idm_nexus_display ), FALSE, array( 'options' => array( 'purchases' => 'idm_purchases', 'downloads' => 'downloads' ) ), NULL, NULL, NULL, 'idm_nexus_display' ) );
		}

		return $form;
	}

	/**
	 * Save the settings form
	 *
	 * @param Form $form The Form Object
	 * @param array $values Values
	 * @param string|null $redirectMessage
	 * @return void
	 */
	protected function _saveSettingsForm( Form $form, array $values, ?string &$redirectMessage ) : void
	{
		/* We can't store '' for idm_nexus_display as it will fall back to the default */
		if ( Application::appIsEnabled( 'nexus' ) and !$values['idm_nexus_display'] )
		{
			$values['idm_nexus_display'] = 'none';
		}

		$rebuildScreenshots = $values['rebuildWatermarkScreenshots'];

		unset( $values['rebuildWatermarkScreenshots'] );

		$form->saveAsSettings( $values );

		/* Save the form first, then queue the rebuild */
		if( $rebuildScreenshots )
		{
			Db::i()->delete( 'core_queue', array( '`app`=? OR `key`=?', 'downloads', 'RebuildScreenshotWatermarks' ) );

			Task::queue( 'downloads', 'RebuildScreenshotWatermarks', array( ), 5 );
			$redirectMessage = 'download_settings_saved_rebuilding';
		}
		else
		{
			$redirectMessage ='saved';
		}
	}
}