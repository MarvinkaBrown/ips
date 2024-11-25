<?php
/**
 * @brief		Popular things
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Oct 2016
 */

namespace IPS\core\modules\front\discover;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DateTimeZone;
use Exception;
use IPS\Application;
use IPS\Content\Reaction;
use IPS\Content\Search\ContentFilter;
use IPS\Content\Search\Index;
use IPS\Content\Search\Query;
use IPS\Content\Search\Results;
use IPS\core\DataLayer;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use function array_slice;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Most popular things
 */
class popular extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		/* Ensure that the rep system is enabled and the leaderboard is enabled */
		if ( ! Settings::i()->reputation_enabled or ! Settings::i()->reputation_leaderboard_on )
		{
			Output::i()->error( 'module_no_permission', '2C343/1', 403, '' );
		}
		
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/search.css' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/leaderboard.css' ) );
		
		/* Make sure default tab is first */
		$tabs = array( Settings::i()->reputation_leaderboard_default_tab );
		
		foreach( array( 'leaderboard', 'history', 'members' ) as $addTab )
		{
			if ( $addTab != Settings::i()->reputation_leaderboard_default_tab )
			{
				$tabs[] = $addTab;
			}
		}

		if ( Application::appIsEnabled('cloud') and \IPS\cloud\Application::featureIsEnabled('trending') )
		{
			$tabs[] = 'trending';
		}
		
		$activeTab = ( isset( Request::i()->tab ) and in_array(Request::i()->tab, $tabs ) ) ? Request::i()->tab : Settings::i()->reputation_leaderboard_default_tab;
		
		/* Initiate the breadcrumb */
		Output::i()->breadcrumb = array( array( Url::internal( "app=core&module=discover&controller=popular&tab=" . $activeTab, 'front', 'leaderboard_' . $activeTab ), Member::loggedIn()->language()->addToStack('leaderboard_title') ) );
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'leaderboard_tabs_' . $activeTab );
		
		$content = $this->$activeTab();

		/* Data Layer Context Property */
		if ( Settings::i()->core_datalayer_enabled AND ! Request::i()->isAjax() )
		{
			DataLayer::i()->addContextProperty( 'community_area', 'leaderboard' );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $content ) );
		}
		else
		{
			/* Display */
			Output::i()->output = Theme::i()->getTemplate('popular')->tabs( $tabs, $activeTab, $content );
		}
	}
	
	/**
	 * View top members
	 *
	 * @return	string
	 */
	protected function members() : string
	{
		/* Get filters */
		$filters = array_merge( array( 'overview' => Member::loggedIn()->language()->addToStack('overview') ), Member::topMembersOptions( Member::TOP_MEMBERS_FILTERS ) );
		$activeFilter = 'overview';
		if ( isset( Request::i()->filter ) )
		{
			if ( array_key_exists( Request::i()->filter, $filters ) )
			{
				$activeFilter = Request::i()->filter;
			}
			else
			{
				$possibleFilter = 'IPS\\' . str_replace( '_', '\\', Request::i()->filter );
				if ( array_key_exists( $possibleFilter, $filters ) )
				{
					$activeFilter = $possibleFilter;
				}
			}
		}
		
		/* Get results */
		if ( $activeFilter == 'overview' )
		{
			$output = Theme::i()->getTemplate('popular')->topMembersOverview( Member::topMembersOptions( Member::TOP_MEMBERS_OVERVIEW ) );
		}
		else
		{
			$output = Theme::i()->getTemplate('popular')->topMembersResults( $activeFilter, NULL, Member::topMembers( $activeFilter, Settings::i()->reputation_max_members ) );
		}

		/* Output */
		Output::i()->linkTags['canonical'] = (string) Url::internal( "app=core&module=discover&controller=popular&tab=members", 'front', 'leaderboard_members' );
		if ( Request::i()->isAjax() and Request::i()->topMembers )
		{
			Output::i()->json( array( 'rows' => $output, 'extraHtml' => $filters[ $activeFilter ] ) );
		}
		else
		{
			return Theme::i()->getTemplate('popular')->topMembers( Url::internal( 'app=core&module=discover&controller=popular&tab=members', 'front', 'leaderboard_members' ), $filters, $activeFilter, $output );
		}
	}
	
	/**
	 * View past leaders
	 *
	 * @return	string
	 */
	protected function history() : string
	{
		$table = new \IPS\Helpers\Table\Db( 'core_reputation_leaderboard_history', Url::internal( "app=core&module=discover&controller=popular&tab=history", 'front', 'leaderboard_history' ) );
		$table->where = array( 'leader_position <= 3' );
		$table->limit = 60;
		$table->selects = array( 'leader_member_id, leader_position, leader_rep_total', 'ABS(leader_date - leader_position) as leader_date' );
		$table->joins = array( array( 'select' => 'core_members.*', 'from' => 'core_members', 'where' => 'core_reputation_leaderboard_history.leader_member_id=core_members.member_id' ) );
		$table->include = array( 'leader_member_id', 'leader_date', 'leader_position', 'leader_rep_total' );
		$table->noSort = array( 'leader_member_id', 'leader_position', 'leader_rep_total' );
		$table->tableTemplate = array( Theme::i()->getTemplate( 'popular', 'core', 'front' ), 'popularTable' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'popular', 'core', 'front' ), 'popularRows' );
		$table->title = 'leaderboard_history';
		$table->mainColumn = 'leader_date';
		$table->sortBy = $table->sortBy ?: 'leader_date';
		$table->sortDirection = $table->sortDirection ?: 'DESC';
		
		/* Like or what? */
		if ( Reaction::isLikeMode() )
		{
			Member::loggedIn()->language()->words['leader_rep_total'] = Member::loggedIn()->language()->addToStack( 'leader_rep_total_likes' );
			Member::loggedIn()->language()->words['leaderboard_history__desc'] = Member::loggedIn()->language()->addToStack( 'leaderboard_history_desc', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'leaderboard_history_likes') ) ) );
		}
		else
		{
			Member::loggedIn()->language()->words['leader_rep_total'] = Member::loggedIn()->language()->addToStack( 'leader_rep_total_rep' );
			Member::loggedIn()->language()->words['leaderboard_history__desc'] = Member::loggedIn()->language()->addToStack( 'leaderboard_history_desc', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'leaderboard_history_rep') ) ) );
		}
		
		/* Parsers */
		$table->parsers = array(
			'leader_member_id' => function( $val, $row )
			{
				return Member::constructFromData( $row );
			},
			'leader_date' => function( $val )
			{
				return DateTime::ts( $val )->setTimezone( new DateTimeZone( Settings::i()->reputation_timezone ) );
			}
		);

		Output::i()->linkTags['canonical'] = (string) Url::internal( "app=core&module=discover&controller=popular&tab=history", 'front', 'leaderboard_history' );
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $table ) );
		}
		else
		{
			if ( $table->page > 1 )
			{
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( Output::i()->title, $table->page ) ) );
			}

			return (string) $table;
		}
	}
	
	/**
	 * View Popular list
	 *
	 * It's slightly cumbersome because we need to group, but we can only select columns in the group list, so we have to:
	 * 1) Fetch the lookup_hash and total_rep
	 * 2) Fetch the rep data so we can...
	 * 3) Fetch the search engine data and then...
	 * 4) Manually sort in PHP
	 *
	 * @return	string
	 */
	protected function leaderboard() : string
	{
		/* Figure out dates */
		$dates = array();
		$timezone = new DateTimeZone( Settings::i()->reputation_timezone );
		$endDate = DateTime::ts( time() )->setTimezone( $timezone );

		$firstRepDate = intval( Db::i()->select( 'MIN(rep_date)', 'core_reputation_index' )->first() );
		$firstIndexDate = intval( Index::i()->firstIndexDate() );
		$appWhere = null;
		$descApp = Member::loggedIn()->language()->addToStack( 'leaderboard_in_all_apps' );

		$dates[ 'oldest' ] = DateTime::ts( ( $firstRepDate > $firstIndexDate ) ? $firstRepDate : $firstIndexDate );
		$oldestStamp = $dates[ 'oldest' ]->getTimeStamp();
		$date = $dates[ 'oldest' ];

		$aYearAgo = DateTime::create()->setTimezone( $timezone )->sub( new DateInterval( 'P1Y' ) );
		$month = DateTime::create()->setTimezone( $timezone )->sub( new DateInterval( 'P1M' ) )->setTime( 0, 0 );
		$week = DateTime::create()->setTimezone( $timezone )->sub( new DateInterval( 'P7D' ) )->setTime( 0, 0 );
		$today = DateTime::create()->setTimezone( $timezone )->setTime( 0, 0 );

		if ( $aYearAgo->getTimeStamp() > $oldestStamp )
		{
			$dates[ 'year' ] = $aYearAgo;
		}

		if ( $month->getTimeStamp() > $oldestStamp )
		{
			$dates[ 'month' ] = $month;
		}

		if ( $week->getTimeStamp() > $oldestStamp )
		{
			$dates[ 'week' ] = $week;
		}

		if ( $today->getTimeStamp() > $oldestStamp )
		{
			$dates[ 'today' ] = $today;
		}

		/* Got a date? */
		if ( isset( Request::i()->time ) and isset( $dates[ Request::i()->time ] ) )
		{
			$date = $dates[ Request::i()->time ];
		}
		else if ( isset( $dates[ 'month' ] ) )
		{
			/* Set the default to month */
			Request::i()->time = 'month';
			$date = $dates[ 'month' ];
		}

		/* Applications */
		$classes = array();
		foreach ( Application::allExtensions( 'core', 'ContentRouter' ) as $object )
		{
			$classes = array_merge( $object->classes, $classes );
		}

		$areas = array();
		foreach ( $classes as $item )
		{
			$commentClass = NULL;
			$reviewClass = NULL;

			if ( IPS::classUsesTrait( $item, 'IPS\Content\Reactable' ) )
			{
				$areas[ $item::$application . '-' . $item::reactionType() ] = array( $item, Member::loggedIn()->language()->addToStack( "{$item::$title}_pl" ) );
			}

			if ( $item::supportsComments( Member::loggedIn() ) and $commentClass = $item::$commentClass and IPS::classUsesTrait( $commentClass, 'IPS\Content\Reactable' )  )
			{
				$supportsComments = IPS::classUsesTrait( $commentClass, 'IPS\Content\Reactable' ) and $item::supportsComments( Member::loggedIn() );
				if ( $supportsComments )
				{
					$areas[ $item::$application . '-' . $commentClass::reactionType() ] = array( $commentClass, Member::loggedIn()->language()->addToStack( "{$commentClass::$title}_pl" ) );
				}
			}

			if ( $item::supportsReviews( Member::loggedIn() ) and $reviewClass = $item::$reviewClass and IPS::classUsesTrait( $reviewClass, 'IPS\Content\Reactable' )  )
			{
				$areas[ $item::$application . '-' . $reviewClass::reactionType() ] = array( $reviewClass, Member::loggedIn()->language()->addToStack( "{$reviewClass::$title}_pl" ) );
			}
		}

		$form = new Form( 'popular_date', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--leaderboard';
		$customStart = ( isset( Request::i()->custom_date_start ) and is_numeric( Request::i()->custom_date_start ) ) ? (int) Request::i()->custom_date_start : NULL;
		$customEnd = ( isset( Request::i()->custom_date_end ) and is_numeric( Request::i()->custom_date_end ) ) ? (int) Request::i()->custom_date_end : NULL;

		$form->add( new DateRange( 'custom_date', array( 'start' => $customStart, 'end' => $customEnd ), FALSE, array( 'start' => array( 'min' => $dates[ 'oldest' ], 'time' => false ) ) ) );

		if ( $values = $form->values() )
		{
			$url = Request::i()->url()->stripQueryString( 'time' );

			if ( isset( $values[ 'custom_date' ][ 'start' ] ) and $values[ 'custom_date' ][ 'start' ] instanceof DateTime )
			{
				$url = $url->setQueryString( 'custom_date_start', $values[ 'custom_date' ][ 'start' ]->getTimeStamp() );
			}

			if ( isset( $values[ 'custom_date' ][ 'end' ] ) and $values[ 'custom_date' ][ 'end' ] instanceof DateTime )
			{
				$url = $url->setQueryString( 'custom_date_end', $values[ 'custom_date' ][ 'end' ]->getTimeStamp() );
			}

			Output::i()->redirect( $url );
		}
		else
		{
			if ( $customStart )
			{
				$date = DateTime::ts( $customStart )->setTimezone( $timezone )->setTime( 0, 0, 1 );
			}

			if ( $customEnd )
			{
				$endDate = DateTime::ts( $customEnd )->setTimezone( $timezone )->setTime( 23, 59, 59 );
			}
		}

		/* Do we want results for a specific app */
		$customApp = FALSE;

		if ( isset( Request::i()->in ) and isset( $areas[ Request::i()->in ] ) )
		{
			$appWhere = " AND rep_class='" . Db::i()->escape_string( $areas[ Request::i()->in ][ 0 ] ) . "'";
			$descApp = Member::loggedIn()->language()->addToStack( 'leaderboard_in_app', FALSE, array( 'sprintf' => array( $areas[ Request::i()->in ][ 1 ] ) ) );
			$customApp = TRUE;
		}
		else
		{
			$repAreas =  array();
			foreach( $areas as $area )
			{
				$repAreas[] = $area[0] ;
			}

			$appWhere = " AND " .  Db::i()->in( 'rep_class', $repAreas );
		}
		
		$storeKey = NULL;
		$hashes   = NULL;
		if ( ! $customStart and ! $customEnd AND ! $customApp )
		{
			$storeKey = 'leaderHashes_' . Request::i()->time . '-' . md5( implode( ',', Member::loggedIn()->groups ) );
		
			if ( isset( Store::i()->$storeKey ) )
			{
				$stored = Store::i()->$storeKey;
				
				if ( isset( $stored['hashes'] ) and isset( $stored['time'] ) and $stored['time'] > ( time() - 900 ) )
				{
					$hashes = $stored['hashes'];
				}
			}
		}

		/* Get hashes and total rep */
		if ( $hashes === NULL )
		{
			/* Prevent race condition */
			if ( $storeKey )
			{
				Store::i()->$storeKey = array( 'time' => time(), 'hashes' => array() );
			}
			
			/* Get rep hashes */
			$inner = Db::i()->select( 'class_type_id_hash, SUM(rep_rating) as total_rep', array( 'core_reputation_index', 'x' ),  array( 'rep_date BETWEEN ' . $date->getTimeStamp() . ' AND ' . $endDate->getTimeStamp() . $appWhere ), NULL, NULL, 'class_type_id_hash' );
			$repHashes = iterator_to_array( Db::i()->select( 'class_type_id_hash, total_rep', $inner, NULL, 'x.total_rep desc', array( 0, 500 ) )->setKeyField('class_type_id_hash') );
				
			/* Now filter through permissions */
			$searchHashes = Index::i()->hashesWithPermission( array_keys( $repHashes ), Member::loggedIn(), 500 );
			
			/* Filter out rep hashes not in search results */
			$hashes = array_slice( array_intersect_key( $repHashes, $searchHashes ), 0, 50 );
			
			if ( $storeKey )
			{
				Store::i()->$storeKey = array( 'time' => time(), 'hashes' => $hashes );
			}
		}
		
		$classes = array();
		$repData = array();
		$or   = array();
		$results = array();
		$preLoadMembers = array();
				
		if ( count( $hashes ) )
		{
			/* Now get the reputation data */
			foreach( Db::i()->select( '*', 'core_reputation_index', array( Db::i()->in( 'class_type_id_hash', array_keys( $hashes ) ) ) ) as $data )
			{
				$data['total_rep'] = $hashes[ $data['class_type_id_hash'] ]['total_rep'];
				$repData[ $data['rep_class'] . '-' . $data['type_id'] ] = $data;
				$classes[ $data['rep_class'] ][] = $data['type_id'];
			}
			
			foreach( $classes as $class => $ids )
			{
				$or[] = ContentFilter::initWithSpecificClass( $class )->onlyInIds( $ids );
			}
			
			/* Query and manually sort */			
			$sorted = array();
			$search = Query::init();
			
			/* Set the result to get as 50, as we pass in 50 unique hashes and the default is only 25, which means some may be missed as they are not sorted by rep count until after fetching */
			$search->resultsToGet = 50;
			
			$array = $search->filterByContent( $or )->search()->getArrayCopy();
			
			foreach( $array as $index => $data )
			{
				if ( isset( $repData[ $data['index_class'] . '-' . $data['index_object_id'] ] ) )
				{
					$data['rep_data'] = $repData[ $data['index_class'] . '-' . $data['index_object_id'] ];
					$sorted[ $data['rep_data']['total_rep'] . '.' . $data['index_date_updated'] . '.'. $index ] = $data;
				}
			}
			unset( $array );
			krsort( $sorted, SORT_NUMERIC );
			
			$results = new Results( $sorted, count( $sorted ) );
			
			/* Load data we need like the authors, etc */
			$results->init();
		}
		
		/* Get top rated contributors */
		$topContributors = array();

		$innerQueryWhere = array();
		$innerQueryWhere[] = array( 'member_received>0 ' . $appWhere . ' and rep_date BETWEEN ' . $date->getTimeStamp() . ' AND ' . $endDate->getTimeStamp() );

		if( Settings::i()->leaderboard_excluded_groups )
		{
			$innerQueryWhere[] = Db::i()->in( 'member_group_id', explode( ',', Settings::i()->leaderboard_excluded_groups ), TRUE );

			$innerQuery = Db::i()->select( 'core_reputation_index.member_received as themember, SUM(rep_rating) as rep', 'core_reputation_index', $innerQueryWhere, NULL, NULL, 'themember' )->join( 'core_members', array( 'core_reputation_index.member_received = core_members.member_id' ) );
		}
		else
		{
			$innerQuery = Db::i()->select( 'core_reputation_index.member_received as themember, SUM(rep_rating) as rep', 'core_reputation_index', $innerQueryWhere, NULL, NULL, 'themember' );
		}

		foreach( Db::i()->select( 'themember, rep', array( $innerQuery, 'in' ), NULL, 'rep DESC', 4 )->setKeyField('themember')->setValueField('rep') as $member => $rep )
		{
			$topContributors[ $member ] = $rep;
		}
		
		if ( count( $topContributors ) )
		{
			$preLoadMembers = array_merge( $preLoadMembers, array_keys( $topContributors ) );
		}
		
		/* Load their data */
		if ( count( $preLoadMembers ) )
		{
			foreach ( Db::i()->select( '*', 'core_members', Db::i()->in( 'member_id', array_unique( $preLoadMembers ) ) ) as $member )
			{
				Member::constructFromData( $member );
			}
		}
		
		/* Work out the description for popular content */
		if ( Reaction::isLikeMode() )
		{
			$popularResultsSingle = 'popular_results_single_desc';
			$popularResultsMany = 'popular_results_desc';
		}
		else
		{
			$popularResultsSingle = 'popular_results_single_desc_rep';
			$popularResultsMany = 'popular_results_desc_rep';
		}
		
		$description = Member::loggedIn()->language()->addToStack( ( $date->localeDate() == $endDate->localeDate() ) ? $popularResultsSingle : $popularResultsMany, NULL, array( 'sprintf' => array( $date->localeDate(), $descApp ) ) );
		
		/* Are our offsets different? */
		$tzOffsetDifference = NULL;
		try
		{
			if ( DateTime::ts( time() )->getOffset() != DateTime::ts( time() )->setTimezone( $timezone )->getOffset() )
			{
				$tzOffsetDifference = DateTime::ts( time() )->setTimezone( $timezone )->format('P');
			}
		}
		catch( Exception $ex ) { }

		Output::i()->linkTags['canonical'] = (string) Url::internal( "app=core&module=discover&controller=popular&tab=leaderboard", 'front', 'leaderboard_leaderboard' );

		/* If there are no results tell search engines not to index the page */
		if( !count( $results ) )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		return Theme::i()->getTemplate('popular')->popularWrapper( $results, $areas, $topContributors, $dates, $description, $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) ), $tzOffsetDifference );
	}

    protected function trending(): string
    {
        return Bridge::i()->popularTrending();
    }
}