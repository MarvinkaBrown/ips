<?php
/**
 * @brief		general
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * general
 */
class general extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'general_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = new Form;
		$form->add( new Text( 'board_name', Settings::i()->board_name, TRUE ) );
		
		$form->add( new YesNo( 'site_online', Settings::i()->site_online, FALSE, array(
			'togglesOff'	=> array( 'site_offline_message_id' ),
		) ) );
		$form->add( new Editor( 'site_offline_message', Settings::i()->site_offline_message, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineoffline', 'attachIds' => array( NULL, NULL, 'site_offline_message' ) ), NULL, NULL, NULL, 'site_offline_message_id' ) );
		$form->add( new Address( 'site_address', GeoLocation::buildFromJson( Settings::i()->site_address ), FALSE ) );
		$form->add( new Stack( 'site_social_profiles', Settings::i()->site_social_profiles ? json_decode( Settings::i()->site_social_profiles, true ) : array(), FALSE, array( 'stackFieldType' => '\IPS\core\Form\SocialProfiles', 'maxItems' => 50, 'key' => array( 'placeholder' => 'https://example.com', 'size' => 20 ) ) ) );
		$form->add( new Text( 'site_twitter_id', Settings::i()->site_twitter_id, FALSE, array( 'placeholder' => Member::loggedIn()->language()->addToStack('site_twitter_id_placeholder'), 'size' => 20 ) ) );
		$form->add( new Translatable( 'copyright_line', NULL, FALSE, array( 'app' => 'core', 'key' => 'copyright_line_value', 'placeholder' => Member::loggedIn()->language()->addToStack('copyright_line_placeholder') ) ) );
		$form->add( new YesNo( 'relative_dates_enable', Settings::i()->relative_dates_enable, FALSE ) );
		$form->add( new YesNo( 'site_site_elsewhere', Settings::i()->site_site_elsewhere, FALSE, [ 'togglesOn' => [ 'site_main_url', 'site_main_title' ] ] ) );
		$form->add( new FormUrl( 'site_main_url', Settings::i()->site_main_url, FALSE, [], NULL, NULL, NULL, 'site_main_url' ) );
		$form->add( new Text( 'site_main_title', Settings::i()->site_main_title, FALSE, [], NULL, NULL, NULL, 'site_main_title' ) );
		
		if ( $values = $form->values() )
		{
			Lang::saveCustom( 'core', "copyright_line_value", $values['copyright_line'] );
			unset( $values['copyright_line'] );

			array_walk( $values['site_social_profiles'], function( &$value ){
				$value['key'] = (string) $value['key'];
			});
			$values['site_social_profiles']	= json_encode( array_filter( $values['site_social_profiles'], function( $value ) {
				return (bool) $value['key'];
			} ) );

			$values['site_address']			= json_encode( $values['site_address'] );
	
			$form->saveAsSettings( $values );
			
			if ( $values['site_online'] )
			{
				AdminNotification::remove( 'core', 'ConfigurationError', 'siteOffline' );
			}
			else
			{
				AdminNotification::send( 'core', 'ConfigurationError', 'siteOffline', FALSE, NULL, Member::loggedIn() );
			}

			/* Clear manifest and ie browser data stores */
			unset( Store::i()->manifest, Store::i()->iebrowserconfig );

			Session::i()->log( 'acplogs__general_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=general' ), 'saved' );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_settings_general');
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_general', $form );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'settings/general.css', 'core', 'admin' ) );
	}
}