<?php
/**
 * @brief		Abstract OAuth2 Login Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		31 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login\Handler;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Http\Response;
use IPS\Http\Url;
use IPS\Log;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use LogicException;
use RuntimeException;
use UnderflowException;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract OAuth2 Login Handler
 */
abstract class OAuth2 extends Handler
{	
	/* !Login Handler: Basics */
	
	/**
	 * @brief	Any additional scopes to authenticate with
	 */
	public mixed $additionalScopes = NULL;

	/**
	 * @brief	Does this handler support PKCE?
	 */
	public bool $pkceSupported = TRUE;
	
	/**
	 * Get type
	 *
	 * @return	int
	 */
	public function type(): int
	{
		if ( $this->grantType() === 'password' )
		{
			return Login::TYPE_USERNAME_PASSWORD;
		}
		else
		{
			return Login::TYPE_BUTTON;
		}
	}
	
	/**
	 * ACP Settings Form
	 *
	 * @return	array	List of settings to save - settings will be stored to core_login_methods.login_settings DB field
	 * @code
	 	return array( 'savekey'	=> new \IPS\Helpers\Form\[Type]( ... ), ... );
	 * @endcode
	 */
	public function acpForm(): array
	{				
		$return = array(
			array( 'login_handler_oauth_settings', Member::loggedIn()->language()->addToStack( static::getTitle() . '_info', FALSE, array( 'sprintf' => array( (string) $this->redirectionEndpoint() ) ) ) ),
			'client_id'		=> new Text( 'oauth_client_id', $this->settings['client_id'] ?? NULL, TRUE ),
			'client_secret'	=> new Text( 'oauth_client_client_secret', $this->settings['client_secret'] ?? NULL, NULL, array(), NULL, NULL, NULL, 'client_secret' ),
		);
				
		$return[] = 'account_management_settings';
		$return['show_in_ucp'] = new Radio( 'login_handler_show_in_ucp', $this->settings['show_in_ucp'] ?? 'always', FALSE, array(
			'options' => array(
				'always'		=> 'login_handler_show_in_ucp_always',
				'loggedin'		=> 'login_handler_show_in_ucp_loggedin',
				'disabled'		=> 'login_handler_show_in_ucp_disabled'
			),
		) );
		
		$nameChangesDisabled = array();
		if ( $forceNameHandler = static::handlerHasForceSync( 'name', $this ) )
		{
			$nameChangesDisabled[] = 'force';
			Member::loggedIn()->language()->words['login_update_changes_yes_name_desc'] = Member::loggedIn()->language()->addToStack( 'login_update_changes_yes_disabled', FALSE, array( 'sprintf' => $forceNameHandler->_title ) );
		}
		$return['update_name_changes'] = new Radio( 'login_update_name_changes', $this->settings['update_name_changes'] ?? 'disabled', FALSE, array( 'options' => array(
			'force'		=> 'login_update_changes_yes_name',
			'optional'	=> 'login_update_changes_optional',
			'disabled'	=> 'login_update_changes_no',
		), 'disabled' => $nameChangesDisabled ), NULL, NULL, NULL, 'login_update_name_changes_inc_optional' );
		
		$emailChangesDisabled = array();
		if ( $forceEmailHandler = static::handlerHasForceSync( 'email', $this ) )
		{
			$emailChangesDisabled[] = 'force';
			Member::loggedIn()->language()->words['login_update_changes_yes_email_desc'] = Member::loggedIn()->language()->addToStack( 'login_update_changes_yes_disabled', FALSE, array( 'sprintf' => $forceEmailHandler->_title ) );
		}
		$return['update_email_changes'] = new Radio( 'login_update_email_changes', $this->settings['update_email_changes'] ?? 'optional', FALSE, array( 'options' => array(
			'force'		=> 'login_update_changes_yes_email',
			'optional'	=> 'login_update_changes_optional',
			'disabled'	=> 'login_update_changes_no',
		), 'disabled' => $emailChangesDisabled ), NULL, NULL, NULL, 'login_update_email_changes_inc_optional' );
		
		return array_merge( $return, parent::acpForm() );		
	}
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	LogicException
	 */
	public function testSettings(): bool
	{
		parent::testSettings();
		
		try
		{
			/* Authorization Code / Implicit */
			if ( $this->grantType() === 'authorization_code' )
			{
				$response = $this->_authenticatedRequest( $this->tokenEndpoint(), array(
					'grant_type'	=> 'authorization_code',
					'code'			=> 'xxx',
					'redirect_uri'	=> (string) $this->redirectionEndpoint(),
				) )->decodeJson();
				
				if ( isset( $response['error'] ) and $response['error'] === 'invalid_client' )
				{
					throw new LogicException( Member::loggedIn()->language()->addToStack( 'oauth_setup_error_secret' ) );
				}
			}
			/* Password */
			elseif ( $this->grantType() === 'password' )
			{
				$response =  $this->_authenticatedRequest( $this->tokenEndpoint(), array(
					'grant_type'	=> 'password',
					'username'		=> 'username',
					'password'		=> 'password',
				) )->decodeJson();

				if ( !isset( $response['error'] ) or $response['error'] !== 'invalid_grant' )
				{
					throw new LogicException( Member::loggedIn()->language()->addToStack( 'oauth_setup_error_generic', FALSE, array( 'sprintf' => array( $response['error_description'] ?? NULL ) ) ) );
				}
			}
		}
		catch( \IPS\Http\Request\Exception $e )
		{
			throw new LogicException( Member::loggedIn()->language()->addToStack( 'oauth_setup_error_generic', FALSE, array( 'sprintf' => array( $e->getMessage() ) ) ) );
		}

		return TRUE;
	}
	
	/* !Button Authentication */
	
	use ButtonHandler;
		
	/**
	 * Authenticate
	 *
	 * @param	Login	$login		The login object
	 * @return	Member|null
	 * @throws	Exception
	 */
	public function authenticateButton( Login $login ): ?Member
	{
		/* If we have a code, process it */
		if ( $this->grantType() === 'authorization_code' and ( isset( Request::i()->code ) or isset( Request::i()->error ) ) )
		{
			return $this->_handleAuthorizationResponse( $login );
		}
		
		/* If we have a token, process that */
		elseif ( $this->grantType() === 'implicit' and ( isset( Request::i()->access_token ) or isset( Request::i()->error ) ) )
		{
			return $this->_handleAuthorizationResponse( $login );
		}
				
		/* Otherwise send them to the Authorization Endpoint */
		else
		{
			$data = array(
				'client_id'		=> $this->settings['client_id'],
				'response_type'	=> $this->grantType() === 'authorization_code' ? 'code' : 'token',
				'redirect_uri'	=> (string) $this->redirectionEndpoint(),
				'state'			=> $this->id . '-' . base64_encode( $login->url ) . '-' . Session::i()->csrfKey . '-' . Request::i()->ref,
			);
			
			if ( $this->grantType() === 'authorization_code' AND $this->pkceSupported === TRUE )
			{
				$codeChallenge = Login::generateRandomString( 128 );
				Request::i()->setCookie('codeVerifier', $codeChallenge, ( new DateTime )->add( new \DateInterval( 'PT10M' ) ) );
				$data['code_challenge'] = rtrim( strtr( base64_encode( pack( 'H*', hash( 'sha256', $codeChallenge ) ) ), '+/', '-_' ), '=' );
				$data['code_challenge_method'] = 'S256';
			}
			
			$target = $this->authorizationEndpoint( $login )->setQueryString( $data );
			
			if ( $scopes = $this->scopesToRequest( isset( Request::i()->scopes ) ? explode( ',', Request::i()->scopes ) : NULL ) )
			{
				$target = $target->setQueryString( 'scope', implode( ' ', $scopes ) );
			}
			
			Output::i()->redirect( $target );
		}
	}
	
	/* !Username/Password Authentication */
	
	use UsernamePasswordHandler;
	
	/**
	 * Authenticate
	 *
	 * @param	Login	$login				The login object
	 * @param string $usernameOrEmail	The username or email address provided by the user
	 * @param object $password			The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	Member
	 * @throws	Exception
	 */
	public function authenticateUsernamePassword( Login $login, string $usernameOrEmail, object $password ): Member
	{
		if( !$usernameOrEmail )
		{
			$member = NULL;

			$member = new Member;
			$member->email = $usernameOrEmail;

			throw new Exception( Member::loggedIn()->language()->addToStack( 'login_bad_username_or_password', FALSE ), Exception::NO_ACCOUNT, NULL, $member );
		}

		$data =  array(
			'grant_type'		=> 'password',
			'username'		=> $usernameOrEmail,
			'password'		=> (string) $password,
		);
		if ( $scopes = $this->scopesToRequest() )
		{
			$data['scope'] = implode( ' ', $scopes );
		}
		
		try
		{
			$accessToken =  $this->_authenticatedRequest( $this->tokenEndpoint(), $data )->decodeJson();
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'oauth' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
		
		if ( isset( $accessToken['access_token'] ) )
		{
			return $this->_processAccessToken( $login, $accessToken );
		}
		else
		{
			if ( isset( $accessToken['error'] ) and $accessToken['error'] === 'invalid_grant' )
			{
				$member = NULL;

				$member = new Member;
				$member->email = $usernameOrEmail;

				throw new Exception( Member::loggedIn()->language()->addToStack( 'login_bad_username_or_password', FALSE ), Exception::NO_ACCOUNT, NULL, $member );
			}
			
			Log::log( print_r( $accessToken, TRUE ), 'oauth' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
	}
	
	/**
	 * Authenticate
	 *
	 * @param	Member	$member		The member
	 * @param object $password	The plaintext password provided by the user, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	bool
	 */
	public function authenticatePasswordForMember( Member $member, object $password ): bool
	{
		try
		{
			$response =  $this->_authenticatedRequest( $this->tokenEndpoint(), array(
				'grant_type'	=> 'password',
				'username'		=> $member->email,
				'password'		=> (string) $password,
			) )->decodeJson();
			if ( isset( $response['access_token'] ) )
			{
				return TRUE;
			}
		}
		catch ( \Exception $e ) { }
		
		return FALSE;
	}
	
	/* !OAuth Authentication */
	
	const AUTHENTICATE_HEADER = 'header';
	const AUTHENTICATE_POST  = 'post';
	
	/**
	 * Should client credentials be sent as an "Authoriation" header, or as POST data?
	 *
	 * @return	string
	 */
	protected function _authenticationType(): string
	{
		return static::AUTHENTICATE_HEADER;
	}

	/**
	 * Send request authenticated with client credentials
	 *
	 * @param Url $url The URL
	 * @param array $data
	 * @return    Response
	 */
	protected function _authenticatedRequest( Url $url, array $data ): Response
	{
		$request = $url->request();
		
		if ( $this->_authenticationType() === static::AUTHENTICATE_HEADER )
		{
			$request = $request->login( $this->settings['client_id'], $this->clientSecret() );
		}
		else
		{
			$data['client_id'] = $this->settings['client_id'];
			$data['client_secret'] = $this->clientSecret();
		}
		
		return $request->post( $data );
	}
	
	/**
	 * Handle authorization response
	 *
	 * @param	Login	$login		The login object
	 * @return	Member|null
	 * @throws	Exception
	 */
	protected function _handleAuthorizationResponse( Login $login ): ?Member
	{
		/* Did we get an error? */
		if ( isset( Request::i()->error ) )
		{
			if ( Request::i()->error === 'access_denied' )
			{
				return NULL;
			}
			else
			{
				Log::log( print_r( $_GET, TRUE ), 'oauth' );
				throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
			}
		}
		
		/* If we have a code, swap it for an access token, otherwise, decode what we have */
		if ( isset( Request::i()->code ) )
		{
			$accessToken = $this->_exchangeAuthorizationCodeForAccessToken( Request::i()->code );
		}
		else
		{
			$accessToken = array(
				'access_token'	=> Request::i()->access_token,
				'token_type'	=> isset( Request::i()->token_type ) ? Request::i()->token_type : 'bearer'
			);
			if ( isset( Request::i()->expires_in ) )
			{
				$accessToken['expires_in'] = Request::i()->expires_in;
			}
		}
		
		/* Process */
		return $this->_processAccessToken( $login, $accessToken );
	}
	
	/**
	 * Process an Access Token
	 *
	 * @param	Login	$login			The login object
	 * @param array $accessToken	Access Token
	 * @return	Member
	 * @throws	Exception
	 */
	protected function _processAccessToken( Login $login, array $accessToken ): Member
	{		
		/* Get user id */
		try
		{
			$userId = $this->authenticatedUserId( $accessToken['access_token'] );
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'oauth' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
		
		/* What scopes did we get? */
		if ( isset( $accessToken['scope'] ) )
		{
			$scope = explode( ' ', $accessToken['scope'] );
		}
		else
		{
			$scope = $this->scopesIssued( $accessToken['access_token'] );
		}
				
		/* Has this user signed in with this service before? */
		try
		{
			$oauthAccess = Db::i()->select( '*', 'core_login_links', array( 'token_login_method=? AND token_identifier=?', $this->id, $userId ) )->first();
			$member = Member::load( $oauthAccess['token_member'] );
			
			/* If the user never finished the linking process, or the account has been deleted, discard this access token */
			if ( !$oauthAccess['token_linked'] or !$member->member_id )
			{
				Db::i()->delete( 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $oauthAccess['token_member'] ) );
				throw new UnderflowException;
			}
			
			/* Otherwise, update our token without replacing values already set but not reset in this request... */
			$update = array( 
				'token_access_token'	=> $accessToken['access_token'],
				'token_expires'			=> ( isset( $accessToken['expires_in'] ) ) ? ( time() + intval( $accessToken['expires_in'] ) ) : NULL
			);

			if( isset( $accessToken['refresh_token'] ) )
			{
				$update['token_refresh_token'] = $accessToken['refresh_token'];
			}

			if( $scope )
			{
				$update['token_scope'] = json_encode( $scope );
			}

			Db::i()->update( 'core_login_links', $update, array( 'token_login_method=? AND token_member=?', $this->id, $oauthAccess['token_member'] ) );
			
			/* ... and return the member object */
			return $member;
		}
		/* No, create or link the account */
		catch ( UnderflowException $e )
		{
			/* Get the username + email */
			$name = NULL;
			try
			{
				$name = $this->authenticatedUserName( $accessToken['access_token'] );
			}
			catch ( \Exception $e ) {}
			
			$email = NULL;
			try
			{
				$email = $this->authenticatedEmail( $accessToken['access_token'] );
			}
			catch ( \Exception $e ) {}
			
			try
			{
				if ( $login->type === Login::LOGIN_UCP )
				{
					$exception = new Exception( 'generic_error', Exception::MERGE_SOCIAL_ACCOUNT );
					$exception->handler = $this;
					$exception->member = $login->reauthenticateAs;
					throw $exception;
				}
				
				$member = $this->createAccount( $name, $email );
				
				Db::i()->replace( 'core_login_links', array(
					'token_login_method'	=> $this->id,
					'token_member'			=> $member->member_id,
					'token_identifier'		=> $userId,
					'token_linked'			=> 1,
					'token_access_token'	=> $accessToken['access_token'],
					'token_expires'			=> isset( $accessToken['expires_in'] ) ? ( time() + intval( $accessToken['expires_in'] ) ) : NULL,
					'token_refresh_token'	=> $accessToken['refresh_token'] ?? NULL,
					'token_scope'			=> $scope ? json_encode( $scope ) : NULL,
				) );
				
				$member->logHistory( 'core', 'social_account', array(
					'service'		=> static::getTitle(),
					'handler'		=> $this->id,
					'account_id'	=> $userId,
					'account_name'	=> $name,
					'linked'		=> TRUE,
					'registered'	=> TRUE
				) );
				
				if ( $syncOptions = $this->syncOptions( $member, TRUE ) )
				{
					$profileSync = array();
					foreach ( $syncOptions as $option )
					{
						$profileSync[ $option ] = array( 'handler' => $this->id, 'ref' => NULL, 'error' => NULL );
					}
					$member->profilesync = $profileSync;
					$member->save();
				}
				
				return $member;
			}
			catch ( Exception $exception )
			{
				if ( $exception->getCode() === Exception::MERGE_SOCIAL_ACCOUNT )
				{
					try
					{
						$identifier = Db::i()->select( 'token_identifier', 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $exception->member->member_id ) )->first();

						if( $identifier != $userId )
						{
							$exception->setCode( Exception::LOCAL_ACCOUNT_ALREADY_MERGED );
							throw $exception;
						}
					}
					catch( UnderflowException $e )
					{
						Db::i()->replace( 'core_login_links', array(
							'token_login_method'	=> $this->id,
							'token_member'			=> $exception->member->member_id,
							'token_identifier'		=> $userId,
							'token_linked'			=> 0,
							'token_access_token'	=> $accessToken['access_token'],
							'token_expires'			=> isset( $accessToken['expires_in'] ) ? ( time() + intval( $accessToken['expires_in'] ) ) : NULL,
							'token_refresh_token'	=> $accessToken['refresh_token'] ?? NULL,
							'token_scope'			=> $scope ? json_encode( $scope ) : NULL,
						) );
					}
				}
				
				throw $exception;
			}
		}
	}
	
	/**
	 * Exchange authorization code for access token
	 *
	 * @param string $code	Authorization code
	 * @return	array
	 * @throws	Exception
	 */
	protected function _exchangeAuthorizationCodeForAccessToken( string $code ): array
	{
		/* Make the request */
		$data = NULL;
		$response = array();
		try
		{
			$post = array(
				'grant_type'	=> 'authorization_code',
				'code'			=> $code,
				'redirect_uri'	=> (string) $this->redirectionEndpoint(),
			);

			if( $this->pkceSupported === TRUE )
			{
				$post['code_verifier'] = Request::i()->cookie['codeVerifier'] ?: NULL;
			}

			$data = $this->_authenticatedRequest( $this->tokenEndpoint(), $post );

			$response = $data->decodeJson();
			Request::i()->setCookie('codeVerifier', NULL );
		}
		catch( RuntimeException $e )
		{
			Log::log( var_export( $data, true ), 'oauth' );
		}
		
		/* Check for any errors */
		if ( isset( $response['error'] ) or !isset( $response['access_token'] ) or ( isset( $response['token_type'] ) and mb_strtolower( $response['token_type'] ) !== 'bearer' ) )
		{
			Log::log( print_r( $response, TRUE ), 'oauth' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}		
		
		/* Return */
		return $response;
	}
	
	/**
	 * Get link
	 *
	 * @param	Member	$member	Member
	 * @return	array|null
	 */
	protected function _link( Member $member ): ?array
	{
		$link = parent::_link( $member );

		if ( $link and $link['token_expires'] and $link['token_expires'] < time() and $link['token_refresh_token'] )
		{
			try
			{
				$newAccessToken =  $this->_authenticatedRequest( $this->tokenEndpoint(), array(
					'grant_type'	=> 'refresh_token',
					'refresh_token'	=> $link['token_refresh_token'],
				) )->decodeJson();
				
				if ( isset( $newAccessToken['error'] ) or !isset( $newAccessToken['access_token'] ) or ( isset( $newAccessToken['token_type'] ) and mb_strtolower( $newAccessToken['token_type'] ) !== 'bearer' ) )
				{
					if( !isset( $newAccessToken['error'] ) OR $newAccessToken['error'] != 'invalid_grant' )
					{
						Log::log( print_r( $newAccessToken, TRUE ), 'oauth' );
					}

					Db::i()->update( 'core_login_links', array( 'token_refresh_token' => NULL ), array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) );
					return $link;
				}
				
				$update = array(
					'token_access_token' => $newAccessToken['access_token']
				);
				if ( isset( $newAccessToken['expires_in'] ) )
				{
					$update['token_expires'] = ( time() + $newAccessToken['expires_in'] );
				}
				if ( isset( $newAccessToken['refresh_token'] ) )
				{
					$update['token_refresh_token'] = $newAccessToken['refresh_token'];
				}
				
				foreach ( $update as $k => $v )
				{
					$link[ $k ] = $v;
					$this->_cachedLinks[ $member->member_id ][ $k ] = $v;
				}
				Db::i()->update( 'core_login_links', $update, array( 'token_login_method=? AND token_member=?', $this->id, $member->member_id ) );
			}
			catch ( \Exception $e )
			{
				Log::log( $e, 'oauth' );
			}
		}
		
		return $link;
	}
		
	/* !OAuth Abstract */
	
	/**
	 * Grant Type
	 *
	 * @return	string
	 */
	abstract protected function grantType(): string;
	
	/**
	 * Get scopes to request
	 *
	 * @param	array|NULL	$additional	Any additional scopes to request
	 * @return	array
	 */
	protected function scopesToRequest( array $additional=NULL ): array
	{
		return array();
	}
	
	/**
	 * Scopes Issued
	 *
	 * @param	string		$accessToken	Access Token
	 * @return	array|NULL
	 */
	public function scopesIssued( string $accessToken ): ?array
	{
		return $this->scopesToRequest(); // Unless the individual handler overrides this, we'll just assume it's given us what we asked for (which is how the OAuth spec says you're supposed to do it anyway)
	}

	/**
	 * Authorized scopes
	 *
	 * @param Member $member
	 * @return    array|NULL
	 */
	public function authorizedScopes( Member $member ): ?array
	{
		if ( !( $link = $this->_link( $member ) ) )
		{
			return NULL;
		}
						
		return $link['token_scope'] ? json_decode( $link['token_scope'] ) : NULL;
	}
	
	/**
	 * Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	Url
	 */
	abstract protected function authorizationEndpoint( Login $login ): Url;
	
	/**
	 * Token Endpoint
	 *
	 * @return	Url
	 */
	abstract protected function tokenEndpoint(): Url;
	
	/**
	 * Redirection Endpoint
	 *
	 * @return	Url
	 */
	protected function redirectionEndpoint(): Url
	{
		return Url::internal( 'oauth/callback/', 'none' );
	}
	
	/**
	 * Get authenticated user's identifier (may not be a number)
	 *
	 * @param	string	$accessToken	Access Token
	 * @return	string|null
	 */
	abstract protected function authenticatedUserId( string $accessToken ): ?string;
	
	/**
	 * Get authenticated user's username
	 * May return NULL if server doesn't support this
	 *
	 * @param	string	$accessToken	Access Token
	 * @return	string|NULL
	 */
	protected function authenticatedUserName( string $accessToken ): ?string
	{
		return NULL;
	}
	
	/**
	 * Get authenticated user's email address
	 * May return NULL if server doesn't support this
	 *
	 * @param	string	$accessToken	Access Token
	 * @return	string|NULL
	 */
	protected function authenticatedEmail( string $accessToken ): ?string
	{
		return NULL;
	}
	
	/**
	 * Get user's identifier (may not be a number)
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userId( Member $member ): ?string
	{
		if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
		{
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedUserId( $link['token_access_token'] );
	}
	
	/**
	 * Get user's profile name
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfileName( Member $member ): ?string
	{
		if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
		{
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedUserName( $link['token_access_token'] );
	}
	
	/**
	 * Get user's email address
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userEmail( Member $member ): ?string
	{
		if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
		{
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedEmail( $link['token_access_token'] );
	}
	
	/* !UCP */
	
	/**
	 * Show in Account Settings?
	 *
	 * @param	Member|NULL	$member	The member, or NULL for if it should show generally
	 * @return	bool
	 */
	public function showInUcp( Member $member = NULL ): bool
	{
		$return = parent::showInUcp( $member );

		if ( $return and !isset( $this->settings['show_in_ucp'] ) ) // Default to showing
		{
			return TRUE;
		}
		return $return;
	}


	/**
	 * Has any sync options
	 *
	 * @return	bool
	 */
	public function hasSyncOptions(): bool
	{
		return TRUE;
	}
	
	/**
	 * Client Secret
	 *
	 * @return	string | NULL
	 */
	public function clientSecret() : ?string
	{
		return $this->settings['client_secret'] ?? NULL;
	}
	
	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param	array	$values	Values from the form
	 * @return    mixed
	 */
	public function saveForm( array $values ): mixed
	{
        /* If we are prompting users we need to disable force syncing */
		if( isset( $values['login_settings']['real_name'] ) AND $values['login_settings']['real_name'] == 0 )
		{
			$values['login_settings']['update_name_changes'] = "disabled";
		}
		
		return parent::saveForm( $values );
	}	
}