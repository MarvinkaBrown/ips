<?php
/**
 * @brief		ACP Member Profile: Parent Accounts Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Dec 2017
 */

namespace IPS\nexus\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\Block;
use IPS\Db;
use IPS\nexus\Customer;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Customer Statistics Block
 */
class ParentAccounts extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$parents = array();
		foreach ( Db::i()->select( 'main_id', 'nexus_alternate_contacts', array( 'alt_id=?', $this->member->member_id ) ) as $row )
		{
			$parents[] = Customer::load( $row );
		}
		
		if ( count( $parents ) )
		{
			return (string) Theme::i()->getTemplate( 'customers', 'nexus' )->parentAccounts( $parents );
		}

		return '';
	}
}