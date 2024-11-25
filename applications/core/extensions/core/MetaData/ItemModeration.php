<?php
/**
 * @brief		Meta Data: ItemModeration
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		01 May 2020
 */

namespace IPS\core\extensions\core\MetaData;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content\Item;
use IPS\Member;
use IPS\Member\Group;
use OutOfRangeException;
use function array_keys;
use function array_shift;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Meta Data: ItemModeration
 */
class ItemModeration
{
	/**
	 * Check if an item is set to require approval for new comments.
	 *
	 * @param	Item	$item			The item.
	 * @param	Member|Group|null $memberOrGroup	If set, will check if this member or group can bypass moderation.
	 * @return	bool
	 */
	public function enabled( Item $item, Member|Group|null $memberOrGroup=NULL ): bool
	{
		/* Extract our meta data */
		$meta = $item->getMeta();
		
		/* Is it set in the meta? */
		if ( isset( $meta['core_ItemModeration'] ) )
		{
			/* This is only set once per item. */
			$data = array_shift( $meta['core_ItemModeration'] );
			
			if ( $data['enabled'] AND $memberOrGroup !== NULL )
			{
				if ( $memberOrGroup instanceof Member )
				{
					$check = $memberOrGroup->group['g_avoid_q'];
				}
				elseif ( $memberOrGroup instanceof Group )
				{
					$check = $memberOrGroup->g_avoid_q;
				}
				
				if ( isset( $check ) && $check )
				{
					return FALSE;
				}
				else
				{
					return TRUE;
				}
			}
			else
			{
				return (bool) $data['enabled'];
			}
		}
		
		/* Not set, so just return */
		return FALSE;
	}
	
	/**
	 * Can Toggle
	 *
	 * @param	Item		$item	The item
	 * @param	Member|NULL			$member	The member to check, or NULL for currently logged in member.
	 * @return	bool
	 */
	public function canToggle( Item $item, ?Member $member = NULL ): bool
	{
		if ( !in_array( 'core_ItemModeration', $item::supportedMetaDataTypes() ) )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		
		try
		{
			return $item::modPermission( 'toggle_item_moderation', $member, $item->container() );
		}
		catch( BadMethodCallException $e )
		{
			return $member->modPermission( 'can_toggle_item_moderation_content' );
		}
	}
	
	/**
	 * Enable
	 *
	 * @param	Item	$item	The item
	 * @param	Member|NULL		$member	The member enabling moderation, or NULL for currently logged in member.
	 * @return	void
	 */
	public function enable( Item $item, ?Member $member = NULL ) : void
	{
		$member = $member ?: Member::loggedIn();
		
		$meta = $item->getMeta();
		
		/* If it's set in the data, update it. */
		if ( isset( $meta['core_ItemModeration'] ) )
		{
			$keys = array_keys( $meta['core_ItemModeration'] );
			$id = array_shift( $keys );
			$item->editMeta( $id, array(
				'enabled'	=> true,
				'member'	=> $member->member_id
			) );
		}
		/* Otherwise add it */
		else
		{
			$item->addMeta( 'core_ItemModeration', array(
				'enabled'	=> true,
				'member'	=> $member->member_id
			) );
		}
	}
	
	/**
	 * Disable
	 *
	 * @param	Item	$item	The item
	 * @return	void
	 * @throws	OutOfRangeException
	 */
	public function disable( Item $item ) : void
	{
		$meta = $item->getMeta();
		
		if ( isset( $meta['core_ItemModeration'] ) )
		{
			/* Technically, this should only be stored once, but for sanity reasons just loop and remove any extras that may have slipped in */
			foreach( $meta['core_ItemModeration'] AS $id => $data )
			{
				$item->deleteMeta( $id );
			}
		}
		else
		{
			/* Not set, so throw */
			throw new OutOfRangeException;
		}
	}
}