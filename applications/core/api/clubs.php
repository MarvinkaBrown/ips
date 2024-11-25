<?php
/**
 * @brief		Clubs API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		7 June 2018
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Application;
use IPS\core\extensions\nexus\Item\ClubMembership;
use IPS\Db;
use IPS\GeoLocation;
use IPS\Member;
use IPS\Member\Club;
use IPS\nexus\Customer;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Clubs API
 */
class clubs extends Controller
{
	/**
	 * GET /core/clubs
	 * Get list of clubs
	 *
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apiparam	int		member_id		Member ID to return only clubs the member is allowed to view.
	 * @note		For requests using an OAuth Access Token for a particular member, only clubs the authorized user can view will be included and the member_id parameter will be ignored.
	 * @apireturn		PaginatedResponse<IPS\Member\Club>
	 * @return PaginatedResponse<Club>
	 */
	public function GETindex(): PaginatedResponse
	{
		$page		= isset( Request::i()->page ) ? Request::i()->page : 1;
		$perPage	= isset( Request::i()->perPage ) ? Request::i()->perPage : 25;

		$forMember = $this->member;

		if( !$this->member AND isset( Request::i()->member_id ) )
		{
			$forMember = Member::load( Request::i()->member_id );

			if( !$forMember->member_id )
			{
				$forMember = NULL;
			}
		}

		/* Return */
		return new PaginatedResponse(
			200,
			Club::clubs( $forMember, array( ( $page - 1 ) * $perPage, $perPage ), 'created' ),
			$page,
			'IPS\Member\Club',
			Club::clubs( $forMember, array( ( $page - 1 ) * $perPage, $perPage ), 'created', FALSE, array(), NULL, TRUE ),
			$this->member,
			$perPage
		);
	}

	/**
	 * GET /core/clubs/{id}
	 * Get specific club
	 *
	 * @param		int		$id			ID Number
	 * @throws		1C386/1	INVALID_ID	The club does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\Member\Club
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );
			if ( $this->member and !$club->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}

			return new Response( 200, $club->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/1', 404 );
		}
	}

	/**
	 * POST /core/clubs
	 * Create a club
	 *
	 * @reqapiparam	string		name				The club name
	 * @reqapiparam	int			owner				The club owner (if not using an OAuth Access Token for a particular member)
	 * @apiparam	string		about				Information about the club
	 * @apiparam	string		type				Club type (one of public, open, private, readonly or closed). Defaults to open.
	 * @apiparam	bool		approved			Whether the club is approved or not (if not using an OAuth Access Token for a particular member)
	 * @apiparam	bool		featured			Whether the club is featured or not (if not using an OAuth Access Token for a particular member)
	 * @apiparam	float		lat					Latitude of the club
	 * @apiparam	float		long				Longitude of the club
	 * @apiparam	string		showMemberTab		Who can see the list of members: nonmember = Everyone can see, member = Only members can see, moderator = Only moderators can see. Defaults to nonmember.
	 * @apiparam	\IPS\nexus\Money		joiningFee	Cost to join the club (Nexus must be installed, paid clubs must be enabled, and the owner must be allowed to create paid clubs)
	 * @apiparam	\IPS\nexus\Purchase\RenewalTerm		renewalTerm	Renewal term for the club (joiningFee must be set)
	 * @apireturn		\IPS\Member\Club
	 * @note		For requests using an OAuth Access Token for a particular member, the authorized user will be the club owner, otherwise you must pass an owner parameter with a valid member ID to set the club owner
	 * @throws		1C386/3	OWNER_REQUIRED	An owner for the club is required. For requests NOT using an OAuth Access Token for a particular member, you must supply a member ID for the owner property
	 * @throws		1C386/4	NAME_REQUIRED	A name is required for the club
	 * @throws		1C386/J	CANNOT_CREATE	The authorized member or supplied owner cannot create the type of club requested
	 * @throws		1C386/K	CLUB_LIMIT_REACHED	The authorized member or supplied owner has reached the maximum number of clubs they are allowed to create based on group restrictions
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* We need an owner */
		if( !$this->member AND !Request::i()->owner )
		{
			throw new Exception( 'OWNER_REQUIRED', '1C386/3', 400 );
		}

		$owner = $this->member ?: Member::load( Request::i()->owner );

		if( !Request::i()->name )
		{
			throw new Exception( 'NAME_REQUIRED', '1C386/4', 400 );
		}

		$availableTypes = array();

		foreach ( explode( ',', $owner->group['g_create_clubs'] ) as $type )
		{
			if ( $type !== '' )
			{
				$availableTypes[ $type ] = 'club_type_' . $type;
			}
		}

		/* Default club type to 'open' if not specified */
		if( !isset( Request::i()->type ) OR !Request::i()->type )
		{
			Request::i()->type = 'open';
		}

		if ( !$availableTypes OR !in_array( Request::i()->type, array_keys( $availableTypes ) ) )
		{
			throw new Exception( 'CANNOT_CREATE', '1C386/J', 403 );
		}
		
		if ( $owner->group['g_club_limit'] )
		{
			if ( Db::i()->select( 'COUNT(*)', 'core_clubs', array( 'owner=?', $owner->member_id ) )->first() >= $owner->group['g_club_limit'] )
			{
				throw new Exception( 'CLUB_LIMIT_REACHED', '1C386/K', 403 );
			}
		}

		$club = new Club;
		$club->owner	= $owner;

		$this->setClubProperties( $club );

		$club->save();

		$club->addMember( $owner, Club::STATUS_LEADER );
		$club->recountMembers();

		if( Settings::i()->clubs_require_approval and !$club->approved )
		{
			$club->sendModeratorApprovalNotification( $owner );
		}

		return new Response( 201, $club->apiOutput( $this->member ) );
	}

	/**
	 * POST /core/clubs/{id}
	 * Edit a club
	 *
	 * @reqapiparam	string		name				The club name
	 * @apiparam	string		about				Information about the club
	 * @apiparam	string		type				Club type (one of public, open, private, readonly or closed). Defaults to open.
	 * @apiparam	bool		approved			Whether the club is approved or not (if not using an OAuth Access Token for a particular member)
	 * @apiparam	bool		featured			Whether the club is featured or not (if not using an OAuth Access Token for a particular member)
	 * @apiparam	float		lat					Latitude of the club
	 * @apiparam	float		long				Longitude of the club
	 * @apiparam	string		showMemberTab		Who can see the list of members: nonmember = Everyone can see, member = Only members can see, moderator = Only moderators can see
	 * @apiparam	\IPS\nexus\Money		joiningFee	Cost to join the club (Nexus must be installed, paid clubs must be enabled, and the owner must be allowed to create paid clubs)
	 * @apiparam	\IPS\nexus\Purchase\RenewalTerm		renewalTerm	Renewal term for the club (joiningFee must be set)
	 * @param		int		$id			ID Number
	 * @apireturn		\IPS\Member\Club
	 * @throws		1C386/5	INVALID_ID	The club ID was invalid or the authorized member does not have permission to edit it
	 * @throws		1C386/L	CANNOT_CREATE	The authorized member or supplied owner cannot create the type of club requested
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );

			if( $this->member AND !$club->isLeader( $this->member ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/5', 404 );
		}

		/* Make sure the club type is allowed */
		if( isset( Request::i()->type ) )
		{
			$owner = $this->member ?: $club->owner;

			$availableTypes = array();

			foreach ( explode( ',', $owner->group['g_create_clubs'] ) as $type )
			{
				if ( $type !== '' )
				{
					$availableTypes[ $type ] = 'club_type_' . $type;
				}
			}

			if ( !$availableTypes OR !in_array( Request::i()->type, array_keys( $availableTypes ) ) )
			{
				throw new Exception( 'CANNOT_CREATE', '1C386/L', 403 );
			}
		}

		$this->setClubProperties( $club );

		$club->save();

		return new Response( 200, $club->apiOutput( $this->member ) );
	}

	/**
	 * Set common club properties
	 *
	 * @param	Club	$club	The club object to set the properties on
	 * @return	void
	 */
	protected function setClubProperties( Club $club ) : void
	{
		if( Request::i()->name )
		{
			$club->name		= Request::i()->name;
		}

		if( isset( Request::i()->about ) )
		{
			$club->about	= Request::i()->about;
		}

		if( isset( Request::i()->showMemberTab ) AND in_array( Request::i()->showMemberTab, array( 'member', 'nonmember', 'moderator' ) ) )
		{
			$club->show_membertab	= Request::i()->showMemberTab;
		}
		else
		{
			$club->show_membertab	= 'nonmember';
		}

		if( isset( Request::i()->type ) AND in_array( Request::i()->type, array( 'open', 'public', 'readonly', 'closed', 'private' ) ) )
		{
			$club->type	= Request::i()->type;
		}

		if( !$this->member AND isset( Request::i()->approved ) )
		{
			$club->approved	= Request::i()->approved;
		}
		/* Set club approval based on AdminCP settings, but only if this is a new club */
		elseif( !$club->id )
		{
			$club->approved = Settings::i()->clubs_require_approval ? 0 : 1;
		}

		if( !$this->member AND isset( Request::i()->featured ) )
		{
			$club->featured	= Request::i()->featured;
		}

		if( Settings::i()->clubs_locations AND isset( Request::i()->lat ) AND isset( Request::i()->long ) )
		{
			try
			{
				$location = GeoLocation::getByLatLong( Request::i()->lat, Request::i()->long );

				$club->location_json	= json_encode( $location );
				$club->location_lat		= $location->lat;
				$club->location_long	= $location->long;
			}
			catch( \Exception $e ){}
		}

		if( isset( Request::i()->joiningFee ) AND Request::i()->joiningFee )
		{
			if ( $club->owner->member_id AND Application::appIsEnabled( 'nexus' ) and Settings::i()->clubs_paid_on and $club->owner->group['gbw_paid_clubs'] )
			{
				$club->fee = json_encode( Request::i()->joiningFee );

				if ( isset( Request::i()->renewalTerm ) AND Request::i()->renewalTerm )
				{						
					$club->renewal_term = Request::i()->renewalTerm['term'];
					$club->renewal_units = Request::i()->renewalTerm['unit'];
					$club->renewal_price = json_encode( Request::i()->renewalTerm['cost'] );
				}
				else
				{
					$club->renewal_term = 0;
					$club->renewal_units = NULL;
					$club->renewal_price = NULL;
				}
			}
		}
	}

	/**
	 * DELETE /core/clubs/{id}
	 * Delete a club
	 *
	 * @apiclientonly
	 * @param		int		$id			ID Number
	 * @apireturn		null
	 * @throws		1C386/2	INVALID_ID	The club does not exist
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );

			/* Deletions cannot be performed by a regular user */
			if( $this->member )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( \Exception $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/2', 404 );
		}

		/* Get nodes and queue for deletion */
		$nodes = $club->nodes();

		foreach( $nodes as $data )
		{
			try
			{
				$class	= $data['node_class'];
				$node	= $class::load( $data['node_id'] );

				$nodesToQueue = array( $node );
				$nodeToCheck = $node;
				while( $nodeToCheck->hasChildren( NULL ) )
				{
					foreach ( $nodeToCheck->children( NULL ) as $nodeToCheck )
					{
						$nodesToQueue[] = $nodeToCheck;
					}
				}
				
				foreach ( $nodesToQueue as $_node )
				{					
					Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => $class, 'id' => $_node->_id, 'deleteWhenDone' => TRUE, 'additional' => array() ) );
				}
			}
			catch( \Exception $e ){}
		}

		/* Now delete the club and associated data */
		$club->delete();
		Db::i()->delete( 'core_clubs_memberships', array( 'club_id=?', $club->id ) );
		Db::i()->delete( 'core_clubs_node_map', array( 'club_id=?', $club->id ) );
		Db::i()->delete( 'core_clubs_fieldvalues', array( 'club_id=?', $club->id ) );

		return new Response( 200, NULL );
	}

	/**
	 * GET /core/clubs/{id}/members
	 * Get members of a club
	 *
	 * @param		int		$id			ID Number
	 * @throws		1C386/6	INVALID_ID	The club does not exist or the authorized user does not have permission to view it
	 * @throws		1C386/I	NO_MEMBERS_PUBLIC_CLUB	The club is a public club which has no member list
	 * @apiresponse	\IPS\Member		owner		Club owner
	 * @apiresponse	[\IPS\Member]		members		Club members
	 * @apiresponse	[\IPS\Member]		leaders		Club leaders
	 * @apiresponse	[\IPS\Member]		moderators		Club moderators
	 * @apireturn		array
	 * @return Response
	 */
	public function GETitem_members( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );

			if( $this->member AND !$club->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}
			elseif( $club->type === Club::TYPE_PUBLIC )
			{
				throw new BadMethodCallException;
			}

			$members		= array();
			$leaders		= array();
			$moderators		= array();

			foreach( $club->members( array( 'member', 'moderator', 'leader' ), 250, 'core_clubs_memberships.joined DESC', 2 ) as $member )
			{
				$member = Member::constructFromData( $member );

				if( $club->owner != $member )
				{
					if( $club->isLeader( $member ) )
					{
						$leaders[] = $member->apiOutput();
					}
					elseif( $club->isModerator( $member ) )
					{
						$moderators[] = $member->apiOutput();
					}
					else
					{
						$members[] = $member->apiOutput();
					}
				}
			}

			return new Response( 200, array( 'owner' => $club->owner ? $club->owner->apiOutput() : NULL, 'members' => $members, 'leaders' => $leaders, 'moderators' => $moderators ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/6', 404 );
		}
		catch( BadMethodCallException $e )
		{
			throw new Exception( 'NO_MEMBERS_PUBLIC_CLUB', '1C386/I', 400 );
		}
	}

	/**
	 * POST /core/clubs/{id}/members
	 * Add (or invite) a member to a club
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		id			Member (ID) to add to the club
	 * @apiparam	string	status		Status of the member being added or updated (member, invited, requested, banned, moderator, leader)
	 * @apiparam	int		waiveFee	If set to 1 and the request is made by a club leader, the join fee will be waived for the member being invited
	 * @throws		1C386/7	INVALID_ID	The club does not exist or the authorized user does not have permission to add members to it
	 * @throws		1C386/8	INVALID_MEMBER	The member to be added could not be found or the member cannot join the club
	 * @apireturn		array
	 * @apiresponse	\IPS\Member		owner		Club owner
	 * @apiresponse	[\IPS\Member]		members		Club members
	 * @apiresponse	[\IPS\Member]		leaders		Club leaders
	 * @apiresponse	[\IPS\Member]		moderators		Club moderators
	 * @note		If the member already exists they will be updated. This can be used to ban a member from a club or promote a member to a leader, for instance.
	 * @note		If using an API key, the id parameter is required and will indicate the member being added to the club. If using an OAuth access token and the request is made by a club leader, the id parameter is required. If the user is already a member of the club they can be moved to a different status (such as banned or moderator), and if the user is not a member of the club, they will be invited (and the waiveFee parameter can be passed to bypass any club joining fee). If using an OAuth access token and no id is passed, a request to join will be submitted if necessary, or they will be added to the club (the status parameter is ignored). Finally, if using an OAuth access token and an id parameter is provided, an invitation will be sent to that user.
	 * @return Response
	 */
	public function POSTitem_members( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );

			if( $this->member AND isset( Request::i()->id ) )
			{
				$member = Member::load( Request::i()->id );
			}
			else
			{
				$member = $this->member ?: Member::load( Request::i()->id );
			}

			/* Do we have the member? */
			if( !$member OR !$member->member_id )
			{
				throw new Exception( 'INVALID_MEMBER', '1C386/8', 404 );
			}

			$currentStatus = $club->memberStatus( $member );

			/* If this is an API key request, just do it */
			if( !$this->member )
			{
				$newStatus = Request::i()->status ?: Club::STATUS_MEMBER;

				if( !in_array( $newStatus, array( Club::STATUS_MEMBER, Club::STATUS_INVITED, Club::STATUS_REQUESTED, Club::STATUS_INVITED_BYPASSING_PAYMENT, Club::STATUS_WAITING_PAYMENT, Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR, Club::STATUS_DECLINED, Club::STATUS_BANNED, Club::STATUS_MODERATOR, Club::STATUS_LEADER ) ) )
				{
					$newStatus = Club::STATUS_MEMBER;
				}

				$club->addMember( $member, $newStatus, TRUE );
			}
			/* If this is an OAuth member request for their _own_ account, we need to check joining fees, etc. */
			elseif( $member === $this->member )
			{
				if( !$club->canJoin( $member ) )
				{
					throw new Exception( 'CANNOT_JOIN', '1C386/M', 403 );
				}

				/* If this is an open club, or the member was invited, or they have mod access anyway go ahead and add them */
				if ( in_array( $currentStatus, array( Club::STATUS_INVITED, Club::STATUS_INVITED_BYPASSING_PAYMENT, Club::STATUS_WAITING_PAYMENT ) ) or $club->type === Club::TYPE_OPEN or $member->modPermission('can_access_all_clubs') )
				{
					/* Unless they have to pay */
					if ( $club->isPaid() and $currentStatus !== Club::STATUS_INVITED_BYPASSING_PAYMENT )
					{
						if ( $club->joiningFee() )
						{
							$club->generateInvoice( Customer::load( $member->member_id ) );
						}
					}
					else
					{
						$club->addMember( $member, Club::STATUS_MEMBER, TRUE, NULL, NULL, TRUE );
						$club->recountMembers();
					}
				}
				/* Otherwise, add the request */
				else
				{
					$club->addMember( $member, Club::STATUS_REQUESTED, TRUE );
				}
			}
			/* If this is an OAuth request for someone else's account and we are not a leader, then we should send an invite */
			elseif( !$club->isLeader( $this->member ) )
			{
				if ( !$club->canInvite( $this->member ) )
				{
					throw new Exception( 'CANNOT_INVITE', '1C386/N', 403 );
				}

				$club->addMember( $member, $club::STATUS_INVITED, TRUE, $member, $this->member, TRUE );
				$club->sendInvitation( $this->member, array( $member ) );
			}
			/* And finally, if this is an OAuth request for someone else's account and we are the leader, we should either send an invite or promote the member if the status is mod/leader */
			elseif( $club->isLeader( $this->member ) )
			{
				/* If the member is not currently a part of the club, send an invite */
				if( $currentStatus === NULL )
				{
					$status = $club::STATUS_INVITED;
					if ( $club->isPaid() and Request::i()->waiveFee )
					{
						$status = $club::STATUS_INVITED_BYPASSING_PAYMENT;
					}

					$club->addMember( $member, $status, TRUE, $this->member, $this->member, TRUE );
					$club->sendInvitation( $this->member, array( $member ) );
				}
				elseif( !in_array( Request::i()->status, array( Club::STATUS_REQUESTED, Club::STATUS_INVITED, Club::STATUS_INVITED_BYPASSING_PAYMENT ) ) )
				{
					$club->addMember( $member, Request::i()->status, TRUE, $this->member, $this->member, TRUE );
				}
				else
				{
					throw new Exception( 'CANNOT_INVITE', '1C386/O', 403 );
				}
			}

			$club->recountMembers();

			return $this->GETitem_members( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/7', 404 );
		}
	}

	/**
	 * DELETE /core/clubs/{id}/members/{member}
	 * Remove a member from a club
	 *
	 * @param		int		$id			ID Number
	 * @param		int		$memberId	Member (ID) to remove
	 * @throws		1C386/A	INVALID_ID	The club does not exist or the current authorized member is not a leader of the club
	 * @throws		1C386/9	INVALID_MEMBER	The member to be deleted could not be found
	 * @apiresponse	\IPS\Member		owner		Club owner
	 * @apiresponse	[\IPS\Member]		members		Club members
	 * @apiresponse	[\IPS\Member]		leaders		Club leaders
	 * @apiresponse	[\IPS\Member]		moderators		Club moderators
	 * @apireturn		array
	 * @return Response
	 */
	public function DELETEitem_members( int $id, int $memberId = 0 ): Response
	{
		try
		{
			$club = Club::load( $id );

			if( $this->member AND !$club->isLeader( $this->member ) )
			{
				throw new OutOfRangeException;
			}

			$member = Member::load( $memberId );

			if( !$member->member_id )
			{
				throw new Exception( 'INVALID_MEMBER', '1C386/9', 404 );
			}

			$club->removeMember( $member );
			$club->recountMembers();

			/* Cancel purchase */
			if ( Application::appIsEnabled('nexus') and Settings::i()->clubs_paid_on )
			{
				foreach ( ClubMembership::getPurchases( Customer::load( $member->member_id ), $club->id ) as $purchase )
				{
					$purchase->cancelled = TRUE;
					$purchase->member->log( 'purchase', array( 'type' => 'cancel', 'id' => $purchase->id, 'name' => $purchase->name, 'by' => 'api' ) );
					$purchase->can_reactivate = FALSE;
					$purchase->save();
				}
			}

			return $this->GETitem_members( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/A', 404 );
		}
	}

	/**
	 * GET /core/clubs/contenttypes
	 * Get content types that can be created in clubs
	 *
	 * @apiparam	int			memberId	Restrict the returned types to only those that can be created by the supplied member ID
	 * @note	For requests using an OAuth Access Token for a particular member, the memberId parameter is ignored and the authorized member is checked
	 * @apiresponse	array	contentTypes	Available content types
	 * @apireturn		array
	 * @return Response
	 */
	public function GETcontenttypes(): Response
	{
		$member = $this->member ?: ( ( isset( Request::i()->memberId ) ) ? Member::load( Request::i()->memberId ) : NULL );

		return new Response( 200, array( 'contentTypes' => Club::availableNodeTypes( $member ) ) );
	}

	/**
	 * GET /core/clubs/{id}/nodes
	 * Get nodes belonging to a particular club
	 *
	 * @param		int		$id			ID Number
	 * @throws		1C386/6	INVALID_ID	The club does not exist or the authorized user does not have permission to view it
	 * @apiresponse	[\IPS\Node\Model]		nodes		Club nodes
	 * @apireturn		array
	 * @return Response
	 */
	public function GETitem_nodes( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );

			if( $this->member AND !$club->canView( $this->member ) )
			{
				throw new OutOfRangeException;
			}

			/* Format in a useful manner */
			$nodes = array();

			foreach( $club->nodes() as $node )
			{
				$class = $node['node_class'];
				$node = $class::load( $node['node_id'] );

				if( $node->canView( $this->member ?: NULL ) )
				{
					$nodes[] = $node->apiOutput();
				}
			}

			return new Response( 200, array( 'nodes' => $nodes ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/B', 404 );
		}
	}

	/**
	 * DELETE /core/clubs/{id}/nodes
	 * Delete a node from a club
	 *
	 * @param		int		$id			ID Number
	 * @reqapiparam	int		id			Node (ID) to remove
	 * @reqapiparam	string	class		Node (class) to remove
	 * @throws		1C386/H	INVALID_ID	The club does not exist or the current authorized member is not a leader of the club
	 * @throws		1C386/G	INVALID_NODE	The node to be deleted could not be found or the authorized user does not have permission to delete it
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem_nodes( int $id ): Response
	{
		try
		{
			$club = Club::load( $id );

			if( $this->member AND !$club->isLeader( $this->member ) )
			{
				throw new OutOfRangeException;
			}

			try
			{
				$class = Request::i()->class;
				
				if( !is_subclass_of( $class, "\IPS\Node\Model" ) )
				{
					throw new OutOfRangeException;
				}
				
				$node = $class::load( (int) Request::i()->id );

				if ( !$node->club() or $node->club()->id !== $club->id )
				{
					throw new OutOfRangeException;
				}

				/* Permission check */
				if( $this->member )
				{
					$itemClass = $node::$contentItemClass;
					if ( !$node->modPermission( 'delete', $this->member ) and $itemClass::contentCount( $node, TRUE, TRUE, TRUE, 1 ) )
					{
						throw new OutOfRangeException;
					}
				}
			}
			catch( OutOfRangeException $e )
			{
				throw new Exception( 'INVALID_NODE', '1C386/G', 404 );
			}

			Db::i()->delete( 'core_clubs_node_map', array( 'club_id=? AND node_class=? AND node_id=?', $club->id, $class, $node->_id ) );
			$node->deleteOrMoveFormSubmit( array() );

			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1C386/H', 404 );
		}
	}
}