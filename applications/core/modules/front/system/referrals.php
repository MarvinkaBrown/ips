<?php
/**
 * @brief		referrals
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Aug 2019
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Http\Url\Exception;
use IPS\Http\Url\Internal;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Incoming Referrals
 *
 * Deprecated controller maintained for backwards compatibility with old referral links
 */
class referrals extends Controller
{
	/**
	 * Handle Referral
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Request::i()->setCookie( 'referred_by', intval( Request::i()->id ), DateTime::create()->add( new DateInterval( 'P1Y' ) ) );

		try
		{
			$target = Request::i()->direct ? Url::createFromString( base64_decode( Request::i()->direct ) ) : Url::baseUrl();
		}
		catch( Exception $e )
		{
			$target = NULL;
		}

		if ( $target instanceof Internal )
		{
			Output::i()->redirect( $target );
		}
		else
		{
			Output::i()->redirect( Settings::i()->base_url );
		}
	}
}