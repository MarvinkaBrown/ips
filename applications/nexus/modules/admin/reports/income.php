<?php


/**
 * @brief		Income Report
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		14 Aug 2014
 */

namespace IPS\nexus\modules\admin\reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

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
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Income Report
 */
class income extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'income_manage' );
		parent::execute();
	}

	/**
	 * View Report
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$currencies = Money::currencies();

		$tabs = array( 'totals' => 'nexus_report_income_totals' );

		if( count( $currencies ) == 1 )
		{
			$tabs['members'] = 'nexus_report_income_members';
		}
		else
		{
			foreach ( $currencies as $currency )
			{
				$tabs[ 'members_' . $currency ] = Member::loggedIn()->language()->addToStack( 'nexus_report_income_by_member', FALSE, array( 'sprintf' => array( $currency ) ) );
			}
		}

		foreach ( $currencies as $currency )
		{
			$tabs[ $currency ] = Member::loggedIn()->language()->addToStack( 'nexus_report_income_by_method', FALSE, array( 'sprintf' => array( $currency ) ) );
		}

		Request::i()->tab ??= 'totals';
		$activeTab = ( array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'totals';

		$extension = Chart::loadFromExtension( 'nexus', 'Income' );
		$chart = $extension->getChart( Url::internal( 'app=nexus&module=reports&controller=income&tab=' . $activeTab ) );
		$extension->setExtra( $chart, $activeTab );

		/* @var Dynamic $chart */
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{	
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_reports_income');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=nexus&module=reports&controller=income" ) );
		}
	}
}