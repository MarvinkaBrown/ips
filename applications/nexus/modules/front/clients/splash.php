<?php
/**
 * @brief		Controller to redirect /clients to something sensible
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		19 May 2017
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\Output;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Controller to redirect /clients to something sensible
 */
class splash extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( Member::loggedIn()->member_id )
		{
			$where = array( array( 'ps_member=?', Member::loggedIn()->member_id ) );
			$parentContacts = Customer::loggedIn()->parentContacts();
			if ( count( $parentContacts ) )
			{
				foreach ( $parentContacts as $contact )
				{
					$where[0][0] .= ' OR ' . Db::i()->in( 'ps_id', $contact->purchaseIds() );
				}
			}
			$where[] = array( 'ps_show=1' );
			
			if ( Db::i()->select( 'COUNT(*)', 'nexus_purchases', $where )->first() )
			{
				Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=purchases', 'front', 'clientspurchases' ) );
			}
		}
		
		Output::i()->redirect( Url::internal( 'app=nexus&module=clients&controller=invoices', 'front', 'clientsinvoices' ) );
	}
}