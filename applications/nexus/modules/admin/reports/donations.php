<?php
/**
 * @brief		donations
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		16 Mar 2023
 */

namespace IPS\nexus\modules\admin\reports;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * donations
 */
class donations extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'donations_stats_manage' );
		parent::execute();
	}

	/**
	 * View Chart
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$chart = new Database(
			Url::internal('app=nexus&module=reports&controller=donations'),
			'nexus_donate_logs',
			'dl_date',
			'',
			array(
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			)
		);

		$chart->groupBy = 'dl_goal';
		$chart->tableInclude	= array( 'dl_id', 'dl_member', 'dl_amount', 'dl_date' );
		$chart->tableParsers	= array(
			'dl_member' => function( $val ) {
				return Theme::i()->getTemplate('global', 'nexus')->userLink( Member::load( $val ) );
			},
			'dl_date'	=> function( $val ) {
				return DateTime::ts( $val );
			}
		);

		$goals = array();
		foreach ( Db::i()->select( 'd_id', 'nexus_donate_goals' ) as $goalId )
		{
			$goals[ $goalId ] = Member::loggedIn()->language()->get( 'nexus_donategoal_' . $goalId );
		}

		asort( $goals );
		foreach ( $goals as $id => $name )
		{
			$chart->addSeries( $name, 'number', 'sum(dl_amount)', TRUE, $id );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_reports_donations');
		Output::i()->output = (string) $chart;
	}
}