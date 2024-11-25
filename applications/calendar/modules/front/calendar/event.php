<?php
/**
 * @brief		View Event Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		14 Jan 2014
 */

namespace IPS\calendar\modules\front\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DateTimeInterface;
use Exception;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\calendar\Calendar;
use IPS\calendar\Date;
use IPS\calendar\Event as EventClass;
use IPS\calendar\Icalendar\ICSParser;
use IPS\Content\Controller;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\GeoLocation;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View Event Controller
 */
class event extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\calendar\Event';

	/**
	 * @var EventClass|null
	 */
	protected ?EventClass $event = null;

	/**
	 * Init
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Calendar::addCss();

		try
		{
			$this->event = EventClass::load( Request::i()->id );
			
			if ( !$this->event->canView( Member::loggedIn() ) )
			{
				if ( $this->event->container()->can('view') AND !$this->event->container()->can('read') )
				{
					if ( Member::loggedIn()->member_id )
					{
						Output::i()->error( 'no_module_permission', '2L179/8', 404, '' );
					}
					else
					{
						Output::i()->error( 'no_module_permission_guest', '2L179/9', 404, '' );
					}
				}

				Output::i()->error( 'node_error', '2L179/1', 403, '' );
			}
			
			if ( $this->event->cover_photo )
			{
				Output::i()->metaTags['og:image'] = File::get( 'calendar_Events', $this->event->cover_photo )->url;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2L179/2', 404, '' );
		}
		
		$this->event->container()->clubCheckRules();

		/* We want to present the same breadcrumb structure as the rest of the calendar */
		Output::i()->breadcrumb['module'] = array( Url::internal( "app=calendar&module=calendar&controller=view", 'front', 'calendar' ), Member::loggedIn()->language()->addToStack('module__calendar_calendar') );

		parent::execute();
	}
	
	/**
	 * View Event
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		/* Init */
		parent::manage();

		/* Fetch RSVP data and pass to template */
		try
		{
			$attendees	= $this->event->attendees();
		}
		catch( BadMethodCallException $e )
		{
			$attendees	= array( 0 => array(), 1 => array(), 2 => array() );
		}

		/* Sort out comments and reviews */
		$tabs = $this->event->commentReviewTabs();
		$_tabs = array_keys( $tabs );
		$tab = isset( Request::i()->tab ) ? Request::i()->tab : array_shift( $_tabs );
		$activeTabContents = $this->event->commentReviews( $tab );
		
		if ( count( $tabs ) > 1 )
		{
			$commentsAndReviews = count( $tabs ) ? Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $tab, $activeTabContents, $this->event->url(), 'tab', FALSE, FALSE ) : NULL;
		}
		else
		{
			$commentsAndReviews = $activeTabContents;
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->output = $activeTabContents;
			return null;
		}

		/* Online User Location */
		Session::i()->setLocation( $this->event->url(), $this->event->onlineListPermissions(), 'loc_calendar_viewing_event', array( $this->event->title => FALSE ) );

		/* Address */
		$address = NULL;
		$location = NULL;
		$addressName = NULL;
		if ( Settings::i()->calendar_venues_enabled and $this->event->venue() )
		{
			$location = GeoLocation::buildFromjson( $this->event->venue()->address );
			$address = $location->toString();
			$addressName = $this->event->venue()->_title;
		}
		else if ( $this->event->location )
		{
			$location = GeoLocation::buildFromjson( $this->event->location );
			$address = $location->toString();
		}

		/* Add JSON-LD */
		$format = $this->event->all_day ? "Y-m-d" : DateTimeInterface::ATOM;
		Output::i()->jsonLd['event']	= array(
			'@context'		=> "https://schema.org",
			'@type'			=> "Event",
			'url'			=> (string) $this->event->url(),
			'name'			=> $this->event->mapped('title'),
			'description'	=> $this->event->truncated( TRUE, NULL ) ?? "",
			'eventStatus'	=> "EventScheduled",
			'organizer'		=> array(
				'@type'		=> 'Person',
				'name'		=> $this->event->author()->name,
				'image'		=> $this->event->author()->get_photo( TRUE, TRUE )
			),
			'startDate'		=> $this->event->nextOccurrence( Date::getDate(), 'startDate' ) ?
				$this->event->nextOccurrence( Date::getDate(), 'startDate' )->format( $format ) :
				$this->event->lastOccurrence( 'startDate' )->format( $format )
		);

		if( $this->event->author()->member_id )
		{
			Output::i()->jsonLd['event']['organizer']['url'] = (string) $this->event->author()->url();
		}

		if( $image = $this->event->coverPhotoFile() )
		{
			Output::i()->jsonLd['event']['image'] = (string) $image->url;
		}

		if( $this->event->_end_date )
		{
			Output::i()->jsonLd['event']['endDate'] = $this->event->nextOccurrence( $this->event->nextOccurrence( Date::getDate(), 'startDate' ) ?: Date::getDate(), 'endDate' ) ?
				$this->event->nextOccurrence( $this->event->nextOccurrence( Date::getDate(), 'startDate' ) ?: Date::getDate(), 'endDate' )->format( $format ) :
				$this->event->lastOccurrence( 'endDate' )->format( $format );
		}

		if( $this->event->container()->allow_reviews AND $this->event->reviews AND $this->event->averageReviewRating() )
		{
			Output::i()->jsonLd['event']['aggregateRating'] = array(
				'@type'			=> 'AggregateRating',
				'reviewCount'	=> $this->event->reviews,
				'ratingValue'	=> $this->event->averageReviewRating(),
				'bestRating'	=> Settings::i()->reviews_rating_out_of,
			);
		}

		if( $this->event->rsvp )
		{
			Output::i()->jsonLd['event']['eventAttendanceMode'] = 'https://schema.org/MixedEventAttendanceMode';

			if( count( $attendees[1] ) )
			{
				Output::i()->jsonLd['event']['attendee'] = array();

				foreach( $attendees[1] as $attendee )
				{
					Output::i()->jsonLd['event']['attendee'][] = array(
						'@type'		=> 'Person',
						'name'		=> $attendee->name
					);
				}
			}
		}

		/* If we have a physical location, use that. */
		if( $location )
		{
			Output::i()->jsonLd['event']['location'] = array(
				'@type'		=> 'Place',
				'address'	=> array(
					'@type'				=> 'PostalAddress',
					'streetAddress'		=> implode( ', ', $location->addressLines ),
					'addressLocality'	=> $location->city,
					'addressRegion'		=> $location->region,
					'postalCode'		=> $location->postalCode,
					'addressCountry'	=> $location->country,
				)
			);
			if( $addressName )
			{
				Output::i()->jsonLd['event']['location']['name'] = $addressName;
			}

			Output::i()->jsonLd['event']['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
		}
		/* If not, default to events URL */
		else
		{
			Output::i()->jsonLd['event']['location'] = array(
				'@type'		=> 'Place',
				'name'		=> Settings::i()->board_name,
				'address'	=> Output::i()->jsonLd['event']['url'],
				'url'		=> Output::i()->jsonLd['event']['url']
			);
		}

		/* Finally, if event is online and there is a URL, use that */
		if ( $this->event->online AND $this->event->url )
		{
			Output::i()->jsonLd['event']['location'] = array(
				'@type'		=> 'VirtualLocation',
				'url'		=> $this->event->url
			);

			Output::i()->jsonLd['event']['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
		}

		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'view' )->view( $this->event, $commentsAndReviews, $attendees, $address, $this->event->getReminder() );
		return null;
	}

	/**
	 * Show a small version of the calendar as a "hovercard"
	 *
	 * @return	void
	 */
	protected function hovercard() : void
	{
		/* Figure out our date object */
		$date = NULL;

		if( Request::i()->sd )
		{
			$dateBits	= explode( '-', Request::i()->sd );

			if( count( $dateBits ) === 3 )
			{
				$date	= Date::getDate( $dateBits[0], $dateBits[1], $dateBits[2] );
			}
		}

		if( $date === NULL )
		{
			$date	= Date::getDate();
		}

		Output::i()->output = Theme::i()->getTemplate( 'view' )->eventBlock( $this->event, $date );
	}

	/**
	 * Download event as ICS
	 *
	 * @return	void
	 */
	protected function download() : void
	{

		$feed	= new ICSParser;
		$feed->addEvent( $this->event );

		$ics	= $feed->buildICalendarFeed( $this->event->container() );

		Output::i()->sendHeader( "Content-type: text/calendar; charset=UTF-8" );
		Output::i()->sendHeader( 'Content-Disposition: inline; filename=calendarEvents.ics' );

		Member::loggedIn()->language()->parseOutputForDisplay( $ics );
		print $ics;
		exit;
	}

	/**
	 * Download RSVP attendee list
	 *
	 * @return	void
	 */
	protected function downloadRsvp() : void
	{
		$output	= Theme::i()->getTemplate( 'view' )->attendees( $this->event );
		Member::loggedIn()->language()->parseOutputForDisplay( $output );
		Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ) );
	}

	/**
	 * RSVP for event
	 *
	 * @return	void
	 */
	protected function rsvp() : void
	{
		if( !$this->event->can('rsvp') )
		{
			Output::i()->error( 'rsvp_error', '2L179/3', 403, '' );
		}

		if( $this->event->hasPassed() AND Settings::i()->calendar_block_past_changes )
		{
			Output::i()->error( 'no_rsvp_past_event', '2L179/6', 403, '' );
		}

		Session::i()->csrfCheck();

		/* We delete either way at this point, because even if we select a different action we have to remove any existing RSVP preference */
		Db::i()->delete( 'calendar_event_rsvp', array( 'rsvp_event_id=? AND rsvp_member_id=?', $this->event->id, (int) Member::loggedIn()->member_id ) );

		if( Request::i()->action == 'leave' )
		{
			$message	= 'rsvp_not_going';
		}
		else
		{
			/* Figure out the action */
			switch( Request::i()->action )
			{
				case 'yes':
					$_go	= EventClass::RSVP_YES;
				break;

				case 'maybe':
					$_go	= EventClass::RSVP_MAYBE;
				break;

				case 'no':
				default:
					Request::i()->action	= 'no';
					$_go	= EventClass::RSVP_NO;
				break;
			}

			/* If there is a limit applied there are more rules */
			if( $this->event->rsvp_limit > 0 )
			{
				/* We do not accept "maybe" in this case */
				if( $_go === EventClass::RSVP_MAYBE )
				{
					Output::i()->error( 'rsvp_limit_nomaybe', '3L179/4', 403, '' );
				}

				/* And we have to actually check the limit */
				if( $_go == EventClass::RSVP_YES and count( $this->event->attendees( EventClass::RSVP_YES ) ) >= $this->event->rsvp_limit )
				{
					Output::i()->error( 'rsvp_limit_reached', '3L179/5', 403, '' );
				}
			}

			Db::i()->insert( 'calendar_event_rsvp', array(
				'rsvp_event_id'		=> $this->event->id,
				'rsvp_member_id'	=> (int) Member::loggedIn()->member_id,
				'rsvp_date'			=> time(),
				'rsvp_response'		=> (int) $_go
			) );

			$webhookData = [
				'event' => $this->event->apiOutput(),
				'action' => Request::i()->action,
				'attendee' => Member::loggedIn()->apiOutput(),
			];

			$message	= 'rsvp_selection_' . Request::i()->action;

			Webhook::fire( 'calendarEvent_rsvp', $webhookData );

			\IPS\Events\Event::fire( 'onEventRsvp', Member::loggedIn(), array( $this->event, $_go ) );

			Member::loggedIn()->achievementAction( 'calendar', 'Rsvp', $this->event );
		}

		Output::i()->redirect( $this->event->url(), $message );
	}

	/**
	 * Edit Item
	 *
	 * @return	void
	 */
	protected function edit() : void
	{
		if ( Application::appIsEnabled('cloud') and $this->event->livetopic_id )
		{
			/* Allow live topic edit form to handle this */
			try
			{
				/* Make sure it's a valid topic */
				$liveTopic = \IPS\cloud\LiveTopic::load( $this->event->livetopic_id );
				Output::i()->redirect( Url::internal( 'app=core&module=modcp&controller=modcp&tab=livetopics&action=create&fromEvent=1&id=' . $liveTopic->id, 'front', 'modcp_livetopics' ) );
			}
			catch( Exception )	{ }
		}

		/* Are we blocking changes to past events? */
		if( $this->event->hasPassed() AND Settings::i()->calendar_block_past_changes )
		{
			if ( !EventClass::modPermission( 'edit', Member::loggedIn(), $this->event->containerWrapper() ) )
			{
				Output::i()->error( 'no_edit_past_event', '2L179/7', 403, '' );
			}
		}

		/* Output resources and go */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_submit.js', 'calendar', 'front' ) );

		parent::edit();
	}

	/**
	 * Return the form for editing. Abstracted so controllers can define a custom template if desired.
	 *
	 * @param Form $form	The form
	 * @return	string
	 */
	protected function getEditForm( Form $form ): string
	{
		return $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'calendar' ), 'submitForm' ) );
	}

	/**
	 * Set a reminder
	 *
	 * @return	void
	 */
	protected function setReminder() : void
	{
		Session::i()->csrfCheck();

		/* Members only */
		if( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'node_error', '3L369/1', 403, '' );
		}

		/* Existing reminder? */
		$existing = $this->event->getReminder();

		/* Build the form */

		/* How far in the future is this event so we can set realistic max reminders */
		$diff = $this->event->_start_date->diff( DateTime::create() );
		$max = $diff->days;

		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--set-reminder';
		$form->add( new Number( 'event_remind_me', isset( $existing ) ? $existing['reminder_days_before'] : ( ( $max < 3 ) ? $max : 3 ), TRUE, array( 'min' => 1, 'max' => (int) $max ), NULL, NULL, Member::loggedIn()->language()->addToStack('event_remind_days_before'), 'event_remind_me' ) );

		if ( $existing )
		{
			$form->addButton( 'event_dont_remind', 'link', Url::internal( "app=calendar&module=calendar&controller=event&do=removeReminder&action=remove&id={$this->event->id}")->csrf(), 'ipsButton ipsButton--negative', array('data-action' => 'removereminder') );
		}

		if( $values = $form->values() )
		{
			/* Delete existing */
			Db::i()->delete( 'calendar_event_reminders', array( 'reminder_event_id=? AND reminder_member_id=?', $this->event->id, (int) Member::loggedIn()->member_id ) );

			Db::i()->insert( 'calendar_event_reminders', array(
				'reminder_event_id'		=> $this->event->id,
				'reminder_member_id'	=> (int) Member::loggedIn()->member_id,
				'reminder_date'			=> $this->event->_start_date->sub( new DateInterval( 'P' . (int) $values['event_remind_me'] . 'D' ) )->getTimestamp(),
				'reminder_days_before'	=> (int) $values['event_remind_me'],
			) );

			Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'eventreminders' ) );

			if( Request::i()->isAjax() )
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'view', 'calendar', 'front' )->reminderButton( $this->event, $this->event->getReminder() ) );
			}
			else
			{
				Output::i()->redirect( $this->event->url(), 'event_reminder_added' );
			}
		}

		/* Display */
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'event_set_reminder' );
		$output = $form->customTemplate( array( Theme::i()->getTemplate( 'view', 'calendar' ), 'reminderForm' ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->output = $output;
		}
	}

	/**
	 * Remve Reminder
	 *
	 * @return	void
	 */
	protected function removeReminder() : void
	{
		Session::i()->csrfCheck();

		if ( Request::i()->action == 'remove' )
		{
			/* Delete existing */
			Db::i()->delete( 'calendar_event_reminders', array( 'reminder_event_id=? AND reminder_member_id=?', $this->event->id, (int) Member::loggedIn()->member_id ) );

			$message = 'event_reminder_removed';
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'ok' );
		}
		else
		{
			Output::i()->redirect( $this->event->url(), $message ?? '' );
		}
	}

	/**
	 * Reminder button
	 *
	 * @return	void
	 */
	protected function reminderButton() : void
	{
		Output::i()->sendOutput( Theme::i()->getTemplate( 'view', 'calendar', 'front' )->reminderButton( $this->event, $this->event->getReminder() ) );
	}
}