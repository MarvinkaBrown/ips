<?php
/**
 * @brief		Notification Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Apr 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Base64Url\Base64Url;
use DateInterval;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Custom;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Http\Url\Internal;
use IPS\Member\Device;
use IPS\Node\Model;
use IPS\Notification\Inline;
use IPS\Notification\Recipients;
use IPS\Patterns\ActiveRecord;
use IPS\Platform\Bridge;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\Utils;
use Minishlink\WebPush\VAPID;
use OutOfRangeException;
use UnderflowException;
use function array_keys;
use function count;
use function defined;
use function function_exists;
use function get_class;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Class
 */
class Notification
{
	/**
	 * @brief	Default Configuration
	 */
	protected static ?array $defaultConfiguration = NULL;

	/**
	 * @brief	Cache of vapid headers to save processing time
	 */
	protected static array $vapidHeaderCache = array();

	/**
	 * @brief	Pre-defined web push TTL values
	 */
	const TTL_IMMEDIATE = 0;
	const TTL_SHORT = 120; // 2 minutes
	const TTL_MEDIUM = 21600; // 6 hours
	const TTL_LONG = 86400; // 1 day

	/**
	 * @brief	Urgency values
	 */
	const URGENCY_VERY_LOW = 'very-low';
	const URGENCY_LOW = 'low';
	const URGENCY_NORMAL = 'normal';
	const URGENCY_HIGH = 'high';

	/**
	 * @brief	Allow notifications to be programatically silenced
	 */
	protected static bool $silenced = FALSE;

	/**
	 * Silence notifications programatically
	 * Useful when performing batch operations that would usually send a notification
	 *
	 * @return void
	 */
	public static function silence() : void
	{
		static::$silenced = TRUE;
	}

	/**
	 * If you don't possess a dictionary, this is the oppopsite of silence.
	 *
	 * @return void
	 */
	public static function unsilence() : void
	{
		static::$silenced = FALSE;
	}

	/**
	 * Do we have the server things we need for web push?
	 *
	 * @return	boolean
	 */
	public static function canUseWebPush(): bool
	{
		if ( Application::appIsEnabled('cloud') or IN_DEV )
		{
            /* Cloud is clearly the superior premium platform, so it already has GMP */
			return TRUE;
		}

		return function_exists('gmp_init');
	}

	/**
	 * Does this site have web push notifications enabled
	 *
	 * @return	boolean
	 */
	public static function webPushEnabled(): bool
	{
		return static::canUseWebPush() and ( ( isset( Settings::i()->vapid_public_key ) && !empty( Settings::i()->vapid_public_key ) &&
			isset( Settings::i()->vapid_private_key ) && !empty( Settings::i()->vapid_private_key ) ) );
	}

	/**
	 * Get default configuration
	 *
	 * @return array|null
	 */
	public static function defaultConfiguration(): ?array
	{
		if ( static::$defaultConfiguration === NULL )
		{
			/* Get data from the actual extensions */
			$extensionDefaults = array();
			$notificationGroups = [];
			foreach(Application::allExtensions( 'core', 'Notifications' ) as $group => $class )
			{
				if ( method_exists( $class, 'configurationOptions' ) )
				{
					foreach ( $class->configurationOptions( NULL ) as $key => $details )
					{
						if ( $details['type'] === 'standard' )
						{
							$extensionDefaults[ $key ] = array(
								'default'	=> $details['default'],
								'disabled'	=> $details['disabled']	
							);
							
							foreach ( $details['notificationTypes'] as $type )
							{
								$notificationGroups[ $type ] = $key;
								$extensionDefaults[ $type ] = array(
									'default'	=> $details['default'],
									'disabled'	=> $details['disabled']	
								);
							}
						}
					}
				}
				else
				{
					$configuration = $class->getConfiguration( NULL );
					if ( !empty( $configuration ) )
					{
						foreach ( $configuration as $key => $data )
						{
							$extensionDefaults[ $key ] = $data;
						}
					}
				}
			}
			
			/* Combine that with what the admin has set */
			static::$defaultConfiguration = iterator_to_array( Db::i()->select( '*', 'core_notification_defaults' )->setKeyField('notification_key') );
			foreach ( $extensionDefaults as $key => $data )
			{
				if ( !isset( static::$defaultConfiguration[ $key ] ) )
				{
					/* If parent group already has defaults stored, use those */
					if( isset( $notificationGroups[ $key ] ) AND isset( static::$defaultConfiguration[ $notificationGroups[ $key ] ] ) )
					{
						Db::i()->insert( 'core_notification_defaults', array(
							'notification_key' => $key,
							'default'		   => implode( ',', static::$defaultConfiguration[ $notificationGroups[ $key ] ]['default'] ),
							'disabled'		   => implode( ',', static::$defaultConfiguration[ $notificationGroups[ $key ] ]['disabled'] )
						) );
						$data['default'] = static::$defaultConfiguration[ $notificationGroups[ $key ] ]['default'];
						$data['disabled'] = static::$defaultConfiguration[ $notificationGroups[ $key ] ]['disabled'];
					}
					else
					{
						/* Row isn't in DB, add it */
						Db::i()->insert( 'core_notification_defaults', array(
							'notification_key' => $key,
							'default' => implode( ',', $data['default'] ),
							'disabled' => implode( ',', $data['disabled'] )
						) );
					}
					
					static::$defaultConfiguration[ $key ] = array_merge( $data, array( 'editable' => TRUE ) );
				}
				else
				{					
					static::$defaultConfiguration[ $key ]['default'] = array_filter( explode( ',', static::$defaultConfiguration[ $key ]['default'] ) );
					static::$defaultConfiguration[ $key ]['disabled'] = array_filter( array_merge( $data['disabled'], explode( ',', static::$defaultConfiguration[ $key ]['disabled'] ) ) );
				}
			}
		}
		
		return static::$defaultConfiguration;
	}
	
	/**
	 * Get available options for an extension
	 *
	 * @param Member|null $member		The member or NULL to get all
	 * @param object $extension	The notifications extension
	 * @return	array
	 */
	public static function availableOptions( ?Member $member, object $extension ) : array
	{
		$settings = $extension::configurationOptions( $member );
		
		/* Now loop through each of those and get the value for each */
		if ( $member )
		{
			$defaultConfiguration = Notification::defaultConfiguration();
			$memberConfiguration = $member->notificationsConfiguration();
			$finalSettings = array();
			foreach ( $settings as $key => $details )
			{
				if ( $details['type'] === 'standard' )
				{
					$options = array();
					$availableTypes = array('inline');
					if ( static::webPushEnabled() && count( $member->getPwaAuths() ) )
					{
						$availableTypes[] = 'push';
					}
					$availableTypes[] = 'email';
					foreach ( $availableTypes as $type )
					{
						if ( !in_array( $type, $defaultConfiguration[ $key ]['disabled'] ) )//if ( !\in_array( $type, $defaultConfiguration[ $key ]['disabled'] ) and ( $type !== 'push' or !\in_array( 'inline', $defaultConfiguration[ $key ]['disabled'] ) ) )
						{
							$enabled = array();
							$haveMemberPreferences = FALSE;
							if ( $defaultConfiguration[ $key ]['editable'] )
							{
								foreach ( $details['notificationTypes'] as $notificationType )
								{									
									if ( isset( $memberConfiguration[ $notificationType ] ) )
									{
										$haveMemberPreferences = TRUE;
										$enabled += $memberConfiguration[ $notificationType ];
									}
								}
							}
							if ( !$haveMemberPreferences )
							{
								$enabled = $defaultConfiguration[ $key ]['default'];
							}
							
							$options[ $type ] = array(
								'title'		=> "member_notifications_{$type}",
								'value'		=> in_array( $type, $enabled ),
								'editable'	=> $defaultConfiguration[ $key ]['editable']
							);
						}
					} 
					
					if ( count( $options ) or ( isset( $details['extra'] ) and count( $details['extra'] ) ) )
					{
						$details['options'] = $options;
						$finalSettings[ $key ] = $details;
					}
				}
				else
				{
					$finalSettings[ $key ] = $details;
				}
			}
			
			/* And return */
			return $finalSettings;
		}
		else
		{
			return $settings;
		}
	}
	
	/**
	 * Get the notification categories to show to a member
	 *
	 * @param Member $member		The member
	 * @param array $extensions	The notification extensions (passed so we don't have to load it more than once)
	 * @return	array
	 */	
	public static function membersOptionCategories( Member $member, array $extensions ): array
	{
		$categories = array();
		foreach( $extensions as $group => $extension )
		{
			$options = static::availableOptions( $member, $extension );
			if ( !empty( $options ) )
			{
				if ( !isset( $categories[ $group ] ) )
				{
					$categories[ $group ] = array();
				}
								
				foreach ( $options as $key => $details )
				{
					if ( isset( $details['extra'] ) )
					{
						foreach ( $details['extra'] as $extraKey => $extraDetails )
						{
							if ( $extraDetails['value'] )
							{
								$categories[ $group ][ $extraKey ] = array( 'title' => Member::loggedIn()->language()->get( $extraDetails['title'] ), 'icon' => $extraDetails['icon'], 'description' => $extraDetails['description'] ?? NULL );
							}
						}
					}
					if ( $details['type'] === 'standard' )
					{
						foreach ( $details['options'] as $type => $typeDetails )
						{
							if ( $typeDetails['value'] )
							{
								switch ( $type )
								{
									case 'inline':
										if ( isset( $details['options']['push'] ) and $details['options']['push']['value'] )
										{
											continue 2;
										}
										$icon = 'bell';
										break;
									case 'push':
										$icon = 'mobile';
										break;
									case 'email':
										$icon = 'envelope';
										break;
								}
								
								$categories[ $group ][ $type ] = array( 'title' => Member::loggedIn()->language()->get( 'member_notifications_' . $type ), 'icon' => $icon );
							}
						}
					}
				}
			}
		}
		
		return $categories;
	}	
	
	/**
	 * Get the form for editing a member's notification preferences for a given notification extension
	 *
	 * @param Member $member		The member
	 * @param object $extension	The notification extension to edit preferences for
	 * @return	Form|NULL|TRUE
	 */
	public static function membersTypeForm( Member $member, object $extension ): Form|bool|null
	{
		$form = NULL;
		$formIsEditable = FALSE;
		
		if ( $options = Notification::availableOptions( $member, $extension ) )
		{
			$form = new Form;
			foreach ( $options as $key => $details )
			{
				if ( $details['type'] === 'separator' and Dispatcher::i()->controllerLocation === 'front' )
				{
					$form->addSeparator();
				}
				elseif ( $details['type'] === 'header' )
				{
					$form->addHeader( $details['header'] );
				}
				elseif ( $details['type'] === 'custom' and ( !isset( $details['adminOnly'] ) ) )
				{
					$form->add( $details['field'] );
					$formIsEditable = TRUE;
				}
				elseif ( $details['type'] === 'standard' )
				{
					if ( !$formIsEditable )
					{
						if ( isset( $details['extra'] ) and count( $details['extra'] ) )
						{
							$formIsEditable = TRUE;
						}
						else
						{
							foreach ( $details['options'] as $option )
							{
								if ( $option['editable'] )
								{
									$formIsEditable = TRUE;
									break;
								}
							}
						}
					}
										
					$form->add( new Custom( "notifications_{$key}", NULL, TRUE, array(
						'rowHtml'	=> function( $field ) use ( $details, $options ) {
							return Theme::i()->getTemplate( 'members', 'core', 'global' )->notificationsSettingsRow( $field, $details );
						}
					) ) );
				}
			}
			
			if ( $values = $form->values() )
			{
				foreach ( $options as $key => $details )
				{
					if ( $details['type'] === 'standard' )
					{
						$value = array();
						if ( isset( $values["notifications_{$key}"] ) )
						{
							foreach ( $values["notifications_{$key}"] as $k => $v )
							{
								if ( $v === 'push' )
								{
									$value['inline'] = 'inline';
									$value['push'] = 'push';
								}
								elseif ( in_array( $k, array( 'inline', 'push', 'email' ) ) and $v )
								{
									$value[ $k ] = $k;
								}
							}
						}
						
						foreach ( $details['notificationTypes'] as $notificationKey )
						{
							Db::i()->insert( 'core_notification_preferences', array(
								'member_id'			=> $member->member_id,
								'notification_key'	=> $notificationKey,
								'preference'		=> implode( ',', $value )
							), TRUE );

							$member->notificationsConfiguration[ $notificationKey ] = $value;
						}
					}
					elseif ( $details['type'] === 'custom' and ( !isset( $details['adminOnly'] ) or !$details['adminOnly'] ) )
					{
						$extension::saveExtra( $member, $key, $values[ $key ] );
					}
					
					if ( isset( $details['extra'] ) )
					{
						foreach ( $details['extra'] as $extraKey => $extraDetails )
						{
							$extension::saveExtra( $member, $extraKey, array_key_exists( $extraKey, $values["notifications_{$key}"] ?? [] ) );
						}
					}
				}
				
				$member->save();
				return TRUE;
			}
		}
		
		if ( $form and !$formIsEditable )
		{
			$form->actionButtons = array();
		}
		
		return $form;
	}
		
	/**
	 * @brief	Application
	 */
	protected Application $app;
	
	/**
	 * @brief	Notification key
	 */
	protected ?string $key = NULL;
	
	/**
	 * @brief	Email template key
	 * @note	Typically this is "notification__{key}"
	 */
	protected ?string $emailKey = NULL;

	/**
	 * @brief	Item
	 */
	protected ?object $item;
		
	/**
	 * @brief	An \IPS\Notification\Recipients object which contains \IPS\Member objects and replacements to use for that member in the notification content.
	 * @code
	 	$notification->recipients->attach( $member, array( 'foo' => 'bar' ) );
	 	$notification->recipients->attach( $member2, array( 'foo' => 'baz' ) );
	 * @endcode
	 */
	public Recipients $recipients;
	
	/**
	 * @brief	Data for notification emails
	 */
	protected array $emailParams = array();
	
	/**
	 * @brief	Extra data to save with inline notifications
	 */
	protected array $inlineExtra = array();
	
	/**
	 * @brief	Unsubscribe Type
	 */
	public string $unsubscribeType = 'notification';

	/**
	 * @brief	Allow merging of notifications
	 */
	protected bool $allowMerging = TRUE;

	/**
	 * Constructor
	 *
	 * @param Application $app			The application the notification belongs to
	 * @param string $key			Notification key
	 * @param object|null $item			The thing the notification is about
	 * @param array $emailParams	Data for notification emails
	 * @param array|null $inlineExtra	Extra data to save with inline notifications. Use sparingly: only in cases where it is not possible to obtain the same data later. Will be merged for duplicate notifications.
	 * @param bool $allowMerging	Allow two identical notification types to be merged
	 * @param string|null $emailKey		Custom email template to use, or NULL to use default
	 * @return	void
	 */
	public function __construct( Application $app, string $key, object $item=NULL, array $emailParams=array(), ?array $inlineExtra=array(), bool $allowMerging=TRUE, string $emailKey=NULL )
	{
		$this->app			= $app;
		$this->key			= $key;
		$this->item			= $item;
		$this->recipients	= new Recipients;
		$this->emailParams	= $emailParams;
		$this->inlineExtra	= $inlineExtra ?: array();
		$this->allowMerging = $allowMerging;
		$this->emailKey		= ( $emailKey === NULL ) ? 'notification_' . $this->key : $emailKey;
	}
	
	/**
	 * Send Notification
	 *
	 * @param array $sentTo		Members who have already received a notification and how (same format as the return value) to prevent duplicates
	 * @return	array	The members that were notified and how they were notified
	 */
	public function send( array $sentTo = array() ) : array
	{
		if ( static::$silenced === TRUE )
		{
			return array();
		}

		/* Make a placeholder for emails - we'll need to generate one per language */
		$emails = array();
		$emailRecipients = array();
		$thingsBeingFollowed = array();
		$pushNotifications = array();

		/* First, loop over the members so we can load their notification preferences en-masse */
		$membersForNotifications = array();
		foreach ( $this->recipients as $member )
		{						
			/* Let's not send notifications to deleted members, banned members or spammers */
			if ( $member === NULL or !$member->member_id or $member->isBanned() or $member->members_bitoptions['bw_is_spammer'] )
			{
				continue;
			}

			$membersForNotifications[ $member->member_id ] = $member;
		}
		if( count( $membersForNotifications ) )
		{			
			/* Fill in any that may not have customized their preferences */
			foreach( $membersForNotifications as $member )
			{
				if( $member->notificationsConfiguration === NULL )
				{
					$member->notificationsConfiguration = array();
				}
			}

			/* Get all preferences at once */
			$preferenceSet = array();

			foreach (
				Db::i()->select(
					'd.*, p.preference, p.member_id',
					array( 'core_notification_defaults', 'd' )
				)->join(
					array( 'core_notification_preferences', 'p' ),
					array( 'd.notification_key=p.notification_key AND p.member_id IN(' . implode( ',', array_keys( $membersForNotifications ) ) . ')' )
				)
				as $row
			) {
				if( !in_array( $row['notification_key'], $preferenceSet ) )
				{
					foreach( $membersForNotifications as $member )
					{
						$member->notificationsConfiguration[ $row['notification_key'] ] = explode( ',', $row['default'] );
					}

					$preferenceSet[] = $row['notification_key'];
				}

				if ( $row['preference'] !== NULL AND $row['editable'] )
				{
					$membersForNotifications[ $row['member_id'] ]->notificationsConfiguration[ $row['notification_key'] ] = array_diff( explode( ',', $row['preference'] ), explode( ',', $row['disabled'] ) );
				}
			}
		}
		
		/* Loop recipients */
		foreach ( $this->recipients as $member )
		{						
			/* Let's not send notifications to deleted members, banned members or spammers */
			if ( $member === NULL or !$member->member_id or $member->isBanned() or $member->members_bitoptions['bw_is_spammer'] )
			{
				continue;
			}
			
			/* If there's an item, check the user has permission to view it and is not ignoring */
			if ( $this->item )
			{
				/* Permission check */
				$item = $this->item;
				if ( $item instanceof Item )
				{
					$application = Application::load( $item::$application );
					if ( !$application->canAccess( $member ) )
					{
						continue;
					}

					/* Skip if member is ignoring the item author but only if this is a new content item.
					If a member is following content they should still receive reply notifications regardless of author */
					if ( $this->key == "new_content" and $member->isIgnoring( $item->author(), 'topics' ) )
					{
						continue;
					}
				}
				
				/* Not ignoring the comment this is about */
				foreach( $this->emailParams AS $param )
				{
					if ( $param instanceof Comment )
					{
						if ( $member->isIgnoring( $param->author(), 'topics' ) )
						{
							continue 2;
						}
					}
					
					if ( $param instanceof Member)
					{
						if ( $member->isIgnoring( $param, 'topics' ) )
						{
							continue 2;
						}
					}
				}
			}
			
			/* Work out how the user wants to receive this notification */
			$notificationPreferences = $member->notificationsConfiguration();
			$info = $this->recipients->getInfo();
			if ( $info !== NULL and $info['follow_app'] === 'core' and $info['follow_area'] === 'member' )
			{
				$keyToCheck = 'follower_content';
			}
			else
			{
				$keyToCheck = $this->key;
				if ( $this->key === 'new_content_bulk' )
				{
					$keyToCheck = 'new_content';
				}
				if ( $this->key === 'unapproved_content_bulk' )
				{
					$keyToCheck = 'unapproved_content';
				}
			}
									
			/* They want to receive an email (we don't send until the end once we've collated all the emails to send) */
			if ( isset( $notificationPreferences[ $keyToCheck ] ) AND in_array( 'email', $notificationPreferences[ $keyToCheck ] ) and ( !isset( $sentTo[ $member->member_id ] ) or !in_array( 'email', $sentTo[ $member->member_id ] ) ) )
			{
				$language = $member->language()->id;

				if ( !isset( $emails[ $language ] ) )
				{
					$email = Email::buildFromTemplate( $this->app->directory, $this->emailKey, $this->emailParams, Email::TYPE_LIST );
					
					if ( $info )
					{
						$email->setUnsubscribe( 'core', 'unsubscribeFollow', array( $this->key ) );
					}
					else
					{
						$email->setUnsubscribe( 'core', 'unsubscribeNotification', array( $this->key ) );
					}
					
					$emails[ $language ] = $email;
				}
				
				$unsubscribeBlurb = NULL;
				$unfollowLink = NULL;
				$okToEmail = TRUE;
				
				if ( $info )
				{
					if ( !isset( $thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ] ) )
					{
						if ( $info['follow_app'] === 'core' and $info['follow_area'] === 'member' )
						{
							$thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ] = Member::load( $info['follow_rel_id'] );
						}
						else
						{
							/* @var ActiveRecord $classname */
							$classname = 'IPS\\' . $info['follow_app'] . '\\' . IPS::mb_ucfirst( $info['follow_area'] );
							$thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ] = $classname::load( $info['follow_rel_id'] );

							/* Set some parameters so the best advertisement possible can be loaded later */
							$email->setAdvertisementParameters( $classname, $info['follow_rel_id'] );
						}
					}
				
					$thingBeingFollowed = $thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ];
					if ( $thingBeingFollowed instanceof Member)
					{
						$unsubscribeBlurb = $member->language()->addToStack( 'unsubscribe_blurb_follow_member', FALSE, array( 'htmlsprintf' => array( $thingBeingFollowed->name ) ) );
					}
					elseif ( $thingBeingFollowed instanceof Model )
					{
						$unsubscribeBlurb	= $member->language()->addToStack( 'unsubscribe_blurb_follow', FALSE, array( 'htmlsprintf' => array( $member->language()->addToStack( $thingBeingFollowed::$nodeTitle . '_sg' ), $thingBeingFollowed->getTitleForLanguage( $member->language() ) ) ) );
					}
					else
					{
						$unsubscribeBlurb	= $member->language()->addToStack( 'unsubscribe_blurb_follow', FALSE, array( 'htmlsprintf' => array( $member->language()->addToStack( $thingBeingFollowed::$title ), $thingBeingFollowed->mapped('title') ) ) );
					}
					
					$guestKey = md5( $info['follow_app'] . ';' . $info['follow_area'] . ';' . $info['follow_rel_id'] . ';' . $info['follow_member_id'] . ';' . $info['follow_added'] ) . '-' . md5( $member->email . ';' . $member->ip_address . ';' . $member->joined->getTimestamp() );
					$unfollowLink = Url::internal( "app=core&module=system&controller=notifications&do=unfollowFromEmail&follow_app={$info['follow_app']}&follow_area={$info['follow_area']}&follow_id={$info['follow_rel_id']}&gkey={$guestKey}", 'front' );

					/* If we are tracking email views/clicks, add the tracking info to this URL as the email handler won't be able to */
					if( Settings::i()->prune_log_emailstats != 0 )
					{
						$unfollowLink = $unfollowLink->setQueryString( array( 'email' => 1, 'type' => $this->emailKey ) );
					}

					$unfollowLink = (string) $unfollowLink;
	
					if ( $member->members_bitoptions['email_notifications_once'] and max( $member->last_activity, $member->last_visit ) < $info['follow_notify_sent'] )
					{
						$okToEmail = FALSE;
					}
				}
				
				if ( $okToEmail )
				{
					$emailRecipients[ $language ][ $member->email ] = array(
						'member_name'		=> $member->name,
						'unsubscribe_blurb'	=> $unsubscribeBlurb,
						'unfollow_link'		=> $unfollowLink
					);
				}
				
				$sentTo[ $member->member_id ][] = 'email';
			}

			/* They want to receive an inline notification... (ignore for report center which is treated special and the 'inline' notification
				preference actually instead controls whether the bubble should be shown on the report center icon at the top or not) */
			$notification = NULL;
			if ( $this->key != 'report_center' and isset( $notificationPreferences[ $keyToCheck ] ) and in_array( 'inline', $notificationPreferences[ $keyToCheck ] ) and ( !isset( $sentTo[ $member->member_id ] ) or !in_array( 'inline', $sentTo[ $member->member_id ] ) ) )
			{
				$hasMerged = false;
				if ( $this->item and $this->allowMerging )
				{
					try
					{
						$item = $this->item;
						$idColumn = $item::$databaseColumnId;
						$notification = Inline::constructFromData( Db::i()->select( '*', 'core_notifications', ['notification_key=? AND item_class=? AND item_id=? AND `member`=? AND read_time IS NULL', $this->key, get_class( $this->item ), $item->$idColumn, $member->member_id] )->first() );

						$notification->member = $member;
						$notification->updated_time = time();
						$notification->extra = array_merge( $notification->extra, $this->inlineExtra );
						$notification->save();

						$hasMerged = true;
					}
					catch ( UnderflowException )
					{
					}
				}

				if ( ! $hasMerged )
				{
					$notification = new Inline;
					$notification->member = $member;
					$notification->notification_app = $this->app;
					$notification->notification_key = $this->key;
					if ( $this->item )
					{
						$notification->item = $this->item;
					}
					$notification->member_data = $info;

					foreach ( $this->emailParams as $param )
					{
						if ( $param instanceof Content )
						{
							$subIdColumn = $param::$databaseColumnId;
							$notification->item_sub_class = get_class( $param );
							$notification->item_sub_id = $param->$subIdColumn;

							/*
							 * If this is a grouped comment or review, set the sent time to the same time as the comment just in case there is a slight delay
							 */
							if ( ( $param instanceof Comment ) && in_array( $this->key, ['new_comment', 'new_review', 'quote', 'new_likes'] ) )
							{
								if ( $this->key === 'new_likes' and $this->emailParams[1] instanceof Member )
								{
									/* Reset the time to the time of the rep to prevent a slight delay from missing this notification */
									try
									{
										$where = $param->getReactionWhereClause();
										$where[] = ['member_id = ?', $this->emailParams[1]->member_id];

										$notification->sent_time = Db::i()->select( 'rep_date', 'core_reputation_index', $where )->join( 'core_reactions', 'reaction=reaction_id' )->first();
									}
									catch ( \Exception $ex )
									{
									}
								}
								else
								{
									$notification->sent_time = $param->mapped( 'date' );
								}
							}
						}
					}

					$notification->extra = $this->inlineExtra;
					$notification->save();
				}

				$sentTo[$member->member_id][] = 'inline';
			}

			/* They want to receive a push notification (we don't send until the end once we've collated all the notifications to send) */
			if ( ( static::webPushEnabled() && count( $member->getPwaAuths() ) ) and isset( $notificationPreferences[ $keyToCheck ] ) AND in_array( 'push', $notificationPreferences[ $keyToCheck ] ) and ( !isset( $sentTo[ $member->member_id ] ) or !in_array( 'push', $sentTo[ $member->member_id ] ) ) )
			{				
				$language = $member->language();
				if ( !isset( $pushNotifications[ $language->id ] ) )
				{
					$method = "parse_mobile_{$this->key}";
					foreach ( $this->app->extensions( 'core', 'Notifications' ) as $class )
					{
						if ( method_exists( $class, $method ) or $this->key === 'follower_content' )
						{
							$data = $class::$method( $language, ...$this->emailParams );
							$language->parseOutputForDisplay( $data );
							
							if ( !isset( $data['data'] ) )
							{
								$data['data'] = array();
							}
							$data['data']['type'] = $this->app->directory . '/' . $this->key;
							
							if ( isset( $data['data']['url'] ) )
							{
								$url = Url::createFromString( $data['data']['url'] );
								if ( $url instanceof Internal )
								{
									foreach ( array( 'app', 'module', 'controller', 'id', 'comment', 'review' ) as $k )
									{
										if ( isset( $url->hiddenQueryString[ $k ] ) )
										{
											$data['data'][ $k ] = $url->hiddenQueryString[ $k ];
										}
										elseif ( isset( $url->queryString[ $k ] ) )
										{
											$data['data'][ $k ] = $url->queryString[ $k ];
										}
									}
								}
							}
							if ( isset( $data['data']['author'] ) )
							{
								$data['data']['image'] = $data['data']['author']->photo;
								unset( $data['data']['author'] );
							}
							
							$pushNotifications[ $language->id ] = $data;
							break;
						}
					}					
				}
								
				if ( isset( $pushNotifications[ $language->id ] ) )
				{
					$pushRecipients[ $member->member_id ] = $pushNotifications[ $language->id ];
					$pushRecipients[ $member->member_id ]['member'] = $member->member_id;
					$pushRecipients[ $member->member_id ]['unreadCount'] = $member->notification_cnt;
					$pushRecipients[ $member->member_id ]['notificationId'] = $notification?->id;
				}
								
				$sentTo[ $member->member_id ][] = 'push';
			}
		}


		/* On cloud, push this to the browser */
		if ( Bridge::i()->featureIsEnabled( 'realtime' ) )
		{
			$memberIds = [];
			foreach ( $sentTo as $memberId => $data )
			{
				if ( in_array( 'push', $data ) OR in_array( 'inline', $data ) )
				{
					$memberIds[] = $memberId;
				}
			}

			if ( count( $memberIds ) )
			{
				Bridge::i()->publishRealtimeEvent( 'notifications_available', location: array_map( 'intval', $memberIds ), locationAsMember: true );
			}
		}


		/* Send any emails and push notifications */
		if ( count( $pushNotifications ) )
		{
			$this->sendPushNotifications( $pushRecipients );
		}
		if ( count( $emails ) )
		{
			$this->sendEmails( $emails, $emailRecipients );
		}
		
		/* And return */
		return $sentTo;
	}

	/**
	 * Send emails
	 *
	 * @param array $emails				Emails to send
	 * @param array $emailRecipients	Email recipients
	 * @return	void
	 */
	protected function sendEmails( array $emails, array $emailRecipients ) : void
	{
		foreach ( $emails as $languageId => $email )
		{
			if ( !empty( $emailRecipients[ $languageId ] ) )
			{
				$email->mergeAndSend( $emailRecipients[ $languageId ], NULL, NULL, array(), Lang::load( $languageId ) );
			}
		}
	}
	
	/**
	 * Send push notifications
	 *
	 * @param array $pushNotifications	Push notifications to send
	 * @return	void
	 */
	protected function sendPushNotifications( array $pushNotifications ) : void
	{
		/* Send PWA notifications */
		$pwaPushNotifications = [];

		foreach ( $pushNotifications as $memberId => $data )
		{
			$data = json_encode( array(
				'member'	=> $memberId,
				'title'		=> static::textForPushNotification( $data['title'] ?? Settings::i()->board_name ),
				'body'		=> static::textForPushNotification( $data['body'] ),
				'url'		=> isset( $data['data']['url'] ) ? (string) $data['data']['url'] : NULL,
				'icon'		=> $data['icon'] ?? NULL,
				'image'		=> $data['image'] ?? NULL,
				'tag'		=> $data['tag'] ?? NULL,
				'grouped'	=> !empty( $data['data']['grouped'] ) ? static::textForPushNotification( $data['data']['grouped'] ) : NULL,
				'groupedTitle'	=> !empty( $data['data']['groupedTitle'] ) ? static::textForPushNotification( $data['data']['groupedTitle'] ) : NULL,
				'groupedUrl' => isset( $data['data']['groupedUrl'] ) ? (string) $data['data']['groupedUrl'] : NULL
			) );

			$notificationId = Db::i()->insert( 'core_notifications_pwa_queue', array( 'notification_data' => $data, 'expiration' => DateTime::ts( time() )->add( new DateInterval('P1D') )->getTimestamp() ) );

			$pwaPushNotifications[ $memberId ] = [
				'member'	=> $memberId,
				'data'		=> array(
					'id'		=> $notificationId
				),
				'TTL'		=> $data['TTL'] ?? static::TTL_SHORT,
				'urgency'	=> $data['urgency'] ?? static::URGENCY_NORMAL
			];
		}

		$memberIds = array_keys( $pwaPushNotifications );
		$count = Db::i()->select( 'COUNT(*)', 'core_notifications_pwa_keys', Db::i()->in( '`member`', $memberIds ) )->first();

		if ( $count )
		{
			if ( $count == 1 )
			{
				static::sendPWANotifications( $pwaPushNotifications );
			}
			else
			{
				Task::queue( 'core', 'PWANotifications', $pwaPushNotifications, 2 );
			}
		}
	}

	/**
	 * Generates a VAPID key pair for web push notification support
	 *
	 * @return	array 	Contains public and private keys
	 */
	public static function generateVapidKeys(): array
	{
		IPS::$PSR0Namespaces['Jose'] = ROOT_PATH . '/system/3rd_party/JwtFramework/src';
		IPS::$PSR0Namespaces['Minishlink'] = ROOT_PATH . '/system/3rd_party/Minishlink';
		IPS::$PSR0Namespaces['Base64Url'] = ROOT_PATH .'/system/3rd_party/Base64Url';
		IPS::$PSR0Namespaces['Brick'] = ROOT_PATH . '/system/3rd_party/Brick';

		return VAPID::createVapidKeys();
	}
	
	/**
	 * Send PWA notifications
	 *
	 * @param array $data	PWA notifications to send
	 * @return	void
	 */
	public static function sendPWANotifications( array $data ) : void
	{
		IPS::$PSR0Namespaces['Jose'] = ROOT_PATH . '/system/3rd_party/JwtFramework/src';
		IPS::$PSR0Namespaces['Minishlink'] = ROOT_PATH . '/system/3rd_party/Minishlink';
		IPS::$PSR0Namespaces['Base64Url'] = ROOT_PATH .'/system/3rd_party/Base64Url';
		IPS::$PSR0Namespaces['Brick'] = ROOT_PATH . '/system/3rd_party/Brick';
		
		// Step 1: Validate VAPID details
		$vapid = VAPID::validate( array(
			'subject'		=> "mailto:" . Settings::i()->email_in,
			'publicKey'		=> Settings::i()->vapid_public_key,
			'privateKey'	=> Settings::i()->vapid_private_key
		) );

		foreach (Db::i()->select( '*', 'core_notifications_pwa_keys', Db::i()->in( '`member`', array_keys( $data ) ) ) as $auth )
		{
			/* Check device is logged in */
			try
			{
				$device = Device::load( $auth['device'], NULL, [ 'member_id=?', $auth['member'] ] );

				if ( !$device->login_key )
				{
					/* Is not, continue */
					continue;
				}
			}
			catch( OutOfRangeException $e )
			{
				/* Device does not exist so delete & skip */
				Db::i()->delete( 'core_notifications_pwa_keys', array( 'device=?', $auth['device'] ) );
				continue;
			}
			
			$endpoint = Url::external( $auth['endpoint'] );

			// Step 2: Content encoding (provided by browser)
			$contentEncoding = $auth['encoding'];
	
			// Step 3: Get and pad the payload
			$payload = json_encode( $data[ $auth['member'] ]['data'] );
			$payload = Encryption::padPayload( $payload, Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH, $contentEncoding );

			// Step 4: Build Vapid headers
			$audience = $endpoint->data[ Url::COMPONENT_SCHEME ] . "://" . $endpoint->data[ Url::COMPONENT_HOST ];
			$cacheKey = implode( '#', array( $audience, $contentEncoding, md5(json_encode($vapid)) ) );

			if( isset( static::$vapidHeaderCache[ $cacheKey ] ) )
			{
				$vapidHeaders = static::$vapidHeaderCache[ $cacheKey ];
			} 
			else 
			{
				$vapidHeaders = VAPID::getVapidHeaders( $audience, $vapid['subject'], $vapid['publicKey'], $vapid['privateKey'], $contentEncoding );
				static::$vapidHeaderCache[ $cacheKey ] = $vapidHeaders;
			}

			// Step 5: Encrypt the payload with user's keys
			$encryptedPayload = Encryption::encrypt( $payload, $auth['p256dh'], $auth['auth'], $contentEncoding );
			$salt = $encryptedPayload['salt'];
			$localPublicKey = $encryptedPayload['localPublicKey'];
			$cipherText = $encryptedPayload['cipherText'];
			
			// Step 6: Get the content coding header and prepend it to the content
			$encryptionContentCodingHeader = Encryption::getContentCodingHeader($salt, $localPublicKey, $contentEncoding);
			$content = $encryptionContentCodingHeader.$cipherText;
			
			// Step 7: Set headers
			$headers = array();
			$headers['Content-Type'] = 'application/octet-stream';
			$headers['Content-Encoding'] = $contentEncoding;
			$headers['TTL'] = $data['TTL'] ?? static::TTL_MEDIUM;

			if( isset( $data['urgency'] ) )
			{
				$headers['urgency'] = $data['urgency'];
			}

			if ( $contentEncoding === "aesgcm" ) 
			{
				$headers['Encryption'] = 'salt=' . Base64Url::encode($salt);
				$headers['Crypto-Key'] = 'dh=' . Base64Url::encode($localPublicKey) . ';' . $vapidHeaders['Crypto-Key'];
			}

			$headers['Content-Length'] = Utils::safeStrlen($content);
			$headers['Authorization'] = $vapidHeaders['Authorization'];
			
			try
			{
				// Step 8: Send the damn thing
				$response = $endpoint->request()->setHeaders( $headers )->post( $content );
			}
			catch( Exception $e )
			{
				/* If the request failed (DNS issues, etc.). Log it and move on. */
				Log::log( $e, 'pwa_notification' );
				continue;
			}

			// Step 9: Check the response - 404/410 indicates a permanent problem, so delete that key
			if( in_array( $response->httpResponseCode, array( 404, 410 ) ) )
			{
				Db::i()->delete( 'core_notifications_pwa_keys', array('id = ?', $auth['id'] ) );
			}
		}
	}

	/**
	 * Convert HTML to plaintext for use in notifications
	 *
	 * @param string $html	HTML Text
	 * @return	string
	 * @todo Almost certainly will need to make this more thorough
	 */
	public static function textForPushNotification( string $html ): string
	{
		return preg_replace( "/\n\s+/", "\n", trim( html_entity_decode( strip_tags( $html ) ) ) );
	}
}
