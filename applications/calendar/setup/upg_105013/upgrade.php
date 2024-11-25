<?php
/**
 * @brief		4.5.0 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		19 Jul 2019
 */

namespace IPS\calendar\setup\upg_105013;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DatePeriod;
use InvalidArgumentException;
use IPS\calendar\Date;
use IPS\calendar\Icalendar\ICSParser;
use IPS\core\Setup\Upgrade as UpgradeClass;
use IPS\Db;
use IPS\Request;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.5.0 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Set the event recurring end date/time for recurring events that end
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$perCycle	= 100;
		$did		= 0;
		$limit		= intval( Request::i()->extra );

		/* Try to prevent timeouts to the extent possible */
		$cutOff			= UpgradeClass::determineCutoff();

		foreach( Db::i()->select( '*', 'calendar_events', array( 'event_recurring IS NOT NULL' ), 'event_id ASC', array( $limit, $perCycle ) ) as $event )
		{
			if( $cutOff !== null AND time() >= $cutOff )
			{
				return ( $limit + $did );
			}

			$did++;

			try
			{
				$recurrenceRule = ICSParser::parseRrule( $event['event_recurring'], 'UTC' );
			}
			catch( InvalidArgumentException $e )
			{
				continue;
			}

			$endDate = NULL;

			/* Please be easy. Please be easy. */
			if( $recurrenceRule['repeat_end_date'] )
			{
				$endDate = $recurrenceRule['repeat_end_date']->format( 'Y-m-d H:i' );
			}
			/* Ok, then check if occurrences limit is present... */
			elseif( $recurrenceRule['repeat_end_occurrences'] )
			{
				switch( $recurrenceRule['event_repeats'] )
				{
					case 'daily':
						$keyword	= "days";
					break;

					case 'weekly':
						$keyword	= 'weeks';
					break;

					case 'monthly':
						$keyword	= "months";
					break;

					case 'yearly':
						$keyword	= "years";
					break;
				}

				$period = new DatePeriod( Date::parseTime( $event['event_start_date'], $event['event_all_day'] ? FALSE : TRUE ), new DateInterval( 'P' . $recurrenceRule['event_repeat_freq'] . mb_strtoupper( mb_substr( $keyword, 0, 1 ) ) ), $recurrenceRule['repeat_end_occurrences'] );

				foreach( $period as $dateOccurrence )
				{
					$endDate = $dateOccurrence->format( 'Y-m-d H:i' );
				}
			}

			if( $endDate !== NULL )
			{
				Db::i()->update( 'calendar_events', array( 'event_recurring_end_date' => $endDate ), array( 'event_id=?', $event['event_id'] ) );
			}
		}

		if( $did )
		{
			return ( $limit + $did );
		}
		else
		{
			unset( $_SESSION['_step1Count'] );
			return TRUE;
		}
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		$limit = isset( Request::i()->extra ) ? Request::i()->extra : 0;

		if( !isset( $_SESSION['_step1Count'] ) )
		{
			$_SESSION['_step1Count'] = Db::i()->select( 'COUNT(*)', 'calendar_events', array( 'event_recurring IS NOT NULL' ) )->first();
		}

		return "Upgrading recurring calendar events (Upgraded so far: " . ( ( $limit > $_SESSION['_step1Count'] ) ? $_SESSION['_step1Count'] : $limit ) . ' out of ' . $_SESSION['_step1Count'] . ')';
	}
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}