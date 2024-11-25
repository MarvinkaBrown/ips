<?php
/**
 * @brief		Dynamic Database Chart Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Aug 2013
 */

namespace IPS\Helpers\Chart;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateTimeZone;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use function count;
use function defined;
use function header;
use function in_array;
use function is_array;
use function mb_strlen;
use function mb_substr;
use function md5;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dynamic Database Chart Helper
 */
class Database extends Dynamic
{
	/**
	 * @brief	Database Table
	 */
	protected ?string $table = null;
	
	/**
	 * @brief	Database column that contains date
	 */
	protected ?string $dateField = null;
	
	/**
	 * @brief	Where clauses
	 */
	public array $where	= array();

	/**
	 * @brief	Query joins
	 */
	public array $joins	= array();
	
	/**
	 * @brief	Extra column to group by (useful for multi-data line charts)
	 */
	public string $groupBy	= '';

	/**
	 * @brief	Group by keys for the series
	 */
	protected array $groupByKeys = array();

	/**
	 * @brief	Table Columns
	 */
	public array $tableInclude = array();
	
	/**
	 * @brief	Table Column Formatters
	 */
	public array $tableParsers = array();

	/**
	 * @brief   Along with the other series, show one of the sum of all
	 */
	public bool $allRecordsSeries = FALSE;

	/**
	 * @brief   The lang string used to label the 'all records' series
	 */
	public string $allRecordsLang  = 'database_chart_all_series';
	
	/**
	 * @brief	Custom form
	 */
	public bool|array $customFiltersForm = FALSE;
	
	/**
	 * Constructor
	 *
	 * @param	Url	$url			The URL the chart will be displayed on
	 * @param	string $table			Database Table
	 * @param string $dateField		Database column that contains date
	 * @param string $title			Title
	 * @param array $options		Options
	 * @param string $defaultType	The default chart type
	 * @param string $defaultTimescale	The default timescale to use
	 * @param array $defaultTimes	The default start/end times to use
	 * @param array $tableInclude	Table columns to include in results
	 * @param string $identifier		If there will be more than one chart per page, provide a unique identifier
	 * @param DateTime|null $minimumDate	The earliest available date for this chart
	 * @return	void
	 *@see		<a href='https://google-developers.appspot.com/chart/interactive/docs/gallery'>Charts Gallery - Google Charts - Google Developers</a>
	 */
	public function __construct( Url $url, string $table, string $dateField, string $title='', array $options=array(), string $defaultType='AreaChart', string $defaultTimescale='monthly', array $defaultTimes=array( 'start' => 0, 'end' => 0 ), array $tableInclude=array(), string $identifier='', DateTime $minimumDate=null )
	{
		$this->table		= $table;
		$this->dateField	= $dateField;
		$this->identifier	= substr( md5( $table . $dateField ), 0, 6 ) . $identifier . ( Request::i()->chartId ?: '_default' );

		if ( !empty( $tableInclude ) )
		{
			$this->tableInclude = $tableInclude;
		}

		parent::__construct( $url, $title, $options, $defaultType, $defaultTimescale, $defaultTimes, $identifier, $minimumDate );
	}

	/**
	 * Add Series
	 *
	 * @param	mixed	$name		Either a string with the series name or an array [ 'value' => "Series Name", 'key' => "XX" ]. Keys are used to give a country code for GeoCharts.
	 * @param string $type		Type of value
	 *	@li	string
	 *	@li	number
	 *	@li	boolean
	 *	@li	date
	 *	@li	datetime
	 *	@li	timeofday
	 * @param string $sql		SQL expression to get value
	 * @param bool $filterable	If TRUE, will show as a filter option to be toggled on/off
	 * @param string|null $groupByKey	If $this->groupBy is set, the raw key value
	 * @return	void
	 */
	public function addSeries( mixed $name, string $type, string $sql, bool $filterable=TRUE, string $groupByKey=NULL ) : void
	{
		if ( $groupByKey !== NULL )
		{
			$filterKey = $groupByKey;
		}
		else
		{
			$name = is_array( $name ) ? $name['value'] : $name;
			Member::loggedIn()->language()->parseOutputForDisplay( $name );
			$filterKey = $name;
		}

		if ( !$filterable or !isset( Request::i()->filters[ $this->identifier ] ) or in_array( $filterKey, Request::i()->filters[ $this->identifier ] ) )
		{
			if ( $this->type !== 'PieChart' and $this->type !== 'GeoChart' )
			{
				$this->addHeader( is_array( $name ) ? $name['value'] : $name, $type );
			}

			if( $this->groupBy )
			{
				$this->groupByKeys[]	= $groupByKey;
			}

			$this->series[ $filterKey ] = $sql;

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
	 * Init the data array
	 *
	 * @return array
	 */
	protected function initData(): array
	{
		$data = parent::initData();

		if ( $this->customFiltersForm )
		{
			if ( $values = $this->getCustomFiltersForm()->values() )
			{
				$whereFunction = $this->customFiltersForm['where'];
				$seriesFunction = $this->customFiltersForm['series'];

				foreach( $values as $k => $v )
				{
					unset( $values[ $k ] );
					$values[ mb_substr( $k, mb_strlen( $this->identifier ) ) ] = $v;
				}

				$this->groupBy = $this->customFiltersForm['groupBy'];

				$whereFunctionResult = $whereFunction( $values );

				$this->where[] = is_array( $whereFunctionResult ) ? $whereFunctionResult : array( $whereFunctionResult );

				foreach( $seriesFunction( $values ) as $series )
				{
					$this->addSeries( $series[0], $series[1], $series[2], $series[3], $series[4] );
				}
			}
			else if ( $this->savedCustomFilters and count( $this->savedCustomFilters ) )
			{
				$whereFunction = $this->customFiltersForm['where'];
				$seriesFunction = $this->customFiltersForm['series'];

				$this->groupBy = $this->customFiltersForm['groupBy'];

				$whereFunctionResult = $whereFunction( $this->savedCustomFilters );
				$this->where[] = is_array( $whereFunctionResult ) ? $whereFunctionResult : array( $whereFunctionResult );

				foreach( $seriesFunction( $this->savedCustomFilters ) as $series )
				{
					$this->addSeries( $series[0], $series[1], $series[2], $series[3], $series[4] );
				}
			}
			else
			{
				$seriesFunction = $this->customFiltersForm['defaultSeries'];

				foreach( $seriesFunction() as $series )
				{
					$this->addSeries( $series[0], $series[1], $series[2], $series[3], $series[4] );
				}
			}
		}

		if ( $this->allRecordsSeries )
		{
			/* Work out where clause */
			$where = $this->where;
			$where[] = array( "{$this->dateField}>?", 0 );
			if ( $this->start )
			{
				$where[] = array( "{$this->dateField}>?", $this->start->getTimestamp() );
			}
			if ( $this->end )
			{
				$where[] = array( "{$this->dateField}<?", $this->end->getTimestamp() );
			}

			/* What's our SQL time? */
			switch ( $this->timescale )
			{
				case 'hourly':
					$timescale = '%Y-%c-%e-%H-%i-%s';
					break;

				case 'daily':
					$timescale = '%Y-%c-%e';
					break;

				case 'weekly':
					$timescale = '%x-%v';
					break;

				case 'monthly':
					$timescale = '%Y-%c';
					break;
			}

			$fromUnixTime = "FROM_UNIXTIME( IFNULL( {$this->dateField}, 0 ) )";
			if ( !$this->timezoneError and Member::loggedIn()->timezone and in_array( Member::loggedIn()->timezone, DateTimeZone::listIdentifiers() ) )
			{
				$fromUnixTime = "CONVERT_TZ( {$fromUnixTime}, @@session.time_zone, '" . Db::i()->escape_string( Member::loggedIn()->timezone ) . "' )";
			}

			$allRecordsStatement = Db::i()->select(
				"SQL_BIG_RESULT DATE_FORMAT( {$fromUnixTime}, '{$timescale}' ) AS time_as_alias, COUNT(*) as $this->allRecordsLang",
				$this->table,
				$where,
				'time_as_alias ASC',
				NULL,
				'time_as_alias'
			);

			foreach ( $allRecordsStatement as $allRecordsRow )
			{
				$data[ $allRecordsRow['time_as_alias'] ] = $data[ $allRecordsRow['time_as_alias'] ] ?? array();
				$data[ $allRecordsRow['time_as_alias'] ][$this->allRecordsLang] = $allRecordsRow[$this->allRecordsLang];
			}

			$this->addSeries( Member::loggedIn()->language()->addToStack($this->allRecordsLang), 'number', 'COUNT(*)', FALSE, $this->allRecordsLang );
		}

		return $data;
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

		/* Work out where clause */
		$where = $this->where;
		$where[] = array( "{$this->dateField}>?", 0 );
		if ( $this->start )
		{
			$where[] = array( "{$this->dateField}>?", $this->start->getTimestamp() );
		}
		if ( $this->end )
		{
			$where[] = array( "{$this->dateField}<?", $this->end->getTimestamp() );
		}

		/* What's our SQL time? */
		switch ( $this->timescale )
		{
			case 'hourly':
				$timescale = '%Y-%c-%e-%H-%i-%s';
				break;

			case 'daily':
				$timescale = '%Y-%c-%e';
				break;

			case 'weekly':
				$timescale = '%x-%v';
				break;

			case 'monthly':
				$timescale = '%Y-%c';
				break;
		}

		/* Pie Chart */
		if ( $this->type === 'PieChart' or $this->type === 'GeoChart' )
		{
			$keys = array_unique( $this->series );
			$key = array_pop( $keys );

			$stmt = Db::i()->select(
				"{$key}" . ( $this->groupBy ? ", {$this->groupBy}" : '' ),
				$this->table,
				$where,
				NULL,
				NULL,
				$this->groupBy
			);

			if( count( $this->joins ) )
			{
				foreach( $this->joins as $join )
				{
					$stmt = $stmt->join( $join[0], $join[1], ( $join[2] ?? 'LEFT' ), ( $join[3] ?? FALSE ) );
				}
			}

			/* Calling this before the joins are added just executes the query and the joins are never added */
			$stmt->setKeyField( $this->groupBy )->setValueField( $key );

			foreach ( $stmt as $k => $v )
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
			/* Fetch */
			$fromUnixTime = "FROM_UNIXTIME( IFNULL( {$this->dateField}, 0 ) )";
			if ( !$this->timezoneError and Member::loggedIn()->timezone and in_array( Member::loggedIn()->timezone, DateTimeZone::listIdentifiers() ) )
			{
				$fromUnixTime = "CONVERT_TZ( {$fromUnixTime}, @@session.time_zone, '" . Db::i()->escape_string( Member::loggedIn()->timezone ) . "' )";
			}

			$stmt = Db::i()->select(
				"SQL_BIG_RESULT DATE_FORMAT( {$fromUnixTime}, '{$timescale}' ) AS time_as_alias" . ( count( $this->series ) ? "," : "" ) . implode( ', ', array_unique( $this->series ) ) . ( $this->groupBy ? ", " . $this->groupBy : '' ),
				$this->table,
				$where,
				'time_as_alias ASC',
				NULL,
				$this->groupBy ? array( 'time_as_alias', $this->groupBy ) : 'time_as_alias'
			);

			if( count( $this->joins ) )
			{
				foreach( $this->joins as $join )
				{
					$stmt = $stmt->join( $join[0], $join[1], ( $join[2] ?? 'LEFT' ), ( $join[3] ?? FALSE ) );
				}
			}

			foreach ( $stmt as $row )
			{
				$result	= array();

				if( $this->groupBy )
				{
					if( count( $this->availableFilters ) AND !in_array( $row[ $this->groupBy ], $this->currentFilters ) )
					{
						continue;
					}
				}

				foreach( $this->series as $column )
				{
					if( $this->groupBy )
					{
						if( empty( $data[ $row['time_as_alias'] ] ) )
						{
							$result	= array( $row[ $this->groupBy ] => $row[ $column ] );
						}
						else
						{
							$result	= $data[ $row['time_as_alias'] ];
							$result[ $row[ $this->groupBy ] ] = $row[ $column ];
						}
					}
					else
					{
						$result[ $column ]	= $row[ $column ];
					}
				}

				$data[ $row['time_as_alias'] ] = $result;
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
					if( $this->groupBy )
					{
						$_values	= array();
						foreach ( $this->series as $id => $col )
						{
							$_values[ $id ] = ( isset( $d[ $id ] ) ) ? $d[ $id ] : ( $this->plotZeros ? 0 : NULL );
						}

						$this->addRow( array_merge( array( $datetime ), $_values ) );
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
		/* Auto-support tables where appropriate */
		if ( !empty( $this->tableInclude ) )
		{
			if( !array_search( 'Table', $this->availableTypes ) )
			{
				$this->availableTypes[] = 'Table';
			}
		}

		/* Work out where clause */
		$where = $this->where;
		$where[] = array( "{$this->dateField}>?", 0 );
		if ( $this->start )
		{
			$where[] = array( "{$this->dateField}>?", $this->start->getTimestamp() );
		}
		if ( $this->end )
		{
			$where[] = array( "{$this->dateField}<?", $this->end->getTimestamp() );
		}

		/* Table... */
		if ( $this->type === 'Table' )
		{
			/* Are we filtering? */
			if( $this->groupBy )
			{
				if( count( $this->availableFilters ) AND count( $this->currentFilters ) )
				{
					$where[] = array( Db::i()->in( $this->groupBy, $this->currentFilters ) );
				}
			}

			$table = new TableDb( $this->table, $this->url, $where );

			/* Reformat chart join for the table helper */
			if( is_array( $this->joins ) )
			{
				array_walk( $this->joins, function ( $item ) use ( $table )
				{
					$table->joins[] = array('from' => $item[0], 'where' => $item[1], 'type' => $item[2] ?? 'LEFT' );
				} );
			}

			if( count( $this->tableInclude ) )
			{
				$table->include = $this->tableInclude;
			}
			if ( $this->tableLangPrefix )
			{
				$table->langPrefix = $this->tableLangPrefix;
			}
			$table->parsers = $this->tableParsers;
			$table->sortBy = $table->sortBy ?: $this->dateField;

			if ( isset( Request::i()->download ) )
			{
				$headers = array();
				foreach( $table->getHeaders( $table->getAdvancedSearchValues() ) AS $header )
				{
					if ( $header === '_buttons' )
					{
						continue;
					}

					$headers[] = array(
						'label'		=> Member::loggedIn()->language()->addToStack( $header ),
						'type'		=> 'string'
					);
				}

				$rows = array();
				foreach( $table->getRows( $table->getAdvancedSearchValues() ) AS $k => $v )
				{
					$rows[$k] = array();
					foreach( $v AS $j => $value )
					{
						$rows[$k][] = trim( strip_tags( (string) $value ) );
					}
				}

				$this->download( $headers, $rows, Output::i()->title );
				return '';
			}

			return (string) $table;
		}

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