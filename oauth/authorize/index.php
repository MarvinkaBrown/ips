<?php
/**
 * @brief		OAuth Server Authorize Endpoint
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Apr 2017
 */

use IPS\Api\OAuthClient;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\External;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Handler\OAuth2\InitException;
use IPS\Member;
use IPS\Member\Device;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Session\Front;
use IPS\Settings;
use IPS\Theme;
use const IPS\DEBUG_OAUTH_REDIRECTS;
use const IPS\OAUTH_REQUIRES_HTTPS;

define('REPORT_EXCEPTIONS', TRUE);
require '../../init.php';

class oAuthServerAuthorizationRequest
{
	/**
	 * @brief	Client
	 */
	protected $client;
	
	/**
	 * @brief	Redirect URI to use
	 */
	protected $redirectUri;
	
	/**
	 * @brief	Redirect URI provided
	 */
	protected $providedRedirectUri;
	
	/**
	 * @brief	State
	 */
	protected $state;
	
	/**
	 * @brief	Response Type
	 */
	protected $responseType;
	
	/**
	 * @brief	Scope
	 */
	protected $scope = array();
	
	/**
	 * @brief	Code Challenge
	 */
	protected $codeChallenge;
	
	/**
	 * @brief	Code Challenge Method
	 */
	protected $codeChallengeMethod;
	
	/**
	 * Init
	 *
	 * @param	string		$clientId				Client ID
	 * @param	string		$redirectUri				Redirect URI, if provided
	 * @param	string|NULL	$state					The state, if provided
	 * @param	string|NULL	$codeChallenge			The code challenge, if provided
	 * @param	string|NULL	$codeChallengeMethod	The code challenge method, if provided
	 * @return	void
	 */
	public static function init( $clientId, $redirectUri = NULL, $state = NULL, $codeChallenge = NULL, $codeChallengeMethod = NULL )
	{
		$obj = new static;
		
		/* Get the client */
		try
		{
			$obj->client = OAuthClient::load( $clientId );
			if ( !$obj->client->enabled )
			{
				throw new OutOfRangeException;
			}
		}
		catch (OutOfRangeException $e )
		{
			throw new InitException('oauth_err_invalid_client');
		}
		
		/* Set the Redirect URI */
		$allowedRedirectUris = json_decode( $obj->client->redirect_uris );

		if( defined('\IPS\DEBUG_OAUTH_REDIRECTS') )
		{
			$allowedRedirectUris = array_merge( $allowedRedirectUris, DEBUG_OAUTH_REDIRECTS );
		}

		if ( $redirectUri )
		{
			if ( !in_array( $redirectUri, $allowedRedirectUris ) )
			{
				throw new InitException('oauth_err_invalid_redirect_uri');
			}
			else
			{
				$obj->redirectUri = Url::external( $redirectUri );
			}
		}
		elseif ( count( $allowedRedirectUris ) === 1 )
		{
			$obj->redirectUri = Url::external( array_shift( $allowedRedirectUris ) );
		}
		else
		{
			throw new InitException('oauth_err_invalid_redirect_uri');
		}
		$obj->providedRedirectUri = $redirectUri;
		
		/* Set the state, if appliable */
		if ( $state )
		{
			$obj->state = $state;
		}
		
		/* Set code challenge and method if applicable */
		if ( $codeChallenge )
		{
			$obj->codeChallenge = $codeChallenge;
			$obj->codeChallengeMethod = $codeChallengeMethod;
		}
		
		return $obj;
	}
	
	/**
	 * Validate the request is valid
	 *
	 * @return	void
	 * @throws	\IPS\Login\Handler\OAuth2\Exception
	 */
	public function validate()
	{
		if ( ( $this->client->pkce !== 'none' and !$this->codeChallenge ) or ( $this->client->pkce === 'S256' and $this->codeChallengeMethod !== 'S256' ) )
		{
			throw new \IPS\Login\Handler\OAuth2\Exception( 'invalid_request', $this->codeChallenge ? "transform algorithm not supported" : "code challenge required" );
		}
	}
	
	/**
	 * Set the response type
	 *
	 * @param	string	$responseType	The response type
	 * @return	void
	 */
	public function setResponseType( $responseType )
	{
		if ( $responseType === 'code' )
		{
			if ( !in_array( 'authorization_code', explode( ',', $this->client->grant_types ) ) )
			{
				throw new \IPS\Login\Handler\OAuth2\Exception('unsupported_response_type');
			}
		}
		elseif ( $responseType === 'token' )
		{
			if ( !in_array( 'implicit', explode( ',', $this->client->grant_types ) ) )
			{
				throw new \IPS\Login\Handler\OAuth2\Exception('unsupported_response_type');
			}
		}
		else
		{
			throw new \IPS\Login\Handler\OAuth2\Exception( 'invalid_request', "response_type parameter is required" );
		}
		
		$this->responseType = $responseType;
	}
	
	/**
	 * Set the requested scopes
	 *
	 * @param	string	$scope	Scopes
	 * @return	void
	 */
	public function setScope( $scope )
	{
		$availableScopes = json_decode( $this->client->scopes, TRUE );
		$scopes = explode( ' ', $scope );
		foreach ( $scopes as $requestedScope )
		{
			if ( !array_key_exists( $requestedScope, $availableScopes ) )
			{
				throw new \IPS\Login\Handler\OAuth2\Exception('invalid_scope');
			}
		}
		$this->scope = $scopes;
	}
	
	/**
	 * Get URL to redirect the user back to the client after successful authorization
	 *
	 * @param	Member	$member					The member
	 * @param	array		$scopes					The authorized scopes
	 * @return	void
	 */
	public function authorized( Member $member, $scopes )
	{
		$scopes = $this->client->choose_scopes ? $scopes : $this->scope;
		$device = Device::loadOrCreate( $member );
		
		if ( $this->responseType === 'code' )
		{
			do
			{
				$authorizationCode = Login::generateRandomString( 64 );
			}
			while ( Db::i()->select( 'COUNT(*)', 'core_oauth_server_authorization_codes', array( 'client_id=? AND code=?', $this->client->client_id, $authorizationCode ) )->first() );
			
			Db::i()->insert( 'core_oauth_server_authorization_codes', array(
				'client_id'				=> $this->client->client_id,
				'redirect_uri'			=> $this->providedRedirectUri ?: NULL,
				'member_id'				=> $member->member_id,
				'expires'				=> time() + 60,
				'code'					=> $authorizationCode,
				'scope'					=> $scopes ? json_encode( $scopes ) : NULL,
				'code_challenge'		=> $this->codeChallenge,
				'code_challenge_method'	=> $this->codeChallengeMethod,
				'user_agent'			=> isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : NULL,
				'device_key'			=> $device ? $device->device_key : NULL
			) );
			
			return $this->redirect( array( 'code' => $authorizationCode ) );
		}
		else
		{
			$accessToken = $this->client->generateAccessToken( $member, $scopes, 'implicit', TRUE, NULL, isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : NULL, isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : NULL, $device );
			
			$response = array( 'access_token' => $accessToken['access_token'], 'token_type' => 'bearer' );
			if ( $accessToken['access_token_expires'] )
			{
				$response['expires_in'] = $accessToken['access_token_expires'] - time();
			}
			
			return $this->redirect( $response );
		}
	}
	
	/**
	 * Get URL to redirect the user back to the client
	 *
	 * @param	array	$response	Response parameters
	 * @return	void
	 */
	public function redirect( array $response )
	{
		if ( $this->state )
		{
			$response['state'] = $this->state;
		}
				
		if ( $this->responseType === 'token' )
		{
			return $this->redirectUri->setFragment( http_build_query( $response, '', '&' ) );
		}
		else
		{
			return $this->redirectUri->setQueryString( $response );
		}
	}
	
	/**
	 * Do we need to be prompted?
	 *
	 * @param	string	$requestedPromptType	Requested prompt type, if provided
	 * @return	bool|array
	 */
	public function promptRequired( $requestedPromptType )
	{
		/* If we're not logged in, we definitely do, unless we cancelled */
		if ( !Member::loggedIn()->member_id and ( !isset( Request::i()->allow ) or Request::i()->allow ) )
		{
			return TRUE;
		}
		
		/* If we're banned or validating, we'll show those screens instead */
		if ( Member::loggedIn()->isBanned() or Member::loggedIn()->members_bitoptions['validating'] )
		{
			return TRUE;
		}

		/* If our account is incomplete (e.g. no name or no email), show that screen instead */
		if( Member::loggedIn()->member_id and !( Member::loggedIn()->real_name and Member::loggedIn()->email ) )
		{
			return TRUE;
		}
		
		/* Have we gone through it already? */
		if ( isset( Request::i()->allow ) and Login::compareHashes( (string) Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
		{
			if ( !Request::i()->allow )
			{
				throw new \IPS\Login\Handler\OAuth2\Exception('access_denied');
			}
			return FALSE;
		}
		
		/* Does the client require it? */
		if ( !in_array( $this->client->prompt, array( 'none', 'automatic' ) ) )
		{
			return TRUE;
		}
				
		/* Did we request it? */
		if ( $requestedPromptType === 'login' or $requestedPromptType === 'reauthorize' )
		{
			return TRUE;
		}
				
		/* Are we always bypassing? */
		if ( $this->client->prompt === 'none' )
		{
			return FALSE;
		}
		
		/* Do we already have an access token with these scopes? */
		$accessToken = $this->client->getAccessToken( Member::loggedIn(), $this->scope );
		if ( $accessToken )
		{
			Request::i()->grantedScope = $accessToken['scope'] ? array_combine( json_decode( $accessToken['scope'], TRUE ), array_fill( 0, count( json_decode( $accessToken['scope'], TRUE ) ), TRUE ) ) : array();
			return FALSE;
		}
				
		/* No? We need a new token */
		return TRUE;
	}
	
	/**
	 * Show authorization form
	 *
	 * @param	string		$requestedPromptType	Requested prompt type, if provided
	 * @param	bool		$loggedIn				Has the user logged in?
	 * @return	void
	 */
	public function prompt( $requestedPromptType, $loggedIn )
	{
		/* If we're banned or validating, we'll show those screens instead */
		if ( Member::loggedIn()->member_id and Member::loggedIn()->isBanned() )
		{
			Output::i()->showBanned();
			exit;
		}
		elseif ( Member::loggedIn()->members_bitoptions['validating'] )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ) );
		}
		
		/* We mustn't have the redirect_uri in the URL when displaying the page as this needs to be handled securely (it will
			probably include a client-issued CSRF key) and if we use it in the URL, any third party scripts that may be being
			used on the community (tracking or advertisements, for example) will have access to it - this also makes the URI
			a bit cleaner */
		if ( isset( Request::i()->client_id ) )
		{
			$key = md5( uniqid() );
			Request::i()->setCookie( 'oauth_authorize', $key );
			
			Db::i()->insert( 'core_oauth_authorize_prompts', array(
				'session_id'			=> $key,
				'client_id'				=> $this->client->client_id,
				'response_type'			=> $this->responseType,
				'redirect_uri'			=> $this->providedRedirectUri ?: NULL,
				'scope'					=> implode( ' ', $this->scope ),
				'state'					=> $this->state,
				'timestamp'				=> time(),
				'logged_in'				=> FALSE,
				'prompt'				=> in_array( $requestedPromptType, array( 'login', 'reauthorize' ) ) ? $requestedPromptType : NULL,
				'code_challenge'		=> $this->codeChallenge,
				'code_challenge_method'	=> $this->codeChallengeMethod
			), TRUE );
			
			$url = Url::internal( 'oauth/authorize/', 'interface' );
			if ( isset( Request::i()->_processLogin ) ) // This is if they clicked a social sign in button on the registration form throwing them back to here
			{
				$url = $url->setQueryString( array(
					'_processLogin'	=> Request::i()->_processLogin,
					'csrfKey'		=> Request::i()->csrfKey,
				) );
			}
			Output::i()->redirect( $url );
		}
		
		/* Construct the URL for this page */
		$url = Url::internal( 'oauth/authorize/', 'interface' );
		
		/* Get the scope definitions */
		$scopes = array();
		$availableScopes = json_decode( $this->client->scopes, TRUE );
		foreach ( $this->scope as $scope )
		{
			$scopes[ $scope ] = $availableScopes[ $scope ]['description'];
		}
				
		/* Do we need them to login? */
		if ( !Member::loggedIn()->member_id or ( ( $this->client->prompt === 'login' or $requestedPromptType === 'login' ) and !$loggedIn ) )
		{
			$login = new Login( $url );

			$member = NULL;
			$error = NULL;
			try
			{
				if ( $success = $login->authenticate() )
				{
					Db::i()->update( 'core_oauth_authorize_prompts', array( 'logged_in' => TRUE, 'prompt' => NULL ), array( 'session_id=?', Request::i()->cookie['oauth_authorize'] ) );
					
					if ( $success->mfa() )
					{
						$_SESSION['processing2FA'] = array( 'memberId' => $success->member->member_id, 'anonymous' => $success->anonymous, 'remember' => $success->rememberMe, 'destination' => (string) $url, 'handler' => $success->handler->id );
						Output::i()->redirect( Url::internal( "app=core&module=system&controller=login", 'front', 'login' )->setQueryString( '_mfaLogin', 1 ) );
					}
					$success->process();
					
					Output::i()->redirect( $url );
				}
			}
			catch ( \IPS\Login\Exception $e )
			{
				if ( $e->getCode() === \IPS\Login\Exception::MERGE_SOCIAL_ACCOUNT )
				{
					$e->member = $e->member->member_id;
					$e->handler = $e->handler->id;
					$_SESSION['linkAccounts'] = json_encode( $e );
					
					Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login&do=link', 'front', 'login' )->setQueryString( 'ref', base64_encode( $url ) ) );
				}
				
				$error = $e->getMessage();
			}
			
			if ( $member === NULL )
			{
				Output::i()->output = Theme::i()->getTemplate( 'login', 'core', 'global' )->oauthLogin( $url, $this->client, $scopes, $login, $error );
				Dispatcher::i()->finish();
			}
		}
		/* If we're logged in but the account is incomplete (e.g. social registration with hitherto unset name/email), redirect to complete registration first */
		elseif ( Member::loggedIn()->member_id and !( Member::loggedIn()->real_name and Member::loggedIn()->email ) )
		{
			$url = Url::internal( 'oauth/authorize/', 'interface' );
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register&do=complete', 'front', 'register' )->addRef( $url )->setQueryString( 'oauth', 1 ) );
		}

		/* Still here? Show an authorization screen */
		Output::i()->output = Theme::i()->getTemplate( 'login', 'core', 'global' )->oauthAuthorize( $url, $this->client, $scopes );
		Dispatcher::i()->finish();
	}
}

/* Init */
Front::i();
External::i();
Output::i()->bodyClasses[] = 'ipsLayout_minimal';
Output::i()->bodyClasses[] = 'ipsLayout_minimalNoHome';
Output::i()->title = Member::loggedIn()->language()->addToStack( 'oauth_authorize', FALSE, array( 'sprintf' => array( Settings::i()->board_name ) ) );
Output::i()->httpHeaders['X-Frame-Options'] = 'DENY';
Output::setCacheTime( false );

/* Check we are not banned */
if ( Request::i()->ipAddressIsBanned() or ( Member::loggedIn()->member_id and Member::loggedIn()->isBanned() ) )
{
	Output::i()->showBanned();
}

/* Handle the OAuth request */
try
{
	/* Get our params */
	$loggedIn = FALSE;
	if ( !isset( Request::i()->client_id ) and isset( Request::i()->cookie['oauth_authorize'] ) )
	{
		try
		{
			$row = Db::i()->select( '*', 'core_oauth_authorize_prompts', array( 'session_id=?', Request::i()->cookie['oauth_authorize'] ) )->first();
			$clientId = $row['client_id'];
			$responseType = $row['response_type'];
			$redirectUri = $row['redirect_uri'];
			$scope = $row['scope'];
			$state = $row['state'];
			$loggedIn = $row['logged_in'];
			$prompt = $row['prompt'];
			$codeChallenge = $row['code_challenge'];
			$codeChallengeMethod = $row['code_challenge_method'];
			
			if ( isset( Request::i()->prompt ) and in_array( Request::i()->prompt, array( 'login', 'reauthorize' ) ) )
			{
				Db::i()->update( 'core_oauth_authorize_prompts', array( 'prompt' => Request::i()->prompt ), array( 'session_id=?', Request::i()->cookie['oauth_authorize'] ) );
				$prompt = Request::i()->prompt;
			}
		}
		catch (UnderflowException $e )
		{
			throw new InitException('oauth_err_invalid_client');
		}
	}
	else
	{
		$clientId = Request::i()->client_id;
		$responseType = Request::i()->response_type;
		$redirectUri = Request::i()->redirect_uri;
		$scope = Request::i()->scope;
		$state = Request::i()->state;
		$prompt = Request::i()->prompt;
		$codeChallenge = isset( Request::i()->code_challenge ) ? Request::i()->code_challenge : NULL;
		$codeChallengeMethod = ( isset( Request::i()->code_challenge_method ) and in_array( Request::i()->code_challenge_method, array( 'plain', 'S256' ) ) ) ? Request::i()->code_challenge_method : NULL;
	}
	
	/* Have we asked to register? */
	if ( isset( Request::i()->register ) )
	{
		/* The authorize prompt data will probably expire before we're done, so put the referal URL (for after registration)
			to the full URL which will initiate a new prompt. But don't delete the current prompt data in case the user hits back */
		$url = Url::internal( 'oauth/authorize/', 'interface' )->setQueryString( array(
			'client_id'			=> $clientId,
			'response_type'		=> $responseType,
			'redirect_uri'		=> $redirectUri,
			'scope'				=> $scope,
			'state'				=> $state,
			'prompt'			=> ( $prompt === 'login' ) ? 'reauthorize' : $prompt, // We never need to log in immediately after registering, that's confusing
		) );
		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=register', 'front', 'register' )->addRef( (string) $url )->setQueryString( 'oauth', 1 ) );
		exit;
	}
	
	/* Init, validating client_id and redirect_uri */
	$request = oAuthServerAuthorizationRequest::init( $clientId, $redirectUri, $state, $codeChallenge, $codeChallengeMethod );
	$request->validate();
	
	/* If site is offline, return temporarily_unavailable */
	if ( ( isset( Settings::i()->setup_in_progress ) AND Settings::i()->setup_in_progress ) or !Settings::i()->site_online )
	{
		throw new \IPS\Login\Handler\OAuth2\Exception('temporarily_unavailable');
	}
	
	/* HTTPs only */
	if ( OAUTH_REQUIRES_HTTPS and !Request::i()->isSecure() )
	{
		throw new \IPS\Login\Handler\OAuth2\Exception( 'invalid_request', "request must be made with https" );
	}
	
	/* Set data */
	$request->setResponseType( $responseType );	
	if ( $scope )
	{
		$request->setScope( $scope );
	}
		
	/* Do we need them to be prompted? */
	$authorizedUrl = NULL;
	if ( $request->promptRequired( $prompt ) )
	{
		$request->prompt( $prompt, $loggedIn );
	}
		
	/* Still here? Go ahead */
	if ( isset( Request::i()->cookie['oauth_authorize'] ) )
	{
		Db::i()->delete( 'core_oauth_authorize_prompts', array( 'session_id=?', Request::i()->cookie['oauth_authorize'] ) );
		Request::i()->setCookie( 'oauth_authorize', NULL );
	}
	Output::i()->redirect( $request->authorized( Member::loggedIn(), isset( Request::i()->grantedScope ) ? array_keys( Request::i()->grantedScope ) : array() ), NULL, 302 );
}
catch ( InitException $e )
{
	Output::i()->error( $e->getMessage(), '2S361/2', 403 );
}
catch ( \IPS\Login\Handler\OAuth2\Exception $e )
{
	$response = array( 'error' => $e->getMessage() );
	if ( $e->description )
	{
		$response['error_description'] = $e->description;
	}
	Output::i()->redirect( $request->redirect( $response ), NULL, 302 );
}
catch ( Exception $e )
{
	Output::i()->redirect( $request->redirect( array( 'error' => 'server_error', 'error_description' => $e->getMessage() ) ), NULL, 302 );
}
