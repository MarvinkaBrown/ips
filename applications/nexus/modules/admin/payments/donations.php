<?php
/**
 * @brief		donations
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		17 Jun 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Donation\Goal;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\Output;
use IPS\Theme;
use IPS\Widget;
use OutOfRangeException;
use function count;
use function defined;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NODE;
use const IPS\Helpers\Table\SEARCH_NUMERIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
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
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'donations_manage' );
		parent::execute();
	}

	/**
	 * View Donations
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		if( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'donationgoals_manage' ) )
		{
			Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'cog',
				'title'	=> 'donation_goals',
				'link'	=> Url::internal( "app=nexus&module=payments&controller=donationgoals" )
			);
		}
		
		$table = new Db( 'nexus_donate_logs', Url::internal('app=nexus&module=payments&controller=donations') );
		
		$table->include = array( 'dl_goal', 'dl_amount', 'dl_member', 'dl_invoice', 'dl_date' );
		$table->parsers = array(
			'dl_goal'	=> function( $val )
			{
				try
				{
					return Goal::load( $val )->_title;
				}
				catch ( Exception )
				{
					return NULL;
				}
			},
			'dl_member'	=> function ( $val )
			{
				return Theme::i()->getTemplate('global')->userLink( Member::load( $val ) );
			},
			'dl_amount'	=> function( $val, $row )
			{
				try
				{
					return (string) new Money( $val, Goal::load( $row['dl_goal'] )->currency );
				}
				catch ( Exception )
				{
					return $val;
				}
			},
			'dl_invoice'	=> function( $val )
			{
				try
				{
					return Theme::i()->getTemplate('invoices')->link( Invoice::load( $val ), TRUE );
				}
				catch ( OutOfRangeException )
				{
					return '';
				}
			},
			'dl_date'	=> function( $val )
			{
				return DateTime::ts( $val );
			}
		);
		
		foreach ( Goal::roots() as $goal )
		{
			$table->filters[ "nexus_donategoal_{$goal->_id}" ] = "dl_goal={$goal->_id}";
		}
		$table->advancedSearch = array(
			'dl_goal'	=> array( SEARCH_NODE, array( 'class' => '\IPS\nexus\Donation\Goal' ) ),
			'dl_member'	=> SEARCH_MEMBER,
			'dl_amount'	=> SEARCH_NUMERIC,
			'dl_date'	=> SEARCH_DATE_RANGE,
		);

		$message = '';
		if ( ! count( Widget::usedWhere( Application::load('nexus'), 'donations' ) ) )
		{
			/* Nudge the admin to add the sidebar block */
			$message = Theme::i()->getTemplate('forms', 'core' )->blurb( 'commerce_donation_goals_widget_not_in_use' );
		}

		Output::i()->output = $message . $table;
		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__nexus_payments_donations');
	}
}