<?php
/**
 * @brief		referrals
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Aug 2019
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\DateTime;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Money;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Referrals
 */
class referrals extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Call
	 *
	 * @param	string	$method	Method name
	 * @param	mixed	$args	Method arguments
	 */
	public function __call( string $method, mixed $args )
	{
		$tabs = array();
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'referrals_manage' ) )
		{
			$tabs['refersettings'] = 'settings';
		}
		if( Settings::i()->ref_on and Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'referrals_manage' ) )
		{
			$tabs['referralbanners'] = 'referral_banners';
		}
		if( Settings::i()->ref_on and Application::appIsEnabled( 'nexus' ) )
		{
			$tabs['referralcommission'] = 'referral_commission';
		}
		if ( isset( Request::i()->tab ) and isset( $tabs[ Request::i()->tab ] ) )
		{
			$activeTab = Request::i()->tab;
		}
		else
		{
			$_tabs = array_keys( $tabs ) ;
			$activeTab = array_shift( $_tabs );
		}

		$classname = 'IPS\core\modules\admin\membersettings\\' . $activeTab;
		$class = new $classname;
		$class->url = Url::internal("app=core&module=membersettings&controller=referrals&tab={$activeTab}");
		$class->execute();

		if ( $method !== 'manage' or Request::i()->isAjax() )
		{
			return;
		}

		Output::i()->sidebar['actions'] = array(
			'history'	=> array(
				'title'		=> 'referral_history',
				'icon'		=> 'clock',
				'link'		=> Url::internal( 'app=core&module=membersettings&controller=referrals&do=history' ),
				),
		);

		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_membersettings_referrals');
		Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, Output::i()->output, Url::internal( "app=core&module=membersettings&controller=referrals" ) );
	}

	/**
	 * Referral History
	 *
	 * @return	void
	 */
	protected function history() : void
	{
		$table = new Db( 'core_referrals', Url::internal( 'app=core&module=membersettings&controller=referrals&do=history' ) );
		$table->langPrefix = 'referrals_';

		/* Columns we need */
		$table->include = array( 'photo', 'member_id', 'referred_by', 'joined' );
		if( Application::appIsEnabled( 'nexus' ) )
		{
			$table->include[] = 'amount';
		}

		$table->sortBy = $table->sortBy ?: 'joined';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		$table->noSort = array( 'photo', 'member_id', 'referred_by' );

		$table->joins = array(
			array( 'select' => 'm.*', 'from' => array( 'core_members', 'm' ), 'where' => "core_referrals.member_id=m.member_id" )
		);

		$table->parsers = array(
			'photo'				=> function( $val, $row )
			{
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( Member::constructFromData( $row ), 'tiny' );

			},
			'member_id'	=> function( $val, $row )
			{
				if ( $val )
				{
					return Theme::i()->getTemplate( 'global', 'core', 'admin' )->userLink( Member::constructFromData( $row ) );
				}
				else
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberReserved( Member::load( $val ) );
				}
			},
			'referred_by'	=> function( $val )
			{
				if ( $val )
				{
					return Theme::i()->getTemplate( 'global', 'core', 'admin' )->userLink( Member::load( $val ) );
				}
				else
				{
					return Theme::i()->getTemplate( 'members', 'core', 'admin' )->memberReserved( Member::load( $val ) );
				}
			},
			'joined'	=> function( $val )
			{
				return DateTime::ts( $val )->localeDate();
			},
			'amount' => function ( $val )
			{
				$return = array();
				if ( $val )
				{
					foreach ( json_decode( $val, TRUE ) as $currency => $amount )
					{
						$return[] = new Money( $amount, $currency );
					}
				}
				else
				{
					$return[] = Member::loggedIn()->language()->addToStack('none');
				}
				return implode( '<br>', $return );
			}
		);

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('referrals');
		Output::i()->output	= (string) $table;
	}
}