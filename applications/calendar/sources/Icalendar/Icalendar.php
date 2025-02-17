<?php
/**
 * @brief		iCalendar active record
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		19 Dec 2013
 */

namespace IPS\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Icalendar\ICSParser;
use IPS\Db;
use IPS\Http\Url;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * iCalendar active record
 */
class Icalendar extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'calendar_import_feeds';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'feed_';

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues() : void
	{
		$this->added		= time();
		$this->lastupdated	= time();
		$this->last_run		= time();
	}

	/**
	 * Reimport iCalendar events
	 *
	 * @return	array
	 * @throws	UnexpectedValueException
	 * @note	PHP installs do not (at least typically) have stream wrappers for webcal://, so we have to change the scheme to http://
	 */
	public function refresh(): array
	{
		$this->last_run	= time();
		$this->save();

		$icsParser	= new ICSParser;

		return $icsParser->parse( Url::external( str_replace( array( 'webcal://', 'webcals://' ), array( 'http://', 'https://' ), $this->url ) )->request()->get(), $this->calendar_id, $this->member_id, $this->id, $this->venue_id );
	}

	/**
	 * Delete events imported from an iCalendar feed
	 *
	 * @return	void
	 */
	public function deleteFeedEvents() : void
	{
		/* Looping over all event IDs this feed has imported, delete them */
		foreach( Db::i()->select( '*', 'calendar_import_map', array( 'import_feed_id=?', $this->id ) ) as $mappedEvent )
		{
			try
			{
				Event::load( $mappedEvent['import_event_id'] )->delete();
			}
			/* An out of range exception means the event no longer exists, so we can ignore the exception in this case */
			catch( OutOfRangeException $e ){ }
		}
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		Db::i()->delete( 'calendar_import_map', array( 'import_feed_id=?', $this->id ) );
		parent::delete();
	}
}