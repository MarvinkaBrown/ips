<?php
/**
 * @brief		SearchContent extension: Events
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Events
 * @since		10 Jul 2023
 */

namespace IPS\calendar\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\calendar\Date;
use IPS\calendar\Event;
use IPS\calendar\Event\Comment;
use IPS\calendar\Event\Review;
use IPS\calendar\Icalendar\ICSParser;
use IPS\Content\Search\SearchContentAbstract;
use IPS\Theme;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Events
 */
class Events extends SearchContentAbstract
{
	/**
	 * Return all searchable classes in your application,
	 * including comments and/or reviews
	 *
	 * @return array
	 */
	public static function supportedClasses() : array
	{
		return array(
			Event::class,
			Comment::class,
			Review::class
		);
	}

	/**
	 * Get snippet HTML for search result display
	 *
	 * @param array $indexData Data from the search index
	 * @param array $authorData Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param array $itemData Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param array|NULL $containerData Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param array $reputationData Array of people who have given reputation and the reputation they gave
	 * @param int|NULL $reviewRating If this is a review, the rating
	 * @param string $view 'expanded' or 'condensed'
	 * @return    callable
	 * @throws Exception
	 */
	public static function searchResultSnippet( array $indexData, array $authorData, array $itemData, array|null $containerData, array $reputationData, int|null $reviewRating, string $view ): string
	{
		$startDate = Date::parseTime( $itemData['event_start_date'], !$itemData['event_all_day']);
		$endDate = $itemData['event_end_date'] ? Date::parseTime( $itemData['event_end_date'], !$itemData['event_all_day']) : NULL;
		$nextOccurance = $startDate;
		if ( $itemData['event_recurring'] )
		{
			$occurances = Event::_findOccurances( $startDate, $endDate, $startDate->adjust( "-1 month" ), $startDate->adjust( "+2 years" ), ICSParser::parseRrule( $itemData['event_recurring'] ), NULL, $itemData['event_all_day'] );
			foreach( $occurances as $occurrence )
			{
				if ( $occurrence['startDate'] AND $occurrence['startDate']->mysqlDatetime( FALSE ) >= $startDate->mysqlDatetime( FALSE ) )
				{
					$nextOccurance = $occurrence['startDate'];
					break;
				}
			}
		}

		if( $indexData['index_class'] == Event::class )
		{
			return Theme::i()->getTemplate( 'global', 'calendar', 'front' )->searchResultEventSnippet( $indexData, $itemData, $nextOccurance, $startDate, $endDate, $itemData['event_all_day'], $view == 'condensed' );
		}
		else
		{
			return Theme::i()->getTemplate( 'global', 'calendar', 'front' )->searchResultCommentSnippet( $indexData, $nextOccurance, $startDate, $endDate, $itemData['event_all_day'], $reviewRating, $view == 'condensed' );
		}
	}
}