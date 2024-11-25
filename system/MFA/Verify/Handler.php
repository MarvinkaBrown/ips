<?php

/**
 * @brief        Multi Factor Authentication Handler for Verify
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        3/29/2023
 */

namespace IPS\MFA\Verify;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\DateTime;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Address;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Member\Group;
use IPS\MFA\MFAHandler;
use IPS\MFA\Verify\Exception as VerifyException;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function in_array;
use function strpos;
use function substr;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Handler extends MFAHandler
{
	/**
	 * @brief	Key
	 */
	protected string $key = 'verify';

	/**
	 * Handler is enabled
	 *
	 * @return    bool
	 */
	public function isEnabled(): bool
	{
		return Settings::i()->verify_enabled;
	}

	/**
	 * Member *can* use this handler (even if they have not yet configured it)
	 *
	 * @param Member $member The member
	 * @return    bool
	 */
	public function memberCanUseHandler( Member $member ): bool
	{
		return Settings::i()->verify_groups == '*' or $member->inGroup( explode( ',', Settings::i()->verify_groups ) );
	}

	/**
	 * Member has configured this handler
	 *
	 * @param Member $member The member
	 * @return    bool
	 */
	public function memberHasConfiguredHandler( Member $member ): bool
	{
		return isset( $member->mfa_details['verify'] ) and $member->mfa_details['verify']['setup'];
	}

	/**
	 * Show a setup screen
	 *
	 * @param Member $member The member
	 * @param bool $showingMultipleHandlers Set to TRUE if multiple options are being displayed
	 * @param Url $url URL for page
	 * @return    string
	 */
	public function configurationScreen( Member $member, bool $showingMultipleHandlers, Url $url ): string
	{
		$mfaDetails = $member->mfa_details;

		/* Starting again? */
		if ( isset( $mfaDetails['verify']['pendingId'] ) and isset( Request::i()->_new ) )
		{
			unset( $mfaDetails['verify']['pendingId'] );
			$member->mfa_details = $mfaDetails;
			$member->save();
		}

		/* If we have already entered our phone number, ask for the code */
		if ( isset( $mfaDetails['verify'] ) and isset( $mfaDetails['verify']['pendingId'] ) and !isset( $_SESSION['verifyConfigureError'] ) )
		{
			/* Asking for a text or call instead? */
			$availableMethods = explode( ',', Settings::i()->verify_setup );
			if ( isset( Request::i()->verify_method ) and $mfaDetails['verify']['setupMethod'] == 'verify' and in_array( Request::i()->verify_method, $availableMethods ) )
			{
				try
				{
					/* Verify with the API */
					$response = static::_api( "Services/" . Settings::i()->verify_service_sid . "/VerificationCheck", 'post', array(
						'VerificationSid' => $mfaDetails['verify']['pendingId'],
						'Code' => Request::i()->verify_auth_code
					) );

					if( $response['status'] == 'approved' )
					{
						/* Update details */
						$mfaDetails['verify']['setupMethod'] = Request::i()->verify_method;
						$member->mfa_details = $mfaDetails;
						$member->save();
					}
					else
					{
						throw new VerifyException( 'verify_mfa_invalid_code' );
					}
				}
				catch ( \Exception $e )
				{
					Log::log( $e, 'verify' );
					$_SESSION['verifyAuthError'] = $e->getMessage();
				}
			}

			/* Display */
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->verifyAuthenticate( $mfaDetails['verify']['setupMethod'], TRUE, $_SESSION['verifyAuthError'] ?? 'verify_error', TRUE, explode( ',', Settings::i()->verify_setup ), $url );
		}
		else
		{
			/* If they have used their allowed attempts, make them wait */
			if ( isset( $mfaDetails['verify'] ) and isset( $mfaDetails['verify']['changeAttempts'] ) and $mfaDetails['verify']['changeAttempts'] >= Settings::i()->verify_setup_tries )
			{
				$lockEndTime = $mfaDetails['verify']['lastChangeAttempt'] + ( Settings::i()->verify_setup_lockout * 3600 );
				if ( $lockEndTime < time() )
				{
					$mfaDetails['verify']['changeAttempts'] = 0;
					$member->mfa_details = $mfaDetails;
					$member->save();
				}
				else
				{
					return Theme::i()->getTemplate( 'login', 'core', 'global' )->verifySetupLockout( $showingMultipleHandlers, DateTime::ts( $lockEndTime ) );
				}
			}

			/* Otherwise show the form */
			return Theme::i()->getTemplate( 'login', 'core', 'global' )->verifySetup( Request::i()->countryCode ?? Address::calculateDefaultCountry(), Request::i()->phoneNumber ?? '', $showingMultipleHandlers, explode( ',', Settings::i()->verify_setup ), $_SESSION['verifyConfigureError'] ?? NULL );
		}
	}

	/**
	 * Submit configuration screen. Return TRUE if was accepted
	 *
	 * @param Member $member The member
	 * @return    bool
	 */
	public function configurationScreenSubmit( Member $member ): bool
	{
		$mfaDetails = $member->mfa_details;

		/* If we've enterred a code, verify it */
		if ( isset( $mfaDetails['verify'] ) and isset( $mfaDetails['verify']['pendingId'] ) and isset( Request::i()->verify_auth_code ) )
		{
			$_SESSION['verifyAuthError'] = NULL;
			try
			{
				/* Verify with the API */
				$response = static::_api( "Services/" . Settings::i()->verify_service_sid . "/VerificationCheck", 'post', array(
					'VerificationSid' => $mfaDetails['verify']['pendingId'],
					'Code' => Request::i()->verify_auth_code
				) );

				if( $response['status'] == 'approved' )
				{
					$mfaDetails['verify'] = array( 'id' => $response['to'], 'setup' => true );
					$member->mfa_details = $mfaDetails;
					$member->save();

					$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => TRUE ) );

					return true;
				}
				else
				{
					throw new VerifyException( 'verify_mfa_invalid_code' );
				}
			}
			catch ( \Exception $e )
			{
				Log::log( $e, 'verify' );
				$_SESSION['verifyAuthError'] = $e->getMessage();
				return false;
			}
		}

		/* Otherwise we need to generate an ID */
		elseif ( Request::i()->phoneNumber )
		{
			/* Do we need to wait a while? */
			if ( isset( $mfaDetails['verify'] ) and isset( $mfaDetails['verify']['changeAttempts'] ) and $mfaDetails['verify']['changeAttempts'] >= Settings::i()->verify_setup_tries )
			{
				return false;
			}

			/* Reformat the phone number to E.164 */
			$countryCode = substr( Request::i()->countryCode, strpos( Request::i()->countryCode, "-" ) + 1 );
			$phoneNumber = "+" . $countryCode . Request::i()->phoneNumber;

			/* Call verify */
			$availableMethods = explode( ',', Settings::i()->verify_setup );
			$method = ( isset( Request::i()->method ) and in_array( Request::i()->method, $availableMethods ) ) ? Request::i()->method : array_shift( $availableMethods );
			$_SESSION['verifyConfigureError'] = NULL;

			$channel = ( Request::i()->method == 'phone' ) ? 'call' : Request::i()->method;

			try
			{
				$response = static::_api( "Services/" . Settings::i()->verify_service_sid . "/Verifications", 'post', array(
					'To' => $phoneNumber,
					'Channel' => $channel
				) );
			}
			catch ( \Exception $e )
			{
				Log::log( $e, 'verify' );
				$_SESSION['verifyConfigureError'] = $e->getMessage();
				return false;
			}

			/* Log the details */
			$mfaDetails['verify']['pendingId'] = $response['sid'];
			if ( !isset( $mfaDetails['verify']['changeAttempts'] ) )
			{
				$mfaDetails['verify']['changeAttempts'] = 1;
			}
			else
			{
				$mfaDetails['verify']['changeAttempts']++;
			}
			$mfaDetails['verify']['lastChangeAttempt'] = time();
			if ( !isset( $mfaDetails['verify']['setup'] ) )
			{
				$mfaDetails['verify']['setup'] = false;
			}
			$mfaDetails['verify']['setupMethod'] = $method;
			$member->mfa_details = $mfaDetails;
			$member->save();

			return false;
		}

		return false;
	}

	/**
	 * Get the form for a member to authenticate
	 *
	 * @param Member $member The member
	 * @param Url $url URL for page
	 * @return    string
	 */
	public function authenticationScreen( Member $member, Url $url ): string
	{
		$mfaDetails = $member->mfa_details;
		$availableMethods = explode( ',', Settings::i()->verify_method );

		/* If we sent a code, but it was less than one minute ago, log a failure and reset */
		if ( isset( $mfaDetails['verify']['sent'] ) and $mfaDetails['verify']['sent']['time'] < ( time() - 60 ) and !isset( Request::i()->verify_auth_code ) )
		{
			unset( $mfaDetails['verify']['sent'] );
			$member->mfa_details = $mfaDetails;
			$member->failed_mfa_attempts++;
			$member->save();
		}

		/* If text message is the only available option, or we have chosen that option, do that... */
		$selectedMethod = Request::i()->verify_method ?? NULL;
		if( $selectedMethod === NULL )
		{
			if( isset( Request::i()->verify_auth_code ) and isset( $mfaDetails['verify']['sent'] ) )
			{
				$selectedMethod = $mfaDetails['verify']['sent']['method'];
			}
			elseif( count( $availableMethods ) == 1 )
			{
				$selectedMethod = $availableMethods[0];
			}
		}

		/* Send the text if we haven't already */
		if ( !isset( $mfaDetails['verify']['sent'] ) )
		{
			try
			{
				$channel = $selectedMethod == 'phone' ? 'call' : $selectedMethod;
				$response = static::_api( "Services/" . Settings::i()->verify_service_sid . "/Verifications", 'post', array(
					'To' => $mfaDetails['verify']['id'],
					'Channel' => $channel
				) );

				/* Update details */
				$mfaDetails['verify']['sent'] = array( 'method' => $selectedMethod, 'time' => time() );
				$member->mfa_details = $mfaDetails;
				$member->save();
			}
			catch ( \Exception $e )
			{
				Log::log( $e, 'verify' );
				return Theme::i()->getTemplate( 'login', 'core', 'global' )->verifyError( $e->getMessage() );
			}
		}

		/* Show screen */
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->verifyAuthenticate( $selectedMethod ?: 'choose', ( isset( $mfaDetails['verify']['sent'] ) and $mfaDetails['verify']['sent']['method'] == $selectedMethod ), $_SESSION['verifyAuthError'] ?? 'verify_error', $mfaDetails['verify']['setup'] ?? FALSE, $availableMethods, $url );
	}

	/**
	 * Submit authentication screen. Return TRUE if was accepted
	 *
	 * @param Member $member The member
	 * @return    bool
	 */
	public function authenticationScreenSubmit( Member $member ): bool
	{
		$mfaDetails = $member->mfa_details;

		$_SESSION['verifyAuthError'] = NULL;

		try
		{
			if ( isset( Request::i()->verify_auth_code ) )
			{
				/* Verify with the API */
				$response = static::_api( "Services/" . Settings::i()->verify_service_sid . "/VerificationCheck", 'post', array(
					'To' => $mfaDetails['verify']['id'],
					'Code' => Request::i()->verify_auth_code
				) );

				if( $response['status'] == 'approved' )
				{
					$member->mfa_details = $mfaDetails;
					$member->save();

					return true;
				}
				else
				{
					throw new VerifyException( 'verify_mfa_invalid_code' );
				}
			}
			else
			{
				return false;
			}
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'verify' );
			$_SESSION['verifyAuthError'] = $e->getMessage();
			return false;
		}
	}

	/**
	 * Toggle
	 *
	 * @param	bool	$enabled	On/Off
	 * @return	void
	 */
	public function toggle( bool $enabled ) : void
	{
		if ( $enabled )
		{
			static::verifyApiKeys( Settings::i()->verify_sid, Settings::i()->verify_token );
		}

		Settings::i()->changeValues( array( 'verify_enabled' => $enabled ) );
	}

	/**
	 * ACP Settings
	 *
	 * @return    string
	 */
	public function acpSettings(): string
	{
		$form = new Form;

		$form->add( new Text( 'verify_sid', Settings::i()->verify_sid, TRUE, array(), NULL, NULL, Member::loggedIn()->language()->addToStack( 'verify_sid_suffix' ) ) );
		$form->add( new Text( 'verify_token', Settings::i()->verify_token, TRUE ) );
		$form->add( new CheckboxSet( 'verify_groups', Settings::i()->verify_groups == '*' ? '*' : explode( ',', Settings::i()->verify_groups ), FALSE, array(
			'multiple'		=> TRUE,
			'options'		=> array_combine( array_keys( Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, Group::groups() ) ),
			'unlimited'		=> '*',
			'unlimitedLang'	=> 'everyone',
			'impliedUnlimited' => TRUE
		) ) );

		$form->addHeader('verify_setup_header');
		$form->add( new CheckboxSet( 'verify_setup', explode( ',', Settings::i()->verify_setup ), TRUE, array( 'options' => array(
			'sms'			=> 'verify_method_sms',
			'phone'			=> 'verify_method_phone',
			'whatsapp'		=> 'verify_method_whatsapp'
		) ) ) );
		$form->add( new Custom( 'verify_setup_protection', array( Settings::i()->verify_setup_tries, Settings::i()->verify_setup_lockout ), FALSE, array(
			'getHtml' => function( $field ) {
				return Theme::i()->getTemplate('settings')->verifySetupProtection( $field->value );
			}
		) ) );

		$form->addHeader('verify_authenticate_header');
		$form->add( new CheckboxSet( 'verify_method', explode( ',', Settings::i()->verify_method ), TRUE, array(
			'options' => array(
				'sms'			=> 'verify_method_sms',
				'phone'			=> 'verify_method_phone',
				'whatsapp'		=> 'verify_method_whatsapp'
			)
		) ) );

		if ( $values = $form->values() )
		{
			try
			{
				$data = static::verifyApiKeys( $values['verify_sid'], $values['verify_token'] );

				/* If we do not have any services yet, create one */
				if( !count( $data['services'] ) )
				{
					/* Temporarily set the tokens so that we can properly make the next call */
					Settings::i()->verify_sid = $values['verify_sid'];
					Settings::i()->verify_token = $values['verify_token'];
					$service = static::_api( "Services", 'post', array(
						'FriendlyName' => 'IPS MFA'
					) );

					$values['verify_service_sid'] = $service['sid'];
				}
				else
				{
					$values['verify_service_sid'] = $data['services'][0]['sid'];
				}

				$values['verify_groups'] = ( $values['verify_groups'] == '*' ) ? '*' : implode( ',', $values['verify_groups'] );
				$values['verify_setup'] = isset( $values['verify_setup'] ) ? implode( ',', $values['verify_setup'] ) : '';
				$values['verify_setup_tries'] = $values['verify_setup_protection'][0];
				$values['verify_setup_lockout'] = $values['verify_setup_protection'][1];
				unset( $values['verify_setup_protection'] );
				$values['verify_method'] = isset( $values['verify_method'] ) ? implode( ',', $values['verify_method'] ) : '';
				$form->saveAsSettings( $values );

				Session::i()->log( 'acplogs__mfa_handler_enabled', array( "mfa_verify_title" => TRUE ) );
				Output::i()->redirect( Url::internal( 'app=core&module=settings&controller=mfa' ), 'saved' );
			}
			catch( DomainException $e )
			{
				$form->error = $e->getMessage();
			}
		}

		return (string) $form;
	}

	/**
	 * Verify API Keys
	 *
	 * @param	string	$sid	The Verify SID
	 * @param   string  $token	The Verify Auth Token
	 * @return	array
	 * @throws	DomainException
	 */
	public static function verifyApiKeys( string $sid, string $token ) : array
	{
		try
		{
			$response = Url::external("https://verify.twilio.com/v2/Services" )
				->request()
				->login( $sid, $token )
				->get()
				->decodeJson();

			if( !isset( $response['services'] ) )
			{
				throw new DomainException( $response['message'] );
			}

			return $response;
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new DomainException( $e->getMessage() );
		}
	}

	/**
	 * If member has configured this handler, disable it
	 *
	 * @param Member $member The member
	 * @return    void
	 */
	public function disableHandlerForMember( Member $member ): void
	{
		$mfaDetails = $member->mfa_details;

		if ( isset( $mfaDetails['verify'] ) )
		{
			unset( $mfaDetails['verify'] );
			$member->mfa_details = $mfaDetails;
			$member->save();

			/* Log MFA Disable */
			$member->logHistory( 'core', 'mfa', array( 'handler' => $this->key, 'enable' => FALSE ) );
		}
	}

	/**
	 * Make API Call
	 *
	 * @param	string		$endpoint	The endpoint to call
	 * @param	string		$method		'get' or 'post'
	 * @param	array|null	$data		Post data or additional query string parameters
	 * @return	array
	 * @throws VerifyException
	 */
	protected static function _api( string $endpoint, string $method='get', ?array $data=NULL ) : array
	{
		$url = Url::external("https://verify.twilio.com/v2/{$endpoint}" );

		if ( $method == 'get' )
		{
			$response = $url->setQueryString( $data )
				->request()
				->login( Settings::i()->verify_sid, Settings::i()->verify_token )
				->get();
		}
		else
		{
			$response = $url->request()
				->login( Settings::i()->verify_sid, Settings::i()->verify_token )
				->post( $data );

		}

		$response = $response->decodeJson();

		if( isset( $response['code'] ) AND isset( $response['message'] ) )
		{
			throw new VerifyException( $response['message'], $response['code'] );
		}

		return $response;
	}
}