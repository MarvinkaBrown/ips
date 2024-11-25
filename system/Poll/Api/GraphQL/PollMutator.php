<?php
/**
 * @brief		Base mutator class for polls
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Dec 2018
 */

namespace IPS\Poll\Api\GraphQL;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\GraphQL\SafeException;
use IPS\Member;
use IPS\Poll;
use IPS\Poll\Vote;
use function count;
use function defined;
use function intval;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base mutator class for polls
 */
class PollMutator
{
	/**
	 * Vote in a poll
	 *
	 * @param Poll $poll Poll reference
	 * @param array|null $votes
	 * @return    void
	 */
	protected function _vote( Poll $poll, ?array $votes ) : void
	{
		if( !$poll instanceof Poll )
		{
			throw new SafeException( 'INVALID_POLL', 'GQL/0007/1', 403 );
		}

		if( !$poll->canVote() )
		{
			throw new SafeException( 'CANNOT_VOTE', 'GQL/0007/2', 403 );
		}

		// A null vote is viewing the results and forfeiting the ability to vote again
		if( $votes === NULL || !count( $votes ) )
		{
			$vote = Vote::fromForm( NULL );
		}
		else
		{
			$voteAsForm = array();

			// Rebuild the vote array to match form input
			foreach( $votes as $vote )
			{
				if( is_array( $vote['choices'] ) )
				{
					if( count( $vote['choices'] ) === 1 )
					{
						// When coming from a form we save this as a string without typecasting first, so replicating here for consistency
						$choice = (string) intval( $vote['choices'][0] );
					}
					else
					{
						$choice = $vote['choices'];
					}
				}
				else
				{
					// When coming from a form we save this as a string without typecasting first, so replicating here for consistency
					$choice = (string) intval( $vote['choices'] );
				}

				$voteAsForm[ $vote['id'] ] = $choice;
			}

			$vote = Vote::fromForm( $voteAsForm );
		}

		$poll->addVote( $vote, Member::loggedIn() );
	}
}