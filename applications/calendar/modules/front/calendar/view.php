<?php
/**
 * @brief		Calendar Views
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		23 Dec 2013
 */

namespace IPS\calendar\modules\front\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use IPS\calendar\Calendar;
use IPS\calendar\Date;
use IPS\calendar\Event;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function array_slice;
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
 * Calendar Views
 */
class view extends Controller
{
	/**
	 * @brief	Calendar we are viewing
	 */
	protected ?Calendar $_calendar = NULL;

	/**
	 * @brief	Date object for the current day
	 */
	protected ?Date $_today	= NULL;
	
	/**
	 * @brief	Root nodes
	 */
	protected ?array $roots	= NULL;

	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* We aren't showing a sidebar in Calendar */
		Output::i()->sidebar['enabled'] = FALSE;
		Calendar::addCss();

		/* Show the RSS link */
		if ( Settings::i()->calendar_rss_feed )
		{
			$urls = $this->_downloadLinks();
			Output::i()->rssFeeds['calendar_rss_title'] = $urls['rss'];
		}

		/* Is there only one calendar? */
		$this->roots	= Settings::i()->club_nodes_in_apps ? Calendar::rootsWithClubs() : Calendar::roots();
		if ( count( $this->roots ) == 1 AND !isset( Request::i()->id ) )
		{
			$root				= array_shift( $this->roots );
			$this->_calendar	= Calendar::loadAndCheckPerms( $root->_id );
		}

		/* Are we viewing a specific calendar only? */
		if( Request::i()->id )
		{
			try
			{
				$this->_calendar	= Calendar::loadAndCheckPerms( Request::i()->id );
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2L182/2', 404, '' );
			}

			/* If we're viewing a club, set the breadcrumbs appropriately */
			if ( $club = $this->_calendar->club() )
			{
				$this->_calendar->clubCheckRules();
				
				$club->setBreadcrumbs( $this->_calendar );
			}
			else
			{
				Output::i()->breadcrumb[] = array( NULL, $this->_calendar->_title );
			}
			
			/* Update Views */
			if ( !Request::i()->isAjax() )
			{
				$this->_calendar->updateViews();
			}
		}

		if( $this->_calendar !== NULL AND $this->_calendar->_id )
		{
			Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_calendars' ) ] = array( 'type' => 'calendar_event', 'nodes' => $this->_calendar->_id );
		}

		$this->_today	= Date::getDate();

		/* Get the date jumper - do this first in case we need to redirect */
		$jump		= $this->_jump();

		if( !Request::i()->isAjax() )
        {
            Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_browse.js', 'calendar', 'front' ) );
        }

		/* Sanitize the parameters */
		foreach( [ 'y', 'm', 'd' ] as $param )
		{
			if( isset( Request::i()->$param ) and !is_numeric( Request::i()->$param ) )
			{
				unset( Request::i()->$param );
			}
		}

		/* If there is a view requested in the URL, use it */
		if( isset( Request::i()->view ) )
		{
			if( method_exists( $this, '_view' . ucwords( Request::i()->view ) ) )
			{
				$method	= "_view" . ucwords( Request::i()->view );
				$this->$method( $jump );
			}
			else
			{
				$method	= "_view" . ucwords( Settings::i()->calendar_default_view );
				$this->$method( $jump );
			}
		}
		/* Otherwise use ACP default preference */
		else
		{
			Request::i()->view = Settings::i()->calendar_default_view;
			$method	= "_view" . ucwords( Settings::i()->calendar_default_view );
			$this->$method( $jump );
		}

		/* Online User Location */
		if ( $this->_calendar )
		{
			Session::i()->setLocation( $this->_calendar->url(), $this->_calendar->permissions()['perm_view'], 'loc_calendar_viewing_calendar', array( "calendar_calendar_{$this->_calendar->id}" => TRUE ) );
		}
		else
		{
			Session::i()->setLocation( Url::internal( 'app=calendar', 'front', 'calendar' ), array(), 'loc_calendar_viewing_calendar_all' );
		}
	}
	
	/**
	 * Show month view
	 *
	 * @param	Form	$jump	Calendar jump
	 * @return	void
	 */
	protected function _viewMonth( Form $jump ) : void
	{
		/* Get the month data */
		$day		= NULL;

		if( ( !Request::i()->y OR Request::i()->y == $this->_today->year ) AND ( !Request::i()->m OR Request::i()->m == $this->_today->mon ) )
		{
			$day	= $this->_today->mday;
		}

		try
		{
			$date		= Date::getDate( Request::i()->y ?: NULL, Request::i()->m ?: NULL, $day, 12 );

			/* Get the events within this range */
			$events		= Event::retrieveEvents(
				Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ),
				Date::getDate( $date->lastDayOfMonth('year'), $date->lastDayOfMonth('mon'), $date->lastDayOfMonth('mday'), 23, 59, 59 ),
				$this->_calendar
			);
		}
		catch( Exception $e )
		{
			Output::i()->error( 'error_bad_date', '2L182/7', 403, '' );
		}

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count($events) === 0 )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		$output = Theme::i()->getTemplate( 'browse' )->calendarMonth( $this->roots, $date, $events, $this->_today, $this->_calendar, $jump );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->title		= Member::loggedIn()->language()->addToStack('cal_month_title', FALSE, array( 'sprintf' => array( $date->monthName, $date->year ) ) );

			Output::i()->output	= Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$this->roots,
				$this->_calendar,
				$jump,
				$date,
				$this->_downloadLinks()
			);	
		}		
	}
	
	/**
	 * Show week view
	 *
	 * @param	Form	$jump	Calendar jump
	 * @return	void
	 */
	protected function _viewWeek( Form $jump ) : void
	{
		/* Get the week data */
		$week		= Request::i()->w ? explode( '-', Request::i()->w ) : NULL;
		try
		{
			$date		= Date::getDate( $week[0] ?? NULL, $week[1] ?? NULL, $week[2] ?? NULL);
		}
		catch( Exception $e )
		{
			Output::i()->error( 'error_bad_date', '2L182/8', 403, '' );
		}

		$nextWeek	= $date->adjust( '+1 week' );
		$lastWeek	= $date->adjust( '-1 week' );

		/* Get the days of the week - we do this in PHP to help keep template a little cleaner */
		try
		{
			$days	= array();

			for( $i = 0; $i < 7; $i++ )
			{
				$days[]	= Date::getDate( $date->firstDayOfWeek('year'), $date->firstDayOfWeek('mon'), $date->firstDayOfWeek('mday') )->adjust( $i . ' days' );
			}

			/* Get the events within this range */
			$events		= Event::retrieveEvents(
				Date::getDate( $date->firstDayOfWeek('year'), $date->firstDayOfWeek('mon'), $date->firstDayOfWeek('mday') ),
				Date::getDate( $date->lastDayOfWeek('year'), $date->lastDayOfWeek('mon'), $date->lastDayOfWeek('mday'), 23, 59, 59 ),
				$this->_calendar
			);
		}
		catch( Exception $e )
		{
			Output::i()->error( 'error_bad_date', '2L182/C', 403, '' );
		}

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count( $events ) === 0 )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		$output = Theme::i()->getTemplate( 'browse' )->calendarWeek( $this->roots, $date, $events, $nextWeek, $lastWeek, $days, $this->_today, $this->_calendar, $jump );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->title		= Member::loggedIn()->language()->addToStack('cal_week_title', FALSE, array( 'sprintf' => array(
				$date->firstDayOfWeek('monthNameShort'), 
				$date->firstDayOfWeek('mday'),
				$date->firstDayOfWeek('year'),
				$date->lastDayOfWeek('monthNameShort'),
				$date->lastDayOfWeek('mday'),
				$date->lastDayOfWeek('year')
			) ) );

			Output::i()->output	= Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$this->roots,
				$this->_calendar,
				$jump,
				$date,
				$this->_downloadLinks()
			);	
		}		
	}
	
	/**
	 * Show day view
	 *
	 * @param	Form	$jump	Calendar jump
	 * @return	void
	 */
	protected function _viewDay( Form $jump ) : void
	{
		/* Get the day data */
		try
		{
			$date		= Date::getDate( Request::i()->y ?: NULL, Request::i()->m ?: NULL, Request::i()->d ?: NULL );
		}
		catch( Exception $e )
		{
			Output::i()->error( 'error_bad_date', '2L182/9', 403, '' );
		}

		$tomorrow	= clone $date->adjust( '+1 day' );
		$yesterday	= clone $date->adjust( '-1 day' );

		/* Get the events within this range */
		$events		= Event::retrieveEvents( clone $date, clone $date, $this->_calendar );

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count($events) === 0 )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		$dayEvents	= array_fill( 0, 23, array() );
		$dayEvents['allDay']	= array();
		$dayEvents['count']		= 0;

		foreach( $events as $day => $_events )
		{
			foreach( $_events as $type => $event )
			{
				foreach( $event as $_event )
				{
					$dayEvents['count']++;

					if( $_event->all_day AND ( $_event->nextOccurrence( $date, 'startDate' ) AND
						$_event->nextOccurrence( $date, 'startDate' )->strFormat('%d') == $date->mday OR
						$_event->nextOccurrence( $date, 'endDate' ) AND $_event->nextOccurrence( $date, 'endDate' )->strFormat('%d') == $date->mday
					) )
					{
						$dayEvents['allDay'][ $_event->id ]	= $_event;
					}
					else
					{
						if( $_event->nextOccurrence( $date, 'startDate' ) AND $_event->nextOccurrence( $date, 'startDate' )->strFormat('%d') == $date->mday )
						{
							$dayEvents[ $_event->_start_date->hours ][ $_event->id ]	= $_event;
						}
						elseif( $_event->nextOccurrence( $date, 'endDate' ) AND $_event->nextOccurrence( $date, 'endDate' )->strFormat('%d') == $date->mday )
						{
							$dayEvents[ 0 ][ $_event->id ]	= $_event;
						}
					}
				}
			}
		}

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( $dayEvents['count'] === 0 )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		$output = Theme::i()->getTemplate( 'browse' )->calendarDay( $this->roots, $date, $dayEvents, $tomorrow, $yesterday, $this->_today, $this->_calendar, $jump );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->title		= Member::loggedIn()->language()->addToStack('cal_month_day', FALSE, array( 'sprintf' => array( $date->monthName, $date->mday, $date->year ) ) );
			Output::i()->output	= Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$this->roots,
				$this->_calendar,
				$jump,
				$date,
				$this->_downloadLinks()
			);
		}
	}

	/**
	 * @brief	Stream per page
	 */
	public int $streamPerPage	= 50;

	/**
	 * Generate keyed links for RSS/iCal download
	 *
	 * @return	array
	 */
	protected function _downloadLinks() : array
	{		
		$downloadLinks = array( 'iCalCalendar' => '', 'iCalAll' => Url::internal( 'app=calendar&module=calendar&controller=view&do=download', 'front', 'calendar_icaldownload' ), 'rss' => Url::internal( 'app=calendar&module=calendar&controller=view&do=rss', 'front', 'calendar_rss' ) );

		if( $this->_calendar )
		{
			$downloadLinks['iCalCalendar'] = Url::internal( 'app=calendar&module=calendar&controller=view&id=' . $this->_calendar->id . '&do=download', 'front', 'calendar_calicaldownload', $this->_calendar->title_seo );
		}

		if ( Member::loggedIn()->member_id )
		{
			$key = Member::loggedIn()->getUniqueMemberHash();

			if( $this->_calendar )
			{
				$downloadLinks['iCalCalendar'] = $downloadLinks['iCalCalendar']->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
			}
			$downloadLinks['iCalAll'] = $downloadLinks['iCalAll']->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
			$downloadLinks['rss'] = $downloadLinks['rss']->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
		}

		return $downloadLinks;
	}

	/**
	 * Return jump form and redirect if appropriate
	 *
	 * @return	Form
	 */
	protected function _jump() : Form
	{
		/* Build the form */
		$form = new Form;
		$form->add( new Form\Date( 'jump_to', \IPS\DateTime::create(), TRUE, array(), NULL, NULL, NULL, 'jump_to' ) );

		if( $values = $form->values() )
		{
			if( Request::i()->goto )
			{
				$dateToGoTo = \IPS\DateTime::create();
			}
			else
			{
				$dateToGoTo = $values['jump_to'];
			}
			
			if ( $this->_calendar )
			{
				$url = Url::internal( "app=calendar&module=calendar&controller=view&view=day&id={$this->_calendar->_id}&y={$dateToGoTo->format('Y')}&m={$dateToGoTo->format('m')}&d={$dateToGoTo->format('j')}", 'front', 'calendar_calday', $this->_calendar->title_seo );
			}
			else
			{
				$url = Url::internal( "app=calendar&module=calendar&controller=view&view=day&y={$dateToGoTo->format('Y')}&m={$dateToGoTo->format('m')}&d={$dateToGoTo->format('j')}", 'front', 'calendar_day' );
			}
			
			Output::i()->redirect( $url );
		}

		return $form;
	}

	/**
	 * Overview
	 *
	 * @return	void
	 */
	protected function _viewOverview() : void
	{
		$this->_today	= Date::getDate();

		/* Featured events */
		$featured = iterator_to_array( Event::featured( 13, '_rand' ) );

		/* Get the calendars we can view */
		$calendars	= \IPS\Settings::i()->club_nodes_in_apps ? \IPS\calendar\Calendar::rootsWithClubs() : \IPS\calendar\Calendar::roots();

		/* If there are no featured get upcoming */
		if( count( $featured ) === 0 )
		{
			$featured = Event::retrieveEvents(
				$this->_today,
				Date::getDate()->adjust( "next year" ),
				array_keys( $calendars ),
				3,
				FALSE,
				NULL,
				NULL,
				FALSE,
				NULL,
				TRUE
			);
		}

		/* Events near me */
		if( isset( Request::i()->lat ) and isset( Request::i()->lon ) )
		{
			$location = array( 'lat' => Request::i()->lat, 'lon' => Request::i()->lon );
		}
		else
		{
			/* Do an IP lookup */
			try
			{
				$geo = GeoLocation::getRequesterLocation();
				$location = array( 'lat' => $geo->lat, 'lon' => $geo->long );
			}
			catch ( Exception $e )
			{
				$location = array( 'lat' => Settings::i()->map_center_lat, 'lon' => Settings::i()->map_center_lon );
			}
		}

		$nearme	= Event::retrieveEvents(
			$this->_today,
			Date::getDate()->adjust( "next year" ),
			array_keys( $calendars ),
			6,
			FALSE,
			NULL,
			NULL,
			FALSE,
			$location,
			FALSE
		);

		/* Set map markers */
		$mapMarkers = array();
		foreach ( $nearme as $event )
		{
			$mapMarkers[ $event->id ] = array( 'lat' => (float) $event->latitude, 'long' => (float) $event->longitude , 'title' => $event->title );
		}

		/* Are we just returning nearby events? */
		if( Request::i()->isAjax() && Request::i()->get == 'nearMe' )
		{
			$output = Theme::i()->getTemplate( 'events', 'calendar' )->nearMeContent( $nearme, $mapMarkers );
			Member::loggedIn()->language()->parseOutputForDisplay( $output );

			$toReturn = array(
				'content' => $output,
				'lat' => (float) $location['lat'],
				'long' => (float) $location['lon']
			);

			Output::i()->sendOutput( json_encode($toReturn), 200, 'application/json' );
		}

		/* By month */
		$day		= NULL;

		if( ( !Request::i()->y OR Request::i()->y == $this->_today->year ) AND ( !Request::i()->m OR Request::i()->m == $this->_today->mon ) )
		{
			$day	= $this->_today->mday;
		}

		try
		{
			$date		= Date::getDate( Request::i()->y ?: NULL, Request::i()->m ?: NULL, $day, 12 );

			/* Get the events within this range */
			$events		= Event::retrieveEvents(
				Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ),
				Date::getDate( $date->lastDayOfMonth('year'), $date->lastDayOfMonth('mon'), $date->lastDayOfMonth('mday'), 23, 59, 59 ),
				array_keys( $calendars ),
				NULL,
				FALSE,
				NULL,
				NULL,
				FALSE
			);
		}
		catch( InvalidArgumentException $e )
		{
			Output::i()->error( 'error_bad_date', '', 403, '' ); //@todo
		}

		/* Sort */
		if( !isset( Request::i()->get ) or Request::i()->get == 'byMonth' )
		{
			$startDate = Date::getDate( Request::i()->y ?: null, Request::i()->m ?: null, 1, 12 );
		}
		else
		{
			$startDate = Date::getDate();
		}

		@usort( $events, function( $a, $b ) use ( $startDate )
		{
			if( $a->nextOccurrence( $startDate, 'startDate' ) === NULL )
			{
				return -1;
			}

			if( $b->nextOccurrence( $startDate, 'startDate' ) === NULL )
			{
				return 1;
			}

			if ( $a->nextOccurrence( $startDate, 'startDate' )->mysqlDatetime() == $b->nextOccurrence( $startDate, 'startDate' )->mysqlDatetime() )
			{
				return 0;
			}

			return ( $a->nextOccurrence( $startDate, 'startDate' )->mysqlDatetime() < $b->nextOccurrence( $startDate, 'startDate' )->mysqlDatetime() ) ? -1 : 1;
		} );

		/* Pagination */
		$offset = isset( Request::i()->offset ) ? min( [ (int) Request::i()->offset, count( $events ) ] ) : 0;

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count( $events ) === 0 )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}
		else
		{
			$events = array_slice( $events, $offset, $this->streamPerPage );
		}

		/* Return events if we're after those specifically */
		if( Request::i()->isAjax() && Request::i()->get == 'byMonth' )
		{
			$eventHtml = "";

			if( count( $events ) )
			{
				foreach( $events as $idx => $event )
				{
					$eventHtml .= Theme::i()->getTemplate( 'events', 'calendar' )->event( $event, 'normal', $startDate );
				}
			}
			else
			{
				$eventHtml = Theme::i()->getTemplate( 'events', 'calendar' )->noEvents();
			}

			Member::loggedIn()->language()->parseOutputForDisplay( $eventHtml );

			$toReturn = array(
				'count' => count( $events ),
				'html' => $eventHtml
			);

			Output::i()->sendOutput( json_encode( $toReturn ), 200, 'application/json' );
		}

		/* Non-existent page */
		if( $offset > 0 && !count( $events ) )
		{
			Output::i()->error( 'no_events_month', '2L182/B', 404, '' );
		}

		/* Clone date so we can update time without affecting other areas on this page */
		$startTime = clone $date;
		$endTime = clone $date;

		$startTime->setTime(0,0,0);
		$endTime->setTime(23,59,59);

		/* Online */
		$online = Event::retrieveEvents(
			$startTime,
			NULL,
			array_keys( $calendars ),
			NULL,
			FALSE,
			NULL,
			NULL,
			FALSE,
			NULL,
			TRUE
		);

		/* Build an array of month objects for the nav */
		$months = new DatePeriod( (new DateTime)->setDate( date('Y'), date('m'), 1 ), new DateInterval( 'P1M' ), 11 );

		$stream = Theme::i()->getTemplate( 'overview', 'calendar' )->byMonth( $calendars, Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ), $featured, $events, NULL, $months );

		$form = $this->getForm();

		$date = Date::getDate();

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $stream );
		}
		else
		{
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_overview.js', 'calendar', 'front' ) );
			Output::i()->title		= Member::loggedIn()->language()->addToStack( '__app_calendar' );
			Output::i()->output	= Theme::i()->getTemplate( 'overview' )->wrapper( $featured, $nearme, $stream, $form, $mapMarkers, $online, $date, $this->_downloadLinks() );
		}
	}

	/**
	 * Build search form
	 *
	 * @return	Form
	 */
	public function getForm() : Form
	{
		/* Search form */
		$form = new Form('form', 'search', Url::internal( 'app=calendar&module=calendar&controller=view&do=search', 'front', 'calendar_event_search' ) );
		if ( GeoLocation::enabled() )
		{
			$form->add( new Text( 'location', FALSE, Request::i()->location, array( 'placeholder' => Member::loggedIn()->language()->addToStack( 'events_search_search_location') ) ) );
		}
		$form->add( new DateRange( 'date', array( 'start' => NULL, 'end' => NULL ), FALSE, array() ) );
		$form->add( new Select( 'show',  'all', FALSE, array( 'options' => array('all' => Member::loggedIn()->language()->addToStack( 'all_events' ), 'online' => Member::loggedIn()->language()->addToStack( 'online_events' ), 'physical' => Member::loggedIn()->language()->addToStack( 'physical_events' ) ) ) ) );

		return $form;
	}

	/**
	 * Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		Output::i()->bypassCsrfKeyCheck = TRUE;
		$results = array();

		/* Build the search form */
		$form = $this->getForm();

		$select = 'calendar_events.*, core_clubs.name';
		$sort = 'event_start_date asc';
		$location = NULL;
		$having = [];
		$where = [];
		$where[] = ['event_approved=?', 1];
		$searchNearLocation = FALSE; // Should results be limited to near the provided location only? If not, we'll just use location to allow sorting.

		if( $values = $form->values() )
		{
			if( isset( Request::i()->searchNearLocation ) && Request::i()->searchNearLocation )
			{
				$searchNearLocation = TRUE;
			}

			if( GeoLocation::enabled() )
			{
				if( Request::i()->location )
				{
					/* Is it a location? */
					$locations = static::geocodeLocation( Request::i()->location, FALSE );
					if( count( $locations ) )
					{
						if( $locations[0]['value'] )
						{
							$having[] = "( event_title LIKE CONCAT( '%', '" . $locations[0]['value'] . "', '%' ) OR name LIKE CONCAT( '%', '" . $locations[0]['value'] . "', '%' ) )";
						}

						$location = array( 'lat' => $locations[0]['lat'], 'lon' => $locations[0]['long'] );
						$searchNearLocation = TRUE;
					}
					else
					{
						$having[] = "( event_title LIKE CONCAT( '%', '" . Request::i()->location . "', '%' ) OR name LIKE CONCAT( '%', '" . Request::i()->location . "', '%' ) )";
					}
				}
				else if ( isset( Request::i()->lat ) and isset( Request::i()->lon ) and Request::i()->lat !== 0 and Request::i()->lon !== 0 )
				{
					$location = array( 'lat' => Request::i()->lat, 'lon' => Request::i()->lon );
					$searchNearLocation = TRUE;
				}
				elseif( $searchNearLocation )
				{
					/* Do an IP lookup */
					try
					{
						$geo = GeoLocation::getRequesterLocation();
						$location = array( 'lat' => $geo->lat, 'lon' => $geo->long );
					}
					catch ( Exception $e )
					{
						$location = array( 'lat' => Settings::i()->map_center_lat, 'lon' => Settings::i()->map_center_lon );
					}
				}

				if ( is_array( $location ) and isset( $location['lat'] ) and isset( $location['lon'] ) and is_numeric( $location['lat'] ) and is_numeric( $location['lon'] )  )
				{
					$select = $select . ', ( 3959 * acos( cos( radians(' . $location['lat'] . ') ) * cos( radians( event_latitude ) ) * cos( radians( event_longitude ) - radians(' . $location['lon'] . ') ) + sin( radians(' . $location['lat'] . ') ) * sin(radians(event_latitude)) ) ) AS distance';

					if( $searchNearLocation )
					{
						$having[] = 'distance < 500';
					}

					if( isset( Request::i()->sortBy ) && Request::i()->sortBy == 'nearest' )
					{
						$sort = 'distance asc';
					}
				}
			}

			/* Filter? */
			if( isset( Request::i()->show ) && Request::i()->show !== 'all' )
			{
				if( Request::i()->show == 'online' )
				{
					$where[] = array( '( event_online = 1 )' );
				}
				else {
					$where[] = array( '( ( event_end_date IS NULL OR TIMESTAMPDIFF( DAY, event_start_date, event_end_date ) < ? ) AND event_online = 0)', 30 );
				}
			}

			$today = new Date;
			$member = Member::loggedIn();

			$startDate = Date::dateTimeToCalendarDate( $values['date']['start'] ?: $today );
			$endDate = $values['date']['end'] ? Date::dateTimeToCalendarDate( $values['date']['end'] ) : NULL;

			/* Get timezone adjusted versions of start/end time */
			$startDateTimezone	= Date::parseTime( $startDate->mysqlDatetime(), TRUE );
			$endDateTimezone	= ( $endDate !== NULL ) ? Date::parseTime( $endDate->mysqlDatetime() ) : NULL;

			if ( $member->timezone )
			{
				$startDateTimezone->setTimezone( new DateTimeZone( 'UTC' ) );

				if( $endDateTimezone !== NULL )
				{
					$endDateTimezone->setTimezone( new DateTimeZone( 'UTC' ) );
				}
			}

			if( $endDate !== NULL AND $startDate == $endDate )
			{
				$where[]	= array(
					'( 
						( event_end_date IS NULL AND DATE( event_start_date ) = ? AND event_all_day=1 )
						OR
						( event_end_date IS NOT NULL AND DATE( event_start_date ) <= ? AND DATE( event_end_date ) >= ? AND event_all_day=1 )
						OR
						( event_end_date IS NULL AND event_start_date >= ? AND event_start_date <= ? AND event_all_day=0 )
						OR
						( event_end_date IS NOT NULL AND event_start_date <= ? AND event_end_date >= ? AND event_all_day=0 )
					)',
					$startDate->mysqlDatetime( FALSE ),
					$endDate->mysqlDatetime( FALSE ),
					$startDate->mysqlDatetime( FALSE ),
					$startDateTimezone->mysqlDatetime(),
					$startDateTimezone->adjust('+1 day')->mysqlDatetime(),
					$endDateTimezone->adjust('+1 day')->mysqlDatetime(),
					$startDateTimezone->mysqlDatetime()
				);
			}
			elseif( $endDate !== NULL )
			{
				$where[]	= array(
					'( 
						( event_end_date IS NULL AND DATE( event_start_date ) >= ? AND DATE( event_start_date ) <= ? AND event_all_day=1 )
						OR
						( event_end_date IS NOT NULL AND DATE( event_start_date ) <= ? AND DATE( event_end_date ) >= ? AND event_all_day=1 )
						OR
						( event_end_date IS NULL AND event_start_date >= ? AND event_start_date <= ? AND event_all_day=0 )
						OR
						( event_end_date IS NOT NULL AND event_start_date <= ? AND event_end_date >= ? AND event_all_day=0 )
					)',
					$startDate->mysqlDatetime( FALSE ),
					$endDate->mysqlDatetime( FALSE ),
					$endDate->mysqlDatetime( FALSE ),
					$startDate->mysqlDatetime( FALSE ),
					$startDateTimezone->mysqlDatetime(),
					$endDateTimezone->mysqlDatetime(),
					$endDateTimezone->mysqlDatetime(),
					$startDateTimezone->mysqlDatetime()
				);
			}
			else
			{
				$where[]	= array(
					"( 
						( DATE( event_start_date ) >= ? AND event_all_day=1 )
						OR
						( event_start_date >= ? AND event_all_day=0 )
						OR 
						( event_end_date IS NOT NULL AND DATE( event_start_date ) <= ? AND DATE( event_end_date ) >= ? AND event_all_day=1 ) 
						OR
						( event_end_date IS NOT NULL AND event_start_date <= ? AND event_end_date >= ? AND event_all_day=0 ) 
					)",
					$startDate->mysqlDatetime( FALSE ),
					$startDateTimezone->mysqlDatetime(),
					$startDate->mysqlDatetime( FALSE ),
					$startDate->mysqlDatetime( FALSE ),
					$startDateTimezone->adjust('+1 day')->mysqlDatetime(),
					$startDateTimezone->mysqlDatetime()
				);
			}

			$offset = isset( Request::i()->offset ) ? intval( Request::i()->offset ) : 0;
			$limit = isset( Request::i()->limit ) ? min( array( intval( Request::i()->limit ), 50 ) ) : 20;
			$query = Db::i()->select( $select, 'calendar_events', $where, $sort, array( $offset, $limit ), NULL, implode( " OR ", $having ) );
			$query->join( 'calendar_calendars', "event_calendar_id = cal_id" );
			$query->join( 'core_clubs', "cal_club_id = id" );

			$results = new ActiveRecordIterator( $query, '\IPS\calendar\Event' );
		}

		$mapMarkers = array();
		foreach ( $results as $event )
		{
			if( $event->latitude and $event->longitude )
			{
				$mapMarkers[ $event->id ] = array( 'lat' => (float) $event->latitude, 'long' => (float) $event->longitude	, 'title' => $event->title );
			}
		}

		if( Request::i()->isAjax() )
		{
			$countQuery = Db::i()->select( $select, 'calendar_events', $where, NULL, array(), NULL, is_array( $having ) ? implode( " OR ", $having ) : NULL );
			$countQuery->join( 'calendar_calendars', "event_calendar_id = cal_id" );
			$countQuery->join( 'core_clubs', "cal_club_id = id" );

			$output = Theme::i()->getTemplate( 'events' )->searchResults( $results );
			Member::loggedIn()->language()->parseOutputForDisplay( $output );

			$toReturn = array(
				'totalCount' => $countQuery->count(),
				'count' => count( $results ),
				'content' => $output,
				'markers' => $mapMarkers,
				'blurb' => '', // @todo
				'online'	=> ( Request::i()->show == 'online' )
			);

			Output::i()->sendOutput( json_encode( $toReturn ), 200, 'application/json' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack( '__app_calendar' );
		Output::i()->output	= Theme::i()->getTemplate( 'overview' )->search( $form, $results, json_encode( $mapMarkers ) );
	}

	/**
	 * Geocode Location
	 *
	 * @param 	string|null 	$input 		Location search term
	 * @param 	bool 	$asJson 	Return type
	 * @return	array
	 */
	public static function geocodeLocation( ?string $input = NULL, bool $asJson = TRUE ) : array
	{
		$items = array();

		try
		{
			if( $geolocation = GeoLocation::geocodeLocation( $input ) )
			{
				$items[] = array(
					'value' => $geolocation->placeName,
					'html' => $geolocation->placeName,
					'lat' => $geolocation->lat,
					'long' => $geolocation->long
				);
			}
		}
		catch( BadFunctionCallException $e ){}

		if( $asJson )
		{
			Output::i()->json( $items );
		}
		else
		{
			return $items;
		}
	}
}