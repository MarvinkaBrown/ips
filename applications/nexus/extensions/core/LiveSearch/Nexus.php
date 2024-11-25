<?php
/**
 * @brief		ACP Live Search Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		18 Sep 2014
 */

namespace IPS\nexus\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\extensions\core\LiveSearch\Members;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\LiveSearchAbstract;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Invoice;
use IPS\nexus\Purchase;
use IPS\nexus\Purchase\LicenseKey;
use IPS\nexus\Transaction;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Live Search Extension
 */
class Nexus extends LiveSearchAbstract
{	
	/**
	 * Check we have access
	 *
	 * @return	bool
	 */
	public function hasAccess(): bool
	{
		return	Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_manage' )
		or 		Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' )
		or		Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' )
		or		Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' )
		or		Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_view' );
	}
	
	/**
	 * Get the search results
	 *
	 * @param	string	$searchTerm	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( string $searchTerm ): array
	{
		$results = array();
		
		/* Numeric */
		if ( is_numeric( $searchTerm ) )
		{
			/* Invoice */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_manage' ) )
			{
				try
				{
					$results[] = Theme::i()->getTemplate('livesearch', 'nexus')->invoice( Invoice::load( $searchTerm ) );
				}
				catch ( OutOfRangeException ) { }
			}
			
			/* Transaction */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
			{
				try
				{
					$results[] = Theme::i()->getTemplate('livesearch', 'nexus')->transaction( Transaction::load( $searchTerm ) );
				}
				catch ( OutOfRangeException ) { }
			}
			
			/* Purchase */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) )
			{
				try
				{
					$results[] = Theme::i()->getTemplate('livesearch', 'nexus')->purchase( Purchase::load( $searchTerm ) );
				}
				catch ( OutOfRangeException ) { }
			}
			
			/* Customer */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' ) )
			{
				try
				{
					$customer = Customer::load( $searchTerm );
					if ( $customer->member_id )
					{
						$results[] = Theme::i()->getTemplate('livesearch', 'nexus')->customer( $customer );
					}
				}
				catch ( OutOfRangeException ) { }
			}
		}
		
		/* Textual */
		else
		{
			/* License Key */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_view' ) )
			{
				try
				{
					$results[] = Theme::i()->getTemplate('livesearch', 'nexus')->licensekey( LicenseKey::load( $searchTerm ) );
				}
				catch ( OutOfRangeException ) { }
			}
			
			/* Customers */
			if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' ) )
			{
				if( Members::canPerformInlineSearch() )
				{
					$query = Db::i()->select( '*', 'nexus_customers', array( Db::i()->like( array( 'core_members.name', 'core_members.email', "CONCAT( LOWER( nexus_customers.cm_first_name ), ' ', LOWER( nexus_customers.cm_last_name ) )" ), $searchTerm, TRUE, TRUE, TRUE ) ), NULL, 50 )->join( 'core_members', 'core_members.member_id=nexus_customers.member_id' );
				}
				else
				{
					$query = Db::i()->select( '*', 'nexus_customers', array( Db::i()->like( array( 'core_members.name', 'core_members.email', 'nexus_customers.cm_first_name', 'nexus_customers.cm_last_name', "CONCAT( LOWER( nexus_customers.cm_first_name ), ' ', LOWER( nexus_customers.cm_last_name ) )" ), $searchTerm ) ), NULL, 50 )->join( 'core_members', 'core_members.member_id=nexus_customers.member_id' );
				}

				foreach ( new ActiveRecordIterator( $query, 'IPS\nexus\Customer' ) as $customer )
				{
					$results[] = Theme::i()->getTemplate('livesearch', 'nexus')->customer( $customer );
				}
			}
		}
		
		/* For either, search for transaction gateway IDs */
		foreach ( Db::i()->select( '*', 'nexus_transactions', array( 't_gw_id=?', $searchTerm ) ) as $transactionData )
		{
			$results[] = Theme::i()->getTemplate( 'livesearch', 'nexus' )->transaction( Transaction::constructFromData( $transactionData ) );
		}
		
		/* Return */		
		return $results;
	}
	
	/**
	 * Is default for current page?
	 *
	 * @return	bool
	 */
	public function isDefault(): bool
	{
		return Dispatcher::i()->application->directory == 'nexus';
	}
}