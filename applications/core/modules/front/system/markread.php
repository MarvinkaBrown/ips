<?php
/**
 * @brief		Mark site as read
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 May 2014
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mark site as read
 */
class markread extends Controller
{
	/**
	 * Mark site as read
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Session::i()->csrfCheck();
		
		if ( Member::loggedIn()->member_id )
		{
			Member::loggedIn()->markAllAsRead();
		}
		
		if( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			/* Don't redirect to an external domain unless explicitly requested, and don't redirect back to ACP */
			$redirectTo = Request::i()->referrer( FALSE, FALSE, 'front' ) ?: Url::internal( '' );

			if ( $redirectTo === NULL )
			{
				$redirectTo = Url::internal( '' );
			}

			Output::i()->redirect( $redirectTo, 'core_site_marked_as_read' );
		}
	}
}