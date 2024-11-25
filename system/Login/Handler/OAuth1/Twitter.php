<?php
/**
 * @brief		Twitter Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 June 2017
 */

namespace IPS\Login\Handler\OAuth1;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form\Radio;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Exception as LoginException;
use IPS\Login\Handler\OAuth1;
use IPS\Member;
use IPS\Theme;
use RuntimeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Twitter Login Handler
 */
class Twitter extends OAuth1
{
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return 'login_handler_Twitter';
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
		Member::loggedIn()->language()->words['login_acp_desc'] = Member::loggedIn()->language()->addToStack('login_acp_will_reauth');
		
		return array_merge(
			array(
				'name'				=> new Radio( 'login_real_name', ( isset( $this->settings['name'] ) ) ? $this->settings['name'] : 'any', TRUE, array(
					'options' => array(
						'real'		=> 'login_twitter_name_real',
						'screen'	=> 'login_twitter_name_screen',
						'any'		=> 'login_real_name_disabled',
					),
					'toggles' => array(
						'real'		=> array( 'login_update_name_changes_inc_optional' ),
						'screen'	=> array( 'login_update_name_changes_inc_optional' ),
					)
				), NULL, NULL, NULL, 'login_real_name' ),
			),
			parent::acpForm(),
			array()
		);
	}
	
	/**
	 * Get the button color
	 *
	 * @return	string
	 */
	public function buttonColor(): string
	{
		return '#1d9bf0';
	}
	
	/**
	 * Get the button icon
	 *
	 * @return	string
	 */
	public function buttonIcon(): string
	{
		return 'x-twitter';
	}
	
	/**
	 * Get button text
	 *
	 * @return	string
	 */
	public function buttonText(): string
	{
		return 'login_twitter';
	}

	/**
	 * Get button class
	 *
	 * @return	string
	 */
	public function buttonClass(): string
	{
		return 'ipsSocial--twitter';
	}
	
	/**
	 * Get logo to display in information about logins with this method
	 * Returns NULL for methods where it is not necessary to indicate the method, e..g Standard
	 *
	 * @return	Url|string|null
	 */
	public function logoForDeviceInformation(): Url|string|null
	{
		return Theme::i()->resource( 'logos/login/Twitter.png', 'core', 'interface' );
	}
	
	/**
	 * Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	Url
	 */
	protected function authorizationEndpoint( Login $login ): Url
	{
		$return = Url::external('https://api.twitter.com/oauth/authenticate');
		
		if ( $login->type === Login::LOGIN_ACP or $login->type === Login::LOGIN_REAUTHENTICATE )
		{
			$return = $return->setQueryString( 'force_login', 'true' );
		}
		
		if ( $login->type === Login::LOGIN_REAUTHENTICATE )
		{
			try
			{
				$token = Db::i()->select( array( 'token_access_token', 'token_secret' ), 'core_login_links', array( 'token_login_method=? AND token_member=?', $this->id, $login->reauthenticateAs->member_id ) )->first();
				$userDetails = $this->_userData( $token['token_access_token'], $token['token_secret'] );
				if ( isset( $userDetails['screen_name'] ) )
				{
					$return = $return->setQueryString( 'screen_name', $userDetails['screen_name'] );
				}
			}
			catch ( \Exception $e ) { }
		}
		
		return $return;
	}
	
	/**
	 * Token Request Endpoint
	 *
	 * @return	Url
	 */
	protected function tokenRequestEndpoint(): Url
	{
		return Url::external('https://api.twitter.com/oauth/request_token');
	}
	
	/**
	 * Access Token Endpoint
	 *
	 * @return	Url
	 */
	protected function accessTokenEndpoint(): Url
	{
		return Url::external('https://api.twitter.com/oauth/access_token');
	}
	
	/**
	 * Get authenticated user's identifier (may not be a number)
	 *
	 * @param	string	$accessToken		Access Token
	 * @param	string	$accessTokenSecret	Access Token Secret
	 * @return	string
	 */
	protected function authenticatedUserId( string $accessToken, string $accessTokenSecret ): string
	{
		return $this->_userData( $accessToken, $accessTokenSecret )['id'];
	}
	
	/**
	 * Get authenticated user's username
	 * May return NULL if server doesn't support this
	 *
	 * @param string $accessToken		Access Token
	 * @param string $accessTokenSecret	Access Token Secret
	 * @return	string|NULL
	 */
	protected function authenticatedUserName( string $accessToken, string $accessTokenSecret ): ?string
	{
		if ( $this->settings['name'] == 'screen' )
		{
			return $this->_userData( $accessToken, $accessTokenSecret )['screen_name'];
		}
		elseif ( $this->settings['name'] == 'real' )
		{
			return $this->_userData( $accessToken, $accessTokenSecret )['name'];
		}
		return NULL;
	}
	
	/**
	 * Get authenticated user's email address
	 * May return NULL if server doesn't support this
	 *
	 * @param string $accessToken		Access Token
	 * @param string $accessTokenSecret	Access Token Secret
	 * @return	string|NULL
	 */
	protected function authenticatedEmail( string $accessToken, string $accessTokenSecret ): ?string
	{
		return $this->_userData( $accessToken, $accessTokenSecret )['email'];
	}

	/**
	 * Get user's profile name
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	string|NULL
	 * @throws    LoginException    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfileName( Member $member ): ?string
	{
		if ( !( $link = $this->_link( $member ) ) )
		{
			throw new LoginException( "", LoginException::INTERNAL_ERROR );
		}
		
		return $this->_userData( $link['token_access_token'], $link['token_secret'] )['screen_name'];
	}
	
	/**
	 * Get user's profile photo
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	Url|NULL
	 * @throws    LoginException    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfilePhoto( Member $member ): ?Url
	{
		if ( !( $link = $this->_link( $member ) ) )
		{
			throw new LoginException( "", LoginException::INTERNAL_ERROR );
		}
				
		$userData = $this->_userData( $link['token_access_token'], $link['token_secret'] );
		if ( !$userData['default_profile_image'] )
		{
			return Url::external( str_replace( '_normal', '', $userData['profile_image_url_https'] ?: $userData['profile_image_url'] ) );
		}
		return NULL;
	}
		
	/**
	 * Get user's cover photo
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	Url|NULL
	 * @throws    LoginException    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userCoverPhoto( Member $member ): ?Url
	{
		if ( !( $link = $this->_link( $member ) ) )
		{
			throw new LoginException( "", LoginException::INTERNAL_ERROR );
		}
				
		$userData = $this->_userData( $link['token_access_token'], $link['token_secret'] );		
		if ( isset( $userData['profile_banner_url'] ) and $userData['profile_banner_url'] )
		{
			return Url::external( $userData['profile_banner_url'] );
		}
		
		return NULL;
	}
	
	/**
	 * Get link to user's remote profile
	 * May return NULL if server doesn't support this
	 *
	 * @param	string	$identifier	The ID Nnumber/string from remote service
	 * @param	string	$username	The username from remote service
	 * @return	Url|NULL
	 * @throws    LoginException    The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userLink( string $identifier, string $username ): ?Url
	{
		return Url::external( "https://twitter.com/" )->setPath( $username );
	}
	
	/**
	 * Syncing Options
	 *
	 * @param	Member	$member			The member we're asking for (can be used to not show certain options iof the user didn't grant those scopes)
	 * @param	bool		$defaultOnly	If TRUE, only returns which options should be enabled by default for a new account
	 * @return	array
	 */
	public function syncOptions( Member $member, bool $defaultOnly=FALSE ): array
	{
		$return = array();
		
		if ( !isset( $this->settings['update_email_changes'] ) or $this->settings['update_email_changes'] === 'optional' )
		{
			$return[] = 'email';
		}
		
		if ( isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] === 'optional' and isset( $this->settings['name'] ) and $this->settings['name'] != 'any' )
		{
			$return[] = 'name';
		}
		
		$return[] = 'photo';
		$return[] = 'cover';
		
		return $return;
	}
	
	/**
	 * @brief	Cached user data
	 */
	protected array $_cachedUserData = array();
	
	/**
	 * Get user data
	 *
	 * @param string $accessToken		Access Token
	 * @param string $accessTokenSecret	Access Token Secret
	 * @return	array
	 * @throws    LoginException    The token is invalid and the user needs to reauthenticate
	 * @throws	RuntimeException		Unexpected error from service
	 */
	protected function _userData( string $accessToken, string $accessTokenSecret ): array
	{
		if ( !isset( $this->_cachedUserData[ $accessToken ] ) )
		{
			$response = $this->_sendRequest( 'get', Url::external('https://api.twitter.com/1.1/account/verify_credentials.json'), array( 'include_email' => 'true' ), $accessToken, $accessTokenSecret )->decodeJson();
			if ( isset( $response['errors'] ) )
			{
				throw new LoginException( $response['errors'][0]['message'], LoginException::INTERNAL_ERROR );
			}
			
			$this->_cachedUserData[ $accessToken ] = $response;
		}
		return $this->_cachedUserData[ $accessToken ];
	}


	/**
	 * @brief       The length of the shortened URLs returned by Twitter's text parser https://developer.twitter.com/en/docs/counting-characters#:~:text=The%20current%20length%20of%20a,count%20towards%20the%20character%20limit.
	 */
	protected static int $defaultShortenedUrlLength = 23;

	/**
	 * Post something to Twitter
	 *
	 * @param	Member			$member		Member posting
	 * @param	string				$content	Content to post
	 * @param	Url|NULL	$url		Optional link
	 * @return	bool
	 */
	public function postToTwitter( Member $member, string $content, ?Url $url = NULL ) : bool
	{
		if ( !( $link = $this->_link( $member ) ) )
		{
			return false;
		}
		
		$data = array( 'status' => $content );

		/* If we have a url, add it to the end prepended by a space */
		if ( $url !== NULL )
		{
			/* Try to refresh if the stored response is more than a day old */
			if ( !isset( Store::i()->twitter_config ) or Store::i()->twitter_config['time'] > time() - 86400 )
			{
				try
				{
					$response = $this->_sendRequest( 'get', Url::external('https://api.twitter.com/1.1/help/configuration.json'), array(), $link['token_access_token'], $link['token_secret'] );
					if ( ( $response->httpResponseCode === 200 ) AND ( $payload = $response->decodeJson() ) )
					{
						Store::i()->twitter_config = array_merge( $payload, array( 'time' => time() ) );
					}
					else
					{
						throw new UnexpectedValueException();
					}
				}
				catch ( \Exception| UnexpectedValueException $e )
				{
					/* We should be fine hard coding to 23 if the deprecated configuration endpoint is no longer online https://developer.twitter.com/en/docs/counting-characters#:~:text=The%20current%20length%20of%20a,count%20towards%20the%20character%20limit. */
					Store::i()->twitter_config['short_url_length'] = static::$defaultShortenedUrlLength;
				}
			}

			$maxUrlLen = Store::i()->twitter_config['short_url_length'] ?? static::$defaultShortenedUrlLength;
			$data['status'] = mb_substr( $data['status'], 0, ( 140 - ( $maxUrlLen + 1 ) ) ) . ' ' . $url;
		}
				
		$response = $this->_sendRequest( 'post', Url::external('https://api.twitter.com/1.1/statuses/update.json'), $data, $link['token_access_token'], $link['token_secret'] )->decodeJson();

		return isset( $response['id_str'] );
	}
	
	/* ! Social Promotes */
	
	/**
	 * Request a token
	 *
	 * @param Url $callback		Callback URL
	 * @return    array
	 */
	public function requestToken( Url $callback ): array
	{
		return $this->_sendRequest( 'get', $this->tokenRequestEndpoint(), array( 'oauth_callback' => (string) $callback ) )->decodeQueryString('oauth_token');
	}
	
	/**
	 * Get authenticated user's identifier (may not be a number)
	 *
	 * @param string $verifier			Verifier
	 * @param string $accessToken	Access Token
	 * @return	array
	 */
	public function exchangeToken(string $verifier, string $accessToken ): array
	{
		return $this->_sendRequest( 'post', $this->accessTokenEndpoint(), array( 'oauth_verifier' => $verifier ), $accessToken )->decodeQueryString('user_id');
	}
	
	/**
	 * Can we publish to this twitter account?
	 *
	 * @param string $accessToken		Access Token
	 * @param string $accessTokenSecret	Access Token Secret
	 * @return	boolean
	 */
	public function hasWritePermissions( string $accessToken, string $accessTokenSecret ): bool
	{
		try
		{
			$response = $this->_sendRequest( 'get', Url::external('https://api.twitter.com/1.1/account/verify_credentials.json'), array(), $accessToken, $accessTokenSecret );
			
			if ( $response->httpResponseCode == 401 )
			{
				return FALSE;
			}
			
			if ( $response->httpHeaders['x-access-level'] == 'read' )
			{
				return FALSE;
			}
			
			$response->decodeJson();
			
			return TRUE;
		}
		catch ( Exception $e ) { }
		
		return FALSE;
	}
	
	/**
	 * Send media
	 *
	 * @param array $contents			Photo contents
	 * @param string $accessToken		Access Token
	 * @param string $accessTokenSecret	Access Token Secret
	 * @return	array
	 */
	public function sendMedia( array $contents, string $accessToken, string $accessTokenSecret ): array
	{
		$mimeBoundary = sha1( microtime() );
		
		$data = '--' . $mimeBoundary . "\r\n";
        $data .= 'Content-Disposition: form-data; name="media";' . "\r\n";
        $data .= 'Content-Type: application/octet-stream' . "\r\n" . "\r\n";
        $data .= $contents . "\r\n";
        $data .= '--' . $mimeBoundary . '--' . "\r\n" . "\r\n";
	        
		return $this->_sendRequest( 'post', Url::external('https://upload.twitter.com/1.1/media/upload.json'), array(), $accessToken, $accessTokenSecret, array(), array( $mimeBoundary, $data ) )->decodeJson();
	}
}