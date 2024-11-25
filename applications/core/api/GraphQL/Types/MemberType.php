<?php
/**
 * @brief		GraphQL: Member Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\Fields\CoverPhotoField;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Application;
use IPS\Content\Search\Query;
use IPS\Content\Search\Results;
use IPS\core\Ignore;
use IPS\DateTime;
use IPS\Db;
use IPS\forums\Topic;
use IPS\Helpers\Form\Editor;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Notification\Api;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MemberrType for GraphQL API
 */
class MemberType extends ObjectType
{
	/**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_Member',
			'description' => 'Community members',
			'fields' => function () {
				return [
					'id' => [
						'type' => TypeRegistry::id(),
						'description' => "Returns the member's ID",
						'resolve' => function( $member ) {
							return $member->member_id;
						}
					],
					'email' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the member's email address (depending on permissions)",
						'resolve' => function( $member ) {
							if( !self::isAuthorized($member) )
							{
								return NULL;
							}
							return $member->email;
						}
					],
					'url' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the URL to member's profile",
						'resolve' => function( $member ) {
							return (string) $member->url();
						}
					],
					'name' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the member's username",
						'args' => [
							'formatted' => [
								'type' => TypeRegistry::boolean(),
								'defaultValue' => FALSE
							]
						],
						'resolve' => function( $member, $args ) {
							return ( $args['formatted'] ) ? Group::load( $member->member_group_id )->formatName( $member->name ) : $member->name;
						}
					],
					'title' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the member's title",
						'resolve' => function( $member ) {
							return null;
						}
					],
					'timezone' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the member's timezone (depending on permissions)",
						'resolve' => function( $member ) {
							if( !self::isAuthorized($member) )
							{
								return NULL;
							}
							return $member->timezone;
						}
					],
					'joined' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the date the member joined",
						'resolve' => function( $member ) {
							return $member->joined->getTimestamp();
						}
					],
					'notifications' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::notification() ),
						'description' => "Returns the member's notifications (depending on permissions)",
						'args' => [
							'offset' => [
								'type' => TypeRegistry::int(),
								'defaultValue' => 0,
							],
							'limit' => [
								'type' => TypeRegistry::int(),
								'defaultValue' => 25
							],
							'sortBy' => [
								'type' => TypeRegistry::eNum([
									'name' => 'notification_sort',
									'values' => ['updated_time', 'sent_time', 'read_time', 'unread']
								]),
								'defaultValue' => 'updated_time'
							],
							'sortDir' => [
								'type' => TypeRegistry::eNum([
									'name' => 'notification_sort_dir',
									'values' => ['asc', 'desc']
								]),
								'defaultValue' => 'desc'
							],
							'unread' => [
								'type' => TypeRegistry::boolean(),
								'defaultValue' => NULL
							]
						],
						'resolve' => function( $member, $args, $context ) {
							if( !self::isOwnerMember($member) )
							{
								return NULL;
							}
							return self::notifications($member, $args, $context);
						}
					],
					'notificationCount' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the member's notification count (depending on permissions)",
						'resolve' => function( $member ) {
							if( !self::isOwnerMember($member) )
							{
								return NULL;
							}
							return $member->notification_cnt;
						}
					],
					'posts' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the member's post count",
						'resolve' => function( $member ) {
							return $member->member_posts;
						}
					],
					'contentCount' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the member's post count",
						'resolve' => function( $member ) {
							return $member->member_posts;
						}
					],
					'reputationCount' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the member's reputation count",
						'resolve' => function( $member ) {
							return ( Settings::i()->reputation_enabled ) ? $member->pp_reputation_points : null;
						}
					],
					'solvedCount' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the number of items this member has solved",
						'resolve' => function ($member) {
							if( !Application::appIsEnabled('forums') || !Topic::anyContainerAllowsSolvable() )
							{
								return 0;
							}

							return (int) Db::i()->select( 'COUNT(*) as count', 'core_solved_index', array( 'member_id=? AND type=?', $member->member_id, 'solved' ) )->first();
						}
					],
					'ip_address' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the member's IP address (depending on permissions)",
						'resolve' => function( $member ) {
							if( !self::isAuthorized($member) )
							{
								return NULL;
							}
							return $member->ip_address;
						}
					],
					'warn_level' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the member's warning level (depending on permissions)",
						'resolve' => function( $member ) {
							if( !self::isAuthorized($member) ){
								return NULL;
							}
							return $member->warn_level;
						}
					],
					'profileViews' => [
						'type' => TypeRegistry::int(),
						'description' => "Returns the number of profile views for this member (depending on permissions)",
						'resolve' => function( $member ) {
							if( !self::isAuthorized($member) )
							{
								return NULL;
							}
							return $member->members_profile_views;
						}
					],
					'validating' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Returns the member's validating status (depending on permissions)",
						'resolve' => function( $member, $args, $context, $info ) {
							if( !self::isAuthorized($member) )
							{
								return NULL;
							}
							return (bool) $member->members_bitoptions['validating'];
						}
					],
					'group' => [
						'type' => \IPS\core\api\GraphQL\TypeRegistry::group(),
						'description' => "Returns the member's primary group",
						'resolve' => function( $member, $args, $context, $info ) {
							return Group::load( $member->member_group_id );
						}
					],
					'isOnline' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Indicates whether the user is online, taking permissions into account",
						'resolve' => function ($member) {
							return ( $member->isOnline() AND !$member->isOnlineAnonymously() ) OR ( $member->isOnlineAnonymously() AND Member::loggedIn()->isAdmin() );
						}
					],
					'lastActivity' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the timestamp of the member's last activity",
						'resolve' => function( $member, $args, $context, $info ) {
							return DateTime::ts( $member->last_activity )->rfc3339();
						}
					],
					'lastVisit'	=> [
						'type' => TypeRegistry::string(),
						'description' => "Returns the timestamp of the member's last visit",
						'resolve' => function( $member, $args, $context, $info ) {
							return DateTime::ts( $member->last_visit )->rfc3339();
						}
					],
					'lastPost' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the timestamp of member's last post",
						'resolve' => function( $member, $args, $context, $info ) {
							return DateTime::ts( $member->member_last_post )->rfc3339();
						}
					],
					'secondaryGroups' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::group() ),
						'description' => "Returns the member's secondary groups (depending on permissions)",
						'resolve' => function( $member, $args, $context, $info ) {
							if( !self::isAuthorized($member) )
							{
								return NULL;
							}
							return self::secondaryGroups( $member, $args, $context, $info );
						}
					],
					'defaultStream' => [
						'type' => TypeRegistry::int(),
						'description' => "The ID of the user's default stream",
						'resolve' => function ($member) {
							if( !self::isAuthorized($member) || $member->defaultStream == FALSE )
							{
								return NULL;
							}

							return $member->defaultStream;
						}
					],
					'allowFollow' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Whether this member allows others to follow them",
						'resolve' => function ($member) {
							return !$member->members_bitoptions['pp_setting_moderate_followers'];
						}
					],
					'follow' => [
						'type' => TypeRegistry::follow(),
						'description' => "Returns fields to handle followers/following",
						'resolve' => function ($member) {
							return array(
								'app' => 'core',
								'area' => 'member',
								'id' => $member->member_id,
								'member' => $member
							);
						}
					],
					'content' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::searchResult() ),
						'args' => [
							'offset' => [
								'type' => TypeRegistry::int(),
								'defaultValue' => 0
							],
							'limit' => [
								'type' => TypeRegistry::int(),
								'defaultValue' => 25
							],
						],
						'description' => "Returns the member's content",
						'resolve' => function ($member, $args) {
							return self::content($member, $args);
						}
					],
					'clubs' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::club() ),
						'description' => "Returns the member's clubs (depending on permissions)",
						'resolve' => function( $member, $args, $context, $info ) {
							return Club::clubs( $member, 25, 'last_activity' );
						}
					],
					'photo' => [
						'type' => TypeRegistry::string(),
						'description' => "Returns the member's photo",
						'resolve' => function( $member, $args, $context, $info ) {
							return self::photo($member);
						}
					],
					'coverPhoto' => CoverPhotoField::getDefinition('core_MemberCoverPhoto'),
					'customFieldGroups' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::profileFieldGroup() ),
						'resolve' => function( $member, $args, $context, $info ) {
							return self::customFieldGroups( $member, $args, $context );
						}
					],
					'maxUploadSize' => [
						'type' => TypeRegistry::int(),
						'description' => "The maximum upload size allowed by this user, either globally or per-item (whichever is smaller). NULL indicates no limit.",
						'resolve' => function( $member ) {
							if( $member->member_id && !self::isOwnerMember( $member ) )
							{
								throw new SafeException( 'INVALID_USER', '2S401/1', 403 );
							}

							return Editor::maxTotalAttachmentSize( $member, 0 );
						}
					],
					'canBeIgnored' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Can this member be ignored by the current user?",
						'resolve' => function ($member) {
							return $member->canBeIgnored();
						}
					],
					'ignoreStatus' => [
						'type' => TypeRegistry::listOf( \IPS\core\api\GraphQL\TypeRegistry::ignoreOption() ),
						'description' => "Returns the ignore status of this member, based on the currently-authenticated member's preferences.",
						'resolve' => function ($member ) {
							return self::ignoreStatus($member);
						} 
					],

					// Messenger fields
					'messengerDisabled' => [
						'type' => TypeRegistry::boolean(),
						'description' => "Is this member's messenger disabled?",
						'resolve' => function( $member ) {
							return (bool) $member->members_disable_pm;
						}
					],
					'messengerNewCount' => [
						'type' => TypeRegistry::int(),
						'description' => "Number of new messages",
						'resolve' => function ($member) {
							if( $member->member_id && !self::isOwnerMember( $member ) )
							{
								return 0;
							}
							
							return $member->msg_count_new;
						}
					]
				];
			}
		];

		parent::__construct($config);
	}

	/**
	 * Return a member's photo
	 *
	 * @param 	Member $member
	 * @return	string
	 */
	protected static function photo( Member $member ) : string
	{
		$member_properties = array();

		foreach( array( 'name', 'pp_main_photo', 'pp_photo_type', 'pp_thumb_photo', 'member_id' ) as $column )
		{
			$member_properties[ $column ] = $member->$column;
		}

		$photoUrl = Member::photoUrl( $member_properties );

		if( mb_strpos( $photoUrl, "data:image/svg+xml" ) === 0 )
		{
			return static::getLetterPhotoData( $member );
		}
		else
		{
			return $photoUrl;
		}
	}

	/**
	 * Return a json array containing letter/color combo for a user's letter photo
	 *
	 * @param 	Member $member
	 * @return	string
	 */
	protected static function getLetterPhotoData( Member $member ) : string
	{
		return json_encode( Member::generateLetterPhoto( array(
				'name'			=> $member->name,
				'pp_main_photo'	=> $member->pp_main_photo,
				'member_id'		=> $member->member_id
			), TRUE ) );
	}

	/**
	 * Determines if this is a user authorized to access sensitive data
	 *
	 * @param 	Member $member
	 * @return	boolean
	 */
	protected static function isAuthorized( Member $member ) : bool
	{
		return self::isOwnerMember($member) || Member::loggedIn()->isAdmin();
	}

	/**
	 * Determines if this user is the same as the user requesting the info
	 *
	 * @param 	Member $member
	 * @return	boolean
	 */
	protected static function isOwnerMember( Member $member ) : bool
	{
		return $member->member_id && $member->member_id == Member::loggedIn()->member_id;
	}

	/**
	 * Returns a member's content
	 *
	 * @param 	Member $member
	 * @param array $args
	 * @return	Results
	 */
	protected static function content( Member $member, array $args) : Results
	{
		// Get page
		// We don't know the count at this stage, so figure out the page number from
		// our offset/limit
		$page = 1;
		$offset = max( $args['offset'], 0 );
		$limit = min( $args['limit'], 50 );

		if( $offset > 0 )
		{
			$page = floor( $offset / $limit ) + 1;
		}

		$latestActivity = Query::init()->filterForProfile( $member )->setLimit( $limit )->setPage( $page )->setOrder( Query::ORDER_NEWEST_CREATED )->search();
		$latestActivity->init();

		return $latestActivity;
	}

	/**
	 * Return user's notifications
	 *
	 * @param 	Member $member
	 * @param 	array 	$args
	 * @param 	array 	$context
	 * @return	array
	 */
	protected static function notifications( Member $member, array $args, array $context ) : array
	{
		/* Specify filter in where clause */
		$where = array();
		$where[] = array( "notification_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')" );
		$where[] = array( "`member`=?", Member::loggedIn()->member_id );

		/* Are we filtering by unread status? */
		if( isset( $args['unread'] ) )
		{
			if( $args['unread'] === TRUE )
			{
				$where[] = array( "read_time IS NULL" );
			}
			else
			{
				$where[] = array( "read_time IS NOT NULL" );
			}
		}

		/* Sorting */
		$sort = $args['sortBy'] . ' ' . $args['sortDir'];

		if( $args['sortBy'] == 'unread' )
		{
			$sort = $args['sortDir'] == 'desc' ? 'read_time IS NOT NULL, sent_time DESC' : 'read_time IS NULL, sent_time ASC';
		}

		/* Get Count */
		$count = Db::i()->select( 'COUNT(*) as cnt', 'core_notifications', $where )->first();

		/* Get results */
		$returnRows = array();
		$offset = max( $args['offset'], 0 );
		$limit = min( $args['limit'], 50 );

		foreach( Db::i()->select( '*', 'core_notifications', $where, $sort, array( $offset, $limit ) ) as $row )
		{
			try
			{
				$notification   = Api::constructFromData( $row );
				$returnRows[]	= array( 'notification' => $notification, 'data' => $notification->getData( FALSE ) );
			}
			catch ( LogicException $e ) { }
		}

		return $returnRows;
	}

	/**
	 * Return custom profile field groups
	 *
	 * @param 	Member $member
	 * @param array $args
	 * @param array $context
	 * @return	array|null
	 */
	protected static function customFieldGroups( Member $member, array $args, array $context ) : ?array
	{
		/* Get profile field values */
		try
		{
			$profileFieldValues	= Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $member->member_id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			return null;
		}

		if( !empty( $profileFieldValues ) )
		{
			$fields = array();

			if( Member::loggedIn()->isAdmin() OR Member::loggedIn()->modPermissions() )
			{
				$where = array( "pfd.pf_member_hide='owner' OR pfd.pf_member_hide='staff' OR pfd.pf_member_hide='all'" );
			}
			elseif( Member::loggedIn()->member_id == $member->member_id )
			{
				$where = array( "pfd.pf_member_hide='owner' OR pfd.pf_member_hide='all'" );
			}
			else
			{
				$where = array( "pfd.pf_member_hide='all'" );
			}

			foreach( new ActiveRecordIterator( Db::i()->select( 'pfd.*', array('core_pfields_data', 'pfd'), $where, 'pfg.pf_group_order, pfd.pf_position' )->join(
				array('core_pfields_groups', 'pfg'),
				"pfd.pf_group_id=pfg.pf_group_id"
			), 'IPS\core\ProfileFields\Field' ) as $field )
			{
				if( $profileFieldValues[ 'field_' . $field->id ] !== '' AND $profileFieldValues[ 'field_' . $field->id ] !== NULL )
				{
					if( !isset( $fields[ $field->group_id ] ) ){
						$fields[ $field->group_id ] = array(
							'id' => md5( $member->member_id . $field->group_id ),
							'groupId' => $field->group_id,
							'title' => 'core_pfieldgroups_' . $field->group_id,
							'fields' => array()
						);
					}

					$fields[ $field->group_id ]['fields'][] = array(
						'id' => md5( $member->member_id . $field->id ),
						'fieldId' => $field->id,
						'title' => 'core_pfield_' . $field->id,
						'value' => json_encode( $field->apiValue( $profileFieldValues['field_' . $field->id], true ) ),
						'type' => $field->type
					);
				}
			}

			return $fields;
		}

		return null;
	}

	/**
	 * Resolve followers field
	 *
	 * @param 	Member $member
	 * @param 	array $args 	Arguments passed to this resolver
	 * @param array $context
	 * @param array $info
	 * @return	array
	 */
	protected static function followers( Member $member, array $args, array $context, array $info ) : array
	{
		$limit = min( $args['limit'], 50 );

		return array_map(
			function ($followRow)
			{
				return Member::load( $followRow['follow_member_id'] );
			},
			iterator_to_array( $member->followers( 3, array( 'immediate', 'daily', 'weekly' ), NULL, array(0, $limit ) ) )
		);
	}

	/**
	 * Resolve secondary groups field
	 *
	 * @param Member $member
	 * @param array $args
	 * @param array $context
	 * @param array $info
	 * @return    array
	 */
	protected static function secondaryGroups( Member $member, array $args, array $context, array $info ) : array
	{
		$secondaryGroups = array();
		foreach ( array_filter( array_map( "intval", explode( ',', $member->mgroup_others ) ) ) as $secondaryGroupId )
		{
			try
			{
				$secondaryGroups[] = Group::load( $secondaryGroupId );
			}
			catch ( OutOfRangeException $e ) { }
		}

		return $secondaryGroups;
	}

	/**
	 * Resolve ignore status field
	 *
	 * @param 	Member $member	Member to check
	 * @return	array|null
	 */
	protected static function ignoreStatus( Member $member) : ?array
	{
		if( !$member->canBeIgnored() || $member->member_id === Member::loggedIn()->member_id ){
			return NULL;
		}

		$ignore = FALSE;

		try
		{
			$ignore = Ignore::load( $member->member_id, 'ignore_ignore_id', array( 'ignore_owner_id=?', Member::loggedIn()->member_id ) );
		}
		catch ( OutOfRangeException $e ) {
			// Just keep ignore as false
		}

		$ignoreStatus = array();

		foreach ( Ignore::types() as $type )
		{
			$ignoreStatus[] = array(
				'type' => $type,
				'is_being_ignored' => !$ignore ? $ignore : $ignore->$type
			);
		}

		return $ignoreStatus;
	}

	public static function getOrderByOptions() : array
	{
		return ['member_id', 'joined','last_activity','name','last_visit'];
	}
}
