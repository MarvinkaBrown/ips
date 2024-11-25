<?php
/**
 * @brief		ACP Member Profile Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		30 Sep 2019
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\MemberACPProfile\Block;
use IPS\Helpers\Table\Db;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile Block
 */
class Referrals extends Block
{
	/**
	 * Get output
	 *
	 * @param bool $edit
	 * @return    string
	 */
	public function output( bool $edit = FALSE ) : string
	{
		if( !Settings::i()->ref_on )
		{
			return "";
		}
		
		$url = $this->member->acpUrl()->setQueryString( array( 'do' => 'editBlock', 'block' => get_class( $this ) ) );
		$referCount = \IPS\Db::i()->select( 'COUNT(*)', 'core_referrals', array( 'referred_by=?', $this->member->member_id ) )->first();
		$referrals = new Db( 'core_referrals', $url, array( 'referred_by=?', $this->member->member_id ) );
		$referrals->langPrefix = 'ref_';
		$referrals->include = array( 'member_id' );

		if ( Application::appIsEnabled( 'nexus' ) )
		{
			$referrals->include[] = 'amount';
		}

		$referrals->sortBy = $referrals->sortBy ?: 'member_id';
		$referrals->parsers = array( 'member_id' => function ($v)
		{
			try
			{
				return Theme::i()->getTemplate( 'global' )->userLink( Member::load( $v ) );
			}
			catch ( OutOfRangeException $e )
			{
				return Member::loggedIn()->language()->addToStack( 'deleted_member' );
			}
		}, 'email' => function ($v, $row)
		{
			try
			{
				return htmlspecialchars( Member::load( $row[ 'member_id' ] )->email, ENT_DISALLOWED, 'UTF-8', FALSE );
			}
			catch ( OutOfRangeException $e )
			{
				return Member::loggedIn()->language()->addToStack( 'deleted_member' );
			}
		}, 'amount' => function ($v)
		{
			$return = array();
			if ( !is_array( $v ) and !empty( $v ) )
			{
				foreach ( json_decode( $v, TRUE ) as $currency => $amount )
				{
					$return[] = new Money( $amount, $currency );
				}
			}
			else
			{
				$return[] = new Money( 0, Customer::load( $this->member->member_id )->defaultCurrency() );
			}
			return implode( '<br>', $return );
		} );

		if( $edit )
		{
			return Theme::i()->getTemplate( 'members', 'core' )->referralPopup( $referrals );
		}
		else
		{
			$referrals->include[] = 'email';
			$referrals->limit = 2;
			$referrals->tableTemplate = array( Theme::i()->getTemplate( 'members', 'core' ), 'referralsOverview' );
			$referrals->rowsTemplate = array( Theme::i()->getTemplate( 'members', 'core' ), 'referralsOverviewRows' );

			return Theme::i()->getTemplate( 'members', 'core' )->referralsTable( $this->member, $referrals, Member::loggedIn()->language()->addToStack( 'num_refer_count', FALSE, array( 'pluralize' => array( $referCount ) ) ), 'referrers' );
		}
	}

	/**
	 * Edit Window
	 *
	 * @return	string
	 */
	public function edit(): string
	{
		return $this->output( TRUE );
	}
}