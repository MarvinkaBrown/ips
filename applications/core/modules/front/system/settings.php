<?php
/**
 * @brief		User CP Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jun 2013
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DOMXpath;
use Exception;
use IPS\Api\OAuthClient;
use IPS\Application;
use IPS\core\ShareLinks\Service;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Email;
use IPS\Events\Event;
use IPS\Extensions\SSOAbstract;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Email as FormEmail;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Exception as LoginException;
use IPS\Login\Handler;
use IPS\Login\Success;
use IPS\Member;
use IPS\Member\Device;
use IPS\Member\PrivacyAction;
use IPS\Member\ProfileStep;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Session\Front;
use IPS\Session\Store;
use IPS\Settings as SettingsClass;
use IPS\Text\Encrypt;
use IPS\Theme;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function strlen;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * User CP Controller
 */
class settings extends Controller
{
	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'settings', 'odkUpdate' => true]
	);

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Only logged in members */
		if ( !Member::loggedIn()->member_id and !in_array( Request::i()->do, array( 'mfarecovery', 'mfarecoveryvalidate', 'invite' ) ) )
		{
			Output::i()->error( 'no_module_permission_guest', '2C122/1', 403, '' );
		}
		
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_system.js', 'core' ) );

		Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Work out output */
		$area = Request::i()->area ?: 'overview';
		$methodName = "_{$area}";
		if ( method_exists( $this, $methodName ) )
		{
			$output = $this->$methodName();
		}
		else
		{
			foreach( Application::allExtensions( 'core', 'AccountSettings', TRUE, 'core' ) as $ext )
			{
				if( $ext->getTab() == $area )
				{
					if( isset( Request::i()->action ) AND method_exists( $ext, Request::i()->action ) )
					{
						$method = Request::i()->action;
						$output = $ext->$method();
					}
					else
					{
						$output = $ext->getContent();
					}
				}
			}
		}
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('settings');
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('settings') );
		if ( !Request::i()->isAjax() )
		{
			if ( Request::i()->service )
			{
				$area = "{$area}_" . Request::i()->service;
			}
            
            Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/settings.css' ) );
            
            if ( $output )
            {
				Output::i()->output .= $this->_wrapOutputInTemplate( $area, $output );
			}
		}
		elseif ( $output )
		{
			Output::i()->output .= $output;
		}
	}
	
	/**
	 * Wrap output in template
	 *
	 * @param	string	$area	Active area
	 * @param	string	$output	Output
	 * @return	string
	 */
	protected function _wrapOutputInTemplate( string $area, string $output ) : string
	{
		/* What can we do? */
		$tabs = [
			'overview' => [
				'icon' => 'circle-user',
				'url' => Url::internal( "app=core&module=system&controller=settings", "front", "settings" )
			]
		];

		/* Can change email? */
		if( SettingsClass::i()->allow_email_changes != 'disabled' )
		{
			$tabs['email'] = [
				'icon' => 'envelope',
				'title' => 'email_address'
			];
			if( Member::loggedIn()->members_bitoptions['email_messages_bounce'] )
			{
				$tabs['email']['warning'] = true;
			}
		}

		/* Can change password? */
		$canChangePassword = (  SettingsClass::i()->allow_password_changes == 'redirect' );
		if (  SettingsClass::i()->allow_password_changes == 'normal' )
		{
			foreach ( Login::methods() as $method )
			{
				if ( $method->canChangePassword( Member::loggedIn() ) )
				{
					$canChangePassword = TRUE;
					break;
				}
			}
		}

		if( $canChangePassword )
		{
			$tabs['password'] = [ 'icon' => 'key' ];
		}

		/* MFA */
		$canConfigureMfa = FALSE;
		foreach ( MFAHandler::handlers() as $handler )
		{
			if ( $handler->isEnabled() and $handler->memberCanUseHandler( Member::loggedIn() ) )
			{
				$canConfigureMfa = TRUE;
				break;
			}
		}

		if( $canConfigureMfa OR !Member::loggedIn()->group['g_hide_online_list'] OR Member::loggedIn()->canUseAccountDeletion() OR SettingsClass::i()->pii_type != 'off' )
		{
			$tabs['mfa'] = [
				'icon' => 'lock',
				'title' => 'ucp_mfa'
			];
		}

		/* Devices */
		if( SettingsClass::i()->device_management )
		{
			$tabs['devices'] = [
				'icon' => 'laptop',
				'title' => 'ucp_devices'
			];
		}

		/* Can change username? */
		if( Member::loggedIn()->group['g_dname_changes'] )
		{
			$tabs['username'] = [ 'icon' => 'user' ];
		}

		/* Content Preferences */
		$tabs['links'] = [
			'icon' => 'file-pen',
			'title' => 'profile_settings_cvb'
		];

		/* Signature */
		if( Member::loggedIn()->canEditSignature() )
		{
			$tabs['signature'] = [ 'icon' => 'pencil' ];
		}

		/* Login methods */
		foreach( Login::methods() as $method )
		{
			if( $method->showInUcp( Member::loggedIn() ) )
			{
				$tabs['login_' . $method->id] = [
					'url' => Url::internal( "app=core&module=system&controller=settings&area=login&service=" . $method->id, "front", "settings_login" ),
					'title' => $method->_title
				];

				$icon = $method->logoForUcp();
				if( is_string( $icon ) )
				{
					$tabs['login_' . $method->id]['icon'] = 'brands fa-' . $icon;
				}
				else
				{
					$tabs['image'] = $icon;
				}
			}
		}

		/* Show our own oauth clients? */
		$showApps = (bool) Db::i()->select( 'COUNT(*)', 'core_oauth_clients', array( array( 'oauth_enabled=1 AND oauth_ucp=1' ) ) )->first();
		if( $showApps )
		{
			$tabs['apps'] = [
				'icon' => 'cubes',
				'title' => 'oauth_apps'
			];
		}

		/* Extensions */
		foreach( Application::allExtensions( 'core', 'AccountSettings', TRUE, 'core' ) as $ext )
		{
			if( $key = $ext->getTab() )
			{
				$tabs[ $key ] = [
					'icon' => $ext::$icon,
					'title' => $ext->getTitle(),
					'warning' => $ext->showWarning()
				];
			}
		}
				
		/* Return */
		return Theme::i()->getTemplate( 'system' )->settings( $area, $output, $tabs );
	}
	
	/**
	 * Overview
	 *
	 * @return	string
	 */
	protected function _overview() : string
	{
		$loginMethods = array();
		$canChangePassword = FALSE;
		
		foreach ( Login::methods() as $method )
		{
			if ( $method->showInUcp( Member::loggedIn() ) )
			{
				if ( $method->canProcess( Member::loggedIn() ) )
				{
					try
					{
						$name = $method->userProfileName( Member::loggedIn() );
						
						$loginMethods[ $method->id ] = array(
							'title'	=> $method->_title,
							'blurb'	=> $name ? Member::loggedIn()->language()->addToStack( 'profilesync_headline', FALSE, array( 'sprintf' => array( $name ) ) ) : Member::loggedIn()->language()->addToStack( 'profilesync_signed_in' ),
							'icon'	=> $method->userProfilePhoto( Member::loggedIn() )
						);
					}
					catch ( LoginException $e )
					{
						$loginMethods[ $method->id ] = array( 'title' => $method->_title, 'blurb' => Member::loggedIn()->language()->addToStack('profilesync_reauth_needed') );
					}
				}
				else
				{
					$loginMethods[ $method->id ] = array( 'title' => $method->_title, 'blurb' => Member::loggedIn()->language()->addToStack('profilesync_not_synced') );
				}
			}
			
			
			if ( $method->canChangePassword( Member::loggedIn() ) )
			{
				$canChangePassword = TRUE;
			}
		}

		if(  SettingsClass::i()->allow_password_changes == 'disabled' )
		{
			$canChangePassword = FALSE;
		}

		return Theme::i()->getTemplate( 'system' )->settingsOverview( $loginMethods, $canChangePassword );
	}
	
	/**
	 * Email
	 *
	 * @return	string
	 */
	protected function _email() : string
	{
		if (  SettingsClass::i()->allow_email_changes == 'redirect' )
		{
			Output::i()->redirect( Url::external(  SettingsClass::i()->allow_email_changes_target ) );
		}

		if(  SettingsClass::i()->allow_email_changes != 'normal' )
		{
			Output::i()->error( 'no_module_permission', '2C122/U', 403, '' );
		}
		
		if( Member::loggedIn()->isAdmin() )
		{
			return Theme::i()->getTemplate( 'system' )->settingsEmail();
		}
				
		$mfaOutput = MFAHandler::accessToArea( 'core', 'EmailChange', Url::internal( 'app=core&module=system&controller=settings&area=email', 'front', 'settings_email' ) );

		if ( $mfaOutput )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=email', 'front', 'settings_email' ) );
			}
			Output::i()->output = $mfaOutput;
		}
		
		/* Do we have any pending validation emails? */
		try
		{
			$pending = Db::i()->select( '*', 'core_validating', array( 'member_id=? AND email_chg=1', Member::loggedIn()->member_id ), 'entry_date DESC' )->first();
		}
		catch( UnderflowException $e )
		{
			$pending = null;
		}
		
		/* Build the form */
		$form = new Form;
		$form->class = 'ipsForm_collapseTablet';

		$currentEmail = htmlspecialchars( Member::loggedIn()->email, ENT_DISALLOWED, 'UTF-8', FALSE );
		$form->addDummy( 'current_email', Member::loggedIn()->members_bitoptions["email_messages_bounce"] ? Theme::i()->getTemplate( 'global', 'core' )->memberEmailBlockedMessage( $currentEmail ) : $currentEmail );
		$form->add( new FormEmail(
			'new_email',
			'',
			TRUE,
			array( 'accountEmail' => Member::loggedIn() )
		) );

		
		/* Handle submissions */
		$values = NULL;
		if ( !$mfaOutput and $values = $form->values() )
		{
			$_SESSION['newEmail'] = $values['new_email'];
		}
		if ( isset( $_SESSION['newEmail'] ) )
		{
			/* Reauthenticate */
			$login = new Login( Url::internal( 'app=core&module=system&controller=settings&area=email', 'front', 'settings_email' ), Login::LOGIN_REAUTHENTICATE );
			
			/* After re-authenticating, change the email */
			$error = NULL;
			try
			{

				if ( !count( $login->buttonMethods() ) or $success = $login->authenticate() )
				{
					/* Disable syncing */
					$profileSync = Member::loggedIn()->profilesync;
					if ( isset( $profileSync['email'] ) )
					{
						unset( $profileSync['email'] );
						Member::loggedIn()->profilesync = $profileSync;
						Member::loggedIn()->save();
					}
							
					/* Change the email */
					$oldEmail = Member::loggedIn()->email;
					Member::loggedIn()->email = $_SESSION['newEmail'];
					Member::loggedIn()->save();
					foreach ( Login::methods() as $method )
					{
						try
						{
							$method->changeEmail( Member::loggedIn(), $oldEmail, $_SESSION['newEmail'] );
						}
						catch( BadMethodCallException $e )
						{

						}
					}
					Member::loggedIn()->logHistory( 'core', 'email_change', array( 'old' => $oldEmail, 'new' => Member::loggedIn()->email, 'by' => 'manual' ) );
					
					/* Invalidate sessions except this one */
					Member::loggedIn()->invalidateSessionsAndLogins( Session::i()->id );
					if( isset( Request::i()->cookie['login_key'] ) )
					{
						Device::loadOrCreate( Member::loggedIn() )->updateAfterAuthentication( TRUE );
					}
								
					/* Send a validation email if we need to */
					if (  SettingsClass::i()->reg_auth_type == 'user' or  SettingsClass::i()->reg_auth_type == 'admin_user' )
					{
						unset( $_SESSION['newEmail'] );

						$vid = Login::generateRandomString();
						$plainSecurityKey = Login::generateRandomString();
						
						Db::i()->insert( 'core_validating', array(
							'vid'			=> $vid,
							'member_id'		=> Member::loggedIn()->member_id,
							'entry_date'	=> time(),
							'email_chg'		=> TRUE,
							'ip_address'	=> Request::i()->ipAddress(),
							'prev_email'	=> $oldEmail,
							'email_sent'	=> time(),
							'security_key'  => Encrypt::fromPlaintext( $plainSecurityKey )->tag()
						) );
		
						Member::loggedIn()->members_bitoptions['validating'] = TRUE;
						Member::loggedIn()->save();
						
						Email::buildFromTemplate( 'core', 'email_change', array( Member::loggedIn(), $vid, $plainSecurityKey ), Email::TYPE_TRANSACTIONAL )->send( Member::loggedIn() );
									
						Output::i()->redirect( Url::internal( '' ) );
					}
					
					/* Or just redirect */
					else
					{
						Event::fire( 'onEmailChange', Member::loggedIn(), array( $_SESSION['newEmail'], $oldEmail ) );
						unset( $_SESSION['newEmail'] );

						/* Send a confirmation email */
						Email::buildFromTemplate( 'core', 'email_address_changed', array( Member::loggedIn(), $oldEmail ), Email::TYPE_TRANSACTIONAL )->send( $oldEmail, array(), array(), NULL, NULL, array( 'Reply-To' =>  SettingsClass::i()->email_in ) );
		
						Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=email', 'front', 'settings' ), 'email_changed' );
					}
				}
			}
			catch ( LoginException|\Exception $e )
			{
				$error = $e->getMessage();
			}
			
			/* Otherwise show the reauthenticate form */
			return Theme::i()->getTemplate( 'system' )->settingsEmail( NULL, $login, $error );
			
		}
		return Theme::i()->getTemplate( 'system' )->settingsEmail( $form );
	}
	
	/**
	 * Password
	 *
	 * @return	string
	 */
	protected function _password() : string
	{
		if (  SettingsClass::i()->allow_password_changes == 'redirect' )
		{
			Output::i()->redirect( Url::external(  SettingsClass::i()->allow_password_changes_target ) );
		}

		if(  SettingsClass::i()->allow_password_changes != 'normal' )
		{
			Output::i()->error( 'no_module_permission', '2C122/T', 403, '' );
		}

		$canChangePassword = FALSE;

		foreach ( Login::methods() as $method )
		{
			if ( $method->canChangePassword( Member::loggedIn() ) )
			{
				$canChangePassword = TRUE;
				break;
			}
		}

		if( !$canChangePassword )
		{
			Output::i()->error( 'no_module_permission', '3C122/W', 403, '' );
		}
		
		if( Member::loggedIn()->isAdmin() )
		{
			return Theme::i()->getTemplate( 'system' )->settingsPassword();
		}
		
		$mfaOutput = MFAHandler::accessToArea( 'core', 'PasswordChange', Url::internal( 'app=core&module=system&controller=settings&area=password', 'front', 'settings_password' ) );
		if ( $mfaOutput )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=password', 'front', 'settings_password' ) );
			}
			Output::i()->output = $mfaOutput;
		}
				
		$form = new Form;
		$form->class = 'ipsForm_collapseTablet';
		if ( !Member::loggedIn()->members_bitoptions['password_reset_forced'] )
		{
			$form->add( new Password( 'current_password', '', TRUE, array( 'protect' => TRUE, 'validateFor' => Member::loggedIn(), 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "current-password" ) ) );
		}
		$form->add( new Password( 'new_password', '', TRUE, array( 'protect' => TRUE, 'showMeter' =>  SettingsClass::i()->password_strength_meter, 'checkStrength' => TRUE, 'strengthMember' => Member::loggedIn(), 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
		$form->add( new Password( 'confirm_new_password', '', TRUE, array( 'protect' => TRUE, 'confirm' => 'new_password', 'bypassProfanity' => Text::BYPASS_PROFANITY_ALL, 'htmlAutocomplete' => "new-password" ) ) );
		
		if ( !$mfaOutput and $values = $form->values() )
		{
			/* Change password */
			Member::loggedIn()->changePassword( $values['new_password'] );

			/* Invalidate sessions except this one */
			Member::loggedIn()->invalidateSessionsAndLogins( Session::i()->id );
			if( isset( Request::i()->cookie['login_key'] ) )
			{
				Device::loadOrCreate( Member::loggedIn() )->updateAfterAuthentication( TRUE );
			}
			
			/* Delete any pending validation emails */
			Db::i()->delete( 'core_validating', array( 'member_id=? AND lost_pass=1', Member::loggedIn()->member_id ) );

			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=password&success=1', 'front', 'settings' ) );
		}
		
		return Theme::i()->getTemplate( 'system' )->settingsPassword( $form );
	}
	
	
	/**
	 * Devices
	 *
	 * @return	string
	 */
	protected function _devices() : string
	{
		/* Can users manage devices? */
		if ( ! SettingsClass::i()->device_management )
		{
			Output::i()->error( 'no_module_permission', '2C122/S' );
		}

		$mfaOutput = MFAHandler::accessToArea( 'core', 'DeviceManagement', Url::internal( 'app=core&module=system&controller=settings&area=devices', 'front', 'settings_devices' ) );
		if ( $mfaOutput )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=devices', 'front', 'settings_devices' ) );
			}
			Output::i()->output = $mfaOutput;
			return Theme::i()->getTemplate( 'system' )->settingsDevices( array(), array() );
		}
		
		$devices = new ActiveRecordIterator( Db::i()->select( '*', 'core_members_known_devices', array( 'member_id=? AND last_seen>?', Member::loggedIn()->member_id, ( new \DateTime )->sub( new DateInterval( Device::LOGIN_KEY_VALIDITY ) )->getTimestamp() ), 'last_seen DESC' ), 'IPS\Member\Device' );

		$locations = array();
		$ipAddresses = array();
		foreach ( $devices as $device )
		{
			try
			{
				$log = Db::i()->select( '*', 'core_members_known_ip_addresses', array( 'member_id=? AND device_key=?', Member::loggedIn()->member_id, $device->device_key ), 'last_seen DESC' )->first();
			}
			catch ( UnderflowException $e )
			{
				continue;
			}
			
			if (  SettingsClass::i()->ipsgeoip )
			{
				if ( !array_key_exists( $log['ip_address'], $locations ) )
				{
					try
					{
						$locations[ $log['ip_address'] ] = GeoLocation::getByIp( $log['ip_address'] );
					}
					catch ( Exception $e )
					{
						$locations[ $log['ip_address'] ] = Member::loggedIn()->language()->addToStack('unknown');
					}
				}
				
				$ipAddresses[ $log['device_key'] ][ $log['ip_address'] ] = array(
					'location'	=> $locations[ $log['ip_address'] ],
					'date'		=> $log['last_seen']
				);
			}
			else
			{
				$ipAddresses[ $log['device_key'] ][ $log['ip_address'] ] = array(
					'date'		=> $log['last_seen']
				);
			}
		}
		
		$oauthClients = OAuthClient::roots();
		$apps = array();
		foreach ( Db::i()->select( '*', 'core_oauth_server_access_tokens', array( 'member_id=?', Member::loggedIn()->member_id ), 'issued DESC' ) as $accessToken )
		{
			if ( $accessToken['device_key'] and isset( $oauthClients[ $accessToken['client_id'] ] ) )
			{				
				$apps[ $accessToken['device_key'] ][ $accessToken['client_id'] ] = array(
					'date'	=> $accessToken['issued'],
				);
			}
		}
		
		return Theme::i()->getTemplate( 'system' )->settingsDevices( $devices, $ipAddresses, $apps, $oauthClients );
	}
	
	/**
	 * Secure Account
	 *
	 * @return	void
	 */
	protected function secureAccount() : void
	{
		/* Only logged in members */
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C122/Q', 403, '' );
		}

		$canChangePassword = FALSE;
		foreach ( Login::methods() as $method )
		{
			if ( $method->canChangePassword( Member::loggedIn() ) )
			{
				$canChangePassword = TRUE;
			}
		}
		
		$canConfigureMfa = FALSE;
		$hasConfiguredMfa = FALSE;
		foreach ( MFAHandler::handlers() as $handler )
		{
			if ( $handler->isEnabled() and $handler->memberCanUseHandler( Member::loggedIn() ) )
			{
				$canConfigureMfa = TRUE;
				
				if ( $handler->memberHasConfiguredHandler( Member::loggedIn() ) )
				{
					$hasConfiguredMfa = TRUE;
					break;
				}
			}
		}
				
		$loginMethods = array();
		foreach ( Login::methods() as $method )
		{
			if ( $method->showInUcp( Member::loggedIn() ) )
			{
				if ( $method->canProcess( Member::loggedIn() ) )
				{
					try
					{
						$name = $method->userProfileName( Member::loggedIn() );
						
						$loginMethods[ $method->id ] = array(
							'title'	=> $method->_title,
							'blurb'	=> $name ? Member::loggedIn()->language()->addToStack( 'profilesync_headline', FALSE, array( 'sprintf' => array( $name ) ) ) : Member::loggedIn()->language()->addToStack( 'profilesync_headline' ),
							'icon'	=> $method->userProfilePhoto( Member::loggedIn() )
						);
					}
					catch ( LoginException $e )
					{
						$loginMethods[ $method->id ] = array( 'title' => $method->_title, 'blurb' => Member::loggedIn()->language()->addToStack('profilesync_reauth_needed') );
					}
				}
			}
		}	
		
		$oauthApps = Db::i()->select( 'COUNT(DISTINCT client_id)', 'core_oauth_server_access_tokens', array( "member_id=? AND oauth_enabled=1 AND oauth_ucp=1 AND status='active'", Member::loggedIn()->member_id ) )
			->join( 'core_oauth_clients', 'oauth_client_id=client_id' )
			->first();
				
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'secure_account' );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=system&controller=settings', 'front', 'settings' ), Member::loggedIn()->language()->addToStack('settings') );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('secure_account') );
		Output::i()->output = Theme::i()->getTemplate( 'system' )->settingsSecureAccount( $canChangePassword, $canConfigureMfa, $hasConfiguredMfa, $loginMethods, $oauthApps );
	}
	
	/**
	 * Disable Automatic Login
	 *
	 * @return	string
	 */
	protected function disableAutomaticLogin() : string
	{
		Session::i()->csrfCheck();
		
		try
		{
			$device = Device::loadAndAuthenticate( Request::i()->device, Member::loggedIn() );
			$device->login_key = NULL;
			$device->save();
			
			Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'member_id=? AND device_key=?', $device->member_id, $device->device_key ) );
			
			Member::loggedIn()->logHistory( 'core', 'login', array( 'type' => 'logout', 'device' => $device->device_key ) );
			
			Store::i()->deleteByMember( $device->member_id, $device->user_agent, array( Session::i()->id ) );
		}
		catch ( Exception $e ) { }
						
		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=devices', 'front', 'settings_devices' ) );
	}
	
	/**
	 * MFA
	 *
	 * @return	string
	 */
	protected function _mfa() : string
	{
		Output::i()->bypassCsrfKeyCheck = true;

		/* Validate password */
		if ( !isset( $_SESSION['passwordForMfa'] ) )
		{
			$login = new Login( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ), Login::LOGIN_REAUTHENTICATE );
			$usernamePasswordMethods = $login->usernamePasswordMethods();
			$buttonMethods = $login->buttonMethods();

			/* Only prompt for re-authentication if it is possible */
			if( $usernamePasswordMethods OR $buttonMethods )
			{
				$_SESSION['mfaValidationRequired'] = TRUE;
				$error = NULL;
				try
				{
					if ( $success = $login->authenticate() )
					{
						$_SESSION['passwordForMfa'] = TRUE;
						Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ) );
					}
				}
				catch ( LoginException $e )
				{
					$error = $e->getMessage();
				}
				return Theme::i()->getTemplate( 'system' )->settingsMfaPassword( $login, $error );
			}
			else
			{
				/* If we don't have any methods available, we have only the standard login */
				$_SESSION['mfaValidationRequired'] = FALSE;
			}
		}

		/* Get our handlers and the output, even if it's just for a backdrop */
		$handlers = array();
		foreach ( MFAHandler::handlers() as $key => $handler )
		{
			if ( $handler->isEnabled() and $handler->memberCanUseHandler( Member::loggedIn() ) )
			{
				$handlers[ $key ] = $handler;
			}
		}
		$output = Theme::i()->getTemplate( 'system' )->settingsMfa( $handlers );
		
		/* Do MFA check */
		$mfaOutput = MFAHandler::accessToArea( 'core', 'SecurityQuestions', Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ) );
		if ( $mfaOutput )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ) );
			}
			return $output . $mfaOutput;
		}

		/* We got past the prompts */
		$_SESSION['passwordValidatedForMfa'] = TRUE;
		
		/* Do any enabling/disabling */
		if ( isset( Request::i()->act ) )
		{
			Session::i()->csrfCheck();

			/* Get the handler */
			$key = Request::i()->type;
			if ( !isset( $handlers[ $key ] ) or MFAHandler::accessToArea( 'core', 'SecurityQuestions', Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ) ) )
			{
				Output::i()->error( 'node_error', '2C122/M', 404, '' );
			}
			
			/* Do it */
			if ( Request::i()->act === 'enable' )
			{
				/* Include the CSS we'll need */
				Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
								
				/* Did we just submit it? */
				if ( isset( Request::i()->mfa_setup ) and $handlers[ $key ]->configurationScreenSubmit( Member::loggedIn() ) )
				{
					$_SESSION['MFAAuthenticated'] = time();
					
					Member::loggedIn()->members_bitoptions['security_questions_opt_out'] = FALSE;
					Member::loggedIn()->save();

					/* Invalidate other sessions */
					Member::loggedIn()->invalidateSessionsAndLogins( Session::i()->id );

					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ) );
				}

				/* Show the configuration modal */
				Output::i()->title = Member::loggedIn()->language()->addToStack('settings');
				return $output . Theme::i()->getTemplate( 'system' )->settingsMfaSetup( $handlers[ $key ]->configurationScreen( Member::loggedIn(), FALSE, Url::internal( 'app=core&module=system&controller=settings&area=mfa&act=enable&type=' . $key, 'front', 'settings_mfa' ) ), Url::internal( 'app=core&module=system&controller=settings&area=mfa&act=enable&type=' . $key, 'front', 'settings_mfa' ) );
			}
			elseif ( Request::i()->act === 'disable' )
			{
				/* Disable it */
				$handlers[ $key ]->disableHandlerForMember( Member::loggedIn() );
				Member::loggedIn()->save();
		
				/* If we have now disabled everything, save that we have opted out */
				if (  SettingsClass::i()->mfa_required_groups != '*' and !Member::loggedIn()->inGroup( explode( ',',  SettingsClass::i()->mfa_required_groups ) ) )
				{
					$enabledHandlers = FALSE;
					foreach ( $handlers as $handler )
					{
						if ( $handler->memberHasConfiguredHandler( Member::loggedIn() ) )
						{
							$enabledHandlers = TRUE;
							break;
						}
					}
					if ( !$enabledHandlers )
					{
						Member::loggedIn()->members_bitoptions['security_questions_opt_out'] = TRUE;
						Member::loggedIn()->save();
					}
				}
				
				/* Redirect */
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front', 'settings_mfa' ) );
			}
		}


		$output.= Theme::i()->getTemplate( 'system' )->settingsPrivacy();
		
		/* If we're still here, just show the screen */
		return $output;
	}

	/**
	 * Request personal identifiable information
	 *
	 * @return void
	 */
	protected function requestPiiData() : void
	{
		if( ( $_SESSION['mfaValidationRequired'] AND !isset( $_SESSION['passwordValidatedForMfa'] ) ) OR !PrivacyAction::canRequestPiiData() OR SettingsClass::i()->pii_type !== 'on' )
		{
			Output::i()->error( 'node_error', '1C122/10', 403, '' );
		}
		PrivacyAction::requestPiiData();
		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front')->setFragment('piiDataRequest'), 'pii_requested' );
	}

	/**
	 * Download personal identifiable information
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function downloadPiiData() : void
	{
		Session::i()->csrfCheck();
		
		if( ( $_SESSION['mfaValidationRequired'] AND !isset( $_SESSION['passwordValidatedForMfa'] ) ) OR !PrivacyAction::canDownloadPiiData() OR SettingsClass::i()->pii_type !== 'on' )
		{
			Output::i()->error( 'node_error', '1C122/11', 403, '' );
		}

		$xml = Member::loggedIn()->getPiiData();

		Db::i()->delete( 'core_member_privacy_actions', array( 'member_id=? AND action=?', Member::loggedIn()->member_id, PrivacyAction::TYPE_REQUEST_PII ) );
		Db::i()->delete( 'core_notifications', array( 'member=? AND notification_key=?', Member::loggedIn()->member_id, 'pii_data' ) );
		Member::loggedIn()->logHistory( 'core', 'privacy', array( 'type' => 'pii_download' ) );
		Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', [ 'Content-Disposition' => Output::getContentDisposition( 'attachment', Member::loggedIn()->name.'_personal_information.xml' ) ], FALSE, FALSE, FALSE );
	}

	/**
	 * Request account deletion
	 *
	 * @return void
	 */
	protected function requestAccountDeletion() : void
	{
		Session::i()->csrfCheck();

		if( ( $_SESSION['mfaValidationRequired'] AND !isset( $_SESSION['passwordValidatedForMfa'] ) ) OR !Member::loggedIn()->canUseAccountDeletion() OR SettingsClass::i()->right_to_be_forgotten_type !== 'on' )
		{
			Output::i()->error( 'node_error', '2C122/13', 403, '' );
		}
		
		if( !PrivacyAction::canDeleteAccount() )
		{
			Output::i()->error( 'node_error', '1C122/12', 403, '' );
		}

		PrivacyAction::requestAccountDeletion();

		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front')->setFragment('requestAccountDeletion'), 'account_deletion_requested' );
	}

	/**
	 * Cancel account deletion
	 *
	 * @return void
	 */
	protected function cancelAccountDeletion() : void
	{
		Session::i()->csrfCheck();
		try
		{
			$where = [];
			$where[] = ['member_id=?', Member::loggedIn()->member_id];
			$where[] = [ Db::i()->in( 'action',[PrivacyAction::TYPE_REQUEST_DELETE, PrivacyAction::TYPE_REQUEST_DELETE_VALIDATION ] ) ];
			$row = Db::i()->select( '*', PrivacyAction::$databaseTable, $where  )->first();
			PrivacyAction::constructFromData( $row )->delete();
			Member::loggedIn()->logHistory( 'core', 'privacy', [ 'type' => 'account_deletion_cancelled' ] );
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=mfa', 'front'), 'account_deletion_cancelled' );
		}
		catch( UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C122/Y', 404, '' );
		}
	}

	/**
	 * Confirm account deletion
	 *
	 * @return void
	 */
	protected function confirmAccountDeletion() : void
	{
		$key = Request::i()->vid;
		try
		{
			$request = PrivacyAction::getDeletionRequestByMemberAndKey( Member::loggedIn(), $key );
		
			$request->confirmAccountDeletion();
			Output::i()->redirect( $this->url->setQueryString( 'area','mfa'), 'account_deletion_confirmed' );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C122/Z', 404, '' );
		}

	}
	
	/**
	 * Initial MFA Setup
	 *
	 * @return	void
	 */
	protected function initialMfa() : void
	{
		$handlers = array();
		foreach ( MFAHandler::handlers() as $key => $handler )
		{
			if ( $handler->isEnabled() and $handler->memberCanUseHandler( Member::loggedIn() ) )
			{
				$handlers[ $key ] = $handler;
			}
		}
		
		if ( isset( Request::i()->mfa_setup ) )
		{
			Session::i()->csrfCheck();
			
			foreach ( $handlers as $key => $handler )
			{
				if ( ( count( $handlers ) == 1 ) or $key == Request::i()->mfa_method )
				{
					if ( $handler->configurationScreenSubmit( Member::loggedIn() ) )
					{							
						$_SESSION['MFAAuthenticated'] = time();
						$this->_performRedirect( Url::internal('') );
					}
				}
			}
		}
		
		foreach ( $handlers as $key => $handler )
		{
			if ( $handler->memberHasConfiguredHandler( Member::loggedIn() ) )
			{
				$this->_performRedirect( Url::internal('') );
			}
		}

		if ( isset( Request::i()->_mfa ) and Request::i()->_mfa == 'optout' )
		{
			Session::i()->csrfCheck();
			
			Member::loggedIn()->members_bitoptions['security_questions_opt_out'] = TRUE;
			Member::loggedIn()->save();
			Member::loggedIn()->logHistory( 'core', 'mfa', array( 'handler' => 'questions', 'enable' => FALSE, 'optout' => TRUE ) );
			$this->_performRedirect( Url::internal('') );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('reg_complete_2fa_title');
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
		Output::i()->output = Theme::i()->getTemplate( 'login', 'core', 'global' )->mfaSetup( $handlers, Member::loggedIn(), Url::internal( 'app=core&module=system&controller=settings&do=initialMfa', 'front', 'settings' )->addRef( $this->_performRedirect( Url::internal(''), '', TRUE ) ) );
	}
		
	/**
	 * Security Questions
	 *
	 * @return	string
	 */
	protected function _securityquestions() : string
	{
		$handler = new \IPS\MFA\SecurityQuestions\Handler();
		
		if ( !$handler->isEnabled() )
		{
			Output::i()->error( 'requested_route_404', '2C122/J', 404, '' );
		}
				
		$url = Url::internal( 'app=core&module=system&controller=settings&area=securityquestions', 'front', 'settings_securityquestions' );
		if ( isset( Request::i()->initial ) )
		{
			if ( isset( Request::i()->ref ) )
			{
				$url = $url->setQueryString( 'ref', Request::i()->ref );
			}
						
			if ( !$handler->memberCanUseHandler( Member::loggedIn() ) or $handler->memberHasConfiguredHandler( Member::loggedIn() ) )
			{
				$this->_performRedirect( Url::internal('') );
			}
			
			$url = $url->setQueryString( 'initial', 1 );
		}
		elseif ( $handler->memberHasConfiguredHandler( Member::loggedIn() ) )
		{
			if ( isset( Request::i()->_securityQuestionSetup ) )
			{
				return Theme::i()->getTemplate( 'system', 'core' )->securityQuestionsFinished();
			}
			elseif ( $output = MFAHandler::accessToArea( 'core', 'SecurityQuestions', $url ) )
			{
				return $output;
			}
		}
		
		$output = $handler->configurationScreen( Member::loggedIn(), !isset( Request::i()->initial ), $url  );
		
		if ( isset( Request::i()->initial ) )
		{
			Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			Output::i()->sidebar['enabled'] = FALSE;
			Output::i()->output = $output;
		}
		else
		{
			return $output;
		}

		return '';
	}
	
	/**
	 * MFA Email Recovery
	 *
	 * @return	void
	 */
	protected function mfarecovery() : void
	{
		/* Who are we */
		if ( isset( $_SESSION['processing2FA'] ) )
		{
			$member = Member::load( $_SESSION['processing2FA']['memberId'] );
		}
		else
		{
			$member = Member::loggedIn();
		}
				
		/* Can we use this? */
		if ( !$member->member_id or !( ( $member->failed_mfa_attempts >=  SettingsClass::i()->security_questions_tries and  SettingsClass::i()->mfa_lockout_behaviour == 'email' ) or in_array( 'email', explode( ',',  SettingsClass::i()->mfa_forgot_behaviour ) ) ) )
		{
			Output::i()->error( 'no_module_permission', '2C122/L', 403, '' );
		}
				
		/* If we have an existing validation record, we can just reuse it */
		$sendEmail = TRUE;
		try
		{
			$existing = Db::i()->select( array( 'vid', 'email_sent' ), 'core_validating', array( 'member_id=? AND forgot_security=1', $member->member_id ) )->first();
			$vid = $existing['vid'];
			
			/* If we sent an email within the last 15 minutes, don't send another one otherwise someone could be a nuisence */
			if ( $existing['email_sent'] and $existing['email_sent'] > ( time() - 900 ) )
			{
				$sendEmail = FALSE;
			}
			else
			{
				$plainSecurityKey = Login::generateRandomString();
				Db::i()->update( 'core_validating', [ 'email_sent' => time(), 'security_key' => Encrypt::fromPlaintext( $plainSecurityKey )->tag() ], [ 'vid=?', $vid ] );
			}
		}
		catch ( UnderflowException $e )
		{
			$vid = md5( $member->members_pass_hash . Login::generateRandomString() );
			$plainSecurityKey = Login::generateRandomString();

			Db::i()->insert( 'core_validating', [
				'vid'         		=> $vid,
				'member_id'   		=> $member->member_id,
				'entry_date'  		=> time(),
				'forgot_security'   => 1,
				'ip_address'  		=> Request::i()->ipAddress(),
				'email_sent'  		=> time(),
				'security_key'      => Encrypt::fromPlaintext( $plainSecurityKey )->tag()
			] );
		}
					
		/* Send email */
		if ( $sendEmail )
		{
			Email::buildFromTemplate( 'core', 'mfaRecovery', array( $member, $vid, $plainSecurityKey ), Email::TYPE_TRANSACTIONAL )->send( $member );
			$message = "mfa_recovery_email_sent";
		}
		else
		{
			$message = "mfa_recovery_email_already_sent";
		}
		
		/* Show confirmation page with further instructions */
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->title = Member::loggedIn()->language()->addToStack('mfa_account_recovery');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->mfaAccountRecovery( $message );
	}
	
	/**
	 * Validate MFA Email Recovery
	 *
	 * @return	void
	 */
	protected function mfarecoveryvalidate() : void
	{
		/* Validate */
		try
		{
			$record = Db::i()->select( '*', 'core_validating', array( 'vid=? AND member_id=? AND forgot_security=1', Request::i()->vid, Request::i()->mid ) )->first();
		}
		catch ( UnderflowException $e )
		{
			Output::i()->error( 'mfa_recovery_no_validation_key', '2C122/K', 410, '' );
		}

		/* Check security key is valid */
		if( !Login::compareHashes( Encrypt::fromTag( $record['security_key'] )->decrypt(), Request::i()->security_key ) )
		{
			Output::i()->error( 'mfavalidate_invalid_security_key', '3C122/16', 403, '' );
		}

		/* Remove all MFA */
		$member = Member::load( $record['member_id'] );
		foreach ( MFAHandler::handlers() as $key => $handler )
		{
			$handler->disableHandlerForMember( $member );
		}
		$member->failed_mfa_attempts = 0;
		$member->save();
		
		/* Delete validating record  */
		Db::i()->delete( 'core_validating', array( 'member_id=? AND forgot_security=1', $member->member_id ) );
		
		/* Log in if necessary */
		if ( !Member::loggedIn()->member_id and isset( $_SESSION['processing2FA'] ) )
		{
			( new Success( $member, Handler::load( $_SESSION['processing2FA']['handler'] ), $_SESSION['processing2FA']['remember'], $_SESSION['processing2FA']['anonymous'] ) )->process();
		}
		
		/* Redirect */
		Output::i()->redirect( Url::internal( '' ) );
	}
	
	/**
	 * Username
	 *
	 * @return	string
	 */
	protected function _username() : string
	{
		/* Check they have permission to change their username */
		if( !Member::loggedIn()->group['g_dname_changes'] )
		{
			Output::i()->error( 'username_err_nochange', '1C122/4', 403, '' );
		}

		/* SSO Overrides */
		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() AND $url = $ext->displayNameChange() )
			{
				Output::i()->redirect( $url );
			}
		}
				
		if ( Member::loggedIn()->group['g_displayname_unit'] )
		{
			if ( Member::loggedIn()->group['gbw_displayname_unit_type'] )
			{
				if ( Member::loggedIn()->joined->diff( DateTime::create() )->days < Member::loggedIn()->group['g_displayname_unit'] )
				{
					Output::i()->error(
						Member::loggedIn()->language()->addToStack( 'username_err_days', FALSE, array( 'sprintf' => array(
						Member::loggedIn()->joined->add(
							new DateInterval( 'P' . Member::loggedIn()->group['g_displayname_unit'] . 'D' )
						)->localeDate()
						), 'pluralize' => array( Member::loggedIn()->group['g_displayname_unit'] ) ) ),
					'1C122/5', 403, '' );
				}
			}
			else
			{
				if ( Member::loggedIn()->member_posts < Member::loggedIn()->group['g_displayname_unit'] )
				{
					Output::i()->error(
						Member::loggedIn()->language()->addToStack( 'username_err_posts' , FALSE, array( 'sprintf' => array(
						( Member::loggedIn()->group['g_displayname_unit'] - Member::loggedIn()->member_posts )
						), 'pluralize' => array( Member::loggedIn()->group['g_displayname_unit'] ) ) ),
					'1C122/6', 403, '' );
				}
			}
		}
		
		/* How many changes */
		$nameCount = Db::i()->select( 'COUNT(*) as count, MIN(log_date) as min_date', 'core_member_history', array(
			'log_member=? AND log_app=? AND log_type=? AND log_date>?',
			Member::loggedIn()->member_id,
			'core',
			'display_name',
			DateTime::create()->sub( new DateInterval( 'P' . Member::loggedIn()->group['g_dname_date'] . 'D' ) )->getTimestamp()
		) )->first();

		if ( Member::loggedIn()->group['g_dname_changes'] != -1 and $nameCount['count'] >= Member::loggedIn()->group['g_dname_changes'] )
		{
			return Theme::i()->getTemplate( 'system' )->settingsUsernameLimitReached( Member::loggedIn()->language()->addToStack('username_err_limit', FALSE, array( 'sprintf' => array( Member::loggedIn()->group['g_dname_date'] ), 'pluralize' => array( Member::loggedIn()->group['g_dname_changes'] ) ) ) );
		}
		else
		{
			/* Build form */
			$form = new Form;
			$form->class = 'ipsForm_collapseTablet';
			$form->add( new Text( 'new_username', '', TRUE, array( 'accountUsername' => Member::loggedIn(), 'htmlAutocomplete' => "username" ) ) );
						
			/* Handle submissions */
			if ( $values = $form->values() )
			{
				/* Disable syncing */
				$profileSync = Member::loggedIn()->profilesync;
				if ( isset( $profileSync['name'] ) )
				{
					unset( $profileSync['name'] );
					Member::loggedIn()->profilesync = $profileSync;
					Member::loggedIn()->save();
				}
				
				/* Save */
				$oldName = Member::loggedIn()->name;
				Member::loggedIn()->name = $values['new_username'];
				Member::loggedIn()->save();
				Member::loggedIn()->logHistory( 'core', 'display_name', array( 'old' => $oldName, 'new' => $values['new_username'], 'by' => 'manual' ) );
				
				/* Sync with login handlers */
				foreach ( Login::methods() as $method )
				{
					try
					{
						$method->changeUsername( Member::loggedIn(), $oldName, $values['new_username'] );
					}
					catch( BadMethodCallException $e ){}
				}
				
				/* Redirect */
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=username', 'front', 'settings_username' ), 'username_changed' );
			}
		}

		return Theme::i()->getTemplate( 'system' )->settingsUsername( $form, $nameCount['count'], Member::loggedIn()->group['g_dname_changes'], $nameCount['min_date'] ? DateTime::ts( $nameCount['min_date'] ) : Member::loggedIn()->joined, Member::loggedIn()->group['g_dname_date'] );
	}

	/**
	 * Link Preference
	 *
	 * @return    string
	 * @throws Exception
	 */
	protected function _links() : string
	{
		/* Build form */
		$form = new Form;
		$form->class = 'ipsForm_collapseTablet';
		$form->add( new Radio( 'link_pref', Member::loggedIn()->linkPref() ?:  SettingsClass::i()->link_default, FALSE, array( 'options' => array(
			'unread'	=> 'profile_settings_cvb_unread',
			'first'	=> 'profile_settings_cvb_first',
			'last'	=> 'profile_settings_cvb_last'
		) ) ) );

		if ( Member::loggedIn()->group['gbw_change_layouts'] )
		{
			if ( Member::loggedIn()->isEditingTheme() )
			{
				$form->addMessage( 'member_change_theme_layouts_editing_message' );
			}
			else
			{
				$form->add( new YesNo( 'member_change_theme_layouts', ( Member::loggedIn()->layouts and $memberOptions = json_decode( Member::loggedIn()->layouts, true ) and count( $memberOptions ) ), false, ['togglesOn' => ['forum_view', 'forum_list_view', 'forum_topic_view', 'downloads_categories_view', 'blog_view', 'courses_default_view', 'cm_store_view']] ) );

				if( Application::appIsEnabled( 'forums' ) )
				{
					$form->add( new Radio( 'forum_view', Member::loggedIn()->getLayoutValue( 'forums_forum' ), false, ['options' => [
						'table' => 'forums_default_view_table',
						'grid' => 'forums_default_view_grid',
						'fluid' => 'forums_default_view_fluid',
						'modern' => 'forums_default_view_modern'
					]], null, null, null, 'forum_view' ) );

					$form->add( new Radio( 'forum_list_view', Member::loggedIn()->getLayoutValue( 'forums_topic' ), false, ['options' => [
						'list' => 'forums_topic_list_list',
						'snippet' => 'forums_topic_list_snippet',
					]], null, null, null, 'forum_list_view' ) );

					$form->add( new Radio( 'forum_topic_view', Member::loggedIn()->getLayoutValue( 'forums_post' ), false, ['options' => [
						'classic' => 'forums_topic_view_classic',
						'modern' => 'forums_topic_view_modern',
					]], null, null, null, 'forum_topic_view' ) );
				}

				if( Application::appIsEnabled( 'nexus' ) )
				{
					$form->add( new Radio( 'cm_store_view', Member::loggedIn()->getLayoutValue( 'store_view' ), FALSE, array( 'options' => array(
						'grid'		=> 'cm_store_view_grid',
						'list'		=> 'cm_store_view_list'
					) ), null, null, null, 'cm_store_view' ) );
				}

				if( Application::appIsEnabled( 'downloads' ) )
				{
					$form->add( new Radio( 'downloads_categories_view', Member::loggedIn()->getLayoutValue( 'downloads_categories' ), false, [ 'options' => [
						'table' => 'downloads_default_view_table',
						'grid' => 'downloads_default_view_grid'
					]], null, null, null, 'downloads_categories_view' ) );
				}

				if( Application::appIsEnabled( 'blog' ) )
				{
					$form->add( new Radio( 'blog_view', Member::loggedIn()->getLayoutValue( 'blog_view' ), false, [ 'options' => [
						'table' => 'blog_view_mode_table',
						'grid' => 'blog_view_mode_grid'
					]], null, null, null, 'blog_view' ) );
				}

				if( Application::appIsEnabled( 'courses' ) )
				{
					$form->add( new Radio( 'courses_default_view', Member::loggedIn()->getLayoutValue( 'courses_view' ), false, [ 'options' => [
						'list' => 'course_view_list',
						'grid' => 'course_view_grid'
					]], null, null, null, 'courses_default_view' ) );
				}
			}
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			switch( $values['link_pref'] )
			{
				case 'last':
					Member::loggedIn()->members_bitoptions['link_pref_unread'] = FALSE;
					Member::loggedIn()->members_bitoptions['link_pref_last'] = TRUE;
					Member::loggedIn()->members_bitoptions['link_pref_first'] = FALSE;
					break;
				case 'unread':
					Member::loggedIn()->members_bitoptions['link_pref_unread'] = TRUE;
					Member::loggedIn()->members_bitoptions['link_pref_last'] = FALSE;
					Member::loggedIn()->members_bitoptions['link_pref_first'] = FALSE;
					break;
				default:
					Member::loggedIn()->members_bitoptions['link_pref_unread'] = FALSE;
					Member::loggedIn()->members_bitoptions['link_pref_last'] = FALSE;
					Member::loggedIn()->members_bitoptions['link_pref_first'] = TRUE;
					break;
			}

			/* Update the view preferences */
			if ( ! Member::loggedIn()->isEditingTheme() )
			{
				if ( ! empty( $values['member_change_theme_layouts'] ) )
				{
					if( Application::appIsEnabled( 'forums' ) )
					{
						Member::loggedIn()->setLayoutValue( 'forums_forum', $values['forum_view'] );
						Member::loggedIn()->setLayoutValue( 'forums_topic', $values['forum_list_view'] );
						Member::loggedIn()->setLayoutValue( 'forums_post', $values['forum_topic_view'] );
					}

					if( Application::appIsEnabled( 'downloads' ) )
					{
						Member::loggedIn()->setLayoutValue( 'downloads_categories', $values['downloads_categories_view'] );
					}

					if( Application::appIsEnabled( 'blog' ) )
					{
						Member::loggedIn()->setLayoutValue( 'blog_view', $values['blog_view'] );
					}

					if( Application::appIsEnabled( 'courses' ) )
					{
						Member::loggedIn()->setLayoutValue( 'courses_view', $values['courses_default_view' ] );
					}

					if( Application::appIsEnabled( 'nexus' ) )
					{
						Member::loggedIn()->setLayoutValue( 'store_view', $values['cm_store_view'] );
					}
				}
				else
				{
					Member::loggedIn()->setLayoutValue( null, null );
				}
			}

			Member::loggedIn()->save();
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=links', 'front', 'settings_links' ), 'saved' );
		}

		return Theme::i()->getTemplate( 'system' )->settingsLinks( $form );
	}

	/**
	 * Signature
	 *
	 * @return	string
	 */
	protected function _signature() : string
	{
		/* Check they have permission to change their signature */
		$sigLimits = explode( ":", Member::loggedIn()->group['g_signature_limits']);
		
		if( !Member::loggedIn()->canEditSignature() )
		{
			Output::i()->error( 'signatures_disabled', '2C122/C', 403, '' );
		}
		
		/* Check limits */
		if ( Member::loggedIn()->group['g_sig_unit'] )
		{
			/* Days */
			if ( Member::loggedIn()->group['gbw_sig_unit_type'] )
			{
				if ( Member::loggedIn()->joined->diff( DateTime::create() )->days < Member::loggedIn()->group['g_sig_unit'] )
				{
					Output::i()->error( Member::loggedIn()->language()->pluralize(
							sprintf(
									Member::loggedIn()->language()->get('sig_err_days'),
									Member::loggedIn()->joined->add(
											new DateInterval( 'P' . Member::loggedIn()->group['g_sig_unit'] . 'D' )
									)->localeDate()
							), array( Member::loggedIn()->group['g_sig_unit'] ) ),
							'1C122/D', 403, '' );
				}
			}
			/* Posts */
			else
			{
				if ( Member::loggedIn()->member_posts < Member::loggedIn()->group['g_sig_unit'] )
				{
					Output::i()->error( Member::loggedIn()->language()->pluralize(
							sprintf(
									Member::loggedIn()->language()->get('sig_err_posts'),
									( Member::loggedIn()->group['g_sig_unit'] - Member::loggedIn()->member_posts )
							), array( Member::loggedIn()->group['g_sig_unit'] ) ),
							'1C122/E', 403, '' );
				}
			}
		}
	
		/* Build form */
		$form = new Form;
		$form->class = 'ipsForm_collapseTablet';
		$form->add( new YesNo( 'view_sigs', Member::loggedIn()->members_bitoptions['view_sigs'], FALSE ) );
		$form->add( new Editor( 'signature', Member::loggedIn()->signature, FALSE, array( 'app' => 'core', 'key' => 'Signatures', 'autoSaveKey' => "frontsig-" . Member::loggedIn()->member_id, 'attachIds' => array( Member::loggedIn()->member_id ) ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( $values['signature'] )
			{
				/* Check Limits */
				$signature = new DOMDocument( '1.0', 'UTF-8' );
				$signature->loadHTML( DOMDocument::wrapHtml( $values['signature'] ) );
				
				$errors = array();
				
				/* Links */
				if ( is_numeric( $sigLimits[4] ) and ( $signature->getElementsByTagName('a')->length + $signature->getElementsByTagName('iframe')->length ) > $sigLimits[4] )
				{
					$errors[] = Member::loggedIn()->language()->addToStack('sig_num_links_exceeded');
				}

				/* Number of Images */
				if ( is_numeric( $sigLimits[1] ) )
				{
					$imageCount = 0;
					foreach ( $signature->getElementsByTagName('img') as $img )
					{
						if( !$img->hasAttribute("data-emoticon") )
						{
							$imageCount++;
						}
					}

					/* Look for background-image URLs too */
					$xpath = new DOMXpath( $signature );

					foreach ( $xpath->query("//*[contains(@style, 'url') and contains(@style, 'background')]") as $styleUrl )
					{
						$imageCount++;
					}

					if( $imageCount > $sigLimits[1] )
					{
						$errors[] = Member::loggedIn()->language()->addToStack('sig_num_images_exceeded');
					}
				}
				
				/* Size of images */
				if ( ( is_numeric( $sigLimits[2] ) and $sigLimits[2] ) or ( is_numeric( $sigLimits[3] ) and $sigLimits[3] ) )
				{
					foreach ( $signature->getElementsByTagName('img') as $image )
					{
						$attachId			= $image->getAttribute('data-fileid');
						$imageProperties	= NULL;

						if( $attachId )
						{
							try
							{
								$attachment = Db::i()->select( 'attach_location, attach_thumb_location', 'core_attachments', array( 'attach_id=?', $attachId ) )->first();
								$imageProperties = File::get( 'core_Attachment', $attachment['attach_thumb_location'] ?: $attachment['attach_location'] )->getImageDimensions();
								$src = (string) File::get( 'core_Attachment', $attachment['attach_location'] )->url;
							}
							catch( UnderflowException $e ){}
						}
						
						if( is_array( $imageProperties ) AND count( $imageProperties ) )
						{
							if( $imageProperties[0] > $sigLimits[2] OR $imageProperties[1] > $sigLimits[3] )
							{
								$errors[] = Member::loggedIn()->language()->addToStack( 'sig_imagetoobig', FALSE, array( 'sprintf' => array( $src, $sigLimits[2], $sigLimits[3] ) ) );
							}
						}
					}
				}
				
				/* Lines */
				$preBreaks = 0;
				
				/* Make sure we are not trying to bypass the limit by using <pre> tags, which will not have <p> or <br> tags in its content */
				foreach( $signature->getElementsByTagName('pre') AS $pre )
				{
					$content = nl2br( trim( $pre->nodeValue ) );
					$preBreaks += count( explode( "<br />", $content ) );
				}

				if ( ( is_numeric( $sigLimits[5] ) and ( $signature->getElementsByTagName('p')->length + $signature->getElementsByTagName('br')->length + $preBreaks ) > $sigLimits[5] ) or strlen( $values['signature'] ) > 20000 )
				{
					$errors[] = Member::loggedIn()->language()->addToStack('sig_num_lines_exceeded');
				}
			}
			
			if( !empty( $errors ) )
			{
				$form->error = Member::loggedIn()->language()->addToStack('sig_restrictions_exceeded');
				$form->elements['']['signature']->error = Member::loggedIn()->language()->formatList( $errors );
				
				return Theme::i()->getTemplate( 'system' )->settingsSignature( $form, $sigLimits );
			}
			
			Member::loggedIn()->signature = $values['signature'];
			Member::loggedIn()->members_bitoptions['view_sigs'] = $values['view_sigs'];
			
			Member::loggedIn()->save();
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=signature', 'front', 'settings_signature' ), 'signature_changed' );
		}

		return Theme::i()->getTemplate( 'system' )->settingsSignature( $form, $sigLimits );
	}
	
	/**
	 * Login Method
	 *
	 * @return	string
	 */
	protected function _login() : string
	{
		/* Load method */
		try
		{
			$method = Handler::load( Request::i()->service );
			if ( !$method->showInUcp( Member::loggedIn() ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'page_doesnt_exist', '2C122/B', 404, '' );
		}
		
		/* Are we connected? */
		$blurb				= 'profilesync_blurb';
		$canDisassociate	= FALSE;

		try
		{
			$connected = $method->canProcess( Member::loggedIn() );
			if ( $connected )
			{
				$photoUrl = $method->userProfilePhoto( Member::loggedIn() );
				$profileName = $method->userProfileName( Member::loggedIn() );

				/* Can we disassociate? */
				foreach ( Login::methods() as $_method )
				{
					if ( $_method->id != $method->id and $_method->canProcess( Member::loggedIn() ) )
					{
						$canDisassociate = TRUE;
						break;
					}
				}
			}
		}
		catch ( LoginException $e )
		{
			$connected = FALSE;
			$blurb = 'profilesync_expire_blurb';

			/* If we previously associated an account but that link has expired, we should still allow you to disassociate */
			if( $method->canProcess( Member::loggedIn() ) )
			{
				$canDisassociate = TRUE;
			}
		}

		if ( $canDisassociate and isset( Request::i()->disassociate ) )
		{				
			Session::i()->csrfCheck();
			$method->disassociate();
			
			if ( $method->showInUcp( Member::loggedIn() ) )
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id, 'front', 'settings_login' ) );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings', 'front', 'settings' ) );
			}
		}

		/* Are we connected? */
		if ( $connected )
		{			
			/* Are we forcing syncing of anything? */
			$syncOptions = $method->syncOptions( Member::loggedIn() );
			$forceSync = array();
			foreach ( $method->forceSync() as $type )
			{
				$forceSync[ $type ] = array(
					'label'	=> Member::loggedIn()->language()->addToStack( "profilesync_{$type}_force", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $method->_title ) ) ) ),
					'error'	=> isset( Member::loggedIn()->profilesync[ $type ]['error'] ) ? Member::loggedIn()->profilesync[ $type ]['error'] : NULL
				);
			}
			
			/* Show sync options */
			$form = NULL;
			if ( $syncOptions )
			{
				$form = new Form( 'sync', 'profilesync_save' );
				$form->class = 'ipsForm--vertical ipsForm--profile-sync';
				foreach ( $syncOptions as $option )
				{
					if ( !Handler::handlerHasForceSync( $option, NULL, Member::loggedIn() ) )
					{
						if ( $option == 'photo' and !Member::loggedIn()->group['g_edit_profile'] )
						{
							continue;
						}
						if ( $option == 'cover' and ( !Member::loggedIn()->group['g_edit_profile'] or !Member::loggedIn()->group['gbw_allow_upload_bgimage'] ) )
						{
							continue;
						}

						if ( $option == 'status' )
						{
							$checked = ( isset( Member::loggedIn()->profilesync[ $option ] ) and array_key_exists( $method->id, Member::loggedIn()->profilesync[ $option ]) );
						}
						else
						{
							$checked = ( isset( Member::loggedIn()->profilesync[ $option ] ) and  Member::loggedIn()->profilesync[ $option ]['handler'] == $method->id );
						}
						$field = new Checkbox( "profilesync_{$option}", $checked, FALSE, array( 'labelSprintf' => array( Member::loggedIn()->language()->addToStack( $method->_title ) ) ), NULL, NULL, NULL, "profilesync_{$option}_{$method->id}" );
						if ( $checked and ( ( $option == 'status' and $error = Member::loggedIn()->profilesync[ $option ][ $method->id ]['error'] ) or ( $option != 'status' and $error = Member::loggedIn()->profilesync[ $option ]['error'] ) ) )
						{
							$field->description = Theme::i()->getTemplate( 'system' )->settingsLoginMethodSynError( $error );
						}		
						$form->add( $field );
					}
				}
				if ( $values = $form->values() )
				{
					$profileSync = Member::loggedIn()->profilesync;
					$changes = array();
					
					foreach ( $values as $k => $v )
					{
						$option = mb_substr( $k, 12 );
						if ( $option === 'status' )
						{
							if ( isset( Member::loggedIn()->profilesync[ $option ][ $method->id ] ) )
							{
								if ( !$v )
								{
									unset( $profileSync[ $option ][ $method->id ] );
									$changes[ $option ] = FALSE;
								}
							}
							else
							{
								if ( $v )
								{
									$profileSync[ $option ][ $method->id ] = array( 'lastsynced' => NULL, 'error' => NULL );
									$changes[ $option ] = TRUE;
								}
							}
						}
						else
						{
							if ( isset( Member::loggedIn()->profilesync[ $option ] ) and  Member::loggedIn()->profilesync[ $option ]['handler'] == $method->id )
							{
								if ( !$v )
								{
									unset( $profileSync[ $option ] );
									$changes[ $option ] = FALSE;
								}
							}
							else
							{
								if ( $v )
								{
									$profileSync[ $option ] = array( 'handler' => $method->id, 'ref' => NULL, 'error' => NULL );
									$changes[ $option ] = TRUE;
								}
							}
						}
					}
					
					if ( count( $changes ) )
					{
						Member::loggedIn()->logHistory( 'core', 'social_account', array( 'changed' => $changes, 'handler' => $method->id, 'service' => $method::getTitle() ) );
					}
					
					Member::loggedIn()->profilesync = $profileSync;
					Member::loggedIn()->save();
					Member::loggedIn()->profileSync();
					
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id, 'front', 'settings_login' ) );
				}
			}
			
			$extraPermissions = NULL;
			$login = NULL;
			if ( isset( Request::i()->scopes ) )
			{
				$method = Handler::findMethod('IPS\Login\Handler\Oauth2\Facebook');
				$extraPermissions = Request::i()->scopes;
				$login = new Login( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id . '&scopes=' . Request::i()->scopes, 'front', 'settings_login' ), Login::LOGIN_UCP );
				$login->reauthenticateAs = Member::loggedIn();

				try
				{
					if ( $success = $login->authenticate( $method ) )
					{				
						if ( $success->member->member_id === Member::loggedIn()->member_id )
						{
							$method->completeLink( Member::loggedIn(), NULL );
							Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id, 'front', 'settings_login' ) );
						}
					}
				}
				catch( Exception $ex ) { }
			}
					
			/* Display */
			return Theme::i()->getTemplate( 'system' )->settingsLoginMethodOn( $method, $form, $canDisassociate, $photoUrl, $profileName, $extraPermissions, $login, $forceSync );
		}
		
		/* No - show option to connect */
		else
		{			
			$login = new Login( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id, 'front', 'settings_login' ), Login::LOGIN_UCP );
			$login->reauthenticateAs = Member::loggedIn();
			$error = NULL;
			try
			{
				if ( $success = $login->authenticate( $method ) )
				{					
					if ( $success->member->member_id === Member::loggedIn()->member_id )
					{
						$method->completeLink( Member::loggedIn(), NULL );
						Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id, 'front', 'settings_login' ) );
					}
					else
					{
						$error = Member::loggedIn()->language()->addToStack( 'profilesync_already_associated', FALSE, array( 'sprintf' => array( $method->_title ) ) );
					}
				}
			}
			catch ( LoginException $e )
			{
				if ( $e->getCode() === LoginException::MERGE_SOCIAL_ACCOUNT )
				{
					if ( $e->member->member_id === Member::loggedIn()->member_id )
					{
						$method->completeLink( Member::loggedIn(), NULL );
						Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=login&service=' . $method->id, 'front', 'settings_login' ) );
					}
					else
					{
						$error = Member::loggedIn()->language()->addToStack( 'profilesync_email_exists', FALSE, array( 'sprintf' => array( $method->_title ) ) );
					}
				}
				elseif( $e->getCode() === LoginException::LOCAL_ACCOUNT_ALREADY_MERGED )
				{
					$error = Member::loggedIn()->language()->addToStack( 'profilesync_already_merged', FALSE, array( 'sprintf' => array( $method->_title, $method->_title, $method->_title ) ) );
				}
				else
				{
					$error = $e->getMessage();
				}
			}
			
			return Theme::i()->getTemplate( 'system' )->settingsLoginMethodOff( $method, $login, $error, $blurb, $canDisassociate );
		}
	}
	
	/**
	 * Apps
	 *
	 * @return	string
	 */
	protected function _apps() : string
	{
		$apps = array();
		
		foreach ( Db::i()->select( '*', 'core_oauth_server_access_tokens', array( 'member_id=?', Member::loggedIn()->member_id ), 'issued DESC' ) as $accessToken )
		{
			if ( $accessToken['status'] == 'revoked' )
			{
				continue;
			}
			try
			{
				$client = OAuthClient::load( $accessToken['client_id'] );
				if ( !$client->enabled )
				{
					throw new OutOfRangeException;
				}
				if ( !$client->ucp )
				{
					continue;
				}
				
				if ( !isset( $apps[ $client->client_id ] ) )
				{
					$apps[ $client->client_id ] = array(
						'issued'	=> $accessToken['issued'],
						'client'	=> $client,
						'scopes'	=> array()
					);
				}
				else
				{
					if ( $accessToken['issued'] < $apps[ $client->client_id ]['issued'] )
					{
						$apps[ $client->client_id ]['issued'] = $accessToken['issued'];
					}
				}
				
				$scopes = array();
				if ( $accessToken['scope'] and $authorizedScopes = json_decode( $accessToken['scope'] ) )
				{
					$availableScopes = json_decode( $client->scopes, TRUE );
					foreach ( $authorizedScopes as $scope )
					{
						if ( isset( $availableScopes[ $scope ] ) and !isset( $apps[ $client->client_id ]['scopes'][ $scope ] ) )
						{
							$apps[ $client->client_id ]['scopes'][ $scope ] = $availableScopes[ $scope ]['description'];
						}
					}
				}
			}
			catch ( OutOfRangeException $e ) { }
		}
				
		return Theme::i()->getTemplate( 'system' )->settingsApps( $apps );
	}
		
	/**
	 * Change App Permissions
	 *
	 * @return	void
	 */
	protected function revokeApp() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$client = OAuthClient::load( Request::i()->client_id );
			Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'client_id=? AND member_id=?', $client->client_id, Member::loggedIn()->member_id ) );
			Member::loggedIn()->logHistory( 'core', 'oauth', array( 'type' => 'revoked_access_token', 'client' => $client->client_id ) );
		}
		catch ( Exception $e ) { }
				
		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=settings&area=apps', 'front', 'settings_apps' ) );
	}
	
	/**
	 * Disable All Signatures
	 *
	 * @return	void
	 */
	protected function toggleSigs() : void
	{
		if ( ! SettingsClass::i()->signatures_enabled )
		{
			Output::i()->error( 'signatures_disabled', '2C122/F', 403, '' );
		}
			
		Session::i()->csrfCheck();
			
		if ( Member::loggedIn()->members_bitoptions['view_sigs'] )
		{
			Member::loggedIn()->members_bitoptions['view_sigs'] = 0;
		}
		else
		{
			Member::loggedIn()->members_bitoptions['view_sigs'] = 1;
		}
		
		Member::loggedIn()->save();
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		
		$redirectUrl = Request::i()->referrer() ?: Url::internal( "app=core&module=system&controller=settings", 'front', 'settings' );
		Output::i()->redirect( $redirectUrl, 'signature_pref_toggled' );
	}
	
	/**
	 * Dismiss Profile Completion
	 *
	 * @return	void
	 */
	protected function dismissProfile() : void
	{
		Session::i()->csrfCheck();
		
		Member::loggedIn()->members_bitoptions['profile_completion_dismissed'] = TRUE;
		Member::loggedIn()->save();
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			$redirectUrl = Request::i()->referrer() ?: Url::internal( "app=core&module=system&controller=settings", 'front', 'settings' );
			Output::i()->redirect( $redirectUrl );
		}
	}
	
	/**
	 * Completion Wizard
	 *
	 * @return	void
	 */
	protected function completion() : void
	{
		$steps = array();
		$url = Url::internal( 'app=core&module=system&controller=settings&do=completion', 'front', 'settings' )->setQueryString( 'ref', Request::i()->ref );

		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $extension )
		{
			if ( is_array( $extension::wizard() ) AND count( $extension::wizard() ) )
			{
				$steps = array_merge( $steps, $extension::wizard() );
			}
			if ( method_exists( $extension, 'extraStep') AND count( $extension::extraStep() ) )
			{
				$steps = array_merge( $steps, $extension::extraStep() );
			}
		}

		$steps = ProfileStep::setOrder( $steps );

		$steps = array_merge( $steps, array( 'profile_done' => function( $data ) use ( $url ) {

			unset( $_SESSION[ 'wizard-' . md5( $url ) . '-step' ] );
			unset( $_SESSION[ 'wizard-' . md5( $url ) . '-data' ] );

			if( isset( $_SESSION['profileCompletionData'] ) )
			{
				unset( $_SESSION['profileCompletionData'] );
			}

			$this->_performRedirect( Url::internal( "app=core&module=system&controller=settings", 'front', 'settings' ), 'saved' );
		} ) );

		$wizard = new Wizard( $steps, $url, FALSE, NULL, TRUE );
		$wizard->template = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'completeWizardTemplate' );

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( '2fa.css', 'core', 'global' ) );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/settings.css' ) );
		
		Output::i()->bodyClasses[]			= 'ipsLayout_minimal';
		Output::i()->sidebar['enabled']	= FALSE;
		Output::i()->title					= Member::loggedIn()->language()->addToStack( 'complete_your_profile' );
		Output::i()->output 				= (string) $wizard;
	}

	/**
	 * Subscribe to newsletter
	 *
	 * @return	void
	 */
	protected function newsletterSubscribe() : void
	{
		Session::i()->csrfCheck();


		Member::loggedIn()->allow_admin_mails = TRUE;
		Member::loggedIn()->save();

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}

		$this->_performRedirect( Url::internal( "app=core&module=system&controller=settings", 'front', 'settings' ), 'block_newsletter_subscribed' );
	}

	/**
	 * Toggle Anonymously Online
	 *
	 * @return	void
	 */
	protected function updateAnon() : void
	{
		Session::i()->csrfCheck();

		/* Check validation */
		if( $_SESSION['mfaValidationRequired'] AND !isset( $_SESSION['passwordValidatedForMfa'] ) )
		{
			Output::i()->error( 'node_error', '1C122/14', 403, '' );
		}

		/* Check this value can be toggled */
		if( Member::loggedIn()->group['g_hide_online_list'] >= 1 )
		{
			Output::i()->error( 'online_status_cannot_change', '2C122/X', 403, '' );
		}

		if ( $output = MFAHandler::accessToArea( 'core', 'DeviceManagement', $this->url->setQuerystring('do','updateDeviceEmail')->csrf()) )
		{
			Output::i()->output = $output;
			return;
		}

		/* Update the bitwise flag */
		Member::loggedIn()->members_bitoptions['is_anon'] = (bool) Request::i()->value;
		Member::loggedIn()->save();

		/* Update users devices */
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_members_known_devices', array( "member_id=?", Member::loggedIn()->member_id ) ), 'IPS\Member\Device' ) AS $device )
		{
			$device->anonymous = ( Request::i()->value ) ? 1 : 0;
			$device->save();
		}

		/* Update the session */
		Session::i()->setType( ( Request::i()->value ) ? Front::LOGIN_TYPE_ANONYMOUS : Front::LOGIN_TYPE_MEMBER );

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}

		$this->_performRedirect( Url::internal( "app=core&module=system&controller=settings&area=mfa", 'front', 'settings_mfa' ), 'saved' );
	}

	/**
	 * Toggle Whether PII is included in the data layer
	 *
	 * @return	void
	 */
	protected function togglePii() : void
	{
		if ( !  SettingsClass::i()->core_datalayer_member_pii_choice OR ( $_SESSION['mfaValidationRequired'] AND !isset( $_SESSION['passwordValidatedForMfa'] ) ) OR SettingsClass::i()->pii_type !== 'on' )
		{
			Output::i()->error( 'page_not_found', '3T251/7', 404 );
		}
		Session::i()->csrfCheck();

		/* Update the bitwise flag */
		Member::loggedIn()->members_bitoptions['datalayer_pii_optout'] = ! Member::loggedIn()->members_bitoptions['datalayer_pii_optout'];
		Member::loggedIn()->save();

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}

		$this->_performRedirect( Url::internal( "app=core&module=system&controller=settings&area=mfa", 'front', 'settings_mfa' ), 'saved' );
	}

	/**
	 * Invite
	 *
	 * @return	void
	 */
	protected function invite() : void
	{
		$url = Url::internal( "" );

		if(  SettingsClass::i()->ref_on and Member::loggedIn()->member_id )
		{
			$url = $url->setQueryString( array( '_rid' => Member::loggedIn()->member_id  ) );
		}

		$links = Service::getAllServices( $url,  SettingsClass::i()->board_name );
		Output::i()->title	= Member::loggedIn()->language()->addToStack( 'block_invite' );
		Output::i()->output = Theme::i()->getTemplate( 'system' )->invite( $links, $url );
	}

	/**
	 * Toggle New Device Email
	 *
	 * @return	void
	 */
	protected function updateDeviceEmail() : void
	{
		Session::i()->csrfCheck();

		if ( $output = MFAHandler::accessToArea( 'core', 'DeviceManagement', $this->url->setQuerystring('do','updateDeviceEmail')->csrf()) )
		{
			Output::i()->output = $output;
			return;
		}

		/* Update the bitwise flag */
		Member::loggedIn()->members_bitoptions['new_device_email'] = (bool) Request::i()->value;
		Member::loggedIn()->save();

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}

		$this->_performRedirect( Url::internal( "app=core&module=system&controller=settings&area=devices", 'front', 'settings_devices' ), 'saved' );
	}

	/**
	 * Redirect the user
	 * -consolidated to reduce duplicate code
	 *
	 * @param	Url	$fallbackUrl		URL to send user to if no referrer was passed
	 * @param	string			$message			(Optional) message to show during redirect
	 * @param	bool			$return				Return URL instead of redirecting
	 * @return    string|null
	 */
	protected function _performRedirect( Url $fallbackUrl, string $message='', bool $return=FALSE ) : ?string
	{
		/* Redirect */
		$ref = Request::i()->referrer();
		if ( $ref === NULL )
		{
			$ref = $fallbackUrl;
		}

		if( $return === TRUE )
		{
			return $ref;
		}

		Output::i()->redirect( $ref, $message );
	}
}