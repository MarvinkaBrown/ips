<?php
/**
 * @brief		Dashboard extension: Income
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Sep 2014
 */

namespace IPS\nexus\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\DashboardAbstract;
use IPS\Helpers\Chart;
use IPS\Member;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dashboard extension: Income
 */
class Income extends DashboardAbstract
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView(): bool
	{
		return Member::loggedIn()->hasAcpRestriction( 'nexus' , 'transactions', 'transactions_manage' );
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock(): string
	{
		$chart = new Chart;
		
		$chart->addHeader( "Day", 'date' );
		foreach ( Money::currencies() as $currency )
		{
			$chart->addHeader( $currency, 'number' );
		}
		
		$thirtyDaysAgo = DateTime::create()->sub( new DateInterval('P30D') );
				
		$results = array();
		foreach( Db::i()->select( "t_currency, DATE_FORMAT( FROM_UNIXTIME( t_date ), '%e %c %Y' ) AS date, SUM(t_amount)-SUM(t_partial_refund) AS amount", 'nexus_transactions', array( 't_date>? AND (t_status=? OR t_status=?) AND t_method>0', $thirtyDaysAgo->getTimestamp(), Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED ), NULL, NULL, array( 't_currency', 'date' ) ) as $result )
		{
			$results[ $result['date'] ][ $result['t_currency'] ] = $result['amount'];
		}
				
		$monthAndYear = date( 'n' ) . ' ' . date( 'Y' );
		foreach ( range( 30, 0 ) as $daysAgo )
		{
			$datetime = new DateTime;
			$datetime->setTime( 0, 0 );
			$datetime->sub( new DateInterval( 'P' . $daysAgo . 'D' ) );
			$resultString = $datetime->format('j n Y');
			
			if ( isset( $results[ $resultString ] ) )
			{
				$row = array( $datetime );
				
				foreach ( Money::currencies() as $currency )
				{
					if ( !isset( $results[ $resultString ][ $currency ] ) )
					{
						$row[] = 0;
					}
					else
					{
						$row[] = $results[ $resultString ][ $currency ];
					}
				}
				
				$chart->addRow( $row );
			}
			else
			{
				$row = array( $datetime );
				foreach ( Money::currencies() as $currency )
				{
					$row[] = 0;
				}
				$chart->addRow( $row );
			}
		}
		
		return $chart->render( 'AreaChart', array(
			'backgroundColor' 	=> '#ffffff',
			'colors'			=> array( '#10967e' ),
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4,
		) );
	}
}