<?php
/**
 * @brief		ACP Notification Center
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 June 2018
 */

namespace IPS\core\modules\admin\overview;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\AdminNotification;
use IPS\core\extensions\core\AdminNotifications\ConfigurationError;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form\Matrix;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Task;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification Center
 */
class notifications extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Show notifications
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$notifications = AdminNotification::notifications();
		
		if ( Request::i()->isAjax() and !isset( Request::i()->_table ) )
		{
			Output::i()->json( array( 'data' => Theme::i()->getTemplate('notifications')->popupList( $notifications ), 'count' => count( $notifications ) ) );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('acp_notifications');
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/notifications.css', 'core', 'admin' ) );
			Output::i()->output = Theme::i()->getTemplate('notifications')->index( $notifications );
			
			Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'title'		=> 'notification_options',
					'icon'		=> 'cog',
					'link'		=> Url::internal( 'app=core&module=overview&controller=notifications&do=settings' ),
				),
			);
		}
	}
	
	/**
	 * Dismiss a notification
	 *
	 * @return	void
	 */
	protected function dismiss() : void
	{
		Session::i()->csrfCheck();
		
		AdminNotification::dismissNotification( Request::i()->id );
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'status' => 'OK' ) );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=notifications' ) );
		}
	}
	
	/**
	 * Notification Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$preferences = iterator_to_array( Db::i()->select( '*', 'core_acp_notifications_preferences', array( '`member`=?', Member::loggedIn()->member_id ) )->setKeyField('type') );
		
		$matrix = new Matrix;
		$matrix->langPrefix = 'acp_notifications_';
		$matrix->manageable = FALSE;
		$matrix->columns = array(
			'name'	=> function( $key, $value, $data )
			{
				return $value;
			},
			'view'	=> function( $key, $value, $data )
			{
				return new YesNo( $key, $data['maybeOptional'] ? $value : TRUE, FALSE, array( 'disabled' => !$data['maybeOptional'] ) );
			},
			'email'	=> function( $key, $value, $data )
			{
				if ( $data['customEmail'] )
				{
					return $data['customEmail'];
				}
				else
				{
					$options = $data['mayRecur'] ? array(
						'never'		=> 'acp_notifications_email_never',
						'once'		=> 'acp_notifications_email_once',
						'always'	=> 'acp_notifications_email_always',
					) : array(
						'never'		=> 'acp_notifications_email_never',
						'once'		=> 'acp_notifications_email_yes',
					);
					
					return new Select( $key, $value, FALSE, array( 'options' => $options, 'class' => 'ipsField_medium' ) );
				}
			},
		);
		
		$rows = array();
		foreach ( Application::allExtensions( 'core', 'AdminNotifications', TRUE, NULL, NULL, FALSE ) as $ext )
		{
			if ( $ext::permissionCheck( Member::loggedIn() ) )
			{
				$exploded = explode( '\\', $ext );
				$key = "{$exploded[1]}_{$exploded[5]}";
				
				$rows[ $ext::$group ]['priority'] = $ext::$groupPriority;
				$rows[ $ext::$group ]['rows'][ $ext ] = array(
					'name'			=> $ext::settingsTitle(),
					'view'			=> $preferences[$key]['view'] ?? $ext::defaultValue(),
					'email'			=> $preferences[$key]['email'] ?? 'never',
					'maybeOptional' => $ext::mayBeOptional(),
					'mayRecur' 		=> $ext::mayRecur(),
					'customEmail' 	=> $ext::customEmailConfigurationSetting( "{$ext}[email]", $preferences[$key]['email'] ?? NULL ),
					'priority' 		=> $ext::$itemPriority,
				);
			}
		}
				
		uasort( $rows, function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		});
		
		foreach ( $rows as $group => $data )
		{
			$matrix->rows[] = Member::loggedIn()->language()->addToStack("acp_notification_group_{$group}");
			
			uasort( $data['rows'], function( $a, $b ) {
				return $a['priority'] - $b['priority'];
			});
			
			foreach ( $data['rows'] as $ext => $row )
			{
				$matrix->rows[ $ext ] = $row;
			}
		}
						
		if ( $values = $matrix->values() )
		{
			foreach ( $values as $ext => $_values )
			{
				$exploded = explode( '\\', $ext );
				$key = "{$exploded[1]}_{$exploded[5]}";
				
				$v = Request::i()->$ext;

				/* @var AdminNotification $ext */
				Db::i()->insert( 'core_acp_notifications_preferences', array(
					'member'	=> Member::loggedIn()->member_id,
					'type'		=> $key,
					'view'		=> $ext::mayBeOptional() ? $_values['view'] : TRUE,
					'email'		=> $v['email'] ?? 'never',
				), TRUE );
			}
			
			if( isset( Store::i()->acpNotificationIds ) )
			{
				$notificationCache = Store::i()->acpNotificationIds;
				unset( $notificationCache[ Member::loggedIn()->member_id ] );
				Store::i()->acpNotificationIds = $notificationCache;
			}
			
			Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=notifications' ) );
		}
		
		Output::i()->title = Member::loggedIn()->language()->addToStack('notification_options');
		Output::i()->output = Theme::i()->getTemplate('forms')->blurb( 'acp_notifications_settings_blurb' ) . $matrix;
	}
	
	/**
	 * Recheck configuration errors
	 *
	 * @return	void
	 */
	protected function configurationErrorChecks() : void
	{
		Session::i()->csrfCheck();
		ConfigurationError::runChecksAndSendNotifications();
		Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=notifications' ) );
	}
	
	/**
	 * Delete orig_ database tables
	 *
	 * @return	void
	 */
	protected function removeOrigTables() : void
	{
		Session::i()->csrfCheck();
		
		$tables = Db::i()->getTables( 'orig_' . Db::i()->prefix );
		Task::queue( 'core', 'CleanupOrigTables', array( 'originalCount' => count( $tables ) ), 5 );
		
		AdminNotification::remove( 'core', 'ConfigurationError', 'origTables' );
		
		Output::i()->redirect( Url::internal( 'app=core&module=overview&controller=notifications' ) );
	}
}