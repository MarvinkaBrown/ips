<?php
/**
 * @brief		Clubs Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Feb 2017
 */

namespace IPS\core\modules\admin\clubs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Application;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Form\Money;
use IPS\Output;
use IPS\Session;
use IPS\Settings as SettingsClass;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Clubs Settings
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
		Dispatcher::i()->checkAcpPermission( 'clubs_settings_manage' );
		parent::execute();
	}

	/**
	 * Manage Club Settings
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$fields =  array( 'clubs_default_sort', 'clubs_header', 'clubs_locations', 'clubs_modperms', 'clubs_require_approval', 'form_header_club_display_settings', 'form_header_club_moderation', 'clubs_default_view', 'clubs_allow_view_change', 'club_nodes_in_apps', 'form_header_clubs_paid_settings', 'clubs_paid_on', '_allow_club_moderators', 'club_max_cover' );

		$form = new Form;
		$form->addHeader( 'club_settings' );
		$form->add( new YesNo( 'clubs_enabled_setting',  SettingsClass::i()->clubs, FALSE, array( 'togglesOn' => $fields ) ) );
		$form->add( new YesNo( 'clubs_require_approval',  SettingsClass::i()->clubs_require_approval, FALSE, array(), NULL, NULL, NULL, 'clubs_require_approval' ) );
		$form->add( new YesNo( 'clubs_locations',  SettingsClass::i()->clubs_locations, FALSE, array(), NULL, NULL, NULL, 'clubs_locations' ) );
		if ( Application::appIsEnabled( 'nexus' ) )
		{
			$form->addHeader( 'clubs_paid_settings' );
			$form->add( new YesNo( 'clubs_paid_on',  SettingsClass::i()->clubs_paid_on, FALSE, array( 'togglesOn' => array( 'clubs_paid_tax', 'clubs_paid_commission', 'clubs_paid_transfee', 'clubs_paid_gateways' ) ), NULL, NULL, NULL, 'clubs_paid_on' ) );
			$form->add( new Node( 'clubs_paid_tax',  SettingsClass::i()->clubs_paid_tax ?:0, FALSE, array( 'class' => '\IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ), NULL, NULL, NULL, 'clubs_paid_tax' ) );
			$form->add( new Number( 'clubs_paid_commission',  SettingsClass::i()->clubs_paid_commission, FALSE, array( 'min' => 0, 'max' => 100 ), NULL, NULL, '%', 'clubs_paid_commission' ) );
			$form->add( new Money( 'clubs_paid_transfee',  SettingsClass::i()->clubs_paid_transfee, FALSE, array(), NULL, NULL, NULL, 'clubs_paid_transfee' ) );
			$form->add( new Node( 'clubs_paid_gateways',  SettingsClass::i()->clubs_paid_gateways, FALSE, array( 'class' => '\IPS\nexus\Gateway', 'zeroVal' => 'no_restriction', 'multiple' => TRUE ), NULL, NULL, NULL, 'clubs_paid_gateways' ) );
		}
		$form->addHeader( 'club_display_settings' );
		$form->add( new Radio( 'clubs_default_view',  SettingsClass::i()->clubs_default_view, FALSE, array( 'options' => array(
			'grid'		=> 'club_view_grid',
			'list'		=> 'club_view_list',
		) ), NULL, NULL, NULL, 'clubs_default_view' ) );
		$form->add( new YesNo( 'clubs_allow_view_change',  SettingsClass::i()->clubs_allow_view_change, FALSE, array(), NULL, NULL, NULL, 'clubs_allow_view_change' ) );
		$form->add( new Radio( 'clubs_default_sort',  SettingsClass::i()->clubs_default_sort, FALSE, array( 'options' => array(
			'last_activity'		=> 'clubs_sort_last_activity',
			'members'			=> 'clubs_sort_members',
			'content'			=> 'clubs_sort_content',
			'created'			=> 'clubs_sort_created',
			'name'				=> 'clubs_sort_name'
		) ), NULL, NULL, NULL, 'clubs_default_sort' ) );
		$form->add( new Radio( 'clubs_header',  SettingsClass::i()->clubs_header, FALSE, array( 'options' => array(
			'full'		=> 'clubs_header_full',
			'sidebar'	=> 'clubs_header_sidebar',
		) ), NULL, NULL, NULL, 'clubs_header' ) );
		$form->add( new Radio( 'club_nodes_in_apps',  SettingsClass::i()->club_nodes_in_apps, FALSE, array( 'options' => array(
			'0'	=> 'club_nodes_in_apps_off',
			'1'	=> 'club_nodes_in_apps_on',
		) ), NULL, NULL, NULL, 'club_nodes_in_apps' ) );
		$form->add( new Number( 'club_max_cover',  SettingsClass::i()->club_max_cover ?: -1, FALSE, array( 'unlimited' => -1 ), function( $value ) {
			if( !$value )
			{
				throw new InvalidArgumentException('form_required');
			}
		}, NULL, Member::loggedIn()->language()->addToStack('filesize_raw_k'), 'club_max_cover' ) );
		$form->addHeader( 'club_moderation' );
		$form->add( new YesNo( '_allow_club_moderators', (  SettingsClass::i()->clubs_modperms != -1 ), FALSE, array( 'togglesOn' => array( 'clubs_modperms' ) ), NULL, NULL, NULL, '_allow_club_moderators' ) );
		$form->add( new CheckboxSet( 'clubs_modperms', (  SettingsClass::i()->clubs_modperms != -1 ) ? explode( ',',  SettingsClass::i()->clubs_modperms ) : array(), FALSE, array( 'options' => array(
			'pin'				=> 'club_modperm_pin',
			'unpin'				=> 'club_modperm_unpin',
			'edit'				=> 'club_modperm_edit',
			'hide'				=> 'club_modperm_hide',
			'unhide'			=> 'club_modperm_unhide',
			'view_hidden'		=> 'club_modperm_view_hidden',
			'future_publish'	=> 'club_modperm_future_publish',
			'view_future'		=> 'club_modperm_view_future',
			'move'				=> 'club_modperm_move',
			'lock'				=> 'club_modperm_lock',
			'unlock'			=> 'club_modperm_unlock',
			'reply_to_locked'	=> 'club_modperm_reply_to_locked',
			'delete'			=> 'club_modperm_delete',
			'split_merge'		=> 'club_modperm_split_merge',
		) ), NULL, NULL, NULL, 'clubs_modperms' ) );

		if ( $values = $form->values() )
		{
			$values['clubs'] = $values['clubs_enabled_setting'];

			/* If this setting is set to "No" then we're going to wipe out moderator permissions */
			if( !$values['_allow_club_moderators'] )
			{
				$values['clubs_modperms'] = array();
			}

			$values['clubs_modperms'] = ( count( $values['clubs_modperms'] ) ) ? implode( ',', $values['clubs_modperms'] ) : -1;

			/* Get rid of fake settings */
			unset( $values['clubs_enabled_setting'], $values['_allow_club_moderators'] );

			if ( Application::appIsEnabled( 'nexus' ) )
			{
				$values['clubs_paid_tax'] = $values['clubs_paid_tax'] ? $values['clubs_paid_tax']->id : 0;	
				$values['clubs_paid_gateways'] = is_array( $values['clubs_paid_gateways'] ) ? implode( ',', array_keys( $values['clubs_paid_gateways'] ) ) : $values['clubs_paid_gateways'];
			}
			$form->saveAsSettings( $values );
			
			Session::i()->log( 'acplog__club_settings' );
			Output::i()->redirect( Url::internal('app=core&module=clubs&controller=settings'), 'saved' );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('club_settings');
		Output::i()->output = $form;
	}
}