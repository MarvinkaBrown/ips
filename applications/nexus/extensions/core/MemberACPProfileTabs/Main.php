<?php
/**
 * @brief		ACP Member Profile: Customer Tab
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\nexus\extensions\core\MemberACPProfileTabs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\MainTab;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Customer Tab
 */
class Main extends MainTab
{
	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		$this->member = Customer::load( $member->member_id );
	}
	
	/**
	 * Can view this Tab
	 *
	 * @return	bool
	 */
	public function canView(): bool
	{
		return (bool) Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' );
	}
	
	/**
	 * Get left-column blocks
	 *
	 * @return	array
	 */
	public function leftColumnBlocks(): array
	{
		return array(
			'IPS\nexus\extensions\core\MemberACPProfileBlocks\AccountInformation',
		);
	}
	
	/**
	 * Get main-column blocks
	 *
	 * @return	array
	 */
	public function mainColumnBlocks(): array
	{
		$return = array();

		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view_statistics' ) )
		{
			$return[] = 'IPS\nexus\extensions\core\MemberACPProfileBlocks\Statistics';
		}

		$return[] = 'IPS\nexus\extensions\core\MemberACPProfileBlocks\ParentAccounts';
		
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_view' ) )
		{
			$return[] = 'IPS\nexus\extensions\core\MemberACPProfileBlocks\Notes';
		}
		
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) )
		{
			$return[] = 'IPS\nexus\extensions\core\MemberACPProfileBlocks\Purchases';
		}
		
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_manage' ) )
		{
			$return[] = 'IPS\nexus\extensions\core\MemberACPProfileBlocks\Invoices';
		}
		
		return $return;
	}
	
	/**
	 * Get Output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customer.css', 'nexus', 'admin' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_customer.js', 'nexus', 'admin' ) );
		
		return parent::output();
	}
}