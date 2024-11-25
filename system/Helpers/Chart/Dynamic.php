<?php
/**
 * @brief		Dynamic Chart Builder Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Mar 2017
 */

namespace IPS\Helpers\Chart;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DateTimeZone;
use Exception;
use IPS\core\Statistics\Chart as StatisticsChart;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use Throwable;
use function defined;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_numeric;
use function mb_substr;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dynamic Chart Helper
 */
abstract class Dynamic extends Chart
{
	/**
	 * @brief	URL
	 */
	public Url $url;

	/**
	 * @var Url|null
	 */
	public ?Url $baseURL = null;

	/**
	 * @brief	$timescale (daily, weekly, monthly)
	 */
	public mixed $timescale = 'monthly';

	/**
	 * @brief	Unique identifier for URLs
	 */
	public string $identifier	= '';
	
	/**
	 * @brief	Start Date
	 */
	public mixed $start = NULL;
	
	/**
	 * @brief	End Date
	 */
	public mixed $end = NULL;
	
	/**
	 * @brief	Series
	 */
	protected array $series = array();
	
	/**
	 * @brief	Title
	 */
	public ?string $title = NULL;

	/**
	 * @brief	Description
	 */
	public ?string $description = NULL;

	/**
	 * @brief	Google Chart Options
	 */
	public array $options = array();
	
	/**
	 * @brief	Type
	 */
	public mixed $type;

	/**
	 * @brief	Search term
	 */
	public mixed $searchTerm = null;
	
	/**
	 * @brief	Available Types
	 */
	public array $availableTypes = array( 'AreaChart', 'LineChart', 'ColumnChart', 'BarChart', 'PieChart', 'Table' );
	
	/**
	 * @brief	Available Filters
	 */
	public array $availableFilters = array();
	
	/**
	 * @brief	Current Filters
	 */
	public array $currentFilters = array();

	/**
	 * @brief	Plot zeros
	 */
	public bool $plotZeros = TRUE;
	
	/**
	 * @brief	Value for number formatter
	 */
	public mixed $format = NULL;

	/**
	 * @brief	Allow user to adjust interval (group by daily, monthly, etc.)
	 */
	public bool $showIntervals = TRUE;
	
	/**
	 * @brief	Allow user to adjust date range
	 */
	public bool $showDateRange = TRUE;
	
	/**
	 * @brief	Show save button
	 */
	public bool $showSave = TRUE;
	
	/**
	 * @brief	If a warning about timezones needs to be shown
	 */
	public bool $timezoneError = FALSE;

	/**
	 * @brief	If we need to show a timezone warning, we usually include a link to learn more - set this to TRUE to hide the link
	 */
	public bool $hideTimezoneLink = FALSE;

	/**
	 * @brief	If set to an DateTime instance, minimum time will be checked against this value
	 */
	public ?DateTime $minimumDate = NULL;

	/**
	 * @brief	Enable hourly filtering. USE WITH CAUTION.
	 */
	public bool $enableHourly	= FALSE;

	/**
	 * @brief	Error(s) to show on chart UI
	 */
	public array $errors = array();
	
	/**
	 * @brief	Saved custom filters
	 */
	public array $savedCustomFilters = array();

	/**
	 * @brief	Prefix passed to tables
	 */
	public string $tableLangPrefix = '';
		
	/**
	 * Constructor
	 *
	 * @param	Url	$url			The URL the chart will be displayed on
	 * @param	string $title			Title
	 * @param array $options		Options
	 * @param string $defaultType	The default chart type
	 * @param string $defaultTimescale	The default timescale to use
	 * @param array $defaultTimes	The default start/end times to use
	 * @param string $identifier		If there will be more than one chart per page, provide a unique identifier
	 * @param DateTime|null $minimumDate	The earliest available date for this chart
	 * @return	void
	 *@see		<a href='https://google-developers.appspot.com/chart/interactive/docs/gallery'>Charts Gallery - Google Charts - Google Developers</a>
	 */
	public function __construct( Url $url, string $title='', array $options=array(), string $defaultType='AreaChart', string $defaultTimescale='monthly', array $defaultTimes=array( 'start' => 0, 'end' => 0 ), string $identifier='', DateTime $minimumDate=null )
	{
		/* If we are deleting a chart, just do that now and redirect */
		if( isset( Request::i()->deleteChart ) )
		{
			Session::i()->csrfCheck();
			Db::i()->delete( 'core_saved_charts', array( 'chart_id=? and chart_member=?', Request::i()->deleteChart, Member::loggedIn()->member_id ) );
			Output::i()->redirect( Request::i()->url()->stripQueryString( array( 'deleteChart', 'chartId', 'csrfKey' ) ), 'chart_deleted' );
		}

		if ( !isset( $options['chartArea'] ) )
		{
			$options['chartArea'] = array(
				'left'	=> '50',
				'width'	=> '75%'
			);
		}

		if( isset( Request::i()->chartId ) AND Request::i()->chartId != '_default' )
		{
			$url = $url->setQueryString( 'chartId', Request::i()->chartId );
		}
		
		$this->baseURL		= $url;
		$this->title		= $title;
		$this->options		= $options;
		$this->timescale	= $defaultTimescale;
		$this->start		= $defaultTimes['start'] ?: DateTime::create()->sub( new DateInterval('P6M') );
		$this->end			= $defaultTimes['end'] ?: DateTime::create();
		$this->minimumDate	= $minimumDate;

		if ( isset( Request::i()->type[ $this->identifier ] ) and in_array( Request::i()->type[ $this->identifier ], $this->availableTypes ) )
		{
			$this->type = Request::i()->type[ $this->identifier ];
			$url = $url->setQueryString( 'type', array( $this->identifier => $this->type ) );
		}
		else
		{
			$this->type = $defaultType;
		}

		/* Are we searching? The chart controller should inspect this property if it supports searching to limit the series it adds. */
		if( isset( $this->options['limitSearch'] ) AND isset( Request::i()->search[ $this->identifier ] ) )
		{
			$this->searchTerm = Request::i()->search[ $this->identifier ];
		}

		/* Change timescale */
		if ( isset( Request::i()->timescale[ $this->identifier ] ) and in_array( Request::i()->timescale[ $this->identifier ], array( 'hourly', 'daily', 'weekly', 'monthly' ) ) )
		{
			if( Request::i()->timescale[ $this->identifier ] != 'hourly' OR ( Request::i()->timescale[ $this->identifier ] == 'hourly' AND $this->enableHourly === TRUE ) )
			{
				$this->timescale = Request::i()->timescale[ $this->identifier ];
				$url = $url->setQueryString( 'timescale', array( $this->identifier => Request::i()->timescale[ $this->identifier ] ) );
			}
		}

		if ( $this->type === 'PieChart' or $this->type === 'GeoChart' )
		{
			$this->addHeader( 'key', 'string' );
			$this->addHeader( 'value', 'number' );
		}
		else
		{
			$this->addHeader( Member::loggedIn()->language()->addToStack('date'), ( $this->timescale == 'none' OR $this->timescale == 'hourly' ) ? 'datetime' : 'date' );
		}

		if ( isset( Request::i()->start[ $this->identifier ] ) )
		{
			try
			{
				$originalStart = $this->start;

				if( !Request::i()->start[ $this->identifier ] )
				{
					$this->start = 0;
				}
				elseif ( is_numeric( Request::i()->start[ $this->identifier ] ) )
				{
					$this->start = DateTime::ts( Request::i()->start[ $this->identifier ] );
				}
				else
				{
					$this->start = new DateTime( Date::_convertDateFormat( Request::i()->start[ $this->identifier ] ), new DateTimeZone( Member::loggedIn()->timezone ) );
				}

				if( $this->minimumDate > $this->start )
				{
					$this->errors[] = array( 'string' => 'minimum_chart_date', 'sprintf' => $this->minimumDate->localeDate() );
					$this->start = $originalStart;
				}
				else
				{
					unset( $originalStart );
				}

				if( $this->start )
				{
					$url = $url->setQueryString( 'start', array( $this->identifier => $this->start->getTimestamp() ) );
				}
			}
			catch ( Exception $e ) {}
		}

		if ( isset( Request::i()->end[ $this->identifier ] ) )
		{
			try
			{
				if( !Request::i()->end[ $this->identifier ] )
				{
					$this->end = DateTime::create();
				}
				elseif ( is_numeric( Request::i()->end[ $this->identifier ] ) )
				{
					$this->end = DateTime::ts( Request::i()->end[ $this->identifier ] );
				}
				else
				{
					$this->end = new DateTime( Date::_convertDateFormat( Request::i()->end[ $this->identifier ] ), new DateTimeZone( Member::loggedIn()->timezone ) );
				}

				/* The end date should include items to the end of the day */
				$this->end->setTime( 23, 59, 59 );

				$url = $url->setQueryString( 'end', array( $this->identifier => $this->end->getTimestamp() ) );
			}
			catch ( Exception $e ) {}
		}	
		
		if ( isset( Request::i()->filters[ $this->identifier ] ) )
		{
			$url = $url->setQueryString( 'filters', '' );
		}
		
		$this->url = $url;
		
		if ( Member::loggedIn()->timezone and in_array( Member::loggedIn()->timezone, DateTimeZone::listIdentifiers() ) )
		{
			try
			{
				$r = Db::i()->query( "SELECT TIMEDIFF( NOW(), CONVERT_TZ( NOW(), @@session.time_zone, '" . Db::i()->escape_string( Member::loggedIn()->timezone ) . "' ) );" )->fetch_row();
				if ( $r[0] === NULL )
				{
					$this->timezoneError = TRUE;
				}
			}
			catch ( Db\Exception $e )
			{
				$this->timezoneError = TRUE;
			}
		}

		/* If we have requested a saved chart, load its filters */
		if( isset( Request::i()->chartId ) AND Request::i()->chartId != '_default' AND !isset( Request::i()->filters ) )
		{
			foreach( $this->loadAvailableChartTabs() as $chart )
			{
				if( $chart['chart_id'] == Request::i()->chartId )
				{
					$filters = array();
					foreach( json_decode( $chart['chart_configuration'], true ) as $key => $value )
					{
						if ( mb_substr( $key, 0, 11 ) == 'customform_' )
						{
							$this->savedCustomFilters[ mb_substr( $key, 11 ) ] = $value;
						}
						else
						{
							$filters[ $key ] = $value;
						}
					}
					Request::i()->filters = array( $this->identifier => $filters );
				}
			}
		}
		
		foreach( Request::i() as $key => $value )
		{
			if ( $value and mb_substr( $key, 0, 11 ) === 'customform_' )
			{
				$name = mb_substr( preg_replace( '#' . $this->identifier . '#', '', $key ), 11 );
				
				$this->savedCustomFilters[ $name ] = $value;
			}
		}
	}

	/**
	 * Compile Data for Output
	 *
	 * @return    void
	 */
	abstract public function compileForOutput() : void;
	
	/**
	 * Get the chart output
	 *
	 * @return string
	 */
	abstract public function getOutput(): string;

	/**
	 * @brief	Form to save filters
	 */
	public ?Form $form	= NULL;

	/**
	 * HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{
			/* Generate a form so we can save our filters as a new saved chart */
			$this->form	= new Form;
			$this->form->class = 'ipsForm--vertical ipsForm--chart-custom-filters';

			if ( $this->customFiltersForm )
			{
				if ( $customValues = $this->getCustomFiltersForm()->values( TRUE ) )
				{
					foreach( $customValues as $key => $value )
					{
						$this->form->hiddenValues['customform_' . $key ] = $value;
					}
				}
			}

			/* If we have a sub selector from a node form, add on the identifier and pass off to the form to show the ajax */
			if ( Request::i()->_nodeSelectName )
			{
				Request::i()->_nodeSelectName = $this->identifier . Request::i()->_nodeSelectName;
				return (string) $this->getCustomFiltersForm();
			}

			/* We have an existing chart ID */
			if( isset( Request::i()->chartId ) AND Request::i()->chartId != '_default' )
			{
				$title = '';

				foreach( $this->loadAvailableChartTabs() as $chart )
				{
					if( $chart['chart_id'] == Request::i()->chartId )
					{
						$title = $chart['chart_title'];
						break;
					}
				}

				$custom = array();
				$chartFilters = ( isset( Request::i()->chartFilters ) and Request::i()->chartFilters ) ? Request::i()->chartFilters : array();
				$this->timescale = ( isset( Request::i()->timescale ) and Request::i()->timescale[ $this->identifier ] ) ? Request::i()->timescale[ $this->identifier ] : $chart['chart_timescale'] ?? 'monthly';


				foreach( Request::i() as $key => $value )
				{
					if ( mb_substr( $key, 0, 11 ) === 'customform_' )
					{
						$custom[ preg_replace( '#' . $this->identifier . '#', '', $key ) ] = $value;
					}
				}

				$this->form->add( new Text( 'custom_chart_title', $title, TRUE ) );

				if( $values = $this->form->values() )
				{
					Db::i()->update( 'core_saved_charts', array( 'chart_title' => $values['custom_chart_title'] ), array( 'chart_id=? AND chart_member=?', Request::i()->chartId, Member::loggedIn()->member_id ) );

					/* And then return the output we need */
					Output::i()->json( array(
						'title'		=> $values['custom_chart_title']
					)	);
				}

				/* And we want to save our filter updates */
				if( isset( Request::i()->saveFilters ) )
				{
					Db::i()->update( 'core_saved_charts', array( 'chart_configuration'	=> json_encode( array_merge( $chartFilters, $custom ) ), 'chart_timescale' => Request::i()->timescale ?? $this->timescale ?? 'monthly' ), array( 'chart_id=? AND chart_member=?', Request::i()->chartId, Member::loggedIn()->member_id ) );
				}
			}
			/* We are not viewing a saved chart */
			else
			{
				$this->form->add( new Text( 'custom_chart_title', NULL, TRUE ) );

				if( $values = $this->form->values() )
				{
					$custom = array();
					foreach( Request::i() as $key => $value )
					{
						if ( mb_substr( $key, 0, 11 ) === 'customform_' )
						{
							$custom[ preg_replace( '#' . $this->identifier . '#', '', $key ) ] = $value;
						}
					}

					$chartFilters = ( isset( Request::i()->chartFilters ) and Request::i()->chartFilters ) ? Request::i()->chartFilters : array();

					/* Store the new chart */
					$id = Db::i()->insert( 'core_saved_charts', array(
						'chart_member'			=> Member::loggedIn()->member_id,
						'chart_controller'		=> Request::i()->app . '_' . Request::i()->module . '_' . Request::i()->controller . ( Request::i()->tab ? '_' . Request::i()->tab : '' ),
						'chart_configuration'	=> json_encode( array_merge( $chartFilters, $custom ) ),
						'chart_timescale'		=> Request::i()->timescale ?? $this->timescale ?? 'monthly',
						'chart_title'			=> $values['custom_chart_title'],
					) );

					/* Set some input parameters */
					$this->url					= $this->url->setQueryString( 'chartId', $id );
					Request::i()->chartId	= $id;
					Request::i()->filters	= array( $this->identifier => Request::i()->chartFilters );

					$this->currentFilters		= Request::i()->filters;

					if ( isset( Request::i()->filters[ $this->identifier ] ) )
					{
						$this->url = $this->url->setQueryString( 'filters', array( $this->identifier => $this->currentFilters ) );
					}

					/* Reset form, since template looks for it and it should not be set for a saved chart */
					$this->form	= new Form;
					$this->form->class = 'ipsForm--vertical ipsForm--chart-title';
					$this->form->add( new Text( 'custom_chart_title', $values['custom_chart_title'], TRUE ) );

					/* And then return the output we need */
					Output::i()->json( array(
						'tabHref'	=> $this->url->stripQueryString( 'filters' ),
						'chartId'	=> $id,
						'tabId'		=> md5( $this->url->acpQueryString() ),
					)	);
				}
			}

			/* Get data */
			$output = '';
			if ( !empty( $this->series ) or $this->customFiltersForm )
			{
				$output = $this->getOutput();
			}
			else
			{
				$output = Member::loggedIn()->language()->addToStack('chart_no_results');
			}

			/* Display */
			if ( Request::i()->noheader )
			{
				return $output;
			}
			else
			{
				$chartOutput = Theme::i()->getTemplate( 'global', 'core', 'global' )->dynamicChart( $this, $output );

				/* If we're not showing filter tabs, just return here. */
				if ( !$this->showFilterTabs )
				{
					return $chartOutput;
				}

				if( !Request::i()->isAjax() OR ( Request::i()->tab AND !Request::i()->chartId ) )
				{
					return Theme::i()->getTemplate( 'global', 'core' )->tabs( $this->getChartTabs(), ( isset( Request::i()->chartId ) ) ? Request::i()->chartId : NULL, $chartOutput, $this->url, 'chartId', 'ipsTabs--small' );
				}
				else
				{
					return $chartOutput;
				}
			}
		}
		catch ( Exception|Throwable $e )
		{
			IPS::exceptionHandler( $e );
			return '';
		}
	}

	/**
	 * @brief	Show filter tabs
	 */
	public bool $showFilterTabs = TRUE;

	/**
	 * @brief	Cached tab data
	 */
	protected ?array $availableChartTabs	= NULL;

	/**
	 * @Brief	Extension
	 */
	public ?StatisticsChart $extension = NULL;

	/**
	 * Retrieve tabs based on saved charts
	 *
	 * @return array
	 */
	protected function getChartTabs(): array
	{
		$tabs	= array( '_default' => 'dynamic_chart_overview' );

		foreach( $this->loadAvailableChartTabs() as $chart )
		{
			$tabs[ $chart['chart_id'] ] = $chart['chart_title'];
		}

		return $tabs;
	}

	/**
	 * Load and return available chart tabs
	 *
	 * @return array|null
	 */
	protected function loadAvailableChartTabs() : ?array
	{
		if ( $this->availableChartTabs === null )
		{
			if ( $this->extension !== null )
			{
				$this->availableChartTabs = iterator_to_array( Db::i()->select( '*', 'core_saved_charts', ['chart_member=? AND chart_controller=?', Member::loggedIn()->member_id, $this->extension->controller] ) );
			}
			else
			{
				$this->availableChartTabs = iterator_to_array( Db::i()->select( '*', 'core_saved_charts', ['chart_member=? AND chart_controller=?', Member::loggedIn()->member_id, Request::i()->app . '_' . Request::i()->module . '_' . Request::i()->controller . ( Request::i()->tab ? '_' . Request::i()->tab : '' )] ) );
			}
		}

		return $this->availableChartTabs;
	}

	/**
	 * Set extension
	 *
	 * @param StatisticsChart $ext	Extension
	 * @return	void
	 */
	public function setExtension( StatisticsChart $ext ) : void
	{
		$this->extension = $ext;

		$this->availableChartTabs = NULL;
	}

	/**
	 * Flip URL Filter
	 *
	 * @param string $filter	The Filter
	 * @return	Url
	 */
	public function flipUrlFilter( string $filter ): Url
	{
		$filters = $this->currentFilters;
		
		if ( in_array( $filter, $filters ) )
		{
			unset( $filters[ array_search( $filter, $filters ) ] );
		}
		else
		{
			$filters[] = $filter;
		}
		
		return $this->url->setQueryString( 'filters', array( $this->identifier => $filters ) );
	}

	/**
	 * Init the data array
	 *
	 * @return array
	 */
	protected function initData(): array
	{
		/* Init data */
		$data = array();
		if ( $this->start AND $this->timescale !== 'none' )
		{
			$date = clone $this->start;
			while ( $date->getTimestamp() < ( $this->end ? $this->end->getTimestamp() : time() ) )
			{
				switch ( $this->timescale )
				{
					case 'hourly':
						$data[ $date->format( 'Y-n-j-h-i-s' ) ] = array();

						$date->add( new DateInterval( 'PT1H' ) );
						break;

					case 'daily':
						$data[ $date->format( 'Y-n-j' ) ] = array();

						$date->add( new DateInterval( 'P1D' ) );
						break;
						
					case 'weekly':
						/* o is the ISO year number, which we need when years roll over.
							@see http://php.net/manual/en/function.date.php#106974 */
						$data[ $date->format( 'o-W' ) ] = array();

						$date->add( new DateInterval( 'P7D' ) );
						break;
						
					case 'monthly':
						$data[ $date->format( 'Y-n' ) ] = array();

						$date->add( new DateInterval( 'P1M' ) );
						break;
				}
			}
		}

		return $data;
	}
	
	/**
	 * Custom filter form
	 *
	 * @return Form
	 */
	public function getCustomFiltersForm(): Form
	{
		$customForm = new Form('filter_form', 'chart_customfilters_save');
		$customForm->class = 'ipsForm--vertical ipsForm--custom-filter-form';
		
		if ( isset( Request::i()->chartId ) and Request::i()->chartId != '_default' )
		{
			$customForm->hiddenValues['chartId'] = Request::i()->chartId;
		}

		foreach( $this->customFiltersForm['form'] as $field )
		{
			if ( ! preg_match( '#^' . $this->identifier . '#', $field->name ) )
			{
				$langKey = $field->name;
				$field->name = $this->identifier . $field->name;
				if( Member::loggedIn()->language()->checkKeyExists( $field->name ) )
				{
					Member::loggedIn()->language()->words[ $field->name ] = Member::loggedIn()->language()->get( $langKey );
				}
			}
			$customForm->add( $field );
		}
		
		return $customForm;
	}

	/**
	 * Download as CSV
	 *
	 * @param array|null $rawHeaders
	 * @param array|null $rawRows
	 * @param string|NULL $fileName File Name, or NULL to use the title defined by this object.
	 * @return    void
	 */
	public function download( ?array $rawHeaders = NULL, ?array $rawRows = NULL, ?string $fileName = NULL ) : void
	{
		/* Compile the data */
		$file = tempnam( TEMP_DIRECTORY, 'IPS' );
		$fh = fopen( $file, 'w' );
		
		/* Set headers */
		$headers = array();
		
		if ( $rawHeaders === NULL )
		{
			$rawHeaders = $this->headers;
		}

		/* Ok this is a very horrible hack to get the language hashes converted into the real names and in a format
		   that we can then reparse later. */
		$uglyHack = '';
		foreach( $rawHeaders AS $data )
		{
			$uglyHack .= str_replace( "\n", " ", $data['label'] ) . "\n";
		}

		/* This really is awful */
		Member::loggedIn()->language()->parseOutputForDisplay( $uglyHack );

		foreach( explode( "\n", trim( $uglyHack ) ) as $label )
		{
			/* Now we have the true string, eg: News, Now! instead of a language hash, eg: 77e90423a7642378a1590420cd66a465 so fputcsv can escape it correctly */
			$headers[] = $label;
		}
		fputcsv( $fh, $headers );
		
		/* Set Rows */
		if ( $rawRows === NULL )
		{
			$rawRows = $this->rows;
		}
		
		foreach( $rawRows AS $row )
		{
			$i = 0;
			$save = array();
			foreach( $rawHeaders AS $data )
			{
				switch( $data['type'] )
				{
					/* Booleans convert to string 'true' or 'false' */
					case 'bool':
						$save[] = ( $row[$i] ) ? 'true' : 'false';
						break;
					
					/* Make dates human readable */
					case 'date':
					case 'datetime':
					case 'timeofday':
						$save[] = ( new DateTime( $row[$i] ) )->fullYearLocaleDate();
						break;
					
					/* Anything else can just save as-is */
					default:
						if ( is_array( $row[$i] ) )
						{
							/* GeoCharts use arrays so we need to use the value rather */
							$save[] = $row[$i]['value'];
						}
						else
						{
							$save[] = $row[$i];
						}
						break;
				}
				$i++;
			}
			fputcsv( $fh, $save );
		}
		
		fclose( $fh );
		$csv = file_get_contents( $file );
		if ( $fileName )
		{
			$name = $fileName;
		}
		else if ( $this->title )
		{
			$name = $this->title;
		}
		else
		{
			$name = Output::i()->title;
		}
		Member::loggedIn()->language()->parseOutputForDisplay( $name );
		Member::loggedIn()->language()->parseOutputForDisplay( $csv );
		Output::i()->sendOutput( $csv, 200, 'text/csv', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "{$name}.csv" ) ), FALSE, FALSE, FALSE );
	}
}