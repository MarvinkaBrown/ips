<?php
/**
 * @brief		ACP Notification: Configuration Errors
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jun 2018
 */

namespace IPS\core\extensions\core\AdminNotifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\core\AdminNotification;
use IPS\core\Setup\Upgrade;
use IPS\Data\Cache;
use IPS\Data\Cache\None;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function function_exists;
use function in_array;
use function intval;
use const IPS\CACHE_METHOD;
use const IPS\CIC;
use const IPS\TASK_OVERDUE_HOURS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ACP Notification: Test
 */
class ConfigurationError extends AdminNotification
{
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'system';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 2;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 2;
	
	/**
	 * Dangerous PHP functions
	 */
	public static array $dangerousPhpFunctions = array( 'exec', 'system', 'passthru', 'pcntl_exec', 'popen', 'proc_open', 'shell_exec' );
	
	/**
	 * Check for any issues we may need to send a notification about
	 *
	 * @return	void
	 */
	public static function runChecksAndSendNotifications() : void
	{
		/* Dangerous PHP functions */
		if ( count( static::enabledDangerousFunctions() ) )
		{
			AdminNotification::send( 'core', 'ConfigurationError', 'dangerousFunctions', FALSE );
		}
		else
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'dangerousFunctions' );
		}
		
		/* display_errors */
		if ( ( (bool) ini_get( 'display_errors' ) ) !== FALSE AND mb_strtolower( ini_get( 'display_errors' ) ) !== 'off' )
		{
			AdminNotification::send( 'core', 'ConfigurationError', 'displayErrors', FALSE );
		}
		else
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'displayErrors' );
		}
		
		/* System Requirement Recommendations */
		$requirementsAndRecommendations = Upgrade::systemRequirements();
		if ( isset( $requirementsAndRecommendations['advice'] ) and count( $requirementsAndRecommendations['advice'] ) )
		{
			AdminNotification::send( 'core', 'ConfigurationError', 'recommendations', FALSE );
		} 
		else
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'recommendations' );
		}
		
		/* orig_ database tables */
		if( !Settings::i()->orig_tables_checked )
		{
			/* Check if we have any orig_* tables */
			$tables = Db::i()->getTables( 'orig_' . Db::i()->prefix );

			/* If we don't have any, we're good. Set a flag so we don't check this every time */
			if( !count( $tables ) )
			{
				Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 1 ), array( 'conf_key=?', 'orig_tables_checked' ) );
				unset( Store::i()->settings );
				AdminNotification::remove( 'core', 'ConfigurationError', 'origTables' );
			}
			else
			{
				/* Determine if the background queue task has already been launched */
				try
				{
					Db::i()->select( '*', 'core_queue', array( "`key`=?", 'CleanupOrigTables' ) )->first();
					$inProgress = TRUE;
				}
				catch( UnderflowException $e )
				{
					$inProgress = FALSE;
				}

				/* If it isn't, show a warning */
				if( !$inProgress )
				{
					AdminNotification::send( 'core', 'ConfigurationError', 'origTables', FALSE, NULL, TRUE ); // We don't send an email for this one since it "happens" as part of the upgrade and is more just an FYI
				}
			}
		}
		
		/* Any tasks which were supposed to have run more than 36 hours ago */
		if ( !CIC )
		{
			$taskWasSupposedToRun = Db::i()->select( 'next_run', 'core_tasks', array( 'core_tasks.enabled=1 AND core_applications.app_enabled=1' ), 'next_run ASC' )
				->join( 'core_applications', array( 'core_applications.app_directory=core_tasks.app' ) )
				->first();
			
			if ( ( time() - $taskWasSupposedToRun ) > ( TASK_OVERDUE_HOURS * 3600 ) )
			{
				AdminNotification::send( 'core', 'ConfigurationError', 'tasksNotRunning', FALSE );
			}
			else
			{
				AdminNotification::remove( 'core', 'ConfigurationError', 'tasksNotRunning' );
			}
		}
		
		/* Data storage not working */
		if( !CIC AND (!Store::testStore() OR Db::i()->select( 'COUNT(*)', 'core_log', array( '`category`=? AND `time`>?', 'datastore', DateTime::create()->sub( new DateInterval( 'PT1H' ) )->getTimestamp() ) )->first() >= 10 ) )
		{
			if ( Settings::i()->last_data_store_update < DateTime::create()->sub( new DateInterval( 'PT24H' ) )->getTimestamp() )
			{
				AdminNotification::send( 'core', 'ConfigurationError', 'dataStorageBroken', FALSE );
			}
		}
		else
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'dataStorageBroken' );
		}
		
		/* Cache Set Up */
		if ( !CIC AND CACHE_METHOD AND CACHE_METHOD != 'None' AND Cache::i() instanceof None )
		{
			AdminNotification::send( 'core', 'ConfigurationError', 'cacheBroken', FALSE );
		}
		else
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'cacheBroken' );
		}
		
		/* CiC Email Quota */
		if ( CIC )
		{
			try
			{
				$cicEmails = Url::external( IPS::$cicConfig['email']['quota_check'] )->setQueryString('account', \IPS\Cicloud\getCicUsername() )->request()->get()->decodeJson();
				
				if ( isset( $cicEmails['status'] ) AND $cicEmails['status'] == 'BLOCKED' )
				{
					AdminNotification::send( 'core', 'ConfigurationError', 'cicEmailQuota', FALSE );
				}
				else
				{
					AdminNotification::remove( 'core', 'ConfigurationError', 'cicEmailQuota' );
				}
			}
			catch( Exception $e ) { }
		}

		/* Failed Emails, show notification if there are errors logs that haven't yet triggered a notification */
		if( Email::countFailedMail() >= 3 )
		{
			AdminNotification::send( 'core', 'ConfigurationError', 'failedMail', TRUE, NULL, TRUE );
		}
		else
		{
			AdminNotification::remove( 'core', 'ConfigurationError', 'failedMail' );
			Db::i()->update( 'core_mail_error_logs', [ 'mlog_notification_sent' => TRUE ], [ 'mlog_notification_sent=?', 0 ] );
		}
	}
	
	/**
	 * Check enabled dangerous functions
	 *
	 * @return	array
	 */
	public static function enabledDangerousFunctions() : array
	{
		$functions = array();
		foreach ( static::$dangerousPhpFunctions as $function )
		{
			if ( function_exists( $function ) )
			{
				$functions[] = $function;
			}
		}
		return $functions;
	}
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	public static function settingsTitle(): string
	{
		return 'acp_notification_ConfigurationError';
	}
	
	/**
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional(): bool
	{
		return FALSE;
	}
	
	/**
	 * Is this type of notification might recur (controls what options will be available for the email setting)
	 *
	 * @return	bool
	 */
	public static function mayRecur(): bool
	{
		return FALSE;
	}
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ): bool
	{
		return ( $member->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'advanced_manage_tasks' ) or
			$member->hasAcpRestriction( 'core', 'overview', 'system_notifications' ) or
			$member->hasAcpRestriction( 'core', 'overview', 'system_notifications' ) or
			$member->hasAcpRestriction( 'core', 'support', 'get_support' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'email_errorlog' ) or $member->hasAcpRestriction( 'core', 'settings', 'email_manage' ) or
			$member->hasAcpRestriction( 'core', 'overview', 'system_notifications' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'advanced_manage' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'general_manage' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'advanced_manage' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'advanced_manage' ) or
			$member->hasAcpRestriction( 'core', 'overview', 'system_notifications' ) or
			$member->hasAcpRestriction( 'core', 'settings', 'login_access' )
		);
	}
	
	/**
	 * Can a member view this notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function visibleTo( Member $member ) : bool
	{
		if ( mb_substr( $this->extra, 0, 12 ) === 'supportAdmin' )
		{
			return $member->hasAcpRestriction( 'core', 'members', 'member_delete_admin' );
		}
		elseif ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' )
		{
			return $member->hasAcpRestriction( 'core', 'settings', 'advanced_manage_tasks' );
		}
		elseif ( $this->extra === 'dangerousFunctions' )
		{
			return $member->hasAcpRestriction( 'core', 'overview', 'system_notifications' );
		}
		elseif ( $this->extra === 'displayErrors' )
		{
			return $member->hasAcpRestriction( 'core', 'overview', 'system_notifications' );
		}
		elseif ( $this->extra === 'recommendations' )
		{
			return $member->hasAcpRestriction( 'core', 'support', 'get_support' );
		}
		elseif ( $this->extra === 'failedMail' )
		{
			return $member->hasAcpRestriction( 'core', 'settings', 'email_errorlog' ) or $member->hasAcpRestriction( 'core', 'settings', 'email_manage' );
		}
		elseif ( $this->extra === 'origTables' )
		{
			return $member->hasAcpRestriction( 'core', 'overview', 'system_notifications' );
		}
		elseif ( $this->extra === 'tasksNotRunning' )
		{
			return $member->hasAcpRestriction( 'core', 'settings', 'advanced_manage' );
		}
		elseif ( $this->extra === 'siteOffline' )
		{
			return $member->hasAcpRestriction( 'core', 'settings', 'general_manage' );
		}
		elseif ( $this->extra === 'dataStorageBroken' )
		{
			return $member->hasAcpRestriction( 'core', 'settings', 'advanced_manage' );
		}
		elseif ( $this->extra === 'cacheBroken' )
		{
			return $member->hasAcpRestriction( 'core', 'settings', 'advanced_manage' );
		}
		elseif ( $this->extra === 'cicEmailQuota' )
		{
			return $member->hasAcpRestriction( 'core', 'overview', 'system_notifications' );
		}

		return false;
	}
	
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	public function title(): string
	{		
		if ( mb_substr( $this->extra, 0, 12 ) === 'supportAdmin' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_support_account');
		}
		elseif ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' )
		{
			return Member::loggedIn()->language()->addToStack( 'dashboard_tasks_broken', FALSE, array( 'sprintf' => array( Task::load( intval( mb_substr( $this->extra, 9 ) ) )->key ) ) );
		}
		elseif ( $this->extra === 'dangerousFunctions' )
		{
			return Member::loggedIn()->language()->addToStack('disable_functions_title');
		}
		elseif ( $this->extra === 'displayErrors' )
		{
			return Member::loggedIn()->language()->addToStack('display_errors_title');
		}
		elseif ( $this->extra === 'recommendations' )
		{
			return Member::loggedIn()->language()->addToStack('system_check_title');
		}
		elseif ( $this->extra === 'failedMail' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_email_broken');
		}
		elseif ( $this->extra === 'origTables' )
		{
			return Member::loggedIn()->language()->addToStack('block_core_OrigTables');
		}
		elseif ( $this->extra === 'tasksNotRunning' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_tasksrun_broken');
		}
		elseif ( $this->extra === 'siteOffline' )
		{
			return Member::loggedIn()->language()->addToStack('offline_message_title');
		}
		elseif ( $this->extra === 'dataStorageBroken' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_datastore_broken');
		}
		elseif ( $this->extra === 'cacheBroken' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_invalid_cachesetup');
		}
		elseif ( $this->extra === 'cicEmailQuota' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_cic_email_quota');
		}
		elseif( $this->extra === 'groupPromotionGroup' )
		{
			return Member::loggedIn()->language()->addToStack('grouppromotion_error_group');
		}
		else
		{
			return htmlentities( $this->extra, ENT_DISALLOWED, 'UTF-8', FALSE );
		}
	}
	
	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle(): ?string
	{
		if ( mb_substr( $this->extra, 0, 12 ) === 'supportAdmin' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_support_account_desc');
		}
		elseif ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_tasks_broken_desc');
		}
		elseif ( $this->extra === 'dangerousFunctions' )
		{
			return Member::loggedIn()->language()->addToStack('disable_functions_desc');
		}
		elseif ( $this->extra === 'displayErrors' )
		{
			return Member::loggedIn()->language()->addToStack('display_errors_subtitle');
		}
		elseif ( $this->extra === 'recommendations' )
		{
			return Member::loggedIn()->language()->addToStack('system_check_recommended_blurb');
		}
		elseif ( $this->extra === 'failedMail' )
		{
			return Member::loggedIn()->language()->addToStack( 'dashboard_email_broken_desc_1', FALSE, array( 'sprintf' => array( Email::countFailedMail() ) ) );
		}
		elseif ( $this->extra === 'origTables' )
		{
			return Member::loggedIn()->language()->addToStack('orig_cleanup_desc');
		}
		elseif ( $this->extra === 'tasksNotRunning' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_tasksrun_broken_desc');
		}
		elseif ( $this->extra === 'siteOffline' )
		{
			return Member::loggedIn()->language()->addToStack( ( Settings::i()->task_use_cron == 'normal' AND !CIC ) ? 'offline_message_desc_task' : 'offline_message_desc_notask' );
		}
		elseif ( $this->extra === 'dataStorageBroken' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_datastore_broken_subtitle');
		}
		elseif ( $this->extra === 'cacheBroken' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_invalid_cachesetup_subtitle');
		}
		elseif ( $this->extra === 'cicEmailQuota' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_cic_email_quota_subtitle');
		}

		return NULL;
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	public function body(): ?string
	{
		if ( mb_substr( $this->extra, 0, 12 ) === 'supportAdmin' )
		{
			return Theme::i()->getTemplate( 'notifications', 'core' )->supportAccountPresent( Member::load( intval( mb_substr( $this->extra, 13 ) ) ) );
		}
		elseif ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' )
		{
			$task = Task::load( intval( mb_substr( $this->extra, 9 ) ) );
			$langKey = 'task__' . $task->key;
			
			return Theme::i()->getTemplate( 'notifications', 'core' )->lockedTask( $task,  Member::loggedIn()->language()->checkKeyExists( $langKey ) ? $langKey : NULL );
		}
		elseif ( $this->extra === 'dangerousFunctions' )
		{
			return Theme::i()->getTemplate( 'notifications', 'core' )->dangerousPhpFunctions( static::enabledDangerousFunctions() );
		}
		elseif ( $this->extra === 'displayErrors' )
		{
			return Theme::i()->getTemplate( 'notifications', 'core' )->displayErrors();
		}
		elseif ( $this->extra === 'failedMail' )
		{
			$table = NULL;
			if ( Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'email_errorlog' ) )
			{
				$where = array( array( 'mlog_notification_sent=?', FALSE ) );
				$table = \IPS\core\modules\admin\settings\email::emailErrorLogTable( Url::internal('app=core&module=overview&controller=notifications&_table=core_ConfigurationError'), $where );
				$table->limit = 10;
			}
			
			return Theme::i()->getTemplate( 'notifications', 'core' )->failedMail( Email::countFailedMail(), $table );
		}
		elseif ( $this->extra === 'recommendations' )
		{
			$requirementsAndRecommendations = Upgrade::systemRequirements();
			$advice = array();

			if( isset( $requirementsAndRecommendations['advice'] ) )
			{
				foreach( $requirementsAndRecommendations['advice'] as $category )
				{
					$advice = array_merge( $advice, $category );
				}
			}
			return Theme::i()->getTemplate( 'notifications', 'core' )->systemRecommendations( $advice );
		}
		elseif ( $this->extra === 'origTables' )
		{
			return Theme::i()->getTemplate( 'notifications', 'core' )->origTables();
		}
		elseif ( $this->extra === 'tasksNotRunning' )
		{
			$cronCommand = PHP_BINDIR . '/php -d memory_limit=-1 -d max_execution_time=0 ' . \IPS\ROOT_PATH . '/applications/core/interface/task/task.php ' . Settings::i()->task_cron_key;
			$webCronUrl = (string) Url::internal( 'applications/core/interface/task/web.php?key=' . Settings::i()->task_cron_key, 'none' );
			
			return Theme::i()->getTemplate( 'notifications', 'core' )->tasksNotRunning( $this, $cronCommand, $webCronUrl );
		}
		elseif ( $this->extra === 'siteOffline' )
		{
			return Theme::i()->getTemplate( 'notifications', 'core', 'admin' )->siteOffline();
		}
		elseif ( $this->extra === 'dataStorageBroken' )
		{
			if( CIC )
			{
				return Member::loggedIn()->language()->addToStack('dashboard_datastore_broken_desc_cic');
			}
			return Member::loggedIn()->language()->addToStack('dashboard_datastore_broken_desc');
		}
		elseif ( $this->extra === 'cacheBroken' )
		{
			if( CIC )
			{
				return Member::loggedIn()->language()->addToStack('dashboard_invalid_cachesetup_desc_cic');
			}
			return Member::loggedIn()->language()->addToStack('dashboard_invalid_cachesetup_desc');
		}
		elseif ( $this->extra === 'cicEmailQuota' )
		{
			return Member::loggedIn()->language()->addToStack('dashboard_cic_email_quota_desc');
		}

		return null;
	}
	
	/**
	 * Severity
	 *
	 * @return	string
	 */
	public function severity(): string
	{
		if ( $this->extra === 'siteOffline' or $this->extra === 'cicEmailQuota' )
		{
			return static::SEVERITY_CRITICAL;
		}
		elseif ( in_array( $this->extra, array( 'tasksNotRunning', 'dataStorageBroken', 'cacheBroken' ) ) )
		{
			return static::SEVERITY_HIGH;
		}
		elseif( $this->extra === 'groupPromotionGroup' )
		{
			return Member::loggedIn()->language()->addToStack('grouppromotion_error_group_desc');
		}
		else
		{
			return static::SEVERITY_NORMAL;
		}
	}
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	public function dismissible(): string
	{		
		if ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' or in_array( $this->extra, array( 'failedMail', 'origTables', 'siteOffline', 'dataStorageBroken', 'cacheBroken' ) ) )
		{
			return static::DISMISSIBLE_NO;
		}
		else
		{
			return static::DISMISSIBLE_TEMPORARY;
		}
	}
	
	/**
	 * Style
	 *
	 * @return	string
	 */
	public function style(): string
	{
		if ( $this->extra === 'siteOffline' )
		{
			if ( Settings::i()->task_use_cron == 'normal' AND !CIC )
			{
				return static::STYLE_WARNING;
			}
			else
			{
				return static::STYLE_INFORMATION;
			}
		}

		if( $this->extra === 'recommendations' )
		{
			return static::STYLE_ERROR;
		}

		if ( mb_substr( $this->extra, 0, 12 ) === 'supportAdmin' or $this->extra === 'origTables' )
		{
			return static::STYLE_INFORMATION;
		}
		elseif ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' or in_array( $this->extra, array( 'failedMail', 'tasksNotRunning', 'dataStorageBroken', 'cacheBroken', 'cicEmailQuota' ) ) )
		{
			return static::STYLE_ERROR;
		}
		else
		{
			return static::STYLE_WARNING;
		}
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url|null                    webg
	 */
	public function link(): Url|null
	{
		if ( mb_substr( $this->extra, 0, 12 ) === 'supportAdmin' )
		{
			return Member::load( intval( mb_substr( $this->extra, 13 ) ) )->acpUrl();
		}
		else
		{
			return parent::link();
		}
	}
	
	/**
	 * Should this notification dismiss itself?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @return	bool
	 */
	public function selfDismiss(): bool
	{
		if ( $this->extra === 'dangerousFunctions' )
		{
			return !count( static::enabledDangerousFunctions() );
		}
		elseif ( $this->extra === 'displayErrors' )
		{
			return !( ( (bool) ini_get( 'display_errors' ) ) !== FALSE AND mb_strtolower( ini_get( 'display_errors' ) ) !== 'off' );
		}
		elseif ( $this->extra === 'recommendations' )
		{
			$requirementsAndRecommendations = Upgrade::systemRequirements();
			return !isset( $requirementsAndRecommendations['advice'] ) or !count( $requirementsAndRecommendations['advice'] );
		}
		elseif ( mb_substr( $this->extra, 0, 8 ) === 'taskLock' )
		{
			try
			{
				return !Task::load( intval( mb_substr( $this->extra, 9 ) ) )->enabled;
			}
			catch( RuntimeException | OutOfRangeException $e )
			{
				return TRUE;
			}
		}
		return parent::selfDismiss();
	}
}