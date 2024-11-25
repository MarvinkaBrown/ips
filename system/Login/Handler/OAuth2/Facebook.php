<?php
/**
 * @brief		Facebook Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		31 May 2017
 */

namespace IPS\Login\Handler\OAuth2;

/* To prevent PHP errors (extending class does not exist) revealing path */
use DomainException;
use IPS\Helpers\Form\Radio;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Exception as LoginException;
use IPS\Login\Handler\OAuth2;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use RuntimeException;
use function defined;
use function in_array;
use function is_numeric;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Facebook Login Handler
 */
class Facebook extends OAuth2
{
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return 'login_handler_Facebook';
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
		Member::loggedIn()->language()->words['oauth_client_id'] = Member::loggedIn()->language()->addToStack('login_facebook_app');
		Member::loggedIn()->language()->words['oauth_client_client_secret'] = Member::loggedIn()->language()->addToStack('login_facebook_secret');

		return array_merge(
			array(
				'real_name'	=> new Radio( 'login_real_name', $this->settings['real_name'] ?? 1, FALSE, array(
					'options' => array(
						1			=> 'login_real_name_facebook',
						0			=> 'login_real_name_disabled',
					),
					'toggles' => array(
						1			=> array( 'login_update_name_changes_inc_optional' ),
					)
				), NULL, NULL, NULL, 'login_real_name' ),
			),
			parent::acpForm(),
			array()
		);
	}
	
	/**
	 * Test Compatibility
	 *
	 * @return	bool
	 * @throws	LogicException
	 */
	public static function testCompatibility(): bool
	{
		if ( mb_substr( Settings::i()->base_url, 0, 8 ) !== 'https://' )
		{
			throw new LogicException( Member::loggedIn()->language()->addToStack( CIC ? 'login_facebook_https_cic' : 'login_facebook_https' ) );
		}
		
		return TRUE;
	}

	/**
	 * Get the button color
	 *
	 * @return	string
	 */
	public function buttonColor(): string
	{
		return '#3a579a';
	}
	
	/**
	 * Get the button icon
	 *
	 * @return	string
	 */
	public function buttonIcon(): string
	{
		return 'facebook-f';
	}
	
	/**
	 * Get button text
	 *
	 * @return	string
	 */
	public function buttonText(): string
	{
		return 'login_facebook';
	}

	/**
	 * Get button class
	 *
	 * @return	string
	 */
	public function buttonClass(): string
	{
		return 'ipsSocial--facebook';
	}
	
	/**
	 * Get logo to display in information about logins with this method
	 * Returns NULL for methods where it is not necessary to indicate the method, e..g Standard
	 *
	 * @return	Url|string|null
	 */
	public function logoForDeviceInformation(): Url|string|null
	{
		return Theme::i()->resource( 'logos/login/Facebook.png', 'core', 'interface' );
	}
	
	/**
	 * Grant Type
	 *
	 * @return	string
	 */
	protected function grantType(): string
	{
		return 'authorization_code';
	}
	
	/**
	 * Get scopes to request
	 *
	 * @param	array|NULL	$additional	Any additional scopes to request
	 * @return	array
	 */
	protected function scopesToRequest( array $additional=NULL ): array
	{
		$return = array('email');
		
		$additionalPermitted = array( 'manage_pages', 'publish_pages' );
		if ( $additional !== NULL )
		{
			foreach( $additional as $scope )
			{
				if ( in_array( $scope, $additionalPermitted ) )
				{
					$return[] = $scope;
				}
			}
		}

		return $return;
	}
	
	/**
	 * Scopes Issued
	 *
	 * @param	string		$accessToken	Access Token
	 * @return	array|NULL
	 */
	public function scopesIssued( string $accessToken ): ?array
	{
		try
		{
			$response = $this->_authorizedRequest( 'me/permissions', $accessToken, array(
				'appsecret_proof' => hash_hmac( 'sha256', $accessToken, $this->settings['client_secret'] )
			), 'get' );
		}
		catch ( \Exception $e )
		{
			return NULL;
		}
		
		$return = array();
		if ( isset( $response['data'] ) )
		{
			foreach ( $response['data'] as $perm )
			{
				if ( $perm['status'] === 'granted' )
				{
					$return[] = $perm['permission'];
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	Url
	 */
	protected function authorizationEndpoint( Login $login ): Url
	{
		$return = Url::external('https://www.facebook.com/dialog/oauth');
		
		if ( $login->type === Login::LOGIN_ACP or $login->type === Login::LOGIN_REAUTHENTICATE )
		{
			$return = $return->setQueryString( 'auth_type', 'reauthenticate' );
		}
		
		return $return;
	}
	
	/**
	 * Token Endpoint
	 *
	 * @return	Url
	 */
	protected function tokenEndpoint(): Url
	{
		return Url::external('https://graph.facebook.com/v2.9/oauth/access_token');
	}
	
	/**
	 * Redirection Endpoint
	 *
	 * @return	Url
	 */
	protected function redirectionEndpoint(): Url
	{
		if ( isset( $this->settings['legacy_redirect'] ) and $this->settings['legacy_redirect'] )
		{
			return Url::internal( 'applications/core/interface/facebook/auth.php', 'none' );
		}
		return parent::redirectionEndpoint();
	}
	
	/**
	 * Get authenticated user's identifier (may not be a number)
	 *
	 * @param	string	$accessToken	Access Token
	 * @return	string|null
	 */
	protected function authenticatedUserId( string $accessToken ): ?string
	{
		return $this->_userData( $accessToken )['id'];
	}
	
	/**
	 * Get authenticated user's username
	 * May return NULL if server doesn't support this
	 *
	 * @param	string	$accessToken	Access Token
	 * @return	string|NULL
	 */
	protected function authenticatedUserName( string $accessToken ): ?string
	{
		if ( isset( $this->settings['real_name'] ) and $this->settings['real_name'] )
		{
			return $this->_userData( $accessToken )['name'];
		}
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
		return $this->_userData( $accessToken )['email'];
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
		if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
		{
			throw new LoginException( "", LoginException::INTERNAL_ERROR );
		}
		
		$photoVars = explode( ':', $member->group['g_photo_max_vars'] );		
		$response = $this->_authorizedRequest( "{$link['token_identifier']}/picture?width={$photoVars[1]}&redirect=false", $link['token_access_token'], NULL, 'get' );
		if ( !$response['data']['is_silhouette'] and isset( $response['data']['url'] ) and $response['data']['url'] )
		{
			return Url::external( $response['data']['url'] );
		}
		return NULL;
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
		if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
		{
			throw new LoginException( "", LoginException::INTERNAL_ERROR );
		}
		
		return $this->_userData( $link['token_access_token'] )['name'];
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
		$scopes = $this->authorizedScopes( $member );

		if ( ( !isset( $this->settings['update_email_changes'] ) or $this->settings['update_email_changes'] === 'optional' ) and ( $scopes and in_array( 'email', $scopes ) ) )
		{
			$return[] = 'email';
		}
		
		if ( isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] === 'optional' and isset( $this->settings['real_name'] ) and $this->settings['real_name'] )
		{
			$return[] = 'name';
		}
		
		$return[] = 'photo';
		
		return $return;
	}
	
	/**
	 * @brief	Cached user data
	 */
	protected array $_cachedUserData = array();
	
	/**
	 * Get user data
	 *
	 * @param string $accessToken	Access Token
	 * @return	array
	 * @throws    LoginException    The token is invalid and the user needs to reauthenticate
	 * @throws	RuntimeException		Unexpected error from service
	 */
	protected function _userData( string $accessToken ): array
	{
		if ( !isset( $this->_cachedUserData[ $accessToken ] ) )
		{
			$response = $this->_authorizedRequest( 'me', $accessToken, array(
				'fields'			=> 'email,id,name,picture',
				'appsecret_proof' 	=> hash_hmac( 'sha256', $accessToken, $this->settings['client_secret'] )
			), 'get' );
				
			if ( isset( $response['error'] ) )
			{
				throw new LoginException( $response['error']['message'], LoginException::INTERNAL_ERROR );
			}
				
			$this->_cachedUserData[ $accessToken ] = $response;
		}
		return $this->_cachedUserData[ $accessToken ];
	}


	/**
	 * Make authorized request
	 *
	 * @param string $endpoint		Endpoint
	 * @param string $accessToken	Access Token
	 * @param array|null $data		Data to post or query string]
	 * @param string|null $method			'get' or 'post'
	 * @return	array
	 * @throws	Exception
	 */
	protected function _authorizedRequest(string $endpoint, string $accessToken, array $data = NULL, string $method = NULL ): array
	{
		$url = Url::external( "https://graph.facebook.com/{$endpoint}" );
		if ( $method === 'get' and $data )
		{
			$url = $url->setQueryString( $data );
		}
		
		$request = $url->request()->setHeaders( array( 'Authorization' => "Bearer {$accessToken}" ) );
		if ( $method === 'get' or !$data )
		{
			$response = $request->get();
		}
		else
		{
			$response = $request->post( $data );
		}
		
		return $response->decodeJson();
	}

	/**
	 * Get user link
	 *
	 * @param Member $member		Member requesting pages
	 * @return	array
	 */
	protected function _promoteLink( Member $member ): array
	{
		if ( is_numeric( Settings::i()->promote_facebook_auth ) )
		{
			/* standard handler */
			if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
			{
				return array();
			}
		}
		
		return $link ?? [];
	}
}