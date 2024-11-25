<?php
/**
 * @brief		Lockable Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Nov 2013
 */

namespace IPS\Content;

use BadMethodCallException;
use IPS\Member;

use function defined;
use function get_class;
use function header;
use function explode;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Lockable Trait for Content Models/Comments
 */
trait Lockable
{
	/**
	 * Is locked?
	 *
	 * @return	bool
	 * @throws	BadMethodCallException: bool
	 */
	public function locked(): bool
	{
		if ( isset( static::$databaseColumnMap['locked'] ) )
		{
			return (bool) $this->mapped('locked');
		}

		if( isset( static::$databaseColumnMap['status'] ) )
		{
			return ( $this->mapped('status') == 'closed' );
		}

		throw new BadMethodCallException;
	}
	
	/**
	 * Can lock?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canLock( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'lock', $member ) )
		{
			return false;
		}

		if ( $this->locked() === TRUE )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		
		if( $member->member_id and static::modPermission( 'lock', $member, $this->containerWrapper() ) )
		{
			return TRUE;
		}

		if( ( $member->group['g_lock_unlock_own'] == '1' or in_array( get_class( $this ), explode( ',', $member->group['g_lock_unlock_own'] ) ) ) AND $member->member_id == $this->author()->member_id )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * Can unlock?
	 *
	 * @param	Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canUnlock( ?Member $member=NULL ): bool
	{
		if( !$this->actionEnabled( 'unlock', $member ) )
		{
			return false;
		}

		if ( $this->locked() === FALSE )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();

		if( $member->member_id and static::modPermission( 'unlock', $member, $this->containerWrapper() ) )
		{
			return TRUE;
		}

		if( $member->group['g_lock_unlock_own'] AND $member->member_id == $this->author()->member_id )
		{
			return TRUE;
		}

		return FALSE;
	}
}