<?php
/**
 * @brief		Base mutator class for nodes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		9 Nov 2018
 */

namespace IPS\Node\Api\GraphQL;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\GraphQL\SafeException;
use IPS\Member;
use IPS\Node\Model;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base mutator class for nodes
 */
class NodeMutator
{
	/**
	 * Mark a node as read
	 *
	 * @param Model $node		The comment to add a reaction on
	 * @return	Model
	 */
	protected function _markRead( Model $node ): Model
	{
		if( !Member::loggedIn()->member_id )
		{
			throw new SafeException( 'NOT_LOGGED_IN', 'GQL/0004/1', 403 );
		}

		try 
		{
			$itemClass = $node::$contentItemClass;
			$itemClass::markContainerRead( $node );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'INVALID_NODE', 'GQL/0004/2', 403 );
		}

		return $node;
	}
}