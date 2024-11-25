<?php
/**
 * @brief		Calendar Application Class
 * @author		<a href=''>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		18 Dec 2013
 * @version
 * @todo support for google maps as well as mapbox
 * @todo Only request location info when search box is focused.
 */
 
namespace IPS\calendar;

use IPS\Application as SystemApplication;
use IPS\calendar\Icalendar\ICSParser;
use IPS\Content\Filter;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use function array_merge;
use function in_array;
use function mb_strlen;
use function mb_substr;

/**
 * Core Application Class
 */
class Application extends SystemApplication
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init() : void
	{
		/* Requesting iCal/RSS Subscription, but guests are required to login */
		if( Request::i()->module == 'calendar' and Request::i()->controller == 'view' and in_array( Request::i()->do, array( 'rss', 'download' ) ) )
		{
			/* Validate RSS/Download key */
			if( Request::i()->member )
			{
				$member = Member::load( Request::i()->member );
				if( !Login::compareHashes( $member->getUniqueMemberHash(), (string) Request::i()->key ) )
				{
					Output::i()->error( 'node_error', '2L217/1', 404, '' );
				}
			}

			/* Output */
			if( Request::i()->do == 'download' )
			{
				$this->download( Request::i()->member ? $member : NULL );
			}

			$this->rss( Request::i()->member ? $member : NULL );
		}

		/* Reset first day of week */
		if( Settings::i()->ipb_calendar_mon )
		{
			Output::i()->jsVars['date_first_day'] = 1;
		}
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'calendar';
	}

	/**
	 * Latest events RSS
	 *
	 * @param Member|null $member	Member to generate feed for
	 * @return	void
	 * @note	There is a hard limit of the most recent 500 events updated
	 */
	public function download( Member $member=NULL ) : void
	{
		$feed	= new ICSParser;
		$calendar = NULL;

		/* Are we viewing a specific calendar only? */
		if( Request::i()->id )
		{
			try
			{
				$calendar = Calendar::load( Request::i()->id );

				if ( !$calendar->can( 'view', $member ) )
				{
					throw new OutOfRangeException;
				}
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2L217/3', 404, '' );
			}
		}

		$where = array();

		if( $calendar !== NULL )
		{
			$where[] = array( 'event_calendar_id=?', $calendar->id );
		}

		foreach(Event::getItemsWithPermission( $where, 'event_lastupdated DESC', 500, 'read', Filter::FILTER_AUTOMATIC, 0, $member ) as $event )
		{
			$feed->addEvent( $event );
		}

		$ics = $feed->buildICalendarFeed( $calendar );

		Output::i()->sendOutput( $ics, 200, 'text/calendar', [ 'Content-Disposition' => Output::getContentDisposition( 'inline', "calendarEvents.ics" ) ], FALSE, FALSE, FALSE );
	}

	/**
	 * Latest events RSS
	 *
	 * @param Member|null $member	Member to generate feed for
	 * @return	void
	 */
	public function rss( Member $member=NULL ) : void
	{
		if( !Settings::i()->calendar_rss_feed )
		{
			Output::i()->error( 'event_rss_feed_off', '2L182/1', 404, 'event_rss_feed_off_admin' );
		}

		/* Load member */
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}

		$rssTitle = $member->language()->get('calendar_rss_title');
		$document = Rss::newDocument( Url::internal( 'app=calendar&module=calendar&controller=view', 'front', 'calendar' ), $rssTitle, $rssTitle );

		$_today	= Date::getDate();

		$endDate = NULL;

		if( Settings::i()->calendar_rss_feed_days > 0 )
		{
			$endDate = $_today->adjust( "+" . Settings::i()->calendar_rss_feed_days . " days" );
		}

		foreach (Event::retrieveEvents( $_today, $endDate, NULL, NULL, FALSE, $member ) as $event )
		{
			$next = ( (int) Settings::i()->calendar_rss_feed_order === 0 ) ? $event->nextOccurrence( $_today, 'startDate' ) : DateTime::ts( $event->saved );
			$content = $event->content;
			Output::i()->parseFileObjectUrls( $content );
			$document->addItem( $event->title, $event->url(), $content, $next, $event->id );
		}

		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Calendar' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Returns a list of all existing webhooks and their payload in this app.
	 *
	 * @return array
	 */
	public function getWebhooks() : array
	{
		return array_merge(  [
				'calendarEvent_rsvp' => [
					'event' => Event::class,
					'action' => "string with the state (attending/etc..)",
					'attendee' => Member::class
				]
			],parent::getWebhooks());
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters() : void
	{
		$url = Request::i()->url();
		$baseUrl = parse_url( Settings::i()->base_url );

		/* We want to match /calendar/ if IC is not installed inside a directory, or if it is, the  /path/calendar/ exactly */
		$strToCheck = ( ! empty( trim( $baseUrl['path'], '/' ) ) ) ? '/' . trim( $baseUrl['path'], '/' ) . '/calendar/' : '/calendar/';


		// once we use php8, we can use str_starts_with, right now here's the hacky way with substr
		if( mb_substr($url->data[ Url::COMPONENT_PATH ], 0, mb_strlen($strToCheck)) === $strToCheck )
		{
			$newPath = str_replace( '/calendar/', '/events/', $url->data[ Url::COMPONENT_PATH ] );
			$url = $url->setPath( $newPath );
	
			Output::i()->redirect( $url );
		}
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'calendar.css', 'calendar', 'front' ) );
		}
	}
}