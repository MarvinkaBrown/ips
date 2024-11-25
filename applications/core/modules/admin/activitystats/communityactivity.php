<?php
/**
 * @brief		Community Activity
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Mar 2017
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Activity
 */
class communityactivity extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'communityactivity_manage' );
		parent::execute();
	}

	/**
	 * Show a graph of user activity
	 *
	 * @return	void
	 * @note	Activity includes posting, following, reacting
	 */
	protected function manage() : void
	{
		$chart = Chart::loadFromExtension( 'core', 'CommunityActivity' )->getChart( Url::internal( "app=core&module=activitystats&controller=communityactivity" ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
			return;
		}
	
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_activitystats_communityactivity');
		Output::i()->output = Theme::i()->getTemplate( 'stats' )->activitymessage();
		Output::i()->output .= $chart;
	}
}