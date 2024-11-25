<?php
/**
 * @brief		Dashboard extension: Member Stats
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Jul 2013
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\DashboardAbstract;
use IPS\Helpers\Chart;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Member Stats
 */
class MemberStats extends DashboardAbstract
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView(): bool
	{
		return TRUE;
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		$stats = NULL;
		
		/* check the cache */
		try
		{
			$stats = Store::i()->acpWidget_memberStats;
			
			if ( ! isset( $stats['_cached'] ) or $stats['_cached'] < time() - ( 60 * 30 ) )
			{
				$stats = NULL;
			}
		}
		catch( Exception $ex ) { }

		if ( $stats === NULL )
		{
			$stats = array();
			
			/* fetch only successful registered members ; if this needs to be changed, please review the other areas where we have the name<>? AND email<>? condition */
			$where = array( array( 'completed=?', true ) );
	
			/* Member count */
			$stats['member_count'] = Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
			
			/* Opt in members */
			$where[] = 'allow_admin_mails=1';
			$stats['member_optin'] = Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
			
			$stats['_cached'] = time();
			
			/* stil here? */
			Store::i()->acpWidget_memberStats = $stats;
		}
		
		/* Init Chart */
		$chart = new Chart;
		
		/* Specify headers */
		$chart->addHeader( Member::loggedIn()->language()->get('chart_email_marketing_type'), "string" );
		$chart->addHeader( Member::loggedIn()->language()->get('chart_members'), "number" );
		
		/* Add Rows */
		$chart->addRow( array( Member::loggedIn()->language()->addToStack( 'memberStatsDashboard_optin' ), $stats['member_optin'] ) );
		$chart->addRow( array( Member::loggedIn()->language()->addToStack( 'memberStatsDashboard_optout' ), $stats['member_count'] - $stats['member_optin'] ) );
		
				
		/* Output */
		return Theme::i()->getTemplate( 'dashboard' )->memberStats( $stats, $chart->render( 'PieChart', array(
			'backgroundColor' 	=> '#ffffff',
			'pieHole' => 0.4,
			'colors' => array( '#44af94', '#cc535f' ),
			'chartArea' => array( 
				'width' =>"90%", 
				'height' => "90%" 
			) 
		) ) );
	}
}