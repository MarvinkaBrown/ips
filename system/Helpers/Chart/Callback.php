<?php
/**
 * @brief		Dynamic Chart Helper with user-supplied results
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 Mar 2017
 */

namespace IPS\Helpers\Chart;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use Exception;
use IPS\DateTime;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use function count;
use function defined;
use function in_array;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dynamic Chart Helper with user supplied results.
 * This is similar to \IPS\Helpers\Chart\Database but with results supplied manually in user-land code. 
 * Useful when you need to merge results from multiple sources.
 */
class Callback extends Dynamic
{
	/**
	 * @brief	Callback to retrieve results
	 */
	public mixed $callback = NULL;

	/**
	 * @brief	Custom form
	 */
	public bool|array $customFiltersForm = FALSE;
	
	/**
	 * Constructor
	 *
	 * @param	Url	$url			The URL the chart will be displayed on
	 * @param	callable|null $callback		Callback to fetch results
	 * @param string $title			Title
	 * @param array $options		Options
	 * @param string $defaultType	The default chart type
	 * @param string $defaultTimescale	The default timescale to use
	 * @param array $defaultTimes	The default start/end times to use
	 * @param string $identifier		If there will be more than one chart per page, provide a unique identifier
	 * @param DateTime|null $minimumDate	The earliest available date for this chart
	 * @return	void
	 *@see		<a href='https://google-developers.appspot.com/chart/interactive/docs/gallery'>Charts Gallery - Google Charts - Google Developers</a>
	 */
	public function __construct( Url $url, callable $callback=NULL, string $title='', array $options=array(), string $defaultType='AreaChart', string $defaultTimescale='monthly', array $defaultTimes=array( 'start' => 0, 'end' => 0 ), string $identifier='', DateTime $minimumDate=NULL )
	{
		$this->identifier	= substr( md5( (string) $url ), 0, 6 ) . $identifier . ( Request::i()->chartId ?: '_default' );
		$this->callback		= $callback;

		parent::__construct( $url, $title, $options, $defaultType, $defaultTimescale, $defaultTimes, $identifier, $minimumDate );
	}

	/**
	 * Add Series
	 *
	 * @param string $name		Name
	 * @param string $type		Type of value
	 *	@li	string
	 *	@li	number
	 *	@li	boolean
	 *	@li	date
	 *	@li	datetime
	 *	@li	timeofday
	 * @param bool $filterable	If TRUE, will show as a filter option to be toggled on/off
	 * @return	void
	 */
	public function addSeries( string $name, string $type, bool $filterable=TRUE ) : void
	{
		Member::loggedIn()->language()->parseOutputForDisplay( $name );
		$filterKey = $name;
		
		if ( !$filterable or !isset( Request::i()->filters[ $this->identifier ] ) or in_array( $filterKey, Request::i()->filters[ $this->identifier ] ) )
		{
			if ( $this->type !== 'PieChart' and $this->type !== 'GeoChart' )
			{
				$this->addHeader( $name, $type );
			}

			$this->series[ $filterKey ] = $filterKey;
			
			if ( $filterable )
			{
				$this->currentFilters[] = $filterKey;
				
				if ( isset( Request::i()->filters[ $this->identifier ] ) )
				{
					$this->url = $this->url->setQueryString( 'filters', array( $this->identifier => $this->currentFilters ) );
				}
			}
		}
		
		if ( $filterable )
		{
			$this->availableFilters[ $filterKey ] = $name;
		}
	}

	/**
	 * Compile Data for Output
	 *
	 * @return    void
	 */
	public function compileForOutput() : void
	{
		/* Init data */
		$data = $this->initData();

		/* Get rows */
		$callbackFunction = $this->callback;
		$rows = $callbackFunction( $this );
		
		/* Pie Chart */
		if ( $this->type === 'PieChart' or $this->type === 'GeoChart' )
		{					
			foreach ( $rows as $k => $v )
			{
				if( count( $this->availableFilters ) and !in_array( $k, $this->currentFilters ) )
				{
					continue;
				}

				$this->addRow( array( 'key' => $this->availableFilters[ $k ], 'value' => $v ) );
			}
		}
		
		/* Graph */
		else
		{
			foreach ( $rows as $row )
			{
				$result	= array();

				foreach( $this->series as $column )
				{
					$result[ $column ]	= $row[ $column ];
				}
	
				$data[ $row['time'] ] = $result;
			}

			ksort( $data, SORT_NATURAL );
			
			/* Add to graph */
			$min = NULL;
			$max = NULL;
			foreach ( $data as $time => $d )
			{
				$datetime = new DateTime;

				if ( Member::loggedIn()->timezone )
				{
					try
					{
						$datetime->setTimezone( new DateTimeZone( Member::loggedIn()->timezone ) );
					}
					catch ( Exception $e )
					{
						Member::loggedIn()->timezone	= null;
						Member::loggedIn()->save();
					}
				}

				$datetime->setTime( 0, 0, 0 );
				$exploded = explode( '-', $time );

				if( $this->enableHourly === TRUE AND $this->timescale == 'hourly' )
				{
					$datetime->setDate( (float) $exploded[0], $exploded[1], $exploded[2] );
					$datetime->setTime( $exploded[3], $exploded[4], $exploded[5] );
				}
				else
				{
					switch ( $this->timescale )
					{
						case 'none':
							$datetime = DateTime::ts( $time );
							break;

						case 'daily':
							$datetime->setDate( (float) $exploded[0], $exploded[1], $exploded[2] );
							//$datetime = $datetime->localeDate();
							break;
							
						case 'weekly':
							$datetime->setISODate( (float) $exploded[0], $exploded[1] );
							//$datetime = $datetime->localeDate();
							break;
							
						case 'monthly':
							$datetime->setDate( (float) $exploded[0], $exploded[1], 1 );
							//$datetime = $datetime->format( 'F Y' );
							break;
					}
				}
							
				if ( empty( $d ) )
				{
					if ( empty( $this->series ) )
					{
						$this->addRow( array( $datetime ) );
					}
					else
					{
						$this->addRow( array_merge( array( $datetime ), array_fill( 0, count( $this->series ), 0 ) ) );
					}
				}
				else
				{
					if( count($d) < count($this->series) )
					{
						$this->addRow( array_merge( array( $datetime ), $d, array_fill( 0, count($this->series) - count($d), 0 ) ) );
					}
					else
					{
						$this->addRow( array_merge( array( $datetime ), $d ) );
					}
				}
			}
			
			if ( count( $data ) === 1 )
			{
				$this->options['domainAxis']['type'] = 'category';
			}
		}
	}

	/**
	 * Get the chart output
	 *
	 * @return	string
	 */
	public function getOutput(): string
	{
		$this->compileForOutput();
		if ( isset( Request::i()->download ) )
		{
			$this->download();
			return '';
		}
		else
		{
			return $this->render( $this->type, $this->options, $this->format );
		}
	}
}