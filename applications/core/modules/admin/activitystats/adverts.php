<?php
/**
 * @brief		adverts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		03 Apr 2023
 */

namespace IPS\core\modules\admin\activitystats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * adverts
 */
class adverts extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'adverts_manage' );
		parent::execute();
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$tabs		= array(
			'impressions'		=> 'core_adverts_stats_impressions',
			'clicks'		=> 'core_adverts_stats_clicks',
		);

		$activeTab	= ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'impressions';

		if ( $activeTab === 'impressions' )
		{
			$chart = new Database( Url::internal( 'app=core&module=activitystats&controller=adverts&tab=impressions' ), 'core_statistics', 'time', '', array(
				'isStacked' => FALSE,
				'backgroundColor' => '#ffffff',
				'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
				'lineWidth' => 1,
				'areaOpacity' => 0.4
			),
				'AreaChart',
				'daily',
				array('start' => 0, 'end' => 0),
				array(),
				$activeTab
			);

			$chart->availableTypes = array( 'Table', 'AreaChart', 'ColumnChart', 'BarChart');

			$chart->groupBy = 'value_1';
			$chart->title = Member::loggedIn()->language()->addToStack('core_adverts_stats_impressions');
			$chart->where[] = array( "type=?", 'advert' );

			foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_advertisements' ), '\IPS\core\Advertisement' ) as $advert )
			{
				$chart->addSeries( Member::loggedIn()->language()->addToStack( "core_advert_{$advert->id}" ), 'number', 'SUM(value_2)', TRUE, $advert->id );
			}
		}
		else
		{
			$chart = new Database( Url::internal( 'app=core&module=activitystats&controller=adverts&tab=clicks' ), 'core_statistics', 'time', '', array(
				'isStacked' => FALSE,
				'backgroundColor' => '#ffffff',
				'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
				'lineWidth' => 1,
				'areaOpacity' => 0.4
			),
				'AreaChart',
				'daily',
				array('start' => 0, 'end' => 0),
				array(),
				$activeTab
			);

			$chart->availableTypes = array( 'Table', 'AreaChart', 'ColumnChart', 'BarChart');

			$chart->groupBy = 'value_1';
			$chart->title = Member::loggedIn()->language()->addToStack('core_adverts_stats_impressions');
			$chart->where[] = array( "type=?", 'advert' );

			foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_advertisements' ), '\IPS\core\Advertisement' ) as $advert )
			{
				$chart->addSeries( Member::loggedIn()->language()->addToStack( "core_advert_{$advert->id}" ), 'number', 'SUM(value_3)', TRUE, $advert->id );
			}
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $chart;
		}
		else
		{
			Output::i()->title	= Member::loggedIn()->language()->addToStack('core_adverts_stats');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, Url::internal( "app=core&module=activitystats&controller=adverts" ), 'tab', '', 'i-padding_3' );
		}

	}
	
	// Create new methods with the same name as the 'do' parameter which should execute it
}