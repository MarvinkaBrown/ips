<?php
/**
 * @brief		upcomingEvents Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		18 Dec 2013
 */

namespace IPS\calendar\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use IPS\Application\Module;
use IPS\calendar\Date;
use IPS\calendar\Event;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * upcomingEvents Widget
 */
class upcomingEvents extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'upcomingEvents';
	
	/**
	 * @brief	App
	 */
	public string $app = 'calendar';

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );

		/* We need to adjust for timezone too */
		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( json_encode( $configuration ) . "_" . Member::loggedIn()->language()->id . "_" . Member::loggedIn()->skin . "_" . json_encode( Member::loggedIn()->groups ) . "_" . $orientation . Member::loggedIn()->timezone );
	}

	/**
	 * Initialize this widget
	 *
	 * @return	void
	 */
	public function init(): void
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'calendar.css', 'calendar' ) );
		
		parent::init();
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
 	{
		$form = parent::configuration( $form );
 		
 		/* Container */
		$form->add( new Node( 'widget_calendar', $this->configuration['widget_calendar'] ?? 0, FALSE, array(
			'class'           => '\IPS\calendar\Calendar',
			'zeroVal'         => 'all',
			'permissionCheck' => 'view',
			'multiple'        => true
		) ) );
		
		$form->add( new YesNo( 'auto_hide', $this->configuration['auto_hide'] ?? FALSE, FALSE ) );
		$form->add( new Number( 'days_ahead', $this->configuration['days_ahead'] ?? 7, TRUE, array( 'unlimited' => -1 ) ) );
		$form->add( new Number( 'maximum_count', $this->configuration['maximum_count'] ?? 5, TRUE, array( 'unlimited' => -1 ) ) );
		return $form;
 	} 
 	
 	/**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
 		if ( is_array( $values['widget_calendar'] ) )
 		{
	 		$values['widget_calendar'] = array_keys( $values['widget_calendar'] );
 		}
 		
 		return $values;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		if( !Member::loggedIn()->canAccessModule( Module::get( 'calendar', 'calendar' ) ) )
		{
			return '';
		}

		$_today	= new Date( "now", Member::loggedIn()->timezone ? new DateTimeZone( Member::loggedIn()->timezone ) : NULL );

		/* Do we have a days ahead cutoff? */
		$endDate	= NULL;

		if( isset( $this->configuration['days_ahead'] ) AND  $this->configuration['days_ahead'] > 0 )
		{
			$endDate	= $_today->adjust( "+" . $this->configuration['days_ahead'] . " days" );
		}
		
		$calendars = NULL;
		
		if ( ! empty( $this->configuration['widget_calendar'] ) )
		{
			$calendars = $this->configuration['widget_calendar'];
		}

		/* How many are we displaying? */
		$count = 5;
		if( isset( $this->configuration['maximum_count'] ) )
		{
			if(  $this->configuration['maximum_count'] > 0  )
			{
				$count = $this->configuration['maximum_count'];
			}
			else if ( $this->configuration['maximum_count'] == -1 )
			{
				$count = NULL;
			}
		}

		/* We only want none club events here */
		if( $calendars === NULL )
		{
			$noneClubCalendars = \IPS\calendar\Calendar::roots();
		}

		$events = Event::retrieveEvents( $_today, $endDate, ( $calendars === NULL ? array_keys( $noneClubCalendars ) : ( !\is_array( $calendars ) ? array( $calendars ) : $calendars ) ), $count, FALSE );

		/* Auto hiding? */
		if( !count($events) AND isset( $this->configuration['auto_hide'] ) AND $this->configuration['auto_hide'] )
		{
			return '';
		}

		return $this->output( $events, $_today );
	}
}