<?php
/**
 * @brief		Login Methods
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 May 2017
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Application;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\SSOAbstract;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Tel;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Wizard;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Login as LoginClass;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login Methods
 */
class login extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Login\Handler';
	
	/**
	 * Show the "add" button in the page root rather than the table root
	 */
	protected bool $_addButtonInRoot = FALSE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'login_access' );
		parent::execute();
	}
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['link'] = $buttons['add']['link']->setQueryString( '_new', 1 );
		}
		
		return $buttons;
	}
	
	/**
	 * Manage
	 *
	 * @return void
	 */
	protected function manage() : void
	{
		/* Work out tabs */
		$tabs = array();
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'login_manage' ) )
		{
			$tabs['handlers'] = 'login_handlers';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'login_settings' ) )
		{
			$tabs['settings'] = 'login_settings';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'registration_settings' ) )
		{
			$tabs['registration'] = 'registration_settings';
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'login_account_management' ) )
		{
			$tabs['accountsettings'] = 'account_management_settings';
		}
		
		/* Get active tab */
		if ( isset( Request::i()->tab ) and isset( $tabs[ Request::i()->tab ] ) )
		{
			$activeTab = Request::i()->tab;
		}
		else
		{
			$_tabs = array_keys( $tabs ) ;
			$activeTab = array_shift( $_tabs );
		}
		
		/* Get active tab contents */
		$output = '';
		switch ( $activeTab )
		{
			case 'handlers':
				Dispatcher::i()->checkAcpPermission( 'login_manage' );
				parent::manage();
				$output = Output::i()->output;
				break;
			case 'settings':
				$output = $this->_settings();
				break;
			case 'registration':
				$output = $this->_registration();
				break;
			case 'accountsettings':
				$output = $this->_accountsettings();
				break;
		}
		
		/* Output */
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_settings_login');
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $output;
		}
		else
		{
			Output::i()->sidebar['actions'] = array(
				'forcelogout'	=> array(
					'title'		=> 'force_all_logout',
					'icon'		=> 'lock',
					'link'		=> Url::internal( 'app=core&module=settings&controller=login&do=forceLogout' )->csrf(),
					'data'		=> array( 'confirm' => '' ),
				),
			);
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $output, Url::internal( "app=core&module=settings&controller=login" ) );
		}
	}
		
	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		if ( Request::i()->id )
		{
			parent::form();
		}
		else
		{
			Output::i()->output = (string) new Wizard( array(
				'login_handler'	=> function( $data )
				{
					$options = array();
					foreach ( Handler::handlerClasses() as $class )
					{
						/* @var Handler $class */
						if ( !$class::$allowMultiple )
						{
							foreach ( Handler::roots() as $handler )
							{
								if ( $handler instanceof $class )
								{
									continue 2;
								}
							}
						}
						$options[ $class ] = $class::getTitle();
					}
					
					$form = new Form( 'login_handler_1', 'continue' );
					$form->add( new Radio( 'login_handler', TRUE, NULL, array( 'options' => $options ), function( $val )
					{
						$val::testCompatibility();
					} ) );
					if ( $values = $form->values() )
					{
						return array( 'handler' => $values['login_handler'] );
					}
					return $form;
				},
				'login_details'	=> function( $data )
				{
					$node = new $data['handler'];
					$node->classname = $data['handler'];
					$form = $this->_addEditForm( $node );
					if ( $values = $form->values() )
					{
						try
						{
							$node->settings = array();
							$node->order = Db::i()->select( 'MAX(login_order)', $node::$databaseTable  )->first() + 1;
							$node->saveForm( $node->formatFormValues( $values ) );
											
							Session::i()->log( 'acplog__node_created', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
			
							Output::i()->redirect( Url::internal('app=core&module=settings&controller=login') );
						}
						catch ( LogicException $e )
						{
							$form->error = $e->getMessage();
						}
					}
					return $form;
				}
			), Url::internal('app=core&module=settings&controller=login&do=form') );
		}
	}
	
	/**
	 * Toggle Enabled/Disable
	 * Overridden so we can check the settings are okay before we enable
	 *
	 * @return	void
	 */
	protected function enableToggle() : void
	{
		Session::i()->csrfCheck();
		
		$loginMethod = Handler::load( Request::i()->id );
		
		if ( Request::i()->status )
		{
			try
			{
				$loginMethod->testSettings();
			}
			catch ( \Exception $e )
			{
				Output::i()->redirect( Url::internal( "app=core&module=settings&controller=login&do=form&id={$loginMethod->id}" ) );
			}
		}
		else
		{
			$this->_disableCheck( $loginMethod );
		}

		/* Clear caches */
		unset( Store::i()->loginMethods, Store::i()->essentialCookieNames );
		Cache::i()->clearAll();

		/* Toggle */
		parent::enableToggle();
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete() : void
	{
		/* Check it's okay */
		$loginMethod = Handler::load( Request::i()->id );
		$this->_disableCheck( $loginMethod );
		
		/* Do it */
		parent::delete();
		
		/* Clear caches */
		unset( Store::i()->loginMethods, Store::i()->essentialCookieNames );
		Cache::i()->clearAll();
	}
	
	/**
	 * If a particular method is disabled/deleted - will we still be able to log in?
	 *
	 * @param	Handler	$methodToRemove	Handler to be disabled/deleted
	 * @return	bool
	 */
	protected function _disableCheck( Handler $methodToRemove ) : bool
	{
		foreach ( LoginClass::methods() as $method )
		{
			if ( $method != $methodToRemove and $method->canProcess( Member::loggedIn() ) and $method->acp )
			{
				return true;
			}
		}
		Output::i()->error( 'login_handler_cannot_disable', '1C166/5', 403, '' );
	}
	
	/**
	 * Login Settings
	 *
	 * @return string
	 */
	protected function _settings() : string
	{
		Dispatcher::i()->checkAcpPermission( 'login_settings' );
		
		/* Build Form */
		$form = new Form();
		$form->addHeader( 'ipb_bruteforce_attempts_title' );
		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'ipb_bruteforce_attempts_block_desc', true, true ) );
		$form->add( new Number( 'ipb_bruteforce_attempts', Settings::i()->ipb_bruteforce_attempts, FALSE, array( 'min' => 0, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), Member::loggedIn()->language()->addToStack('failed_logins'), 'ipb_bruteforce_attempts' ) );
		$form->add( new Interval( 'ipb_bruteforce_period', Settings::i()->ipb_bruteforce_period, FALSE, array( 'valueAs' => Interval::MINUTES, 'min' => 0, 'max' => 10000, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'ipb_bruteforce_period' ) );
		$form->add( new YesNo( 'ipb_bruteforce_unlock', Settings::i()->ipb_bruteforce_unlock, FALSE, array(), NULL, NULL, NULL, 'ipb_bruteforce_unlock' ) );
		$form->addHeader( 'bruteforce_global_attempts_title' );
		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'bruteforce_global_attempts_block_desc', true, true ) );
		$form->add( new Number( 'bruteforce_global_attempts', Settings::i()->bruteforce_global_attempts, FALSE, array( 'min' => 5 ), NULL, Member::loggedIn()->language()->addToStack('after'), Member::loggedIn()->language()->addToStack('failed_logins'), 'bruteforce_global_attempts' ) );
		$form->add( new Interval( 'bruteforce_global_period', Settings::i()->bruteforce_global_period, FALSE, array( 'valueAs' => Interval::MINUTES, 'min' => 0, 'max' => 10000 ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'bruteforce_global_period' ) );

		$form->addHeader( 'login_settings' );
		$form->add( new YesNo( 'new_device_email', Settings::i()->new_device_email, FALSE ) );
		
		/* Save */
		if ( $values = $form->values() )
		{
			/* unlock all locked members if we have disabled the locking */
			if ( Settings::i()->ipb_bruteforce_attempts > 0 AND $values['ipb_bruteforce_attempts'] == 0 )
			{
				Member::updateAllMembers( array( 'failed_login_count' => 0 ) );
				Db::i()->delete( 'core_login_failures', [ 'login_member_id IS NOT NULL' ] );

				/* disable task */
				Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), "`key`='unlockmembers'" );
			}
			else if ( $values['ipb_bruteforce_attempts'] > 0 )
			{
				/* enable task */
				Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='unlockmembers'" );
			}

			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__login_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=login&tab=settings' ), 'saved' );
		}
		
		/* Display */
		return (string) $form;
	}
	
	/**
	 * HTTPS check
	 *
	 * @return void
	 */
	protected function httpsCheck() : void
	{
		try
		{
			$response = Url::external( 'https://' . mb_substr( Settings::i()->base_url, 7 ) )->request()->get();
			Output::i()->output = $response;
		}
		catch ( Exception $e )
		{
			Output::i()->output = Theme::i()->getTemplate( 'global' )->message( $e->getMessage() ?: '500_error_title', 'error' );
		}
	}
	
	/**
	 * Registration Settings
	 *
	 * @return string
	 */
	protected function _registration() : string
	{
		Dispatcher::i()->checkAcpPermission( 'registration_settings' );

		/* Check SSO Extensions for overloads */
		$disabledSettings = $appBlocks = [];
		$ssoSettings = [ 'allow_reg', 'allow_reg_target' ];
		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $app => $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() )
			{
				foreach( array_keys( $ext->overrideSettings() ) as $settingKey )
				{
					if( !in_array( $settingKey, $ssoSettings ) )
					{
						continue;
					}
					$appBlocks[ $app ] = Member::loggedIn()->language()->addToStack( '__app_' . explode( '_', $app )[0], FALSE );
					$disabledSettings[ $settingKey ] = $settingKey;
					Member::loggedIn()->language()->words[ $settingKey . '_desc'] = Member::loggedIn()->language()->addToStack( 'sso_setting_override', FALSE, [ 'pluralize' => [ count( $appBlocks ) ],'htmlsprintf' =>  [ Member::loggedIn()->language()->formatList( $appBlocks ) ] ] );
				}
			}
		}

		/* Build Form */
		$form = new Form();
		$form->addHeader('registration_standard_settings');
		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'registration_standard_settings_blurb', true, true ) );
		$form->add( new Radio( 'allow_reg', LoginClass::registrationType(), FALSE, [
			'options' 	=>  [
				'normal'	=> 'allow_reg_normal',
				'full'		=> 'allow_reg_full',
				'redirect'	=> 'allow_reg_redirect',
				'disabled'	=> 'allow_reg_disabled'
			],
			'toggles'	=> [
				'normal'    => [ 'allow_reg_normal_warning' ],
				'full'		=> [ 'minimum_age', 'use_coppa' ],
				'redirect'	=> [ 'allow_reg_target' ]
			],
			'disabled'	=> isset( $disabledSettings['allow_reg'] ) ? TRUE : ( Handler::findMethod( 'IPS\Login\Handler\Standard' ) ? [] : [ 'normal', 'full' ] )
		] ) );
	
		/* Do we have required custom fields? */
		if ( Db::i()->select( 'COUNT(*)', 'core_pfields_data', array( 'pf_not_null != 0 and pf_show_on_reg=1' ) )->first() )
		{
			Member::loggedIn()->language()->words['allow_reg_normal_warning'] = Member::loggedIn()->language()->addToStack( 'allow_reg_normal_desc_required_warning' );
		}

		$form->add( new FormUrl( 'allow_reg_target', Settings::i()->allow_reg_target, NULL, [ 'disabled' => isset( $disabledSettings['allow_reg_target'] ) ], function( $val )
		{
			if ( !$val and Request::i()->allow_red === 'redirect' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'allow_reg_target' ) );
		$form->add( new Number( 'minimum_age', Settings::i()->minimum_age, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'any_age' ), NULL, NULL, NULL, 'minimum_age' ) );
		$form->add( new YesNo( 'use_coppa', Settings::i()->use_coppa, FALSE, array( 'togglesOn' => array( 'coppa_fax', 'coppa_address' ) ), NULL, NULL, NULL, 'use_coppa' ) );
		$form->add( new Tel( 'coppa_fax', Settings::i()->coppa_fax, FALSE, array(), NULL, NULL, NULL, 'coppa_fax' ) );
		$form->add( new Address( 'coppa_address', Settings::i()->coppa_address ? GeoLocation::buildFromJson( Settings::i()->coppa_address ) : null, FALSE, array(), NULL, NULL, NULL, 'coppa_address' ) );
		$form->addHeader('registration_global_settings');
		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'registration_global_settings_blurb', true, true ) );
		$form->add( new Radio( 'reg_auth_type', Settings::i()->reg_auth_type, FALSE, array(
			'options'	=> array( 'user' => 'reg_auth_type_user', 'admin' => 'reg_auth_type_admin', 'admin_user' => 'reg_auth_type_admin_user', 'none' => 'reg_auth_type_none' ),
			'toggles'	=> array( 'user' => array( 'validate_day_prune' ), 'admin_user' => array( 'validate_day_prune' ) )
		), NULL, NULL, NULL, 'reg_auth_type' ) );
		$form->add( new Interval( 'validate_day_prune', Settings::i()->validate_day_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'validate_day_prune' ) );
		$form->add( new Stack( 'allowed_reg_email', explode( ',', Settings::i()->allowed_reg_email ), FALSE, array( 'placeholder' => 'mycompany.com' ), function( $value ) {
			if( isset( $value[0] ) AND mb_stripos( $value[0], '@' ) )
			{
				throw new DomainException( 'allowed_reg_email_email_detected' );
			}
		}, NULL, NULL ) );
		$form->add( new Radio( 'force_reg_terms', Settings::i()->force_reg_terms, FALSE, array( 'options' => array(
			'0'	=> 'force_reg_terms_0',
			'1'	=> 'force_reg_terms_1',
		) ) ) );

		/* Save */
		if ( $values = $form->values() )
		{
			/* Save */
			if ( isset( $values['allowed_reg_email'] ) and is_array( $values['allowed_reg_email'] ) )
			{
				$values['allowed_reg_email'] = implode( ',', $values['allowed_reg_email'] );
			}
			
			if ( isset( $values['coppa_address'] ) AND ( $values['coppa_address'] instanceof GeoLocation ) )
			{
				$values['coppa_address'] = json_encode( $values['coppa_address'] );
			}
			
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplogs__registration_settings' );
			
			/* Redirect */
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=login&tab=registration' ), 'saved' );
		}
		$desc = Theme::i()->getTemplate( 'global', 'core' )->message( Member::loggedIn()->language()->addToStack('registration_default_group_info', NULL, [ 'sprintf' => [Group::load( Settings::i()->member_group )->formattedName ] ] ), 'none i-border-radius_0' );
		
		/* Display */
		return $desc . $form;
	}
	
	/**
	 * Account Management Settings
	 *
	 * @return string
	 */
	protected function _accountsettings() : string
	{
		Dispatcher::i()->checkAcpPermission( 'login_account_management' );

		/* Check SSO Extensions for overloads */
		$disabledSettings = $appBlocks = [];
		$ssoSettings = [ 'allow_email_changes', 'allow_email_changes_target', 'allow_password_changes', 'allow_password_changes_target', 'allow_forgot_password', 'allow_forgot_password_target' ];

		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $app => $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() )
			{
				foreach( array_keys( $ext->overrideSettings() ) as $settingKey )
				{
					if( !in_array( $settingKey, $ssoSettings ) )
					{
						continue;
					}
					$appBlocks[ $settingKey ][] = Member::loggedIn()->language()->addToStack( '__app_' . explode( '_', $app )[0], FALSE );
					$disabledSettings[ $settingKey ] = true;
				}
			}
		}

		foreach( $appBlocks as $sKey => $apps )
		{
			Member::loggedIn()->language()->words[ $sKey . '_desc'] = Member::loggedIn()->language()->addToStack( 'sso_setting_override', FALSE, [ 'pluralize' => [ count( $apps ) ],'htmlsprintf' =>  [ Member::loggedIn()->language()->formatList( $apps ) ] ] );
		}

		/* Build Form */
		$form = new Form();
		$form->addHeader( 'security_header_accounts' );
		$form->add( new YesNo( 'password_strength_meter', Settings::i()->password_strength_meter, FALSE, array( 'togglesOn' => array( 'password_strength_meter_enforce' ) ), NULL, NULL, NULL, 'password_strength_meter' ) );
		$form->add( new YesNo( 'password_strength_meter_enforce', Settings::i()->password_strength_meter_enforce, FALSE, array( 'togglesOn' => array( 'password_strength_option' ) ), NULL, NULL, NULL, 'password_strength_meter_enforce' ) );
		$strengthOptions = array(
			'3' => 'strength_3',
			'4' => 'strength_4',
			'5' => 'strength_5',
		);
		$form->add( new Radio( 'password_strength_option', Settings::i()->password_strength_option, FALSE, array( 'options' => $strengthOptions ), NULL, NULL, NULL, 'password_strength_option' ) );
		$form->add( new YesNo( 'device_management', Settings::i()->device_management, FALSE ) );
		$form->addHeader('account_management_email_pass');
		$form->addHtml( Theme::i()->getTemplate( 'forms' )->blurb( 'account_management_email_pass_blurb', true, true ) );
		$form->add( new Radio( 'allow_email_changes', Settings::i()->allow_email_changes, FALSE, [
			'options'	 => [
				'normal'	=> 'allow_email_changes_normal',
				'redirect'	=> 'allow_email_changes_redirect',
				'disabled'	=> 'allow_email_changes_disabled',
			],
			'toggles'	=> [
				'redirect'	=> [ 'allow_email_changes_target' ]
			],
			'disabled'  => isset( $disabledSettings['allow_email_changes'] )
		] ) );
		$form->add( new FormUrl( 'allow_email_changes_target', Settings::i()->allow_email_changes_target, NULL, [ 'disabled'  => isset( $disabledSettings['allow_email_changes_target'] ) ], function( $val )
		{
			if ( !$val and Request::i()->allow_email_changes === 'redirect' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'allow_email_changes_target' ) );
		$form->add( new Radio( 'allow_password_changes', Settings::i()->allow_password_changes, FALSE, [
			'options'	 => [
				'normal'	=> 'allow_password_changes_normal',
				'redirect'	=> 'allow_password_changes_redirect',
				'disabled'	=> 'allow_password_changes_disabled',
			],
			'toggles'	=> [
				'redirect'	=> [ 'allow_password_changes_target' ]
			],
			'disabled'  => isset( $disabledSettings['allow_password_changes'] )
		] ) );
		$form->add( new FormUrl( 'allow_password_changes_target', Settings::i()->allow_password_changes_target, NULL, [ 'disabled'  => isset( $disabledSettings['allow_password_changes_target'] ) ], function( $val )
		{
			if ( !$val and Request::i()->allow_password_changes === 'redirect' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'allow_password_changes_target' ) );
		$form->add( new Radio( 'allow_forgot_password', Settings::i()->allow_forgot_password, FALSE, [
			'options'	 => [
				'normal'	=> 'allow_forgot_password_normal',
				'handler'	=> 'allow_forgot_password_handler',
				'redirect'	=> 'allow_forgot_password_redirect',
				'disabled'	=> 'allow_forgot_password_disabled',
			],
			'toggles'	=> [
				'redirect'	=> [ 'allow_forgot_password_target' ]
			],
			'disabled'  => isset( $disabledSettings['allow_forgot_password'] )
		] ) );
		$form->add( new FormUrl( 'allow_forgot_password_target', Settings::i()->allow_forgot_password_target, FALSE, [ 'disabled'  => isset( $disabledSettings['allow_forgot_password_target'] ) ], function( $val )
		{
			if ( !$val and Request::i()->allow_forgot_password === 'redirect' )
			{
				throw new DomainException('form_required');
			}
		}, NULL, NULL, 'allow_forgot_password_target' ) );

		/* Save */
		if ( $values = $form->values() )
		{
			$values['password_strength_meter_enforce'] = $values['password_strength_meter'] ? $values['password_strength_meter_enforce'] : FALSE;
			
			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__account_management_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=login&tab=accountsettings' ), 'saved' );
		}
		
		/* Display */
		return (string) $form;
	}
	
	/**
	 * Force all users to be logged out
	 *
	 * @return	void
	 */
	protected function forceLogout() : void
	{
		Session::i()->csrfCheck();
		
		Db::i()->update( 'core_members_known_devices', array( 'login_key' => NULL ) );
		Db::i()->delete( 'core_sessions' );

		Session::i()->log( 'acplogs__logout_force' );
		Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=login' ), 'logged_out_force' );
	}
}