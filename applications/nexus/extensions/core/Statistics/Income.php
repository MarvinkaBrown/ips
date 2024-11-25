<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Commerce
 * @since		26 Jan 2023
 */

namespace IPS\nexus\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Gateway;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Income extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'nexus_reports_income_totals';
	
	/**
	 * @brief	Identifier
	 */
	protected string $_identifier = 'totals';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database(
			$url,
			'nexus_transactions',
			't_date',
			'',
			array(
				'backgroundColor' 	=> '#ffffff',
				'colors'			=> array( '#10967e', '#ea7963', '#de6470', '#6b9dde', '#b09be4', '#eec766', '#9fc973', '#e291bf', '#55c1a6', '#5fb9da' ),
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			),
			( Request::i()->tab == 'members' ) ? 'PieChart' : 'AreaChart',
			'monthly',
			array( 'start' => 0, 'end' => 0 ),
			array(),
			$this->_identifier
		);
		$chart->setExtension( $this );
		$chart->where[] = array( '( t_status=? OR t_status=? ) AND t_method>0', Transaction::STATUS_PAID, Transaction::STATUS_PART_REFUNDED );
		
		$chart->tableInclude = array( 't_id', 't_member', 't_invoice', 't_method', 't_amount', 't_date' );
		$chart->tableParsers = array(
			't_member'	=> function( $val ) {
				return Theme::i()->getTemplate('global', 'nexus')->userLink( Member::load( $val ) );
			},
			't_method'	=> function( $val ) {
				if ( $val )
				{
					try
					{
						return Gateway::load( $val )->_title;
					}
					catch ( OutOfRangeException )
					{
						return '';
					}
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('account_credit');
				}
			},
			't_amount'	=> function( $val, $row )
			{
				return (string) new Money( $val, $row['t_currency'] );
			},
			't_invoice'	=> function( $val )
			{
				try
				{
					return Theme::i()->getTemplate('invoices', 'nexus')->link( Invoice::load( $val ) );
				}
				catch ( OutOfRangeException )
				{
					return '';
				}
			},
			't_date'	=> function( $val ) {
				return DateTime::ts( $val );
			}
		);
		
		return $chart;
	}
	
	/**
	 * Set Extra
	 *
	 * @param	Chart	$chart	Chart
	 * @param	string				$what	What we're setting.
	 * @return void
	 */
	public function setExtra( Chart $chart, string $what ) : void
	{
		$currencies = Money::currencies();
		$this->controller	= "nexus_reports_income_{$what}";
		$this->_identifier	= $what;
		
		if ( $what === 'totals' )
		{
			$chart->groupBy = 't_currency';
			
			foreach ( $currencies as $currency )
			{
				$chart->addSeries( $currency, 'number', 'SUM(t_amount)-SUM(t_partial_refund)', TRUE, $currency );
			}
		}
		elseif( mb_strpos( $what, 'members' ) === 0 )
		{
			$chart->groupBy = 't_member';

			if( count( $currencies ) === 1 )
			{
				$chart->format = array_pop( $currencies );
			}
			else
			{
				$chart->format	= mb_substr( $what, 8 );
				$chart->where[]	= array( 't_currency=?', $chart->format );
			}
			
			foreach ( Db::i()->select( 't_member, SUM(t_amount)-SUM(t_partial_refund) as _amount', 'nexus_transactions', $chart->where, '_amount DESC', array( 0, 20 ), 't_member' ) as $member )
			{
				$chart->addSeries( Member::load( $member['t_member'] )->name, 'number', 'SUM(t_amount)-SUM(t_partial_refund)', TRUE, $member['t_member'] );
			}
		}
		else
		{
			$chart->where[] = array( 't_currency=?', $what );
			$chart->groupBy = 't_method';
			$chart->format = $what;
			
			foreach ( Gateway::roots() as $gateway )
			{
				$chart->addSeries( $gateway->_title, 'number', 'SUM(t_amount)-SUM(t_partial_refund)', TRUE, $gateway->id );
			}
		}
	}
}