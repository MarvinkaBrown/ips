<?php
/**
 * @brief		Venue
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		27 Feb 2017
 */

namespace IPS\calendar\modules\front\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\calendar\Calendar;
use IPS\calendar\Date;
use IPS\calendar\Event;
use IPS\calendar\Venue as VenueClass;
use IPS\Dispatcher\Controller;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * venue
 */
class venue extends Controller
{
	/**
	 * @brief	Venue we are viewing
	 */
	protected ?VenueClass $venue	= NULL;

	/**
	 * Init
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		try
		{
			$this->venue = VenueClass::loadAndCheckPerms( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2L354/1', 404, '' );
		}

		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Load the css for Calendar badges */
		Calendar::addCss();

		$today = Date::getDate();

		/* Get the month data */
		$day		= NULL;

		if( ( !Request::i()->y OR Request::i()->y == $today->year ) AND ( !Request::i()->m OR Request::i()->m == $today->mon ) )
		{
			$day	= $today->mday;
		}

		try
		{
			$date		= Date::getDate( Request::i()->y ?: NULL, Request::i()->m ?: NULL, $day );
		}
		catch( InvalidArgumentException $e )
		{
			Output::i()->error( 'error_bad_date', '2L354/2', 403, '' );
		}

		$upcoming = Event::retrieveEvents(
			Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ),
			Date::getDate( $date->lastDayOfMonth('year'), $date->lastDayOfMonth('mon'), $date->lastDayOfMonth('mday'), 23, 59, 59 ),
			NULL,
			NULL,
			FALSE,
			NULL,
			$this->venue
		);

		$upcomingOutput = Theme::i()->getTemplate( 'venue', 'calendar', 'front' )->upcomingStream( $date, $upcoming, $this->venue );

		/* Address */
		$address = NULL;
		if ( $this->venue->address )
		{
			$address = GeoLocation::buildFromjson( $this->venue->address )->toString();
		}

		/* Display */
		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $upcomingOutput );
		}
		else
		{
			/* Add JSON-LD */
			Output::i()->jsonLd['eventVenue']	= array(
				'@context'		=> "https://schema.org",
				'@type'			=> "EventVenue",
				'url'			=> (string) $this->venue->url(),
				'name'			=> $this->venue->_title
			);

			Output::i()->title = $this->venue->_title;
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_venue.js', 'calendar', 'front' ) );

			/* We want to present the same breadcrumb structure as the rest of the calendar */
			Output::i()->breadcrumb['module'] = array( Url::internal( "app=calendar&module=calendar&controller=view", 'front', 'calendar' ), Member::loggedIn()->language()->addToStack('module__calendar_calendar') );

			Output::i()->output = Theme::i()->getTemplate( 'venue', 'calendar', 'front' )->view( $this->venue, $upcomingOutput, NULL, $address );
		}
	}
}