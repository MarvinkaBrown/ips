<?php
/**
 * @brief		Polls Trait for Content Models
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jan 2013
 */

namespace IPS\Content;

use BadMethodCallException;
use IPS\Member;
use IPS\Poll;
use IPS\Node\Model;
use IPS\Member\Club\Poll as ClubPoll;
use OutOfRangeException;
use SplSubject;

use function defined;
use function header;
use function time;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Polls Trait for Content Models
 */
trait Polls
{
	/**
	 * Can create polls?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	Model|NULL	$container	The container to check if polls can be used in, if applicable
	 * @return	bool
	 */
	public static function canCreatePoll( Member|null $member = NULL, Model|null $container = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $member->group['g_post_polls'];
	}
	
	/**
	 * Get poll
	 *
	 * @return	Poll|NULL
	 * @throws	BadMethodCallException
	 */
	public function getPoll(): Poll|null
	{
		try
		{
			if( $this->mapped('poll') )
			{
				/* If the poll is in a club, return the special extended class */
				if( $this->containerWrapper() AND $club = $this->containerWrapper()->club() )
				{
					$poll		= ClubPoll::load( $this->mapped('poll') );
					$poll->club	= $club;

					return $poll;
				}
				else
				{
					return Poll::load( $this->mapped('poll') );
				}
			}
			
			return NULL;
		}
		catch ( OutOfRangeException )
		{
			return NULL;
		}
	}
	
	/**
	 * Update Last Vote
	 *
	 * @param SplSubject|NULL	$subject	If the class implements SplObserver, the subject.
	 * @return	void
	 */
	public function updateLastVote( SplSubject|null $subject = NULL ): void
	{
		if ( isset( static::$databaseColumnMap['last_vote'] ) )
		{
			$column = static::$databaseColumnMap['last_vote'];
			$this->$column	= time();
		}
		
		$this->save();
	}
}