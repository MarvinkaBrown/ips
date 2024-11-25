<?php
/**
 * @brief		Abstract OAuth1 Login Handler
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
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract OAuth1 Login Handler
 */
abstract class OAuth1 extends Handler
{
	/* !Login Handler: Basics */
		
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
			'consumer_key'		=> new Text( 'oauth_consumer_key', ( isset( $this->settings['consumer_key'] ) ) ? $this->settings['consumer_key'] : '', TRUE ),
			'consumer_secret'	=> new Text( 'oauth_consumer_secret', ( isset( $this->settings['consumer_secret'] ) ) ? $this->settings['consumer_secret'] : '', TRUE ),
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
		), 'disabled' => $emailChangesDisabled  ), NULL, NULL, NULL, 'login_update_email_changes_inc_optional' );
		
		return $return;
	}
		
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	LogicException
	 */
	public function testSettings(): bool
	{
		try
		{
			$response = $this->_sendRequest( 'get', $this->tokenRequestEndpoint() );
		}
		catch ( \Exception $e )
		{
			throw new LogicException( Member::loggedIn()->language()->addToStack( 'oauth1_setup_error', FALSE, array( 'sprintf' => array( $e->getMessage() ) ) ) );
		}
		
		try
		{
			$response->decodeQueryString('oauth_token');
		}
		catch ( \Exception $e )
		{
			throw new LogicException( Member::loggedIn()->language()->addToStack( 'oauth1_setup_error', FALSE, array( 'sprintf' => array( (string) $response ) ) ) );
		}

		return true;
	}
	
	/* !Authentication */
	
	use ButtonHandler;
	
	/**
	 * Authenticate
	 *
	 * @param	Login	$login	The login object
	 * @return	Member|null
	 * @throws	Exception
	 */
	public function authenticateButton( Login $login ) : ?Member
	{
		if ( isset( Request::i()->denied ) )
		{
			return NULL;
		}
		elseif ( isset( Request::i()->oauth_token ) )
		{
			return $this->_handleAuthorizationResponse( $login );
		}
		else
		{		
			$this->_redirectToAuthorizationEndpoint( $login );
		}

		return null;
	}
	
	/**
	 * Redirect to Resource Owner Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	void
	 */
	protected function _redirectToAuthorizationEndpoint( Login $login ) : void
	{
		$callback = $this->redirectionEndpoint()->setQueryString( 'state' , $this->id . '-' . base64_encode( $login->url ) . '-' . Session::i()->csrfKey . '-' . Request::i()->ref );
		
		try
		{
			$response = $this->_sendRequest( 'get', $this->tokenRequestEndpoint(), array( 'oauth_callback' => (string) $callback ) )->decodeQueryString('oauth_token');
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'twitter' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
		
		Output::i()->redirect( $this->authorizationEndpoint( $login )->setQueryString( 'oauth_token', $response['oauth_token'] ) );
	}
	
	/**
	 * Handle authorization response
	 *
	 * @param	Login	$login	The login object
	 * @return	Member
	 * @throws	Exception
	 */
	protected function _handleAuthorizationResponse( Login $login ): Member
	{		
		/* Authenticate */
		try
		{
			$response = $this->_sendRequest( 'post', $this->accessTokenEndpoint(), array( 'oauth_verifier' => Request::i()->oauth_verifier ), Request::i()->oauth_token )->decodeQueryString('oauth_token');
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'twitter' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
		}
						
		/* Get user id */
		try
		{
			$userId = $this->authenticatedUserId( $response['oauth_token'], $response['oauth_token_secret'] );
		}
		catch ( \Exception $e )
		{
			Log::log( $e, 'oauth' );
			throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
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
			
			/* Otherwise, update our token... */
			Db::i()->update( 'core_login_links', array(
				'token_access_token'	=> $response['oauth_token'],
				'token_secret'			=> $response['oauth_token_secret'],
			), array( 'token_login_method=? AND token_member=?', $this->id, $oauthAccess['token_member'] ) );
			
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
				$name = $this->authenticatedUserName( $response['oauth_token'], $response['oauth_token_secret'] );
			}
			catch ( \Exception $e ) {}
			$email = NULL;
			try
			{
				$email = $this->authenticatedEmail( $response['oauth_token'], $response['oauth_token_secret'] );
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
				
				Db::i()->insert( 'core_login_links', array(
					'token_login_method'	=> $this->id,
					'token_member'			=> $member->member_id,
					'token_identifier'		=> $userId,
					'token_linked'			=> 1,
					'token_access_token'	=> $response['oauth_token'],
					'token_secret'			=> $response['oauth_token_secret'],
				) );
				
				$member->logHistory( 'core', 'social_account', array(
					'service'		=> static::getTitle(),
					'handler'		=> $this->id,
					'account_id'	=> $this->userId( $member ),
					'account_name'	=> $this->userProfileName( $member ),
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
						Db::i()->insert( 'core_login_links', array(
							'token_login_method'	=> $this->id,
							'token_member'			=> $exception->member->member_id,
							'token_identifier'		=> $userId,
							'token_linked'			=> 0,
							'token_access_token'	=> $response['oauth_token'],
							'token_secret'			=> $response['oauth_token_secret'],
						) );
					}
				}
				
				throw $exception;
			}
		}
	}
	
	/**
	 * Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	Url
	 */
	abstract protected function authorizationEndpoint( Login $login ): Url;
	
	/**
	 * Token Request Endpoint
	 *
	 * @return	Url
	 */
	abstract protected function tokenRequestEndpoint(): Url;
	
	/**
	 * Access Token Endpoint
	 *
	 * @return	Url
	 */
	abstract protected function accessTokenEndpoint(): Url;

	/**
	 * Redirection Endpoint
	 *
	 * @return	Url
	 */
	public function redirectionEndpoint(): Url
	{
		return Url::internal( 'oauth/callback/', 'none' );
	}
	
	/**
	 * Send Request
	 *
	 * @param string $method			HTTP Method
	 * @param Url $url				URL
	 * @param array|string|null $params			Parameters
	 * @param string $token			OAuth Token
	 * @param string $secret		OAuth Secret
	 * @param array $otherParams		Other params to send obvs
	 * @param array|string $mimeBoundary		Mime data to send (boundary => data )
	 * @return	Response
	 * @throws	\IPS\Http\Request\Exception
	 */
	protected function _sendRequest( string $method, Url $url, array|string|null $params=array(), string $token='', string $secret='', array $otherParams=array(), array|string $mimeBoundary=array() ): Response
	{		
		/* Generate the OAUTH Authorization Header */
		$OAuthAuthorization = array_merge( array(
			'oauth_consumer_key'	=> $this->settings['consumer_key'],
			'oauth_nonce'			=> md5( Login::generateRandomString() ),
			'oauth_signature_method'=> 'HMAC-SHA1',
			'oauth_timestamp'		=> time(),
			'oauth_token'			=> $token,
			'oauth_version'			=> '1.0'
		) );
		
		$queryStringParams = array();
		foreach ( $params as $k => $v )
		{
			if ( mb_substr( $k, 0, 6 ) === 'oauth_' )
			{
				$OAuthAuthorization = array_merge( array( $k => $v ), $OAuthAuthorization );
				unset( $params[ $k ] );
			}
			elseif ( $method === 'get' )
			{
				$queryStringParams[ $k ] = $v;
			}
		}
		
		/* All keys sent in the signature must be in alphabetical order, that includes oAuth keys and user sent params */
		$allKeys = array_merge( $OAuthAuthorization, $params );
		ksort( $allKeys );
		
		$signatureBaseString = mb_strtoupper( $method ) . '&' . rawurlencode( (string) $url ) . '&' . rawurlencode( http_build_query( $allKeys, NULL, '&', PHP_QUERY_RFC3986 ) );	
		$signingKey = rawurlencode( $this->settings['consumer_secret'] ) . '&' . rawurlencode( $secret ?: $token );			
		$OAuthAuthorizationEncoded = array();
		foreach ( $OAuthAuthorization as $k => $v )
		{
			$OAuthAuthorizationEncoded[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
			
			if ( $k === 'oauth_nonce' )
			{
				$signature = base64_encode( hash_hmac( 'sha1', $signatureBaseString, $signingKey, TRUE ) );
				$OAuthAuthorizationEncoded[] = rawurlencode( 'oauth_signature' ) . '="' . rawurlencode( $signature ) . '"';
			}
		}
		$OAuthAuthorizationHeader = 'OAuth ' . implode( ', ', $OAuthAuthorizationEncoded );
		
		$headers = array( 'Authorization' => $OAuthAuthorizationHeader );
		
		/* Send the request */
		if ( ! count( $mimeBoundary ) )
		{
			if ( $method === 'get' )
			{
				return $url->setQueryString( $queryStringParams )->request()->setHeaders( $headers )->get();
			}
			else
			{
				return $url->setQueryString( $queryStringParams )->request()->setHeaders( $headers )->$method( $params );
			}
		}
		else
		{
			$headers['Content-Type'] = 'multipart/form-data; boundary=' . $mimeBoundary[0];
			
			return $url->setQueryString( $queryStringParams )->request()->setHeaders( $headers )->$method( $mimeBoundary[1] );
		}
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
		if ( !( $link = $this->_link( $member ) ) )
		{
			throw new Exception( "", Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedUserId( $link['token_access_token'], $link['token_secret'] );
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
		if ( !( $link = $this->_link( $member ) ) )
		{
			throw new Exception( "", Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedUserName( $link['token_access_token'], $link['token_secret'] );
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
		if ( !( $link = $this->_link( $member ) ) )
		{
			throw new Exception( "", Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedEmail( $link['token_access_token'], $link['token_secret'] );
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
		
		if ( $return AND !isset( $this->settings['show_in_ucp'] ) )
		{
			return TRUE; // Default to showing
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
}