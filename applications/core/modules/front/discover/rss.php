<?php
/**
 * @brief		rss
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Oct 2016
 */

namespace IPS\core\modules\front\discover;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Rss as RssClass;
use IPS\Dispatcher\Controller;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * rss
 */
class rss extends Controller
{
	/**
	 * Display Feed
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		try
		{
			$feed = RssClass::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C340/1', 404, '' );
		}
		
		if ( !$feed->_enabled )
		{
			Output::i()->error( 'node_error_no_perm', '2C340/2', 403, '' );
		}
		
		/* Specific Member? */
		if ( isset( Request::i()->member_id ) AND isset( Request::i()->key ) )
		{
			/* Load Member */
			$member = Member::load( Request::i()->member_id );
			
			/* Make sure we have an actual member, and that the key matches. If it doesn't, we can bubble up and see if the feed works for guests, and just use that */
			if ( $member->member_id AND Login::compareHashes( $member->getUniqueMemberHash(), (string) Request::i()->key ) )
			{
				/* Make sure we have access to this feed. */
				if ( $feed->groups == '*' OR $member->inGroup( $feed->groups ) )
				{
					/* Send It */
					Output::i()->sendOutput( $feed->generate( $member ), 200, 'text/xml' );
				}
				else
				{
					Output::i()->error( 'node_error_no_perm', '2C340/3', 403, '' );
				}
			}
		}
		
		/* We're working with a guest. */
		if ( $feed->groups == '*' OR in_array( Settings::i()->guest_group, $feed->groups ) )
		{
			Output::i()->sendOutput( $feed->generate(), 200, 'text/xml' );
		}
		else
		{
			Output::i()->error( 'node_error_no_perm', '2C340/4', 403, '' );
		}
	}
}