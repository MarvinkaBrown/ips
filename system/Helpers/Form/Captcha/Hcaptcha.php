<?php
/**
 * @brief		hCaptcha
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Mai 2022
 */

namespace IPS\Helpers\Form\Captcha;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use RuntimeException;
use function defined;
use function mb_strlen;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * hCaptcha
 */
class Hcaptcha implements CaptchaInterface
{
	/**
	 *  Does this CAPTCHA service support being added in a modal?
	 */
	public static bool $supportsModal = FALSE;

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
		Output::i()->jsFilesAsync[] = "https://js.hcaptcha.com/1/api.js";
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->hCaptcha( Settings::i()->hcaptcha_sitekey );
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
			$response = Url::external( 'https://hcaptcha.com/siteverify' )->request()->post( array(
				'secret'		=> Settings::i()->hcaptcha_secret,
				'response'		=> trim( Request::i()->__get('h-captcha-response') ),
				'remoteip'		=> Request::i()->ipAddress(),
			) )->decodeJson();

			$hostname = Url::internal('')->data[ Url::COMPONENT_HOST ];
			return ( ( $response['success'] ) and ( $response['hostname'] === mb_substr( $hostname, mb_strlen( $hostname ) - mb_strlen( $response['hostname'] ) ) ) );
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