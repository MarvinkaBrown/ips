<?php
/**
 * @brief		Cookie Policy
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Dec 2017
 */

namespace IPS\core\modules\front\system;

use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Http\Url\Exception;
use IPS\Http\Url\Internal;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Cookie Information Page
 */
class cookies extends Controller
{
	public function execute(): void
	{
		parent::execute();

		Output::setCacheTime( false );
	}

	/**
	 * Cookie Information Page
	 *
	 * @return	void
	 */
	protected function manage(): void
	{
		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=cookies', NULL, 'cookies' ), array(), 'loc_viewing_cookie_policy' );

		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('cookies_about') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('cookies_about');
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->metaTags['robots'] = 'noindex';
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->output = Theme::i()->getTemplate( 'system' )->cookies();

	}

	/**
	 * Opt out of optional cookies
	 *
	 * @return void
	 */
	protected function cookieConsentToggle(): void
	{
		Session::i()->csrfCheck();
		Member::loggedIn()->setAllowOptionalCookies( (bool) Request::i()->status );
		if ( isset( Request::i()->ref ) )
		{
			try
			{
				$url = Url::createFromString( base64_decode( Request::i()->ref ) );
			}
			catch( Exception $e )
			{
				$url = NULL;
			}

			if ( $url instanceof Internal and !$url->openRedirect() )
			{
				Output::i()->redirect( $url );
			}
		}
		Output::i()->redirect( Url::internal('') );
	}
}