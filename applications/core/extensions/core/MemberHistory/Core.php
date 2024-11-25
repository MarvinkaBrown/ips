<?php
/**
 * @brief		MemberHistory: Core
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Jan 2017
 */

namespace IPS\core\extensions\core\MemberHistory;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Api\OAuthClient;
use IPS\Application;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Recognize;
use IPS\core\Warnings\Warning;
use IPS\DateTime;
use IPS\Extensions\MemberHistoryAbstract;
use IPS\gallery\Image;
use IPS\Http\Url;
use IPS\Http\UserAgent;
use IPS\IPS;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Device;
use IPS\Member\Group;
use IPS\Member\GroupPromotion;
use IPS\nexus\Purchase;
use IPS\nexus\Subscription\Package;
use IPS\Theme;
use OutOfRangeException;
use Throwable;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member History: Core
 */
class Core extends MemberHistoryAbstract
{
	/**
	 * Return the valid member history log types
	 *
	 * @return array
	 */
	public function getTypes(): array
	{
		return array(
			'photo',
			'coverphoto',
			'group',
			'login',
			'password_change',
			'display_name',
			'email_change',
			'social_account',
			'account',
			'warning',
			'mfa',
			'oauth',
			'admin_mails',
			'terms_acceptance',
			'points',
			'badges',
			'club_membership',
			'privacy',
		);
	}

	/**
	 * Parse LogData column
	 *
	 * @param	string		$value		column value
	 * @param	array		$row		entire log row
	 * @return	string
	 */
	public function parseLogData( string $value, array $row ): string
	{		
		$jsonValue = json_decode( $value, TRUE );
		
		$byMember = '';
		$byStaff = '';
		if ( $row['log_by'] )
		{
			if ( $row['log_by'] === $row['log_member'] and ( $row['log_type'] !== 'points' and $row['log_type'] !== 'badges' ) )
			{
				$byMember = Member::loggedIn()->language()->addToStack('history_by_member');
			}

			$byStaff = Member::loggedIn()->language()->addToStack('history_by_admin', FALSE, array( 'sprintf' => array( Member::load( $row['log_by'] )->name ) ) );
		}

		switch( $row['log_type'] )
		{
			case 'photo':
				if ( $jsonValue['action'] === 'new' )
				{
					if ( $jsonValue['type'] === 'gallery' )
					{
						try
						{
							$image = Image::load( $jsonValue['id'] );
							$link = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $image->url(), TRUE, Member::loggedIn()->language()->addToStack('history_new_photo_gallery_link'), FALSE );
						}
						catch ( OutOfRangeException $e )
						{
							$link = Member::loggedIn()->language()->addToStack('history_new_photo_gallery_link');
						}
						return Member::loggedIn()->language()->addToStack( 'history_new_photo_gallery', FALSE, array( 'htmlsprintf' => array( $link, $byMember ?: $byStaff ) ) );
					}
					elseif ( $jsonValue['type'] === 'profilesync' )
					{
						try
						{
							$method = Handler::load( $jsonValue['id'] )->_title;
						}
						catch ( OutOfRangeException $e )
						{
							$method = Member::loggedIn()->language()->addToStack( $jsonValue['service'] );
						}
						return Member::loggedIn()->language()->addToStack( 'history_new_photo_profilesync', FALSE, array( 'sprintf' => array( $method ) ) );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack( 'history_new_photo_' . $jsonValue['type'], FALSE, array( 'sprintf' => array( $byMember ?: $byStaff ) ) );
					}
				}
				else
				{
					return Member::loggedIn()->language()->addToStack( 'history_photo_' . $jsonValue['action'], FALSE, array( 'sprintf' => array( $byMember ?: $byStaff ) ) );
				}
			
			case 'coverphoto':
				if ( isset( $jsonValue['type'] ) and $jsonValue['type'] == 'profilesync' )
				{
					try
					{
						$method = Handler::load( $jsonValue['id'] )->_title;
					}
					catch ( OutOfRangeException $e )
					{
						$method = Member::loggedIn()->language()->addToStack( $jsonValue['service'] );
					}
					return Member::loggedIn()->language()->addToStack( 'history_cover_photo_profilesync', FALSE, array( 'sprintf' => array( $method ) ) );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack( 'history_cover_photo_' . $jsonValue['action'], FALSE, array( 'sprintf' => array( $byMember ?: $byStaff ) ) );
				}
			
			case 'group':
			
				if ( $jsonValue['by'] == 'legacy' )
				{
					$jsonValue['new'] = explode( ',', $jsonValue['data']['new'] );
					$jsonValue['old'] = array();
				}
			
				foreach ( array( 'old', 'new' ) as $k )
				{
					$$k = array();
					foreach ( is_array( $jsonValue[ $k ] ) ? $jsonValue[ $k ] : array( $jsonValue[ $k ] ) as $id )
					{
						try
						{
							${$k}[] = Theme::i()->getTemplate( 'members', 'core' )->groupLink( Group::load( $id ) );
						}
						catch ( OutOfRangeException $e )
						{
							${$k}[] = Member::loggedIn()->language()->addToStack( 'history_deleted_group_id', FALSE, array( 'sprintf' => array( $id ) ) );
						}
					}
					if ( $$k )
					{
						$$k = Member::loggedIn()->language()->formatList( $$k );
					}
					else
					{
						$$k = Member::loggedIn()->language()->addToStack('history_no_groups');
					}
				}
				
				switch ( $jsonValue['by'] )
				{
					case 'subscription':
						$subs = '';

						if( Application::appIsEnabled( 'nexus' ) )
						{
							try
							{
								$subs = Package::load( $jsonValue['id'] )->_title;
							}
							catch ( Throwable $e ) { }
						}

						switch ( $jsonValue['action'] )
						{
							case 'add':
							case 'remove':
								return Member::loggedIn()->language()->addToStack( 'history_group_change_subscription_' . $jsonValue['action'], FALSE, array( 'htmlsprintf' => array( $subs, $old, $new ) ) );
						}
						break;
					case 'purchase':
						$purchase = $jsonValue['id'];

						if( Application::appIsEnabled( 'nexus' ) )
						{
							try
							{
								$purchase = Theme::i()->getTemplate('purchases', 'nexus')->link( Purchase::load( $jsonValue['id'] ) );
							}
							catch ( Throwable $e ) { }
						}

						$type = Member::loggedIn()->language()->addToStack( 'history_group_change_purchase_' . $jsonValue['type'] );
						
						switch ( $jsonValue['action'] )
						{
							case 'add':
							case 'remove':
								return Member::loggedIn()->language()->addToStack( 'history_group_change_purchase_' . $jsonValue['action'], FALSE, array( 'htmlsprintf' => array( $purchase, $type, $old, $new ) ) );

							case 'change':
								$expiringPurchase = $jsonValue['remove_id'];

								if( Application::appIsEnabled( 'nexus' ) )
								{
									try
									{
										$expiringPurchase = Theme::i()->getTemplate('purchases', 'nexus')->link( Purchase::load( $jsonValue['remove_id'] ), TRUE );
									}
									catch ( Throwable $e ) { }
								}
								
								return Member::loggedIn()->language()->addToStack( 'history_group_change_purchase_' . $jsonValue['action'], FALSE, array( 'htmlsprintf' => array( $expiringPurchase, $purchase, $type, $old, $new ) ) );
						}
						break;
						
					case 'manual':
						return Member::loggedIn()->language()->addToStack( 'history_group_change_' . $jsonValue['type'], FALSE, array( 'htmlsprintf' => array( $old, $new ), 'sprintf' => array( $byStaff ) ) );
						
					case 'mass':
						return Member::loggedIn()->language()->addToStack( 'history_group_change_mass', FALSE, array( 'htmlsprintf' => array( $old, $new ), 'sprintf' => array( $byStaff ) ) );
					
					case 'api':
						return Member::loggedIn()->language()->addToStack( 'history_group_change_api_' . $jsonValue['type'], FALSE, array( 'htmlsprintf' => array( $old, $new ) ) );
					
					case 'promotion':
						try
						{
							$rule = GroupPromotion::load( $jsonValue['id'] )->_title;
						}
						catch ( OutOfRangeException $e )
						{
							$rule = '#' . $jsonValue['id'];
						}
						return Member::loggedIn()->language()->addToStack( 'history_group_change_promotion_' . $jsonValue['type'], FALSE, array( 'htmlsprintf' => array( $old, $new ), 'sprintf' => array( $rule ) ) );
						
					case 'legacy':
						if ( in_array( $jsonValue['type'], array( 'group_promotion', 'group_promotion_o' ) ) )
						{
							try
							{
								$rule = GroupPromotion::load( $jsonValue['data']['reason'] )->_title;
							}
							catch ( OutOfRangeException $e )
							{
								$rule = '#' . $jsonValue['id'];
							}
							
							return Member::loggedIn()->language()->addToStack( 'history_group_legacy_' . $jsonValue['type'], FALSE, array( 'htmlsprintf' => array( $new ), 'sprintf' => array( $rule ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_group_legacy_' . $jsonValue['type'], FALSE, array( 'htmlsprintf' => array( $new ) ) );
						}
				}
				break;
				
			case 'login':
				switch ( $jsonValue['type'] )
				{
					case 'new_device':
					case 'logout':
						$deviceExists = FALSE;
						try
						{
							$device = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( Url::internal("app=core&module=members&controller=devices&do=device&key={$jsonValue['device']}&member={$row['log_member']}"), FALSE, (string) Device::load( $jsonValue['device'] )->userAgent(), FALSE );
							$deviceExists = TRUE;
						}
						catch ( Exception $e )
						{
							if ( isset( $jsonValue['user_agent'] ) )
							{
								$device = (string) UserAgent::parse( $jsonValue['user_agent'] );
							}
							else
							{
								$device = htmlspecialchars( $jsonValue['device'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
							}
						}
						if ( $jsonValue['type'] == 'new_device' )
						{
							return $deviceExists ? Member::loggedIn()->language()->addToStack( 'history_new_device', FALSE, array( 'htmlsprintf' => array( $device ) ) ) : Member::loggedIn()->language()->addToStack( 'history_new_device_no_mfa', FALSE, array( 'sprintf' => array( $device ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_device_logout', FALSE, array( 'htmlsprintf' => array( $device, $byMember ?: $byStaff ) ) );
						}
					case 'lock':
						if ( isset( $jsonValue['unlockTime'] ) )
						{
							return Member::loggedIn()->language()->addToStack( 'history_account_locked_time', FALSE, array( 'htmlsprintf' => array( DateTime::ts( $jsonValue['unlockTime'] ), $jsonValue['count'] ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_account_locked', FALSE, array( 'htmlsprintf' => array( $jsonValue['count'] ) ) );
						}
					case 'mfalock':
						if ( isset( $jsonValue['unlockTime'] ) )
						{
							return Member::loggedIn()->language()->addToStack( 'history_account_mfa_locked_time', FALSE, array( 'htmlsprintf' => array( DateTime::ts( $jsonValue['unlockTime'] ), $jsonValue['count'] ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_account_mfa_locked', FALSE, array( 'htmlsprintf' => array( $jsonValue['count'] ) ) );
						}
					case 'unlock':
						return Member::loggedIn()->language()->addToStack( 'history_account_unlocked', FALSE, array( 'htmlsprintf' => array( $byStaff ) ) );
				}
				break;
				
			case 'password_change':
				switch ( $jsonValue )
				{
					case 'forced':
						return Member::loggedIn()->language()->addToStack('history_password_changed_forced');
					case 'lost':
						return Member::loggedIn()->language()->addToStack('history_password_changed_lost');
					case 'api':
						return Member::loggedIn()->language()->addToStack('history_password_changed_api');
					default:
						return Member::loggedIn()->language()->addToStack( 'history_password_changed', FALSE, array( 'sprintf' => array( $byMember ?: $byStaff ) ) );
				}
				
			case 'display_name':
				$oldDisplayName = $jsonValue['old'] ?: Member::loggedIn()->language()->addToStack('history_unknown'); // Old display name records may be missing this
				switch ( $jsonValue['by'] ?? NULL )
				{
					case 'manual':
						return Member::loggedIn()->language()->addToStack('history_name_changed_manual', FALSE, array( 'sprintf' => array( $jsonValue['new'], $oldDisplayName, $byMember ?: $byStaff ) ) );
					case 'api':
						return Member::loggedIn()->language()->addToStack('history_name_changed_api', FALSE, array( 'sprintf' => array( $jsonValue['new'], $oldDisplayName ) ) );
					case 'profilesync':
						try
						{
							$method = Handler::load( $jsonValue['id'] )->_title;
						}
						catch ( OutOfRangeException $e )
						{
							$method = Member::loggedIn()->language()->addToStack( $jsonValue['service'] );
						}
						return Member::loggedIn()->language()->addToStack('history_name_changed_profilesync', FALSE, array( 'sprintf' => array( $jsonValue['new'], $oldDisplayName, $method ) ) );
					default:
						return Member::loggedIn()->language()->addToStack('history_name_changed', FALSE, array( 'sprintf' => array( $jsonValue['new'], $oldDisplayName ) ) );
				}
				
			case 'email_change':
				$newEmailAddress = $jsonValue['new'] ?: Member::loggedIn()->language()->addToStack('history_unknown'); // Previous customer history didn't log what it was changed to
				switch ( $jsonValue['by'] ?? NULL )
				{
					case 'manual':
						return Member::loggedIn()->language()->addToStack('history_email_changed_manual', FALSE, array( 'sprintf' => array( $newEmailAddress, $jsonValue['old'], $byMember ?: $byStaff ) ) );
					case 'api':
						return Member::loggedIn()->language()->addToStack('history_email_changed_api', FALSE, array( 'sprintf' => array( $newEmailAddress, $jsonValue['old'] ) ) );
					case 'profilesync':
						try
						{
							$method = Handler::load( $jsonValue['id'] )->_title;
						}
						catch ( OutOfRangeException $e )
						{
							$method = Member::loggedIn()->language()->addToStack( $jsonValue['service'] );
						}
						return Member::loggedIn()->language()->addToStack('history_email_changed_profilesync', FALSE, array( 'sprintf' => array( $newEmailAddress, $jsonValue['old'], $method ) ) );
					default:
						return Member::loggedIn()->language()->addToStack('history_email_changed', FALSE, array( 'sprintf' => array( $newEmailAddress, $jsonValue['old'] ) ) );
				}
				
			case 'social_account':
				$handler = NULL;
				if ( isset( $jsonValue['handler'] ) )
				{
					try
					{
						$handler = Handler::load( $jsonValue['handler'] );
						$handlerName = $handler->_title;
					}
					catch ( OutOfRangeException $e )
					{
						$handlerName = Member::loggedIn()->language()->addToStack( $jsonValue['service'] );
					}
				}
				else
				{
					$handlerName = Member::loggedIn()->language()->addToStack( 'login_handler_' . IPS::mb_ucfirst( $jsonValue['service'] ) );
				}
				
				if ( isset( $jsonValue['changed'] ) )
				{
					$changes = array();
					foreach ( $jsonValue['changed'] as $k => $v )
					{
						$changes[] = Member::loggedIn()->language()->addToStack( 'history_social_sync_changed_' . $k, FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $v ? 'history_social_sync_enabled' : 'history_social_sync_disabled' ) ) ) );
					}
					return Member::loggedIn()->language()->addToStack('history_social_sync_changes', FALSE, array( 'sprintf' => array( $handlerName, $byMember ?: $byStaff, implode( '; ', $changes ) ) ) );
				}
				
				$account = NULL;
				$link = NULL;
				if ( $handler and ( isset( $jsonValue['account_id'] ) or isset( $jsonValue['account_name'] ) ) )
				{
					$account = $jsonValue['account_name'];
					$link = $handler->userLink( $jsonValue['account_id'], $jsonValue['account_name'] );
				}				
				if( isset( $jsonValue['registered'] ) or $jsonValue['linked'] === TRUE )
				{
					$type = 'connected';
				}
				else
				{
					$type = 'disconnected';
				}
				
				if ( $account )
				{
					if ( $link )
					{
						return Member::loggedIn()->language()->addToStack('history_'.$type.'_social_with_link', FALSE, array( 'sprintf' => array( $handlerName, $link, $account, $byMember ?: $byStaff ) ) );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack('history_'.$type.'_social_with_account', FALSE, array( 'sprintf' => array( $handlerName, $account, $byMember ?: $byStaff ) ) );
					}
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('history_'.$type.'_social', FALSE, array( 'sprintf' => array( $handlerName, $byMember ?: $byStaff ) ) );
				}
				
			case 'account':
				
				$spamDefenseScore = NULL;
				if ( isset( $jsonValue['spamCode'] ) AND isset( $jsonValue['spamAction'] ) )
				{
					if ( isset( $jsonValue['disposable'] ) AND $jsonValue['disposable'] )
					{
						$spamDefenseScore = Member::loggedIn()->language()->addToStack( 'history_spam_defense_disposable_' . $jsonValue['spamAction'] );
					}
					elseif ( isset( $jsonValue['geoBlock'] ) AND $jsonValue['geoBlock'] )
					{
						$spamDefenseScore = Member::loggedIn()->language()->addToStack( 'history_spam_defense_geoblock' );
					}
					else
					{
						$spamDefenseScore = Member::loggedIn()->language()->addToStack( 'history_spam_defense_' . $jsonValue['spamAction'], FALSE, array( 'sprintf' => array( $jsonValue['spamCode'] ) ) );
					}
				}
				
				switch ( $jsonValue['type'] )
				{
					case 'register':
						return Member::loggedIn()->language()->addToStack( 'history_account_register', FALSE, array( 'sprintf' => array( $spamDefenseScore ) ) );
					
					case 'register_checkout':
						return Member::loggedIn()->language()->addToStack( 'history_account_register_checkout', FALSE, array( 'sprintf' => array( $spamDefenseScore ) ) );
					
					case 'register_admin':
						return Member::loggedIn()->language()->addToStack( 'history_account_register_admin', FALSE, array( 'sprintf' => array( $byStaff ) ) );
					
					case 'register_handler':
						try
						{
							$method = Handler::load( $jsonValue['handler'] )->_title;
						}
						catch ( OutOfRangeException $e )
						{
							$method = Member::loggedIn()->language()->addToStack( $jsonValue['service'] );
						}
						return Member::loggedIn()->language()->addToStack( $jsonValue['complete'] ? 'history_account_created_handler' : 'history_account_created_handler_incomplete', FALSE, array( 'sprintf' => array( $method, $spamDefenseScore ) ) );
					
					case 'complete':
						return Member::loggedIn()->language()->addToStack( 'history_account_completed', FALSE, array( 'sprintf' => array( $byMember ?: $byStaff, $spamDefenseScore ) ) );
						
					case 'email_validated':
						return Member::loggedIn()->language()->addToStack( 'history_account_email_validated' );
					
					case 'admin_validated':
						return Member::loggedIn()->language()->addToStack( 'history_account_admin_validated', FALSE, array( 'sprintf' => array( $byStaff ) ) );
					
					case 'merge':
						if ( isset( $jsonValue['id'] ) )
						{
							return Member::loggedIn()->language()->addToStack( 'history_account_merged', FALSE, array( 'sprintf' => array( $jsonValue['id'], $jsonValue['name'], $jsonValue['email'], $byStaff ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_account_merged_legacy', FALSE, array( 'sprintf' => array( $jsonValue['legacy']['old'], $byStaff ) ) );
						}
						
					case 'spammer':
						$set = $jsonValue['set'] ?? $jsonValue['legacy']['set'];
						if ( $set and isset( $jsonValue['actions'] ) )
						{
							$flagActions = array();
							if ( in_array( 'delete', $jsonValue['actions'] ) )
							{
								$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_delete');
							}
							elseif ( in_array( 'unapprove', $jsonValue['actions'] ) )
							{
								$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_unapprove');
							}
							if ( in_array( 'ban', $jsonValue['actions'] ) )
							{
								$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_ban');
							}
							elseif ( in_array( 'disable', $jsonValue['actions'] ) )
							{
								$flagActions[] = Member::loggedIn()->language()->addToStack('history_flagged_spammer_action_disable');
							}
							if ( count( $flagActions ) )
							{
								return Member::loggedIn()->language()->addToStack( 'history_flagged_spammer_with_actions', FALSE, array( 'sprintf' => array( $byStaff, Member::loggedIn()->language()->formatList( $flagActions ) ) ) );
							}
						}
						
						return Member::loggedIn()->language()->addToStack( $set ? 'history_flagged_spammer' : 'history_unflagged_spammer', FALSE, array( 'sprintf' => array( $byStaff ) ) );
				}
				break;
				
			case 'warning':
			
				if ( isset( $jsonValue['restrictions'] ) ) // Manual restrictions change 
				{
					$changes = array();
					if ( isset( $jsonValue['restrictions']['member_warnings'] ) )
					{
						$changes[] = Member::loggedIn()->language()->addToStack( 'history_restrictions_warning_level', FALSE, array( 'sprintf' => array( $jsonValue['restrictions']['member_warnings']['new'], $jsonValue['restrictions']['member_warnings']['old'] ) ) );
					}
					if ( isset( $jsonValue['restrictions']['ban'] ) )
					{
						if ( $jsonValue['restrictions']['ban']['new'] )
						{
							$c = Member::loggedIn()->language()->addToStack( 'moderation_banned' );
							if ( $jsonValue['restrictions']['ban']['new'] != -1 )
							{
								$diff = DateTime::ts( $row['log_date'] )->diff( DateTime::ts( $jsonValue['restrictions']['ban']['new'] ) );
								$c = Member::loggedIn()->language()->addToStack( 'history_received_warning_penalty_time', FALSE, array( 'sprintf' => array( $c, DateTime::formatInterval( $diff, 2 ) ) ) );
							}
						}
						else
						{
							$c = Member::loggedIn()->language()->addToStack( 'history_warning_revoke_temp_ban' );
						}
						$changes[] = $c;
					} 
					if ( isset( $jsonValue['restrictions']['members_disable_pm'] ) )
					{
						$changes[] = Member::loggedIn()->language()->addToStack( 'history_restrictions_messenger_' . intval( $jsonValue['restrictions']['members_disable_pm']['new'] ) );
					}

					foreach ( Application::allExtensions( 'core', 'MemberRestrictions', TRUE, 'core', 'Content', FALSE ) as $class )
					{
						foreach ( $class::changesForHistory( $jsonValue['restrictions'], $row ) as $v )
						{
							$changes[] = $v;
						}
					}
					
					return Member::loggedIn()->language()->addToStack( 'history_restrictions_change', FALSE, array( 'sprintf' => array( $byStaff, Member::loggedIn()->language()->formatList( $changes ) ) ) );
				}
				elseif ( isset( $jsonValue['type'] ) and $jsonValue['type'] == 'revoke' ) // Warning revoked
				{
					$consequences = array();
					if ( isset( $jsonValue['consequences'] ) )
					{
						foreach ( array( 'modq' => 'mod_posts', 'nopost' => 'restrict_post', 'banned' => 'temp_ban', 'cheev_point_reduction' => 'cheev_point_reduction' ) as $k => $v )
						{
							if ( isset( $jsonValue['consequences'][ $v ] ) )
							{
								$consequences[] = Member::loggedIn()->language()->addToStack( 'history_warning_revoke_' . $v );
							}
						}
					}
					$consequences = count( $consequences ) ? Member::loggedIn()->language()->formatList( $consequences ) : Member::loggedIn()->language()->addToStack('history_received_warning_no_changes');
					
					return Member::loggedIn()->language()->addToStack( 'history_revoke_warning', FALSE, array( 'sprintf' => array( $byStaff, $consequences ) ) );
				}
				else // Warning given
				{			
					$consequences = array();
					if ( isset( $jsonValue['consequences'] ) )
					{
						foreach ( array( 'modq' => 'mod_posts', 'nopost' => 'restrict_post', 'banned' => 'temp_ban', 'cheev_point_reduction' => 'cheev_point_reduction' ) as $k => $v )
						{
							if ( isset( $jsonValue['consequences'][ $v ] ) )
							{
								if( $v == 'cheev_point_reduction' )
								{
									$consequences[]= Member::loggedIn()->language()->addToStack( 'moderation_cheev_point_reduction', FALSE, array('sprintf' => array( $jsonValue['consequences'][ $v ] ) ) );
								}
								else
								{
									$c = Member::loggedIn()->language()->addToStack( 'moderation_' . $k );
									if ( $jsonValue['consequences'][$v] != -1 )
									{
										$c = Member::loggedIn()->language()->addToStack( 'history_received_warning_penalty_time', FALSE, array('sprintf' => array($c, DateTime::formatInterval( new DateInterval( $jsonValue['consequences'][$v] ), 2 ))) );
									}
									$consequences[] = $c;
								}
							}
						}
					}
					$consequences = count( $consequences ) ? Member::loggedIn()->language()->formatList( $consequences ) : Member::loggedIn()->language()->addToStack('history_received_warning_no_penalties');
					
					$byApi	= ( isset( $jsonValue['by'] ) AND $jsonValue['by'] === 'api' ) ? '_api' : '';

					try
					{
						$warning = Warning::load( $jsonValue['wid'] );
						if ( $warning->canViewDetails() )
						{
							if ( isset( $jsonValue['points'] ) )
							{
								return Member::loggedIn()->language()->addToStack('history_received_warning_link' . $byApi, FALSE, array( 'sprintf' => array( Url::internal("app=core&module=members&controllers=members&do=viewWarning&id={$warning->id}"), $jsonValue['points'], Member::loggedIn()->language()->addToStack( 'core_warn_reason_' . $jsonValue['reason'] ), $byStaff, $consequences ) ) );
							}
							else
							{
								return Member::loggedIn()->language()->addToStack('history_received_warning_legacy_link' . $byApi, FALSE, array( 'sprintf' => array( Url::internal("app=core&module=members&controllers=members&do=viewWarning&id={$warning->id}") ) ) );
							}
						}
					}
					catch ( Exception $e ) { }
					
					if ( isset( $jsonValue['points'] ) )
					{
						return Member::loggedIn()->language()->addToStack('history_received_warning_details' . $byApi, FALSE, array( 'sprintf' => array( $jsonValue['points'], Member::loggedIn()->language()->addToStack( 'core_warn_reason_' . $jsonValue['reason'] ), $byStaff, $consequences ) ) );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack( 'history_received_warning' . $byApi, FALSE, array( 'sprintf' => array( $jsonValue['points'], $byStaff ) ) );
					}
				}
			
			case 'mfa':
				$handlerName = Member::loggedIn()->language()->addToStack('mfa_' . $jsonValue['handler'] . '_title');
				if( $jsonValue['enable'] === TRUE )
				{
					if ( isset( $jsonValue['reconfigure'] ) and $jsonValue['reconfigure'] )
					{
						return Member::loggedIn()->language()->addToStack( 'history_mfa_reconfigured', FALSE, array( 'sprintf' => array( $handlerName, $byMember ?: $byStaff ) ) );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack( 'history_mfa_enabled', FALSE, array( 'sprintf' => array( $handlerName, $byMember ?: $byStaff ) ) );
					}
				}

				if( isset( $jsonValue['optout'] ) )
				{
					return Member::loggedIn()->language()->addToStack( $jsonValue['optout'] ? 'history_mfa_optout' : 'history_mfa_optin', FALSE, array( 'sprintf' => array( $byMember ?: $byStaff ) ) );
				}
				
				return Member::loggedIn()->language()->addToStack( 'history_mfa_disabled', FALSE, array( 'sprintf' => array( $handlerName, $byMember ?: $byStaff ) ) );
				
			case 'oauth':
				
				$clientObj = OAuthClient::load( $jsonValue['client'] );
				try
				{
					$clientObj = OAuthClient::load( $jsonValue['client'] );
					$client = Theme::i()->getTemplate( 'api', 'core' )->oauthClientLink( $clientObj );
				}
				catch ( OutOfRangeException $e )
				{
					$client =  Theme::i()->getTemplate( 'api', 'core' )->apiKey( $jsonValue['client'] );
				}
				
				switch ( $jsonValue['type'] )
				{
					case 'issued_access_token':
						if ( $jsonValue['grant'] === 'refresh_token' )
						{
							return Member::loggedIn()->language()->addToStack( 'history_oauth_token_issued_refresh_token', FALSE, array( 'htmlsprintf' => array( $client ) ) );
						}
						elseif ( $clientObj and count( explode( ',', $clientObj->grant_types ) ) < 2 )
						{
							return Member::loggedIn()->language()->addToStack( 'history_oauth_token_issued_no_details', FALSE, array( 'htmlsprintf' => array( $client, Member::loggedIn()->language()->addToStack( 'history_oauth_token_issued_' . $jsonValue['grant'] ) ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_oauth_token_issued', FALSE, array( 'htmlsprintf' => array( $client, Member::loggedIn()->language()->addToStack( 'history_oauth_token_issued_' . $jsonValue['grant'] ) ) ) );
						}
					case 'revoked_access_token':
						return Member::loggedIn()->language()->addToStack( 'history_oauth_token_revoked', FALSE, array( 'htmlsprintf' => array( $client, $byMember ?: $byStaff ) ) );
				}
				
				break;
				
			case 'admin_mails':
				if( $jsonValue['enabled'] === TRUE )
				{
					return Member::loggedIn()->language()->addToStack( 'history_enabled_admin_mails', FALSE, array( 'htmlsprintf' => array( $byMember ?: $byStaff ) ) );
				}

				return Member::loggedIn()->language()->addToStack( 'history_disabled_admin_mails', FALSE, array( 'htmlsprintf' => array( $byMember ?: $byStaff ) ) );

			case 'terms_acceptance':
				if( $jsonValue['type'] == 'privacy' )
				{
					return Member::loggedIn()->language()->addToStack('history_terms_accepted_privacy');
				}

				if( $jsonValue['type'] == 'terms' )
				{
					return Member::loggedIn()->language()->addToStack('history_terms_accepted_terms');
				}
				break;
			case 'points':
				if ( isset( $jsonValue['recognize'] ) )
				{
					try
					{
						$recognize = Recognize::load( $jsonValue['recognize'] );
						return Member::loggedIn()->language()->addToStack( 'history_recognize_points_adjustment', FALSE, array( 'sprintf' => array( $recognize->points, $recognize->content()->url(), $recognize->content()->indefiniteArticle() ) ) );

					}
					catch( Exception $e )
					{
						return Member::loggedIn()->language()->addToStack( 'history_recognize_points_adjustment_deleted' );
					}
				}
				else if ( isset( $jsonValue['by'] ) and $jsonValue['by'] === 'manual' )
				{
					return Member::loggedIn()->language()->addToStack( 'history_manual_points_adjustment', FALSE, array( 'htmlsprintf' => array( intval( $jsonValue['old'] ), intval( $jsonValue['new'] ), $byMember ?: $byStaff ) ) );
				}

				break;
			case 'badges':
				if ( isset( $jsonValue['action'] ) )
				{
					try
					{
						$badge = Badge::load( $jsonValue['id'] );

						if ( $jsonValue['action'] == 'manual' )
						{
							return Member::loggedIn()->language()->addToStack( 'history_manual_badge_addition', FALSE, array('htmlsprintf' => array( $badge->_title, $byMember ?: $byStaff ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( 'history_manual_badge_deletion', FALSE, array('htmlsprintf' => array( $badge->_title, $byMember ?: $byStaff ) ) );
						}
					}
					catch( Exception $e ) { }
				}

				break;
			case 'club_membership':
				if( isset( $jsonValue['club_id'] ))
				{
					try 
					{
						$club = Club::load( $jsonValue['club_id'] );
						switch( $jsonValue['type'] )
						{
							case Club::STATUS_BANNED:
								return Member::loggedIn()->language()->addToStack( 'history_removed_from_club', FALSE, array('htmlsprintf' => array( $club->name, $byStaff ) ) );
							case  Club::STATUS_MEMBER:
								return Member::loggedIn()->language()->addToStack( 'history_added_to_club', FALSE, array('htmlsprintf' => array( $club->name, $byStaff ) ) );
						}
					}
					catch ( Exception $e ) { }
				}
				break;
			case 'privacy':
				if( $jsonValue == 'account_deletion_cancelled' ) // This may not have been properly structured in some previous versions [4.7.11.x]
				{
					return Member::loggedIn()->language()->addToStack( 'account_deletion_cancelled', FALSE, [ 'htmlsprintf' => [ $byMember ?: $byStaff ] ] );
				}

				switch ( $jsonValue['type'] )
				{
					case 'account_deletion_requested':
						return Member::loggedIn()->language()->addToStack( 'history_account_deletion_request', FALSE, [ 'htmlsprintf' => [ $byMember ?: $byStaff ] ] );
					case 'account_deletion_cancelled':
						return Member::loggedIn()->language()->addToStack( 'account_deletion_cancelled', FALSE, [ 'htmlsprintf' => [ $byMember ?: $byStaff ] ] );
					case 'pii_data_request':
						return Member::loggedIn()->language()->addToStack( 'history_pii_data_request', FALSE, [ 'htmlsprintf' => [ $byMember ?: $byStaff ] ] );
					case 'pii_download':
						return Member::loggedIn()->language()->addToStack( 'history_pii_downloaded', FALSE, [ 'htmlsprintf' => [ $byMember ?: $byStaff ] ] );
				}
		}

		return $value;
	}

	/**
	 * Parse LogType column
	 *
	 * @param	string		$value		column value
	 * @param	array		$row		entire log row
	 * @return	string
	 */
	public function parseLogType( string $value, array $row ): string
	{
		return Theme::i()->getTemplate( 'members', 'core' )->logType( $value );
	}
}