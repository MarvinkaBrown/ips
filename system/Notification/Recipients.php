<?php
/**
 * @brief		Data Structure for storing notification recipieints - basically a non-unique SplObjectStorage
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Feb 2017
 */

namespace IPS\Notification;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ArrayAccess;
use Countable;
use IPS\Member;
use Iterator;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Data Structure for storing notification recipieints - basically a non-unique SplObjectStorage
 */
class Recipients implements Countable, Iterator, ArrayAccess
{
	/**
	 * @brief	Recipients
	 */
	protected array $recipients = array();
	
	/**
	 * @brief	Count
	 */
	protected int $count = 0;
	
	/**
	 * @brief	Current position
	 */
	protected int $position = 0;
	
	/**
	 * [SplObjectStorage] Add a recipient
	 *
	 * @param	Member	$member		The member object
	 * @param array|null $followData	If the notification is about something being followed, the appropriate row from core_follow
	 * @return	void
	 */
	public function attach( Member $member, array $followData = NULL ) : void
	{
		$this->recipients[] = array( 'member' => $member, 'followData' => $followData );
		$this->count++;
	}
	
	/**
	 * [SplObjectStorage] Remove a recipient
	 *
	 * @param	Member	$member		The member object
	 * @return	void
	 */
	public function detach( Member $member ) : void
	{
		foreach ( $this->recipients as $k => $data )
		{
			if ( $data['member']->member_id == $member->member_id )
			{
				unset( $this->recipients[ $k ] );
				$this->count--;
			}
		}
	}
	
	/**
	 * [Countable] Get a count
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return $this->count;
	}
	
	/**
	 * [Iterator] Get current
	 *
	 * @return	Member
	 */
	public function current(): Member
	{
		return $this->recipients[ $this->position ]['member'];
	}
	
	/**
	 * Get current's info
	 *
	 * @return	array|null
	 */
	public function getInfo(): ?array
	{
		return $this->recipients[ $this->position ]['followData'];
	}
	
	/**
	 * [Iterator] Get key
	 *
	 * @return	int
	 */
	public function key(): int
	{
		return $this->position;
	}
	
	/**
	 * [Iterator] Go to next
	 *
	 * @return	void
	 */
	public function next() : void
	{
		$this->position++;
	}
	
	/**
	 * [Iterator] Rewind
	 *
	 * @return	void
	 */
	public function rewind() : void
	{
		$this->position = 0;
	}
	
	/**
	 * [Iterator] Is valid?
	 *
	 * @return	bool
	 */
	public function valid(): bool
	{
		return $this->position < $this->count;
	}
	
	/**
	 * [ArrayAccess] Offset exists?
	 *
	 * @param	mixed	$offset	The offset
	 * @return	bool
	 */
	public function offsetExists( mixed $offset ): bool
	{
		return $offset < $this->count;
	}
	
	/**
	 * [ArrayAccess] Get offset
	 *
	 * @param	mixed	$offset	The offset
	 * @return	Member
	 */
	public function offsetGet( mixed $offset ): Member
	{
		return $this->recipients[ $offset ]['member'];
	}
	
	/**
	 * [ArrayAccess] Set offset
	 *
	 * @param	mixed	$offset	The offset
	 * @param	mixed	$value	The value
	 * @return	void
	 */
	public function offsetSet( mixed $offset, mixed $value ): void
	{
		$this->recipients[] = array( 'member' => $value, 'followData' => NULL );
		
		if ( $offset > $this->count )
		{
			$this->count++;
		}
	}
	
	/**
	 * [ArrayAccess] Unset offset
	 *
	 * @param	mixed	$offset	The offset
	 * @return	void
	 */
	public function offsetUnset( mixed $offset ) : void
	{
		if ( isset( $this->recipients[ $offset ] ) )
		{
			unset( $this->recipients[ $offset ] );
			$this->count--;
		}
	}
}