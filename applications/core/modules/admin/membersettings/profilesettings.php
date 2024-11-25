<?php
/**
 * @brief		Profile Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		08 Jan 2018
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use function defined;
use function is_array;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile Settings
 */
class profilesettings extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'profiles_manage' );

		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_members.js', 'core' ) );
		
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		$form = new Form;

		$form->addHeader( 'photos' );
		$form->add( new Radio( 'letter_photos', Settings::i()->letter_photos, FALSE, array( 'options' => array( 'default' => 'letterphoto_default', 'letters' => 'letterphoto_letters' ) ) ) );

		$form->addHeader( 'usernames' );
		$form->add( new Custom( 'user_name_length', array( Settings::i()->min_user_name_length, Settings::i()->max_user_name_length ), FALSE, array(
			'getHtml'	=> function( $field ) {
				return Theme::i()->getTemplate('members')->usernameLengthSetting( $field->name, $field->value );
			}
		),
		function( $val )
		{
			if ( $val[0] < 1 )
			{
				throw new DomainException('user_name_length_too_low');
			}
			if ( $val[1] > 255 )
			{
				throw new DomainException('user_name_length_too_high');
			}
			if ( $val[0] > $val[1] )
			{
				throw new DomainException('user_name_length_no_match');
			}
		} ) );

		$form->add( new Custom( 'username_characters', Settings::i()->username_characters, FALSE, array(
			'getHtml'	=> function( $field ) {
				$easy = NULL;
				if ( is_array( $field->value ) )
				{
					$easy['letters'] = $field->value['letters'];
					$easy['numbers'] = $field->value['numbers'];
					$easy['spaces'] = isset( $field->value['spaces'] );
					$easy['extra'] = isset( $field->value['extra_enabled'] ) ? $field->value['extra'] : '';
					$field->value = $field->value['regex'];
				}
				else{				
					if ( preg_match( '/^\/\^\(\(\[(\\\p\{L\}\\\p\{M\}|A\-Za\-z)(\\\p\{N\}|0-9)?(.+)?\]\+\)( )?\?\)\+\$\/u$/', $field->value, $matches ) )
					{
						$easy['letters'] = ( $matches[1] == '\\p{L}\\p{M}' ) ? 'all' : 'latin';
						if ( $matches[2] )
						{
							$easy['numbers'] = ( $matches[2] == '\\p{N}' ) ? 'all' : 'arabic';
						}
						else
						{
							$easy['numbers'] = 'none';
						}
						$easy['spaces'] = isset( $matches[4] );
						$easy['extra'] = stripslashes( $matches[3] );
					}
				}
				return Theme::i()->getTemplate('members')->usernameRegexSetting( $field->name, $field->value, $easy );
			}
		) ) );
		$form->addHeader( 'signatures' );
		$form->add( new YesNo( 'signatures_enabled', Settings::i()->signatures_enabled,  FALSE, array( 'togglesOn' => array( 'signatures_mobile', 'signatures_guests' ) ) ) );
		$form->add( new YesNo( 'signatures_mobile', Settings::i()->signatures_mobile,  FALSE, array(), NULL, NULL, NULL, 'signatures_mobile' ) );
		$form->add( new YesNo( 'signatures_guests', Settings::i()->signatures_guests,  FALSE, array(), NULL, NULL, NULL, 'signatures_guests' ) );

		$form->addHeader( 'profile_settings_birthdays' );
		$form->add( new Radio( 'profile_birthday_type', Settings::i()->profile_birthday_type, TRUE, array(
			'options'	=> array( 'public' => 'profile_birthday_type_public', 'private' => 'profile_birthday_type_private', 'none' => 'profile_birthday_type_none' )
		), NULL, NULL, NULL, 'profile_birthday_type' ) );

		if( !CIC AND Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'member_history_prune' ) )
		{
			$form->addHeader( 'profile_member_history' );
			$form->add( new Interval( 'prune_member_history', Settings::i()->prune_member_history, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_member_history' ) );

			if ( Application::appIsEnabled('nexus') )
			{
				$form->add( new Interval( 'nexus_prune_history', Settings::i()->nexus_prune_history, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'nexus_prune_history' ) );
			}

			$form->add( new Interval( 'prune_known_ips', Settings::i()->prune_known_ips, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), function( $val ) {
				if( $val > 0 AND $val < 7 )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_interval_min_d', FALSE, array( 'pluralize' => array( 6 ) ) ) );
				}
			}, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_known_ips' ) );

			$form->add( new Interval( 'prune_known_devices', Settings::i()->prune_known_devices, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), function( $val ) {
				if( $val > 0 AND $val < 30 )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_interval_min_d', FALSE, array( 'pluralize' => array( 29 ) ) ) );
				}
			}, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_known_devices' ) );

			$form->add( new Interval( 'prune_item_markers', Settings::i()->prune_item_markers, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), function( $val ) {
				if( $val > 0 AND $val < 7 )
				{
					throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_interval_min_d', FALSE, array( 'pluralize' => array( 6 ) ) ) );
				}
			}, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_item_markers' ) );
		}

		$form->addHeader( 'profile_settings_pms' );
		$form->add( new Interval( 'prune_pms', Settings::i()->prune_pms, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_pms' ) );

		$form->addHeader( 'profile_settings_ignore' );
		$form->add( new YesNo( 'ignore_system_on', Settings::i()->ignore_system_on, FALSE, array(), NULL, NULL, NULL, 'ignore_system_on' ) );

		$form->addHeader( 'profile_settings_display' );
		$form->add( new Radio( 'group_formatting_type', Settings::i()->group_formatting, FALSE, array( 'options' => array(
			'legacy'	=> 'group_formatting_type_legacy',
			'global'	=> 'group_formatting_type_global'
		) ) ) );

		$form->add( new Radio( 'link_default', Settings::i()->link_default, FALSE, array( 'options' => array(
			'unread'	=> 'profile_settings_cvb_unread',
			'last'	=> 'profile_settings_cvb_last',
			'first'	=> 'profile_settings_cvb_first'
		) ) ) );

		if ( $values = $form->values() )
		{
			if ( $values['username_characters']['easy'] )
			{
				$regex = '/^(([';
				if ( $values['username_characters']['letters'] == 'all' )
				{
					$regex .= '\p{L}\p{M}';
				}
				else
				{
					$regex .= 'A-Za-z';
				}
				if ( $values['username_characters']['numbers'] == 'all' )
				{
					$regex .= '\p{N}';
				}
				elseif ( $values['username_characters']['numbers'] == 'arabic' )
				{
					$regex .= '0-9';
				}
				if ( isset( $values['username_characters']['extra_enabled'] ) )
				{
					$regex .= preg_quote( $values['username_characters']['extra'], '/' );
				}
				$regex .= ']+)';
				if ( isset( $values['username_characters']['spaces'] ) )
				{
					$regex .= ' ';
				}
				$regex .= '?)+$/u';
				
				$values['username_characters'] = $regex;
			}
			else
			{
				$values['username_characters'] = $values['username_characters']['regex'];
			}
			
			$values['group_formatting'] = $values['group_formatting_type'];
			unset( $values['group_formatting_type'] );
			
			$values['min_user_name_length'] = $values['user_name_length'][0];
			$values['max_user_name_length'] = $values['user_name_length'][1];
			unset( $values['user_name_length'] );

			/* If we're enabling pruning on a potentially large table, handle that */
			if( !Settings::i()->prune_member_history AND $values['prune_member_history'] )
			{
				Task::queue( 'core', 'PruneLargeTable', array(
					'table'			=> 'core_member_history',
					'where'			=> array( 'log_date < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_member_history'] . 'D' ) )->getTimestamp() ),
					'setting'		=> 'prune_member_history',
				), 4 );
			}

			if( !Settings::i()->prune_known_ips AND $values['prune_known_ips'] )
			{
				Task::queue( 'core', 'PruneLargeTable', array(
					'table'			=> 'core_members_known_ip_addresses',
					'where'			=> array( 'last_seen < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_known_ips'] . 'D' ) )->getTimestamp() ),
					'setting'		=> 'prune_known_ips',
				), 4 );
			}

			if( !Settings::i()->prune_known_devices AND $values['prune_known_devices'] )
			{
				Task::queue( 'core', 'PruneLargeTable', array(
					'table'			=> 'core_members_known_devices',
					'where'			=> array( 'last_seen < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_known_devices'] . 'D' ) )->getTimestamp() ),
					'setting'		=> 'prune_known_devices',
				), 4 );
			}

			if( !Settings::i()->prune_item_markers AND $values['prune_item_markers'] )
			{
				Task::queue( 'core', 'PruneLargeTable', array(
					'table'			=> 'core_item_markers',
					'where'			=> array( 'item_member_id IN(?)', Db::i()->select( 'member_id', 'core_members', array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_item_markers'] . 'D' ) )->getTimestamp() ) ) ),
					'setting'		=> 'prune_item_markers',
					'deleteJoin'	=> array(
						'column'		=> 'member_id',
						'table'			=> 'core_members',
						'where'			=> array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_item_markers'] . 'D' ) )->getTimestamp() ),
						'outerColumn'	=> 'item_member_id'
					)
				), 4 );
			}
		
			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__profile_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=profiles&tab=profilesettings' ), 'saved' );
		}
		
		Output::i()->output = (string) $form;
	}
}