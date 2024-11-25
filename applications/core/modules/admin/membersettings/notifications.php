<?php
/**
 * @brief		Notification Settings
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Apr 2013
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
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Custom;
use IPS\Http\Url;
use IPS\Member;
use IPS\Notification;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use function defined;
use function in_array;
use const IPS\CIC;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Settings
 */
class notifications extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'notifications_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$types = array();
		foreach ( Application::allExtensions( 'core', 'Notifications' ) as $k => $extension )
		{
			$types[ $k ] = array(
				'title'	=> Member::loggedIn()->language()->get('notifications__' . $k)
			);
		}
		
		$typeTable = new Custom( $types, Url::internal( 'app=core&module=membersettings&controller=notifications', 'admin' ) );
		$typeTable->langPrefix = 'notificationsettings_';
		$typeTable->rowButtons = function( $row, $k ) {
			return array(
				'edit'	=> array(
					'title'	=> 'edit',
					'link'	=> Url::internal( 'app=core&module=membersettings&controller=notifications&do=edit', 'admin' )->setQueryString( 'id', $k ),
					'icon'	=> 'pencil'
				)
			);
		};

		if( !CIC )
		{
			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'title'		=> 'prunesettings',
					'icon'		=> 'cog',
					'link'		=> Url::internal( 'app=core&module=membersettings&controller=notifications&do=pruneSettings' ),
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('prunesettings') )
				),
			);
		}

		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'profiles_manage' ) )
		{
			Output::i()->sidebar['actions']['reset'] = array(
				'icon'		=> 'undo',
				'title'		=> 'notification_prefs_reset',
				'link'		=> Url::internal( 'app=core&module=membersettings&controller=notifications&do=updateMemberFollowPrefs' )->csrf(),
				'data'		=> array( 'confirm' => '', 'confirmSubMessage' => Member::loggedIn()->language()->addToStack('notification_prefs_reset_confirm') )
			);
		}
				
		Output::i()->title = Member::loggedIn()->language()->addToStack('notifications');

		if ( ! Notification::canUseWebPush() )
		{
			Output::i()->output = Theme::i()->getTemplate('global')->message( 'acp_notifications_cannot_use_web_push', 'general' );
		}

		Output::i()->output .= $typeTable;
	}

	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function pruneSettings() : void
	{
		if( CIC )
		{
			Output::i()->error( 'node_error', '2C413/1', 403, '' );
		}

		$form = new Form;
		$form->add( new Interval( 'prune_follows', Settings::i()->prune_follows, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), function( $val ) {
			if( $val > 0 AND $val < 30 )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_interval_min_d', FALSE, array( 'pluralize' => array( 29 ) ) ) );
			}
		}, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_follows' ) );
		$form->add( new Interval( 'prune_notifications', Settings::i()->prune_notifications, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), function( $val ) {
			if( $val > 0 AND $val < 7 )
			{
				throw new DomainException( Member::loggedIn()->language()->addToStack( 'form_interval_min_d', FALSE, array( 'pluralize' => array( 6 ) ) ) );
			}
		}, Member::loggedIn()->language()->addToStack('after') ) );
		
		if ( $values = $form->values() )
		{
			/* If we're enabling pruning on a potentially large table, handle that */
			if( !Settings::i()->prune_follows AND $values['prune_follows'] )
			{
				Task::queue( 'core', 'PruneLargeTable', array(
					'table'			=> 'core_follow',
					'where'			=> array( 'follow_app!=? AND follow_area!=? AND follow_member_id IN(?)', 'core', 'member', Db::i()->select( 'member_id', 'core_members', array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_follows'] . 'D' ) )->getTimestamp() ) ) ),
					'setting'		=> 'prune_follows',
					'deleteJoin'	=> array(
						'column'		=> 'member_id',
						'table'			=> 'core_members',
						'where'			=> array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . $values['prune_follows'] . 'D' ) )->getTimestamp() ),
						'outerColumn'	=> 'follow_member_id'
					)
				), 4 );
			}

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__follow_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=notifications' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('notification_pruning');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'notification_pruning', $form, FALSE );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		$extensions = Application::allExtensions( 'core', 'Notifications' );
		if ( !isset( $extensions[ Request::i()->id ] ) )
		{
			Output::i()->error( 'node_error', '2C404/2', 404, '' );
		}
		$extension = $extensions[ Request::i()->id ];
		
		$defaultConfiguration = Notification::defaultConfiguration();
		
		$form = new Form;
		
		foreach ( Notification::availableOptions( NULL, $extension ) as $key => $option )
		{
			if ( $option['type'] === 'standard' )
			{
				$form->addHeader( $option['title'] );
				if ( isset( $option['adminDescription'] ) )
				{
					$form->addMessage( $option['adminDescription'] );
				}
				elseif ( $option['description'] )
				{
					$form->addMessage( $option['description'] );
				}
				
				if ( !in_array( 'inline', $option['disabled'] ) or (  Notification::webPushEnabled() and !in_array( 'push', $option['disabled'] ) ) or !in_array( 'email', $option['disabled'] ) )
				{
					$canEditField = new YesNo( 'notificationsettings_editable_' . $key, $defaultConfiguration[ $key ]['editable'], FALSE, array(
						'togglesOn'		=> array( "member_notifications_{$key}_inline_editable",  "member_notifications_{$key}_push",  "member_notifications_{$key}_email_editable" ),
						'togglesOff'	=> array( "member_notifications_{$key}_inline", "member_notifications_{$key}_email" )
					) );
					$canEditField->label = Member::loggedIn()->language()->addToStack('notificationsettings_editable');
					$form->add( $canEditField );
				}
				
				if ( isset( $option['extra'] ) )
				{
					foreach ( $option['extra'] as $k => $extra )
					{
						if ( isset( $extra['adminCanSetDefault'] ) and $extra['adminCanSetDefault'] )
						{
							$field = new Radio( 'member_notifications_' . $key . '_' . $k, $extra['default'] ? 'default' : 'optional', FALSE, array( 'options' => array(
								'default'	=> isset( $extra['admin_lang'] ) ? $extra['admin_lang']['default'] : 'admin_notification_pref_default',
								'optional'	=> isset( $extra['admin_lang'] ) ? $extra['admin_lang']['optional'] : 'admin_notification_pref_optional',
							) ), NULL, NULL, NULL, 'member_notifications_' . $k );
							$field->label = Member::loggedIn()->language()->addToStack( isset( $extra['admin_lang'] ) ? $extra['admin_lang']['title'] : $extra['title'] );
							$form->add( $field );
						}
					}
				}

				if ( Notification::canUseWebPush() )
				{
					$types = array( 'inline', 'push', 'email' );
				}
				else
				{
					$types = array( 'inline', 'email' );
				}
				foreach ( $types as $k )
				{
					if ( !in_array( $k, $option['disabled'] ) )
					{
						$editableFieldValue = in_array( $k, $defaultConfiguration[ $key ]['default'] ) ? 'default' : 'optional';
						if ( in_array( $k, $defaultConfiguration[ $key ]['disabled'] ) )
						{
							$editableFieldValue = 'disabled';
						}
						if ( $k !== 'push' )
						{
							$editableField = new Radio( 'member_notifications_' . $key . '_' . $k . '_editable', $editableFieldValue, FALSE, array(
								'options'	=> array(
									'default'	=> 'admin_notification_pref_default',
									'optional'	=> 'admin_notification_pref_optional',
									'disabled'	=> 'admin_notification_pref_disabled'
								),
								'toggles'	=> $k === 'inline' ? array(
									'default'	=> array( 'member_notifications_' . $key . '_push' ),
									'optional'	=> array( 'member_notifications_' . $key . '_push' ),
								) : array()
							), NULL, NULL, NULL, 'member_notifications_' . $key . '_' . $k . '_editable' );
							$editableField->label = Member::loggedIn()->language()->addToStack( 'member_notifications_' . $k );
							$form->add( $editableField );
						}
						
						$nonEditableField = new Radio( 'member_notifications_' . $key . '_' . $k, in_array( $k, $defaultConfiguration[ $key ]['disabled'] ) ? 'disabled' : 'default', FALSE, array( 'options' => array(
							'default'	=> $k === 'push' ? 'admin_notification_pref_available' : 'admin_notification_pref_force',
							'disabled'	=> 'admin_notification_pref_disabled'
						) ), NULL, NULL, NULL, 'member_notifications_' . $key . '_' . $k );
						$nonEditableField->label = Member::loggedIn()->language()->addToStack( 'member_notifications_' . $k );
						$form->add( $nonEditableField );
					}
				}
			}
			elseif ( $option['type'] === 'custom' )
			{
				if ( ( isset( $option['adminCanSetDefault'] ) and $option['adminCanSetDefault'] ) or ( isset( $option['adminOnly'] ) and $option['adminOnly'] ) )
				{
					if ( isset( $option['admin_lang']['header'] ) and isset( $option['admin_lang'] ) )
					{
						$form->addHeader( $option['admin_lang']['header'] );
					}
					$form->add( $option['field'] );
				}
			}
		}
				
		if ( $values = $form->values() )
		{
			foreach ( Notification::availableOptions( NULL, $extension ) as $key => $option )
			{
				if ( $option['type'] === 'standard' )
				{		
					if ( isset( $option['extra'] ) )
					{
						foreach ( $option['extra'] as $k => $extra )
						{
							if ( isset( $extra['adminCanSetDefault'] ) and $extra['adminCanSetDefault'] )
							{
								$extension->saveExtra( NULL, $k, ( $values[ 'member_notifications_' . $key . '_' . $k ] === 'default' ) );
							}
						}
					}
					
					if ( !in_array( 'inline', $option['disabled'] ) or (  Notification::webPushEnabled()and !in_array( 'push', $option['disabled'] ) ) or !in_array( 'email', $option['disabled'] ) )
					{		
						$row = array(
							'notification_key'	=> $key,
							'default'			=> array(),
							'disabled'			=> array(),
							'editable'			=> $values[ 'notificationsettings_editable_' . $key ]
						);
						
						foreach ( array( 'inline', 'push', 'email' ) as $k )
						{							
							if ( !in_array( $k, $option['disabled'] ) )
							{
								if ( $k === 'push' )
								{
									if ( !Notification::webPushEnabled() )
									{
										continue;
									}
									$fieldToCheck = $values[ 'member_notifications_' . $key . '_push' ];
								}
								else
								{
									$fieldToCheck = $values[ 'notificationsettings_editable_' . $key ] ? $values[ 'member_notifications_' . $key . '_' . $k . '_editable' ] : $values[ 'member_notifications_' . $key . '_' . $k ];
								}
								
								if ( $fieldToCheck === 'default' )
								{
									$row['default'][] = $k;
								}
								elseif ( $fieldToCheck === 'disabled' )
								{
									$row['disabled'][] = $k;
								}
							}
						}
						
						$row['default'] = implode( ',', $row['default'] );
						$row['disabled'] = implode( ',', $row['disabled'] );
												
						Db::i()->replace( 'core_notification_defaults', $row );
						
						$extensionConfiguration = $extension->configurationOptions();
						if ( isset( $extensionConfiguration[ $key ] ) and $extensionConfiguration[ $key ]['type'] === 'standard' )
						{
							foreach ( $extensionConfiguration[ $key ]['notificationTypes'] as $notificationType )
							{
								$row['notification_key'] = $notificationType;
								Db::i()->replace( 'core_notification_defaults', $row );
							}
						}
					}
				}
				elseif ( $option['type'] === 'custom' )
				{
					if ( ( isset( $option['adminCanSetDefault'] ) and $option['adminCanSetDefault'] ) or ( isset( $option['adminOnly'] ) and $option['adminOnly'] ) )
					{
						$extension->saveExtra( NULL, $key, $option['field']->value );
					}
				}
			}
						
			Session::i()->log( 'acplog__notifications_edited' );
			Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=notifications' ), 'saved' );
		}
		
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=membersettings&controller=notifications' ), Member::loggedIn()->language()->addToStack( 'menu__core_membersettings_notifications' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'notifications__' . Request::i()->id );
		Output::i()->output = $form;
	}
	
	/**
	 * Member Auto Follow Preferences
	 *
	 * @return	void
	 */
	protected function updateMemberFollowPrefs() : void
	{
		Dispatcher::i()->checkAcpPermission( 'profiles_manage' );
		Session::i()->csrfCheck();
		
		/* Do standard preferences */
		Db::i()->delete( 'core_notification_preferences' );
		
		/* Do "extra" preferences */
		foreach ( Application::allExtensions( 'core', 'Notifications' ) as $k => $extension )
		{
			$extension->resetExtra();
		}

		/* Log and redirect */
		Session::i()->log( 'acplog__notification_settings_existing' );
		Output::i()->redirect( Url::internal( 'app=core&module=membersettings&controller=notifications' ), 'reset' );
	}
}