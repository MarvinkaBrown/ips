<?php
/**
 * @brief		ACP Member Profile: Invoices
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
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Invoice;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Invoices
 */
class Invoices extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$invoiceCount = Db::i()->select( 'COUNT(*)', 'nexus_invoices', array( 'i_member=?', $this->member->member_id ) )->first();
		$invoices = Invoice::table( array( 'i_member=?', $this->member->member_id ), $this->member->acpUrl()->setQueryString( 'tab', 'invoices' ), 'c' );
		$invoices->limit = 15;
		if ( Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_add' ) )
		{
			$invoices->rootButtons = array(
				'add'	=> array(
					'link'	=> Url::internal( "app=nexus&module=payments&controller=invoices&do=generate&member={$this->member->member_id}" ),
					'title'	=> 'add',
					'icon'	=> 'plus',
				)
			);
		}
		$invoices->tableTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'invoicesTable' );
		$invoices->rowsTemplate = array( Theme::i()->getTemplate( 'customers', 'nexus' ), 'invoicesTableRows' );
		
		return (string) Theme::i()->getTemplate( 'customers', 'nexus' )->invoices( $this->member, $invoices, $invoiceCount );
	}
}