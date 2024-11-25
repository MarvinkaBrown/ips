<?php
/**
 * @brief		Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		18 Dec 2013
 */

namespace IPS\calendar\modules\admin\calendars;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Date;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings as SettingsClass;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings
 */
class settings extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->title = Member::loggedIn()->language()->addToStack('settings');

		$form = new Form;

		$form->add( new Radio( 'calendar_default_view', SettingsClass::i()->calendar_default_view, TRUE, array( 'options' => array( 'overview' => 'cal_df_overview', 'month' => 'cal_df_month', 'week' => 'cal_df_week', 'day' => 'cal_df_day' ) ) ) );

		$options	= array_combine( array_keys( Date::$dateFormats ), array_map( function( $val ){ return "calendar_df_" . $val; }, array_keys( Date::$dateFormats ) ) );
		$form->add( new Select( 'calendar_date_format', SettingsClass::i()->calendar_date_format, TRUE, array( 'options' => $options, 'unlimited' => '-1', 'unlimitedLang' => "calendar_custom_df", 'unlimitedToggles' => array( 'calendar_date_format_custom' ) ) ) );
		$form->add( new Text( 'calendar_date_format_custom', SettingsClass::i()->calendar_date_format_custom, FALSE, array(), NULL, NULL, NULL, 'calendar_date_format_custom' ) );

		$form->add( new YesNo( 'ipb_calendar_mon', SettingsClass::i()->ipb_calendar_mon ) );
		$form->add( new YesNo( 'calendar_rss_feed', SettingsClass::i()->calendar_rss_feed, FALSE, array( 'togglesOn' => array( 'calendar_rss_feed_days', 'calendar_rss_feed_order' ) ) ) );
		$form->add( new Number( 'calendar_rss_feed_days', SettingsClass::i()->calendar_rss_feed_days, FALSE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'calendar_rss_feed_days' ) );
		$form->add( new Radio( 'calendar_rss_feed_order', SettingsClass::i()->calendar_rss_feed_order, FALSE, array( 'options' => array(
			0 => 'calendar_rss_feed_order_date',
			1 => 'calendar_rss_feed_order_publish'
		) ), NULL, NULL, NULL, 'calendar_rss_feed_order' ) );

		$form->add( new YesNo( 'calendar_venues_enabled', SettingsClass::i()->calendar_venues_enabled ) );

		$form->add( new YesNo( 'calendar_block_past_changes', SettingsClass::i()->calendar_block_past_changes ) );

		if( GeoLocation::enabled() )
		{
			$form->add( new Number( 'map_center_lat', SettingsClass::i()->map_center_lat, FALSE, array( 'decimals' => 8, 'min' => "-180", 'max' => "180" ), NULL, NULL, NULL, 'map_center_lat' ) );
			$form->add( new Number( 'map_center_lon', SettingsClass::i()->map_center_lon, FALSE, array( 'decimals' => 8, 'min' => "-180", 'max' => "180" ), NULL, NULL, NULL, 'map_center_lon' ) );
		}

		if ( $values = $form->values() )
		{
			if( $values['calendar_date_format'] == -1 AND !$values['calendar_date_format_custom'] )
			{
				$form->error	= Member::loggedIn()->language()->addToStack('calendar_no_date_format');
				Output::i()->output = $form;
				return;
			}

			$form->saveAsSettings();

			Session::i()->log( 'acplogs__calendar_settings' );
		}

		Output::i()->output = $form;
	}
}