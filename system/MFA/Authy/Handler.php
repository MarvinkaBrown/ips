<?php
/**
 * @brief		Multi Factor Authentication Handler for Authy
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 March 2017
 */

namespace IPS\MFA\Authy;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\DateTime;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\MFA\Authy\Exception as AuthyException;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multi Factor Authentication Handler for Authy
 */
class Handler extends MFAHandler
{
	/**
	 * @brief	Key
	 */
	protected string $key = 'authy';
	
	/* !Setup */
	
	/**
	 * Handler is enabled
	 *
	 * @return	bool
	 */
	public function isEnabled(): bool
	{
		return Settings::i()->authy_enabled;
	}
	
	/**
	 * Member *can* use this handler (even if they have not yet configured it)
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function memberCanUseHandler( Member $member ): bool
	{
		return Settings::i()->authy_groups == '*' or $member->inGroup( explode( ',', Settings::i()->authy_groups ) );
	}
	
	/**
	 * Member has configured this handler
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function memberHasConfiguredHandler( Member $member ): bool
	{
		return isset( $member->mfa_details['authy'] ) and $member->mfa_details['authy']['setup'];
	}
		
	/**
	 * Show a setup screen
	 *
	 * @param	Member		$member						The member
	 * @param	bool			$showingMultipleHandlers	Set to TRUE if multiple options are being displayed
	 * @param	Url	$url						URL for page
	 * @return	string
	 */
	public function configurationScreen( Member $member, bool $showingMultipleHandlers, Url $url ): string
	{
		$mfaDetails = $member->mfa_details;
		
		/* Starting again? */
		if ( isset( $mfaDetails['authy']['pendingId'] ) and isset( Request::i()->_new ) )
		{
			unset( $mfaDetails['authy']['pendingId'] );
			$member->mfa_details = $mfaDetails;
			$member->save();
		}
				
		/* If we have already enterred our phone number, ask for the code */
		if ( isset( $mfaDetails['authy'] ) and isset( $mfaDetails['authy']['pendingId'] ) and !isset( $_SESSION['authyConfigureError'] ) )
		{	
			/* Asking for a text or call instead? */
			$availableMethods = explode( ',', Settings::i()->authy_setup );
			if ( isset( Request::i()->authy_method ) and $mfaDetails['authy']['setupMethod'] == 'authy' and in_array( Request::i()->authy_method, $availableMethods ) )
			{
				try
				{
					/* Send text or make call */
					if ( Request::i()->authy_method == 'phone' )
					{
						static::totp( "call/{$mfaDetails['authy']['pendingId']}", 'get', array( 'force' => 'true' ) );
					}
					elseif ( Request::i()->authy_method == 'sms' )
					{
						static::totp( "sms/{$mfaDetails['authy']['pendingId']}", 'get', array( 'force' => 'true' ) );
					}
					
					/* Update details */
					$mfaDetails['authy']['setupMethod'] = Request::i()->authy_method;
					$member->mfa_details = $mfaDetails;
					$member->save();
				}
				catch ( \Exception $e )
				{
					Log::log( $e, 'authy' );
					$_SESSION['authyAuthError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : 'authy_error';
				}				
			}

			/* Display */
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( $mfaDetails['authy']['setupMethod'], TRUE, $_SESSION['authyAuthError'] ?? 'authy_error', TRUE, explode( ',', Settings::i()->authy_setup ), NULL, $url );
		}
		else
		{
			/* If they have used their allowed attempts, make them wait */
			if ( isset( $mfaDetails['authy'] ) and isset( $mfaDetails['authy']['changeAttempts'] ) and $mfaDetails['authy']['changeAttempts'] >= Settings::i()->authy_setup_tries )
			{
				$lockEndTime = $mfaDetails['authy']['lastChangeAttempt'] + ( Settings::i()->authy_setup_lockout * 3600 );
				if ( $lockEndTime < time() )
				{
					$mfaDetails['authy']['changeAttempts'] = 0;
					$member->mfa_details = $mfaDetails;
					$member->save();
				}
				else
				{
					return Theme::i()->getTemplate( 'login', 'core', 'global' )->authySetupLockout( $showingMultipleHandlers, DateTime::ts( $lockEndTime ) );
				}
			}
			
			/* Otherwise show the form */
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->authySetup( isset( Request::i()->countryCode ) ? Request::i()->countryCode : Address::calculateDefaultCountry(), isset( Request::i()->phoneNumber ) ? Request::i()->phoneNumber : '', $showingMultipleHandlers, explode( ',', Settings::i()->authy_setup ), $_SESSION['authyConfigureError'] ?? NULL );
		}
	}
	
	/**
	 * Submit configuration screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	public function configurationScreenSubmit( Member $member ): bool
	{
		$mfaDetails = $member->mfa_details;
		
		/* If we've enterred a code, verify it */
		if ( isset( $mfaDetails['authy'] ) and isset( $mfaDetails['authy']['pendingId'] ) and isset( Request::i()->authy_auth_code ) )
		{
			$_SESSION['authyAuthError'] = NULL;
			try
			{
				$response = static::totp( "verify/" . preg_replace( '/[^A-Z0-9]/i', '', Request::i()->authy_auth_code ) . "/{$mfaDetails['authy']['pendingId']}" );
				
				$mfaDetails['authy'] = array( 'id' => $mfaDetails['authy']['pendingId'], 'setup' => true );
				$member->mfa_details = $mfaDetails;
				$member->save();
				
				$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => TRUE ) );
				
				return true;
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				if ( in_array( $e->getCode(), array( AuthyException::TOKEN_REUSED, AuthyException::TOKEN_INVALID ) ) )
				{
					$_SESSION['authyAuthError'] = $e->getUserMessage();
				}
				else
				{
					Log::log( $e, 'authy' );
					$_SESSION['authyAuthError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : $e->getUserMessage();
				}
				return false;
			}
			catch ( \Exception $e )
			{
				Log::log( $e, 'authy' );
				$_SESSION['authyAuthError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : 'authy_error';
				return false;
			}
		}
		
		/* Otherwise we need to generate an ID */
		elseif ( Request::i()->phoneNumber )
		{
			/* Do we need to wait a while? */
			if ( isset( $mfaDetails['authy'] ) and isset( $mfaDetails['authy']['changeAttempts'] ) and $mfaDetails['authy']['changeAttempts'] >= Settings::i()->authy_setup_tries )
			{
				return false;
			}
			
			/* Call Authy */
			$availableMethods = explode( ',', Settings::i()->authy_setup );
			$method = ( isset( Request::i()->method ) and in_array( Request::i()->method, $availableMethods ) ) ? Request::i()->method : array_shift( $availableMethods );
			$_SESSION['authyConfigureError'] = NULL;
			try
			{
				/* Create User */
				$data = array(
					'user'						=> array(
						'email'						=> $member->email,
						'cellphone'					=> Request::i()->phoneNumber,
						'country_code'				=> explode( '-', Request::i()->countryCode )[1]
					)
				);
				if ( Settings::i()->authy_method != 'authy' )
				{
					$data['send_install_link_via_sms'] = false;
				}
				$response = static::totp( 'users/new', 'post', $data );
				if ( isset( $mfaDetails['authy']['id'] ) and $mfaDetails['authy']['id'] == $response['user']['id'] )
				{
					return true;
				}
				
				/* Send text message or make phone call */
				if ( $method == 'phone' )
				{
					static::totp( "call/{$response['user']['id']}", 'get', array( 'force' => 'true' ) );
				}
				elseif ( $method == 'sms' )
				{
					static::totp( "sms/{$response['user']['id']}", 'get', array( 'force' => 'true' ) );
				}
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				if ( in_array( $e->getCode(), array( AuthyException::USER_INVALID, AuthyException::PHONE_NUMBER_INVALID ) ) )
				{
					$_SESSION['authyConfigureError'] = $e->getUserMessage();
				}
				else
				{
					Log::log( $e, 'authy' );
					$_SESSION['authyConfigureError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : 'authy_error';
				}
				return false;
			}
			catch ( \Exception $e )
			{
				Log::log( $e, 'authy' );
				$_SESSION['authyConfigureError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : 'authy_error';
				return false;
			}
			
			/* Log the details */
			$mfaDetails['authy']['pendingId'] = $response['user']['id'];
			if ( !isset( $mfaDetails['authy']['changeAttempts'] ) )
			{
				$mfaDetails['authy']['changeAttempts'] = 1;
			}
			else
			{
				$mfaDetails['authy']['changeAttempts']++;
			}
			$mfaDetails['authy']['lastChangeAttempt'] = time();
			if ( !isset( $mfaDetails['authy']['setup'] ) )
			{
				$mfaDetails['authy']['setup'] = false;
			}
			$mfaDetails['authy']['setupMethod'] = $method;
			$member->mfa_details = $mfaDetails;
			$member->save();
			
			return false;
		}
		
		return false;
	}
	
	/* !Authentication */
	
	/**
	 * Get the form for a member to authenticate
	 *
	 * @param	Member		$member		The member
	 * @param	Url	$url		URL for page
	 * @return	string
	 */
	public function authenticationScreen( Member $member, Url $url ): string
	{
		$mfaDetails = $member->mfa_details;
		$availableMethods = explode( ',', Settings::i()->authy_method );
		
		/* If we sent a code, but it was more than one minute ago, log a failure and reset */
		if ( isset( $mfaDetails['authy']['sent'] ) and $mfaDetails['authy']['sent']['time'] < ( time() - 60 ) )
		{
			unset( $mfaDetails['authy']['sent'] );
			$member->mfa_details = $mfaDetails;
			$member->failed_mfa_attempts++;
			$member->save();
		}
				
		/* If Authy app is one of the available options... */
		if ( in_array( 'authy', $availableMethods ) )
		{
			/* Are we getting a onetouch status? */
			if ( Request::i()->onetouchCheck and Request::i()->isAjax() )
			{
				Output::i()->json( array( 'status' => intval( $this->_onetouchCheck( $member, Request::i()->onetouchCheck ) ) ) );
			}
			
			/* If it is not the only option... */
			if ( count( $availableMethods ) > 1 )
			{
				/* If they have asked for a text/call instead, do that */
				if ( !isset( $mfaDetails['authy']['sent'] ) and isset( Request::i()->authy_method ) and in_array( Request::i()->authy_method, $availableMethods ) )
				{
					try
					{
						/* Send text or make call */
						if ( Request::i()->authy_method == 'phone' )
						{
							static::totp( "call/{$mfaDetails['authy']['id']}", 'get', array( 'force' => 'true' ) );
						}
						elseif ( Request::i()->authy_method == 'sms' )
						{
							static::totp( "sms/{$mfaDetails['authy']['id']}", 'get', array( 'force' => 'true' ) );
						}
						
						/* Update details */
						$mfaDetails['authy']['sent'] = array( 'method' => Request::i()->authy_method, 'time' => time() );
						$member->mfa_details = $mfaDetails;
						$member->save();
					}
					catch ( \Exception $e )
					{
						Log::log( $e, 'authy' );
						$_SESSION['authyAuthError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : 'authy_error';
					}				
				}
				if ( isset( $mfaDetails['authy']['sent'] ) )
				{
					return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( $mfaDetails['authy']['sent']['method'], TRUE, $_SESSION['authyAuthError'] ?? 'authy_error', FALSE, $availableMethods, NULL, $url );
				}
				
				/* Otherwise, check if they have the app installed. If they do, show the Authy authenticate page */
				try
				{
					$userDetails = static::totp("users/{$mfaDetails['authy']['id']}/status");
					$userHasAuthyApp = FALSE;
					foreach ( $userDetails['status']['devices'] as $device )
					{
						if ( $device != 'sms' )
						{
							$userHasAuthyApp = TRUE;
						}
					}
					
					if ( $userHasAuthyApp )
					{						
						return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( 'authy', TRUE, $_SESSION['authyAuthError'] ?? 'authy_error', FALSE, $availableMethods, $this->_onetouchInit( $member, $url ), $url );
					}
				}
				catch ( \Exception $e ) { }
			}
			
			/* If it is the only option, show it anyway */
			else
			{
				return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( 'authy', TRUE, $_SESSION['authyAuthError'] ?? 'authy_error', FALSE, $availableMethods, $this->_onetouchInit( $member, $url ), $url );
			}
		}
		
		/* If text message is the only available option, or we have chosen that option, do that... */
		if ( in_array( 'sms', $availableMethods ) and ( count( $availableMethods ) == 1 ) or ( isset( $mfaDetails['authy']['sent'] ) and $mfaDetails['authy']['sent']['method'] == 'sms' ) or ( isset( Request::i()->authy_method ) and Request::i()->authy_method == 'sms' ) )
		{
			/* Send the text if we haven't already */
			if ( !isset( $mfaDetails['authy']['sent'] ) )
			{
				try
				{
					static::totp( "sms/{$mfaDetails['authy']['id']}", 'get', array( 'force' => 'true' ) );
				}
				catch ( \Exception $e )
				{
					Log::log( $e, 'authy' );
					return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyError( $e->getMessage() );
				}
				
				$mfaDetails['authy']['sent'] = array( 'method' => 'sms', 'time' => time() );
				$member->mfa_details = $mfaDetails;
				$member->save();
			}
			
			/* Show screen */
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( 'sms', TRUE, $_SESSION['authyAuthError'] ?? 'authy_error', FALSE, $availableMethods, NULL, $url );
		}
		
		/* If we have confirmed the phone call, do that now */
		if ( ( isset( $mfaDetails['authy']['sent'] ) and $mfaDetails['authy']['sent']['method'] == 'phone' ) or ( isset( Request::i()->authy_method ) and Request::i()->authy_method == 'phone' ) )
		{
			/* Send the text if we haven't already */
			if ( !isset( $mfaDetails['authy']['sent'] ) )
			{
				try
				{
					static::totp( "call/{$mfaDetails['authy']['id']}", 'get', array( 'force' => 'true' ) );
				}
				catch ( \Exception $e )
				{
					Log::log( $e, 'authy' );
					return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyError( $e->getMessage() );
				}
				
				$mfaDetails['authy']['sent'] = array( 'method' => 'call', 'time' => time() );
				$member->mfa_details = $mfaDetails;
				$member->save();
			}
			
			/* Show screen */
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( 'phone', TRUE, $_SESSION['authyAuthError'] ?? 'authy_error', FALSE, $availableMethods, NULL, $url );
		}
		
		/* Otherwise we're going to show a screen */
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->authyAuthenticate( count( $availableMethods ) == 1 ? 'phone' : 'choose', FALSE, $_SESSION['authyAuthError'] ?? 'authy_error', FALSE, $availableMethods, NULL, $url );
	}
	
	/**
	 * If enabled, initiate a OneTouch request and get the ID
	 *
	 * @param	Member		$member		The member
	 * @param	Url	$url		URL for page
	 * @return	string|null
	 */
	protected function _onetouchInit( Member $member, Url $url ): ?string
	{		
		if ( Settings::i()->authy_onetouch )
		{
			$mfaDetails = $member->mfa_details;

			if ( isset( $mfaDetails['onetouch'] ) and $mfaDetails['onetouch']['time'] > ( time() - 30 ) )
			{
				$response = static::onetouch( "approval_requests/" . preg_replace( '/[^A-Z0-9\-]/i', '', $mfaDetails['onetouch']['id'] ) );
				
				if ( $response['approval_request']['status'] === 'pending' )
				{
					return $mfaDetails['onetouch']['id'];
				}
			}
			
			try
			{
				$response = static::onetouch( "users/{$mfaDetails['authy']['id']}/approval_requests", 'post', array(
					'message'			=> $member->language()->get('authy_onetouch_message'),
					'seconds_to_expire'	=> 300
				) );
				
				$mfaDetails['onetouch'] = array( 'id' => $response['approval_request']['uuid'], 'time' => time() );
				$member->mfa_details = $mfaDetails;
				$member->save();
			}
			catch ( \Exception $e )
			{
				return NULL;
			}
			
			return $mfaDetails['onetouch']['id'];
		}
		return NULL;
	}

	/**
	 * Check the status of a onetouch request
	 *
	 * @param Member $member The member
	 * @param string $id The onetouch request ID
	 * @return bool|string|null
	 */
	protected function _onetouchCheck( Member $member, string $id ): bool|string|null
	{
		if ( Settings::i()->authy_onetouch )
		{
			$mfaDetails = $member->mfa_details;
			
			try
			{				
				$response = static::onetouch( "approval_requests/" . preg_replace( '/[^A-Z0-9\-]/i', '', $id ) );
				return $response['approval_request']['status'] === 'approved';
			}
			catch ( \Exception $e ) {}
		}
		return FALSE;
	}
	
	/**
	 * Submit authentication screen. Return TRUE if was accepted
	 *
	 * @param	Member		$member	The member
	 * @return	bool
	 */
	public function authenticationScreenSubmit( Member $member ): bool
	{
		$mfaDetails = $member->mfa_details;

		$_SESSION['authyAuthError'] = NULL;
		
		try
		{
			if ( isset( Request::i()->authy_auth_code ) )
			{
				$response = static::totp( "verify/" . preg_replace( '/[^A-Z0-9]/i', '', Request::i()->authy_auth_code ) . "/{$mfaDetails['authy']['id']}" );
				$mfaDetails['authy'] = array( 'id' => $mfaDetails['authy']['id'], 'setup' => true );
			}
			elseif ( isset( Request::i()->onetouch ) )
			{
				return $this->_onetouchCheck( $member, Request::i()->onetouch );
			}
			else
			{
				return false;
			}
			$member->mfa_details = $mfaDetails;
			$member->save();
			
			return true;
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			if ( in_array( $e->getCode(), array( AuthyException::TOKEN_REUSED, AuthyException::TOKEN_INVALID ) ) )
			{
				$_SESSION['authyAuthError'] = $e->getUserMessage();
			}
			else
			{
				Log::log( $e, 'authy' );
				$_SESSION['authyAuthError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : $e->getUserMessage();
			}
			return false;
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'authy' );
			$_SESSION['authyAuthError'] = Member::loggedIn()->isAdmin() ? $e->getMessage() : 'authy_error';
			return false;
		}
	}
	
	/* !ACP */
	
	/**
	 * Toggle
	 *
	 * @param	bool	$enabled	On/Off
	 * @return	void
	 */
	public function toggle( bool $enabled ): void
	{
		/* This handler is deprecated, so if it's already disabled, don't allow it to be re-enabled */
		if( !$this->isEnabled() )
		{
			return;
		}

		if ( $enabled )
		{
			static::verifyApiKey( Settings::i()->authy_key );
		}
		
		Settings::i()->changeValues( array( 'authy_enabled' => $enabled ) );
	}
	
	/**
	 * ACP Settings
	 *
	 * @return	string
	 */
	public function acpSettings(): string
	{
		if( !$this->isEnabled() )
		{
			Output::i()->error( 'authy_deprecated_message', '2C345/3' );
		}

		$form = new Form;
		
		$form->add( new Text( 'authy_key', Settings::i()->authy_key, TRUE, array(), function( $val ) {
			$details = Handler::verifyApiKey( $val );
			if ( !$details['app']['sms_enabled'] and ( array_key_exists( 'sms', Request::i()->authy_setup ) or array_key_exists( 'sms', Request::i()->authy_method ) ) )
			{
				throw new DomainException('authy_key_no_sms');
			}
			if ( !$details['app']['phone_calls_enabled'] and ( array_key_exists( 'phone', Request::i()->authy_setup ) or array_key_exists( 'phone', Request::i()->authy_method ) ) )
			{
				throw new DomainException('authy_key_no_sms');
			}
			if ( !$details['app']['onetouch_enabled'] and Request::i()->authy_onetouch )
			{
				throw new DomainException('authy_key_no_onetouch');
			}
		}, NULL, Member::loggedIn()->language()->addToStack('authy_key_suffix') ) );
		
		$form->add( new CheckboxSet( 'authy_groups', Settings::i()->authy_groups == '*' ? '*' : explode( ',', Settings::i()->authy_groups ), FALSE, array(
			'multiple'		=> TRUE,
			'options'		=> array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ),
			'unlimited'		=> '*',
			'unlimitedLang'	=> 'everyone',
			'impliedUnlimited' => TRUE
		) ) );
				
		$form->addHeader('authy_setup_header');
		$form->add( new CheckboxSet( 'authy_setup', explode( ',', Settings::i()->authy_setup ), TRUE, array( 'options' => array(
			'authy'			=> 'authy_method_authy',
			'sms'			=> 'authy_method_sms',
			'phone'			=> 'authy_method_phone',
		) ) ) );
		$form->add( new Custom( 'authy_setup_protection', array( Settings::i()->authy_setup_tries, Settings::i()->authy_setup_lockout ), FALSE, array(
			'getHtml' => function( $field ) {
				return Theme::i()->getTemplate('settings')->authySetupProtection( $field->value );
			}
		) ) );
		
		$form->addHeader('authy_authenticate_header');
		$form->add( new CheckboxSet( 'authy_method', explode( ',', Settings::i()->authy_method ), TRUE, array(
			'options' => array(
				'authy'			=> 'authy_method_authy',
				'sms'			=> 'authy_method_sms',
				'phone'			=> 'authy_method_phone',
			),
			'toggles' => array(
				'authy'			=> array( 'authy_onetouch' )
			)
		) ) );
		$form->add( new Radio( 'authy_onetouch', Settings::i()->authy_onetouch, TRUE, array(
			'options' => array(
				'1'			=> 'authy_onetouch_on',
				'0'			=> 'authy_onetouch_off',
			),
		), NULL, NULL, NULL, 'authy_onetouch' ) );
		
		if ( $values = $form->values() )
		{
			$values['authy_groups'] = ( $values['authy_groups'] == '*' ) ? '*' : implode( ',', $values['authy_groups'] );
			$values['authy_setup'] = isset( $values['authy_setup'] ) ? implode( ',', $values['authy_setup'] ) : '';
			$values['authy_setup_tries'] = $values['authy_setup_protection'][0];
			$values['authy_setup_lockout'] = $values['authy_setup_protection'][1];
			unset( $values['authy_setup_protection'] );
			$values['authy_method'] = isset( $values['authy_method'] ) ? implode( ',', $values['authy_method'] ) : '';
			$form->saveAsSettings( $values );	

			Session::i()->log( 'acplogs__mfa_handler_enabled', array( "mfa_authy_title" => TRUE ) );
			Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=mfa' ), 'saved' );
		}
		
		return (string) $form;
	}
	
	
	
	/* !Misc */
	
	/**
	 * If member has configured this handler, disable it
	 *
	 * @param	Member	$member	The member
	 * @return	void
	 */
	public function disableHandlerForMember( Member $member ): void
	{
		$mfaDetails = $member->mfa_details;
		
		if ( isset( $mfaDetails['authy']['id'] ) )
		{
			try
			{
				static::totp( "users/{$mfaDetails['authy']['id']}/delete", 'post', array(
					'user_ip'	=> Request::i()->ipAddress()
				) );
			}
			catch ( \Exception $e ) { }
		}
		
		unset( $mfaDetails['authy'] );
		$member->mfa_details = $mfaDetails;
		$member->save();

		/* Log MFA Disable */
		$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => FALSE ) );
	}
	
	/**
	 * Get title for UCP
	 *
	 * @return	string
	 */
	public function ucpTitle(): string
	{
		$availableMethods = explode( ',', Settings::i()->authy_method );
		
		if ( in_array( 'authy', $availableMethods ) )
		{
			return Member::loggedIn()->language()->addToStack('mfa_authy_title');
		}
		elseif ( in_array( 'sms', $availableMethods ) and count( $availableMethods ) == 1 )
		{
			return Member::loggedIn()->language()->addToStack('mfa_sms_title');
		}
		else
		{
			return Member::loggedIn()->language()->addToStack('mfa_phone_title');
		}
	}
	
	/**
	 * Get description for UCP
	 *
	 * @return	string
	 */
	public function ucpDesc(): string
	{
		$availableMethods = explode( ',', Settings::i()->authy_method );
		
		if ( in_array( 'authy', $availableMethods ) )
		{
			if ( count( $availableMethods ) == 1 )
			{
				return Member::loggedIn()->language()->addToStack('mfa_authy_only_desc_user');
			}
			else
			{
				return Member::loggedIn()->language()->addToStack('mfa_authy_mixed_desc_user');
			}
		}
		elseif ( in_array( 'sms', $availableMethods ) and count( $availableMethods ) == 1 )
		{
			return Member::loggedIn()->language()->addToStack('mfa_sms_desc_user');
		}
		elseif ( in_array( 'phone', $availableMethods ) and count( $availableMethods ) == 1 )
		{
			return Member::loggedIn()->language()->addToStack('mfa_phone_desc_user');
		}
		else
		{
			return Member::loggedIn()->language()->addToStack('mfa_sms_or_phone_desc_user');
		}
	}
	
	/**
	 * Get label for recovery button
	 *
	 * @return	string
	 */
	public function recoveryButton(): string
	{
		$availableMethods = explode( ',', Settings::i()->authy_method );
		
		if ( in_array( 'authy', $availableMethods ) and count( $availableMethods ) == 1 )
		{
			return Member::loggedIn()->language()->addToStack('mfa_authy_recovery');
		}
		elseif ( in_array( 'sms', $availableMethods ) and count( $availableMethods ) == 1 )
		{
			return Member::loggedIn()->language()->addToStack('mfa_sms_recovery');
		}
		else
		{
			return Member::loggedIn()->language()->addToStack('mfa_phone_recovery');
		}
	}
	
	/* !Helper Methods */
	
	/**
	 * Make TOTP API Call
	 *
	 * @param string $endpoint	The endpoint to call
	 * @param string $method		'get' or 'post'
	 * @param array|null $data		Post data or additional query string parameters
	 * @return	array
	 */
	public static function totp( string $endpoint, string $method='get', array $data=NULL ): array
	{
		return static::_api( "protected/json/{$endpoint}", $method, $data );
	}
	
	/**
	 * Make OneTouch API Call
	 *
	 * @param string $endpoint	The endpoint to call
	 * @param string $method		'get' or 'post'
	 * @param array|null $data		Post data or additional query string parameters
	 * @return	array
	 */
	public static function onetouch( string $endpoint, string $method='get', array $data=NULL ): array
	{
		return static::_api( "onetouch/json/{$endpoint}", $method, $data );
	}
	
	/**
	 * Make API Call
	 *
	 * @param string $endpoint	The endpoint to call
	 * @param string $method		'get' or 'post'
	 * @param array|null $data		Post data or additional query string parameters
	 * @return	array
	 */
	protected static function _api( string $endpoint, string $method='get', array $data=NULL ): array
	{
		$url = Url::external("https://api.authy.com/{$endpoint}")->setQueryString( 'api_key', Settings::i()->authy_key );
		
		if ( $method == 'get' )
		{
			$response = $url->setQueryString( $data )->request()->get();
		}
		else
		{
			$response = $url->request()->post( $data );
		}
		
		$response = $response->decodeJson();
		
		if ( !$response['success'] )
		{
			throw new \IPS\Http\Request\Exception( $response['message'], $response['error_code'] );
		}
		
		return $response;
	}
	
	/**
	 * Verify an Authy API Key
	 *
	 * @param string $val	The API key submitted
	 * @return	array
	 * @throws	DomainException
	 */
	public static function verifyApiKey( string $val ): array
	{
		try
		{
			return Url::external("https://api.authy.com/protected/json/app/details")->setQueryString( 'api_key', $val )->request()->get()->decodeJson();
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new DomainException( $e->getMessage() );
		}
	}
	
}