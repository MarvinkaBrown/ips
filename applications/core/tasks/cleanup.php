<?php
/**
 * @brief		Daily Cleanup Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Aug 2013
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\Content;
use IPS\core\AdminNotification;
use IPS\DateTime;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use IPS\Widget;
use OutOfRangeException;
use RuntimeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Daily Cleanup Task
 */
class cleanup extends Task
{
	/**
	 * Execute
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	RuntimeException
	 */
	public function execute() : mixed
	{
		/* Delete old failed guest logins for accounts that do not exist */
		if( $maxCutoff = (int) Settings::i()->bruteforce_global_period )
		{
			Db::i()->delete( 'core_login_failures', [
				'login_date<? AND login_ip_address IS NOT NULL AND login_email IS NOT NULL',
				( new DateTime )->sub( new DateInterval( 'PT' . $maxCutoff . 'M' ) )->sub( new DateInterval( 'PT24H' ) )->getTimestamp()
			] );
		}

		/* Delete old password / security answer reset requests */
		Db::i()->delete( 'core_validating', array( '( lost_pass=1 OR forgot_security=1 ) AND email_sent < ?', DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() ) );

		/* If we are currently pruning any large tables via a bg task, find out so we don't try to prune them normally here as well. The bg task should finish first. */
		$currentlyPruning = array();

		foreach( Db::i()->select( '*', 'core_queue', array( '`key`=?', 'PruneLargeTable' ) ) as $pruneTask )
		{
			$data = json_decode( $pruneTask['data'], true );

			$currentlyPruning[] = $data['table'];
		}
				
		/* Delete old validating members */
		if ( Settings::i()->validate_day_prune )
		{
			$select = Db::i()->select( 'core_validating.member_id, core_members.member_posts', 'core_validating', array( 'core_validating.new_reg=1 AND core_validating.coppa_user<>1 AND core_validating.entry_date<? AND core_validating.lost_pass<>1 AND core_validating.user_verified=0 AND !(core_members.members_bitoptions2 & 16384 ) AND core_members.member_posts < 1 AND core_validating.do_not_delete=0', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->validate_day_prune . 'D' ) )->getTimestamp() ) )->join( 'core_members', 'core_members.member_id=core_validating.member_id' );

			foreach ( $select as $row )
			{
				$member = Member::load( $row['member_id'] );

				if( $member->member_id )
				{
					$member->delete();
				}
				else
				{
					Db::i()->delete( 'core_validating', array( 'member_id=?', $row['member_id'] ) );
				}
			}
		}

		/* Delete file system logs */
		if( Settings::i()->file_log_pruning )
		{
			Db::i()->delete( 'core_file_logs', array( 'log_date < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->file_log_pruning . 'D' ) )->getTimestamp() ) );
		}

		/* Delete edit history past prune date */
		if( Settings::i()->edit_log_prune > 0 )
		{
			Db::i()->delete( 'core_edit_history', array( 'time < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->edit_log_prune . 'D' ) )->getTimestamp() ) );
		}

		/* Delete task logs older than the prune-since date */
		if( Settings::i()->prune_log_tasks )
		{
			Db::i()->delete( 'core_tasks_log', array( 'time < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_tasks . 'D' ) )->getTimestamp() ) );
		}

		/* Delete email error logs older than the prune-since date */
		if( Settings::i()->prune_log_email_error )
		{
			Db::i()->delete( 'core_mail_error_logs', array( 'mlog_date < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_email_error . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and admin logs */
		if( Settings::i()->prune_log_admin )
		{
			Db::i()->delete( 'core_admin_logs', array( 'ctime < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_admin . 'D' ) )->getTimestamp() ) );
		}

		/* ...and moderators logs */
		if( Settings::i()->prune_log_moderator )
		{
			Db::i()->delete( 'core_moderator_logs', array( 'ctime < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_moderator . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and error logs */
		if( Settings::i()->prune_log_error )
		{
			Db::i()->delete( 'core_error_logs', array( 'log_date < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_error . 'D' ) )->getTimestamp() ) );

			/* If we don't have any logs left, remove any notifications */
			if( Db::i()->select( 'count(log_id)', 'core_error_logs' )->first() === 0 )
			{
				AdminNotification::remove( 'core', 'Error' );
			}
		}
		
		/* ...and spam service logs */
		if( Settings::i()->prune_log_spam )
		{
			Db::i()->delete( 'core_spam_service_log', array( 'log_date < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_spam . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and admin login logs */
		if( Settings::i()->prune_log_adminlogin )
		{
			Db::i()->delete( 'core_admin_login_logs', array( 'admin_time < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_adminlogin . 'D' ) )->getTimestamp() ) );
		}

		/* ...and statistics */
		if( Settings::i()->stats_online_users_prune )
		{
			Db::i()->delete( 'core_statistics', array( 'type=? AND time < ?', 'online_users', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_online_users_prune . 'D' ) )->getTimestamp() ) );
		}

		if( Settings::i()->stats_keywords_prune )
		{
			Db::i()->delete( 'core_statistics', array( 'type=? AND time < ?', 'keyword', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_keywords_prune . 'D' ) )->getTimestamp() ) );
		}

		if( Settings::i()->stats_search_prune )
		{
			Db::i()->delete( 'core_statistics', array( 'type=? AND time < ?', 'search', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->stats_search_prune . 'D' ) )->getTimestamp() ) );
		}

		if( Settings::i()->prune_log_emailstats > 0 )
		{
			Db::i()->delete( 'core_statistics', array( "type IN('emails_sent','email_views','email_clicks') AND time < ?", DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_log_emailstats . 'D' ) )->getTimestamp() ) );
		}

		/* ...and guest details stored with reports */
		if( Settings::i()->report_guest_details_store_days )
		{
			Db::i()->update( 'core_rc_reports', [ 'guest_name' => null, 'guest_email' => null ], array( 'date_reported < ?', DateTime::create()->sub( new DateInterval( 'P' . \IPS\Settings::i()->report_guest_details_store_days . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and geoip cache */
		Db::i()->delete( 'core_geoip_cache', array( 'date < ?', DateTime::create()->sub( new DateInterval( 'P7D' ) )->getTimestamp() ) );

		/* ...and API logs */
		$successfulCodes = [ 200, 201, 202, 203, 204, 205, 206, 207, 208, 226 ];
		if( Settings::i()->api_log_prune )
		{
			Db::i()->delete( 'core_api_logs', array( 'date < ? and response_code IN(' . implode( ',', $successfulCodes ) . ')', DateTime::create()->sub( new \DateInterval( 'P' . Settings::i()->api_log_prune . 'D' ) )->getTimestamp() ) );
		}

		if( Settings::i()->api_log_prune_failures )
		{
			Db::i()->delete( 'core_api_logs', array( 'date < ? and response_code NOT IN(' . implode( ',', $successfulCodes ) . ')', DateTime::create()->sub( new \DateInterval( 'P' . Settings::i()->api_log_prune_failures . 'D' ) )->getTimestamp() ) );
		}

		/* ...and non-member history */
		if( Settings::i()->prune_member_history AND !in_array( 'core_member_history', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_member_history', array( 'log_date < ? and log_app != ?', DateTime::create()->sub( new \DateInterval( 'P' . Settings::i()->prune_member_history . 'D' ) )->getTimestamp(), 'nexus' ) );
		}

		/* ...and Nexus member history */
		if( Application::appIsEnabled('nexus') and Settings::i()->nexus_prune_history AND !\in_array( 'core_member_history', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_member_history', array( 'log_date < ? and log_app = ?', DateTime::create()->sub( new \DateInterval( 'P' . Settings::i()->nexus_prune_history . 'D' ) )->getTimestamp(), 'nexus' ) );
		}
		
		/* ...and webhook logs */
		if( Settings::i()->webhook_logs_success )
		{
			Db::i()->delete( 'core_api_webhook_fires', array( "status='successful' AND time < ?", DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->webhook_logs_success . 'D' ) )->getTimestamp() ) );
		}
		if( Settings::i()->webhook_logs_fail )
		{
			Db::i()->delete( 'core_api_webhook_fires', array( "status='failed' AND time < ?", DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->webhook_logs_fail . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and points log */
		if( Settings::i()->prune_points_log )
		{
			Db::i()->delete( 'core_achievements_log', array( 'datetime < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_points_log . 'D' ) )->getTimestamp() ) );
			Db::i()->delete( 'core_points_log', array( 'datetime < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_points_log . 'D' ) )->getTimestamp() ) );
		}
		
		/* Delete old notifications */
		if ( Settings::i()->prune_notifications )
		{
			$memberIds	= array();

			foreach( Db::i()->select( '`member`', 'core_notifications', array( 'sent_time < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_notifications . 'D' ) )->getTimestamp() ) ) as $member )
			{
				$memberIds[ $member ]	= $member;
			}

			Db::i()->delete( 'core_notifications', array( 'sent_time < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_notifications . 'D' ) )->getTimestamp() ) );

			foreach( $memberIds as $member )
			{
				Member::load( $member )->recountNotifications();
			}
		}

		/* Delete old follows */
		if ( Settings::i()->prune_follows AND !in_array( 'core_follow', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_follow', array( 'follow_app!=? AND follow_area!=? AND follow_member_id IN(?)', 'core', 'member', Db::i()->select( 'member_id', 'core_members', array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_follows . 'D' ) )->getTimestamp() ) ) ) );

			/* And clear the cache so it can rebuild */
			Db::i()->delete( 'core_follow_count_cache' );
		}

		/* Delete old item markers */
		if ( Settings::i()->prune_item_markers AND !in_array( 'core_item_markers', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_item_markers', array( 'item_member_id IN(?)', Db::i()->select( 'member_id', 'core_members', array( 'last_activity < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_item_markers . 'D' ) )->getTimestamp() ) ) ) );
		}

		/* Delete old seen IP addresses */
		if ( Settings::i()->prune_known_ips AND !in_array( 'core_members_known_ip_addresses', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_members_known_ip_addresses', array( 'last_seen < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_known_ips . 'D' ) )->getTimestamp() ) );
		}

		/* Delete old seen devices */
		if ( Settings::i()->prune_known_devices AND !in_array( 'core_members_known_devices', $currentlyPruning ) )
		{
			Db::i()->delete( 'core_members_known_devices', array( 'last_seen < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_known_devices . 'D' ) )->getTimestamp() ) );
		}

		/* Delete moved links */
		if ( Settings::i()->topic_redirect_prune )
		{
			foreach ( Content::routedClasses( FALSE, FALSE, TRUE ) as $class )
			{
				if ( isset( $class::$databaseColumnMap['moved_on'] ) )
				{
					foreach ( new ActiveRecordIterator( Db::i()->select( '*', $class::$databaseTable, array( $class::$databasePrefix . $class::$databaseColumnMap['moved_on'] . '>0 AND ' . $class::$databasePrefix . $class::$databaseColumnMap['moved_on'] . '<?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->topic_redirect_prune . 'D' ) )->getTimestamp() ), $class::$databasePrefix . $class::$databaseColumnId, 100 ), $class ) as $item )
					{
						$item->delete();
					}
				}
			}
		}
		
		/* Remove warnings points */
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_members_warn_logs', array( 'wl_expire_date>0 AND wl_expire_date<?', time() ), 'wl_date ASC', 25 ), 'IPS\core\Warnings\Warning' ) as $warning )
		{
			$member = Member::load( $warning->member );
			$member->warn_level -= $warning->points;
			$member->save();
			
			Db::i()->update( 'core_members_warn_logs', array( 'wl_removed_on' => $warning->expire_date ), array( 'wl_id=?', $warning->id ) );
			
			$warning->expire_date = 0;
			$warning->save();
		}
		
		/* Remove widgets */
		if ( Application::appIsEnabled('cms') )
		{
			\IPS\cms\Widget::emptyTrash();
		}
		else
		{
			Widget::emptyTrash();
		}
		
		/* Reset expired "moderate content till.." timestamps */
		Db::i()->update( 'core_members', array( 'mod_posts' => 0 ), array( 'mod_posts != -1 and mod_posts <?', time() ) );

		/* Set expired announcements inactive */
		Db::i()->update( 'core_announcements', array( 'announce_active' => 0 ), array( 'announce_active = 1 and announce_end > 0 and announce_end <?', time() ) );
		
		/* Delete old Google Authenticator code uses */
		Db::i()->delete( 'core_googleauth_used_codes', array( 'time < ?', DateTime::create()->sub( new DateInterval( 'PT1M' ) )->getTimestamp() ) );

		/* Close open polls that need closing */
		Db::i()->update( 'core_polls', array( 'poll_closed' => 1 ), array( 'poll_closed=? AND poll_close_date>? AND poll_close_date<?', 0, -1, time() ) );
		
		/* Delete expired oAuth Authorization Codes */
		Db::i()->delete( 'core_oauth_server_authorization_codes', array( 'expires<?', time() ) );
		Db::i()->delete( 'core_oauth_server_access_tokens', array( 'refresh_token_expires<?', time() ) );
		Db::i()->delete( 'core_oauth_authorize_prompts', array( 'timestamp<?', ( time() - 300 ) ) );
		
		/* Delete any unfinished "Post before register" posts */
		foreach ( Db::i()->select( '*', 'core_post_before_registering', array( "`member` IS NULL AND followup IS NOT NULL AND followup<" . ( time() - ( 86400 * 6 ) ) ), 'followup ASC' ) as $row )
		{
			$class = $row['class'];
			try
			{
				$class::load( $row['id'] )->delete();
			}
			catch ( OutOfRangeException $e ) { }

			Db::i()->delete( 'core_post_before_registering', array( 'class=? AND id=?', $row['class'], $row['id'] ) );
		}
		
		/* Delete old core follow caches */
		Db::i()->delete( 'core_follow_count_cache', array( 'added < ?', DateTime::create()->sub( new DateInterval( 'P30D' ) )->getTimestamp() ) );
		
		/* Delete old core statistic caches */
		Db::i()->delete( 'core_item_statistics_cache', array( 'cache_added < ?', DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() ) );

		/* Delete alerts */
		Db::i()->delete( 'core_alerts', array( 'alert_end > 0 and alert_end < ?', time() ) );

		/* Trigger queue item to prune old PMs with no replies in x days */
		if( Settings::i()->prune_pms )
		{
			/* Get count */
			$rows = new ActiveRecordIterator( Db::i()->select( '*', 'core_message_topics', array( 'mt_last_post_time < ?', DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->prune_pms . 'D' ) )->getTimestamp() ) ), '\IPS\core\Messenger\Conversation');

			$count = $rows->count();
			if( $count > 5 )
			{
				/* Queue */
				Task::queue( 'core', 'PrunePms', array(), 2 );
			}
			else
			{
				/* Loop and delete now */
				foreach ( $rows as $conversation )
				{
					$conversation->delete();
				}
			}

		}

		/* Delete Contact Us Verifications older than a month */
		Db::i()->delete( 'core_contact_verify', array( "verify_time<?", DateTime::create()->sub( new DateInterval( 'P30D' ) )->getTimestamp() ) );

		return NULL;
	}
}