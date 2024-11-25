<?php
/**
 * @brief		ACP Member Profile: Purchases
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Dec 2017
 */

namespace IPS\nexus\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\TabbedBlock;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Tree\Tree;
use IPS\nexus\Purchase;
use IPS\Request;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Purchases
 */
class Purchases extends TabbedBlock
{
	/**
	 * Purchase tree
	 */
	protected ?Tree $_purchases = NULL;

	/**
	 * Get Tab Names
	 *
	 * @return	array
	 */
	public function tabs(): array
	{
		$tabs = array();

		$activeCount = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_show=1 AND ps_active=1 AND ps_parent = 0', $this->member->member_id ) )->first();
		$tabs['active'] = array(
				'icon'		=> 'credit-card',
				'count'		=> $activeCount,
		);

		$expiredCount = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_active = 0 AND ps_cancelled = 0 AND ps_parent = 0 AND ps_expire <?', $this->member->member_id, DateTime::create()->getTimestamp() ) )->first();
		$tabs['expired'] = array(
				'icon'		=> 'credit-card',
				'count'		=> $expiredCount,
		);

		$canceledCount = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_cancelled=1 AND ps_parent = 0', $this->member->member_id ) )->first();
		$tabs['canceled'] = array(
			'icon'		=> 'credit-card',
			'count'		=> $canceledCount,
		);

		return $tabs;
	}
	
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$tabs = $this->tabs();
		if ( !count( $tabs ) )
		{
			return '';
		}
		$tabKeys = array_keys( $tabs );
		$activeTabKey = ( isset( Request::i()->block['nexus_Purchase'] ) and array_key_exists( Request::i()->block['nexus_Purchases'], $tabs ) ) ? Request::i()->block['nexus_Purchases'] : array_shift( $tabKeys );

		return (string) Theme::i()->getTemplate( 'customers', 'nexus' )->purchases( $this->member, $tabs, $activeTabKey, $this->tabOutput( $activeTabKey ) );
	}

	/**
	 * Get output
	 *
	 * @param string $tab
	 * @return    mixed
	 */
	public function tabOutput(string $tab ): mixed
	{
		if ( $this->_purchases === NULL )
		{
			$where = array();
			$where[] = array( 'ps_member=?', $this->member->member_id );

			switch ( $tab )
			{
				case 'active':
					$where[] = array( 'ps_active=1' );
					break;
				case 'canceled':
					$where[] = array( 'ps_cancelled=1' );
					break;
				case 'expired':
					$where[] = array( 'ps_active = 0 and ps_cancelled = 0 and ps_expire <?', DateTime::create()->getTimestamp() );
					break;
			}

			$this->_purchases = Purchase::tree( $this->member->acpUrl()->setQueryString( 'blockKey', 'nexus_Purchases' ), $where );
			$this->_purchases->getTotalRoots = function()
			{
				return NULL;
			};
		}
		return $this->_purchases;
	}
}