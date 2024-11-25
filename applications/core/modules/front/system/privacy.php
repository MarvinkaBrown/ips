<?php
/**
 * @brief		Privacy Policy
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Jun 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
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
 * Privacy Policy
 */
class privacy extends Controller
{
	/**
	 * Privacy Policy
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if ( Settings::i()->privacy_type == "none" )
		{
			Output::i()->error( 'node_error', '2C381/1', 404, 'privacy_set_to_none_acp' );
		}
		
		if ( Settings::i()->privacy_type == "external" )
		{
			if ( $url = Settings::i()->privacy_link )
			{
				Output::i()->redirect( Url::external( $url ) );
			} 
			else 
			{
				Output::i()->error( 'node_error', '2C381/1', 404, 'privacy_link_not_set_acp' );
			}
		}

		$subprocessors = array();
		/* Work out the main subprocessors that the user has no direct choice over */
		if ( Settings::i()->privacy_show_processors )
		{
			foreach( Application::enabledApplications() as $app )
			{
				$subprocessors = array_merge( $subprocessors, $app->privacyPolicyThirdParties() );
			}
		}
		
		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=privacy', NULL, 'privacy' ), array(), 'loc_viewing_privacy_policy' );
		
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('privacy') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('privacy');
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->output = Theme::i()->getTemplate( 'system' )->privacy( $subprocessors );
	}
}