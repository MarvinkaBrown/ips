<?php
/**
 * @brief		Terms of Use
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Sept 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

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

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Terms of Use
 */
class terms extends Controller
{
	/**
	 * Terms of Use
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=terms', NULL, 'terms' ), array(), 'loc_viewing_reg_terms' );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('reg_terms');
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->output = Theme::i()->getTemplate( 'system' )->terms();
	}
	
	/**
	 * Dismiss Terms
	 *
	 * @return	void
	 */
	protected function dismiss() : void
	{
		Session::i()->csrfCheck();
		
		Request::i()->setCookie( 'guestTermsDismissed', 1, NULL, FALSE );

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'message' => Member::loggedIn()->language()->addToStack( 'terms_dismissed' ) ) );
		}
		else
		{
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
					Output::i()->redirect( $url, 'terms_dismissed' );
				}
			}
			
			/* Still here? Just redirect to the index */
			Output::i()->redirect( Url::internal( '' ), 'terms_dismissed' );
		}
	}
}