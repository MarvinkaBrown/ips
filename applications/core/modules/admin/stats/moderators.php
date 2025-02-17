<?php
/**
 * @brief		moderators
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		22 Sep 2021
 */

namespace IPS\core\modules\admin\stats;

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
 * moderators
 */
class moderators extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'moderatorstats_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs = array(
			'users'				=> 'stats_moderators_users',
			'actions'			=> 'stats_moderators_actions'
		);

		Request::i()->tab ??= 'users';
		$activeTab = ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'users';

		$output = match( $activeTab ) {
			'users'				=> Chart::loadFromExtension( 'core', 'Moderators' )->getChart( Url::internal( 'app=core&module=stats&controller=moderators&tab=users' ) ),
			'actions'			=> Chart::loadFromExtension( 'core', 'ModeratorsByAction' )->getChart( Url::internal( "app=core&module=stats&controller=moderators&tab=actions" ) )
		};

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $output;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_moderators');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $output, Url::internal( "app=core&module=stats&controller=moderators" ) );
		}
	}
}