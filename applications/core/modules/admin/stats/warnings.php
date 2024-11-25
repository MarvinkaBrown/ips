<?php
/**
 * @brief		warnings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		20 Sep 2021
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
 * warnings
 */
class warnings extends Controller
{
	
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'warnings_manage' );
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
			'reason'			=> 'stats_warnings_reason',
			'suspended'		=> 'stats_warnings_suspended',
		);

		/* Make sure tab is set, otherwise saved charts may not show up when loading the page. */
		Request::i()->tab ??= 'reason';
		$activeTab	= ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'reason';

		if ( $activeTab === 'reason' )
		{
			$chart = Chart::loadFromExtension( 'core', 'WarningReasons' )->getChart( Url::internal( 'app=core&module=stats&controller=warnings&tab=reason' ) );
		}
		else if ( $activeTab === 'suspended' )
		{
			$chart = Chart::loadFromExtension( 'core', 'WarningSuspended' )->getChart( Url::internal( 'app=core&module=stats&controller=warnings&tab=suspended' ) );
		}
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_warnings');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=core&module=stats&controller=warnings" ), 'tab', '', 'i-padding_3' );
		}
	}
	
}