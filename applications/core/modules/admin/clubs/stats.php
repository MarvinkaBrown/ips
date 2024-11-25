<?php
/**
 * @brief		Statistics
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		06 Dec 2019
 */

namespace IPS\core\modules\admin\clubs;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Callback;
use IPS\Helpers\Chart\Database;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics
 */
class stats extends Controller
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
		Dispatcher::i()->checkAcpPermission( 'stats_manage' );
		parent::execute();
	}

	/**
	 * Display the statistics
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* If clubs are not enabled, stop now */
		if ( !Settings::i()->clubs )
		{
			$availableTypes = array();
			foreach ( Club::availableNodeTypes() as $class )
			{
				$availableTypes[] = Member::loggedIn()->language()->addToStack( $class::clubAcpTitle() );
			}
			
			$availableTypes = Member::loggedIn()->language()->formatList( $availableTypes );
			
			Output::i()->output = Theme::i()->getTemplate( 'clubs' )->disabled( $availableTypes );
			return;
		}

		/* Generate the tabs */
		$tabs		= array( 'overview' => 'overview', 'total' => 'stats_club_activity', 'byclub' => 'stats_club_club_activity' );
		$activeTab	= ( isset( Request::i()->tab ) and array_key_exists( Request::i()->tab, $tabs ) ) ? Request::i()->tab : 'overview';

		/* Get the HTML to output */
		$method = '_' . $activeTab;
		$output = $this->$method();

		/* And then print it */
		if ( Request::i()->isAjax() )
		{
			Output::i()->output = (string) $output;
		}
		else
		{	
			Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_clubs_stats');
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $output, Url::internal( "app=core&module=clubs&controller=stats" ) );
		}
	}

	/**
	 * Club activity overview
	 *
	 * @return	string
	 */
	protected function _overview() : string
	{
		$clubTypePieChart	= $this->_getClubTypePieChart();
		$clubSignups		= $this->_getClubSignups();
		$clubCreations		= $this->_getClubCreations();

		return Theme::i()->getTemplate( 'clubs', 'core' )->statsOverview( $clubTypePieChart, $clubSignups, $clubCreations );
	}

	/**
	 * Get line chart showing club signups
	 *
	 * @return Chart
	 */
	protected function _getClubSignups() : Chart
	{
		$chart	= new Database( Url::internal( 'app=core&module=clubs&controller=stats&fetch=signups' ), 'core_clubs_memberships', 'joined', '', array(
			'isStacked' => TRUE,
			'backgroundColor' 	=> '#ffffff',
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		 ), 'ColumnChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array( 'club_id', 'name' ), 'signups' );
		
		$chart->joins[] = array( 'core_clubs', 'core_clubs.id=core_clubs_memberships.club_id' );
		$chart->groupBy = 'club_id';

		foreach( Club::clubs( NULL, NULL, 'name' ) as $club )
		{
			$chart->addSeries( $club->name, 'number', 'COUNT(*)', TRUE, $club->id );
		}

		$chart->title = Member::loggedIn()->language()->addToStack('stats_clubs_signups');
		$chart->availableTypes = array( 'ColumnChart', 'BarChart' );

		if( Request::i()->fetch == 'signups' AND Request::i()->isAjax() )
		{
			Output::i()->sendOutput( (string) $chart );
		}

		return $chart;
	}

	/**
	 * Get line chart showing club creations
	 *
	 * @return Chart
	 */
	protected function _getClubCreations() : Chart
	{
		$chart	= new Database( Url::internal( 'app=core&module=clubs&controller=stats&fetch=clubs' ), 'core_clubs', 'created', '', array(
			'isStacked' => FALSE,
			'backgroundColor' 	=> '#ffffff',
			'colors'			=> array( '#10967e' ),
			'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
			'lineWidth'			=> 1,
			'areaOpacity'		=> 0.4
		 ), 'ColumnChart', 'monthly', array( 'start' => 0, 'end' => 0 ), array(), 'signups' );
		$chart->addSeries( Member::loggedIn()->language()->addToStack('stats_clubs_creations'), 'number', 'COUNT(*)', FALSE );
		$chart->title = Member::loggedIn()->language()->addToStack('stats_clubs_creations');
		$chart->availableTypes = array( 'ColumnChart', 'BarChart' );

		if( Request::i()->fetch == 'clubs' AND Request::i()->isAjax() )
		{
			Output::i()->sendOutput( (string) $chart );
		}

		return $chart;
	}

	/**
	 * Get pie chart showing club types
	 *
	 * @return string
	 */
	protected function _getClubTypePieChart() : string
	{
		$percentages	= array();
		$counts			= array();
		$total			= 0;

		foreach( Db::i()->select( 'type, COUNT(*) as total', 'core_clubs', array(), NULL, NULL, array( 'type' ) ) as $clubs )
		{
			$counts[ $clubs['type'] ] = $clubs['total'];
			$total += $clubs['total'];
		}

		foreach( $counts as $type => $typeTotal )
		{
			$percentages[ $type ] = number_format( round( 100 / $total * $typeTotal, 2 ), 2 );
		}

		return Theme::i()->getTemplate( 'clubs', 'core' )->clubTypesBar( $percentages, $counts );
	}

	/**
	 * Total club activity
	 *
	 * @return	Callback
	 */
	protected function _total() : Chart
	{
		$chart = new Callback(
			Url::internal( 'app=core&module=clubs&controller=stats&tab=total' ),
			array( $this, 'getResults' ),
			'', 
			array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'ColumnChart', 
			'monthly',
			array( 'start' => ( new DateTime )->sub( new DateInterval('P6M') ), 'end' => DateTime::create() ),
			'total'
		);

		foreach( Club::availableNodeTypes() as $nodeType )
		{
			$contentClass = $nodeType::$contentItemClass;
			$chart->addSeries( Member::loggedIn()->language()->get( $contentClass::$title . '_pl' ), 'number');
		}

		$chart->title = Member::loggedIn()->language()->addToStack( 'stats_club_activity' );
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );

		return $chart;
	}

	/**
	 * Activity by club
	 *
	 * @return	string
	 */
	protected function _byclub() : string
	{
		$chart = new Callback(
			Url::internal( 'app=core&module=clubs&controller=stats&tab=byclub' ),
			array( $this, 'getResults' ),
			'', 
			array( 
				'isStacked' => TRUE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'ColumnChart', 
			'monthly',
			array( 'start' => ( new DateTime )->sub( new DateInterval('P6M') ), 'end' => DateTime::create() ),
			'byclub'
		);

		foreach( Club::clubs( NULL, NULL, 'name' ) as $club )
		{
			$chart->addSeries( $club->name, 'number', TRUE );
		}

		$chart->title = Member::loggedIn()->language()->addToStack( 'stats_club_club_activity' );
		$chart->availableTypes = array( 'AreaChart', 'ColumnChart', 'BarChart' );

		return $chart;
	}

	/**
	 * Fetch the results
	 *
	 * @param	Callback	$chart	Chart object
	 * @return	array
	 */
	public function getResults( Callback $chart ) : array
	{
		/* Get the info we need */
		$nodeTypes	= Club::availableNodeTypes();
		$clubNodes	= array();
		$classes	= array();
		$classMap	= array();
		$results	= array();

		foreach( $nodeTypes as $nodeType )
		{
			$clubNodes[ $nodeType ]	= $nodeType::clubNodes( NULL );
			$itemClass	= $nodeType::$contentItemClass;
			$classes[]	= $itemClass;

			$classMap[ $itemClass ]	= $nodeType;

			if( isset( $itemClass::$commentClass ) )
			{
				$classes[] = $itemClass::$commentClass;
				$classMap[ $itemClass::$commentClass ]	= $nodeType;
			}

			if( isset( $itemClass::$reviewClass ) )
			{
				$classes[] = $itemClass::$reviewClass;
				$classMap[ $itemClass::$reviewClass ]	= $nodeType;
			}
		}

		/* If we are fetching by club, we need to build a map of containers to clubs */
		if( mb_strpos( $chart->identifier, 'byclub' ) !== FALSE )
		{
			$clubNodeMap = array();

			foreach( Db::i()->select( '*', 'core_clubs_node_map' ) as $nodeMap )
			{
				$nodeClass = $nodeMap['node_class'];
				$nodeClass = $nodeClass::$contentItemClass;
				$clubNodeMap[ $nodeClass::$databaseTable . '-' . $nodeMap['node_id'] ] = $nodeMap['club_id'];

				if( isset( $nodeClass::$commentClass ) )
				{
					$commentClass = $nodeClass::$commentClass;
					$clubNodeMap[ $commentClass::$databaseTable . '-' . $nodeMap['node_id'] ] = $nodeMap['club_id'];
				}

				if( isset( $nodeClass::$reviewClass ) )
				{
					$reviewClass = $nodeClass::$reviewClass;
					$clubNodeMap[ $reviewClass::$databaseTable . '-' . $nodeMap['node_id'] ] = $nodeMap['club_id'];
				}
			}
		}
		else
		{
			$groupByContainer = FALSE;
		}

		/* Get results */
		foreach ( $classes as $class )
		{
			$where	= array();
			$join	= NULL;

			if( is_subclass_of( $class, '\IPS\Content\Comment' ) )
			{
				/* We're going to need a subquery... */
				$parentClass = $class::$itemClass;

				/* @var array $databaseColumnMap */
				$where[] = array( 
					$class::$databasePrefix . $class::$databaseColumnMap['item'] . " IN(" .					
					Db::i()->select( $parentClass::$databasePrefix . $parentClass::$databaseColumnId, $parentClass::$databaseTable, array( Db::i()->in( $parentClass::$databasePrefix . $parentClass::$databaseColumnMap['container'], array_keys( $clubNodes[ $classMap[ $class ] ] ) ) ) )
					. ")"
				);

				if( mb_strpos( $chart->identifier, 'byclub' ) !== FALSE )
				{
					$groupByContainer = $parentClass::$databaseTable . '.' . $parentClass::$databasePrefix . $parentClass::$databaseColumnMap['container'];
					$join = array( $parentClass::$databaseTable, $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['item'] . '=' . $parentClass::$databaseTable . '.' . $parentClass::$databasePrefix . $parentClass::$databaseColumnId );
				}

				/* We need to account for topic + first post not counting as two posts */
				if( $parentClass::$firstCommentRequired === TRUE AND isset( $class::$databaseColumnMap['first'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['first'] . '=?', 0 );
				}
			}
			else
			{
				/* @var array $databaseColumnMap */
				$where[] = array( Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['container'], array_keys( $clubNodes[ $classMap[ $class ] ] ) ) );

				if( mb_strpos( $chart->identifier, 'byclub' ) !== FALSE )
				{
					$groupByContainer = $class::$databasePrefix . $class::$databaseColumnMap['container'];
				}
			}

			$stmt = $this->getSqlResults(
				$class::$databaseTable,
				$class::$databasePrefix . ( $class::$databaseColumnMap['updated'] ?? $class::$databaseColumnMap['date'] ),
				$class::$databasePrefix . $class::$databaseColumnMap['author'],
				$chart,
				$where,
				( $groupByContainer ?? false ),
				$join
			);

			foreach( $stmt as $row )
			{
				if( !isset( $results[ $row['time'] ] ) )
				{
					$results[ $row['time'] ] = array( 
						'time' => $row['time']
					);

					if( mb_strpos( $chart->identifier, 'byclub' ) !== FALSE )
					{
						foreach( Club::clubs( NULL, NULL, 'name' ) as $club )
						{
							$results[ $row['time'] ][ $club->name ] = 0;
						}
					}
					else
					{
						foreach( Club::availableNodeTypes() as $nodeType )
						{
							$contentClass = $nodeType::$contentItemClass;
							$key = Member::loggedIn()->language()->get( $contentClass::$title . '_pl' );
							Member::loggedIn()->language()->parseOutputForDisplay( $key );
							$results[ $row['time'] ][ $key ] = 0;
						}
					}
				}

				$classType = $classMap[ $class ];

				if( mb_strpos( $chart->identifier, 'byclub' ) !== FALSE )
				{
					$results[ $row['time'] ][ Club::load( $clubNodeMap[ $class::$databaseTable . '-' . $row['container'] ] )->name ] += $row['total'];
				}
				else
				{
					$contentClass = $classMap[ $class ]::$contentItemClass;
					$key = Member::loggedIn()->language()->get( $contentClass::$title . '_pl' );
					Member::loggedIn()->language()->parseOutputForDisplay( $key );
					$results[ $row['time'] ][ $key ] += $row['total'];
				}
			}
		}

		return $results;
	}

	/**
	 * Get SQL query/results
	 *
	 * @note Consolidated to reduce duplicated code
	 * @param	string		$table				Database table
	 * @param	string		$date				Date column
	 * @param	string		$author				Author column
	 * @param	object		$chart				Chart
	 * @param	array		$where				Where clause
	 * @param	bool|string	$groupByContainer	If a string is provided, it must be a column name to group by in addition to time
	 * @param	NULL|array	$join				Join data, if needed (only used when $groupByContainer is set)
	 * @return	Select
	 */
	protected function getSqlResults( string $table, string $date, string $author, object $chart, array $where = array(), bool|string $groupByContainer = FALSE, ?array $join = NULL ) : Select
	{
		/* What's our SQL time? */
		switch ( $chart->timescale )
		{
			case 'daily':
				$timescale = '%Y-%c-%e';
				break;
			
			case 'weekly':
				$timescale = '%x-%v';
				break;
				
			case 'monthly':
			default:
				$timescale = '%Y-%c';
				break;
		}

		$where[]	= array( "{$date}>?", 0 );

		if ( $chart->start )
		{
			$where[] = array( "{$date}>?", $chart->start->getTimestamp() );
		}
		if ( $chart->end )
		{
			$where[] = array( "{$date}<?", $chart->end->getTimestamp() );
		}

		/* First we need to get search index activity */
		$fromUnixTime = "FROM_UNIXTIME( IFNULL( {$date}, 0 ) )";
		if ( !$chart->timezoneError and Member::loggedIn()->timezone and in_array( Member::loggedIn()->timezone, DateTime::getTimezoneIdentifiers() ) )
		{
			$fromUnixTime = "CONVERT_TZ( {$fromUnixTime}, @@session.time_zone, '" . Db::i()->escape_string( Member::loggedIn()->timezone ) . "' )";
		}

		if( $groupByContainer !== FALSE )
		{
			$stmt = Db::i()->select( "DATE_FORMAT( {$fromUnixTime}, '{$timescale}' ) AS time, COUNT(*) as total, {$groupByContainer} as container", $table, $where, 'time ASC', NULL, array( 'time', 'container' ) );

			if( $join !== NULL )
			{
				$stmt = $stmt->join( ...$join );
			}
		}
		else
		{
			$stmt = Db::i()->select( "DATE_FORMAT( {$fromUnixTime}, '{$timescale}' ) AS time, COUNT(*) as total", $table, $where, 'time ASC', NULL, array( 'time' ) );
		}

		return $stmt;
	}
}