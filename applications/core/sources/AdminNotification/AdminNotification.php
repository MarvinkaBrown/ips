<?php
/**
 * @brief		Admin Notification
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\Helpers\Form\FormAbstract;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function method_exists;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Block
 */
abstract class AdminNotification extends ActiveRecord
{
	/**
	 * @brief	Dynamic severity
	 * @note	Uses more resources than the other types. Use only when the notification may show to some admins but not others
	 */
	const SEVERITY_DYNAMIC = 'dynamic';

	/**
	 * @brief	Optional severity
	 */
	const SEVERITY_OPTIONAL = 'optional';

	/**
	 * @brief	Normal severity
	 */
	const SEVERITY_NORMAL = 'normal';

	/**
	 * @brief	High severity
	 */
	const SEVERITY_HIGH = 'high';

	/**
	 * @brief	Critical severity
	 */
	const SEVERITY_CRITICAL = 'critical';

	/**
	 * @brief	Not dismissable
	 */
	const DISMISSIBLE_NO = 'no';

	/**
	 * @brief	Temporarily dismissable
	 */
	const DISMISSIBLE_TEMPORARY = 'temp';
	
	/**
	 * @brief	Dismissible until it recurs
	 */
	const DISMISSIBLE_UNTIL_RECUR = 'recur';

	/**
	 * @brief	Fully dismissable
	 */
	const DISMISSIBLE_PERMANENT = 'perm';
	
	/**
	 * @brief	Styling: error
	 */
	const STYLE_ERROR = 'error';

	/**
	 * @brief	Styling: warning
	 */
	const STYLE_WARNING = 'warning';

	/**
	 * @brief	Styling: information
	 */
	const STYLE_INFORMATION = 'information';

	/**
	 * @brief	Styling: expiring
	 */
	const STYLE_EXPIRE = 'expire';
		
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'acpNotifications', 'acpNotificationIds' );
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_acp_notifications';
	
	/**
	 * Get Number of Notifications
	 *
	 * @param	Member|NULL	$member		The member viewing, or NULL for currently logged in
	 * @param	array				$severities	The severities
	 * @return	int
	 */
	public static function notificationCount( Member $member = NULL, array $severities = array( 'dynamic', 'optional', 'normal', 'high', 'critical' ) ) : int
	{
		$member = $member ?: Member::loggedIn();
		$notificationIds = static::notificationIdsForMember( $member );
				
		$return = 0;
		foreach ( $severities as $s )
		{
			if ( $s === 'dynamic' )
			{
				foreach ( $notificationIds['dynamic'] as $i )
				{
					$notification = static::load( $i );
					if ( $notification->dynamicShow( $member ) )
					{
						$return++;
					}
				}
			}
			else
			{
				$return += count( $notificationIds[ $s ] );
			}
		}
						
		return $return;
	}
	
	/**
	 * Get Notifications that a particular member can see by severity
	 *
	 * @param	Member|NULL	$member		The member viewing, or NULL for currently logged in
	 * @param	array				$severities	The severities
	 * @return array
	 */
	public static function notifications( Member $member = NULL, array $severities = array( 'dynamic', 'optional', 'normal', 'high', 'critical' ) ) : array
	{
		$member = $member ?: Member::loggedIn();
		$notificationIds = static::notificationIdsForMember( $member );
		
		$return = array();
		foreach ( static::allNotifications() as $notification )
		{
			$severity = $notification->severity();
			if ( in_array( $severity, $severities ) and in_array( $notification->id, $notificationIds[ $severity ] ) and ( $severity !== static::SEVERITY_DYNAMIC or $notification->dynamicShow( $member ) ) )
			{
				/* Check if this notification should dismiss itself first */
				if ( $notification->selfDismiss() )
				{
					$notification->delete();
					continue;
				}

				$return[ $notification->id ] = $notification;
			}
		}
		
		return $return;
	}
	
	/**
	 * Get Cached IDs of Notifications that a particular member can see by severity
	 *
	 * @param	Member	$member		The member
	 * @return	array
	 */
	protected static function notificationIdsForMember( Member $member ) : array
	{
		if ( !isset( Store::i()->acpNotificationIds ) or !isset( Store::i()->acpNotificationIds[ $member->member_id ] ) )
		{
			/* Init */
			$data = isset( Store::i()->acpNotificationIds ) ? Store::i()->acpNotificationIds : array();
			$data[ $member->member_id ] = array( static::SEVERITY_DYNAMIC => array(), static::SEVERITY_OPTIONAL => array(), static::SEVERITY_NORMAL => array(), static::SEVERITY_HIGH => array(), static::SEVERITY_CRITICAL => array() );
			
			/* Get our preferences */
			$preferences = iterator_to_array( Db::i()->select( '*', 'core_acp_notifications_preferences', array( '`member`=?', $member->member_id ) )->setKeyField('type') );
			
			/* Get our dismissals */
			$dismissals = array();
			foreach ( Db::i()->select( '*', 'core_acp_notifcations_dismissals', array( '`member`=?', $member->member_id ) ) as $row )
			{
				$dismissals[ $row['notification'] ] = $row['time'];
			}
			
			/* Loop through them */
			foreach ( static::allNotifications() as $notification )
			{
				/* Check we want to see it if it's optional */
				$exploded = explode( '\\', get_class( $notification ) );
				$key = "{$exploded[1]}_{$exploded[5]}";
				$view = isset( $preferences[ $key ] ) ? $preferences[ $key ]['view'] : $notification::defaultValue();
				if ( !$view )
				{
					continue;
				}
				
				/* Check we haven't dismissed it */
				if ( isset( $dismissals[ $notification->id ] ) )
				{
					if ( $notification->dismissible() === static::DISMISSIBLE_TEMPORARY and $dismissals[ $notification->id ] < ( time() - 86400 ) )
					{
						Db::i()->delete( 'core_acp_notifcations_dismissals', array( 'notification=? AND `member`=?', $notification->id, $member->member_id ) );
					}
					elseif ( $notification->dismissible() === static::DISMISSIBLE_UNTIL_RECUR and $dismissals[ $notification->id ] < $notification->sent->getTimestamp() )
					{
						// Nothing, we're showing it
					}
					else
					{
						continue;
					}
				}
				
				/* Check we can see it */
				if ( $notification->visibleTo( $member ) )
				{
					$data[ $member->member_id ][ $notification->severity() ][ $notification->id ] = $notification->id;
				}
			}
			
			Store::i()->acpNotificationIds = $data;
		}
		
		return Store::i()->acpNotificationIds[ $member->member_id ];
	}
	
	/**
	 * Get Notifications
	 *
	 * @return	array
	 */
	protected static function allNotifications() : array
	{
		if ( !isset( Store::i()->acpNotifications ) )
		{
			Store::i()->acpNotifications = iterator_to_array( Db::i()->select( '*', 'core_acp_notifications', NULL, 'sent DESC' ) );
		}		
				
		$notifications = array();
		foreach ( Store::i()->acpNotifications as $notification )
		{
			if ( Application::appIsEnabled( $notification['app'] ) AND class_exists( 'IPS\\' . $notification['app'] . '\extensions\core\AdminNotifications\\' . IPS::mb_ucfirst( $notification['ext'] ) ) )
			{
				$notificationObject = static::constructFromData( $notification );

				if ( $notificationObject )
				{
					$notifications[$notificationObject->id] = $notificationObject;
				}
				else
				{
					/* Remove orphan entry */
					Db::i()->delete( 'core_acp_notifications', ['id=?', $notification['id']] );
				}
			}
		}
		return $notifications;
	}
	
	/**
	 * Find Existing Notification
	 *
	 * @param	string		$app		Application key
	 * @param	string		$extension	Extension key
	 * @param	string|null	$extra		Any additional information
	 * @param	bool		$forceRebuild	Force a rebuild of the cache; used primarily for sending, not removing
	 * @return	static|null
	 */
	public static function find( string $app, string $extension, ?string $extra = NULL, bool $forceRebuild=true ) : ?static
	{
		/* Drop the cache before we search, to ensure that we have the latest data */
		if( $forceRebuild )
		{
			try
			{
				unset( Store::i()->acpNotifications );
			}
			catch( OutOfRangeException ){}
		}

		foreach ( static::allNotifications() as $notification )
		{
			if ( $notification->app === $app and $notification->ext === $extension and $notification->extra === $extra )
			{
				return $notification;
			}
		}
		return NULL;
	}
	
	/**
	 * Send Notification
	 *
	 * @param	string				$app				Application key
	 * @param	string				$extension			Extension key
	 * @param	string|null			$extra				Any additional information which persists if the notification is resent
	 * @param	bool|null			$resend				If an existing notification exists, it will be bumped / resent
	 * @param	mixed				$extraForEmail		Any additional information specific to this instance which is used for the email but not saved
	 * @param	bool|Member	$bypassEmail		If TRUE, no email will be sent, regardless of admin preferences - or if a member object, that admin will be skipped. Should only be used if the action is initiated by an admin making an email unnecessary
	 * @param	array				$additionalData		Any additional data to save to the notification
	 * @return	void
	 */
	public static function send( string $app, string $extension, ?string $extra = NULL, ?bool $resend = TRUE, mixed $extraForEmail = NULL, bool|Member $bypassEmail = FALSE, array $additionalData = [] ) : void
	{
		/* Create or update */
		if ( $notification = static::find( $app, $extension, $extra ) )
		{
			if ( !$resend )
			{
				return;
			}
			$notification->sent = time();
		}
		else
		{
			$classname = 'IPS\\' . $app . '\extensions\core\AdminNotifications\\' . IPS::mb_ucfirst( $extension );
			$notification = new $classname;
			$notification->app = $app;
			$notification->ext = $extension;
			$notification->extra = $extra;
			$notification->additionalData = $additionalData;

			unset( Store::i()->acpNotifications );
		}		
		
		/* Is this a new notification? */
		if ( !$notification->_new and !$resend )
		{
			return;
		}
				
		/* Get where clause for email notifications */		
		$exploded = explode( '\\', get_class( $notification ) );
		$key = "{$exploded[1]}_{$exploded[5]}";
		$where = array( array( 'type=?', $key ) );
		$where[] = $notification->emailWhereClause( $extraForEmail );
			
		/* Save */
		$notification->save();
		
		/* Email */
		if ( $bypassEmail !== TRUE )
		{
			/* Work out if we need to email this to anyone */
			$emailRecipients = array();
			foreach ( Db::i()->select( '`member`', 'core_acp_notifications_preferences', $where ) as $memberId )
			{
				$member = Member::load( $memberId );
				if ( $member->member_id and $notification->visibleTo( $member ) )
				{
					if ( !( $bypassEmail instanceof Member ) or $bypassEmail->member_id !== $member->member_id )
					{
						$emailRecipients[] = $member;
					}
				}
			}
											
			/* And if we do, do it */
			if ( count( $emailRecipients ) )
			{
				$email = Email::buildFromTemplate( $exploded[1], 'acp_notification_' . $exploded[5], array( $notification, $extraForEmail ), Email::TYPE_TRANSACTIONAL );
				$email->setUnsubscribe( 'core', 'unsubscribeAcpNotification', array( get_class( $notification ) ) );
				foreach ( $emailRecipients as $member )
				{
					$email->send( $member );
				}
			}
		}
	}
	
	/**
	 * Delete Notification
	 *
	 * @param	string				$app		Application key
	 * @param	string				$extension	Extension key
	 * @param	string|null			$extra		Any additional information
	 * @param	DateTime|null	$newTime		If provided, rather than deleting the notification, it will modify it's sent time to the specified time
	 * @return	void
	 */
	public static function remove( string $app, string $extension, ?string $extra = NULL, ?DateTime $newTime = NULL ) : void
	{
		if ( $notification = static::find( $app, $extension, $extra, false ) )
		{
			if ( $newTime )
			{
				$notification->sent = $newTime->getTimestamp();
				$notification->save();
			}
			else
			{
				$notification->delete();
			}
		}
	}
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    ActiveRecord
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): ActiveRecord
	{
		$classname = 'IPS\\' . $data['app'] . '\extensions\core\AdminNotifications\\' . IPS::mb_ucfirst( $data['ext'] );

		/* Initiate an object */
		$obj = new $classname;
		$obj->_new = FALSE;

		/* Import data */
		$databasePrefixLength = strlen( static::$databasePrefix );
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = substr( $k, $databasePrefixLength );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();

		/* Init */
		if ( method_exists( $obj, 'init' ) )
		{
			$obj->init();
		}

		/* Return */
		return $obj;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->sent = time();
	}

	/**
	 * Set Additional Data
	 *
	 * @param $data
	 * @return void
	 */
	public function set_additionalData( $data ): void
	{
		$this->_data['additional_data'] = json_encode( $data );
	}

	/**
	 * Get Additional Data
	 *
	 * @return array
	 */
	public function get_additionalData(): array
	{
		return $this->_data['additional_data'] ? json_decode( $this->_data['additional_data'], TRUE ) : [];
	}

	/**
	 * Get sent time
	 *
	 * @return	DateTime
	 */
	public function get_sent() : DateTime
	{
		return DateTime::ts( $this->_data['sent'] );
	}
	
	/**
	 * @brief	Identifier for what to group this notification type with on the settings form
	 */
	public static string $group = 'other';
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this group compared to others
	 */
	public static int $groupPriority = 5;
	
	/**
	 * @brief	Priority 1-5 (1 being highest) for this notification type compared to others in the same group
	 */
	public static int $itemPriority = 3;
	
	/**
	 * Can a member access this type of notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public static function permissionCheck( Member $member ) : bool
	{
		return TRUE;
	}
	
	/**
	 * Title for settings
	 *
	 * @return	string
	 */
	abstract public static function settingsTitle() : string;
		
	/**
	 * Can a member view this notification?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function visibleTo( Member $member ) : bool
	{
		return static::permissionCheck( $member );
	}
	
	/**
	 * For dynamic notifications: should this show for this member?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function dynamicShow( Member $member ) : bool
	{
		return FALSE;
	}
	
	/**
	 * Should this notification dismiss itself?
	 *
	 * @note	This is checked every time the notification shows. Should be lightweight.
	 * @return	bool
	 */
	public function selfDismiss() : bool
	{
		return FALSE;
	}
	
	/**
	 * The default value for if this notification shows in the notification center
	 *
	 * @return	bool
	 */
	public static function defaultValue() : bool
	{
		return TRUE;
	}
	
	/**
	 * Is this type of notification ever optional (controls if it will be selectable as "viewable" in settings)
	 *
	 * @return	bool
	 */
	public static function mayBeOptional() : bool
	{
		return TRUE;
	}
	
	/**
	 * Is this type of notification might recur (controls what options will be available for the email setting)
	 *
	 * @return	bool
	 */
	public static function mayRecur() : bool
	{
		return TRUE;
	}
	
	/**
	 * Custom per-admin setting for if email shoild be sent for this notification
	 *
	 * @param string $key	Setting field key
	 * @param mixed $value	Current value
	 * @return    FormAbstract|NULL
	 */
	public static function customEmailConfigurationSetting( string $key, mixed $value ): ?FormAbstract
	{
		return NULL;
	}
	
	/**
	 * WHERE clause to use against core_acp_notifications_preferences for fetching members to email
	 *
	 * @param mixed $extraForEmail		Any additional information specific to this instance which is used for the email but not saved
	 * @return    array
	 */
	public function emailWhereClause( mixed $extraForEmail ): array
	{
		if ( $this->_new )
		{
			return array( "( email='always' OR email='once' )" );
		}
		else
		{
			return array( "email='always'" );
		}
	}
		
	/**
	 * Notification Title (full HTML, must be escaped where necessary)
	 *
	 * @return	string
	 */
	abstract public function title() : string;
	
	/**
	 * Notification Subtitle (no HTML)
	 *
	 * @return	string|null
	 */
	public function subtitle() : ?string
	{
		return NULL;
	}
	
	/**
	 * Notification Body (full HTML, must be escaped where necessary)
	 *
	 * @return	string|null
	 */
	abstract public function body() : ?string;
		
	/**
	 * Severity
	 *
	 * @return	string
	 */
	abstract public function severity() : string;
	
	/**
	 * Dismissible?
	 *
	 * @return	string
	 */
	abstract public function dismissible() : string;
	
	/**
	 * Style
	 *
	 * @return string
	 */
	public function style() : string
	{
		switch ( $this->severity() )
		{
			case static::SEVERITY_CRITICAL:
				return static::STYLE_ERROR;
			case static::SEVERITY_HIGH:
				return static::STYLE_WARNING;
			default:
				return static::STYLE_INFORMATION;
		}
	}
	
	/**
	 * Quick link from popup menu
	 *
	 * @return	Url|null
	 */
	public function link() : Url|null
	{
		return Url::internal( 'app=core&module=overview&controller=notifications&highlightedId=' . $this->id );
	}
		
	/**
	 * Delete
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		Db::i()->delete( 'core_acp_notifcations_dismissals', array( 'notification=?', $this->id ) );
		parent::delete();
	}


	/**
	 * Dismiss a notification for a member and rebuild the datastore
	 *
	 * @param int $notificationId
	 * @param Member|null $member
	 *
	 * @return void
	 */
	public static function dismissNotification( int $notificationId, ?Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();

		Db::i()->insert( 'core_acp_notifcations_dismissals', array(
			'notification'	=> $notificationId,
			'member'		=> $member->member_id,
			'time'			=> time()
		), TRUE );

		if( isset( Store::i()->acpNotificationIds ) )
		{
			$notificationCache = Store::i()->acpNotificationIds;

			if( isset( $notificationCache[ $member->member_id ] ) )
			{
				unset( $notificationCache[ $member->member_id ] );
			}
		
			Store::i()->acpNotificationIds = $notificationCache;
		}
	}
}