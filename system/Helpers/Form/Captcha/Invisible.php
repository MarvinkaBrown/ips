<?php
/**
 * @brief		Invisible reCAPTCHA
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 June 2017
 */

namespace IPS\Helpers\Form\Captcha;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invisible reCAPTCHA
 */
class Invisible implements CaptchaInterface
{
	/**
	 *  Does this CAPTCHA service support being added in a modal?
	 */
	public static bool $supportsModal = TRUE;
	
	/**
	 * @brief	Error
	 */
	protected ?string $error;
	
	/**
	 * Display
	 *
	 * @return	string
	 */
	public function getHtml(): string
	{		
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->captchaInvisible( Settings::i()->recaptcha2_public_key, preg_replace( '/^(.+?)\..*$/', '$1', Member::loggedIn()->language()->short ) );
	}
	
	/**
	 * Display
	 *
	 * @return	string
	 */
	public function rowHtml(): string
	{
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->captchaInvisible( Settings::i()->recaptcha2_public_key, preg_replace( '/^(.+?)\..*$/', '$1', Member::loggedIn()->language()->short ), TRUE );
	}
		
	/**
	 * Verify
	 *
	 * @return	bool|null	TRUE/FALSE indicate if the test passed or not. NULL indicates the test failed, but the captcha system will display an error so we don't have to.
	 */
	public function verify(): ?bool
	{		
		try
		{
			$response = Url::external( 'https://www.google.com/recaptcha/api/siteverify' )->request()->post( array(
				'secret'		=> Settings::i()->recaptcha2_private_key,
				'response'		=> trim( Request::i()->__get('g-recaptcha-response') ),
				'remoteip'		=> Request::i()->ipAddress(),
			) )->decodeJson();
						
			return ( ( $response['success'] ) and ( $response['hostname'] === Url::internal('')->data[ Url::COMPONENT_HOST ] ) );
		}
		catch( RuntimeException $e )
		{
			if( $e->getMessage() == 'BAD_JSON' )
			{
				return FALSE;
			}
			else
			{
				throw $e;
			}
		}
	}

}