<?php
/**
 * @brief		Activity stream items extension: StreamItems
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		21 Feb 2017
 */

namespace IPS\calendar\extensions\core\StreamItems;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Event;
use IPS\Content\Search\Result\Custom;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\StreamItemsAbstract;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Activity stream items extension: StreamItems
 */
class StreamItems extends StreamItemsAbstract
{
	/**
	 * Is there content to display?
	 *
	 * @param Member|null $author		The author to limit extra items to
	 * @param int|null $lastTime	If provided, only items since this date are included. If NULL, it works out which to include based on what results are being shown
	 * @param int|null $firstTime	If provided, only items before this date are included. If NULL, it works out which to include based on what results are being shown
	 * @return	array Array of \IPS\Content\Search\Result\Custom objects
	 */
	public function extraItems( Member $author=NULL, int $lastTime=NULL, int $firstTime=NULL ): array
	{
		$rsvps = array();

		/* RSVP */
		$where = array( array( 'rsvp_date>? and calendar_event_rsvp.rsvp_response=?', $lastTime, 1 ) );
		if ( $firstTime )
		{
			$where[] = array( 'rsvp_date<?', $firstTime );
		}
		if ( $author )
		{
			$where[] = array( 'calendar_event_rsvp.rsvp_member_id=?', $author->member_id );
		}
		foreach ( Db::i()->select( '*', 'calendar_event_rsvp', $where, 'rsvp_date DESC', 10 ) as $rsvp )
		{
			try
			{
				$event = Event::load( $rsvp[ 'rsvp_event_id' ] );
				if( $event->canView() )
				{
					$member = Member::load( $rsvp['rsvp_member_id'] );
					$title = htmlspecialchars( $event->title, ENT_DISALLOWED, 'UTF-8', FALSE );
					$rsvps[] = new Custom( DateTime::ts( $rsvp[ 'rsvp_date' ] ), Member::loggedIn()->language()->addToStack( 'calendar_activity_stream_rsvp', FALSE, array( 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( $member ), $event->url(), $title ) ) ) );
				}
			}
			catch ( OutOfRangeException $e )
			{
				/* Event doesn't exist */
			}

		}

		/* Return */
		if ( !empty( $rsvps ) )
		{
			return $rsvps;
		}

		return array();
	}

}