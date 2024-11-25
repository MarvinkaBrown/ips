<?php
/**
 * @brief		Subscribers
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		14 Feb 2018
 */

namespace IPS\nexus\modules\admin\subscriptions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription;
use IPS\nexus\Subscription\Package;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_NODE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Subscribers
 */
class subscribers extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'subscribers_manage' );
		parent::execute();
	}
	
	/**
	 * Manage 
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* If we came from the Subscription Plans page, we might have a
		package ID passed in the URL */
		$where = [];
		if( isset( Request::i()->nexus_sub_package_id ) AND Request::i()->nexus_sub_package_id )
		{
			$where[] = array( 'sub_package_id=?', Request::i()->nexus_sub_package_id );
		}

		/* Create the table */
		$table = new Db( 'nexus_member_subscriptions', Url::internal( 'app=nexus&module=subscriptions&controller=subscribers' ), $where );
		$table->joins = array(
			array(
				'select'	=> 'core_members.*',
				'from'		=> 'core_members',
				'where'		=> 'core_members.member_id=nexus_member_subscriptions.sub_member_id'
			),
			array(
				'select'	=> 'nexus_customers.cm_first_name, nexus_customers.cm_last_name',
				'from'		=> 'nexus_customers',
				'where'		=> 'core_members.member_id=nexus_customers.member_id'
			)
		);
		
		$table->include = array( 'photo', 'sub_member_id', 'cm_last_name', 'cm_first_name', 'email', 'sub_package_id', 'sub_active', 'sub_start', 'sub_expire');
		$table->noSort = array( 'photo');
		$table->sortBy = $table->sortBy ?: 'sub_start';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		$table->langPrefix = 'nexus_';
		
		$table->quickSearch = 'email';
		
		$table->parsers = array(
			'photo'	=> function( $val, $row )
			{
				return Theme::i()->getTemplate('customers')->rowPhoto( Member::constructFromData( $row ) );
			},
			'sub_member_id' => function( $val ) {
				return Theme::i()->getTemplate('global', 'nexus')->userLink( Member::load( $val ) );
			},
			'sub_package_id' => function( $val ) {
				try
				{
					return Theme::i()->getTemplate('subscription', 'nexus')->packageLink( Package::load( $val ) );
				}
				catch( OutOfRangeException ) { }
				return '';
			},
			'sub_start'	=> function( $val ) {
				return DateTime::ts( $val );
			},
			'sub_expire'	=> function( $val, $row ) {
				return $val ? Subscription::constructFromData( $row )->_expire : '';
			},
			'sub_active'    => function( $val ) {
				return Theme::i()->getTemplate('subscription', 'nexus')->status( $val );
			}
		);
		
		$table->rowButtons = function( $row ) {
			$return = array();
			
			if ( $row['sub_purchase_id'] )
			{
				$return['purchase']	= array(
					'title'	=> 'nexus_subs_view_purchase',
					'icon'	=> 'search',
					'link'	=> Url::internal( "app=nexus&module=subscriptions&controller=subscribers&do=findPurchase&id=" . $row['sub_id'] )
				);
			}
			
			return $return;
		};
			
		$table->filters = array(
			'nexus_subs_filter_active'	 => array( 'sub_active=1' ),
			'nexus_subs_filter_inactive' => array( 'sub_active=0' ),
			'nexus_subs_filter_renews'	 => array( 'sub_active=1 and sub_renews=1' )
		);
		
		$table->advancedSearch = array(
			'cm_first_name'		=> SEARCH_CONTAINS_TEXT,
			'cm_last_name'		=> SEARCH_CONTAINS_TEXT,
			'email'				=> SEARCH_CONTAINS_TEXT,
			'name'				=> SEARCH_CONTAINS_TEXT,
			'sub_expire'	 => SEARCH_DATE_RANGE,
			'sub_start'	     => SEARCH_DATE_RANGE,
			'sub_package_id' => array( SEARCH_NODE, array(
				'class'				=> '\IPS\nexus\Subscription\Package',
				'zeroVal'			=> 'any'
			) ),
		);
		
		/* Breadcrumb? */
		if ( isset( Request::i()->nexus_sub_package_id ) )
		{
			try
			{
				$package = Package::load( Request::i()->nexus_sub_package_id );
				Output::i()->breadcrumb[] = array( Url::internal( "app=nexus&module=subscriptions&controller=subscriptions&id=" . $package->id ), $package->_title );
			}
			catch( OutOfRangeException ) { }
		}
		
		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack('r__subscribers');
		Output::i()->output = (string) $table;
	}
	
	/**
	 * Find an purchase and redirect to it.
	 *
	 * @return	void
	 */
	protected function findPurchase() : void
	{
		try
		{
			$sub = Subscription::load( Request::i()->id );
			$purchase = Purchase::load( $sub->purchase_id );
			Output::i()->redirect( $purchase->acpUrl() );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'nexus_sub_no_purchase', '2X378/2', 404, '' );
		}
	}
	
}