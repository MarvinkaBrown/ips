<?php
/**
 * @brief		Guidelines
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Sept 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Guidelines
 */
class guidelines extends Controller
{
	/**
	 * Guidelines
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( Settings::i()->gl_type == "none" )
		{
			Output::i()->error( 'node_error', '2C380/1', 404, 'guidelines_set_to_none_admin' );
		}

		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=guidelines', NULL, 'guidelines' ), array(), 'loc_viewing_guidelines' );

		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('guidelines') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('guidelines');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->guidelines( Settings::i()->gl_guidelines );
	}
}