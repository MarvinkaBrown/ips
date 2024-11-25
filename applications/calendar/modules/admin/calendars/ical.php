<?php
/**
 * @brief		iCalendar feed management
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		19 Dec 2013
 */

namespace IPS\calendar\modules\admin\calendars;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\calendar\Calendar;
use IPS\calendar\Icalendar;
use IPS\calendar\Icalendar\ICSParser;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\Table\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;
use function file_get_contents;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_NODE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * iCalendar feed management
 */
class ical extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'calendar_feeds_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		/* Create the table */
		$table = new Db( 'calendar_import_feeds', Url::internal( 'app=calendar&module=calendars&controller=ical' ) );
		$table->langPrefix = 'ical_';

		/* Column stuff */
		$table->include = array( 'feed_title', 'feed_added', 'feed_lastupdated', 'feed_calendar_id' );
		$table->mainColumn = 'feed_title';

		/* Sort stuff */
		$table->sortBy = $table->sortBy ?: 'feed_title';
		$table->sortDirection = $table->sortDirection ?: 'asc';

		/* Search */
		$table->quickSearch = 'feed_title';
		$table->advancedSearch = array(
			'feed_title'		=> SEARCH_CONTAINS_TEXT,
			'feed_url'			=> SEARCH_CONTAINS_TEXT,
			'feed_calendar_id'	=> array( SEARCH_NODE, array(
				'class'				=> '\IPS\calendar\Calendar',
				'zeroVal'			=> 'any'
			) ),
			'feed_added'		=> SEARCH_DATE_RANGE,
			'feed_lastupdated'	=> SEARCH_DATE_RANGE,
			);

		/* Formatters */
		$table->parsers = array(
			'feed_added'			=> function( $val )
			{
				$date	= DateTime::ts( $val );

				return $date->localeDate() . ' ' . $date->localeTime( FALSE ) ;
			},
			'feed_lastupdated'	=> function( $val )
			{
				$date	= DateTime::ts( $val );

				return $date->localeDate() . ' ' . $date->localeTime( FALSE ) ;
			},
			'feed_calendar_id'	=> function( $val )
			{
				try
				{
					return Calendar::load( $val )->_title;
				}
				catch( OutOfRangeException $e )
				{
					return '';
				}
			}
		);

		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array( 'update' => array(
				'icon'		=> 'refresh',
				'title'		=> 'update_ical',
				'link'		=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=refresh&id=' . $row['feed_id'] )->csrf(),
				'hotkey'	=> 'r'
			)	);

			if ( Member::loggedIn()->hasAcpRestriction( 'calendar', 'calendars', 'calendar_feeds_edit' ) )
			{
				$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=edit&id=' ) . $row['feed_id'],
					'hotkey'	=> 'e'
				);
			}
						
			if ( Member::loggedIn()->hasAcpRestriction( 'calendar', 'calendars', 'calendar_feeds_delete' ) )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=delete&id=' . $row['feed_id'] )->csrf(),
					'data'		=> array( 'delete' => '' ),
				);
			}
			
			return $return;
		};

		/* Root buttons */
		if ( Member::loggedIn()->hasAcpRestriction( 'calendar', 'calendars', 'calendar_feeds_add' ) )
		{
			Output::i()->sidebar['actions']['add'] = array(
				'primary'	=> true,
				'icon'		=> 'plus',
				'title'		=> 'calendar_feeds_add',
				'link'		=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=add' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('calendar_feeds_add') )
			);

			Output::i()->sidebar['actions']['upload'] = array(
				'icon'		=> 'upload',
				'title'		=> 'calendar_feeds_upload',
				'link'		=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=upload' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('calendar_feeds_upload') )
			);
		}

		/* Display */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('ical_title');
		Output::i()->output	= (string) $table;
	}

	/**
	 * Add iCalendar feed to import
	 *
	 * @return	void
	 */
	public function add() : void
	{
		/* Check permissions */
		Dispatcher::i()->checkAcpPermission( 'calendar_feeds_add' );

		/* Page title */
		Output::i()->title		= Member::loggedIn()->language()->addToStack('calendar_feeds_add');

		/* Build the form */
		$form = new Form;
		$form->add( new Text( 'feed_title', NULL, TRUE ) );
		$form->add( new Form\Url( 'feed_url', NULL, TRUE, array( 'allowedProtocols' => array( 'http', 'https', 'webcal', 'webcals' ) ) ) );
		$form->add( new Node( 'feed_calendar_id', NULL, TRUE, array( 'class' => 'IPS\calendar\Calendar', 'url' => Url::internal( 'app=calendar&module=calendars&controller=ical&do=add', 'admin' ) ) ) );
		if( Settings::i()->calendar_venues_enabled )
		{
			$form->add( new Node( 'feed_venue_id', NULL, FALSE, array( 'class' => 'IPS\calendar\Venue', 'url' => Url::internal( 'app=calendar&module=calendars&controller=ical&do=add', 'admin' ) ) ) );
		}
		$form->add( new Form\Member( 'feed_member_id', NULL, TRUE, array() ) );
		$form->add( new YesNo( 'feed_allow_rsvp', NULL, FALSE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$values['feed_url'] = str_replace( '#', '%23', $values['feed_url'] ); // Google Calendar links include a # in the URL, which both cURL and sockets will ignore :\
			
			try
			{
				ICSParser::isValid( Url::external( str_replace( array( 'webcal://', 'webcals://' ), array( 'http://', 'https://' ), $values['feed_url'] ) )->request()->get() );
			}
			catch( Exception $e )
			{
				$form->error = ( $e instanceof UnexpectedValueException ) ? Member::loggedIn()->language()->addToStack('ical_error_' . $e->getMessage()) : $e->getMessage();

				Output::i()->output = Theme::i()->getTemplate('global', 'core')->block('calendar_feeds_add', $form, FALSE);
				return;
			}

			/* Insert the new record */
			$feed	= new Icalendar;
			$feed->title		= $values['feed_title'];
			$feed->url			= $values['feed_url'];
			$feed->calendar_id	= $values['feed_calendar_id']->_id;

			if( Settings::i()->calendar_venues_enabled )
			{
				$feed->venue_id		= $values['feed_venue_id']->_id;
			}

			$feed->member_id	= $values['feed_member_id']->member_id;
			$feed->allow_rsvp	= $values['feed_allow_rsvp'];
			$feed->save();

			/* Admin log */
			Session::i()->log( 'acplog__icalfeed_created', array( $feed->title => FALSE ) );

			/* Grab the feed events */
			$feed->refresh();

			/* Redirect */
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendars&controller=ical' ), 'saved' );
		}

		Output::i()->output	= Theme::i()->getTemplate( 'global', 'core' )->block( 'calendar_feeds_add', $form, FALSE );
	}

	/**
	 * Upload iCalendar file
	 *
	 * @return	void
	 */
	public function upload() : void
	{
		/* Check permissions */
		Dispatcher::i()->checkAcpPermission( 'calendar_feeds_add' );

		/* Build the form */
		$form = new Form;
		$form->add( new Upload( 'feed_file', NULL, TRUE, array( 'temporary' => TRUE ) ) );

		/* Init */
		$form->add( new Node( 'feed_calendar_id', NULL, TRUE, array(
			'url'					=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=upload' ),
			'class'					=> 'IPS\calendar\Calendar',
		) ) );
		if( Settings::i()->calendar_venues_enabled )
		{
			$form->add( new Node( 'feed_venue_id', NULL, FALSE, array(
				'url'					=> Url::internal( 'app=calendar&module=calendars&controller=ical&do=upload' ),
				'class'					=> 'IPS\calendar\Venue',
			) ) );
		}
		$form->add( new Form\Member( 'feed_member_id', NULL, TRUE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Import the events */
			try
			{
				$icsParser	= new ICSParser;

				$count	= $icsParser->parse( file_get_contents( $values['feed_file'] ), $values['feed_calendar_id'], $values['feed_member_id'], NULL, ( isset( $values['feed_venue_id'] ) AND $values['feed_venue_id'] ) ? $values['feed_venue_id']->_id : NULL );
			}
			catch( UnexpectedValueException $e )
			{
				Output::i()->error( $e->getMessage(), '1L169/4', 403, '' );
			}

			/* Admin log */
			Session::i()->log( 'acplog__ical_uploaded', array( $count['imported'] => FALSE, $count['skipped'] => FALSE ) );

			/* Redirect */
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendars&controller=ical' ), 'feed_uploaded' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('calendar_feeds_add');
		Output::i()->output	= Theme::i()->getTemplate( 'global', 'core' )->block( 'calendar_feeds_add', $form, FALSE );
	}

	/**
	 * Edit iCalendar feed
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		/* Check permissions */
		Dispatcher::i()->checkAcpPermission( 'calendar_feeds_edit' );

		/* Get existing feed */
		try
		{
			$feed	= Icalendar::load( Request::i()->id );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'feed_not_found', '2L169/2', 404, '' );
		}

		/* Build the form */
		$form = new Form;
		$form->add( new Text( 'feed_title', $feed->title, TRUE ) );
		$form->add( new Form\Url( 'feed_url', $feed->url, TRUE, array( 'allowedProtocols' => array( 'http', 'https', 'webcal', 'webcals' ) ) ) );

		$form->add( new Node( 'feed_calendar_id', Calendar::load( $feed->calendar_id ), TRUE, array( 'class' => 'IPS\calendar\Calendar', 'url' => Url::internal( 'app=calendar&module=calendars&controller=ical&do=edit&id=' . Request::i()->id, 'admin' ) ) ) );

		if( Settings::i()->calendar_venues_enabled )
		{
			$form->add( new Node( 'feed_venue_id', NULL, FALSE, array( 'class' => 'IPS\calendar\Venue', 'url' => Url::internal( 'app=calendar&module=calendars&controller=ical&do=edit', 'admin' ) ) ) );
		}

		$form->add( new Form\Member( 'feed_member_id', Member::load( $feed->member_id )->name, TRUE ) );
		$form->add( new YesNo( 'feed_allow_rsvp', $feed->allow_rsvp, FALSE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$values['feed_url'] = str_replace( '#', '%23', $values['feed_url'] ); // Google Calendar links include a # in the URL, which both cURL and sockets will ignore :\

			try
			{
				ICSParser::isValid( Url::external( str_replace( array( 'webcal://', 'webcals://' ), array( 'http://', 'https://' ), $values['feed_url'] ) )->request()->get() );
			}
			catch( Exception $e )
			{
				$form->error = ( $e instanceof UnexpectedValueException ) ? Member::loggedIn()->language()->addToStack('ical_error_' . $e->getMessage()) : $e->getMessage();

				Output::i()->output = Theme::i()->getTemplate('global', 'core')->block('calendar_feeds_add', $form, FALSE);
				return;
			}

			/* Insert the new record */
			$feed->title		= $values['feed_title'];
			$feed->url			= $values['feed_url'];
			$feed->calendar_id	= $values['feed_calendar_id']->_id;

			if( Settings::i()->calendar_venues_enabled )
			{
				$feed->venue_id		= $values['feed_venue_id']->_id;
			}

			$feed->member_id	= $values['feed_member_id']->member_id;
			$feed->allow_rsvp	= $values['feed_allow_rsvp'];
			$feed->save();

			/* Admin log */
			Session::i()->log( 'acplog__icalfeed_updated', array( $feed->title => FALSE ) );

			/* Grab the feed events */
			$feed->refresh();

			/* Redirect */
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendars&controller=ical' ), 'saved' );
		}

		Output::i()->title		= $feed->title;
		Output::i()->output	= Theme::i()->getTemplate( 'global', 'core' )->block( $feed->title, $form, FALSE );
	}

	/**
	 * Delete iCalendar feed
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		/* Check permissions */
		Dispatcher::i()->checkAcpPermission( 'calendar_feeds_delete' );
		Session::i()->csrfCheck();

		/* Build the form */
		$form = new Form( 'form', 'delete' );
		$form->add( new YesNo( 'keep_events', TRUE, FALSE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Delete the feed */
			try
			{
				$feed	= Icalendar::load( Request::i()->id );

				if( !$values['keep_events'] )
				{
					$feed->deleteFeedEvents();
				}

				$feed->delete();
			}
			catch( OutOfRangeException $e )
			{
				Output::i()->error( 'feed_not_found', '2L169/3', 404, '' );
			}

			/* Admin log */
			Session::i()->log( 'acplog__icalfeed_deleted', array( $feed->title => FALSE ) );

			/* Redirect */
			Output::i()->redirect( Url::internal( 'app=calendar&module=calendars&controller=ical' ), 'deleted' );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('delete');
		Output::i()->output	= Theme::i()->getTemplate( 'global', 'core' )->block( 'delete', $form, FALSE );
	}

	/**
	 * Refresh iCalendar feed
	 *
	 * @return	void
	 */
	public function refresh() : void
	{
		Session::i()->csrfCheck();
		
		/* Refresh the feed */
		try
		{
			$count	= Icalendar::load( Request::i()->id )->refresh();
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'feed_not_found', '2L169/1', 404, '' );
		}
		catch( UnexpectedValueException $e )
		{
			Output::i()->error( 'ical_error_' . $e->getMessage(), '4L169/5', 500, '' );
		}

		/* Admin log */
		Session::i()->log( 'acplog__icalfeed_refreshed', array( $count['imported'] => FALSE, $count['skipped'] => FALSE ) );

		/* Redirect */
		Output::i()->redirect( Url::internal( 'app=calendar&module=calendars&controller=ical' ), 'feed_refreshed' );
	}
}