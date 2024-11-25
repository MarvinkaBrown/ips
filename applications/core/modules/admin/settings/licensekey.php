<?php
/**
 * @brief		licensekey
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\AdminNotification;
use IPS\Data\Store;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * licensekey
 */
class licensekey extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * @brief Data about the license key from the store
	 */
	protected array $licenseData = array();

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'licensekey_manage' );
		parent::execute();
	}

	/**
	 * License key overview screen
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Get license info.  If license info is empty, refresh it. */
		$licenseData = IPS::licenseKey();
		
		/* If no license key has been supplied yet just show the form */
		if( !Settings::i()->ipb_reg_number )
		{
			$this->settings();
			return;
		}
		
		/* Init */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('license_settings');
		Output::i()->sidebar['actions'] = array(
			'refresh'	=> array(
				'icon'	=> 'refresh',
				'link'	=> Url::internal( 'app=core&module=settings&controller=licensekey&do=refresh' )->csrf(),
				'title'	=> 'license_refresh',
				'class' => 'ipsButton--disabled',
			),
			'remove'	=> array(
				'icon'	=> 'pencil',
				'link'	=> Url::internal( 'app=core&module=settings&controller=licensekey&do=settings' ),
				'title'	=> 'license_change',
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('license_change') ),
				'class' => 'ipsButton--disabled',
			),
		);
		
		/* If we have a license key, but the server doesn't recognise it, show an error */
		if ( !$licenseData )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->message( 'license_not_recognised', 'error' );
		}
		/* Otherwise show the normal info */
		else
		{
			Output::i()->output = Theme::i()->getTemplate( 'licensekey', 'core' )->overview( $licenseData );
		}
	}

	/**
	 * Refresh the license key data stored locally
	 *
	 * @return	void
	 */
	protected function refresh() : void
	{
		Session::i()->csrfCheck();
		
		/* Fetch the license key data and update our local storage */
		IPS::licenseKey( TRUE );

		/* Return the overview screen afterwards */
		if ( isset( Request::i()->return ) and Request::i()->return === 'cloud' and Application::appIsEnabled('cloud') )
		{
			\IPS\cloud\Application::toggleDisabledApps();
			Output::i()->redirect( Url::internal( 'app=cloud&module=smartcommunity&controller=smartcommunity' ), 'cloud_license_refreshed' );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=licensekey' ), 'license_key_refreshed' );
		}
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$form = new Form;
		$form->add( new Text( 'ipb_reg_number', NULL, TRUE, array(), function( $val ){
			IPS::checkLicenseKey( $val, Settings::i()->base_url );
		} ) );

		if ( $values = $form->values() )
		{
			$values['ipb_reg_number'] = trim( $values['ipb_reg_number'] );

			if ( mb_substr( $values['ipb_reg_number'], -12 ) === '-TESTINSTALL' )
			{
				$values['ipb_reg_number'] = mb_substr( $values['ipb_reg_number'], 0, -12 );
			}
			
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplogs__license_settings' );

			/* Refresh the locally stored license info */
			unset( Store::i()->license_data );
			
			AdminNotification::remove( 'core', 'License', 'missing' );

			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=licensekey' ), 'saved' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('license_settings');
		Output::i()->output	= Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_licensekey', $form );
	}
}