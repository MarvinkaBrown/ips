<?php
/**
 * @brief		Poll model for clubs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Oct 2018
 */

namespace IPS\Member\Club;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use IPS\Member\Club;
use IPS\Poll as SystemPoll;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Poll Model
 */
class Poll extends SystemPoll
{
	/**
	 * @brief Club this poll exists within
	 */
	public ?Club $club = NULL;

	/**
	 * Member can vote?
	 *
	 * @param	Member|NULL	$member	Member or NULL for currently logged in member
	 * @return	bool
	 */
	public function canVote( Member $member = NULL ) : bool
	{
		$member = $member ?: Member::loggedIn();

		if( !parent::canVote( $member ) )
		{
			return FALSE;
		}

		/* This poll exists in a club, so make sure we have access to the club in order to vote */
		if( $this->club )
		{
			/* The canPost() method simply checks our club membership status (not actual posting privileges) and is appropriate here */
			return $this->club->canPost();
		}

		return TRUE;
	}
}