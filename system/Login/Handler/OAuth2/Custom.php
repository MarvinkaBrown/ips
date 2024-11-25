<?php
/**
 * @brief		Custom OAuth 2 Login Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		31 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login\Handler\OAuth2;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\File;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Log;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Login\Handler\OAuth2;
use IPS\Member;
use IPS\Settings;
use RuntimeException;
use function defined;
use function is_array;
use function is_string;
use const IPS\OAUTH_REQUIRES_HTTPS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom OAuth 2 Login Handler
 */
class Custom extends OAuth2
{
	/**
	 * @brief	Can we have multiple instances of this handler?
	 */
	public static bool $allowMultiple = TRUE;
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public static function getTitle(): string
	{
		return 'login_handler_custom_oauth';
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
		$return = array();
		
		$return[] = array( 'login_handler_oauth_settings', 'login_handler_custom_oauth_info' );
		
		$return['grant_type'] = new Radio( 'oauth_custom_grant_type', $this->settings['grant_type'] ?? 'authorization_code', TRUE, array(
			'options' => array(
				'authorization_code'	=> 'client_grant_type_authorization_code',
				'implicit'				=> 'client_grant_type_implicit',
				'password'				=> 'client_grant_type_password',
			),
			'toggles' => array(
				'authorization_code'	=> array( 'authorization_endpoint', 'authorization_endpoint_secure', 'button_color', 'button_text', 'client_secret' ),
				'implicit'				=> array( 'authorization_endpoint', 'authorization_endpoint_secure', 'button_color', 'button_text' ),
				'password'				=> array( 'client_secret', 'oauth_custom_auth_types', 'forgot_password_url' )
			)
		) );
		
		$accountManagementSettings = array();
		$active = 'return';
		foreach ( parent::acpForm() as $k => $v )
		{
			if ( $v === 'account_management_settings' )
			{
				$active = 'accountManagementSettings';
			}
			if ( !is_string( $v ) and !is_array( $v ) )
			{
				${$active}[ $k ] = $v;
			}
		}
		
		$return['authentication_type'] = new Radio( 'oauth_custom_authentication_type', $this->settings['authentication_type'] ?? static::AUTHENTICATE_HEADER, TRUE, array(
			'options' => array(
				static::AUTHENTICATE_HEADER	=> 'oauth_custom_authentication_type_header',
				static::AUTHENTICATE_POST	=> 'oauth_custom_authentication_type_post',
			)
		) );
		
		$return['scopes'] = new Stack( 'oauth_scopes_to_request', $this->settings['scopes'] ?? array(), FALSE, array() );
		
		$authorizationEndpointValidation = function( $val )
		{
			if ( OAUTH_REQUIRES_HTTPS and $val and $val instanceof Url )
			{
				if ( $val->data[ Url::COMPONENT_SCHEME ] !== 'https' )
				{
					throw new DomainException('authorization_endpoint_https');
				}
				if ( $val->data[ Url::COMPONENT_FRAGMENT ] )
				{
					throw new DomainException('authorization_endpoint_fragment');
				}
			}
		};

		$return['authorization_endpoint'] = new FormUrl( 'oauth_authorization_endpoint', $this->settings['authorization_endpoint'] ?? NULL, NULL, array( 'placeholder' => 'https://example.com/oauth/authorize' ), $authorizationEndpointValidation, NULL, NULL, 'authorization_endpoint' );
		$return['authorization_endpoint_secure'] = new FormUrl( 'oauth_authorization_endpoint_secure', ( isset( $this->settings['authorization_endpoint_secure'] ) AND $this->settings['authorization_endpoint_secure'] ) ? $this->settings['authorization_endpoint_secure'] : NULL, NULL, array( 'nullLang' => 'oauth_authorization_endpoint_same', 'placeholder' => 'https://example.com/oauth/authorize/?prompt=login' ), $authorizationEndpointValidation, NULL, NULL, 'authorization_endpoint_secure' );
		$return['token_endpoint'] = new FormUrl( 'oauth_token_endpoint', $this->settings['token_endpoint'] ?? NULL, TRUE, array( 'placeholder' => 'https://www.example.com/oauth/token' ) );
		$return['user_endpoint'] = new FormUrl( 'oauth_user_endpoint', $this->settings['user_endpoint'] ?? NULL, TRUE, array( 'placeholder' => 'https://www.example.com/oauth/me' ) );
		$return['uid_field'] = new Text( 'oauth_custom_uid_field', $this->settings['uid_field'] ?? NULL, TRUE, array() );
		$return['name_field'] = new Text( 'oauth_custom_name_field', $this->settings['name_field'] ?? NULL, FALSE, array(), NULL, NULL, NULL, 'login_real_name' );
		$return['email_field'] = new Text( 'oauth_custom_email_field', $this->settings['email_field'] ?? NULL, FALSE, array(), NULL, NULL, NULL, 'login_real_email' );
		$return['photo_field'] = new Text( 'oauth_custom_photo_field', $this->settings['photo_field'] ?? NULL );
		if ( Settings::i()->allow_forgot_password == 'normal' or Settings::i()->allow_forgot_password == 'handler' )
		{
			$return['forgot_password_url'] = new FormUrl( 'handler_forgot_password_url', $this->settings['forgot_password_url'] ?? NULL, FALSE, array(), NULL, NULL, NULL, 'forgot_password_url' );
			Member::loggedIn()->language()->words['handler_forgot_password_url_desc'] = Member::loggedIn()->language()->addToStack( Settings::i()->allow_forgot_password == 'normal' ? 'handler_forgot_password_url_desc_normal' : 'handler_forgot_password_url_deschandler' );
		}
		
		$return[] = 'login_handler_oauth_ui';
		$return['auth_types'] = new Select( 'oauth_custom_auth_types', $this->settings['auth_types'] ?? ( Login::AUTH_TYPE_USERNAME + Login::AUTH_TYPE_EMAIL ), TRUE, array( 'options' => array(
			Login::AUTH_TYPE_USERNAME + Login::AUTH_TYPE_EMAIL => 'username_or_email',
			Login::AUTH_TYPE_EMAIL	=> 'email_address',
			Login::AUTH_TYPE_USERNAME => 'username',
		) ), NULL, NULL, NULL, 'oauth_custom_auth_types' );
		$return['button_color'] = new Color( 'oauth_custom_button_color', $this->settings['button_color'] ?? '#478F79', NULL, array(), NULL, NULL, NULL, 'button_color' );
		$return['button_text'] = new Translatable( 'oauth_custom_button_text',  NULL, NULL, array( 'placeholder' => Member::loggedIn()->language()->addToStack('oauth_custom_button_text_custom_placeholder'), 'app' => 'core', 'key' => ( $this->id ? "core_custom_oauth_{$this->id}" : NULL ) ), NULL, NULL, NULL, 'button_text' );
		$return['button_icon'] = new Upload( 'oauth_custom_button_icon',  ( isset( $this->settings['button_icon'] ) and $this->settings['button_icon'] ) ? File::get( 'core_Login', $this->settings['button_icon'] ) : NULL, FALSE, array( 'storageExtension' => 'core_Login' ), NULL, NULL, NULL, 'button_icon' );
		
		$return[] = 'account_management_settings';
		foreach ( $accountManagementSettings as $k => $v )
		{
			$return[ $k ] = $v;
		}
		
		return $return;
	}
	
	/**
	 * Save Handler Settings
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function acpFormSave( array &$values ): array
	{
		$return = parent::acpFormSave( $values );
		$return['button_icon'] = (string) $return['button_icon'];
		$return['authorization_endpoint'] = (string) $return['authorization_endpoint'];
		$return['token_endpoint'] = (string) $return['token_endpoint'];
		$return['user_endpoint'] = (string) $return['user_endpoint'];
		return $return;
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$parent = parent::formatFormValues( $values );

		if( isset( $values['oauth_custom_button_text'] ) )
		{
			if ( !$this->id )
			{
				$this->save();
			}
			Lang::saveCustom( 'core', "core_custom_oauth_{$this->id}", $values['oauth_custom_button_text'] );
			unset( $values['button_text'] );
		}
		
		return $parent;
	}
	
	/**
	 * Get the button color
	 *
	 * @return	string
	 */
	public function buttonColor(): string
	{
		return $this->settings['button_color'];
	}
	
	/**
	 * Get the button icon
	 *
	 * @return	string
	 */
	public function buttonIcon(): string
	{
		return ( isset( $this->settings['button_icon'] ) and $this->settings['button_icon'] ) ? File::get( 'core_Login', $this->settings['button_icon'] ) : '';
	}

	/**
	 * Get logo to display in information about logins with this method
	 * Returns NULL for methods where it is not necessary to indicate the method, e..g Standard
	 *
	 * @return Url|string|null
	 */
	public function logoForDeviceInformation(): Url|string|null
	{
		return ( isset( $this->settings['button_icon'] ) and $this->settings['button_icon'] ) ? File::get( 'core_Login', $this->settings['button_icon'] )->url : NULL;
	}

	/**
	 * Get logo to display in user cp sidebar
	 *
	 * @return Url|string|null
	 */
	public function logoForUcp(): Url|string|null
	{
		return $this->logoForDeviceInformation();
	}
	
	/**
	 * Get button text
	 *
	 * @return	string
	 */
	public function buttonText(): string
	{
		return "core_custom_oauth_{$this->id}";
	}
	
	/**
	 * Grant Type
	 *
	 * @return	string
	 */
	protected function grantType(): string
	{
		return $this->settings['grant_type'] ?? 'authorization_code';
	}
	
	/**
	 * Should client credentials be sent as an "Authoriation" header, or as POST data?
	 *
	 * @return	string
	 */
	protected function _authenticationType(): string
	{
		return $this->settings['authentication_type'] ?? static::AUTHENTICATE_HEADER;
	}
	
	/**
	 * Get scopes to request
	 *
	 * @param array|null $additional	Any additional scopes to request
	 * @return	array
	 */
	protected function scopesToRequest( array $additional=NULL ): array
	{
		return $this->settings['scopes'];
	}
	
	/**
	 * Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	Url
	 */
	protected function authorizationEndpoint( Login $login ): Url
	{
		if ( isset( $this->settings['authorization_endpoint_secure'] ) and $this->settings['authorization_endpoint_secure'] and ( $login->type === Login::LOGIN_ACP or $login->type === Login::LOGIN_REAUTHENTICATE ) )
		{
			return Url::external( $this->settings['authorization_endpoint_secure'] );
		}
		
		return Url::external( $this->settings['authorization_endpoint'] );
	}
	
	/**
	 * Token Endpoint
	 *
	 * @return	Url
	 */
	protected function tokenEndpoint(): Url
	{
		return Url::external( $this->settings['token_endpoint'] );
	}
	
	/**
	 * Get authenticated user's identifier (may not be a number)
	 *
	 * @param string $accessToken	Access Token
	 * @return	string|null
	 */
	protected function authenticatedUserId( string $accessToken ): ?string
	{		
		if ( $userId = static::getValueFromArray( $this->_userData( $accessToken, $this->settings['uid_field'] ), $this->settings['uid_field'] ) )
		{
			return $userId;
		}
		throw new \Exception;
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
		if ( isset( $this->settings['name_field'] ) and $this->settings['name_field'] and $username = static::getValueFromArray( $this->_userData( $accessToken, $this->settings['name_field'] ), $this->settings['name_field'] ) )
		{
			return $username;
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
		if ( isset( $this->settings['email_field'] ) and $this->settings['email_field'] and $email = static::getValueFromArray( $this->_userData( $accessToken, $this->settings['email_field'] ), $this->settings['email_field'] ) )
		{
			return $email;
		}
		return NULL;
	}
	
	/**
	 * Get user's profile photo
	 * May return NULL if server doesn't support this
	 *
	 * @param	Member	$member	Member
	 * @return	Url|NULL
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	DomainException		General error where it is safe to show a message to the user
	 * @throws	RuntimeException		Unexpected error from service
	 */
	public function userProfilePhoto( Member $member ): ?Url
	{
		if ( isset( $this->settings['photo_field'] ) and $this->settings['photo_field'] )
		{
			if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
			{
				throw new Exception( "", Exception::INTERNAL_ERROR );
			}
						
			if ( $photo = static::getValueFromArray( $this->_userData( $link['token_access_token'], $this->settings['photo_field'] ), $this->settings['photo_field'] ) )
			{
				return Url::external( $photo );
			}
		}
		
		return NULL;
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
			throw new Exception( "", Exception::INTERNAL_ERROR );
		}
		
		return $this->authenticatedUserName( $link['token_access_token'] );
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
		
		if ( isset( $this->settings['email_field'] ) and $this->settings['email_field'] and ( !isset( $this->settings['update_email_changes'] ) or $this->settings['update_email_changes'] === 'optional' ) )
		{
			$return[] = 'email';
		}
		
		if ( isset( $this->settings['name_field'] ) and $this->settings['name_field'] and isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] === 'optional' )
		{
			$return[] = 'name';
		}
		
		if ( isset( $this->settings['photo_field'] ) and $this->settings['photo_field'] )
		{
			$return[] = 'photo';
		}
		
		return $return;
	}
	
	/**
	 * @brief	Cached user data
	 */
	protected array $_cachedUserData = array();

	/**
	 * Get user data
	 *
	 * @param string $accessToken Access Token
	 * @param string|null $expectedParam
	 * @return array
	 */
	protected function _userData( string $accessToken, ?string $expectedParam = NULL ) : array
	{
		if ( !isset( $this->_cachedUserData[ $accessToken ] ) )
		{
			/* Try the most sensible way first */
			$response = Url::external( $this->settings['user_endpoint'] )->request()
				->setHeaders( array(
					'Authorization' => "Bearer {$accessToken}"
				) )
				->get()
				->decodeJson();
			
			/* Check if we got what we were expecting. If we didn't, try sending the access token in the query string.
				While the spec discourages this usage, it is still valid and some providers may require it */ 
			if ( $expectedParam !== NULL )
			{
				if ( static::getValueFromArray( $response, $expectedParam ) === NULL )
				{
					$response = Url::external( $this->settings['user_endpoint'] )->setQueryString( 'access_token', $accessToken )->request()
						->get()
						->decodeJson();
				}
			}

			/* Check for any errors */
			if ( $response === NULL OR static::getValueFromArray( $response, $this->settings['uid_field'] ) === NULL )
			{
				Log::log( print_r( $response, TRUE ), 'oauth_custom' );
				throw new Exception( 'generic_error', Exception::INTERNAL_ERROR );
			}

			/* Set */						
			$this->_cachedUserData[ $accessToken ] = $response;
		}
		return $this->_cachedUserData[ $accessToken ];
	}
	
	/**
	 * Get value from an array
	 *
	 * @param array $array	The array with the data
	 * @param string $key	The key using[square][brackets]
	 * @return	mixed
	 */
	protected static function getValueFromArray( array $array, string $key ): mixed
	{
		while ( $pos = mb_strpos( $key, '[' ) )
		{
			preg_match( '/^(.+?)\[([^\]]+?)?\](.*)?$/', $key, $matches );
			
			if ( !array_key_exists( $matches[1], $array ) )
			{
				return NULL;
			}
				
			$array = $array[ $matches[1] ];
			$key = $matches[2] . $matches[3];
		}
		
		if ( !isset( $array[ $key ] ) )
		{
			return NULL;
		}
				
		return $array[ $key ];
	}
	
	/**
	 * Forgot Password URL
	 *
	 * @return	Url|NULL
	 */
	public function forgotPasswordUrl(): ?Url
	{
		return ( isset( $this->settings['forgot_password_url'] ) and $this->settings['forgot_password_url'] ) ? Url::external( $this->settings['forgot_password_url'] ) : NULL;
	}
}