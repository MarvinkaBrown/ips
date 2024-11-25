<?php
/**
 * @brief		Member Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DateTimeZone;
use DomainException;
use ErrorException;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application\Module;
use IPS\Content\Comment;
use IPS\Content\Followable;
use IPS\Content\Review;
use IPS\core\Achievements\Badge;
use IPS\core\Achievements\Rank;
use IPS\core\Achievements\Rule;
use IPS\core\AdminNotification;
use IPS\core\Assignments\Assignment;
use IPS\core\ProfileFields\Api\Field;
use IPS\core\ProfileFields\Api\FieldGroup;
use IPS\core\Reports\Report;
use IPS\Data\Store;
use IPS\Db\Select;
use IPS\Events\Event;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\Menu;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Lang\Setup\Lang as SetupLang;
use IPS\Lang\Upgrade\Lang as UpgradeLang;
use IPS\Login\Handler;
use IPS\Math\Number;
use IPS\Member\Club;
use IPS\Member\Device;
use IPS\Member\Group;
use IPS\Member\GroupPromotion;
use IPS\Member\LetterPhoto;
use IPS\Member\PrivacyAction;
use IPS\Member\ProfileStep;
use IPS\Member\Team;
use IPS\Member\UserMenu;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Text\Encrypt;
use IPS\Xml\SimpleXML;
use OutOfRangeException;
use RuntimeException;
use UnderflowException;
use function array_key_exists;
use function count;
use function defined;
use function get_called_class;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function mb_stripos;
use function strlen;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Model
 *
 * @property ?int member_id
 * @property DateTime joined
 */
class Member extends ActiveRecord
{
	use Followable
	{
		Followable::followersCount as public _followersCountBase;
		Followable::followers as public _followersBase;
	}

	/**
	 * @brief Spam Check Post Threshold
	 */
	const SPAM_CHECK_POST_THRESHOLD = 5;

	/**
	 * @brief	Spam Check Dormant Threshold
	 */
	const SPAM_CHECK_DORMANT_THRESHOLD = 'P6M';

	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
	
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_members';
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'member_id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'name', 'email' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	Bitwise values for members_bitoptions field
	 */
	public static array $bitOptions = array(
		'members_bitoptions'	=> array(
			'members_bitoptions'	=> array(
				'bw_is_spammer'					=> 1,			// Flagged as spam?
				// 2 is deprecated
				// 4 (bw_vnc_type) is deprecated
				// 8 (bw_forum_result_type) is deprecated
				// 16 (bw_no_status_update is deprecated
				// 32 (bw_status_email_mine) is deprecated
				// 64 (bw_status_email_all) is deprecated
				// 128 is deprecated (previously bw_disable_customization)
				// 256 is deprecated (bw_local_password_set) - used to represent if a local passwnot was set and block the change password form if not
				'bw_disable_tagging'			=> 512,			// Tags disabled for this member? 1 means they are, 0 means they aren't.
				'bw_disable_prefixes'			=> 1024,		// Tag prefixes disabled? 1 means they are, 0 means they aren't.
				'bw_using_skin_gen'				=> 2048,		// 1 means the user has the theme editor active, 0 means they do not.
				// 4096 (bw_disable_gravatar) is deprecated
				// 8192 (bw_paste_plain) is deprecated
				// 16384 (bw_html_sig) is deprecated
				// 32768 (allow_admin_mails) is deprecated
				'view_sigs'						=> 65536,		// View signatures?
				// 131072 (view_img) is deprecated
				// 262144 is deprecated
				'coppa_user'					=> 524288,		// Was the member validated using coppa?
				// 1048576 (login_anonymous) is deprecated
				// 2097152 (login_anonymous_online) is deprecated
				// 4194304 (members_auto_dst) is deprecated
				// 8388608 (members_created_remote) is deprecated
				// 16777216 (members_disable_pm) is deprecated
				'unacknowledged_warnings'		=> 33554432,	// 1 means the member has at least one warning they have not acknowledged. 0 means they have none.
				// 67108864 (pp_setting_moderate_comments) is deprecated and replaced with global setting
				'pp_setting_moderate_followers'	=> 134217728,	// Previously pp_setting_moderate_friends. Replaced with setting that toggles whether or not member can be followed. 
				'pp_setting_count_visitors'		=> 268435456,	// If TRUE, last 5 visitors will be shown on profile
				// 536870912 (timezone_override) is deprecated
				'validating'					=> 1073741824,	// If TRUE user is validating and should have a corresponding row in core_validating
			),
			'members_bitoptions2'	=> array(
				'show_pm_popup'					=> 1, // "Show pop-up when I have a new message"
				'remove_gallery_access'			=> 2, // Remove access to Gallery
				'remove_gallery_upload'			=> 4, // Remove permission to upload images in Gallery
				'no_report_count'				=> 8, // 1 means the report count will not show
				'has_no_ignored_users'			=> 16, // If we know the user has no ignored users, we don't have to query for them
				'must_reaccept_privacy'			=> 32, // 1 means the member needs to re-accept the privacy policy
				'must_reaccept_terms'			=> 64, // 1 means the member needs to re-accept the registration terms
				'email_notifications_once'      => 128, // 1 means the member only wants one email per notification item until they revisit the community
				'disable_notification_sounds'	=> 256, /** @deprecated Notification sounds are no longer supported by default */
				'has_security_answers'			=> 512,
				'security_questions_opt_out'	=> 1024,
				'ignore_promotions'				=> 2048,// 1 means that the user will not be checked against group promotion rules. Set by Commerce to prevent "promoting" users incorrectly after a purchase.
				'profile_completed'				=> 4096,
				'profile_completion_dismissed'	=> 8192,
				'created_externally'			=> 16384,
				'is_support_account'			=> 32768, // Created by ACP support tool
				'is_anon'						=> 65536, // Has chosen to log in anonymously
				// 131072 (has_new_acp_features) is deprecated
				'password_reset_forced'			=> 262144, // A password reset has been forced
				'link_pref_unread'				=> 524288, // Take me to comments I haven't read
				'link_pref_last'				=> 1048576, // Take me to the latest comment
				'mobile_notifications'			=> 2097152, // Has the user has enabled push notifications on at least 1 mobile device?
				'link_pref_first'			    => 4194304, // Take me to the first comment
				'datalayer_event_fired'		    => 8388608,
				'new_device_email'				=> 16777216, // Send an email when a new device is used to log in.
				'no_solved_reenage'				=> 33554432, // User does not want to get topic  re-engagement emails
				'datalayer_pii_optout'			=> 67108864, // User has opted in to having their PII collected by datalayer
				'email_messages_bounce'			=> 134217728, // User's email keeps returning hard bounces so they need to change it
				// 268435456 - cookie_optout moved to cookie preferences
				'expert_user_disabled'				=> 536870912, // User does not want to be a community expert
				'expert_user_blocked'			    => 1073741824 // Admin has blocked expert user
			)
		)
	);

	/**
	 * Member Changes to skip for the member_edited webhook
	 *
	 * @var string[]
	 */
	public static array $changesToSkipForMemberEditWebhooks = [
		'last_visit',
		'last_activity',
		'profilesync_lastsync'
	];
	
	/* !Follow */
	
	/**
	 * @brief	Cached logged in member
	 */
	public static ?Member $loggedInMember	= NULL;
	
	/**
	 * @brief	If we change the photo_type, then we need to record the previous photo type to determine if set_pp_main_photo should attempt removal of existing images
	 */
	protected ?string $_previousPhotoType = NULL;

	/**
	 * Get logged in member
	 *
	 * @return    Member|null
	 */
	public static function loggedIn(): ?Member
	{
		/* If we haven't loaded the member yet, or if the session member has changed since we last loaded the member, reload and cache */
		if( static::$loggedInMember === NULL )
		{
			if( Request::isCliEnvironment() )
			{
				static::$loggedInMember = new Member;
			}
			else
			{
				static::$loggedInMember = Session::i()->member;
			}
			
			if ( isset( $_SESSION['logged_in_as_key'] ) )
			{
				if ( static::$loggedInMember->isAdmin() AND static::$loggedInMember->hasAcpRestriction( 'core', 'members', 'member_login' ) )
				{
					$key = $_SESSION['logged_in_as_key'];
	
					if ( isset( Store::i()->$key ) )
					{
						static::$loggedInMember	= static::load( Store::i()->$key );
						
						if ( !static::$loggedInMember->member_id )
						{
							unset( Store::i()->$key );
							unset( $_SESSION['logged_in_as_key'] );
						}
					}
				}
			}
			
			if ( !static::$loggedInMember->member_id and isset( Request::i()->cookie['ipsTimezone'] ) )
			{
				/* Check for valid timezone identifier */
				$tz = DateTime::getFixedTimezone( Request::i()->cookie['ipsTimezone'] );
				if( in_array( $tz, DateTimeZone::listIdentifiers() ) )
				{
					static::$loggedInMember->timezone = $tz;
				}
			}
		}

		return static::$loggedInMember;
	}

	/**
	 * Load Record
	 * We override it so we return a guest object for a non-existant member
	 *
	 * @param int|string|null	$id					ID
	 * @param	string|null		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause (see \IPS\Db::build for details)
	 * @return	static|ActiveRecord
	 *@see		Db::build
	 */
	public static function load( int|string|null $id, string $idField=NULL, mixed $extraWhereClause=NULL ): ActiveRecord|static
	{
		try
		{
			if( $id === NULL OR $id === 0 OR $id === '' )
			{
				$classname = get_called_class();
				return new $classname;
			}
			else
			{
				$member = parent::load( $id, $idField, $extraWhereClause );
				
				if ( $member->restrict_post > 0 and $member->restrict_post <= time() )
				{
					$member->restrict_post = 0;
					$member->save();
				}

				return $member;
			}
		}
		catch ( OutOfRangeException $e )
		{
			$classname = get_called_class();
			return new $classname;
		}
	}
	
	/**
	 * Load record based on a URL
	 *
	 * @param	Url	$url	URL to load from
	 * @return	mixed
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): mixed
	{
		try
		{
			$member = parent::loadFromUrl( $url );
		}
		catch( InvalidArgumentException $e )
		{
			throw new OutOfRangeException;
		}

		if ( !$member->member_id )
		{
			throw new OutOfRangeException;
		}
		return $member;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		/* If we're in the installer - don't do this */
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'setup' )
		{
			return;
		}

		$this->member_group_id		= Settings::i()->guest_group;
		$this->mgroup_others		= '';
		$this->joined				= time();
		$this->ip_address			= Request::i()->ipAddress();
		$this->timezone				= 'UTC';
		$this->allow_admin_mails	= ( Settings::i()->updates_consent_default == 'enabled' );
		$this->pp_photo_type        = '';
		$this->member_posts 		= 0;
		$this->last_visit			= NULL;
		$this->_data['pp_main_photo'] = NULL;
		$this->_data['pp_thumb_photo'] = NULL;
		$this->_data['failed_logins'] = NULL;
		$this->_data['mfa_details'] = NULL;
		$this->_data['pp_reputation_points'] = 0;
		$this->_data['signature'] = '';

		/* We need to enable the view_sigs bit if we want to show signatures to guests */
		if ( Settings::i()->signatures_guests )
		{
			$this->members_bitoptions['view_sigs'] = TRUE;
		}

		$this->members_bitoptions['new_device_email'] = TRUE;
		$this->members_bitoptions['show_pm_popup'] = (bool)Settings::i()->notification_prefs_popup;
		$this->members_bitoptions['email_notifications_once'] = (bool)Settings::i()->notification_prefs_one_per_view;

		$this->_data['auto_track']	= json_encode( array(
			'content'	=> Settings::i()->auto_follow_new_content ? 1 : 0,
			'comments'	=> Settings::i()->auto_follow_replied_to ? 1 : 0,
			'method'	=> 'immediate'
		)	);

		if( isset( Request::i()->cookie['language'] ) AND Request::i()->cookie['language'] )
		{
			$this->language	= Request::i()->cookie['language'];
		}
		
		if( isset( Request::i()->cookie['theme'] ) AND Request::i()->cookie['theme'] )
		{
			$this->skin	= Request::i()->cookie['theme'];
		}
	}

	/**
	 * Allow optional cookies
	 *
	 * @param   bool    $allow
	 * @return  bool
	 */
	public function setAllowOptionalCookies( bool $allow = TRUE ): bool
	{
		$expire = ( new DateTime )->add( new DateInterval('P6M' ) );
		Request::i()->setCookie( 'cookie_consent', 1, $expire, FALSE );

		if ( !$allow )
		{
			/* Remove optional cookies */
			foreach ( Request::i()->cookie as $cookieName => $value )
			{
				if ( !in_array( $cookieName, Request::i()->getEssentialCookies() ) )
				{
					Request::i()->setCookie( $cookieName, null );
				}
			}

			Request::i()->setCookie( 'cookie_consent_optional', NULL );
		}
		else
		{
			Request::i()->setCookie( 'cookie_consent_optional', 1, $expire, FALSE );
		}

		return $allow;
	}

	/**
	 * Are optional cookies allowed?
	 *
	 * @return  bool
	 */
	public function get_optionalCookiesAllowed(): bool
	{
		return isset( Request::i()->cookie[ 'cookie_consent' ] ) AND isset( Request::i()->cookie[ 'cookie_consent_optional' ] );
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @param bool $setAuthorToGuest	Sets the author id of all content to 0 ( guest )
	 * @param bool $keepAuthorName		Keeps the author name
	 * @return void
	 */
	public function delete( bool $setAuthorToGuest = TRUE, bool $keepAuthorName = TRUE ): void
	{
		/* Is this a guest object? */
		if( !$this->member_id )
		{
			Log::log( [ $this->name, $this->email ], 'guest_delete_attempt' );
			return;
		}

        if( $setAuthorToGuest )
        {
            /* Clean up content - set to member ID 0 - We check $setAuthorToGuest because of member merging.
            As the member is immediately deleted we do not want to compete with the existing merge in progress. */
            $this->hideOrDeleteAllContent( 'merge', array( 'merge_with_id' => 0, 'merge_with_name' => $keepAuthorName  ?  $this->name : '' ) );
        }

		/* Let apps do their stuff */
		Event::fire( 'onDelete', $this );
		
		/* Actually delete from database */
		parent::delete();
		
		/* We may have deleted a member waiting validation */
		if ( !Db::i()->select( 'COUNT(*)', 'core_validating', array( 'user_verified=?', TRUE ) )->first() )
		{
			AdminNotification::remove( 'core', 'NewRegValidate' );
		}
		if ( $latestReg = Db::i()->select( 'joined', 'core_members',  array( 'email != ?', '' ), 'joined DESC', 1 )->first() )
		{
			AdminNotification::remove( 'core', 'NewRegComplete', NULL, DateTime::ts( $latestReg ) );
		}
		else
		{
			AdminNotification::remove( 'core', 'NewRegComplete' );
		}
		if ( $this->members_bitoptions['is_support_account'] )
		{
			AdminNotification::remove( 'core', 'ConfigurationError', "supportAdmin-{$this->member_id}" );
		}

		/* Delete from member map */
		Db::i()->delete( 'core_item_member_map', array( 'map_member_id=?', $this->member_id ) );

		/* Reset statistics */
		Widget::deleteCaches();
	}

	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return    void
	 * @note	We have to be careful when upgrading in case we are coming from an older version
	 */
	public function save(): void
	{
		if ( $this->member_id AND ( !Dispatcher::hasInstance() OR Dispatcher::i()->controllerLocation != 'setup' ) )
		{
			$this->checkGroupPromotion();
		}

		$new		= $this->_new;
		$changes	= $this->changed;

		/* If we don't have a name or email address, account is not 'complete' */
		/* @note The completed column is added in the 4.4.0 Beta 1 routine, so we need to verify the upgrade has been performed otherwise
			the auto-upgrader can replace this file before the column is added, and an SQL error can prevent the admin from completing the
			auto-upgrade process */
		$fireRegistrationCompletedWebhook = FALSE;
		if ( Application::load( 'core' )->long_version > 104000 )
		{
			if( $this->completed AND ( !$this->name OR !$this->email ) )
			{
				$this->completed = FALSE;
			}
			/* Conversely if it's flagged incomplete but we have all the details, change the flag */
			elseif( !$this->completed AND $this->name AND $this->email )
			{
				$this->completed = TRUE;
				$fireRegistrationCompletedWebhook = TRUE;
			}
		}

		/* If the email was changed, make sure that the bounce flag is unset so the member no longer sees the error message */
		if ( isset( $changes['email'] ) )
		{
			$this->members_bitoptions['email_messages_bounce'] = 0;
		}

		parent::save();

		if ( $new )
		{
			/* Profile Fields */
			Db::i()->insert( 'core_pfields_content', array( 'member_id' => $this->member_id ), TRUE );

			Event::fire( 'onCreateAccount', $this );
		}
		else
		{
			if ( $fireRegistrationCompletedWebhook )
			{
				Webhook::fire( 'member_registration_complete', $this, $this->webhookFilters() );
			}

			/* Run member sync, but not if the only change is the last_activity timestamp */
			if( count( $this->changedCustomFields ) > 0 OR ( count( $changes ) > 0 AND !( count( $changes ) === 1 AND isset( $changes['last_activity'] ) ) ) )
			{
				Event::fire( 'onProfileUpdate', $this, array( 'changes' => array_merge( $changes, $this->changedCustomFields ) ) );
			}
		}

		/* If we have updated custom fields, make sure we don't have any cached in the class - just wipe out cache so we will refetch if needed */
		if( count( $this->changedCustomFields ) )
		{
			$this->rawProfileFieldsData	= NULL;
			$this->profileFields		= NULL;
		}

		/* Remove pending validation entries if our email address has changed */
		if( !$new AND isset( $changes['email'] ) )
		{
			/* Delete any pending validation emails */
			Db::i()->delete( 'core_validating', array( 'member_id=? AND email_chg=1', $this->member_id ) );

			/* Delete any pending password reset emails */
			Db::i()->delete( 'core_validating', array( 'member_id=? AND lost_pass=1', $this->member_id ) );
		}
	}
	
	/* !Getters/Setters Data */
	
	/**
	 * Group Data, taking into consideration secondary groups
	 */
	public ?array $_group = NULL;
	
	/**
	 * @brief	Admin CP Restrictions
	 */
	protected mixed $restrictions = NULL;
	
	/**
	 * @brief	Moderator Permissions
	 */
	protected mixed $modPermissions = NULL;
	
	/**
	 * @brief	Calculated language ID
	 */
	protected ?int $calculatedLanguageId = NULL;
	
	/**
	 * @brief	Marker Cache
	 */
	public array $markers = array();

	/**
	 * @brief	Markers reset times
	 */
	public array $markersResetTimes = array();

	/**
	 * @brief	We have fetched markers already
	 */
	public bool $haveAllMarkers = FALSE;
	
	/**
	 * @brief	Default stream ID
	 */
	protected mixed $defaultStreamId = FALSE;
	
	/**
	 * @brief	Keep track of any changed profile fields
	 */
	public array $changedCustomFields = array();

	/**
	 * Get name, do not return "guest" name if not set
	 *
	 * @return	string
	 */
	public function get_real_name(): string
	{
		return ( isset( $this->_data['name'] ) ) ? $this->_data['name'] : '';
	}

	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function get_name(): string
	{
		if( !isset( $this->_data['name'] ) )
		{
			return Member::loggedIn()->language()->addToStack('guest');
		}
		
		return $this->member_id ? $this->_data['name'] : Member::loggedIn()->language()->addToStack( 'guest_name_shown', FALSE, array( 'sprintf' => array( $this->_data['name'] ) ) );
	}
	
	/**
	 * Get name, loading the guest_name_shown language string directly if needed
	 *
	 * @return	string
	 */
	public function get__name(): string
	{
		if ( !isset( $this->_data['name'] ) )
		{
			return Member::loggedIn()->language()->get('guest');
		}
		
		return $this->member_id ? $this->_data['name'] : sprintf( Member::loggedIn()->language()->get('guest_name_shown'), $this->_data['name'] );
	}

	/**
	 * @brief	Previous name - stored temporarily for display name history log
	 */
	protected ?string $previousName	= NULL;

	/**
	 * Set name
	 *
	 * @param string $value	Value
	 * @return	void
	 */
	public function set_name( string $value ) : void
	{
		if( isset( $this->_data['name'] ) )
		{
			$this->previousName				= $this->_data['name'];
		}

		$this->_data['name']				= $value;
		$this->_data['members_seo_name']	= Friendly::seoTitle( $value );
	}

	/**
	 * Set group
	 *
	 * @param int $value	Value
	 * @return	void
	 */
	public function set_member_group_id( int $value ) : void
	{
		$this->_data['member_group_id'] = $value;
		$this->_group = NULL;
	}
	
	/**
	 * Set Secondary Groups
	 *
	 * @param string $value	Value
	 * @return	void
	 */
	public function set_mgroup_others( string $value ) : void
	{
		$groups = array_filter( explode( ",", $value ) );
		if ( in_array( Settings::i()->guest_group, $groups ) )
		{
			throw new InvalidArgumentException;
		}
				
		$this->_data['mgroup_others'] = implode( ',', $groups );
		$this->_group = NULL;
	}
		
	/**
	 * Flag as spammer
	 *
	 * @return	void
	 */
	public function flagAsSpammer() : void
	{
		if ( !$this->members_bitoptions['bw_is_spammer'] )
		{
			$actions = explode( ',', Settings::i()->spm_option );
						
			/* Hide or delete */
			if ( in_array( 'unapprove', $actions ) or in_array( 'delete', $actions ) )
			{
				/* Send to queue */
				$this->hideOrDeleteAllContent( in_array( 'delete', $actions ) ? 'delete' : 'hide' );
				
				/* Clear out their profile */
				if ( in_array( 'delete', $actions ) )
				{
					$this->signature		= '';
					$this->pp_main_photo	= NULL;
					$this->pp_photo_type	= NULL;

					$this->coverPhoto()->delete();
					$this->pp_cover_photo	= '';
					$this->pp_cover_offset	= 0;
					
					Db::i()->delete( 'core_pfields_content', array( 'member_id=?', $this->member_id ) );
				}
			}
			
			/* Restrict from posting or ban */
			if ( in_array( 'disable', $actions ) or in_array( 'ban', $actions ) )
			{
				if ( in_array( 'ban', $actions ) )
				{
					$this->temp_ban = -1;
				}
				else
				{
					$this->restrict_post = -1;
					$this->members_disable_pm = 2;
				}
			}
									
			/* Save */
			$this->members_bitoptions['bw_is_spammer'] = TRUE;
			$this->save();

			/* Run sync */
			$this->logHistory( 'core', 'account', array( 'type' => 'spammer', 'set' => TRUE, 'actions' => $actions ) );
			Event::fire( 'onSetAsSpammer', $this );
			
			/* Notify admin */
			AdminNotification::send( 'core', 'Spammer', NULL, TRUE, $this->member_id, Member::loggedIn() );
			
			/* Feedback to Spam Monitoring Service */
			if ( Settings::i()->spam_service_enabled and Settings::i()->spam_service_send_to_ips )
			{
				$this->spamService( 'markspam' );
			}
		}
	}
	
	/**
	 * Hide/Delete All Content
	 *
	 * @param string $action	'hide' or 'delete' or 'merge'
	 * @param array $extra	Extra data needed by the MemberContent plugin
	 * @return	void
	 */
	public function hideOrDeleteAllContent( string $action, array $extra=array() ) : void
	{
		/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
		Widget::deleteCaches();

		/* Send to the queue, include archived content */
		foreach ( Content::routedClasses( FALSE, TRUE, FALSE ) as $class )
		{
			if ( isset( $class::$databaseColumnMap['author'] ) and ( $action == 'delete' or IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) ) )
			{
				/* Comments run first when merging so rebuilding topic doesn't fail with incorrect author ID */
				switch( $action )
				{
					case 'hide':
					case 'delete':
						$order = 1;
						break;
					case 'merge':
						$order = ( !is_subclass_of( $class, '\IPS\Content\Comment' ) ) ? 3 : 2;
						break;
				}

				Task::queue( 'core', 'MemberContent', array_merge( array( 'initiated_by_member_id' => Member::loggedIn()->member_id, 'member_id' => $this->member_id, 'name' => $this->name, 'class' => $class, 'action' => $action ), $extra ), $order );
			}
		}

		/* And private messages */
		Task::queue( 'core', 'MemberContent', array_merge( array( 'initiated_by_member_id' => Member::loggedIn()->member_id, 'member_id' => $this->member_id, 'name' => $this->name, 'class' => 'IPS\\core\\Messenger\\Conversation', 'action' => $action ), $extra ), 2 );
	}
	
	/**
	 * Unflag as spammer
	 *
	 * @return	void
	 */
	public function unflagAsSpammer() : void
	{
		if ( $this->members_bitoptions['bw_is_spammer'] )
		{
			/* Save */
			$this->members_bitoptions['bw_is_spammer'] = FALSE;
			$this->save();
			
			/* Log */
			$this->logHistory( 'core', 'account', array( 'type' => 'spammer', 'set' => FALSE ) );

			/* Remove any pending hide or delete content queued tasks */
			foreach(Db::i()->select( '*', 'core_queue', array( '`key`=?', 'MemberContent' ) ) as $task )
			{
				$data = json_decode( $task['data'], true );

				if( $data['member_id'] == $this->member_id )
				{
					Db::i()->delete( 'core_queue', array( 'id=?', $task['id'] ) );
				}
			}
			
			/* Report back to spam service */
			if ( Settings::i()->spam_service_enabled and Settings::i()->spam_service_send_to_ips )
			{
				$this->spamService( 'notspam' );
			}

			/* Run sync */
			Event::fire( 'onUnSetAsSpammer', $this );
		}
	}

	/**
	 * Get auto-track data
	 *
	 * @return	array
	 */
	public function get_auto_follow(): array
	{
		return ( mb_substr( $this->_data['auto_track'], 0, 1 ) !== '{' ) ?
			array( 'method' => 'immediate', 'content' => 0, 'comments' => (int) $this->_data['auto_track'] ) :
			json_decode( $this->_data['auto_track'], TRUE );
	}
	
	/**
	 * Set banned
	 *
	 * @param string $value	Value
	 * @return	void
	 */
	public function set_temp_ban( string $value ) : void
	{
		$this->_data['temp_ban'] = $value;
		if ( $value == -1 )
		{
			Db::i()->delete( 'core_validating', array( 'member_id=?', $this->member_id ) );
			
			if ( !Db::i()->select( 'COUNT(*)', 'core_validating', array( 'user_verified=?', TRUE ) )->first() )
			{
				AdminNotification::remove( 'core', 'NewRegValidate' );
			}
		}
		else
		{
			$this->members_bitoptions['validating'] = FALSE;
		}

		/* Revoke oAuth tokens */
		if( $value )
		{
			Db::i()->update( 'core_oauth_server_access_tokens', array( 'status' => 'revoked' ), array( 'member_id=?', $this->member_id ) );
		}
	}

	/**
	 * Get Group Data
	 *
	 * @return array|null
	 */
	public function get_group(): ?array
	{
		if ( $this->_group === NULL )
		{
			/* Load primary group */
			try
			{
				$group = Group::load( $this->_data['member_group_id'] );
			}
			catch ( OutOfRangeException $e )
			{
				$group = Group::load( Settings::i()->member_group );
			}
			
			$this->_group = array_merge( $group->data(), $group->g_bitoptions->asArray() );

			/* Merge in secondary group data */
			if ( !empty( $this->_data['mgroup_others'] ) )
			{
				$groups			= array_filter( explode( ',', $this->_data['mgroup_others'] ) );
				$exclude		= array();
				$lessIsMore		= array();
				$neg1IsBest		= array();
				$zeroIsBest		= array();
				$callback		= array();
	
				/* Get the limits we need to work out from apps */
				foreach (Application::allExtensions( 'core', 'GroupLimits', FALSE, 'core' ) as $key => $extension )
				{
					$appLimits = $extension->getLimits();

					if( !empty( $appLimits[ 'neg1IsBest' ] ) )
					{
						$neg1IsBest = array_merge( $neg1IsBest, $appLimits[ 'neg1IsBest' ] );
					}

					if( !empty( $appLimits[ 'zeroIsBest' ] ) )
					{
						$zeroIsBest = array_merge( $zeroIsBest, $appLimits[ 'zeroIsBest' ] );
					}

					if( !empty( $appLimits[ 'lessIsMore' ] ) )
					{
						$lessIsMore = array_merge( $lessIsMore, $appLimits[ 'lessIsMore' ] );
					}

					if( !empty( $appLimits[ 'exclude' ] ) )
					{
						$exclude = array_merge( $exclude, $appLimits[ 'exclude' ] );
					}

					if( !empty( $appLimits[ 'callback' ] ) )
					{
						$callback = array_merge( $callback, $appLimits[ 'callback' ] );
					}
				}
				
				/* Do the merging */
				$skippedGroups	= array();
	
				foreach( $groups as $gid )
				{
					try
					{
						$group = Group::load( $gid );
					}
					catch( OutOfRangeException $e )
					{
						$skippedGroups[]	= $gid;
						continue;
					}
	
					$_data = array_merge( $group->_data, $group->g_bitoptions->asArray() );
	
					foreach( $_data as $k => $v )
					{
						if ( ! in_array( $k, $exclude ) )
						{
							if ( in_array( $k, $zeroIsBest ) )
							{
								if ( empty( $this->_group[ $k ] ) )
								{
									continue;
								}
								else if( $v == 0 )
								{
									$this->_group[ $k ] = 0;
								}
								else if ( $v > $this->_group[ $k ] )
								{
									$this->_group[ $k ] = $v;
								}
							}
							else if( in_array( $k, $neg1IsBest ) )
							{
								
								if ( $this->_group[ $k ] == -1 )
								{
									continue;
								}
								else if( $v == -1 )
								{
									$this->_group[ $k ] = -1;
								}
								else if ( $v > $this->_group[ $k ] )
								{
									$this->_group[ $k ] = $v;
								}
							}
							else if ( in_array( $k, $lessIsMore ) )
							{
								if ( $v < $this->_group[ $k ] )
								{
									$this->_group[ $k ] = $v;
								}
							}
							else if ( array_key_exists( $k, $callback ) )
							{
								$callbackFunction = $callback[ $k ];
								$result = $callbackFunction( $this->_group, $_data, $k, $this->_data );
	
								if( is_array( $result ) )
								{
									$this->_group	= array_merge( $this->_group, $result );
								}
								else if( $result !== NULL )
								{
									$this->_group[ $k ]	= $result;
								}
							}
							else
							{
								if ( !isset( $this->_group[ $k ] ) OR $v > $this->_group[ $k ] )
								{
									$this->_group[ $k ] = $v;
								}
							}
						}
					}
				}
	
				if( count( $skippedGroups ) )
				{
					$this->mgroup_others = implode( ',', array_diff( $groups, $skippedGroups ) );
					
					parent::save();
				}
			}
		}

		return $this->_group;
	}

	/**
	 * Retrieve the group name
	 *
	 * @return string
	 */
	public function get_groupName(): string
	{
		if ( $this->_group === NULL )
		{
			$group = $this->group;
		}

		if( $this->_data['member_group_id'] )
		{
			$group = Group::load( $this->_data['member_group_id'] );
			$this->_group['name'] = $group->formatName( Member::loggedIn()->language()->addToStack( "core_group_{$group->g_id}" ) );
		}

		return $this->_group['name'];
	}

	/**
	 * @brief	Cached groups check
	 */
	protected ?array $_groups = NULL;

	/**
	 * Get an array of the group IDs (including secondary groups) this member belongs to
	 *
	 * @return array|null
	 */
	public function get_groups(): ?array
	{
		if ( $this->_groups !== NULL )
		{
			return $this->_groups;
		}
		
		$this->_groups = array( $this->_data['member_group_id'] );

		if( $this->_data['mgroup_others'] )
		{
			foreach( explode( ',', $this->_data['mgroup_others'] ) as $id )
			{
				$this->_groups[] = intval( $id );
			}
		}
		
		/* Sort for consistency when using permissions as part of a cache key */
		sort( $this->_groups, SORT_NUMERIC );
	
		return $this->_groups;
	}
	
	/**
	 * Social Groups
	 */
	protected ?array $_socialGroups = NULL;
	
	/**
	 * Social Groups
	 *
	 * @param bool $bypassCache	Fetch directly from the database
	 * @return	array|null
	 */
	public function socialGroups( bool $bypassCache=FALSE ): ?array
	{
		if ( $this->_socialGroups === NULL OR $bypassCache )
		{
			/* If this is a guest, they will not have any social groups - save the query */
			if( !$this->member_id )
			{
				$this->_socialGroups = array();
			}
			else
			{
				if( $bypassCache )
				{
					$this->_socialGroups = iterator_to_array( Db::i()
						->select( 'group_id', 'core_sys_social_group_members', array( 'member_id=?', $this->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )
						->setKeyField( 'group_id' )
						->setValueField( 'group_id' ) );
				}
				else
				{
					$this->_socialGroups = array();

					foreach( $this->permissionArray() as $permissionRow )
					{
						if( mb_substr( $permissionRow, 0, 1 ) === 's' )
						{
							$groupId = (int) mb_substr( $permissionRow, 1 );

							$this->_socialGroups[ $groupId ] = $groupId;
						}
					}
				}
			}
		}
		return $this->_socialGroups;
	}
	
	/**
	 * Clubs
	 */
	protected ?array $_clubs = array();
	
	/**
	 * Clubs
	 *
	 * @param bool $fromWriteServer	If Read/Write separation is enabled, this flag can be used to force reading from the write server, which can be used when rebuilding cached permission strings
	 * @param bool $moderatorOnly	If true will only return clubs the member is a modertor of
	 * @return	array
	 */
	public function clubs( bool $fromWriteServer=FALSE, bool $moderatorOnly=FALSE ): array
	{
		if ( !Settings::i()->clubs )
		{
			return array();
		}
		if ( !isset( $this->_clubs[ intval( $moderatorOnly ) ] ) )
		{
			/* If this is a guest, they will not have any clubs - save the query */
			if( !$this->member_id )
			{
				$this->_clubs[ intval( $moderatorOnly ) ] = array();
			}
			else
			{
				$statuses = $moderatorOnly ? ( Club::STATUS_MODERATOR . "','" . Club::STATUS_LEADER ) : ( Club::STATUS_MEMBER . "','" . Club::STATUS_MODERATOR . "','" . Club::STATUS_LEADER ) ;
				
				$this->_clubs[ intval( $moderatorOnly ) ] = iterator_to_array( Db::i()->select( 'club_id', 'core_clubs_memberships', array( "member_id=? AND status IN('" . $statuses . "')", $this->member_id ), NULL, NULL, NULL, NULL, $fromWriteServer ? Db::SELECT_FROM_WRITE_SERVER : 0 ) );
			}
		}
		return $this->_clubs[ intval( $moderatorOnly ) ];
	}
	
	/**
	 * Permission Array
	 *
	 * @return	array
	 */
	public function permissionArray(): array
	{
		$return = $this->groups;

		if ( $this->member_id )
		{
			$return[] = "m{$this->member_id}";

			if ( Settings::i()->clubs )
			{
				$return[] = "ca"; // Public clubs, which is everyone except guests
			}

			if ( $this->modPermission('can_access_all_clubs') )
			{
				$return[] = 'cm';
			}

			if ( $this->permission_array === NULL )
			{			
				$this->rebuildPermissionArray();
			}
			
			if( $this->permission_array )
			{
				$return = array_merge( $return, explode( ',', $this->permission_array ) );
			}
		}

		return $return;
	}
	
	/**
	 * Permission Array
	 *
	 * @return	void
	 */
	public function rebuildPermissionArray() : void
	{
		$permissionArray = array();
		foreach ($this->socialGroups(TRUE) as $socialGroupId )
		{
			$permissionArray[] = "s{$socialGroupId}";
		}
		if ( Settings::i()->clubs )
		{
			/* Wipe club cache as when we are added, perm is rebuilt but the new club membership is not detected as we are using cached value */
			$this->_clubs = NULL;
			
			$clubs = $this->clubs(TRUE);
			if ( $clubs )
			{
				foreach ( $clubs as $clubId )
				{
					$permissionArray[] = "c{$clubId}";
				}
				foreach ($this->clubs(TRUE, TRUE) as $clubId )
				{
					$permissionArray[] = "cm{$clubId}";
				}
			}
		}
	
		$this->permission_array = implode( ',', $permissionArray );
		$this->save();
	}
	
	/**
	 * Get Joined Date
	 *
	 * @return    DateTime
	 */
	public function get_joined(): DateTime
	{
		return DateTime::ts( $this->_data['joined'] );
	}
	
	/**
	 * Get SEO Name
	 *
	 * @return	string|null
	 */
	public function get_members_seo_name(): ?string
	{
		/* Set it so it will be saved */
		if( !isset( $this->_data['members_seo_name'] ) or !$this->_data['members_seo_name'] )
		{
			if ( !$this->name )
			{
				return NULL;
			}
			
			$this->members_seo_name	= Friendly::seoTitle( $this->name );
		}

		return $this->_data['members_seo_name'] ?: Friendly::seoTitle( $this->name );
	}

	/**
	 * Get localized birthday, taking into account optional year
	 *
	 * @return	string|null
	 */
	public function get_birthday(): ?string
	{
		try
		{
			if( $this->_data['bday_year'] )
			{
				$date	= new DateTime( str_pad( $this->_data['bday_year'], 4, 0, STR_PAD_LEFT ) . str_pad( $this->_data['bday_month'], 2, 0, STR_PAD_LEFT ) . str_pad( $this->_data['bday_day'], 2, 0, STR_PAD_LEFT ) );

				return $date->fullYearLocaleDate();
			}
			else if( $this->_data['bday_month'] )
			{
				$date	= new DateTime( $this->_data['bday_month'] . '/' . $this->_data['bday_day'] );

				return $date->dayAndMonth();
			}
			else
			{
				return NULL;
			}
		}
		catch ( Exception $e )
		{
			Log::debug( "Member " . $this->id ." has a not valid birthday date" , 'birthday_error' );
			return NULL;
		}
	}

	/**
	 * Get the member's age
	 *
	 * @param DateTime|null $date	If supplied, birthday is calculated from this point
	 * @note	If the member has not specified a birth year (which is optional), NULL is returned
	 * @return	int|null
	 */
	public function age( DateTime $date=NULL ): ?int
	{
		if( $this->_data['bday_year'] AND checkdate( $this->_data['bday_month'], $this->_data['bday_day'], $this->_data['bday_year'] ) )
		{
			/* We use dashes because DateTime accepts two digit years with it */
			$birthday	= new DateTime( $this->_data['bday_year'] . '-' . $this->_data['bday_month'] . '-' . $this->_data['bday_day'] );
			$birthday->setTime( 0, 0, 1 );

			$today = $date ? new DateTime( $date->format('Y') . '-' . $date->format('m') . '-' . $date->format('d') ) : new DateTime();

			if( Member::loggedIn()->timezone )
			{
				$today->setTimezone( new DateTimeZone( Member::loggedIn()->timezone ) );
			}

			$today->setTime( 23, 59, 59 ); // We want how old they'll be at the end of the provided date

			return $birthday->diff( $today )->y;
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * User's photo URL
	 *
	 * @param bool $thumb Use thumbnail?
	 * @param bool $email Is the photo going to be used in an email?
	 * @return string|null
	 */
	public function get_photo( bool $thumb=TRUE, bool $email=FALSE ): ?string
	{
		return static::photoUrl($this->_data, $thumb, $email);
	}

	/**
	 * Set Photo Type
	 *
	 * @param string|null $type	Photo type
	 * @return	void
	 */
	public function set_pp_photo_type( ?string $type ) : void
	{
		if ( $this->_previousPhotoType === NULL and isset( $this->_data['pp_photo_type'] ) )
		{
			$this->_previousPhotoType = $this->_data['pp_photo_type'];
		}
		$this->_data['pp_photo_type'] = $type;
	}
	
	/**
	 * Set Photo
	 *
	 * @param string|null $photo	Photo location
	 * @return	void
	 */
	public function set_pp_main_photo( ?string $photo ) : void
	{
		$this->deletePhoto();
		
		$this->_data['pp_main_photo'] = $photo;
	}

	/**
	 * Delete the profile photo
	 *
	 * @return void
	 */
	public function deletePhoto() : void
	{
		/* It is common to update pp_photo_type before pp_main_photo */
		$photoType = ( $this->_previousPhotoType !== NULL ) ? $this->_previousPhotoType : $this->_data['pp_photo_type'];
		
		/* Attempt to delete existing images if they are from a profile sync, or uploaded/imported from URL */
		if ( mb_substr( $photoType, 0, 5 ) === 'sync-' or $photoType === 'custom' or $photoType === 'letter' )
		{
			if ( $this->_data['pp_main_photo'] )
			{
				try
				{
					File::get( 'core_Profile', $this->_data['pp_main_photo'] )->delete();
				}
				catch ( Exception $e ) {}
			}
			if ( $this->_data['pp_thumb_photo'] )
			{
				try
				{
					File::get( 'core_Profile', $this->_data['pp_thumb_photo'] )->delete();
					$this->_data['pp_thumb_photo'] = NULL;
				}
				catch ( Exception $e ) {}
			}
		}
	}
	
	/**
	 * Get reputation points
	 *
	 * @return	int
	 */
	public function get_pp_reputation_points(): int
	{
		return isset( $this->_data['pp_reputation_points'] ) ? (int) $this->_data['pp_reputation_points'] : 0;
	}
	
	/**
	 * Get warning points
	 *
	 * @return	int
	 */
	public function get_warn_level(): int
	{
		return isset( $this->_data['warn_level'] ) ? (int) $this->_data['warn_level'] : 0;
	}

	/**
	 * Get member title
	 *
	 * @return		array
	 * @deprecated	Use rank() instead
	 */
	public function get_rank(): array
	{
		if ( $rank = $this->rank() )
		{
			return [ 'title' => $rank->_title, 'image' => $rank->html() ];
		}
		return [ 'title' => NULL, 'image' => NULL ];
	}
	
	/**
	 * Get member location
	 *
	 * @return	string|null
	 */
	public function get_location(): ?string
	{
		return $this->location();
	}
	
	/**
	 * Get members posts for today
	 *
	 * @return	array
	 */
	public function get_members_day_posts(): array
	{
		return explode( ',', $this->_data['members_day_posts'] );
	}
	
	/**
	 * Set members posts for today
	 *
	 * @param array $value	Array of daily post data. Index 0 is the amount of posts posted in this time period, and optional index 1 is a timestamp of when we started counting
	 * @return	void
	 */
	public function set_members_day_posts(array $value ) : void
	{
		/* Are we updating time? */
		if ( ! isset( $value[1] ) )
		{
			$value[1] = $this->members_day_posts[1];
		}
		
		$this->_data['members_day_posts'] = implode( ',', $value );
	}
	
	/**
	 * Get member's default stream
	 *
	 * @return	int|null
	 */
	public function get_defaultStream(): ?int
	{
		if ( $this->defaultStreamId === FALSE )
		{
			if ( $this->member_streams and $streams = json_decode( $this->member_streams, TRUE ) and count( $streams ) )
			{
				$this->defaultStreamId = ( $streams['default'] ?? NULL );
			}
			else
			{
				$this->defaultStreamId = NULL;
			}
		}
		
		return $this->defaultStreamId;
	}
	
	/**
	 * Set member's default stream
	 *
	 * @param int|null $value	Null or stream ID. 0 is for 'all activity'
	 * @return	void
	 */
	public function set_defaultStream( ?int $value ) : void
	{
		if ( $this->member_streams and $streams = json_decode( $this->member_streams, TRUE ) and count( $streams ) )
		{
			$streams['default'] = $value;
		}
		else
		{
			$streams = array( 'streams' => array(), 'default' => $value );
		}
		
		$this->member_streams = json_encode( $streams );
		$this->save();
		
		$this->defaultStreamId = $value;
	}
	
	/**
	 * Get member location
	 *
	 * @return	string|null
	 */
	public function location(): ?string
	{
		if( $this->sessionData === FALSE )
		{
			return NULL;
		}

		if( $this->sessionData === NULL )
		{
			$this->sessionData = Session\Store::i()->getLatestMemberSession( $this->member_id );
		}
		
		return ( $this->sessionData ) ? Session::i()->getLocation( $this->sessionData ) : NULL;
	}

	/**
	 * Get validating description
	 *
	 * @param 	null	$validatingRow
	 * @return 	string
	 */
	public function validatingDescription( $validatingRow=NULL ): string
	{
		try
		{
			$validatingRow = ( $validatingRow ) ?: Db::i()->select( '*', 'core_validating', array( 'member_id=?', $this->member_id ) )->first();
		}
		catch( UnderflowException $ex )
		{
			return '';
		}
		
		$validatingDescription = '';
		if ( $validatingRow['new_reg'] )
		{
			if ( $validatingRow['reg_cancelled'] )
			{
				$validatingDescription = Member::loggedIn()->language()->addToStack('members_validating_cancelled');
			}
			elseif ( $validatingRow['user_verified'] )
			{
				$validatingDescription = Member::loggedIn()->language()->addToStack('members_validating_admin');
			}
			else
			{
				$validatingDescription = Member::loggedIn()->language()->addToStack('members_validating_user');
			}
	
			if ( $validatingRow['coppa_user'] )
			{
				$validatingDescription .= Member::loggedIn()->language()->addToStack('members_validating_coppa');
			}
	
			if ( $validatingRow['spam_flag'] )
			{
				$validatingDescription .= Member::loggedIn()->language()->addToStack('members_validating_spam');
			}
		}
		elseif ( $validatingRow['email_chg'] )
		{
			$validatingDescription .= Member::loggedIn()->language()->addToStack('members_validating_email_chg');
		}
		
		return $validatingDescription;
	}

	/**
	 * Followers Count
	 *
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|NULL	$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @return	int
	 * @throws	BadMethodCallException
	 */
	public function followersCount( int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL ): int
	{
		if( $this->members_bitoptions['pp_setting_moderate_followers'] )
		{
			return 0;
		}

		if( !$this->member_id )
		{
			return 0;
		}

		/* Return the count */
		return static::_followersCount( 'member', $this->member_id, $privacy, $frequencyTypes, $date );
	}

	/**
	 * Followers
	 *
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|NULL		$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @param	int|array|NULL			$limit			LIMIT clause
	 * @param	string|NULL				$order			Column to order by
	 * @param	bool					$countOnly		Return only the count
	 * @return	Select|int
	 * @throws	BadMethodCallException
	 */
	public function followers(int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL, int|array|null $limit=array( 0, 25 ), string|null $order=NULL, bool $countOnly=FALSE ): Select|int
	{
		if( $this->members_bitoptions['pp_setting_moderate_followers'] )
		{
			return 0;
		}

		return static::_followers( 'member', $this->member_id ?: 0, $privacy, $frequencyTypes, $date, $limit, $order, FALSE, null );
	}

	/**
	 * Record a failed login attempt
	 *
	 * @return void
	 */
	public function failedLogin(): void
	{
		Db::i()->insert( 'core_login_failures', [
			'login_member_id'   => $this->member_id ?: NULL,
			'login_date'        => ( new DateTime )->getTimestamp(),
			'login_ip_address'  => Request::i()->ipAddress(),
			'login_email'       => $this->member_id ? NULL : $this->email
		]);

		if( $this->member_id )
		{
			$where = [ [ 'login_date>=?', ( Settings::i()->ipb_bruteforce_unlock ? ( new DateTime() )->sub( new DateInterval( 'PT' . Settings::i()->ipb_bruteforce_period . 'M' ) )->getTimestamp() : 0 ) ] ];
			$where[] = [ 'login_ip_address IS NOT NULL AND login_member_id=?', $this->member_id ];
			$failedLogins = iterator_to_array( Db::i()->select( 'count(login_ip_address)', 'core_login_failures', $where, NULL, NULL, 'login_ip_address' ) );

			/* We hit an issue where the query above returned an empty result set,
			possibly a read/write issue. Rather than switch to the writer, just assume we have
			1 failure, or we wouldn't be here. */
			$this->failed_login_count = count( $failedLogins ) ? max( $failedLogins ) : 1;
			$this->save();
		}
	}
	
	/**
	 * Get MFA details
	 *
	 * @return	array
	 */
	public function get_mfa_details(): array
	{
		return $this->_data['mfa_details'] ? json_decode( $this->_data['mfa_details'], TRUE ) : array();
	}
	
	/**
	 * Set MFA details
	 *
	 * @param array $data	Data
	 * @return void
	 */
	public function set_mfa_details( array $data ) : void
	{
		$this->_data['mfa_details'] = json_encode( $data );
	}
	
	/**
	 * Get profile sync settings
	 *
	 * @return	array
	 */
	public function get_profilesync(): array
	{
		$return = isset( $this->_data['profilesync'] ) ? ( json_decode( $this->_data['profilesync'], TRUE ) ?: array() ) : array();
		
		if ( isset( $return['facebook'] ) or isset( $return['Facebook'] ) or isset( $return['google'] ) or isset( $return['Google'] ) or isset( $return['linkedin'] ) or isset( $return['Linkedin'] ) or isset( $return['LinkedIn'] ) or isset( $return['microsoft'] ) or isset( $return['Microsoft'] ) or isset( $return['live'] ) or isset( $return['Live'] ) or isset( $return['twitter'] ) or isset( $return['Twitter'] ) )
		{
			$newVal = array();
			foreach ( $return as $loginKey => $prefs )
			{
				$classname = NULL;
				switch ( mb_strtolower( $loginKey ) )
				{
					case 'internal':
						$classname = 'IPS\\Login\\Handler\\Standard';
						break;
					case 'facebook':
						$classname = 'IPS\\Login\\Handler\\OAuth2\\Facebook';
						break;
					case 'google':
						$classname = 'IPS\\Login\\Handler\\OAuth2\\Google';
						break;
					case 'linkedin':
						$classname = 'IPS\\Login\\Handler\\OAuth2\\LinkedIn';
						break;
					case 'live':
					case 'microsoft':
						$classname = 'IPS\\Login\\Handler\\OAuth2\\Microsoft';
						break;
					case 'twitter':
						$classname = 'IPS\\Login\\Handler\\OAuth1\\Twitter';
						break;
				}
				
				if ( $classname and class_exists( $classname ) )
				{
					try
					{
						$methodId = Db::i()->select( 'login_id', 'core_login_methods', array( 'login_classname=? AND login_enabled=1', $classname ) )->first();
						
						foreach ( $prefs as $option => $v )
						{
							if ( $v )
							{
								$newVal[ $option ] = array( 'handler' => $methodId, 'ref' => NULL, 'error' => NULL );
							}
						}
					}
					catch ( UnderflowException $e ) { }
				}
			}
			
			$this->_data['profilesync'] = json_encode( $newVal );
			return $newVal;
		}
		
		return $return;
	}
	
	/**
	 * Set profile sync settings
	 *
	 * @param array $data	Data
	 * @return void
	 */
	public function set_profilesync( array $data ) : void
	{
		$this->_data['profilesync'] = json_encode( $data );
		
		if ( !$data )
		{
			$this->_data['profilesync_lastsync'] = 0;
		}
	}
	
	/**
	 * Get timezone
	 *
	 * @return	string
	 */
	public function get_timezone(): ?string
	{
		return DateTime::getFixedTimezone( $this->_data['timezone'] );
	}
	
	/* !Photos */
	
	/**
	 * Columns needed to build photos
	 *
	 * @return	array
	 */
	public static function columnsForPhoto(): array
	{
		return array( 'member_id', 'name', 'members_seo_name', 'member_group_id', 'mgroup_others', 'pp_photo_type', 'pp_main_photo', 'pp_thumb_photo' );
	}
	
	/**
	 * Get photo from data
	 *
	 * @param array $memberData			Array of member data, must include values for at least the keys returned by columnsForPhoto()
	 * @param bool $thumb				Use thumbnail?
	 * @param bool $email				Is the photo going to be used in an email?
	 * @param bool $useDefaultPhoto	If there is no photo, should the default (rather than NULL) be returned?
	 * @return	string|null
	 */
	public static function photoUrl( array $memberData, bool $thumb=TRUE, bool $email=FALSE, bool $useDefaultPhoto=TRUE ): ?string
	{
		$photoUrl = NULL;

		/* All this only applies to members... */
		if ( isset( $memberData['member_id'] ) and $memberData['member_id'] )
		{
			/* We have an uploaded or synced photo */
			if ( $memberData['pp_main_photo'] and ( mb_substr( $memberData['pp_photo_type'], 0, 5 ) === 'sync-' or $memberData['pp_photo_type'] === 'custom' ) )
			{
				try
				{
					$photoUrl = File::get( 'core_Profile', ( $thumb and $memberData['pp_thumb_photo'] ) ? $memberData['pp_thumb_photo'] : $memberData['pp_main_photo'] )->url;
				}
				catch ( InvalidArgumentException $e ) { }
			}
			/* Letter photos are enabled and we do not have a photo set, but only if this isn't for email */
			elseif( Settings::i()->letter_photos == 'letters' AND $email === FALSE and isset( $memberData['name'] ) )
			{
				if( $photo = static::generateLetterPhoto($memberData) )
				{
					$photoUrl = $photo;
				}
			}
			/* Other - This allows an app (such as Gallery) to set the pp_photo_type to a storage container to support custom images without duplicating them */
			elseif( $memberData['pp_photo_type'] and $memberData['pp_photo_type'] != 'none' and mb_strpos( $memberData['pp_photo_type'], '_' ) !== FALSE )
			{
				try
				{
					$photoUrl = File::get( $memberData['pp_photo_type'], $memberData['pp_main_photo'] )->url;
				}
				catch ( InvalidArgumentException $e )
				{				
					/* If there was an exception, clear these values out - most likely the image or storage container is no longer valid */
					$member = Member::load( $memberData['member_id'] );
					$member->pp_photo_type	= NULL;
					$member->pp_main_photo	= NULL;
					$member->save();
				}
			}

			/* Return */
			if( $photoUrl !== NULL )
			{
				return (string) $photoUrl;
			}
		}

		/* Still here? Return default photo */
		if ( !$photoUrl and $useDefaultPhoto )
		{
			if( $email )
			{
				return rtrim( Settings::i()->base_url, '/' ) . '/applications/core/interface/email/default_photo.png';
			}
			else
			{
				if( Settings::i()->letter_photos == 'letters' AND isset( $memberData['member_id'] ) AND $memberData['member_id'] AND isset( $memberData['name'] ) AND $memberData['name'] )
				{
					if( $photo = static::generateLetterPhoto($memberData) )
					{
						return (string) $photo;
					}
				}

				return (string) Theme::i()->resource( 'default_photo.png', 'core', 'global' );
			}
		}
		return NULL;
	}

	/**
	 * Generate a letter photo (SVG)
	 *
	 * @param	array 	$memberData	Member data
	 * @param bool $returnJson		Return the letter and color as an array, instead of the SVG string
	 * @return	string|array
	 */
	public static function generateLetterPhoto(array $memberData, bool $returnJson=FALSE ): array|string
	{
		/* Get the letter we'll use */
		$letter = static::getLettersForPhoto( $memberData['name'] );

		/* Have we already cached the color? */
		if( isset( $memberData['pp_main_photo'] ) AND $memberData['pp_main_photo'] AND strlen( $memberData['pp_main_photo'] ) === 6 )
		{
			$color = $memberData['pp_main_photo'];
		}
		else
		{
			/* Generate a new unique color */
			$photo = new LetterPhoto( $memberData['name'] );

			$color = $photo->generateColorCode();

			/* Save the color for next time */
			if( isset( $memberData['member_id'] ) AND $memberData['member_id'] )
			{
				$member = static::load( $memberData['member_id'] );

				if( $member->member_id )
				{
					$member->pp_photo_type = 'none';
					$member->pp_main_photo = $color;
					$member->save();
				}
			}
		}

		if( $returnJson === TRUE )
		{
			return array( 'letter' => $letter, 'color' => $color );
		}

		return "data:image/svg+xml," . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" style="background:#' . $color . '"><g><text text-anchor="middle" dy=".35em" x="512" y="512" fill="#ffffff" font-size="700" font-family="-apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif">' . $letter . '</text></g></svg>' );
	}

	/**
	 * Return the first X non-punctuation characters of the name
	 *
	 * @param string $name		Name to use
	 * @param int $letters	Number of letters to return
	 * @return	string|NULL
	 */
	public static function getLettersForPhoto( string $name, int $letters=1 ): ?string
	{
		$name = str_replace( array( '<', '>', '=', '-', '+', '"', "'" ), '', $name );
		$name = preg_replace( "/(\pP)+/u", '', trim( $name ) );

		return $name ? mb_strtoupper( mb_substr( $name, 0, $letters ) ) : NULL;
	}

	/* !Get Calculated Properties */
	
	/**
	 * Get administrators
	 *
	 * @return	array
	 */
	public static function administrators(): array
	{
		if ( !isset( Store::i()->administrators ) )
		{
			Store::i()->administrators = array(
				'm'	=> iterator_to_array( Db::i()->select( '*', 'core_admin_permission_rows', array( 'row_id_type=?', 'member' ) )->setKeyField( 'row_id' ) ),
				'g'	=> iterator_to_array( Db::i()->select( '*', 'core_admin_permission_rows', array( 'row_id_type=?', 'group' ) )->setKeyField( 'row_id' ) ),
			);
		}
		return Store::i()->administrators;
	}
	
	/**
	 * Is an admin?
	 *
	 * @return	bool
	 */
	public function isAdmin(): bool
	{
		return $this->acpRestrictions() !== FALSE;
	}

	/**
	 * Does this member have any moderator permissions?
	 *
	 * @return bool
	 */
	public function isModerator() : bool
	{
		if( $this->isAdmin() )
		{
			return true;
		}

		return (bool) $this->modShowBadge();
	}

	/**
	 * @brief	Is this member an expert in the community?
	 */
	protected mixed $isExpert = null;

	/**
	 * Is Member a community Expert?
	 *
	 * @return	bool
	 */
	public function isExpert(): bool
	{
		if( !Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			return false;
		}

		/* Admin level blocked */
		if ( $this->members_bitoptions['expert_user_blocked'] )
		{
			return false;
		}

		if ( $this->isExpert === null )
		{
			try
			{
				$this->isExpert = (bool)Db::i()->select( 'member_id', 'core_expert_users', ['member_id=?', $this->member_id] )->first();
			}
			catch ( UnderflowException $e )
			{
				$this->isExpert = false;
			}
		}

		return $this->isExpert;
	}

	/**
	 * Does this member want to be an expert?
	 *
	 * @return	bool
	 */
	public function showAsExpert(): bool
	{
		return ( $this->members_bitoptions['expert_user_disabled'] and $this->isExpert );
	}

	/**
	 * @brief	Cache the session data if we pull it for location, etc.
	 */
	protected mixed $sessionData	= NULL;

	/**
	 * Is online?
	 *
	 * @return	bool
	 */
	public function isOnline(): bool
	{
		if( !$this->member_id )
		{
			return FALSE;
		}

		if ( $this->sessionData === NULL )
		{
	    	$this->sessionData	= Session\Store::i()->getLatestMemberSession( $this->member_id );
		}
		
		if( $this->sessionData === FALSE )
		{
			return FALSE;
		}

		$diff = DateTime::ts( $this->last_activity )->diff( DateTime::create() );
		if ( $diff->y or $diff->m or $diff->d or $diff->h or $diff->i > 15 )
		{
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Is Online Anonymously
	 *
	 * @return	bool
	 */
	public function isOnlineAnonymously(): bool
	{
		if ( !$this->member_id )
		{
			return FALSE;
		}

		/* If the group is forced to be anonymous */
		if( $this->group['g_hide_online_list'] == 1 )
		{
			return TRUE;
		}

		/* Anonymous status is not allowed */
		if( $this->group['g_hide_online_list'] == 2 )
		{
			return FALSE;
		}
		
		return $this->members_bitoptions['is_anon'];
	}

	/**
	 * Are any of these memberIds following me?
	 *
	 * @param array $memberIds
	 * @return array
	 */
	public function isFollowing( array $memberIds ): array
	{
		$memberIds = array_unique( array_filter( $memberIds ) );
		if ( !$memberIds )
		{
			return array();
		}

		$follows = array();
		foreach ( $memberIds as $memberId )
		{
			$follows[ $memberId ] = false;
		}

		foreach( Db::i()->select( 'follow_rel_id', 'core_follow', array( "follow_app='core' AND follow_area='member' AND follow_rel_id IN (" . implode( ',', $memberIds ) . ") AND follow_member_id=?", $this->member_id ) ) as $member )
		{
			$follows[ $member ] = true;
		}

		return $follows;
	}

	/**
	 * Is banned?
	 * If is banned until a certain time, returns an \IPS\DateTime object
	 *
	 * @return bool|DateTime
	 */
	public function isBanned(): bool|DateTime
	{
		if ( $this->temp_ban != 0 )
		{
			if ( $this->temp_ban != -1 and time() >= $this->temp_ban )
			{
				$this->temp_ban = 0;
				$this->save();
				return FALSE;
			}
			elseif ( $this->temp_ban > 0 )
			{
				return DateTime::ts( $this->temp_ban );
			}
			
			return TRUE;
		}

		if( !$this->group['g_view_board'] )
		{
			return TRUE;
		}
		
		return FALSE;
	}
		
	/**
	 * Is the member in a certain group (including secondary groups)
	 *
	 * @param array|int|Group $group				The group, or array of groups
	 * @param bool $permissionArray	If TRUE, checks the permission array rather than the groups
	 * @return	bool
	 */
	public function inGroup( array|int|Group $group, bool $permissionArray=FALSE ): bool
	{
		$group = array_filter( is_array( $group ) ? $group : array( $group ) );
		$check = array_filter( $permissionArray ? $this->permissionArray() : $this->groups );

		foreach ( $group as $_group )
		{
			$groupId = ( $_group instanceof Group ) ? $_group->g_id : $_group;

			if ( in_array( $groupId, $check ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

	/**
	 * Is this member editing the theme?
	 * @note This is only used in the front-end
	 *
	 * @return bool
	 */
	public function isEditingTheme(): bool
	{
		return (bool) $this->members_bitoptions['bw_using_skin_gen'];
	}

	/**
	 * Store a reference to the language object
	 */
	protected Lang|UpgradeLang|SetupLang|null $_lang = NULL;

	/**
	 * Return the language object to use for this member - returns default if member has not selected a language
	 *
	 * @param bool $frontOnly If TRUE, will only look at the the langauge for the front-end, not the AdminCP
	 * @return Lang|UpgradeLang|SetupLang|null
	 */
	public function language(bool $frontOnly=FALSE ): Lang|UpgradeLang|SetupLang|null
	{
		/* Did we already load the language object? */
		if( $this->_lang !== NULL )
		{
			return $this->_lang;
		}

		/* If in API use the lang set by dispatcher */
		if ( Dispatcher::hasInstance() and class_exists( 'IPS\Dispatcher', FALSE ) AND Dispatcher::i()->controllerLocation === 'api' )
		{
			$this->_lang = Dispatcher::i()->_setLanguage();
			return $this->_lang;
		}

		/* If in setup, create a "dummy" language */
		if ( Dispatcher::hasInstance() and class_exists( 'IPS\Dispatcher', FALSE ) AND Dispatcher::i()->controllerLocation === 'setup' AND Dispatcher::i()->setupLocation === 'install' )
		{
			$this->_lang = Lang::setupLanguage();
			return $this->_lang;
		}
		else if ( Dispatcher::hasInstance() and class_exists( 'IPS\Dispatcher', FALSE ) AND Dispatcher::i()->controllerLocation === 'setup' AND Dispatcher::i()->setupLocation === 'upgrade' )
		{
			$this->_lang = Lang::upgraderLanguage();
			return $this->_lang;
		}

		/* Work out if we are getting the ACP language or the normal language */
		$column	= 'language';
		if ( !$frontOnly and Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'admin' and $this->member_id and $this->member_id == static::loggedIn()->member_id )
		{
			$column	= 'acp_language';
		}
		
		/* If the member has a language set, try that */
		if( $this->calculatedLanguageId !== NULL or $this->$column )
		{
			try
			{
				$this->_lang	= Lang::load( $this->calculatedLanguageId ?: $this->$column );

				/* Disabled Languages are allowed to be used in the ACP */
				if( $this->_lang->enabled OR $column == 'acp_language' )
				{
					return $this->_lang;
				}
			}
			catch ( OutOfRangeException $e ) { }
		}
		
		/* Otherwise, if this is us, try looking at HTTP_ACCEPT_LANGUAGE, if enabled */
		if ( Dispatcher::hasInstance() and $this->member_id == static::loggedIn()->member_id and Settings::i()->lang_auto_detect )
		{
			/* Work out what's in HTTP_ACCEPT_LANGUAGE */
			$preferredLanguage = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? Lang::autoDetectLanguage( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) : NULL;
			
			/* If we worked one out, use that and save it on the account so it gets used for emails etc */
			if ( $preferredLanguage )
			{
				$this->calculatedLanguageId = $preferredLanguage;
				
				if ( $this->member_id )
				{
					$this->$column = $preferredLanguage;
					$this->save();
				}
			}
			/* Otherwise, just use the default */
			else
			{
				$this->calculatedLanguageId = Lang::defaultLanguage();
			}
		}		
		else
		{
			/* Just return the default language */
			$this->calculatedLanguageId = Lang::defaultLanguage();
		}
		
		/* Set it */
		$this->_lang = Lang::load( $this->calculatedLanguageId );
		
		/* Add upgrader language bits if appropriate */
		if ( Dispatcher::hasInstance() AND class_exists( 'IPS\Dispatcher', FALSE ) AND Dispatcher::i()->controllerLocation === 'setup' AND Dispatcher::i()->setupLocation === 'upgrade' )
		{
			$this->_lang->upgraderLanguage();
		}
		
		/* Return */
		return $this->_lang;
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;

	/**
	 * Get URL
	 *
	 * @return	Url|string|null
	 */
	function url(): Url|string|null
	{
		if( $this->_url === NULL )
		{
			$this->_url = Url::internal( "app=core&module=members&controller=profile&id={$this->member_id}", 'front', 'profile', $this->members_seo_name );
		}

		return $this->_url;
	}
	
	/**
	 * URL to ACP "Edit Member"
	 *
	 * @return	Url
	 */
	public function acpUrl(): Url
	{
		return Url::internal( "app=core&module=members&controller=members&do=view&id={$this->member_id}", 'admin' );
	}
	
	/**
	 * HTML link to profile with hovercard
	 *
	 * @param string|null $warningRef			The reference key for warnings
	 * @param boolean|null $groupFormatting		Apply the group prefix/suffix to the name?
	 * @param boolean|null $anonymous			Is this shown with anonymous content?
	 * @return	string
	 */
	public function link( string $warningRef=NULL, bool $groupFormatting=NULL, ?bool $anonymous=FALSE ): string
	{
		$groupFormatting = ( $groupFormatting === NULL ) ? !( ( Settings::i()->group_formatting == 'legacy' ) ) : $groupFormatting;

		if ( !Settings::i()->warn_on )
		{
			$warningRef = NULL;
		}
		return Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( $this, $warningRef, $groupFormatting, $anonymous );
	}
	
	/**
	 * Profile Fields shown next to users content
	 */
	public ?array $rawProfileFieldsData = NULL;

	/**
	 * Profile Fields
	 *
	 * @param int $location	\IPS\core\ProfileFields\Field::PROFILE for profile, \IPS\core\ProfileFields\Field::REG for registration screen, \IPS\core\ProfileFields\Field::STAFF for ModCP/ACP, \IPS\core\ProfileFields\Field::EDIT for member editing
	 * @param bool $raw		Returns the raw value if true or the display value if false. Useful for comparisons for field types like Yes/NO to see if a value is set.
	 * @return	array|null
	 */
	public function profileFields( int $location = 0, bool $raw=FALSE ): ?array
	{
		if ( !$this->member_id )
		{
			return array();
		}

		if( $this->rawProfileFieldsData !== NULL )
		{
			return $this->rawProfileFieldsData;
		}

		$this->rawProfileFieldsData = array();
		$values = array();
		
		try
		{
			$values = Db::i()->select( '*', 'core_pfields_content', array( 'member_id = ?', $this->member_id ) )->first();
		}
		catch ( UnderflowException $e ) {}

		if( !empty( $values ) )
		{
			foreach (core\ProfileFields\Field::values( $values, $location, $raw ) as $group => $fields )
			{
				$this->rawProfileFieldsData[ 'core_pfieldgroups_' . $group ] =  $fields;
			}
		}

		return $this->rawProfileFieldsData;
	}

	/**
	 * Profile Fields shown next to users content
	 */
	public ?array $profileFields = NULL;

	/**
	 * Profile Fields shown next to users content
	 *
	 * @param array|null $fieldValues Field values already fetched from the database
	 *
	 * @return array|null
	 */
	public function contentProfileFields( array $fieldValues=NULL ): ?array
	{
		if ( $this->profileFields === NULL )
		{
			$this->profileFields = array();
			if ( $this->member_id AND core\ProfileFields\Field::fieldsForContentView() )
			{
				$select = '*';

				/* Can we view private fields? */
				if( !Dispatcher::hasInstance() OR !( Member::loggedIn()->isAdmin() OR Member::loggedIn()->member_id === $this->member_id ) )
				{
					$select = 'member_id';
					$publicFields = Db::i()->select( '*', 'core_pfields_data', array( 'pf_topic_hide != ?', 'hide' ) );
					foreach( $publicFields as $field )
					{
						if( $field['pf_topic_hide'] == 'all' OR ( $field['pf_topic_hide'] == 'staff' AND ( Member::loggedIn()->isAdmin() OR Member::loggedIn()->modPermissions() ) )
						  )
						{
							$select .= ", field_{$field['pf_id']}";
						}
					}
				}
				
				if ( $fieldValues !== NULL and is_array( $fieldValues ) )
				{
					if ( $select == '*' )
					{
						$values = $fieldValues;
					}
					else
					{
						$fields = explode( ', ', $select );
						$values = array();
						foreach( $fields as $f )
						{
							if ( isset( $fieldValues[ $f ] ) )
							{
								$values[ $f ] = $fieldValues[ $f ];
							}
						}
					}
				}
				else
				{
					try
					{
						$values = Db::i()->select( $select, 'core_pfields_content', array( 'member_id = ?', $this->member_id ) )->first();
					}
					catch ( UnderflowException $e ) {}
				}
				
				if ( is_array( $values ) )
				{
					$this->setProfileFieldValuesInMemory( $values );
				}
			}
		}

		return $this->profileFields;
	}
	
	/**
	 * Store profile field values in memory
	 *
	 * @param	array	$values
	 * @return	void
	 */
	public function setProfileFieldValuesInMemory( array $values ) : void
	{
		$this->profileFields = array();

		$values = array_filter( $values, function ( $val) { return ( $val !== '' AND $val !== NULL ); } );

		if( !empty( $values ) )
		{
			/* Make sure member_id is set */
			$values['member_id'] = $this->member_id;

			foreach (core\ProfileFields\Field::values( $values, core\ProfileFields\Field::CONTENT ) as $group => $fields )
			{
				$this->profileFields[ 'core_pfieldgroups_' . $group ] = $fields;
			}
		}
	}
		
	/**
	 * IP Addresses
	 *
	 * @code
	 	return array(
	 		'::1' => array(
		 		'count'		=> ...	// int (number of times this member has used this IP)
		 		'first'		=> ... 	// \IPS\DateTime (first use)
		 		'last'		=> ... 	// \IPS\DateTime (last use)
		 	),
		 	...
	 	);
	 * @endcode
	 * @return	array
	 */
	public function ipAddresses(): array
	{
		$return = array();
		
		foreach ( Application::allExtensions( 'core', 'IpAddresses' ) as $class )
		{
			$results	= $class->findByMember( $this );

			if( $results === NULL )
			{
				continue;
			}

			foreach ( $results as $ip => $data )
			{
				if ( isset( $return[ $ip ] ) )
				{
					$return[ $ip ]['count'] += $data['count'];
					if ( $data['first'] < $return[ $ip ]['first'] )
					{
						$return[ $ip ]['first'] = $data['first'];
					}
					if ( $data['last'] > $return[ $ip ]['last'] )
					{
						$return[ $ip ]['last'] = $data['last'];
					}
				}
				else
				{
					if ( $ip )
					{
						$return[ $ip ] = $data;
					}
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Mark the entire site as read
	 *
	 * @return void
	 */
	public function markAllAsRead() : void
	{
		/* Delete all member markers */
		Db::i()->delete( 'core_item_markers', array( 'item_member_id=?', $this->member_id ) );
		
		$this->marked_site_read = time();
		$this->save();
	}
	
	/**
	 * Get read/unread markers
	 *
	 * @param string $app	Application key
	 * @param string $key	Marker key
	 * @return	array
	 */
	public function markersItems( string $app, string $key ): array
	{
		if ( !isset( $this->markers[ $app ] ) or !array_key_exists( $key, $this->markers[ $app ] ) )
		{
			try
			{
				$marker = Db::i()->select( '*', 'core_item_markers', array( 'item_key=? AND item_member_id=? AND item_app=?', $key, $this->member_id, $app ) )->first();
				$this->markers[ $app ][ $key ] = $marker;
			}
			catch ( UnderflowException $e )
			{
				$this->markers[ $app ][ $key ] = NULL;
			}
		}
		return $this->markers[ $app ][ $key ] ? json_decode( $this->markers[ $app ][ $key ]['item_read_array'], TRUE ) : array();
	}
	
	/**
	 * Get read/unread markers for containers
	 *
	 * @param	string|NULL	$app	Application key or NULL for all applications
	 * @return	array|int
	 */
	public function markersResetTimes( ?string $app ): array|int
	{
		if ( ( !$app and !$this->haveAllMarkers ) or ( $app and !isset( $this->markersResetTimes[ $app ] ) ) )
		{
			try
			{
				$where = array( array( 'item_member_id=?', $this->member_id ) );
				if ( $app )
				{
					$this->markersResetTimes[ $app ] = array();
					$where[] = array( 'item_app=?', $app );
				}
				else
				{
					$this->markersResetTimes = array();
				}

				foreach (Db::i()->select( '*', 'core_item_markers', $where ) as $row )
				{
					$this->setMarkerResetTimes( $row );
				}
				
				if ( !$app )
				{
					$this->haveAllMarkers = TRUE;
				}
			}
			catch ( UnderflowException $e )
			{
				if ( $app )
				{
					$this->markersResetTimes[ $app ] = array();
				}
				else
				{
					$this->markersResetTimes = array();
				}
			}
		}
		
		if ( $app )
		{
			return $this->markersResetTimes[ $app ];
		}
		else
		{
			return $this->markersResetTimes;
		}
	}

	/**
	 * Set read/unread markers for containers
	 *
	 * @param array $row	Row of marker data
	 * @return	void
	 */
	public function setMarkerResetTimes( array $row ) : void
	{
		if( !isset( $this->markersResetTimes[ $row['item_app'] ] ) or !is_array( $this->markersResetTimes[ $row['item_app'] ] ) )
		{
			$this->markersResetTimes[ $row['item_app'] ] = array();
		}

		if ( $row['item_app_key_1'] )
		{
			if ( $row['item_app_key_2'] )
			{
				if( !isset( $this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ] ) OR !is_array( $this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ] ) )
				{
					$this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ]	= array();
				}

				if ( $row['item_app_key_3'] )
				{
					if( !isset( $this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ][ $row['item_app_key_2'] ] ) OR !is_array( $this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ][ $row['item_app_key_2'] ] ) )
					{
						$this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ][ $row['item_app_key_2'] ]	= array();
					}

					$this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ][ $row['item_app_key_2'] ][ $row['item_app_key_3'] ] = $row['item_global_reset'];
				}
				else
				{
					$this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ][ $row['item_app_key_2'] ] = $row['item_global_reset'];
				}
			}
			else
			{
				$this->markersResetTimes[ $row['item_app'] ][ $row['item_app_key_1'] ] = $row['item_global_reset'];
			}
		}
		else
		{
			$this->markersResetTimes[ $row['item_app'] ] = $row['item_global_reset'];
		}

		$this->markers[ $row['item_app'] ][ $row['item_key'] ] = $row;
	}
	
	/**
	 * Get Warnings
	 *
	 * @param int|null $limit			The number to get
	 * @param bool|null $acknowledged	If true, will only get warnings that have been acknowledged, if false will only get warnings that have not been knowledged. If NULL, will get both.
	 * @param string|null $type			If specified, will only pull warnings that applied a specific action.
	 * @return	ActiveRecordIterator|array
	 */
	public function warnings( int|null $limit, bool $acknowledged=NULL, string $type=NULL ): ActiveRecordIterator|array
	{
		if ( !$this->member_id )
		{
			return array();
		}
		
		if ( !Settings::i()->warn_on )
		{
			return array();
		}
		
		$where = array( array( 'wl_member=?', $this->member_id ) );
		if ( $acknowledged !== NULL )
		{
			$where[] = array( 'wl_acknowledged=?', $acknowledged );
		}
		
		switch ( $type )
		{
			case 'mq':
				$where[] = array( 'wl_mq IS NOT NULL' );
				break;
			case 'rpa':
				$where[] = array( 'wl_rpa IS NOT NULL' );
				break;
			case 'suspend':
				$where[] = array( 'wl_suspend IS NOT NULL' );
				break;
		}
				
		return new ActiveRecordIterator( Db::i()->select( '*', 'core_members_warn_logs', $where, 'wl_date DESC', $limit, NULL, NULL, Db::SELECT_DISTINCT ), 'IPS\core\Warnings\Warning' );
	}

	/**
	 * @brief	Cached reputation data
	 */
	protected ?array $_reputationData	= NULL;
	
	/**
	 * Calculate and cache the member's reputation level data
	 *
	 * @return	array
	 */
	protected function getReputationData() : array
	{
		if( $this->_reputationData === NULL )
		{
			$this->_reputationData	= array();
			
			if ( isset( Store::i()->reputationLevels ) )
			{
				$reputationLevels = Store::i()->reputationLevels;
			}
			else
			{
				$reputationLevels = iterator_to_array( Db::i()->select( '*', 'core_reputation_levels', NULL, 'level_points DESC' ) );
				Store::i()->reputationLevels = $reputationLevels;
			}
			
			foreach ( $reputationLevels as $level )
			{
				if ( $this->pp_reputation_points >= $level['level_points'] )
				{
					$this->_reputationData = $level;
					break;
				}
			}
		}

		return $this->_reputationData;
	}
	
	/**
	 * @brief	Cached reputation last day won
	 */
	protected array|null|false $_reputationLastDayWon = NULL;
		
	/**
	 * Return the 'date' of the last day won, along with the 'rep_total'.
	 *
	 * @return array|bool|null ( 'date' => \IPS\DateTime, 'rep_total' => int )|FALSE
	 */
	public function getReputationLastDayWon(): bool|array|null
	{
		if ( $this->_reputationLastDayWon === NULL )
		{
			try
			{
				$dayWon = Db::i()->select( 'leader_date, leader_rep_total', 'core_reputation_leaderboard_history', array( 'leader_position=1 AND leader_member_id=?', $this->member_id ), 'leader_date DESC', array( 0, 1 ) )->first();
				/* The 'day won' must be in the leaderboard timezone otherwise it will be off for people in significantly different timezones */
				$this->_reputationLastDayWon = array( 'date' => DateTime::ts( $dayWon['leader_date'], true )->setTimezone( new DateTimeZone( Settings::i()->reputation_timezone ) ), 'rep_total' => $dayWon['leader_rep_total'] );
				
			}
			catch( UnderflowException $ex )
			{
				$this->_reputationLastDayWon = FALSE;
			}
		}
		
		return $this->_reputationLastDayWon;
	}
	
	/**
	 * @brief	Cached reputation days won count
	 */
	protected ?int $_reputationDaysWonCount = NULL;
	
	/**
	 * Return the total number of days won
	 *
	 * @return int|null
	 */
	public function getReputationDaysWonCount(): ?int
	{
		if ( $this->_reputationDaysWonCount === NULL )
		{
			$this->_reputationDaysWonCount = Db::i()->select( 'COUNT(*)', 'core_reputation_leaderboard_history', array( 'leader_position=1 AND leader_member_id=?', $this->member_id ) )->first();
		}
		
		return $this->_reputationDaysWonCount;
	}

	/**
	 * Reputation level description
	 *
	 * @return	string|NULL
	 */
	public function reputation(): ?string
	{
		$level	= $this->getReputationData();
		
		if( isset( $level['level_id'] ) )
		{
			return Member::loggedIn()->language()->addToStack( 'core_reputation_level_' . $level['level_id'] );
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Reputation image
	 *
	 * @return	string|NULL
	 */
	public function reputationImage(): ?string
	{
		$level	= $this->getReputationData();
		
		if( isset( $level['level_id'] ) )
		{
			return $level['level_image'];
		}
		else
		{
			return NULL;
		}
	}
		
	/**
	 * Verify legacy password
	 *
	 * @param object $password	The (legacy) password to verify, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	bool
	 */
	public function verifyLegacyPassword( object $password ): bool
	{
		return Login::compareHashes( $this->members_pass_hash, md5( md5( $this->members_pass_salt ) . md5( Request::legacyEscape( $password ) ) ) );
	}
	
	/**
	 * Set local password
	 *
	 * BE CAREFUL: The standard login handler may be disabled, only call this method
	 * if you have alreadu checked it is enabled. In most cases, it is better to let
	 * the available login handlers handle password management
	 *
	 * @param object|string $password	Password to encrypt, can be wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @return	void
	 */
	public function setLocalPassword( string|object $password ) : void
	{
		/* We can safeuly assume that no matter what, this doesn't need to be set anymore */
		$this->members_bitoptions['password_reset_forced'] = FALSE;
		
		$this->members_pass_hash = password_hash( $password, PASSWORD_DEFAULT );
		$this->members_pass_salt = NULL;
	}
	
	/**
	 * Change member's password for all applicable login handlers
	 *
	 * @param	object	$newPassword		The new password in plaintext, wrapped in an object that can be cast to a string so it doesn't show in any logs
	 * @param string $type			Type of change for log
	 * @return	bool
	 */
	public function changePassword( object $newPassword, string $type='manual' ): bool
	{
		$return = FALSE;
		foreach (Login::methods() as $method )
		{
			if ( $method->canChangePassword( $this ) )
			{
				try
				{
					$method->changePassword( $this, $newPassword );
					$return = TRUE;
				}
				catch( BadMethodCallException $e ){}
			}
		}
		Event::fire( 'onPassChange', $this, array( $newPassword ) );
		$this->logHistory( 'core', 'password_change', $type );
		
		return $return;
	}
	
	/**
	 * Force user to reset their password.
	 *
	 * @return	void
	 */
	public function forcePasswordReset() : void
	{
		/* If the user is already forced, but hasn't yet, just return */
		if ( $this->members_bitoptions['password_reset_forced'] )
		{
			return;
		}
		
		$this->members_pass_hash = NULL; # Intentionally bypasses setLocalPassword() as it will try and hash the NULL value.
		$this->members_bitoptions['password_reset_forced'] = TRUE;
		$this->save();
		
		/* Invalidate login keys and sessions for all devices */
		$this->invalidateSessionsAndLogins();
		
		$key = md5( SUITE_UNIQUE_KEY . $this->email . $this->real_name );
		
		$email = Email::buildFromTemplate( 'core', 'password_reset_forced', array( $this, $key ), Email::TYPE_TRANSACTIONAL );
		$email->send( $this );
	}
	
	/**
	 * Password Reset Forced
	 *
	 * @param	Url|null $ref		Referrer, or NULL for no referrer.
	 * @return	Url|null
	 */
	public function passwordResetForced( ?Url $ref = NULL ): ?Url
	{
		if ( $this->members_bitoptions['password_reset_forced'] AND !$this->members_pass_hash )
		{
			return Url::internal( 'app=core&module=system&controller=settings&area=password', 'front', 'settings_password' )->setQueryString( 'ref', base64_encode( $ref ) );
		}
		
		return NULL;
	}

	/**
	 * @brief	Cached notifications configuration
	 * @note	This property is public so it can be populated en-masse in areas that may benefit from doing so
	 */
	public ?array $notificationsConfiguration	= NULL;

	static protected array $extCache = [];
	
	/**
	 * Notifications Configuration
	 *
	 * @return	array|null
	 */
	public function notificationsConfiguration(): ?array
	{
		if( $this->notificationsConfiguration === NULL )
		{
			$this->notificationsConfiguration = array();
			$defaultNotifications = [];

			foreach (
				Db::i()->select(
					'd.*, p.preference',
					array( 'core_notification_defaults', 'd' )
				)->join(
					array( 'core_notification_preferences', 'p' ),
					array( 'd.notification_key=p.notification_key AND p.member_id=?', $this->member_id )
				)
				as $row
			) {
				if ( $row['preference'] === NULL or !$row['editable'] )
				{
					$defaultNotifications[ $row['notification_key'] ] = explode( ',', $row['default'] );
				}
				else
				{
					$this->notificationsConfiguration[ $row['notification_key'] ] = array_diff( explode( ',', $row['preference'] ), explode( ',', $row['disabled'] ) );
				}
			}

			/* Only cycle through if this member has any non-default notifications */
			if( count( $this->notificationsConfiguration ) )
			{
				/* Cache extension references */
				if ( !count( static::$extCache ) )
				{
					static::$extCache = Application::allExtensions( 'core', 'Notifications' );
				}

				foreach ( $defaultNotifications as $k => $v )
				{
					foreach ( static::$extCache as $extension )
					{
						foreach ( $extension->configurationOptions( null ) as $optionKey => $option )
						{
							if ( $option['type'] == 'standard' and in_array( $k, $option['notificationTypes'] ) and count( $option['notificationTypes'] ) > 1 )
							{
								if ( !empty( $this->notificationsConfiguration[ $option['notificationTypes'][0] ] ) )
								{
									$defaultNotifications[ $k ] = $this->notificationsConfiguration[ $option['notificationTypes'][0] ];
									Db::i()->insert( 'core_notification_preferences', [
									'member_id' => $this->member_id,
									'notification_key' => $k,
									'preference' => implode( ',', $this->notificationsConfiguration[ $option['notificationTypes'][0] ] )
									] );
								}

								continue 2;
							}
						}
					}
				}
			}

			$this->notificationsConfiguration = array_merge( $this->notificationsConfiguration, $defaultNotifications );
		}

		return $this->notificationsConfiguration;
	}
	
	/**
	 * @brief	Cached PWA Authorizations
	 */
	protected ?array $_pwa = NULL;
	
	/**
	 * Get members PWA Authorizations
	 *
	 * @return	array
	 */
	public function getPwaAuths(): array
	{
		if ( $this->_pwa === NULL )
		{
			$this->_pwa = array();
			foreach(Db::i()->select( '*', 'core_notifications_pwa_keys', array( "`member`=?", $this->member_id ) ) AS $auth )
			{
				$this->_pwa[ $auth['id'] ] = $auth;
			}
		}
		
		return $this->_pwa;
	}

	/**
	 * Get members PWA Authorizations
	 *
	 * @return	void
	 */
	public function clearPwaAuths() : void
	{
		$this->_pwa = NULL;
		Db::i()->delete( 'core_notifications_pwa_keys', array( "`member`=?", $this->member_id ) );
	}
	
	/**
	 * @brief	Following?
	 */
	protected array $_following	= array();

	/**
	 * Following
	 *
	 * @param string $app	Application key
	 * @param string $area	Area
	 * @param int $id		Item ID
	 * @return	bool
	 */
	public function following( string $app, string $area, int $id ): bool
	{
		$_key	= md5( $app . $area . $id );
		if( isset( $this->_following[ $_key ] ) )
		{
			return $this->_following[ $_key ];
		}

		try
		{
			Db::i()->select( 'follow_id', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=? AND follow_member_id=?', $app, $area, $id, $this->member_id ) )->first();
			$this->_following[ $_key ]	= TRUE;
		}
		catch ( UnderflowException $e )
		{
			$this->_following[ $_key ]	= FALSE;
		}

		return $this->_following[ $_key ];
	}

	/**
	 * Admin CP Restrictions
	 *
	 * @return bool|array|string
	 */
	protected function acpRestrictions(): bool|array|string
	{
		if ( !$this->member_id )
		{
			return FALSE;
		}
		
		if ( $this->restrictions === NULL )
		{
			$administrators = static::administrators();
			
			$rows = array();
			if ( isset( $administrators['m'][ $this->member_id ] ) )
			{
				$rows[] = $administrators['m'][ $this->member_id ];
			}
			foreach ( $this->groups as $id )
			{
				if ( isset( $administrators['g'][ $id ] ) )
				{
					$rows[] = $administrators['g'][ $id ];
				}
			}
									
			$this->restrictions = FALSE;
			if ( count( $rows ) > 0 )
			{
				$this->restrictions = array();
				foreach ( $rows as $row )
				{
					if ( $row['row_perm_cache'] === '*' )
					{
						$this->restrictions = '*';
						break;
					}
					
					$perms = json_decode( $row['row_perm_cache'], TRUE );
					if ( $row['row_id_type'] === 'member' )
					{
						$this->restrictions = $perms;
						break;
					}
					else if( is_array( $perms ) )
					{
						if ( empty( $this->restrictions ) )
						{
							$this->restrictions = $perms;
						}
						else
						{
							if( isset( $perms['applications'] ) )
							{
								foreach ( $perms['applications'] as $app => $modules )
								{
									if ( !isset( $this->restrictions['applications'][ $app ] ) )
									{
										$this->restrictions['applications'][ $app ] = array();
									}

									foreach ( $modules as $module )
									{
										if ( !isset( $this->restrictions['applications'][ $app ][ $module ] ) )
										{
											$this->restrictions['applications'][ $app ][ $module ] = $module;
										}
									}
								}
							}
							if( isset( $perms['items'] ) )
							{
								foreach ( $perms['items'] as $app => $modules )
								{
									if ( !isset( $this->restrictions['items'][ $app ] ) )
									{
										$this->restrictions['items'][ $app ] = array();
									}

									foreach ( $modules as $module => $items )
									{
										if ( !isset( $this->restrictions['items'][ $app ][ $module ] ) )
										{
											$this->restrictions['items'][ $app ][ $module ] = array();
										}

										foreach ( $items as $item )
										{
											if ( !in_array( $item, $this->restrictions['items'][ $app ][ $module ] ) )
											{
												$this->restrictions['items'][ $app ][ $module ][] = $item;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		return $this->restrictions;
	}
	
	/**
	 * @brief Cache moderator badge results
	 */
	protected static array $modBadge = array();
	
	/**
	 * Show moderator badge?
	 *
	 * @return	boolean|array
	 */
	public function modShowBadge(): bool|array
	{
		if ( ! $this->member_id )
		{
			return FALSE;
		}
		
		if ( ! array_key_exists( $this->member_id, static::$modBadge ) )
		{
			static::$modBadge[ $this->member_id ] = false;
			
			if ( $this->modPermission() )
			{
				if ( isset( Store::i()->moderators['m'][ $this->member_id ] ) )
				{
					static::$modBadge[ $this->member_id ] = Store::i()->moderators['m'][ $this->member_id ]['show_badge'];
				}
				else
				{
					foreach ( $this->groups as $id )
					{
						if ( isset( Store::i()->moderators['g'][ $id ] ) and Store::i()->moderators['g'][ $id ]['show_badge'] )
						{
							static::$modBadge[ $this->member_id ] = Store::i()->moderators['g'][ $id ];
						}
					}
				}
			}
		}

		return static::$modBadge[ $this->member_id ];
	}
	
	/**
	 * Moderator Permissions
	 *
	 * @return	bool|array|string
	 */
	public function modPermissions(): bool|array|string
	{
		/* Only members can be moderators of course */
		if ( !$this->member_id )
		{
			return FALSE;
		}
		
		/* Work out the permissions... */	
		if ( $this->modPermissions === NULL )
		{
			/* Start with FALSE (no moderator permissions) */
			$this->modPermissions = FALSE;
			
			/* If we don't have a datastore of moderator configuration, load that now */
			if ( !isset( Store::i()->moderators ) )
			{
				Store::i()->moderators = array(
					'm'	=> iterator_to_array( Db::i()->select( '*', 'core_moderators', array( 'type=?', 'm' ) )->setKeyField( 'id' ) ),
					'g'	=> iterator_to_array( Db::i()->select( '*', 'core_moderators', array( 'type=?', 'g' ) )->setKeyField( 'id' ) ),
				);
			}
			
			/* Member-level moderator permissions override all group-level permissions, so if this member is a moderator at a member-level, just use that */
			if ( isset( Store::i()->moderators['m'][ $this->member_id ] ) )
			{
				$perms = Store::i()->moderators['m'][ $this->member_id ]['perms'];
				$this->modPermissions = $perms == '*' ? '*' : json_decode( $perms, TRUE );
			}
			
			/* Otherwise, examine the groups and combine the permissions each group awards */
			else
			{
				/* Get all the groups the member is in which have moderator permissions... */
				$rows = array();
				foreach ( $this->groups as $id )
				{
					if ( isset( Store::i()->moderators['g'][ $id ] ) )
					{
						$rows[] = Store::i()->moderators['g'][ $id ];
					}
				}
				
				/* And if we have any... */			
				if ( count( $rows ) > 0 )
				{
					/* Start with an empty array (indicates they are a moderator, but haven't get given them any permissions) */
					$this->modPermissions = array();
					
					/* Loop the groups and combine the permissions... */
					foreach ( $rows as $row )
					{
						/* If any group has all permissions, this user has all moderator permissions and we don't need to go further */
						if ( $row['perms'] === '*' )
						{
							$this->modPermissions = '*';
							break;
						}
						
						/* Otherwise, examine what permissions this group has... */
						$perms = json_decode( $row['perms'], TRUE );
						if( !empty( $perms ) )
						{
							foreach ( $perms as $k => $v )
							{
								/* If we haven't seen this permission key at all, give them the value */
								if ( !isset( $this->modPermissions[ $k ] ) )
								{
									$this->modPermissions[ $k ] = $v;
								}
								/* If it's an array, combine the values */
								elseif ( is_array( $this->modPermissions[ $k ] ) AND is_array( $v ) )
								{
									$this->modPermissions[ $k ] = array_merge( $this->modPermissions[ $k ], $v );
								}
								
								/* If it's a number, they get the higher one, or -1 is best */
								elseif ( $v == -1 or ( $this->modPermissions[ $k ] != -1 and $v > $this->modPermissions[ $k ] ) )
								{
									$this->modPermissions[ $k ] = $v;
								}
							}
						}
					}
				}
			}
		}

		/* Return */
		return $this->modPermissions;
	}
	
	/**
	 * @brief	Report count
	 */
	protected ?int $reportCount = NULL;
	
	/**
	 * Get number of open reports that this member can see
	 *
	 * @param bool $force	Fetch the count even if the notification is off
	 * @return	int
	 */
	public function reportCount( bool $force=FALSE ): int
	{
		if ( $this->reportCount === NULL OR $force === TRUE )
		{
			if ( $this->canAccessModule( Module::get( 'core', 'modcp' ) ) )
			{
				if( !$this->members_bitoptions['no_report_count'] OR $force === TRUE )
				{
					$where = [ Report::where( $this ), [ 'status IN( 1,2 )' ] ];

					$reportCount = Db::i()->select(
						'COUNT(*)',
						'core_rc_index',
						$where
					)->first();

					if( $force === FALSE )
					{
						$this->reportCount	= $reportCount;
					}
					else
					{
						return $reportCount;
					}
				}
			}
			else
			{
				$this->reportCount = 0;
			}
		}
		
		return (int) $this->reportCount;
	}

	/**
	 * Can the member view any reported content?
	 *
	 * @return bool
	 */
	public function canAccessReportCenter() : bool
	{
		if( $this->modPermission( 'can_view_reports' ) )
		{
			return TRUE;
		}

		$perms = $this->modPermissions();

		if ( $perms === false )
		{
			return false;
		}
		
		foreach ( Content::routedClasses( $this, FALSE, TRUE ) as $_class )
		{
			if( isset( $perms[ "can_view_reports_{$_class::$title}" ] ) and $perms[ "can_view_reports_{$_class::$title}" ] )
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @var int|null
	 */
	protected ?int $_assignments = null;

	/**
	 * Return total number of content items assigned to this member
	 *
	 * @return int
	 */
	public function totalAssignments() : int
	{
		if( $this->_assignments === null )
		{
			$clause = [
				"(assign_type=? and assign_to=?)"
			];
			$binds = [
				Assignment::ASSIGNMENT_TEAM,
				$this->member_id
			];

			if( $teams = $this->teams() )
			{
				$clause[] = "(assign_type=? and " . Db::i()->in( 'assign_to', array_keys( $teams ) ) . ")";
				$binds[] = Assignment::ASSIGNMENT_MEMBER;
			}

			$where = array_merge( array( implode( " OR ", $clause ) ), $binds );
			$this->_assignments = (int) Db::i()->select( 'count(assign_id)', 'core_assignments', $where )->first();
		}

		return $this->_assignments;
	}

	/**
	 * Get all teams to which this member belongs
	 *
	 * @return array|null
	 */
	public function teams() : array|null
	{
		$teams = [];
		foreach( Team::teams() as $t )
		{
			if( in_array( $this->member_id, $t->members ) )
			{
				$teams[$t->id] = $t;
			}
		}

		return count( $teams ) ? $teams : null;
	}
	
	/**
	 * @brief	Ignore Preferences
	 * @see		isIgnoring
	 */
	protected ?array $ignorePreferences = NULL;
	
	/**
	 * Is this member ignoring another member?
	 *
	 * @param array|Member $member	The member
	 * @param string $type	The type (topics, messages, signatures)
	 * @return	bool
	 */
	public function isIgnoring( array|Member $member, string $type ): bool
	{
		if( is_array( $member ) )
		{
			$member = Member::load( $member['member_id'] );
		}

		$group = $member->group;
		$id    = $member->member_id;
		$cannotBeIgnored = !$member->canBeIgnored();

		if ( $cannotBeIgnored or !$this->member_id )
		{
			return FALSE;
		}

		/* We cannot ignore ourself */
		if( $this->member_id == $member->member_id )
		{
			return FALSE;
		}
		
		if ( $this->ignorePreferences === NULL )
		{
			if ( $this->members_bitoptions['has_no_ignored_users'] )
			{
				$this->ignorePreferences = array();
			}
			else
			{
				$this->ignorePreferences = iterator_to_array( Db::i()->select( '*', 'core_ignored_users', array( 'ignore_owner_id=?', $this->member_id ) )->setKeyField( 'ignore_ignore_id' ) );
				
				if ( empty( $this->ignorePreferences ) )
				{
					$this->members_bitoptions['has_no_ignored_users'] = TRUE;
					$this->save();
				}
			}
		}
				
		if ( isset( $this->ignorePreferences[ $id ] ) )
		{
			return (bool) $this->ignorePreferences[ $id ][ 'ignore_' . $type ];
		}
		
		return FALSE;
	}

	/**
	 * Build a menu for this member
	 * @param string $menuType (account|mobile|profile)
	 * @return Menu|null
	 */
	public function menu( string $menuType ) : Menu|null
	{
		$method = $menuType . 'Menu';
		$menu = UserMenu::$method( $this );
		if( $menu instanceof Menu AND $menu->hasContent() )
		{
			return $menu;
		}

		return null;
	}

	/**
	 * Insert extra content in the user navigation bar
	 *
	 * @return string
	 */
	public function userNav() : string
	{
		$return = "";
		foreach( Application::allExtensions( 'core', 'UserMenu', $this, 'core' ) as $ext )
		{
			$return .= $ext->userNav();
		}
		return $return;
	}

	/**
	 * Insert extra content in the mobile navigation bar
	 *
	 * @param string $position (header/footer)
	 * @return string
	 */
	public function mobileNav( string $position = 'footer' ) : string
	{
		$return = "";
		foreach( Application::allExtensions( 'core', 'UserMenu', $this, 'core' ) as $ext )
		{
			$return .= $ext->mobileNav( $position, Theme::i()->getParsedCssVariableFromKey( 'set__i-mobile-icons-location' ) );
		}
		return $return;
	}
	
	/**
	 * Moderate New Content
	 *
	 * @param bool $considerPostBeforeRegistering	If TRUE, and $member is a guest, will check if a newly registered member would be moderated
	 * @return	bool
	 */
	public function moderateNewContent( ?bool $considerPostBeforeRegistering=FALSE ): bool
	{
		$modQueued = FALSE;
		
		/* Check the group */
		if ( $this->group['g_mod_preview'] )
		{
			if ( $this->group['g_mod_post_unit'] )
			{
				/* Days since joining */
				if ( $this->group['gbw_mod_post_unit_type'] )
				{
					$modQueued = $this->joined->add( new DateInterval( "P{$this->group['g_mod_post_unit']}D" ) )->getTimestamp() > time();
				}
				/* Content items */
				else
				{
					$modQueued = $this->member_posts < $this->group['g_mod_post_unit'];
				}
			}
			else
			{
				$modQueued = TRUE;
			}
		}
		
		/* If we're not group moderated what about individual member */
		if ( !$modQueued )
		{
			if( $this->mod_posts == -1 or ( $this->mod_posts > 0 and $this->mod_posts > time() ) )
			{
				$modQueued = TRUE;
			}
		}
		
		/* Post before register check */
		if ( !$modQueued and $considerPostBeforeRegistering and $this->member_group_id == Settings::i()->guest_group )
		{
			$modQueued = (bool) Group::load( Settings::i()->member_group )->g_mod_preview;
		}

		/* Return */
		return $modQueued;
	}
	
	/**
	 * Cover Photo
	 *
	 * @return	Coverphoto
	 */
	public function coverPhoto(): Coverphoto
	{
		$photo = new CoverPhoto;
		if ( $this->pp_cover_photo )
		{
			$photo->file = File::get( 'core_Profile', $this->pp_cover_photo );
			$photo->offset = $this->pp_cover_offset;
		}
		$photo->editable	= ( Member::loggedIn()->modPermission('can_modify_profiles') or ( Member::loggedIn()->member_id == $this->member_id and $this->group['g_edit_profile'] and $this->group['gbw_allow_upload_bgimage'] ) );
		$photo->maxSize		= $this->group['g_max_bgimg_upload'];
		$photo->object		= $this;
		
		return $photo;
	}
	
	/**
	 * Get HTML for search result display
	 *
	 * @return	string
	 */
	public function searchResultHtml(): string
	{
		return Theme::i()->getTemplate('search')->member( $this );
	}
	
	/**
	 * Should a reply made by this member be highlighted?
	 *
	 * @return	boolean
	 */
	public function hasHighlightedReplies(): bool
	{
		return (boolean) $this->group['gbw_post_highlight'];
	}
	
	/**
	 * Get output for API
	 *
	 * @param Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @param array|null $otherFields		Array of additional fields to return (raw values)
	 * @return	array
	 * @apiresponse			int											id						ID number
	 * @apiresponse			string										name					Username
	 * @apiresponse			string										title					Member title
	 * @clientapiresponse	string										timezone				Member timezone
	 * @apiresponse			string										formattedName			Username with group formatting
	 * @apiresponse			\IPS\Member\Group							primaryGroup			Primary group
	 * @clientapiresponse	[\IPS\Member\Group]							secondaryGroups			Secondary groups
	 * @clientapiresponse	string										email					Email address
	 * @apiresponse			datetime									joined					Registration date
	 * @clientapiresponse	string										registrationIpAddress	IP address when registered
	 * @clientapiresponse	int											warningPoints			Number of active warning points
	 * @apiresponse			int											reputationPoints		Number of reputation points
	 * @apiresponse			string										photoUrl				URL to photo (which will be the site's default if they haven't set one)
	 * @apiresponse			bool										photoUrlIsDefault		Indicates if the value of photoUrl is the site's default
	 * @apiresponse			string										coverPhotoUrl			URL to profile cover photo (will be blank if there isn't one)
	 * @apiresponse			string|null									profileUrl				URL to profile
	 * @clientapiresponse	bool										validating				Whether or not the validating flag is set on the member account
	 * @apiresponse			int											posts					Number of content item submissions member has made
	 * @clientapiresponse	datetime|null								lastActivity			Last activity date on the site.
	 * @clientapiresponse	datetime|null								lastVisit				Last distinct visit date on the site.
	 * @clientapiresponse	datetime|null								lastPost				Latest content submission date.
	 * @apiresponse			int											profileViews			Number of times member's profile has been viewed
	 * @apiresponse			string										birthday				Member birthday in MM/DD/YYYY format (or MM/DD format if no year has been supplied).
	 * @apiresponse			[\IPS\core\ProfileFields\Api\FieldGroup]	customFields			Custom profile fields. For requests using an OAuth Access Token for a particular member, only fields the authorized user can view will be included
	 * @apiresponse			[\IPS\core\Achievements\Rank]					rank					Rank
	 * @apiresponse			int											achievements_points		Points
	 * @apiresponse			bool										allowAdminEmails		Whether or not this member wants to receive admin emails
	 * @apiresponse			bool										completed				Whether or not the registration is completed
	 */
	public function apiOutput( Member $authorizedMember = NULL, array $otherFields = NULL ): array
	{
		try
		{
			$group = Group::load( $this->_data['member_group_id'] );
		}
		catch( OutOfRangeException $e )
		{
			Log::log( "{$this->name} has an invalid group ({$this->_data['member_group_id']}) during API call. Resetting.", 'api_invalid_group' );
			$group = Group::load( Settings::i()->member_group ); // Intentially no catch here as that means things are really broken.
			$this->member_group_id = Settings::i()->member_group;
			$this->save();
		}
		
		$secondaryGroups = array();
		foreach ( array_filter( array_map( "intval", explode( ',', $this->_data['mgroup_others'] ) ) ) as $secondaryGroupId )
		{
			try
			{
				$secondaryGroups[] = Group::load( $secondaryGroupId )->apiOutput( $authorizedMember );
			}
			catch ( OutOfRangeException $e ) { }
		}

		/* Figure out custom fields if any */
		$fields = array();
		
		try
		{
			$fieldData		= core\ProfileFields\Field::fieldData();
			$fieldValues	= Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member_id ) )->first();
	
			foreach( $fieldData as $profileFieldGroup => $profileFields )
			{
				$groupValues = array();
	
				foreach( $profileFields as $field )
				{
					if ( !$authorizedMember or
						$field['pf_contains_pii'] == 1 AND ( $authorizedMember->member_id == $this->member_id ) or
						$field['pf_member_hide'] == 'all' or
						( $field['pf_member_hide'] == 'owner' and ( $authorizedMember->member_id == $this->member_id OR $authorizedMember->modPermissions() OR $authorizedMember->isAdmin() ) ) or
						( $field['pf_member_hide'] == 'staff' and ( $authorizedMember->modPermissions() OR $authorizedMember->isAdmin() ) )
						)
					{
						$groupValues[ $field['pf_id'] ] = new Field( $this->language()->get( 'core_pfield_' . $field['pf_id'] ), $fieldValues[ 'field_' . $field['pf_id'] ] );
					}
				}
				
				$fields[ $profileFieldGroup ] = ( new FieldGroup( $this->language()->get( 'core_pfieldgroups_' . $profileFieldGroup ), $groupValues ) )->apiOutput( $authorizedMember );
			}
		}
		catch( UnderflowException $e ) { } # Guests will not have any profile field information.
		
		$return = array();
		$return['id']					= $this->member_id;
		$return['name']					= $this->_name;
		$return['title']				= null;
		if ( !$authorizedMember )
		{
			$return['timeZone']				= $this->timezone;
		}
		$return['formattedName']		= $group->formatName( $this->_name );
		$return['primaryGroup']			= $group->apiOutput( $authorizedMember );
		if ( !$authorizedMember )
		{
			$return['secondaryGroups']		= $secondaryGroups;
			$return['email']				= $this->email;
		}
		$return['joined']				= $this->joined->rfc3339();
		if ( !$authorizedMember )
		{
			$return['registrationIpAddress']= $this->ip_address;
			$return['warningPoints']		= $this->warn_level;
		}
		$return['reputationPoints']		= $this->pp_reputation_points;
		$return['photoUrl']				= static::photoUrl($this->_data, FALSE);
		$return['photoUrlIsDefault']	= in_array( $this->pp_photo_type, [ 'none', 'letter' ] ) or static::photoUrl($this->_data, FALSE, FALSE, FALSE) != $return['photoUrl'];
		$return['coverPhotoUrl']		= $this->pp_cover_photo ? ( (string) File::get( 'core_Profile', $this->pp_cover_photo )->url ) : '';
		$return['profileUrl']			= ( $this->member_id ) ? (string) $this->url() : NULL;
		if ( !$authorizedMember )
		{
			$return['validating']			= (bool) $this->members_bitoptions['validating'];
		}
		$return['posts']				= $this->member_posts;
		
		if( !$authorizedMember )
		{
			$return['lastActivity'] 		= ( $this->last_activity AND !$this->isOnlineAnonymously() ) ? DateTime::ts( $this->last_activity )->rfc3339() : NULL;
			$return['lastVisit'] 		= $this->last_visit ? DateTime::ts( (int)$this->last_visit )->rfc3339() : NULL;
			$return['lastPost'] 		= $this->member_last_post ? DateTime::ts( $this->member_last_post )->rfc3339() : NULL;
		}

		if( !$authorizedMember OR $authorizedMember->member_id === $this->member_id )
		{
			$return['birthday'] = $this->bday_month ? ( $this->bday_month . '/' . $this->bday_day . ( $this->bday_year ? '/' . $this->bday_year : '' ) ) : NULL;
		}
		$return['profileViews']		= $this->members_profile_views;
		$return['customFields']		= $fields;

		if ( !$authorizedMember )
		{
			if( $otherFields !== NULL AND is_array( $otherFields ) )
			{
				foreach( $otherFields as $property )
				{
					$return[ $property ] = $this->$property;
				}
			}
		}

		$return['rank'] = $this->rank() ? $this->rank()->apiOutput( $authorizedMember ) : NULL;
		$return['achievements_points'] = $this->achievements_points;
		$return['allowAdminEmails'] = $this->allow_admin_mails;
		$return['completed'] = $this->completed;

		return $return;
	}
	
	/**
	 * Answers to security questions
	 *
	 * @return	Select
	 */
	public function securityAnswers(): Select
	{
		return Db::i()->select( array( 'answer_question_id', 'answer_answer' ), 'core_security_answers', array( 'answer_member_id=?', $this->member_id ) )->setKeyField('answer_question_id')->setValueField('answer_answer');
	}
	
	/**
	 * Last used device
	 *
	 * @return	Device|NULL
	 */
	public function lastUsedDevice(): ?Device
	{
		try
		{
			return Device::constructFromData( Db::i()->select( '*', 'core_members_known_devices', array( 'member_id=?', $this->member_id ), 'last_seen DESC', 1 )->first() );
		}
		catch ( Exception $e )
		{
			return NULL;
		}
	}
		
	/**
	 * Last used IP address
	 *
	 * @return	string|NULL
	 */
	public function lastUsedIp(): ?string
	{
		try
		{
			return Db::i()->select( 'ip_address', 'core_members_known_ip_addresses', array( 'member_id=?', $this->member_id ), 'last_seen DESC', 1 )->first();
		}
		catch ( Exception $e )
		{
			return NULL;
		}
	}
		
	/**
	 * Device count
	 *
	 * @return	int
	 */
	public function deviceCount(): int
	{
		return Db::i()->select( 'COUNT(*)', 'core_members_known_devices', array( 'member_id=?', $this->member_id ) )->first();
	}

	protected array $_failedLoginCache;

	/**
	 * Check if account is locked - returns FALSE if account is unlocked, an \IPS\DateTime object if the account is locked until a certain time, or TRUE if account is locked indefinitely
	 *
	 * @return    DateTime|bool
	 */
	public function unlockTime() : DateTime|bool
	{
		if( !Settings::i()->ipb_bruteforce_attempts )
		{
			return FALSE;
		}

		if( !isset( $this->_failedLoginCache[ Request::i()->ipAddress() ] ) )
		{
			$where = [ [ 'login_date>=? AND login_ip_address=?', ( new DateTime() )->sub( new DateInterval( 'PT' . Settings::i()->ipb_bruteforce_period . 'M' ) )->getTimestamp(), Request::i()->ipAddress() ] ];
			if ( $this->member_id )
			{
				$where[] = [ 'login_member_id=?', $this->member_id ];
			}
			else
			{
				$where[] = [ 'login_email=?', $this->email ];
			}

			$this->_failedLoginCache[ Request::i()->ipAddress() ] = iterator_to_array( Db::i()->select( '*', 'core_login_failures', $where ) );
		}

		if( count( $this->_failedLoginCache[ Request::i()->ipAddress() ] ) > Settings::i()->ipb_bruteforce_attempts )
		{
			if ( Settings::i()->ipb_bruteforce_period and Settings::i()->ipb_bruteforce_unlock )
			{
				return DateTime::ts( max( array_column( $this->_failedLoginCache[ Request::i()->ipAddress() ], 'login_date' ) ) )->add( new DateInterval( 'PT' . Settings::i()->ipb_bruteforce_period . 'M' ) );
			}

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Return the number of failed logins for a given IP address
	 *
	 * @param string $ipAddress	IP Address
	 * @return	int
	 */
	/**
	 * Return the number of failed logins for a given IP address
	 *
	 * @param string $ipAddress	IP Address
	 * @return	int
	 */
	public function failedLoginCount( string $ipAddress ): int
	{
		if( !isset( $this->_failedLoginCache[ $ipAddress ] ) )
		{
			$where = [ [ 'login_date>=? AND login_ip_address=?', ( new DateTime() )->sub( new DateInterval( 'PT' . Settings::i()->ipb_bruteforce_period . 'M' ) )->getTimestamp(), Request::i()->ipAddress() ] ];
			if( $this->member_id )
			{
				$where[] = [ 'login_member_id=?', $this->member_id ];
			}
			else
			{
				$where[] = [ 'login_email=?', $this->email ];
			}

			$this->_failedLoginCache[ $ipAddress ] = Db::i()->select( '*', 'core_login_failures', $where )->first();
		}

		return count( $this->_failedLoginCache[ $ipAddress ] );
	}
			
	/* !Permissions */
	
	/**
	 * @var	array	ACP restriction keys which are hardcoded to FALSE when on CiC (even if admin has no restrictions)
	 */
	public static array $cicBlockedAcpRestrictions = [ 'core.membersettings.member_history_prune', 'core.settings.datastore', 'core.membersettings.notifications_prune' ];
	
	/**
	 * Has access to a restricted ACP area?
	 *
	 * @param string|Application $app	Application
	 * @param string|Module|null $module	Module
	 * @param string|null $key	Restriction Key
	 */
	public function hasAcpRestriction( Application|string $app, Module|string $module=NULL, string $key=NULL ): bool
	{		
		/* Load our ACP restrictions */
		$restrictions = $this->acpRestrictions();
		if ( $restrictions === FALSE )
		{
			return FALSE;
		}
		
		/* CiC Block */
		$appKey = is_string( $app ) ? $app : $app->directory;
		$moduleKey = ( $module === NULL or is_string( $module ) ) ? $module : $module->key;
		if ( CIC and in_array( $appKey . ( $moduleKey ? ( ".{$moduleKey}" . ( $key ? ".{$key}" : '' ) ) : '' ), static::$cicBlockedAcpRestrictions ) )
		{
			return FALSE;
		}

		/* If we have all permissions, return true */
		if ( $restrictions === '*' )
		{
			return TRUE;
		}

		/* If we don't have any permissions, return false */
		if( !count( $restrictions ) )
		{
			return FALSE;
		}
		
		/* Otherwise, check 'em! */
		if ( array_key_exists( $appKey, $restrictions['applications'] ) )
		{
			if ( $module === NULL )
			{
				return TRUE;
			}
			else
			{
				if ( in_array( $moduleKey, $restrictions['applications'][ $appKey ] ) )
				{
					if ( $key === NULL )
					{
						return TRUE;
					}
					elseif ( isset( $restrictions['items'][ $appKey ][ $moduleKey ] ) and in_array( $key, $restrictions['items'][ $appKey ][ $moduleKey ] ) )
					{
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}
	
	/**
	 * Get moderator permission
	 *
	 * @param string|null $key	Permission Key to check, or NULL to just test if they have any moderator permissions.
	 * @return	mixed
	 */
	public function modPermission( string $key=NULL ): mixed
	{
		/* Load our permissions */
		$permissions = $this->modPermissions();

		if ( $permissions == FALSE )
		{
			return FALSE;
		}
		
		/* If we have all permissions, return true */
		if ( $permissions === '*' or $key === NULL )
		{
			return TRUE;
		}
				
		/* Otherwise return it */
		return $permissions[$key] ?? NULL;
	}
	
	/**
	 * Can warn
	 *
	 * @param Member $member	The member to warn
	 * @return	bool
	 */
	public function canWarn( Member $member ): bool
	{
		if( !$this->modPermission('mod_can_warn') OR !$this->modPermission('mod_see_warn') )
		{
			return FALSE;
		}
		
		if( $member->inGroup( explode( ',', Settings::i()->warn_protected ) ) or $member->member_id == Member::loggedIn()->member_id )
		{
			return FALSE;
		}
		
		if ( $this->modPermission('warn_mod_day') !== TRUE and $this->modPermission('warn_mod_day') != -1 )
		{
			$oneDayAgo = DateTime::create()->sub( new DateInterval( 'P1D' ) );
			$warningsGivenInTheLastDay = Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( 'wl_moderator=? AND wl_date>?', $this->member_id, $oneDayAgo->getTimestamp() ) )->first();
			if( $warningsGivenInTheLastDay >= $this->modPermission('warn_mod_day') )
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/* !Recounting */
	
	/**
	 * Recalculate notification count
	 *
	 * @return	void
	 */
	public function recountNotifications() : void
	{
		$this->notification_cnt = Db::i()->select( 'COUNT(*)', 'core_notifications', array( '`member`=? AND read_time IS NULL', $this->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$this->save();
	}

	/**
	 * Recounts content for this member
	 *
	 * @return void
	 */
	public function recountContent() : void
	{
		$this->member_posts = 0;
        foreach ( Content::routedClasses( $this, TRUE, FALSE ) as $class )
		{			
			$this->member_posts += $class::memberPostCount( $this );
		}
		
		$this->save();
	}
	
	/**
	 * Recounts reputation for this member
	 *
	 * @return void
	 */
	public function recountReputation() : void
	{
		$this->pp_reputation_points = Db::i()->select( 'SUM(rep_rating)', 'core_reputation_index', array( 'member_received=?', $this->member_id ) )->first();
		$this->save();
	}

	/**
	 * Removes reputation for this member
	 *
	 * @param string $type	given|received The type of reputation to remove
	 * @return void
	 */
	public function removeReputation( string $type ) : void
	{
		$where = ( $type == 'given' ) ? array( 'member_id=?', $this->member_id ) : array( 'member_received=?', $this->member_id );

		Db::i()->delete( 'core_reputation_index', $where );

		if( $type == 'received' )
		{
			$this->recountReputation();
		}
	}
	
	/* !Do Stuff */
	
	/**
	 * Can use module
	 *
	 * @param Module $module	The module to test
	 * @return	bool
	 * @throws	InvalidArgumentException
	 */
	public function canAccessModule( Module $module ): bool
	{
		if ( !( $module instanceof Module ) )
		{
			throw new InvalidArgumentException;
		}
		
		return Application::load( $module->application )->canAccess( $this ) and ( $module->protected or $module->can( 'view', $this ) );
	}

	/**
	 * @brief		Store whitelist filters
	 */
	public static ?array $whitelistFilters = NULL;

	/**
	 * Check Spam Defense Whitelist
	 *
	 * @param string|null $emailAddress			Email address to check, NULL for existing email address
	 * @return	boolean
	 */
	public function spamDefenseWhitelist( string $emailAddress=NULL ): bool
	{
		$email = $emailAddress ?: $this->email;

		if( static::$whitelistFilters === NULL )
		{
			static::$whitelistFilters = iterator_to_array( Db::i()->select( 'whitelist_type, whitelist_content', 'core_spam_whitelist' ) );
		}

		foreach( static::$whitelistFilters as $whitelist )
		{
			if( $whitelist['whitelist_type'] == 'ip' )
			{
				if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $whitelist['whitelist_content'], '/' ) ) . '$/i', $this->ip_address ) )
				{
					return TRUE;
				}
			}
			else
			{
				if( preg_match( '/' . str_replace( '\*', '.*', preg_quote( '@' . $whitelist['whitelist_content'], '/' ) ) . '$/i', $email ) )
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * IPS Spam Defense Service
	 *
	 * @param string $type			Request type
	 * @param string|null $emailAddress	Email address to check, NULL for existing email address
	 * @param int|null $spamCode		If set, will modify by reference with the raw value from the spam service
	 * @param	bool		$disposable		If set, will modify by reference if the user used a disposable email.
	 * @param	bool		$geoBlock		If set, will modify by reference if the user is in a moderated / blocked country.
	 * @return	int|NULL					Action code based on spam service response, or NULL for no action
	 */
	public function spamService( string $type='register', string $emailAddress=NULL, int &$spamCode=NULL, bool &$disposable=FALSE, bool &$geoBlock=FALSE ): ?int
	{
		$email = $emailAddress ?: $this->email;

		/* Check Whitelist */
		if( $this->spamDefenseWhitelist( $email ) )
		{
			/* Account details were whitelisted */
			return NULL;
		}

		try
		{
			$response = Url::ips( 'spam/' . $type )->request()->login( Settings::i()->ipb_reg_number, '' )->post( array(
				'email'	=> $email,
				'ip'	=> $this->ip_address,
			) );

			if ( $response->httpResponseCode != 200 )
			{
				throw new DomainException( print_r( $response, TRUE ) );
			}
			
			$spamCode = intval( (string) $response );
		}
		catch ( Exception $e )
		{
			Log::debug( $e, 'spam-service' );
			$spamCode = 0;
		}
				
		$action = NULL;
					
		if( $type == 'register' and $spamCode )
		{
			/* Log Request */
			Db::i()->insert( 'core_spam_service_log', array(
				'log_date'		=> time(),
				'log_code'		=> $spamCode,
				'log_msg'		=> '',	// No value is returned unless it's a developer account making the call
				'email_address'	=> $email,
				'ip_address'	=> $this->ip_address
			) );
			
			/* Action to perform */
			$key = "spam_service_action_{$spamCode}";
			$action = Settings::i()->$key;
			
			/* Perform Action */
			switch( $action )
			{
				/* Proceed with registration */
				case 1:
					break;
			
					/* Flag for admin approval */
				case 2:
					Settings::i()->reg_auth_type = 'admin';
					break;
			
					/* Approve the account, but ban it */
				case 3:
					$this->temp_ban = -1;
					$this->members_bitoptions['bw_is_spammer'] = TRUE;
					break;
			
					/* Deny registration - we return the code and the controller is expected to show an error */
				case 4:
					break;

					/* Moderate posts */
				case 5:
					$days = json_decode( Settings::i()->spam_service_days, TRUE );

					if ( isset( $days[ $spamCode ] ) )
					{
						if ( $days[ $spamCode ] == -1 )
						{
							$this->mod_posts = -1;
						}
						else if ( $days[ $spamCode ] > 0 )
						{
							$this->mod_posts = DateTime::ts( time() )->add( new DateInterval( "P{$days[ $spamCode ]}D" ) )->getTimestamp();
						}
					}
					break;
			}
		}

		/* GeoLocation */
		if ( $type === 'register' AND $action == 1 )
		{
			$action = $this->geoSpamCheck( $geoBlock );
		}

		/* If the normal spam service and geolocation didn't pick up anything, check for a disposable email. */
		if ( $type === 'register' AND $action == 1 )
		{
			$action = $this->disposableEmailCheck( $email, $disposable );
		}

		return $action;
	}

	/**
	 * GeoLocation Block Check
	 *
	 * @param	bool		$geoBlock		If set, will modify by reference if the user is in a moderated / blocked country.
	 * @return	int|NULL
	 */
	public function geoSpamCheck( bool &$geoBlock ): ?int
	{
		/* If Geo is enabled, don't bother. */
		if ( !Settings::i()->ipsgeoip )
		{
			return 1;
		}

		/* See we have anything configured. If we don't, don't bother. */
		$settings = Settings::i()->spam_geo_settings ? json_decode( Settings::i()->spam_geo_settings, true ) : array();

		if ( !count( $settings ) )
		{
			return 1;
		}

		try
		{
			$location = GeoLocation::getRequesterLocation();
		}
		catch( BadMethodCallException| Http\Request\Exception | RuntimeException |OutOfRangeException $e )
		{
			/* If it fails for any reason, don't bother. */
			Log::debug( $e, 'spam-service' );
			return 1;
		}

		/* All good, start checking. */
		if ( !isset( $settings[ $location->country ] ) )
		{
			/* Not in the list, we're good. */
			return 1;
		}

		/* See what action we're taking. */
		switch( $settings[$location->country] )
		{
			case 'moderate':
				Settings::i()->reg_auth_type = 'admin';
				$geoBlock = TRUE;
				return 2;

			case 'block':
				return 4;
		}

		return 1;
	}

	/**
	 * Check for disposable email address domain
	 *
	 * @param string $emailAddress 	Email to check, or NULL for current.
	 * @param bool $disposable		If set, will modify by reference if the user used a disposable email.
	 * @return	int|NULL
	 */
	public function disposableEmailCheck( string $emailAddress, bool &$disposable=FALSE ): ?int
	{
		$email = $emailAddress ?: $this->email;

		try
		{
			$response = Url::ips( 'spam/disposable' )->request()->login( Settings::i()->ipb_reg_number, '' )->post( array(
				'email'	=> $email,
			) );

			if ( $response->httpResponseCode != 200 )
			{
				throw new DomainException( print_r( $response, TRUE ) );
			}

			$result = (bool) $response->decodeJson()['result'];
		}
		catch ( Exception $e )
		{
			Log::debug( $e, 'spam-service' );
			$result = false;
		}

		$action = NULL;
		if ( $result )
		{
			$disposable = TRUE;

			$action = Settings::i()->spam_service_disposable;

			/* Perform Action */
			switch( $action )
			{
				/* Proceed with registration */
				case 1:
					break;

					/* Flag for admin approval */
				case 2:
					Settings::i()->reg_auth_type = 'admin';
					break;

					/* Approve the account, but ban it */
				case 3:
					$this->temp_ban = -1;
					$this->members_bitoptions['bw_is_spammer'] = TRUE;
					break;

					/* Deny registration - we return the code and the controller is expected to show an error */
				case 4:
					break;

					/* Moderate posts */
				case 5:
					$days = json_decode( Settings::i()->spam_service_days, TRUE );

					if ( isset( $days['disposable'] ) )
					{
						if ( $days['disposable'] == -1 )
						{
							$this->mod_posts = -1;
						}
						else if ( $days['disposable'] > 0 )
						{
							$this->mod_posts = DateTime::ts( time() )->add( new DateInterval( "P{$days['disposable']}D" ) )->getTimestamp();
						}
					}
					break;
			}
		}

		return $action;
	}

	/**
	 * Member Sync
	 *
	 * Use Event::fire instead
	 *
	 * @param string $method Method
	 * @param array $params Additional parameters to pass
	 * @return    void
	 * @throws Exception
	 * @deprecated
	 */
	public function memberSync( string $method, array $params=array() ) : void
	{
		/* Just in case someone calls this, redirect to the Event::fire */
		Event::fire( $method, $this, $params );
	}
			
	/**
	 * Merge
	 *
	 * @param Member $otherMember	Member to merge with
	 * @return	void
	 */
	public function merge( Member $otherMember ) : void
	{
		if ( $this === $otherMember )
		{
			throw new InvalidArgumentException( 'merge_self_error' );
		}
		
		/* Merge content */
		$otherMember->hideOrDeleteAllContent( 'merge', array( 'merge_with_id' => $this->member_id, 'merge_with_name' => $this->name ) );
		
		/* Log */
		$this->logHistory( 'core', 'account', array( 'type' => 'merge', 'id' => $otherMember->member_id, 'name' => $otherMember->name, 'email' => $otherMember->email ) );
		
		/* Let apps do their stuff */
		Event::fire( 'onMerge', $this, array( $otherMember ) );
	}
	
	/**
	 * Add profile visitor
	 *
	 * @param Member $visitor	Member that viewed profile
	 * @return	void
	 */
	public function addVisitor( Member $visitor ) : void
	{
		$visitors = json_decode( $this->pp_last_visitors, TRUE );
				
		/* If this member is already in the visitor list remove the entry so we can add back in the correct order */
		if( isset( $visitors[ $visitor->member_id ] ) )
		{
			unset( $visitors[ $visitor->member_id ] );
		}
		/* We want to limit to 5 members */
		else if ( is_array( $visitors ) AND count( $visitors ) >= 5 )
		{
			$visitors	= array_reverse( $visitors, TRUE );
			array_pop( $visitors );
			$visitors	= array_reverse( $visitors, TRUE );
		}
		
		/* Add the new entry */
		$visitors[ $visitor->member_id ] = time();
		
		/* Encode and save*/
		$this->pp_last_visitors = json_encode( $visitors );
		$this->save();
	}
	
	/**
	 * @brief	Posts Per Day Storage
	 */
	protected ?int $_ppdLimit = NULL;
	
	/**
	 * Check posts per day to see if this member can post.
	 *
	 * @return	bool
	 */
	public function checkPostsPerDay(): bool
	{
		/* Fetch our PPD limit - we should only need to do this once */
		if ( $this->_ppdLimit === NULL )
		{
			$this->_ppdLimit = $this->group['g_ppd_limit'];
		}
		/* We can't actually check guests as we can't store how often they have posted - simply counting content is not viable */
		if ( ! $this->member_id )
		{
			return TRUE;
		}
		
		/* Is there any limit at all? */
		if ( ! $this->_ppdLimit )
		{
			return TRUE;
		}
		
		$count	= $this->members_day_posts[0];
		$time	= $this->members_day_posts[1];
		
		/* Have we posted at all yet? */
		if ( ! $count OR ! $time )
		{
			return TRUE;
		}
		
		/* Are we beyond our 24 hours? */
		if ( $time AND $time < DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() )
		{
			/* Update member immediately */
			$this->members_day_posts = array( 0, 0 );
			$this->save();
			return TRUE;
		}
		
		/* Still within 24 hours... have we hit the limit? */
		if ( $count >= $this->_ppdLimit )
		{
			if ( $this->group['g_ppd_unit'] )
			{
				/* The limit may have been removed due to number of total posts or days since joining */
				if( $this->group['gbw_ppd_unit_type'] )
				{
					/* Days */
					if ( $this->joined->add( new DateInterval( "P{$this->group['g_ppd_unit']}D" ) )->getTimestamp() < time() )
					{
						return TRUE;
					}
				}
				else
				{
					/* Posts */
					if ( $this->member_posts >= $this->group['g_ppd_unit'] )
					{
						return TRUE;
					}
				}
			}
			
			return FALSE;
		}
		
		/* Still here? */
		return TRUE;
	}
	
	/**
	 * Check Group Promotion
	 *
	 * @return	void
	 */
	public function checkGroupPromotion() : void
	{
		/* If we should ignore promotions for this member, do so */
		if( $this->members_bitoptions['ignore_promotions'] )
		{
			return;
		}

		/* Default to member group if none. This shouldn't happen but can if Redis/MySQL is temporarily unavailable. The next save event will re-check promotion. */
		try
		{
			$primaryGroup = Group::load( $this->member_group_id );
		}
		catch ( OutOfRangeException $e )
		{
			/* Log the error as part of the failure audit trail */
			\IPS\Log::log( "Group promotion found a member (#{$this->member_id}: {$this->name} [Group ID {$this->member_group_id}] with an invalid member group", 'group_promotion' );
			return;
		}

		/* Just check the primary group, secondary groups should not prevent promoting */
		if( Group::load( $this->member_group_id )->g_promote_exclude )
		{
			return;
		}
		
		$ruleToUse = NULL;

		/* Loop over all group promotion rules and get the last one that matches us */
		foreach( GroupPromotion::roots() as $rule )
		{
			if( $rule->enabled and $rule->matches( $this ) )
			{
				$ruleToUse = $rule->id;
			}
		}

		/* If there's no rule, return now */
		if( $ruleToUse === NULL )
		{
			return;
		}

		/* If we matched a rule, get that rule now */
		$ruleToUse	= GroupPromotion::load( $ruleToUse );

		/* Set the primary and secondary groups as appropriate */
		$action = $ruleToUse->_actions;

		if( $action['primary_group'] AND $this->member_group_id != $action['primary_group'] )
		{
			try
			{
				$group = Group::load( $action['primary_group'] );

				/* Need to store the history before we actually change the group, otherwise history shows "promoted from group X to X" */
				$this->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'promotion', 'id' => $ruleToUse->id, 'old' => $this->member_group_id, 'new' => $action['primary_group'] ) );

				$this->member_group_id = $action['primary_group'];
			}
			catch ( OutOfRangeException $e )
			{
				Log::debug( 'Promotion ' .  $ruleToUse->id . ' tried to set not existing member group' );
			}
		}

		if( count( $action['secondary_group'] ) OR count( $action['secondary_remove'] ) )
		{
			$secondaryGroups = array_filter( explode( ',', $this->_data['mgroup_others'] ) );
			$oldSecondaryGroups = $secondaryGroups;

			foreach ( $action['secondary_group'] as $key => $group )
			{
				try
				{
					$group = Group::load( $group );
				}
				catch ( OutOfRangeException $e )
				{
					unset( $action['secondary_group'][$key] );
					Log::debug( 'Promotion ' .  $ruleToUse->id . ' tried to set not existing member group' );
				}
			}
			if( count( $action['secondary_group'] ) )
			{
				if( array_diff( $action['secondary_group'], $secondaryGroups ) )
				{
					$secondaryGroups = array_merge( $secondaryGroups, $action['secondary_group'] );
				}
			}

			if( count( $action['secondary_remove'] ) )
			{
				foreach( $action['secondary_remove'] as $groupToRemove )
				{
					while( in_array( $groupToRemove, $secondaryGroups ) )
					{
						$key = array_search( $groupToRemove, $secondaryGroups );

						unset( $secondaryGroups[ $key ] );
					}
				}
			}
			
			if ( array_diff( $secondaryGroups, $oldSecondaryGroups ) or array_diff( $oldSecondaryGroups, $secondaryGroups ) )
			{
				$this->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'promotion', 'id' => $ruleToUse->id, 'old' => $oldSecondaryGroups, 'new' => $secondaryGroups ) );
			}

			$this->mgroup_others = implode( ',', array_unique( $secondaryGroups ) );
		}
	}

	/**
	 * Is the current user allowed to use the contact us form
	 *
	 * @return bool
	 */
	public function canUseContactUs(): bool
	{
		try
		{
			$module = Module::get( 'core', 'contact', 'front' );
		}
		catch ( OutOfRangeException $e )
		{
			return FALSE;
		}
		
		if ( !$this->canAccessModule( $module ) )
		{
			return FALSE;
		}

		/* If all groups have access, we can */
		if( Settings::i()->contact_access != '*' )
		{
			/* Check member */
			$memberGroups	= array_merge( array( $this->member_group_id ), array_filter( explode( ',', $this->mgroup_others ) ) );
			$accessGroups	= explode( ',', Settings::i()->contact_access );

			/* Are we in an allowed group? */
			if( count( array_intersect( $accessGroups, $memberGroups ) ) )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Can use the messenger system
	 *
	 * @return bool
	 */
	public function canUseMessenger(): bool
	{
		/* Test member */
		if ( $this->members_disable_pm == 2 )
		{
			return false;
		}

		/* Test module permissions */
		return $this->canAccessModule( Module::get( 'core', 'messaging' ) );
	}

	/**
	 * Get a unique member hash for use in RSS keys and such.
	 *
	 * If the member has a current style password hash, the md5() of that value will be returned. Otherwise,
	 * one will be generated, stored in a new column in the members table, and its md5() will be returned.
	 *
	 * @return	string
	 */
	public function getUniqueMemberHash(): string
	{
		/* If this is a guest, just return a random string. */
		if ( !$this->member_id )
		{
			return Login::generateRandomString();
		}
		
		/* Return the password hash if we already have it */
		if ( !empty( $this->members_pass_hash ) )
		{
			return md5( ( $this->members_pass_hash ?: $this->email ) . $this->members_pass_salt );
		}

		/* Do we already have one? */
		if ( !empty( $this->unique_hash ) )
		{
			return $this->unique_hash;
		}

		/* Otherwise, generate one */
		$this->unique_hash = md5( Login::generateRandomString(32) );

		$this->save();

		return $this->unique_hash;
	}
	
	/**
	 * Perform a database update on all members
	 *
	 * Typically, updating the entire table locks the table which makes other queries stack up.
	 * On busy sites this is a real problem. We mitigate this by updating in batches via a background task
	 *
	 * We replace if the fields we want to update are the exact same on a subsequent call to this method, so if you had ( 'skin' => 2 ) and then ( 'skin' => 3 ),
	 * the row matching 'skin' => 2 will be removed from the queue table and the 'skin' => 3 row will replace it. This is to ensure that if you update the same fields while
	 * an existing task is running, it will update the members with the latest data.
	 *
	 * @note This task can be processing or waiting to process and the member can still change the value, so it could be possible for the member to set a 'skin' parameter and then have this
	 * overwritten when this task processes.
	 *
	 * @param array $update		array( field => value ) pairs to be used directly in a \IPS\Db::i()->update( 'core_members', $update ) query
	 * @param int $severity	Severity level. 1 being highest, 5 lowest
	 * @return	void
	 */
	public static function updateAllMembers( array $update, int $severity=3 ) : void
	{
		Task::queue( 'core', 'UpdateMembers', array( 'update' => $update ), $severity, array( 'update' ) );
	}
	
	/**
	 * Invalidate all sessions and auto-login-keys. Called after the user changes their email address or password.
	 *
	 * @param bool|string $frontEndSessions	Boolean value indicating if front-end sessions should be cleared, or a string containing a session ID to wipe all except that one
	 * @param bool|string $acpSessions		Boolean value indicating if acp sessions should be cleared, or a string containing a session ID to wipe all except that one
	 * @param bool $loginKeys			Boolean value indicating if login keys (used for "Remember Me" logins) should be wiped
	 * @return	void
	 */
	public function invalidateSessionsAndLogins( bool|string $frontEndSessions=TRUE, bool|string $acpSessions=TRUE, bool $loginKeys=TRUE ) : void
	{
		/* Terminate any active sessions */
		if ( $frontEndSessions !== FALSE )
		{
			Session\Store::i()->deleteByMember( $this->member_id, NULL, $frontEndSessions !== TRUE ? array( $frontEndSessions ) : NULL );
		}
		if ( $acpSessions !== FALSE )
		{
			$where = array( array( 'session_member_id=?', $this->member_id ) );
			if ( is_string( $acpSessions ) )
			{
				$where[] = array( 'session_id<>?', $acpSessions );
			}
			Db::i()->delete( 'core_sys_cp_sessions', $where );
		}
		
		/* Wipe login keys to stop "Remember Me" cookies automatically logging us in */
		if ( $loginKeys )
		{
			Db::i()->update( 'core_members_known_devices', array( 'login_key' => NULL ), array( 'member_id=?', $this->member_id ) );
		}
		
		/* Invalidate any pending "Forgot Password" or 2FA recovery emails, because they provide a doorway into accessing the account */
		Db::i()->delete( 'core_validating', array( 'member_id=? AND ( lost_pass=1 OR forgot_security=1 )', $this->member_id ) );
	}
	
	/* !Registration/Validation */
	
	/**
	 * Call after completed registration to send email for validation if required or flag for admin validation
	 *
	 * @param bool $noEmailValidationRequired	If the user's email is implicitly trusted (for example, provided by a third party), set this to TRUE to bypass email validation
	 * @param bool $doNotDelete				If TRUE, the account will not be deleted in the normal cleanup of unvalidated accounts. Used for accounts created in Commerce checkout.
	 * @param array|null $postBeforeRegister			The row from core_post_before_registering if applicable
	 * @param Url|null $refUrl						The URL the user should be redirected to after validation
	 * @return	void
	 */
	public function postRegistration( bool $noEmailValidationRequired=FALSE, bool $doNotDelete=FALSE, array $postBeforeRegister = NULL, Url $refUrl = NULL ) : void
	{
		/* Work out validation type */
		$validationType = Settings::i()->reg_auth_type;
		if ( $noEmailValidationRequired )
		{
			switch ( $validationType )
			{
				case 'user':
					$validationType = 'none';
					break;
				case 'admin_user':
					$validationType = 'admin';
					break;
			}
		}
		
		/* Validation */
		if ( $validationType != 'none' )
		{
			/* Set the flag */
			$this->members_bitoptions['validating'] = TRUE;
			$this->save();
			
			/* Prevent duplicates from double clicking, etc */
			Db::i()->delete( 'core_validating', array( 'member_id=? and new_reg=1', $this->member_id ) );
			
			/* Insert a record */
			$vid = md5( $this->members_pass_hash . Login::generateRandomString() );
			$plainSecurityKey = Login::generateRandomString();
			Db::i()->insert( 'core_validating', array(
				'vid'		   	=> $vid,
				'member_id'	 	=> $this->member_id,
				'entry_date'	=> time(),
				'new_reg'	   	=> 1,
				'ip_address'	=> $this->ip_address,
				'spam_flag'	 	=> ( $this->members_bitoptions['bw_is_spammer'] ) ?: FALSE,
				'user_verified' => $validationType == 'admin',
				'email_sent'	=> ( $validationType != 'admin' ) ? time() : NULL,
				'do_not_delete'	=> $doNotDelete,
				'ref'			=> $refUrl ? ( (string) $refUrl ) : NULL,
				'security_key'  => Encrypt::fromPlaintext( $plainSecurityKey )->tag()
			) );
			
			/* Send email for validation */
			if ( $validationType != 'admin' )
			{
				Email::buildFromTemplate( 'core', 'registration_validate', array( $this, $vid, $plainSecurityKey ), Email::TYPE_TRANSACTIONAL )->send( $this );
			}
			
			/* Update core_post_before_registering */
			if ( $postBeforeRegister )
			{
				Db::i()->update( 'core_post_before_registering', array( 'member' => $this->member_id ), array( 'secret=?', $postBeforeRegister['secret'] ) );
			}
		}
		
		/* If no email-related validation is required, send admin notification */
		if ( $validationType == 'admin' )
		{
			AdminNotification::send( 'core', 'NewRegValidate', NULL, TRUE, $this );
		}
		elseif ( $validationType == 'none' )
		{
			AdminNotification::send( 'core', 'NewRegComplete', NULL, TRUE, $this );
		}

		/* Send emails and handle post before register if validation is disabled */
	   if( $validationType == 'none' )
	   {
	   		$secret = $postBeforeRegister['secret'] ?? NULL ;
			$this->_processPostBeforeRegistering( $secret );
			$this->_sendWelcomeEmail();
			$this->_sendReferralNotification();
	   }
   }
   	
	/**
	 * Email Validation Confirmed
	 *
	 * @param array $record		validating record
	 * @return	void
	 */
	public function emailValidationConfirmed( array $record ) : void
	{
		/* Log */
		$this->logHistory( 'core', 'account', array( 'type' => 'email_validated' ) );
		
		/* If admin validation is required, set the flag and send an admin notification */
		if ( Settings::i()->reg_auth_type == 'admin_user' )
		{
			AdminNotification::send( 'core', 'NewRegValidate', NULL, TRUE, $this );
			
			Db::i()->update( 'core_validating', array( 'user_verified' => TRUE ), array( 'member_id=?', $this->member_id ) );
		}
		
		/* Otherwise, validation is complete */
		else
		{
			$this->validationComplete();
		}		
	}
	
	/**
	 * Final Validation Complete
	 * If no validation is enabled: this is never called
	 * If email-only validation is enabled: this is called after the user has validated their email address or if the admin manually validates the account
	 * If admin (including email and admin) validation is enabled: this is called after the admin has validated the account
	 *
	 * @param FALSE|Member $initiatedByAdmin			If an administrator performed the validation, the admin that did so
	 * @param string|null $postBeforeRegisterSecret	The secret from core_post_before_register, if applicable
	 * @return	void
	 */
	public function validationComplete( Member|bool $initiatedByAdmin=FALSE, string $postBeforeRegisterSecret=NULL ) : void
	{
		/* Process any posts made before registering */
		$this->_processPostBeforeRegistering( $postBeforeRegisterSecret );
		
		/* Send a success email */
		$this->_sendWelcomeEmail();

		/* Send referral notification */
		$this->_sendReferralNotification();
		
		/* Send an admin notification */
		AdminNotification::send( 'core', 'NewRegComplete', NULL, TRUE, $this, !$initiatedByAdmin );
		
		/* Delete rows */
		Db::i()->delete( 'core_validating', array( 'member_id=?', $this->member_id ) );
		
		/* Reset the flag */
		$this->members_bitoptions['validating'] = FALSE;
		$this->save();

		Webhook::fire( 'member_registration_complete', $this, $this->webhookFilters() );

		/* Sync */
		Event::fire( 'onValidate', $this );
	}
	
	/**
	 * Process any posts made before registering
	 *
	 * @param string|null $secret	The secret, if just created (necessary for avoiding R/W separation issues if no validation is required)
	 * @return	void
	 */
	protected function _processPostBeforeRegistering( string $secret = NULL ) : void
	{		
		$where = $secret ? array( '`member`=? OR secret=?', $this->member_id, $secret ) : array( '`member`=?', $this->member_id );
		
		foreach (Db::i()->select( '*', 'core_post_before_registering', $where ) as $row )
		{
			Db::i()->delete( 'core_post_before_registering', array( 'class=? AND id=?', $row['class'], $row['id'] ) );
			
			try
			{
				$class = $row['class'];
				$content = $class::load( $row['id'] );
				
				if ( $content->author()->member_id )
				{
					throw new OutOfRangeException;
				}
				
				$content->changeAuthor( $this, FALSE );

				$moderated = FALSE;
				if ( $content instanceof Review AND IPS::classUsesTrait( $content->item(), 'IPS\Content\Hideable' ) )
				{
					$moderated = $content->item()->moderateNewReviews( $this );
				}
				elseif ( $content instanceof Comment AND IPS::classUsesTrait( $content->item(), 'IPS\Content\Hideable' ) )
				{
					$moderated = $content->item()->moderateNewComments( $this );
				}
				elseif( IPS::classUsesTrait( $content, 'IPS\Content\Hideable' ) )
				{
					$moderated = $content::moderateNewItems( $this, $content->containerWrapper(), FALSE );
				}

				/* Do additional processing */
				$moderated = Bridge::i()->cloudPbrProcessMember( $this, $content, $moderated );
				
				if ( $moderated )
				{
					if ( isset( $content::$databaseColumnMap['hidden'] ) )
					{
						$column = $content::$databaseColumnMap['hidden'];
						$content->$column = 1;
					}
					elseif ( isset( $content::$databaseColumnMap['approved'] ) )
					{
						$column = $content::$databaseColumnMap['approved'];
						$content->$column = 0;
					}
										
					$content->save();
					$content->sendUnapprovedNotification();
					
					$container = NULL;
					if ( $content instanceof Comment )
					{
						if ( $content instanceof Review )
						{
							$content->item()->resyncReviewCounts();
						}
						else
						{
							$content->item()->resyncCommentCounts();
						}
						$content->item()->save();
						$container = $content->item()->containerWrapper();
					}
					else
					{
						$container = $content->containerWrapper();
					}
					
					if ( $container )
					{
						$container->resetCommentCounts();
						$container->save();
					}
				}
				else
				{
					/* Set the value so that checkProfanityFilters() thinks it's good */
					$column = NULL;
					if ( isset( $content::$databaseColumnMap['hidden'] ) )
					{
						$column = $content::$databaseColumnMap['hidden'];
						$oldVal = $content->$column;
						$content->$column = 0;
					}
					elseif ( isset( $content::$databaseColumnMap['approved'] ) )
					{
						$column = $content::$databaseColumnMap['approved'];
						$oldVal = $content->$column;
						$content->$column = 1;
					}
					
					/* Run through checkProfanityFilters() */
					if ( $content->checkProfanityFilters( $content instanceof Comment and $content->item()::$firstCommentRequired and $content->isFirst() ) )
					{
						$content->sendUnapprovedNotification();
					}
					else
					{
						if ( $column )
						{
							$content->$column = $oldVal;
						}
						
						$content->unhide( FALSE );
					}
				}
			}
			catch ( OutOfRangeException $e ) { }
		}
	}

	/**
	 * Send a welcome email
	 * Called if no validation is enabled or after validation is complete
	 *
	 * @return	void
	 */
	protected function _sendWelcomeEmail() : void
	{
		try
		{
			Email::buildFromTemplate( 'core', 'registration_complete', array( $this ), Email::TYPE_TRANSACTIONAL )->send( $this );
		}
		catch( ErrorException $e ) { }
	}

	/**
	 * Send a referral notification to referrer
	 * Called if no validation is enabled or after validation is complete
	 *
	 * @return	void
	 */
	protected function _sendReferralNotification() : void
	{
		try
		{
			$referrer = Db::i()->select( 'referred_by', 'core_referrals', array( 'member_id=?', $this->member_id ) )->first();
			$notification = new Notification( Application::load( 'core' ), 'referral', $this, array( $this ) );
			$notification->recipients->attach( Member::load( $referrer ) );
			$notification->send();
		}
		catch( OutOfRangeException | UnderflowException $e ) {}
	}

	/**
	 * Get how often the member changed his name
	 *
	 * @return bool|int
	 */
	public function hasNameChanges(): bool|int
	{
		try
		{
			return Db::i()->select( 'count(*)', 'core_member_history', array( 'log_member=? AND log_app=? AND log_type=?', $this->member_id, 'core', 'display_name' ) )->first();
		}
		catch ( UnderflowException $e )
		{
			return FALSE;
		}
	}
		
	/**
	 * Profile Sync
	 *
	 * @return	void
	 */
	public function profileSync(): void
	{		
		$profileSync = $this->profilesync;
		
		foreach (Login::methods() as $method )
		{
			if ( $method->canProcess( $this ) )
			{
				foreach ( $method->forceSync() as $type )
				{
					if ( !isset( $profileSync[ $type ] ) or $profileSync[ $type ]['handler'] !== $method->_id )
					{
						$profileSync[ $type ] = array( 'handler' => $method->_id, 'ref' => NULL, 'error' => NULL );
					}
				}
			}
		}
						
		if ( is_array( $profileSync ) )
		{
			foreach ( $profileSync as $k => $v )
			{
				try
				{
					$method = Handler::load( $v['handler'] );
				}
				catch ( OutOfRangeException $e )
				{
					unset( $profileSync[ $k ] );
					continue;
				}

				/* Check the method is enabled */
				if( !$method->_enabled )
				{
					continue;
				}

				/* Check we're syncing this item */
				if ( !in_array( $k, $method->forceSync() ) and !in_array( $k, $method->syncOptions( $this ) ) )
				{
					continue;
				}

				try
				{
					$profileSync[ $k ]['error'] = NULL;

					switch ( $k )
					{
						case 'email':
							$email = $method->userEmail( $this );
							if ( $email and $email != $this->email )
							{
								if ( $error = Login::emailIsInUse( $email, $this ) )
								{
									throw new DomainException('member_email_exists');
								}

								foreach (Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'email' ) ) as $bannedEmail )
								{
									if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedEmail, '/' ) ) . '$/i', $this->value ) )
									{
										throw new DomainException( 'form_email_banned' );
									}
								}

								if ( Settings::i()->allowed_reg_email !== '' AND $allowedEmailDomains = explode( ',', Settings::i()->allowed_reg_email )  )
								{
									$matched = FALSE;
									foreach ( $allowedEmailDomains AS $domain )
									{
										if( mb_stripos( $email,  "@" . $domain ) !== FALSE )
										{
											$matched = TRUE;
										}
									}

									if ( count( $allowedEmailDomains ) AND !$matched )
									{
										throw new DomainException( 'form_email_banned' );
									}
								}

								$this->logHistory( 'core', 'email_change', array( 'old' => $this->email, 'new' => $email, 'by' => 'profilesync', 'id' => $method->id, 'service' => $method::getTitle() ) );
								$this->email = $email;
							}
							break;

						case 'name':
							$name = $method->userProfileName( $this );
							if ( $name and $name != $this->name )
							{
								if ( mb_strlen( $name ) < Settings::i()->min_user_name_length )
								{
									throw new DomainException('form_minlength_unspecific');
								}
								if ( mb_strlen( $name ) > Settings::i()->max_user_name_length )
								{
									throw new DomainException('form_minlength_unspecific');
								}

								if ( !Login::usernameIsAllowed( $name ) )
								{
									throw new DomainException('form_name_banned');
								}

								if ( Login::usernameIsInUse( $name, $this ) )
								{
									throw new DomainException('member_name_exists');
								}

								foreach(Db::i()->select( 'ban_content', 'core_banfilters', array("ban_type=?", 'name') ) as $bannedName )
								{
									if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedName, '/' ) ) . '$/i', $this->value ) )
									{
										throw new DomainException( 'form_name_banned' );
									}
								}

								$this->logHistory( 'core', 'display_name', array( 'old' => $this->name, 'new' => $name, 'by' => 'profilesync', 'id' => $method->id, 'service' => $method::getTitle() ) );
								$this->name = $name;
							}
							break;

						case 'photo':
							$photoUrl = $method->userProfilePhoto( $this );
							if ( (string) $photoUrl )
							{
								/* Make sure we have a scheme */
								if( !$photoUrl->data[ Url::COMPONENT_SCHEME ] )
								{
									$photoUrl = $photoUrl->setScheme( 'https' );
								}

								$contents = $photoUrl->request()->get();
								$md5 = md5( $contents );

								if ( $contents AND ( !isset( $v['ref'] ) or $md5 != $v['ref'] ) )
								{
									$photoVars = explode( ':', $this->group['g_photo_max_vars'] );

									try
									{
										$image = Image::create( $contents );
									}
									catch( Exception $e )
									{
										throw new DomainException('member_photo_bad_url');
									}
									if( $image->isAnimatedGif and !$this->group['g_upload_animated_photos'] )
									{
										throw new DomainException('member_photo_upload_no_animated');
									}
									if ( $image->width > $photoVars[1] or $image->height > $photoVars[2] )
									{
										$image->resizeToMax( $photoVars[1], $photoVars[2] );
									}
									if ( $photoVars[0] and strlen( $image ) > ( $photoVars[0] * 1024 ) )
									{
										throw new DomainException('upload_too_big_unspecific');
									}

									$newFile = File::create( 'core_Profile', 'imported-photo-' . $this->member_id . '.' . $image->type, (string) $image );

									$this->pp_photo_type  = 'custom';
									$this->pp_main_photo  = (string) $newFile;
									$thumbnail = $newFile->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE, TRUE );
									$this->pp_thumb_photo = (string) $thumbnail;
									if ( isset( $v['ref'] ) )
									{
										$this->photo_last_update = time();
									}
									$this->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => 'profilesync', 'id' => $method->id, 'service' => $method::getTitle() ) );

									$profileSync['photo']['ref'] = $md5;
								}
							}
							break;

						case 'cover':
							/* Sync the cover photo only if the user has permission to have one */
							if ( $this->group['gbw_allow_upload_bgimage'] )
							{
								$coverPhotoUrl = $method->userCoverPhoto( $this );
								if ( (string) $coverPhotoUrl )
								{
									/* Make sure we have a scheme */
									if( !$coverPhotoUrl->data[ Url::COMPONENT_SCHEME ] )
									{
										$coverPhotoUrl = $coverPhotoUrl->setScheme( 'https' );
									}

									$contents = $coverPhotoUrl->request()->get();
									$md5 = md5( $contents );

									if ( !isset( $v['ref'] ) or $md5 != $v['ref'] )
									{
										try
										{
											$image = Image::create( $contents );
										}
										catch( Exception $e )
										{
											throw new DomainException('member_photo_bad_url');
										}

										if ( $this->group['g_max_bgimg_upload'] != -1 and strlen( $image ) > ( $this->group['g_max_bgimg_upload'] * 1024 ) )
										{
											throw new DomainException('upload_too_big_unspecific');
										}

										$newFile = File::create( 'core_Profile', 'imported-cover-photo-' . $this->member_id . '.' . $image->type, (string) $image );

										if ( $this->pp_cover_photo )
										{
											try
											{
												File::get( 'core_Profile', $this->pp_cover_photo )->delete();
											}
											catch ( Exception $e ) { }
										}

										$this->pp_cover_photo = (string) $newFile;
										$this->logHistory( 'core', 'photo', array( 'action' => 'new', 'type' => 'profilesync', 'id' => $method->id, 'service' => $method::getTitle() ) );

										$profileSync['cover']['ref'] = $md5;
									}
								}
							}
							break;
					}

				}
				catch ( Login\Exception $e )
				{
					unset( $profileSync[ $k ] );
				}
				catch ( DomainException $e )
				{
					$profileSync[ $k ]['error'] = $e->getMessage();
				}
				catch ( Exception $e )
				{
					Log::log( $e, 'profilesync' );
					$profileSync[ $k ]['error'] = 'profilesync_generic_error';
				}
			}

		}
				
		$this->profilesync = $profileSync;
		$this->profilesync_lastsync = time();
		$this->save();
	}

	/**
	 * Can this member be ignored?
	 *
	 * @return bool
	 */
	public function canBeIgnored(): bool
	{
		if ( !Settings::i()->ignore_system_on )
		{
			return FALSE;
		}
		
		if( !$this->member_id )
		{
			return FALSE;
		}

		if ( $this->modPermission() AND !$this->modPermission( 'can_moderator_be_ignored' ) )
		{
			return FALSE;
		}

		if ( $this->group['gbw_cannot_be_ignored'] )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Log Member Action
	 *
	 * @param	mixed		$app			The application action applies to
	 * @param string $type			Log type
	 * @param mixed|null $extra			Any extra data for the type
	 * @param mixed|null $by				The member performing the action. NULL for currently logged in member or FALSE for no member
	 * 
	 * @return	void
	 */
	public function logHistory( mixed $app, string $type, mixed $extra=NULL, mixed $by=NULL ) : void
	{
		if ( $this->member_id )
		{
			/* Set this only if this was not called by the task system, otherwise we'll set the member who triggered the task as moderator   */
			if ( Dispatcher::hasInstance() and !Dispatcher::i()->inDestructor and $by === NULL  )
			{
				$by = Session::i()->member; // Not \IPS\Member::loggedIn() because if this is an admin logged in as a member, we want to log that the action was done by the admin
			}

			Db::i()->insert( 'core_member_history', array(
				'log_app'			=> $app,
				'log_member'		=> (int) $this->member_id,
				'log_by'			=> $by ? $by->member_id : NULL,
				'log_type'			=> $type,
				'log_data'			=> json_encode( $extra ),
				'log_date'			=> microtime( TRUE ),
				'log_ip_address'	=> Request::i()->ipAddress()
			) );
		}
	}
	
	/* !Top Members */
	/**
	 * @brief	Top members overview
	 */
	const TOP_MEMBERS_OVERVIEW = 1;

	/**
	 * @brief	Top members with filters
	 */
	const TOP_MEMBERS_FILTERS = 2;
	
	/**
	 * @brief	Custom count for a top member result
	 */
	public ?int $_customCount = NULL;
	
	/**
	 * Get available Top Members options
	 *
	 * @param int $filter		See TOP_MEMBERS_* constants
	 * @return	array
	 */
	public static function topMembersOptions( int $filter = 0 ): array
	{
		$filters = array(
			'pp_reputation_points' => Member::loggedIn()->language()->addToStack('leaderboard_tab_reputation'),
			'member_posts' => Member::loggedIn()->language()->addToStack('leaderboard_tab_posts')
		);
		
		foreach (Application::allExtensions( 'core', 'ContentRouter', TRUE ) as $object )
		{
			foreach( $object->classes as $item )
			{
				$commentClass = NULL;
				if ( isset( $item::$commentClass ) )
				{
					$commentClass = $item::$commentClass;
				}
				
				if ( $item::$firstCommentRequired and isset( $commentClass::$databaseColumnMap['author'] ) )
				{
					$filters[ $commentClass ] = Member::loggedIn()->language()->addToStack( 'leaderboard_tab_x', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack("{$commentClass::$title}_pl_lc") ) ) );
				}
				elseif ( isset( $item::$databaseColumnMap['author'] ) )
				{
					$filters[ $item ] = Member::loggedIn()->language()->addToStack( 'leaderboard_tab_x', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack("{$item::$title}_pl_lc") ) ) );
				}
			}
		}
		
		if ( $filter )
		{
			$available = $filter === static::TOP_MEMBERS_OVERVIEW ? Settings::i()->reputation_top_members_overview : Settings::i()->reputation_top_members_filters;
			if ( $available != '*' )
			{
				$available = explode( ',', $available );
				$filters = array_filter( $filters, function( $k ) use ( $available ) {
					return in_array( $k, $available );
				}, ARRAY_FILTER_USE_KEY );
			}
		}
		
		return $filters;
	}
	
	/**
	 * Get top members for a particular type
	 *
	 * @param string $type	The type (as returned by topMembersOptions())
	 * @param int $limit	Number to get
	 * @return	ActiveRecordIterator|array
	 */
	public static function topMembers( string $type, int $limit ): ActiveRecordIterator|array
	{
		if ( in_array( $type, array( 'pp_reputation_points', 'member_posts' ) ) )
		{
			$where = array(
				array( "completed=1" ),
				array( "temp_ban != -1" ),
				array( $type . '>0' ),
				Db::i()->in( 'member_group_id', explode( ',', Settings::i()->leaderboard_excluded_groups ), TRUE )
			);
			$orderBy = $type . ' DESC';
			
			return new ActiveRecordIterator( Db::i()->select( '*', 'core_members', $where, $orderBy, $limit ), 'IPS\Member' );
		}
		else
		{
			$storeKey = 'store_' . str_replace( '\\', '-', $type );
			$stored = isset( Store::i()->$storeKey ) ? Store::i()->$storeKey : NULL;
			
			if ( ! $stored or ( ( time() - $stored['time'] ) > 300 ) )
			{
				/* @var Content|Member $type */
				$members = array();
				foreach( $type::topMembersQuery( $limit ) as $row )
				{
					/* @var $databaseColumnMap array */
					$members[ $row[ $type::$databasePrefix . $type::$databaseColumnMap['author'] ] ] = $row['count'];
				}
				
				/* Make sure these members actually exist */
				$memberIds = array();

				/* Make sure they're not in an excluded group */
				$memberWhere = array();
				$memberWhere[] = Db::i()->in( 'member_id', array_keys( $members ) );
				$memberWhere[] = Db::i()->in( 'member_group_id', explode( ',', Settings::i()->leaderboard_excluded_groups ), TRUE );

				foreach(Db::i()->select( 'member_id', 'core_members', $memberWhere ) AS $member_id )
				{
					$memberIds[ $member_id ] = $members[ $member_id ];
				}
			
				Store::i()->$storeKey = array( 'time' => time(), 'memberIds' => $memberIds );
				$stored = Store::i()->$storeKey;
			}

			$results = array();
			foreach (new ActiveRecordIterator( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_keys( $stored['memberIds'] ) ), NULL, $limit ), 'IPS\Member' ) as $member )
			{
				$member->_customCount = $stored['memberIds'][ $member->member_id ];
				$results[ $member->member_id ] = $member;
			}
			
			/* Sort by custom value DESC */
			uasort( $results, function( $a, $b ) {
				return ( $a->_customCount == $b->_customCount ) ? 0 : ( ( $a->_customCount < $b->_customCount ) ? 1 : -1 );
			});

			/* Preload follower data */
			if( count( $results ) )
			{
				Member::loggedIn()->preloadMemberFollowers( array_keys( $results ) );
			}

			return $results;
		}
	}

	/**
	 * Preload follower information for top members
	 *
	 * @note	Allows us to run two queries instead of two per member
	 * @param array $memberIds	Member IDs to preload
	 * @return	void
	 */
	public function preloadMemberFollowers( array $memberIds ) : void
	{
		/* Preload the "are we following" flag */
		foreach( $memberIds as $memberId )
		{
			$this->_following[ md5( 'coremember' . $memberId ) ]	= FALSE;
		}

		foreach(Db::i()->select( 'follow_rel_id', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id IN(' . implode( ',', $memberIds ) . ') AND follow_member_id=?', 'core', 'member', $this->member_id ) ) as $followerId )
		{
			$this->_following[ md5( 'coremember' . $followerId ) ]	= TRUE;
		}

		/* And then preload the total follower counts */
		static::_followersCount( 'member', $memberIds );
	}
	
	/* !Profile Completion */
	
	/**
	 * @brief	Profile Completion Cache
	 */
	public ?array $_profileCompletion = NULL;
	
	/**
	 * Returns suggested profile items
	 *
	 * @return	array|null
	 */
	public function profileCompletion(): ?array
	{
		if ( $this->_profileCompletion === NULL )
		{
			$this->_profileCompletion = array();
			$this->_profileCompletion['required'] = array();
			$this->_profileCompletion['suggested'] = array();
			
			if ( $this->member_id AND !$this->members_bitoptions['profile_completed'] )
			{
				foreach( Member\ProfileStep::loadAll() AS $id => $step )
				{
					if( !$step->canComplete( $this ) )
					{
						continue;
					}

					$this->_profileCompletion[ ( $step->required ) ? 'required' : 'suggested' ][ $step->id ] = $step->completed( $this );
				}
			}
		}
		
		return $this->_profileCompletion;
	}
	
	/**
	 * Profile Completion Percentage
	 *
	 * @return	Number
	 */
	public function profileCompletionPercentage(): Number
	{
		if ( $this->members_bitoptions['profile_completed'] )
		{
			return new Number( '100' );
		}
		
		$total	= 0;
		$done	= 0;
		
		/* Check Required Ones */
		$completion = $this->profileCompletion();
		foreach( $completion['required'] AS $key => $value )
		{
			$total++;
			if ( $value )
			{
				$done++;
			}
		}
		
		/* Now Suggested */
		foreach( $completion['suggested'] AS $key => $value )
		{
			$total++;
			if ( $value )
			{
				$done++;
			}
		}
		
		/* Are we actually done? */
		if ( $done === $total AND $this->members_bitoptions['profile_completed'] === FALSE )
		{
			$this->members_bitoptions['profile_completed'] = TRUE;
			$this->save();
		}
		
		if ( $this->members_bitoptions['profile_completed'] )
		{
			return new Number( '100' );
		}
		elseif( !$total )
		{
			return new Number( '100' );
		}
		else
		{
			return new Number( (string) round( 100 / $total * $done ) );
		}
	}
	
	/**
	 * Next Profile Step
	 *
	 * @return	ProfileStep|NULL
	 */
	public function nextProfileStep(): ?ProfileStep
	{
		if ( $this->members_bitoptions['profile_completed'] )
		{
			return NULL;
		}
		
		$completed = $this->profileCompletion();
		
		if ( !count( $completed['suggested'] ) )
		{
			return NULL;
		}
		
		$nextStep = NULL;
		foreach( $completed['suggested'] AS $id => $complete )
		{
			if ( !$complete )
			{
				$nextStep = ProfileStep::load( $id );
				break;
			}
		}
		
		return $nextStep;
	}

	/**
	 * Determine if the member can use signatures
	 *
	 * @return bool
	 */
	public function canEditSignature(): bool
	{
		/* If signatures are globally disabled, we can't edit them */
		if( !Settings::i()->signatures_enabled )
		{
			return FALSE;
		}

		/* Are they enabled for our group? */
		$sigLimits = explode( ":", $this->group['g_signature_limits'] );

		if( $sigLimits[0] OR $sigLimits[5] == 0 )
		{
			return FALSE;
		}

		/* Are there post count or day restrictions? */
		if( $this->group['g_sig_unit'] )
		{
			if( $this->group['gbw_sig_unit_type'] )
			{
				if ( $this->joined->diff( DateTime::create() )->days < $this->group['g_sig_unit'] )
				{
					return FALSE;
				}
			}
			else
			{
				if ( $this->member_posts < $this->group['g_sig_unit'] )
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Produce a random hex color for a background
	 *
	 * @return string
	 */
	public function coverPhotoBackgroundColor(): string
	{
		return $this->staticCoverPhotoBackgroundColor( $this->name );
	}

	/**
	 * returns the recent profile visitors
	 *
	 * @return array
	 */
	public function get_profileVisitors(): array
	{
		$visitors = array();
		$visitorData = array();
		$visitorInfo = json_decode( $this->pp_last_visitors, TRUE );
		if ( !is_array( $visitorInfo ) )
		{
			$visitorInfo = array();
		}

		foreach(new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', array_keys( array_reverse( $visitorInfo, TRUE ) ) ) ) ), 'IPS\Member' ) AS $row )
		{
			$visitorData[$row->member_id] = $row;
		}

		foreach( array_reverse( $visitorInfo, TRUE ) as $id => $time )
		{
			if ( isset( $visitorData[$id] ) )
			{
				$visitors[$id]['member'] = $visitorData[$id];
				$visitors[$id]['visit_time'] = $time;
			}
		}

		return $visitors;
	}

	/* Referrals */
	protected ?Member $_referredBy = NULL;

	/**
	 * Referred By
	 *
	 * @return    Member|NULL
	 */
	public function referredBy(): Member|null
	{
		if ( $this->_referredBy === NULL )
		{
			try
			{
				$referredByID = Db::i()->select( 'referred_by', 'core_referrals', array( 'member_id=?', $this->member_id ) )->first();
				$this->_referredBy = Member::load( $referredByID );
			}
			catch ( UnderflowException | OutOfRangeException $e )
			{
				$this->_referredBy = NULL;
			}
		}

		return $this->_referredBy;
	}

	/**
	 * Add Referral
	 *
	 * @param Member $member			The member being referred
	 *
	 * @return	void
	 */
	public function addReferral( Member $member ) : void
	{
		if ( $this->member_id and $member->member_id )
		{
			Db::i()->insert( 'core_referrals', array( 'member_id' => $member->member_id, 'referred_by' => $this->member_id ) );
		}
	}
    
	/**
	 * Get member link preference
	 *
	 * @return	string|null
	 */
	public function linkPref(): ?string
	{
		if ( $this->members_bitoptions['link_pref_unread'] )
		{
			return 'unread';
		}

		if ( $this->members_bitoptions['link_pref_last'] )
		{
			return 'last';
		}

		if ( $this->members_bitoptions['link_pref_first'] )
		{
			return 'first';
		}
		
		return NULL;
	}
	
	/**
	 * Has this member joined recently?
	 *
	 * @return boolean
	 */
	public function joinedRecently() : bool
	{
		if ( $this->member_id AND $this->joined->diff( DateTime::ts( time() ) )->days < 30 )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/* !Achievements */

	/**
	 * Get current rank
	 *
	 * @return Rank|null
	 */
	public function rank(): ?Rank
	{
		return Rank::fromPoints( (int) $this->achievements_points );
	}
	
	/**
	 * Get when the current rank was earned
	 *
	 * @return	?DateTime
	 */
	public function rankEarned(): ?DateTime
	{
		if ( $rank = $this->rank() )
		{
			try
			{
				return DateTime::ts( Db::i()->select( 'datetime', 'core_points_log', [ '`member`=? AND new_rank=?', $this->member_id, $rank->id ], 'datetime DESC', 1 )->first() );
			}
			catch ( UnderflowException $e ) { }
		}
		return NULL;
	}

	/**
	 * Get next rank
	 *
	 * @return Rank|null
	 */
	public function nextRank(): ?Rank
	{
		foreach ( Rank::getStore() as $rank )
		{
			if ( $rank->points > $this->achievements_points )
			{
				return $rank;
			}
		}
		return NULL;
	}

	protected ?array $rankHistory = NULL;

	/**
	 * Return rank history
	 *
	 * @return array
	 */
	public function rankHistory(): array
	{
		if ( ! $this->rankHistory )
		{
			$return = [ 'earned' => NULL, 'not_earned' => NULL ];
			$query = iterator_to_array( Db::i()->select( 'new_rank, datetime', 'core_points_log', ['`member`=? and new_rank IS NOT NULL', $this->member_id] )->setKeyField('new_rank')->setValueField('datetime') );
			$currentRank = $this->rank();

			foreach ( Rank::getStore() as $rank )
			{
				if ( $rank->points == 0 )
				{
					$return['earned'][] = ['rank' => $rank, 'time' => $this->joined ];
				}
				elseif ( $rank->points <= $currentRank->points )
				{
					$return['earned'][] = ['rank' => $rank, 'time' => isset( $query[ $rank->id ] ) ? DateTime::ts( $query[ $rank->id ] ) : NULL ];
				}
				else
				{
					$return['not_earned'][] = ['rank' => $rank];
				}
			}

			$this->rankHistory = $return;
		}

		return $this->rankHistory;
	}

	/**
	 * Return the number of days since the last rank upgrade
	 *
	 * @return int
	 */
	public function daysSinceLastRank(): int
	{
		$history = $this->rankHistory();
		$lastRank = NULL;

		foreach( $history['earned'] as $earned )
		{
			if ( $earned['rank']->id == $this->rank()->id )
			{
				break;
			}

			$lastRank = $earned;
		}

		$time = ( $lastRank  and $lastRank['time'] ) ? $lastRank['time'] : $this->joined;
		return intval( DateTime::ts( time() )->diff( $time )->days );
	}

	/**
	 * @brief	List of badge IDs that this member has earned
	 */
	protected ?array $_badgeIds = NULL;
	
	/**
	 * Get list of badge IDs that this member has earned
	 *
	 * @return	array
	 */
	protected function badgeIds(): array
	{
		if ( $this->_badgeIds === NULL )
		{
			$this->_badgeIds = iterator_to_array( Db::i()->select( 'badge', 'core_member_badges', [ '`member`=?', $this->member_id ] ) );
		}
		return $this->_badgeIds;
	}
	
	/**
	 * @brief	Count of badges this member has earned
	 */
	public ?int $_badgeCount = NULL;
	
	/**
	 * Get count of badges that this member has earned
	 *
	 * @return	int
	 */
	public function badgeCount(): int
	{
		if ( $this->_badgeCount === NULL )
		{
			$this->_badgeCount = Db::i()->select( 'count(*)', 'core_member_badges', [ '`member`=?', $this->member_id ] )->first();
		}
		return $this->_badgeCount;
	}
	
	/**
	 * Recent Badges
	 *
	 * @param int|null $numberToGet	Number to get
	 * @return	ActiveRecordIterator
	 */
	public function recentBadges( ?int $numberToGet ): ActiveRecordIterator
	{
		return new ActiveRecordIterator(
			Db::i()->select( 'core_badges.*, core_member_badges.member, core_member_badges.badge, core_member_badges.datetime, core_member_badges.rule, core_member_badges.action_log, core_member_badges.actor, core_member_badges.recognize', 'core_member_badges', [ '`member`=?', $this->member_id ], 'datetime DESC', $numberToGet )
				->join( 'core_badges', 'core_member_badges.badge=core_badges.id' ),
			'IPS\core\Achievements\Badge'
		);
	}

	/**
	 * @var array|null Cache for excluded groups for rules
	 */
	protected static ?array $rulesExcluded = NULL;

	/**
	 * Can this member partake in the fun an frivolity of achievements?
	 *
	 * @return bool
	 */
	public function canHaveAchievements(): bool
	{
		if ( ! Settings::i()->achievements_enabled )
		{
			return FALSE;
		}

		if ( ! $this->member_id )
		{
			return FALSE;
		}

		if ( static::$rulesExcluded === NULL )
		{
			static::$rulesExcluded = json_decode( Settings::i()->rules_exclude_groups, TRUE );
		}

		if ( ! is_array( static::$rulesExcluded ) or ! count( static::$rulesExcluded  ) )
		{
			return TRUE;
		}

		return ! $this->inGroup(static::$rulesExcluded);
	}
	
	/**
	 * User has done something which could earn Points / Badges
	 *
	 * @param	string	$app				Application key for the action being performed
	 * @param	string	$extension			Extension key for the action being performed
	 * @param	mixed|null $extra				Any additional information to be passed along to the extension (e.g. if a post is being made: the post object)
	 * @param	DateTime|null $date		Optional DateTime object to be used as the date the achievement was earned
	 * @return	void
	 */
	public function achievementAction( string $app, string $extension, mixed $extra = NULL, DateTime $date = NULL ) : void
	{
		if ( ! $this->canHaveAchievements() )
		{
			return;
		}

		if ( isset( Rule::getStore()["{$app}_{$extension}"] ) )
		{
			$enabled = FALSE;
			foreach ( Rule::getStore()["{$app}_{$extension}"] as $ruleId => $ruleData )
			{
				if ( $ruleData['enabled'] )
				{
					$enabled = TRUE;
				}
			}

			if ( $enabled === TRUE )
			{
				$extensionObject = Application::load( $app )->extensions( 'core', 'AchievementAction' )[$extension];

				/* Log it first, because doing so allows us to tell if we have already ran the rules
					for this action (e.g. if someone unlikes and relikes a post, we don't want to keep
					doing the actions for that every time: only once) */
				$logId = Db::i()->insert( 'core_achievements_log', [
					'action' => "{$app}_{$extension}",
					'identifier' => $extensionObject->identifier( $this, $extra ),
					'datetime' => ( $date ) ? $date->getTimestamp() : time()
				], FALSE, TRUE );
				if ( !$logId )
				{
					return;
				}
			}
			else
			{
				/* There are no enabled rules with this extension */
				return;
			}

			/* Work out what we have to dish out */
			$awards = [];
			$milestoneHitWithRules = [];
			foreach ( Rule::getStore()["{$app}_{$extension}"] as $ruleId => $ruleData )
			{
				if ( ! $ruleData['enabled'] )
				{
					continue;
				}

				if ( !$ruleData['filters'] or $extensionObject->filtersMatch( $this, $ruleData['filters'], $extra ) )
				{
					/* Did we already award for this milestone? */
					if ( Rule::ruleHasMilestone( $ruleData['filters'] ) )
					{
						$milestoneHitWithRules[ $ruleId ] = [];
					}

					if ( $ruleData['points_subject'] or $ruleData['badge_subject'] )
					{
						if ( array_key_exists( $ruleId, $milestoneHitWithRules ) )
						{
							$milestoneHitWithRules[ $ruleId ][ 'subject-' . $this->member_id ] = 'subject-' . $this->member_id;
						}

						if ( !isset( $awards[ $this->member_id ] ) )
						{
							$awards[ $this->member_id ] = [ 'points' => [], 'badges' => [], 'actor' => [] ];
						}
						if ( $ruleData['points_subject'] )
						{
							$awards[ $this->member_id ]['actor']['subject'] = 'subject';
							$awards[ $this->member_id ]['points'][ $ruleId ] = $ruleData['points_subject'];
						}
						if ( $ruleData['badge_subject'] and !in_array( $ruleData['badge_subject'], $this->badgeIds() ) )
						{
							$awards[ $this->member_id ]['actor']['subject'] = 'subject';
							$awards[ $this->member_id ]['badges'][ $ruleId ] = $ruleData['badge_subject'];
						}
					}

					if ( $ruleData['points_other'] or $ruleData['badge_other'] )
					{
						foreach ( $extensionObject->awardOther( $extra, $ruleData['filters'] ) as $member )
						{
							/* If the "other" member is also the subject member (e.g. member is replying to their own topic), we
								only give them the "subject" points */
							if ( $member->member_id == $this->member_id )
							{
								continue;
							}

							if ( array_key_exists( $ruleId, $milestoneHitWithRules ) )
							{
								$milestoneHitWithRules[ $ruleId ][ 'other-' . $member->member_id ] = 'other-' . $member->member_id;
							}

							/* Otherwise carry on as usual... */
							if ( !isset( $awards[ $member->member_id ] ) )
							{
								$awards[ $member->member_id ] = [ 'points' => [], 'badges' => [], 'actor' => [] ]; // So given the above comment, why is actor an array? In case we change our minds.
							}
							if ( $ruleData['points_other'] )
							{
								$awards[ $member->member_id ]['actor']['other'] = 'other';
								$awards[ $member->member_id ]['points'][ $ruleId ] = $ruleData['points_other'];
							}
							if ( $ruleData['badge_other'] and !in_array( $ruleData['badge_other'], $member->badgeIds() ) )
							{
								$awards[ $member->member_id ]['actor']['other'] = 'other';
								$awards[ $member->member_id ]['badges'][ $ruleId ] = $ruleData['badge_other'];
							}
						}
					}
				}
			}

			/* If we have stuff to dish out... */
			if ( $awards )
			{
				$alreadyHitMilestone = [];

				/* Insert these milestone maps */
				foreach( $milestoneHitWithRules as $ruleId => $members )
				{
					foreach( $members as $data )
					{
						[ $type, $memberId ] = explode( '-', $data );
						$insertId = Db::i()->insert( 'core_achievements_log_milestones', [
							'milestone_member_id' => $memberId,
							'milestone_rule'      => $ruleId,
							'milestone_log_id'    => $logId,
							'milestone_type'	  => $type,
						], FALSE, TRUE );

						if ( ! $insertId )
						{
							$alreadyHitMilestone[ $memberId ][] = $ruleId;
						}
					}
				}

				foreach ( $awards as $memberId => $memberAwards )
				{
					if ( $memberAwards['points'] )
					{
						foreach( $memberAwards['points'] as $ruleId => $data )
						{
							if ( isset( $alreadyHitMilestone[ $memberId ] ) and in_array( $ruleId, $alreadyHitMilestone[ $memberId ] ) )
							{
								unset( $memberAwards['points'][ $ruleId ] );
							}
						}

						if ( array_key_exists( 'points', $memberAwards ) and count( $memberAwards['points'] ) )
						{
							Member::load( $memberId )->awardPoints( array_sum( $memberAwards['points'] ), $logId, array_keys( $memberAwards['points'] ), $memberAwards['actor'], 0, $date );
						}
					}
					if ( $memberAwards['badges'] )
					{
						foreach ( array_unique( $memberAwards['badges'] ) as $ruleId => $badgeId )
						{
							if ( ! isset( $alreadyHitMilestone[ $memberId ] ) or ! in_array( $ruleId, $alreadyHitMilestone[ $memberId ] ) )
							{
								try
								{
									Member::load( $memberId )->awardBadge( Badge::load( $badgeId ), $logId, $ruleId, $memberAwards['actor'], 0, $date );
								}
								catch ( OutOfRangeException $e ) { }
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Award Points
	 *
	 * @param	int		$points		Number of points to award
	 * @param	int		$logId		The ID number of the log in core_achievements_log which will tell us specifically what ACTION caused this (eg what post was made)
	 * @param	array	$ruleIds	The ID number of the rules which matched that action which contributed to this number of points being awarded
	 * @param	array	$actor		If member is "subject", "other" or both
	 * @param	int		$recognize	The ID of the recognize table row (optional)
	 * @param	DateTime|null $date		Optional DateTime object to be used as the date the achievement was earned
	 */
	public function awardPoints(int $points, int $logId, array $ruleIds, array $actor, int $recognize=0, DateTime $date=NULL ) : void
	{
		if ( ! $this->canHaveAchievements() )
		{
			return;
		}

		/* What are they on now? */
		$oldPointsBalance = $this->achievements_points;
		$oldRank = $this->rank();
		
		/* Increase it */
		$this->achievements_points += $points;
		$newPointsBalance = $this->achievements_points;
		$newRank = $this->rank();
		if ( $oldRank and $newRank and ( $oldRank->id == $newRank->id ) )
		{
			$newRank = NULL;
		}
		Db::i()->update( 'core_members', "achievements_points = achievements_points + " . $points, [ 'member_id=?', $this->member_id ] );
		
		/* Log it */
		$logId = Db::i()->insert( 'core_points_log', [
			'member'	=> $this->member_id,
			'points'	=> $points,
			'datetime'	=> ( $date ) ? $date->getTimestamp() : time(),
			'balance'	=> $newPointsBalance,
			'rules'		=> implode( ',', $ruleIds ),
			'action_log'=> $logId,
			'actor'		=> implode( ',', $actor ),
			'new_rank'	=> $newRank?->id,
			'time_to_new_rank'	=> $newRank ? time() - $this->joined->getTimestamp() : NULL,
			'recognize' => $recognize
		], FALSE, TRUE );

		/* Did that push them over threshold for a new rank? Don't send a notification if rebuilds are in progress, and a random post made live triggers a new badge or award */
		if ( $newRank and ! Settings::i()->achievements_rebuilding )
		{
			$notification = new Notification( Application::load( 'core' ), 'new_rank', $this, [ $newRank, $this ], [ $newRank->id ] );
			$notification->recipients->attach( $this );
			$notification->send();
		}
		
		$this->save();

		if ( isset( static::$_memberRecognizedPoints[ $this->member_id ] ) )
		{
			unset( static::$_memberRecognizedPoints[ $this->member_id ] );
		}
	}
	
	/**
	 * Award Badge
	 *
	 * @param	Badge	$badge		The badge to award
	 * @param	int								$logId		The ID number of the log in core_achievements_log which will tell us specifically what ACTION caused this (eg what post was made)
	 * @param	int								$ruleId		The ID number of the rule which matched that action which caused this badge to be awarded
	 * @param	array							$actor		If member is "subject", "other" or both
	 * @param	int								$recognize	The ID of the recognize table row (optional)
	 * @param DateTime|null $date		Optional DateTime object to be used as the date the achievement was earned
	 */
	public function awardBadge( Badge $badge, int $logId, int $ruleId, array $actor, int $recognize=0, DateTime $date=NULL ) : void
	{
		if ( ! $this->canHaveAchievements() )
		{
			return;
		}

		$insertId = Db::i()->insert( 'core_member_badges', [
			'member'		=> $this->member_id,
			'badge'			=> $badge->id,
			'datetime'		=> ( $date ) ? $date->getTimestamp() : time(),
			'rule'			=> $ruleId,
			'action_log'	=> $logId,
			'actor'			=> implode( ',', $actor ),
			'recognize'     => $recognize
		], FALSE, TRUE );
		if ( !$insertId )
		{
			// Member has already earned this badge
			return;
		}

		/* Don't send a notification if rebuilds are in progress, and a random post made live triggers a new badge or award */
		if ( ! Settings::i()->achievements_rebuilding )
		{
			$notification = new Notification( Application::load( 'core' ), 'new_badge', $this, [ $this, $badge ], [ $badge->id ] );
			$notification->recipients->attach( $this );
			$notification->send();
		}

		$data = [
			'member' => $this->apiOutput(),
			'badge' => $badge->apiOutput(),
		];

		Webhook::fire( 'member_badge_awarded', $data );
		$this->save();
	}

	public static array $_memberRecognizedPoints = array();
	/**
	 * Grab todays recognized points
	 *
	 * @return int
	 */
	public function todaysRecognizePoints(): int
	{
		if ( ! isset( static::$_memberRecognizedPoints[ $this->member_id ] ) )
		{
			$timezone = new DateTimeZone( Settings::i()->reputation_timezone );
			$today = DateTime::create()->setTimezone( $timezone )->setTime( 0, 0 );

			$sum = Db::i()->select( 'SUM(r_points)', 'core_member_recognize', [ 'r_member_id=? and r_added >=?', $this->member_id, $today->getTimestamp() ] )->first();

			static::$_memberRecognizedPoints[ $this->member_id ] = $sum !== NULL ? $sum : 0;
		}

		return static::$_memberRecognizedPoints[ $this->member_id ];
	}

	public static array $_memberRecognizedBadges = array();
	/**
	 * Grab todays recognized points
	 *
	 * @return int
	 */
	public function todaysRecognizeBadges(): int
	{
		if ( ! isset( static::$_memberRecognizedBadges[ $this->member_id ] ) )
		{
			$timezone = new DateTimeZone( Settings::i()->reputation_timezone );
			$today = DateTime::create()->setTimezone( $timezone )->setTime( 0, 0 );

			$sum = Db::i()->select( 'COUNT(*)', 'core_member_recognize', [ 'r_member_id=? and r_badge > 0 and r_added >=?', $this->member_id, $today->getTimestamp() ] )->first();

			static::$_memberRecognizedBadges[ $this->member_id ] = $sum !== NULL ? $sum : 0;
		}

		return static::$_memberRecognizedBadges[ $this->member_id ];
	}
	
	/**
	 * @brief	Count of solutions member has given
	 */
	public ?int $_solutionCount = NULL;
	
	/**
	 * Get count of solutions member has given
	 *
	 * @return	int
	 */
	public function solutionCount(): int
	{
		if ( $this->_solutionCount === NULL )
		{
			$this->_solutionCount = Db::i()->select( 'COUNT(*)', 'core_solved_index', array( 'member_id=? AND type=?', $this->member_id, 'solved' ) )->first();
		}
		return $this->_solutionCount;
	}

	/**
	 * Record this log in
	 *
	 * @return void
	 */
	public function recordLogin() : void
	{
		try
		{
			Db::i()->insert( 'core_members_logins', [ 'member_id' => $this->member_id, 'member_timestamp' => time(), 'member_date' => date( 'Y-m-d', DateTime::ts( time() )->getTimestamp() ) ] );
		}
		catch( Exception $e ) { }
	}

	/**
	 * Webhook filters
	 *
	 * @return	array
	 */
	public function webhookFilters() : array
	{
		return [
			'completed' => $this->completed,
			'allowAdminEmails' => $this->allow_admin_mails,
		];
	}

	/**
	 * Get unread feature highlights
	 *
	 * @param	bool			$markRead	Mark returned features as read? Useful for debugging.
	 * @return	array|NULL
	 */
	public function getFeatureHighlights( bool $markRead = TRUE ): ?array
	{
		return NULL;
	}

	/**
	 * Get the member's personal information
	 *
	 * @return SimpleXML
	 */
	public function getPiiData(): SimpleXML
	{
		/* Init */
		$xml = SimpleXML::create('member_export');
		$xml->addAttribute( 'created', DateTime::ts( time() )->rfc3339() );

		/* Get the data */
		foreach( Application::allExtensions( 'core', 'MemberExportPersonalInformation', TRUE, 'core' ) AS $key => $ext )
		{
			if ( $data = $ext->getData( $this ) and is_array( $data ) and count( $data ) )
			{
				$child = $xml->addChild( $key );
				foreach( $data as $k => $v )
				{
					$child->addChild( $k, $v );
				}
			}
		}
		return $xml;
	}

	/**
	 * Is there a deletion request pending?
	 *
	 * @return bool
	 */
	public function get_isDeletionPending(): bool
	{
		$where = [];
		$where[] = ['member_id=?', $this->member_id];
		$where[] = [ 'action=?', PrivacyAction::TYPE_REQUEST_DELETE ];

		return (bool) Db::i()->select( 'count(*)', PrivacyAction::$databaseTable, $where )->first();
	}

	/**
	 * Is there a deletion request pending validation? We can still cancel it
	 *
	 * @return bool
	 */
	public function get_canCancelDeletion() : bool
	{
		return (bool) Db::i()->select( 'count(*)', PrivacyAction::$databaseTable, [ 'member_id=? and action=?', $this->member_id, PrivacyAction::TYPE_REQUEST_DELETE_VALIDATION ] )->first();
	}

	/**
	 * Delete all logged IP addresses which were logged after a certain time
	 * 
	 * @param int $time
	 * @return void
	 */
	public static function pruneAllLoggedIpAddresses(int $time ) : void
	{
		foreach ( Content::routedClasses( FALSE, TRUE ) as $class )
		{
			try
			{
				$class::pruneIpAddresses( Settings::i()->ip_address_prune );
			}
			catch( Exception $ex ) { }
		}

		/* PMs are treated extra */
		Db::i()->update( 'core_message_posts', array( 'msg_ip_address' => '' ), array( "msg_ip_address != '' AND msg_date <= " . $time ) );

		foreach ( Application::allExtensions( 'core', 'IpAddresses', FALSE, 'core' ) as $key => $extension )
		{
			if( method_exists( $extension, 'pruneIpAddresses' ) )
			{
				$extension->pruneIpAddresses( $time );
			}
		}
	}

	/**
	 * Can this member request that his account gets deleted via the frontend
	 *
	 * @return bool
	 */
	public function canUseAccountDeletion(): bool
	{
		if( Settings::i()->right_to_be_forgotten_type != 'on' )
		{
			return FALSE;
		}

		if( Member::loggedIn()->isAdmin() )
		{
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Determines if this member has permission to change the layout
	 *
	 * @return bool
	 */
	public function canChangeLayoutValue() : bool
	{
		return ( $this->member_id and $this->group['gbw_change_layouts'] );
	}

	/**
	 * Return the layout value based on member choice, permissions and default theme option
	 *
	 * @param string $what Theme constant
	 * @return string
	 * @throws Exception
	 */
	public function getLayoutValue( string $what ): string
	{
		$themeOption = Theme::i()->getLayoutValue( $what );

		/* If we are in the theme editor mode, then return theme settings */
		if ( Member::loggedIn()->isEditingTheme() )
		{
			return $themeOption;
		}

		if ( $this->canChangeLayoutValue() and $this->layouts and $memberOptions = json_decode( $this->layouts, TRUE ) )
		{
			if ( isset( $memberOptions[ $what ] ) )
			{
				return $memberOptions[ $what ];
			}
		}

		/* Still here? Return the theme default value */
		return $themeOption;
	}

	/**
	 * Store the layout value
	 *
	 * @param string|null $what
	 * @param string|null $value
	 * @return void
	 */
	public function setLayoutValue( string|null $what, string|null $value ): void
	{
		if( $this->canChangeLayoutValue() )
		{
			if ( $what === null )
			{
				/* Clear all custom layouts */
				$this->layouts = null;
			}
			else
			{
				$memberOptions = json_decode( $this->layouts, true );
				$memberOptions[$what] = $value;
				$this->layouts = json_encode( $memberOptions );
			}

			$this->save();
		}
	}
}