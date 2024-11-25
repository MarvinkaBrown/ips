<?php
/**
 * @brief		Account Settings Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		29 Jul 2023
 */

namespace IPS\core\extensions\core\AccountSettings;

use IPS\Application;
use IPS\Extensions\AccountSettingsAbstract;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\CommissionRule\Iterator;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Theme;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AccountSettings Extension
 */
class Referrals extends AccountSettingsAbstract
{	
	/**
	 * @var string
	 */
	public static string $icon = 'users';

	/**
	 * Return the key for the tab, or NULL if it should not be displayed
	 *
	 * @return string|null
	 */
	public function getTab() : string|null
	{
	    return Settings::i()->ref_on ? 'referrals' : null;
	}

	/**
	 * Return the content for the main tab
	 *
	 * @return string
	 */
	public function getContent() : string
	{
		if( ! Settings::i()->ref_on )
		{
			Output::i()->error( 'referrals_disabled', '2C122/V', 403, '' );
		}

		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/referrals.css' ) );

		$table = new \IPS\Helpers\Table\Db( 'core_referrals', Url::internal( 'app=core&module=system&controller=settings&area=referrals', 'front', 'settings_custom' ), array( array( 'core_referrals.referred_by=?', Member::loggedIn()->member_id ) ) );
		$table->joins = array(
			array( 'select' => 'm.joined', 'from' => array( 'core_members', 'm' ), 'where' => "m.member_id=core_referrals.member_id" )
		);

		$table->tableTemplate = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'referralTable' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'referralsRows' );

		$url = Url::internal('')->setQueryString( '_rid', Member::loggedIn()->member_id );

		$rules = NULL;
		if ( Application::appIsEnabled('nexus') )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'clients.css', 'nexus' ) );
			$rules = new Iterator( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_referral_rules' ), 'IPS\nexus\CommissionRule' ), Member::loggedIn() );
		}

		return Theme::i()->getTemplate( 'system' )->settingsReferrals( $table, $url, $rules );
	}
}