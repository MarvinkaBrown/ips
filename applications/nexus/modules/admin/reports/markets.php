<?php
/**
 * @brief		Markets Report
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		14 Aug 2014
 */

namespace IPS\nexus\modules\admin\reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\core\Statistics\Chart;
use IPS\Helpers\Chart\Dynamic;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Money;
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
 * Markets Report
 */
class markets extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'markets_manage' );
		parent::execute();
	}

	/**
	 * View Chart
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs['count'] = 'nexus_report_count';
		foreach ( Money::currencies() as $currency )
		{
			$tabs[ $currency ] = Member::loggedIn()->language()->addToStack( 'nexus_report_income', FALSE, array( 'sprintf' => array( $currency ) ) );
		}

		Request::i()->tab ??= 'count';
		$activeTab = ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'count';
		$extension = Chart::loadFromExtension( 'nexus', 'Market' );
		$chart = $extension->getChart( Url::internal( "app=nexus&module=reports&controller=markets&tab={$activeTab}" ) );

		if ( $activeTab !== 'count' )
		{
			try
			{
				$extension->setCurrency( $chart, $activeTab );
			}
			catch( InvalidArgumentException ) {}
		}

		/* @var Dynamic $chart */
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{	
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_reports_markets');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=nexus&module=reports&controller=markets" ) );
		}
	}
}