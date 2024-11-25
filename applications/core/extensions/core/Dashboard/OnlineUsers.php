<?php
/**
 * @brief		Dashboard extension: Online Users
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Jul 2013
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\DashboardAbstract;
use IPS\Helpers\Chart;
use IPS\Member;
use IPS\Session\Store;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Online Users
 */
class OnlineUsers extends DashboardAbstract
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
		/* Init Chart */
		$chart = new Chart;
		
		/* Specify headers */
		$chart->addHeader( Member::loggedIn()->language()->get('chart_app'), "string" );
		$chart->addHeader( Member::loggedIn()->language()->get('chart_members'), "number" );
		
		/* Add Rows */
		$online = array();
		$seen   = array();
		foreach( Store::i()->getOnlineUsers( Store::ONLINE_MEMBERS | Store::ONLINE_GUESTS, 'desc' ) as $row )
		{
			$key = ( $row['member_id'] ?: $row['id'] );
			
			if ( ! isset( $seen[ $key ] ) )
			{
				$online[ $row['current_appcomponent'] ][ $key ] = $row['id'];
				$seen[ $key ] = true;
			}
		}
		
		$total = 0;
		foreach ( $online as $app => $data )
		{
			/* Only show if the application is still installed and enabled */
			if( !Application::appIsEnabled( $app ) )
			{
				continue;
			}
			
			$total += count( $data );
			$chart->addRow( array( Member::loggedIn()->language()->addToStack( "__app_" . $app), count( $data ) ) );
		}
		
		/* Output */
		return Theme::i()->getTemplate( 'dashboard' )->onlineUsers( $online, $chart->render( 'PieChart', array(
			'backgroundColor' 	=> '#ffffff',
			'pieHole' => 0.4,
			'colors' => array( '#cc535f', '#d8624b', '#598acd', '#9a84d2', '#e4b555', '#8cb65e', '#44af94', '#4da5c8', '#acb2bb', '#676d76' ),
			'chartArea' => array( 
				'width' =>"90%", 
				'height' => "90%" 
			) 
		) ), $total );
	}
}