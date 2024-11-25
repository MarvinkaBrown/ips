<?php
/**
 * @brief		Wordpress Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Oct 2017
 */

namespace IPS\Login\Handler\OAuth2;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\File;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Login\Handler\OAuth2;
use IPS\Member;
use RuntimeException;
use function count;
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
 * Wordpress oAuth Login Handler
 */
class Wordpress extends OAuth2
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
		return 'login_handler_wordpress';
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
		$return[] = array( 'login_handler_wordpress_settings', 'login_handler_wordpress_oauth_info' );

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

		$endpointValidation = function( $val )
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
		$return['wordpress_url'] = new \IPS\Helpers\Form\Url( 'wordpress_url', $this->settings['wordpress_url'] ?? NULL, NULL, array( 'placeholder' => 'https://example.com/' ), $endpointValidation, NULL, NULL, 'wordpress_url' );

		$return[] = 'login_handler_oauth_ui';
		$return['button_color'] = new Color( 'oauth_custom_button_color', $this->settings['button_color'] ?? '#23282d', NULL, array(), NULL, NULL, NULL, 'button_color' );
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
		$return['wordpress_url'] = (string) $return['wordpress_url'];
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
		return 'wordpress';
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
		return $this->logoForDeviceInformation() ?: 'wordpress';
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
	 * Should client credentials be sent as an "Authoriation" header, or as POST data?
	 *
	 * @return	string
	 */
	protected function _authenticationType(): string
	{
		return static::AUTHENTICATE_POST; // Just because it's possible their server isn't configured to accept HTTP Authorization whereas we know this will always work
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
		return array( "profile" );
	}

	/**
	 * Authorization Endpoint
	 *
	 * @param	Login	$login	The login object
	 * @return	Url
	 */
	protected function authorizationEndpoint( Login $login ): Url
	{
		return Url::external( rtrim( $this->settings['wordpress_url'], "/" ) . "/wp-json/moserver/authorize" );
	}

	/**
	 * Token Endpoint
	 *
	 * @return	Url
	 */
	protected function tokenEndpoint(): Url
	{
		return Url::external( rtrim( $this->settings['wordpress_url'], "/" ) . "/wp-json/moserver/token" );
	}

	/**
	 * Get authenticated user's identifier (may not be a number)
	 *
	 * @param string $accessToken Access Token
	 * @return string|null
	 */
	protected function authenticatedUserId( string $accessToken ): ?string
	{
		if ( isset( $this->_userData( $accessToken )[ 'username' ] ) )
		{
			return $this->_userData( $accessToken )[ 'username' ];
		}
		return NULL;
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
		if ( isset( $this->_userData( $accessToken )[ 'display_name' ] ) )
		{
			return $this->_userData( $accessToken )[ 'display_name' ];
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
		if ( isset( $this->_userData( $accessToken )[ 'email' ] ) )
		{
			return $this->_userData( $accessToken )[ 'email' ];
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
		if ( !( $link = $this->_link( $member ) ) or ( $link['token_expires'] and $link['token_expires'] < time() ) )
		{
			throw new Exception( "", Exception::INTERNAL_ERROR );
		}
		
		if ( isset( $this->_userData( $link['token_access_token'] )[ 'avatar' ] ) )
		{
			$url = preg_replace( '/^.*src=[\'"](.+?)[\'"].*$/i', '$1', $this->_userData( $link['token_access_token'] )[ 'avatar' ] );

			try
			{
				return Url::external( $url );
			}
			catch ( \Exception $e ) { }
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

		if ( ( !isset( $this->settings['update_email_changes'] ) or $this->settings['update_email_changes'] === 'optional' ) )
		{
			$return[] = 'email';
		}

		if ( isset( $this->settings['update_name_changes'] ) and $this->settings['update_name_changes'] === 'optional' )
		{
			$return[] = 'name';
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
	 * @param	string	$accessToken	Access Token
	 * @throws	Exception	The token is invalid and the user needs to reauthenticate
	 * @throws	RuntimeException		Unexpected error from service
	 */
	protected function _userData( string $accessToken ): array
	{
		if ( !isset( $this->_cachedUserData[ $accessToken ] ) )
		{
			$data = Url::external( rtrim( $this->settings['wordpress_url'], "/" ) . "/wp-json/moserver/resource" )
				->request()
				->setHeaders( array(
					'Authorization' => "Bearer {$accessToken}"
				) )
				->get()
				->decodeJson();
			
			/* As with the Custom handler - if we did not get expected data, try using the query string method. */
			if ( !count( $data ) )
			{
				$data = Url::external( rtrim( $this->settings['wordpress_url'], "/" ) . "/wp-json/moserver/resource" )
					->setQueryString( 'access_token', $accessToken )
					->request()
					->get()
					->decodeJson();
			}
			
			$this->_cachedUserData[ $accessToken ] = $data;
		}
		return $this->_cachedUserData[ $accessToken ];
	}

	/**
	 * Forgot Password URL
	 *
	 * @return	Url|NULL
	 */
	public function forgotPasswordUrl(): ?Url
	{
		return Url::external( rtrim( $this->settings['wordpress_url'], "/" ) . "/wp-login.php?action=lostpassword" );
	}
}