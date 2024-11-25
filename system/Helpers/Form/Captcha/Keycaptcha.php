<?php
/**
 * @brief		keyCAPTCHA
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Apr 2013
 */

namespace IPS\Helpers\Form\Captcha;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Login;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * keyCAPTCHA
 */
class Keycaptcha implements CaptchaInterface
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
		$explodedKey	= explode( '0', Settings::i()->keycaptcha_privatekey, 2 );
		$uniq			= md5( mt_rand() );
		$sign			= md5( $uniq . Request::i()->ipAddress . $explodedKey[0] );
		$sign2			= md5( $uniq . $explodedKey[0] );
		
		return Theme::i()->getTemplate( 'forms', 'core', 'global' )->captchaKeycaptcha( $explodedKey[1], $uniq, $sign, $sign2 );
	}
	
	/**
	 * Verify
	 *
	 * @return	bool|null	TRUE/FALSE indicate if the test passed or not. NULL indicates the test failed, but the captcha system will display an error so we don't have to.
	 */
	public function verify(): ?bool
	{
		$explodedResponse	= explode( '|', Request::i()->keycaptcha );
		$explodedKey		= explode( '0', Settings::i()->keycaptcha_privatekey );
	
		if( Login::compareHashes( $explodedResponse[0], md5( 'accept' . $explodedResponse[1] . $explodedKey[0] . $explodedResponse[2] ) ) )
		{
			if( (string) Url::external( $explodedResponse[2] )->request()->get() === '1' )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

}