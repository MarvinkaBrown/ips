<?php
/**
 * @brief		Submit Event Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		8 Jan 2014
 */

namespace IPS\calendar\modules\front\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\calendar\Calendar;
use IPS\calendar\Event;
use IPS\core\FrontNavigation;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\File\Exception;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function is_object;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Submit Event Controller
 */
class submit extends Controller
{
	/**
	 * Choose Calendar
	 *
	 * @return    void
	 */
	protected function manage() : void
	{
		$form = new Form( 'select_calendar', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--submit-event ipsForm--noLabels';
		$form->add( new Node( 'calendar', NULL, TRUE, array(
			'url'					=> Url::internal( 'app=calendar&module=calendar&controller=submit', 'front', 'calendar_submit' ),
			'class'					=> 'IPS\calendar\Calendar',
			'permissionCheck'		=> 'add',
			'forceOwner'			=> Member::loggedIn(),
			'clubs'					=> Settings::i()->club_nodes_in_apps
		) ) );

		/* Are we creating an event for a specific day? If yes, pass the values to the form */
		if( Request::i()->y AND Request::i()->m AND Request::i()->d )
		{
			$form->hiddenValues['y'] = Request::i()->y;
			$form->hiddenValues['m'] = Request::i()->m;
			$form->hiddenValues['d'] = Request::i()->d;
		}

		/* Are we coming from a venue? */
		if( Settings::i()->calendar_venues_enabled and Request::i()->venue )
		{
			$form->hiddenValues['venue'] = Request::i()->venue;
		}

		if ( $values = $form->values() )
		{
			$url = Url::internal( 'app=calendar&module=calendar&controller=submit&do=submit', 'front', 'calendar_submit' )->setQueryString( 'id', $values['calendar']->_id );

			if( isset( $values['y'], $values['m'], $values['d'] ) )
			{
				$url = $url->setQueryString( 'd', $values['d'] )->setQueryString( 'm', $values['m'] )->setQueryString( 'y', $values['y'] );
			}

			if( Settings::i()->calendar_venues_enabled and isset( $values['venue'] ) )
			{
				$url = $url->setQueryString( 'venue', $values['venue'] );
			}

			Output::i()->redirect( $url );
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'submit_event' );
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'add_cal_event_header' ) );
		Output::i()->output = Theme::i()->getTemplate( 'submit' )->calendarSelector( $form );
	}

	/**
	 * Submit Event
	 *
	 * @return	void
	 */
	protected function submit() : void
	{
		$calendar = NULL;
	
		try
		{
			$calendar = Calendar::loadAndCheckPerms( Request::i()->id );
			
			if ( $club = $calendar->club() )
			{
				FrontNavigation::$clubTabActive = TRUE;
				Output::i()->breadcrumb = array();
				Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
				Output::i()->breadcrumb[] = array( $club->url(), $club->name );
				Output::i()->breadcrumb[] = array( $calendar->url(), $calendar->_title );
				
				if ( Settings::i()->clubs_header == 'sidebar' )
				{
					Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $calendar, 'sidebar' );
				}
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendar&controller=submit', 'front', 'calendar_submit' ) );
		}

		$form = Event::create( $calendar );

		$extraOutput = '';
		$guestPostBeforeRegister = ( !Member::loggedIn()->member_id ) ? ( $calendar and !$calendar->can( 'add', Member::loggedIn(), FALSE ) ) : FALSE;
		$modQueued = Event::moderateNewItems( Member::loggedIn(), $calendar, $guestPostBeforeRegister );
		if ( $guestPostBeforeRegister or $modQueued )
		{
			$extraOutput .= Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( $guestPostBeforeRegister, $modQueued, TRUE );
		}			

		/* Display */
		Output::i()->output	.= Theme::i()->getTemplate( 'submit' )->submitPage( $extraOutput . $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'calendar' ), 'submitForm' ) ), Member::loggedIn()->language()->addToStack('add_cal_event_header'), $calendar );
		Output::i()->title		= Member::loggedIn()->language()->addToStack( 'submit_event' );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'add_cal_event_header' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_submit.js', 'calendar', 'front' ) );
	}

	/**
	 * Copy Event
	 *
	 * @return	void
	 */
	protected function copy() : void
	{
		try
		{
			$existing = Event::loadAndCheckPerms( Request::i()->event_id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendar&controller=submit', 'front', 'calendar_submit' ) );
		}

		if( !$existing->canCopyEvent())
		{
			Output::i()->error( 'no_module_permission', '2L179/9', 403, '' );
		}

		/* Are we the author of the existing event? */
		if( $existing->author()->member_id !== Member::loggedIn()->member_id )
		{
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendar&controller=submit', 'front', 'calendar_submit' ) );
		}
		
		$form = new Form( 'form', Member::loggedIn()->language()->checkKeyExists( Event::$formLangPrefix . '_save' ) ? Event::$formLangPrefix . '_save' : 'save' );
		$form->class = 'ipsForm--vertical ipsForm--copy-event';
		$formElements = Event::formElements( $existing, $existing->container(), TRUE );
		foreach ( $formElements as $key => $object )
		{
			if ( is_object( $object ) )
			{
				$form->add( $object );
			}
			else
			{
				$form->addMessage( $object, NULL, FALSE, $key );
			}
		}

		if ( $values = $form->values() )
		{
			/* Set the container */
			if ( !isset( $values[ 'event_container' ] ) )
			{
				$values[ 'event_container' ] = $existing->container();
			}

			/* Disable read/write separation */
			Db::i()->readWriteSeparation = FALSE;

			try
			{
				$obj = Event::createFromForm( $values );

				/* Set cover photo offset from original if we're using the same photo */
				if( $existing->cover_photo and $existing->cover_photo == $obj->cover_photo )
				{
					try
					{
						$obj->cover_photo = File::get( 'calendar_Events', $existing->cover_photo )->duplicate();
						$obj->cover_offset = $existing->cover_offset;
						$obj->save();
					}
					catch ( Exception $e ){}
				}

				if ( !Member::loggedIn()->member_id and $obj->hidden() )
				{
					Output::i()->redirect( $obj->container()->url(), 'mod_queue_message' );
				}
				else if ( $obj->hidden() == 1 )
				{
					Output::i()->redirect( $obj->url(), 'mod_queue_message' );
				}
				else
				{
					Output::i()->redirect( $obj->url() );
				}
			}
			catch ( DomainException $e )
			{
				$form->error = $e->getMessage();
			}
		}

		Output::i()->output	= Theme::i()->getTemplate( 'submit' )->submitPage( $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'calendar' ), 'submitForm' ) ), Member::loggedIn()->language()->addToStack('copy_cal_event_header', TRUE, array( 'sprintf' => $existing->title ) ) );

		if ( Event::moderateNewItems( Member::loggedIn() ) )
		{
			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->modQueueMessage( Member::loggedIn()->warnings( 5, NULL, 'mq' ), Member::loggedIn()->mod_posts ) . Output::i()->output;
		}

		/* Display */
		Output::i()->title	= Member::loggedIn()->language()->addToStack('copy_cal_event_header', TRUE, array( 'sprintf' => $existing->title ) );
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('copy_cal_event_header', TRUE, array( 'sprintf' => $existing->title ) ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_submit.js', 'calendar', 'front' ) );
	}

	/**
	 * Create a live topic right from the events page
	 */
	public function livetopic(): void
	{
		Bridge::i()->liveTopicCreateFormFromCalendar();
	}
}