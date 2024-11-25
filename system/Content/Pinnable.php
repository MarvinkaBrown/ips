<?php
/**
 * @brief		Pinnable Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Nov 2013
 */

namespace IPS\Content;

use IPS\Member;

use function defined;
use function header;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Pinnable Trait for Content Models/Comments
 */
trait Pinnable
{
	/**
	 * Pinned
	 *
	 * @return	bool
	 */
	public function pinned(): bool
	{
		return (bool) $this->mapped('pinned');
	}
	
	/**
	 * Can pin?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canPin( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'pin', $member ) )
		{
			return false;
		}

		if ( $this->pinned() )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		return ( $member->member_id and static::modPermission( 'pin', $member, $this->containerWrapper() ) );
	}
	
	/**
	 * Can unpin?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canUnpin( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'unpin', $member ) )
		{
			return false;
		}

		if ( !$this->pinned() )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		return ( $member->member_id and static::modPermission( 'unpin', $member, $this->containerWrapper() ) );
	}
}