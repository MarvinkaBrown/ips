<?php
/**
 * @brief		Members API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		3 Dec 2015
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Application;
use IPS\Content\Item;
use IPS\core\Achievements\Badge;
use IPS\core\Followed\Follow;
use IPS\core\Warnings\Reason;
use IPS\core\Warnings\Warning;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\Events\Event;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Notification\Inline;
use IPS\Request;
use IPS\Settings;
use IPS\Text\Parser;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use const IPS\SUITE_UNIQUE_KEY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Members API
 */
class members extends Controller
{
	/**
	 * @brief	Parameters to mask in logs. Keys are the method names and values an array of field or request keys.
	 */
	public array $parametersToMask = array(
		'POSTindex'		=> array( 'password' ),
		'POSTitem'		=> array( 'password' ),
	);

	/**
	 * GET /core/members
	 * Get list of members
	 *
	 * @apiparam	string	ids			Comma-delimited list of member IDs
	 * @apiparam	string	sortBy		What to sort by. Can be 'joined', 'name', 'last_activity' or leave unspecified for ID
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	string	name		(Partial) user name to search for
	 * @apiparam	string	email		(Partial) Email address to search for
	 * @apiparam	int|array	group		Group ID or IDs to search for
	 * @apiparam	int		activity_after		Find members that have been active since unix timestamp
	 * @apiparam	int		activity_before		Find members that have been active before unix timestamp
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\Member>
	 * @return PaginatedResponse<Member>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array( array( 'core_members.email<>?', '' ) );

		/* Get members by the id/ids */
		if ( isset( Request::i()->ids ) )
		{
			$where[] = array( Db::i()->in( 'member_id', array_map( 'intval', explode( ',', Request::i()->ids ) ) ) );
		}

		/* Are we searching? */
		if( isset( Request::i()->name ) )
		{
			$where[] = Db::i()->like( 'name', Request::i()->name );
		}

		if( isset( Request::i()->email ) )
		{
			$where[] = Db::i()->like( 'email', Request::i()->email );
		}
		
		if( isset( Request::i()->activity_after ) )
		{
			$where[] = array( 'last_activity > ?', Request::i()->activity_after );
		}
		
		if( isset( Request::i()->activity_before ) )
		{
			$where[] = array( 'last_activity < ?', Request::i()->activity_before );
		}

		if( isset( Request::i()->group ) )
		{
			if( is_array( Request::i()->group ) )
			{
				$groups = array_map( function( $value ){ return intval( $value ); }, Request::i()->group );
				$where[] = array( "(member_group_id IN(" . implode( ',', $groups ) . ") OR " . Db::i()->findInSet( 'mgroup_others', $groups ) . ")" );
			}
			elseif( Request::i()->group )
			{
				$where[] = array( "(member_group_id=" . intval( Request::i()->group ) . " OR " . Db::i()->findInSet( 'mgroup_others', array( intval( Request::i()->group ) ) ) . ")" );
			}
		}

		/* Sort */
		$sortBy = ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'name', 'joined', 'last_activity' ) ) ) ? Request::i()->sortBy : 'member_id';
		$sortDir = ( isset( Request::i()->sortDir ) and in_array( mb_strtolower( Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortDir : 'asc';

		/* Return */
		return new PaginatedResponse(
			200,
			Db::i()->select( '*', 'core_members', $where, "{$sortBy} {$sortDir}" ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			'IPS\Member',
			Db::i()->select( 'COUNT(*)', 'core_members', $where )->first(),
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}
	
	/**
	 * GET /core/members/{id}
	 * Get information about a specific member
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	array	otherFields	An array of additional non-standard fields to return via the REST API
	 * @throws		1C292/2	INVALID_ID	The member ID does not exist
	 * @apireturn		\IPS\Member
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			$member = Member::load( $id );
			if ( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
			
			return new Response( 200, $member->apiOutput( $this->member, ( isset( Request::i()->otherFields ) ) ? Request::i()->otherFields : NULL ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C292/2', 404 );
		}
	}

	/**
	 * Create or update member
	 *
	 * @param	Member	$member			The member
	 * @throws		1C292/4	USERNAME_EXISTS	The username provided is already in use
	 * @throws		1C292/5	EMAIL_EXISTS	The email address provided is already in use
	 * @throws		1C292/6	INVALID_GROUP	The group ID provided is not valid
	 * @return		Member
	 */
	protected function _createOrUpdate( Member $member ): Member
	{
		if ( isset( Request::i()->name ) and Request::i()->name != $member->name )
		{
			$existingUsername = Member::load( Request::i()->name, 'name' );
			if ( !$existingUsername->member_id )
			{
				if ( $member->member_id )
				{
					$member->logHistory( 'core', 'display_name', array( 'old' => $member->name, 'new' => Request::i()->name, 'by' => 'api' ) );
				}
				$member->name = Request::i()->name;
			}
			else
			{
				throw new Exception( 'USERNAME_EXISTS', '1C292/4', 403 );
			}
		}

		if ( isset( Request::i()->email ) and Request::i()->email != $member->email )
		{
			$existingEmail = Member::load( Request::i()->email, 'email' );
			if ( !$existingEmail->member_id )
			{				
				/* Only do this if we are updating a member */
				if ( $member->member_id )
				{
					$member->logHistory( 'core', 'email_change', array( 'old' => $member->email, 'new' => Request::i()->email, 'by' => 'api' ) );
					$member->invalidateSessionsAndLogins();
				}

				$member->email = Request::i()->email;
			}
			else
			{
				throw new Exception( 'EMAIL_EXISTS', '1C292/5', 403 );
			}
		}

		if ( isset( Request::i()->group ) )
		{
			if( is_array( Request::i()->group ) )
			{
				$groups = Request::i()->group;
				$group = array_shift(  $groups );

				try
				{
					$group = Group::load( $group );
					$member->member_group_id = $group->g_id;
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'INVALID_GROUP', '1C292/6', 403 );
				}

				if( count( $groups ) )
				{
					if( !isset( Request::i()->secondaryGroups ) )
					{
						Request::i()->secondaryGroups  = $groups;
					}
					else
					{
						if( !is_array( Request::i()->secondaryGroups ) )
						{
							Request::i()->secondaryGroups = array( Request::i()->secondaryGroups );
						}
						Request::i()->secondaryGroups = array_merge(  Request::i()->secondaryGroups , Request::i()->group );
					}	
				}
			}
			else
			{
				try
				{
					$group = Group::load( Request::i()->group );
					$member->member_group_id = $group->g_id;
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'INVALID_GROUP', '1C292/6', 403 );
				}
			}

		}

		if( isset( Request::i()->secondaryGroups ) AND is_array( Request::i()->secondaryGroups ) )
		{
			foreach( Request::i()->secondaryGroups as $groupId )
			{
				try
				{
					$group = Group::load( $groupId );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'INVALID_GROUP', '1C292/7', 403 );
				}
			}

			$member->mgroup_others = implode( ',', Request::i()->secondaryGroups );
		}
		elseif( isset( Request::i()->secondaryGroups ) AND Request::i()->secondaryGroups == '' )
		{
			$member->mgroup_others = '';
		}

		if( isset( Request::i()->registrationIpAddress ) AND filter_var( Request::i()->registrationIpAddress, FILTER_VALIDATE_IP ) )
		{
			$member->ip_address	= Request::i()->registrationIpAddress;
		}

		if( isset( Request::i()->rawProperties ) AND is_array( Request::i()->rawProperties ) )
		{
			foreach( Request::i()->rawProperties as $property => $value )
			{
				$member->$property	= is_numeric( $value ) ? (int) $value : $value;
			}
		}

		if ( isset( Request::i()->password ) )
		{
			/* Setting the password for the just created member shouldn't be logged to the member history and shouldn't fire the onPassChange Sync call */
			$logPasswordChange = TRUE;
			if ( $member->member_id )
			{
				$logPasswordChange = FALSE;
			}
			$member->setLocalPassword( Request::i()->protect('password') );
			$member->save();

			if ( $logPasswordChange )
			{
				Event::fire( 'onPassChange', $member, array( Request::i()->protect('password') ) );
				$member->logHistory( 'core', 'password_change', 'api' );
			}

			$member->invalidateSessionsAndLogins();
		}
		else
		{
			$member->save();
		}

		/* Validation stuff */
		if( isset( Request::i()->validated ) )
		{
			/* If the member is currently validating and we are setting the validated flag to true, then complete the validation */
			if( Request::i()->validated == 1 AND $member->members_bitoptions['validating'] )
			{
				$member->validationComplete();
			}
			/* If the member is not currently validating, and we set the validated flag to false AND validation is enabled, mark the member validating */
			elseif( Request::i()->validated == 0 AND !$member->members_bitoptions['validating'] AND Settings::i()->reg_auth_type != 'none' )
			{
				$member->postRegistration();
			}
		}

		/* Any custom fields? */
		if( isset( Request::i()->customFields ) )
		{
			/* Profile Fields */
			try
			{
				$profileFields = Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $member->member_id ) )->first();
			}
			catch( UnderflowException $e )
			{
				$profileFields	= array();
			}

			/* If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here. */
			if ( !is_array( $profileFields ) )
			{
				$profileFields = array();
			}

			$profileFields['member_id'] = $member->member_id;

			foreach ( Request::i()->customFields as $k => $v )
			{
				$profileFields[ 'field_' . $k ] = $v;
			}

			Db::i()->replace( 'core_pfields_content', $profileFields );

			$member->changedCustomFields = $profileFields;
			$member->save();
		}

		return $member;
	}

	/**
	 * POST /core/members
	 * Create a member. Requires the standard login handler to be enabled
	 *
	 * @apiclientonly
	 * @apiparam	string	name			Username
	 * @apiparam	string	email			Email address
	 * @apiparam	string	password		Password (standard login handler only). If not provided, the member will be emailed to set one.
	 * @apiparam	int|array		group			Group ID number ; if an array was provided, the first item will be used for the primary group and everything else for the secondary.
	 * @apiparam	string	registrationIpAddress		IP Address
	 * @apiparam	array	secondaryGroups	Secondary group IDs, or empty value to reset secondary groups
	 * @apiparam	object	customFields	Array of custom fields as fieldId => fieldValue
	 * @apiparam	int		validated		Flag to indicate if the account is validated (1) or not (0)
	 * @apiparam	array	rawProperties	Key => value object of member properties to set. Note that values will be set exactly as supplied without validation. USE AT YOUR OWN RISK.
	 * @throws		1C292/4	USERNAME_EXISTS			The username provided is already in use
	 * @throws		1C292/5	EMAIL_EXISTS			The email address provided is already in use
	 * @throws		1C292/6	INVALID_GROUP			The group ID provided is not valid
	 * @throws		1C292/7	INVALID_GROUP			A secondary group ID provided is not valid
	 * @throws		1C292/8	NO_USERNAME_OR_EMAIL	No Username or Email Address was provided for the account
	 * @apireturn		\IPS\Member
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* One of these must be provided to ensure user can log in. */
		if ( !isset( Request::i()->name ) AND !isset( Request::i()->email ) )
		{
			throw new Exception( 'NO_USERNAME_OR_EMAIL', '1C292/8', 403 );
		}

		$member = new Member;
		$member->member_group_id = Settings::i()->member_group;
		$member->members_bitoptions['created_externally'] = TRUE;
		
		$forcePass = FALSE;
		if ( !isset( Request::i()->password ) )
		{
			$member->members_bitoptions['password_reset_forced'] = TRUE;
			$forcePass = TRUE;
		}
		
		$member = $this->_createOrUpdate( $member );
		
		if ( $forcePass AND isset( Request::i()->name ) AND isset( Request::i()->email ) )
		{
			$passSetKey = md5( SUITE_UNIQUE_KEY . Request::i()->email . Request::i()->name );
			Email::buildFromTemplate( 'core', 'admin_reg', array( $member, $forcePass, $passSetKey ), Email::TYPE_TRANSACTIONAL )->send( $member );
		}

		return new Response( 201, $member->apiOutput( $this->member ) );
	}

	/**
	 * POST /core/members/{id}
	 * Edit a member
	 *
	 * @apiclientonly
	 * @apiparam	string	name			Username
	 * @apiparam	string	email			Email address
	 * @apiparam	string	password		Password (standard login handler only)
	 * @apiparam	int|array		group			Group ID number ; if an array was provided, the first item will be used for the primary group and everything else for the secondary.
	 * @apiparam	string	registrationIpAddress		IP Address
	 * @apiparam	array	secondaryGroups	Secondary group IDs, or empty value to reset secondary groups
	 * @apiparam	object	customFields	Array of custom fields as fieldId => fieldValue
	 * @apiparam	int		validated		Flag to indicate if the account is validated (1) or not (0)
	 * @apiparam	array	rawProperties	Key => value object of member properties to set. Note that values will be set exactly as supplied without validation. USE AT YOUR OWN RISK.
	 * @param		int		$id			ID Number
	 * @throws		2C292/7	INVALID_ID	The member ID does not exist
	 * @throws		1C292/4	USERNAME_EXISTS	The username provided is already in use
	 * @throws		1C292/5	EMAIL_EXISTS	The email address provided is already in use
	 * @throws		1C292/6	INVALID_GROUP	The group ID provided is not valid
	 * @throws		1C292/7	INVALID_GROUP	A secondary group ID provided is not valid
	 * @apireturn		\IPS\Member
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$member = Member::load( $id );
			if ( !$member->member_id )
			{
				throw new OutOfRangeException;
			}
			
			$oldPrimaryGroup = $member->member_group_id;
			$oldSecondaryGroups = array_unique( array_filter( explode( ',', $member->mgroup_others ) ) );
			$member = $this->_createOrUpdate( $member );
			
			if ( $oldPrimaryGroup != $member->member_group_id )
			{
				$member->logHistory( 'core', 'group', array( 'type' => 'primary', 'by' => 'api', 'apiKey' => $this->apiKey?->id, 'client' => $this->client?->client_id, 'old' => $oldPrimaryGroup, 'new' => $member->member_group_id ), $this->member ?: FALSE );
			}
			$newSecondaryGroups = array_unique( array_filter( explode( ',', $member->mgroup_others ) ) );
			if ( array_diff( $oldSecondaryGroups, $newSecondaryGroups ) or array_diff( $newSecondaryGroups, $oldSecondaryGroups ) )
			{
				$member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'api', 'apiKey' => $this->apiKey?->id, 'client' => $this->client?->client_id, 'old' => $oldSecondaryGroups, 'new' => $newSecondaryGroups ), $this->member ?: FALSE );
			}

			return new Response( 200, $member->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/7', 404 );
		}
	}
	
	/**
	 * DELETE /core/members/{id}
	 * Deletes a member
	 *
	 * @apiclientonly
	 * @apiparam	string	contentAction		delete|hide|leave
	 * @apiparam	bool	contentAnonymize	Keep member name = 1, anonymize content = 0
	 * @param		int		$id					ID Number
	 * @throws		1C292/2	INVALID_ID			The member ID does not exist
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ) : Response
	{
		try
		{
			$member = Member::load( $id );
			if ( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			if( Request::i()->contentAction )
			{
				switch( Request::i()->contentAction )
				{
					case 'delete':
						$member->hideOrDeleteAllContent( 'delete' );
						$member->delete( FALSE );
						break;
					case 'hide':
						$member->hideOrDeleteAllContent( 'hide' );
						$member->delete( TRUE, Request::i()->contentAnonymize ?: FALSE );
						break;
					case 'leave':
						$member->delete( TRUE, Request::i()->contentAnonymize ?: FALSE );
						break;
				}
			}
			else
			{
				$member->delete();
			}

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C292/2', 404 );
		}
	}

	/**
	 * GET /core/members/{id}/follows
	 * Get list of items a member is following
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\core\Followed\Follow>
	 * @throws		2C292/F	NO_PERMISSION	The authorized user does not have permission to view the follows
	 * @throws		2C292/I	INVALID_ID		The member could not be found
	 * @return PaginatedResponse<Follow>
	 */
	public function GETitem_follows( int $id ) : PaginatedResponse
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* We can only adjust follows for ourself, if we are an authorized member */
			if ( $this->member and $member->member_id != $this->member->member_id )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/F', 403 );
			}

			/* Return */
			return new PaginatedResponse(
				200,
				Db::i()->select( '*', 'core_follow', array( 'follow_member_id=?', $member->member_id ), "follow_added ASC" ),
				isset( Request::i()->page ) ? Request::i()->page : 1,
				'IPS\core\Followed\Follow',
				Db::i()->select( 'COUNT(*)', 'core_follow', array( 'follow_member_id=?', $member->member_id ) )->first(),
				$this->member,
				isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
			);
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/I', 404 );
		}
	}

	/**
	 * POST /core/members/{id}/follows
	 * Store a new follow for the member
	 *
	 * @param		int		$id			ID Number
	 * @reqapiparam	string	followApp	Application of the content to follow
	 * @reqapiparam	string	followArea	Area of the content to follow
	 * @reqapiparam	int		followId	ID of the content to follow
	 * @apiparam	bool	followAnon	Whether or not to follow anonymously
	 * @apiparam	bool	followNotify	Whether or not to receive notifications
	 * @apiparam	string	followType		Type of notification to receive (immediate=send a notification immediately, daily=daily notification digest, weekly=weekly notification digest)
	 * @apireturn		\IPS\core\Followed\Follow
	 * @throws		2C292/G	NO_PERMISSION	The authorized user does not have permission to view the follows
	 * @throws		2C292/H	INVALID_ID		The member could not be found
	 * @throws		2C292/J	INVALID_CONTENT	The app, area or content ID could not be found
	 * @return Response
	 */
	public function POSTitem_follows( int $id ) : Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* We can only adjust follows for ourself, if we are an authorized member */
			if ( $this->member and $member->member_id != $this->member->member_id )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/G', 403 );
			}

			/* Make sure follow app/area/id is valid (Phil I'm looking at you) */
			try
			{
				/* @var Item|Member|Club $classToFollow */
				$classToFollow = Follow::getClassToFollow( Request::i()->followApp, Request::i()->followArea );

				$thingToFollow	= $classToFollow::load( (int) Request::i()->followId );
			}
			catch( \Exception $e )
			{
				throw new Exception( 'INVALID_CONTENT', '2C292/J', 404 );
			}

			/* If we are already following this, update instead of insert */
			try
			{
				$follow = Follow::load( md5( Request::i()->followApp . ';' . Request::i()->followArea . ';' . Request::i()->followId . ';' . $member->member_id ) );
			}
			catch( OutOfRangeException $e )
			{
				$follow = new Follow;
				$follow->member_id	= $member->member_id;
				$follow->app		= Request::i()->followApp;
				$follow->area		= Request::i()->followArea;
				$follow->rel_id		= Request::i()->followId;
			}

			$follow->is_anon	= ( isset( Request::i()->followAnon ) ) ? (int) Request::i()->followAnon : 0;
			$follow->notify_do	= ( isset( Request::i()->followType ) AND Request::i()->followType == 'none' ) ? 0 : ( ( isset( Request::i()->followNotify ) ) ? (int) Request::i()->followNotify : 1 );
			$follow->notify_freq	= ( isset( Request::i()->followType ) AND in_array( Request::i()->followType, array( 'none', 'immediate', 'daily', 'weekly' ) ) ) ? Request::i()->followType : 'immediate';
			$follow->save();
			
			/* Delete cache */
			Db::i()->delete( 'core_follow_count_cache', array( 'class=? AND id=?', $classToFollow, $follow->rel_id ) );
			
			/* If we're following a member, add points */
			if ( $follow->app == 'core' and $follow->area == 'member' )
			{
				/* Give points */
				$receiver = Member::load( $follow->rel_id );
				$receiver->achievementAction( 'core', 'FollowMember', [
					'giver' => Member::load( $follow->member_id )
				] );
			}
			else if ( in_array( 'IPS\Node\Model', class_parents( $classToFollow ) ) )
			{
				$member->achievementAction( 'core', 'FollowNode', $classToFollow::load( Request::i()->followId ) );
			}
			else if ( in_array( 'IPS\Content\Item', class_parents( $classToFollow ) ) )
			{
				/* @var Item $classToFollow */
				$item = $classToFollow::load( Request::i()->followId );
				$member->achievementAction( 'core', 'FollowContentItem', [
					'item' => $item,
					'author' => $item->author()
				] );
			}
			
			/* If we're following a club, follow all nodes in the club automatically */
			if ( $follow->app == 'core' and $follow->area == 'club' )
			{
				$thing = Club::loadAndCheckPerms( $follow->rel_id );
				
				foreach ( $thing->nodes() as $node )
				{
					$itemClass = $node['node_class']::$contentItemClass;
					$followApp = $itemClass::$application;
					$followArea = mb_strtolower( mb_substr( $node['node_class'], mb_strrpos( $node['node_class'], '\\' ) + 1 ) );

					/* If we are already following this, update instead of insert */
					try
					{
						$nodeFollow = Follow::load( md5( $followApp . ';' . $followArea . ';' . $node['node_id'] . ';' . $member->member_id ) );
					}
					catch( OutOfRangeException $e )
					{
						$nodeFollow = new Follow;
						$nodeFollow->member_id	= $member->member_id;
						$nodeFollow->app		= $followApp;
						$nodeFollow->area		= $followArea;
						$nodeFollow->rel_id		= $node['node_id'];
					}
					
					$nodeFollow->is_anon	= $follow->is_anon;
					$nodeFollow->notify_do	= $follow->notify_do;
					$nodeFollow->notify_freq	= $follow->notify_freq;
					$nodeFollow->save();

					/* Delete cache */
					Db::i()->delete( 'core_follow_count_cache', array( 'class=? AND id=?', $itemClass, $nodeFollow->rel_id ) );
				}
			}

			return new Response( 200, $follow->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/H', 404 );
		}
	}

	/**
	 * DELETE /core/members/{id}/follows/{followKey}
	 * Delete a follow for the member
	 *
	 * @param		int		$id			ID Number
	 * @param		string	$followKey	Follow Key
	 * @throws		2C292/C	INVALID_ID			The member could not be found
	 * @throws		2C292/E	INVALID_FOLLOW_KEY	The follow does not exist or does not belong to this member
	 * @throws		2C292/D	NO_PERMISSION		The authorized user does not have permission to delete the follow
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem_follows( int $id, string $followKey='' ) : Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* We can only adjust follows for ourself, if we are an authorized member */
			if ( $this->member and $member->member_id != $this->member->member_id )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/D', 403 );
			}
			
			/* Load our follow, and make sure it belongs to the specified member */
			try
			{
				if( !$followKey )
				{
					throw new UnderflowException;
				}

				$follow = Db::i()->select( '*', 'core_follow', array( 'follow_id=?', $followKey ) )->first();

				if( $follow['follow_member_id'] != $member->member_id )
				{
					throw new UnderflowException;
				}
			}
			catch( UnderflowException $e )
			{
				throw new Exception( 'INVALID_FOLLOW_KEY', '2C292/E', 404 );
			}

			/* Unfollow */
			Db::i()->delete( 'core_follow', array( 'follow_id=?', $followKey ) );

			/* If this is a club, unfollow all nodes in the club too */
			if( $follow['follow_app'] == 'core' AND $follow['follow_area'] == 'club' )
			{
				$class = 'IPS\Member\Club';

				try
				{
					$thing = $class::loadAndCheckPerms( $follow['follow_rel_id'] );

					foreach ( $thing->nodes() as $node )
					{
						$itemClass = $node['node_class']::$contentItemClass;
						$followApp = $itemClass::$application;
						$followArea = mb_strtolower( mb_substr( $node['node_class'], mb_strrpos( $node['node_class'], '\\' ) + 1 ) );
						
						Db::i()->delete( 'core_follow', array( 'follow_id=? AND follow_member_id=?', md5( $followApp . ';' . $followArea . ';' . $node['node_id'] . ';' .  $member->member_id ), $member->member_id ) );
					}
				}
				catch ( OutOfRangeException $e ){}
			}

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/C', 404 );
		}
	}

	/**
	 * GET /core/members/{id}/notifications
	 * Get list of notifications for a member
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\Notification\Inline>
	 * @throws		2C292/K	NO_PERMISSION	The authorized user does not have permission to view the follows
	 * @throws		2C292/L	INVALID_ID		The member could not be found
	 * @return PaginatedResponse<Inline>
	 */
	public function GETitem_notifications( int $id ) : PaginatedResponse
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* We can only fetch notifications for ourself, if we are an authorized member */
			if ( $this->member and $member->member_id != $this->member->member_id )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/K', 403 );
			}

			/* Return */
			return new PaginatedResponse(
				200,
				Db::i()->select( '*', 'core_notifications', array( "`member`=? AND notification_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')", $member->member_id ), "updated_time DESC" ),
				isset( Request::i()->page ) ? Request::i()->page : 1,
				'IPS\Notification\RestApi',
				Db::i()->select( 'COUNT(*)', 'core_notifications', array( "`member`=? AND notification_app IN('" . implode( "','", array_keys( Application::enabledApplications() ) ) . "')", $member->member_id ) )->first(),
				$this->member,
				isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
			);
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/L', 404 );
		}
	}

	/**
	 * GET /core/members/{id}/warnings
	 * Get list of warnings for a member
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\core\Warnings\Warning>
	 * @throws		2C292/M	NO_PERMISSION	The authorized user does not have permission to view the warnings
	 * @throws		2C292/N	INVALID_ID		The member could not be found
	 * @return PaginatedResponse<Warning>
	 */
	public function GETitem_warnings( int $id ) : PaginatedResponse
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* We can only view warnings for ourself, if we are an authorized member */
			if ( $this->member and ( $member->member_id != $this->member->member_id OR !Settings::i()->warn_show_own ) )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/M', 403 );
			}

			/* Return */
			return new PaginatedResponse(
				200,
				Db::i()->select( '*', 'core_members_warn_logs', array( 'wl_member=?', $member->member_id ), "wl_date ASC" ),
				isset( Request::i()->page ) ? Request::i()->page : 1,
				'IPS\core\Warnings\Warning',
				Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( 'wl_member=?', $member->member_id ) )->first(),
				$this->member,
				isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
			);
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/N', 404 );
		}
	}

	/**
	 * GET /core/members/{id}/warning/{warning}
	 * Get a specific warning for a member
	 *
	 * @param		int		$id			ID Number
	 * @param		int		$warning	Warning ID
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn		\IPS\core\Warnings\Warning
	 * @throws		2C292/T	NO_PERMISSION	The authorized user does not have permission to view the warning
	 * @throws		2C292/U	INVALID_ID		The member could not be found
	 * @throws		2C292/V	INVALID_WARNING		The warning could not be found
	 * @return Response
	 */
	public function GETitem_warning( int $id, int $warning = 0 ) : Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			try
			{
				$warning = Warning::load( $warning );
			}
			catch( OutOfRangeException $e )
			{
				throw new Exception( 'INVALID_WARNING', '2C292/V', 404 );
			}

			/* We can only view warnings for ourself, if we are an authorized member */
			if ( $this->member and ( $member->member_id != $this->member->member_id OR !Settings::i()->warn_show_own OR $warning->member != $member->member_id ) )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/T', 403 );
			}

			/* Return */
			return new Response( 200, $warning->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/U', 404 );
		}
	}

	/**
	 * POST /core/members/{id}/warnings
	 * Store a new warning for the member
	 *
	 * @param		int		$id			ID Number
	 * @reqapiparam	int|null		moderator	Member ID of the moderator to issue the warning from
	 * @apiparam	int|null		reason		Warn reason to use for this warning
	 * @apiparam	int|null		points		Points to issue for the warning. Will use the points from the reason if not specified and a reason is, or if the reason does not allow points to be overridden.
	 * @apiparam	int|null		deductPoints	Achievement points to deduct. Will use the points deduction from the reason if not specified and a reason is, or if the reason does not allow deductions to be overriden.
	 * @apiparam	string|null		memberNote	Note to display to the member as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	string|null		moderatorNote	Note to display to moderators as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	bool			acknowledged	Whether the warning should be considered acknowledged by the member or not
	 * @apiparam	datetime|int|null		modQueue		Date to place in moderator queue until or -1 to place in moderator queue indefinitely. NULL to not place member in moderator queue.
	 * @apiparam	datetime|int|null		restrictPosts	Date to restrict posts until or -1 to restrict posts indefinitely. NULL to not restrict posts.
	 * @apiparam	datetime|int|null		suspend			Date to suspend member until or -1 to suspend indefinitely. NULL to not suspend.
	 * @apiparam	datetime|int|null		expire			Date to expire warn points after or -1 to not expire warn points. Will use the warn points expiration from the warn reason if not specified and a reason is, or if the reason does not allow warn point removal to be overridden. NULL to not expire.
	 * @apireturn		\IPS\core\Warnings\Warning
	 * @throws		2C292/G	NO_PERMISSION	The authorized user does not have permission to warn the member
	 * @throws		2C292/O	INVALID_ID		The member could not be found
	 * @throws		1C292/Y	MODERATOR_REQUIRED		When not using an OAuth access token a moderator member ID must be supplied
	 * @throws		1C292/Z	INVALID_DATE	An invalid datetime was supplied for the expire parameter
	 * @throws		1C292/12	INVALID_DATE	An invalid datetime was supplied for the suspend parameter
	 * @throws		1C292/11	INVALID_DATE	An invalid datetime was supplied for the restrictPosts parameter
	 * @throws		1C292/10	INVALID_DATE	An invalid datetime was supplied for the modQueue parameter
	 * @note		The warning will be issued as the current authorized user if using an OAuth access token, otherwise the 'moderator' parameter must be specified to indicate which moderator the warning should be issued from
	 * @return Response
	 */
	public function POSTitem_warnings( int $id ) : Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* Make sure we can warn */
			if ( $this->member and !$this->member->canWarn( $member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/R', 403 );
			}

			/* Make sure we have a moderator */
			if( !$this->member AND ( !Request::i()->moderator OR !Member::load( Request::i()->moderator )->member_id ) )
			{
				throw new Exception( 'MODERATOR_REQUIRED', '1C292/Y', 401 );
			}

			/* Start the warning with the easy stuff */
			$warning = new Warning;
			$warning->date		= time();
			$warning->member	= $member->member_id;
			$warning->moderator	= $this->member ? $this->member->member_id : Member::load( Request::i()->moderator )->member_id;

			$options = array(
				'warn_points'					=> (int) Request::i()->points,
				'warn_reason'					=> Request::i()->reason,
				'warn_cheeve_point_reduction'	=> (int) Request::i()->deductPoints,
				'warn_member_note'				=> Request::i()->memberNote,
				'warn_mod_note'					=> Request::i()->moderatorNote,
				'warn_punishment'				=> array(),
				'warn_remove'					=> NULL,
			);

			if ( $this->member )
			{
				$options['warn_member_note']	= Parser::parseStatic( $options['warn_member_note'], NULL, $this->member, 'core_Modcp' );
				$options['warn_mod_note']		= Parser::parseStatic( $options['warn_mod_note'], NULL, $this->member, 'core_Modcp' );
			}

			if( isset( Request::i()->reason ) )
			{
				try
				{
					$reason = Reason::load( Request::i()->reason );
					if( $reason->cheev_point_reduction AND ( !$options['warn_cheeve_point_reduction'] OR !$reason->cheev_override ) )
					{
						$options['warn_cheeve_point_reduction'] = $reason->cheev_point_reduction;
					}
				}
				catch( OutOfRangeException $e ){}
			}

			if( isset( Request::i()->expire ) )
			{
				if( Request::i()->expire == -1 )
				{
					$options['warn_remove']	= Request::i()->expire;
				}
				else
				{
					try
					{
						$options['warn_remove'] = new DateTime( Request::i()->expire );
					}
					catch( \Exception $e )
					{
						throw new Exception( 'INVALID_DATE', '1C292/Z', 404 );
					}
				}
			}

			if( Request::i()->modQueue )
			{
				if( Request::i()->modQueue == -1 )
				{
					$options['warn_punishment'][] = 'mq';
					$options['warn_mq'] = Request::i()->modQueue;
				}
				else
				{
					try
					{
						$options['warn_punishment'][] = 'mq';
						$options['warn_mq'] = new DateTime( Request::i()->modQueue );
					}
					catch( \Exception $e )
					{
						throw new Exception( 'INVALID_DATE', '1C292/10', 404 );
					}
				}
			}

			if( Request::i()->restrictPosts )
			{
				if( Request::i()->restrictPosts == -1 )
				{
					$options['warn_punishment'][] = 'rpa';
					$options['warn_rpa'] = Request::i()->restrictPosts;
				}
				else
				{
					try
					{
						$options['warn_punishment'][] = 'rpa';
						$options['warn_rpa'] = new DateTime( Request::i()->restrictPosts );
					}
					catch( \Exception $e )
					{
						throw new Exception( 'INVALID_DATE', '1C292/11', 404 );
					}
				}
			}

			if( Request::i()->suspend )
			{
				if( Request::i()->suspend == -1 )
				{
					$options['warn_punishment'][] = 'suspend';
					$options['warn_suspend'] = Request::i()->suspend;
				}
				else
				{
					try
					{
						$options['warn_punishment'][] = 'suspend';
						$options['warn_suspend'] = new DateTime( Request::i()->suspend );
					}
					catch( \Exception $e )
					{
						throw new Exception( 'INVALID_DATE', '1C292/12', 404 );
					}
				}
			}

			$options = $warning->processWarning( $options, Member::load( $warning->moderator ), TRUE );

			if( Settings::i()->warnings_acknowledge AND isset( Request::i()->acknowledged ) )
			{
				$warning->acknowledged = Request::i()->acknowledged;
			}

			if( !$warning->expire_date )
			{
				$warning->expire_date = -1;
			}

			$warning->save();

			/* Now apply the consequences */
			if ( $warning->points )
			{
				$member->warn_level += $warning->points;
			}
			$consequences = array();
			foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $k => $v )
			{
				if ( $warning->$k )
				{
					$consequences[ $v ] = $warning->$k;
					if ( $warning->$k != -1 )
					{
						$member->$v = DateTime::create()->add( new DateInterval( $warning->$k ) )->getTimestamp();
					}
					else
					{
						$member->$v = $warning->$k;
					}
				}
			}
			$member->members_bitoptions['unacknowledged_warnings'] = Settings::i()->warnings_acknowledge && !$warning->acknowledged;
			$member->save();
			$member->logHistory( 'core', 'warning', array( 'wid' => $warning->id, 'by' => 'api', 'points' => $warning->points, 'reason' => $warning->reason, 'consequences' => $consequences ) );

			return new Response( 200, $warning->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/O', 404 );
		}
	}

	/**
	 * DELETE /core/members/{id}/warnings/{warning}
	 * Delete (undo) a warning for the member
	 *
	 * @param		int		$id			ID Number
	 * @param		int		$warningId	Warning ID
	 * @apiparam	bool	deleteOnly	Delete the warning but do not revoke the consequences
	 * @throws		2C292/P	INVALID_ID			The member could not be found
	 * @throws		2C292/Q	INVALID_WARNING		The warning could not be loaded or the current authorized user does not have permission to delete the warning
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem_warnings( int $id, int $warningId = 0 ) : Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* Load the warning and then undo it */
			try
			{
				$warning = Warning::load( $warningId );

				if( $this->member AND !$warning->canDelete( $this->member ) )
				{
					throw new OutOfRangeException;
				}
			}
			catch( OutOfRangeException $e )
			{
				throw new Exception( 'INVALID_WARNING', '2C292/Q', 404 );
			}

			/* Revoke the warning */
			if( !isset( Request::i()->deleteOnly ) OR !Request::i()->deleteOnly )
			{
				$warning->undo();
			}

			$warning->delete();

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/P', 404 );
		}
	}

	/**
	 * POST /core/members/{id}/warnings/{warning}/acknowledge
	 * Acknowledge a warning
	 *
	 * @param		int		$id			ID Number
	 * @param		int		$warningId	Warning to acknowledge
	 * @apireturn		\IPS\core\Warnings\Warning
	 * @throws		2C292/M	NO_PERMISSION	The authorized user does not have permission to view the warnings
	 * @throws		2C292/N	INVALID_ID		The member could not be found
	 * @return Response
	 */
	public function POSTitem_warnings_acknowledge( int $id, int $warningId = 0 ) : Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* Load the warning */
			try
			{
				$warning = Warning::load( $warningId );
			}
			catch( OutOfRangeException $e )
			{
				throw new Exception( 'INVALID_WARNING', '2C292/W', 404 );
			}

			/* We can only view warnings for ourself, if we are an authorized member */
			if ( $this->member and !$warning->canAcknowledge( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2C292/X', 403 );
			}

			/* Acknowledge it */
			$warning->acknowledged = TRUE;
			$warning->save();

			$member->members_bitoptions['unacknowledged_warnings'] = (bool) Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( "wl_member=? AND wl_acknowledged=0", $member->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
			$member->save();

			/* Return */
			return new Response( 200, $warning->apiOutput() );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/N', 404 );
		}
	}

	/**
	 * POST /core/members/{id}/achievements/{badge}/awardbadge
	 * Award a badge
	 *
	 * @param		int		$id			ID Number
	 * @param int $badgeId
	 * @reqapiparam	int		$badgeId	Badge to award
	 * @apireturn		\IPS\Member
	 * @throws		2C292/13	INVALID_BADGE	The badge could not be found
	 * @throws		2C292/14	INVALID_ID		The member could not be found
	 * @return Response
	 */
	public function POSTitem_achievements_awardbadge( int $id, int $badgeId ): Response
	{
		try
		{
			/* Load member */
			$member = Member::load( $id );
			if( !$member->member_id )
			{
				throw new OutOfRangeException;
			}

			/* Load the badge */
			try
			{
				$badge = Badge::load( $badgeId );
			}
			catch( OutOfRangeException $e )
			{
				throw new Exception( 'INVALID_BADGE', '2C292/13', 404 );
			}

			$member->awardBadge($badge, 0, 0, ['subject'] );
			return new Response( 200, $member->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2C292/14', 404 );
		}
	}

	/**
	 * POST /core/members/{id}/secgroup/{groupId}
	 * Adds a secondary group to the member
	 *
	 * @param		int		$id			ID Number
	 * @param int $groupId
	 * @reqapiparam	int		$groupId	Group To Add
	 * @apireturn		\IPS\Member
	 * @throws		2C292/15	INVALID_GROUP	The group could not be found
	 * @throws		2C292/16	INVALID_MEMBER		The member could not be found
	 * @return Response
	 */
	public function POSTitem_secgroup( int $id, int $groupId ): Response
	{
		/* Load member */
		$member = Member::load( $id );
		if( !$member->member_id )
		{
			throw new Exception( 'INVALID_MEMBER', '2C292/16', 404 );
		}

		try
		{
			$group = Group::load( $groupId );
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_GROUP', '2C292/15', 404 );
		}

		$mgroup_others  = array_filter( explode( ',', $member->mgroup_others ) );

		$newSecondaryGroups = array_unique( array_merge( [$group->g_id], $mgroup_others ) );

		$member->mgroup_others = implode( ',', $newSecondaryGroups );
		$member->save();
		$newSecondaryGroups = array_unique( array_filter( explode( ',', $member->mgroup_others ) ) );
		if ( array_diff( $mgroup_others, $newSecondaryGroups ) or array_diff( $newSecondaryGroups, $mgroup_others ) )
		{
			$member->logHistory( 'core', 'group', array( 'type' => 'secondary', 'by' => 'api', 'old' => $mgroup_others, 'new' => $newSecondaryGroups ), $this->member ?: FALSE );
		}

		return new Response( 201, $member->apiOutput( $this->member ) );
	}

	/**
	 * DELETE /core/members/{id}/secgroup/{groupId}
	 * Removes a secondary group
	 *
	 * @param		int		$id			ID Number
	 * @param int $groupId
	 * @reqapiparam	int		$groupId	Group To Add
	 * @apireturn		Member
	 * @throws		2C292/17	INVALID_GROUP	The group could not be found
	 * @throws		2C292/18	INVALID_MEMBER		The member could not be found
	 * @return Response
	 */
	public function DELETEitem_secgroup( int $id, int $groupId ): Response
	{
		/* Load member */
		$member = Member::load( $id );
		if( !$member->member_id )
		{
			throw new Exception( 'INVALID_MEMBER', '2C292/18', 404 );
		}

		try
		{
			$group = Group::load( $groupId );
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_GROUP', '2C292/17', 404 );
		}

		$secondaryGroups = array_filter( explode( ',', $member->mgroup_others ) );
		$key = array_search( $group->g_id, $secondaryGroups );
		if( $key )
		{
			unset( $secondaryGroups[ $key ] );
		}

		$member->mgroup_others =  implode( ',', array_unique( $secondaryGroups ) );
		$member->save();

		return new Response( 201, $member->apiOutput( $this->member ) );
	}
}
