<?php
/**
 * @brief		Achievement settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		01 Mar 2021
 */

namespace IPS\core\modules\admin\achievements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Rule;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Session;
use IPS\Settings as SettingsClass;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement settings
 */
class settings extends Controller
{
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
		$form = new Form;

		$form->add( new YesNo( 'achievements_enabled', SettingsClass::i()->achievements_enabled, FALSE, [ 'togglesOn' => [ 'rare_badge_percent', 'prune_points_log', 'rules_exclude_groups', 'achievements_recognize_max_per_user_day' ] ] ) );

		$form->add( new Number( 'rare_badge_percent', SettingsClass::i()->rare_badge_percent, FALSE, [ 'decimals' => 1, 'unlimited' => 0, 'unlimitedLang' => 'never' ], NULL, Member::loggedIn()->language()->addToStack('rare_badge_percent_prefix'), Member::loggedIn()->language()->addToStack('rare_badge_percent_suffix'), 'rare_badge_percent' ) );
		$form->add( new Interval( 'prune_points_log', SettingsClass::i()->prune_points_log, FALSE, [ 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ], NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_points_log' ) );
		$groups		= array_combine( array_keys( Group::groups( TRUE, FALSE ) ), array_map( function( $_group ) { return (string) $_group; }, Group::groups( TRUE, FALSE ) ) );
		$selectedGroups = json_decode( SettingsClass::i()->rules_exclude_groups, TRUE );
		$form->add( new CheckboxSet( 'rules_exclude_groups', $selectedGroups, FALSE, array( 'options' => $groups, 'multiple' => true ), NULL, NULL, NULL, 'rules_exclude_groups' ) );
		$form->add( new Number( 'achievements_recognize_max_per_user_day', SettingsClass::i()->achievements_recognize_max_per_user_day, FALSE, [ 'unlimited' => -1 ], NULL, Member::loggedIn()->language()->addToStack('achievements_recognize_max_per_user_day_prefix'), Member::loggedIn()->language()->addToStack('achievements_recognize_max_per_user_day_suffix'), 'achievements_recognize_max_per_user_day' ) );

		if ( $values = $form->values() )
		{
			$values['rules_exclude_groups'] = json_encode( $values['rules_exclude_groups'] );
			$form->saveAsSettings( $values );
			
			Session::i()->log( 'acplog__achievement_settings' );
		}

		if ( SettingsClass::i()->achievements_enabled )
		{
			Output::i()->sidebar['actions']['rebuild'] = array(
				'primary' => true,
				'icon' => 'plus',
				'link' => Url::internal( 'app=core&module=achievements&controller=settings&do=rebuildForm' )->csrf(),
				'data' => array('ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'acp_rebuild_achievements' )),
				'title' => 'acp_rebuild_achievements',
			);
		}

		if( $data = Rule::getRebuildProgress() )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'achievements' )->rebuildProgress( $data, TRUE );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack('achievements_settings');
		Output::i()->output .= $form;
	}

	/**
	 * Rebuild Members' Achievements
	 *
	 * @return	void
	 */
	protected function rebuildForm() : void
	{
		if ( ! SettingsClass::i()->achievements_enabled )
		{
			Output::i()->error( 'achievements_not_enabled', '1C421/1', 403, '' );
		}

		$form = new Form( 'rebuild_form', 'acp_rebuild_achievements_rebuild');
		$form->addMessage('acp_rebuild_achievements_blurb', 'ipsMessage ipsMessage--info');
		$form->add( new Checkbox( 'acp_rebuild_achievements_checkbox', FALSE, TRUE ) );
		$form->add( new Date( 'acp_rebuild_achievements_time', 0, TRUE, [ 'unlimited' => 0, 'unlimitedLang' => 'acp_rebuild_achievements_time_unlimited' ] ) );

		if ( $values = $form->values() )
		{
			if ( ! $values['acp_rebuild_achievements_checkbox'] )
			{
				$form->error = Member::loggedIn()->language()->addToStack('acp_achievements_rebuild_not_checked');
			}
			else
			{
				Session::i()->log( 'acplogs__achievements_rebuild' );
				Rule::rebuildAllAchievements( $values['acp_rebuild_achievements_time'] ?: NULL );

				SettingsClass::i()->changeValues( array('achievements_rebuilding' => 1) );
				Output::i()->redirect( Url::internal( 'app=core&module=achievements&controller=settings' ) );
			}
		}

		Output::i()->bypassCsrfKeyCheck = TRUE;
		Output::i()->title = Member::loggedIn()->language()->addToStack('acp_rebuild_achievements');
		Output::i()->output = $form;
	}
	
}