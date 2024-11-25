<?php
/**
 * @brief		Search
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Apr 2014
 */

namespace IPS\core\modules\front\search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DateTimeZone;
use IPS\Application;
use IPS\Application\Module;
use IPS\Content;
use IPS\Content\Search\ContentFilter;
use IPS\Content\Search\Index;
use IPS\Content\Search\Query;
use IPS\core\DataLayer;
use IPS\core\Facebook\Pixel;
use IPS\core\ProfileFields\Field;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use const IPS\UPGRADE_MANUAL_THRESHOLD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search
 */
class search extends Controller
{
	/**
	 * @brief	Amount of time to cache search result in browser/CDN
	 */
	protected int $_cacheTimeout = 30 * 60;

	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 * @example array(
		'community_area' => array( 'value' => 'search', 'odkUpdate' => 'true' )
	   )
	 */
	public static array $dataLayerContext = array(
		'community_area' => array( 'value' => 'search', 'odkUpdate' => 'true' )
	);

	/**
	 * Check if this was a cached result we should just return a 304 against
	 *
	 * @return	void
	 */
	protected function _checkCached() : void
	{
		/* Check whether a 304 Not modified response is appropriate */
		if( !empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) AND ( new DateTime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )->getTimestamp() > ( time() - $this->_cacheTimeout ) )
		{
			header('HTTP/1.1 304 Not Modified' );
			exit;
		}
	}

	/**
	 * Search Form
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Make sure this isn't a cached result we should just honor */
		$this->_checkCached();

		/* Init stuff for the output */
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/streams.css' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/search.css' ) );

		/* Add any global CSS from other apps */
		foreach( Application::applications() as $app )
		{
			$app::outputCss();
		}

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_search.js', 'core' ) );
		Output::i()->metaTags['robots'] = 'noindex'; // Tell search engines not to index search pages

		if( !Settings::i()->tags_enabled and isset( Request::i()->tags ) )
		{
			Output::i()->error( 'page_doesnt_exist', '2C205/4', 404, '' );
		}

		/* Get the form */
		$baseUrl = Url::internal( 'app=core&module=search&controller=search', 'front', 'search' );
		$form = $this->_form();

		/* If we have the term, show the results */
		if ( ( Request::i()->isAjax() and ! isset( Request::i()->_nodeSelectName ) ) or isset( Request::i()->q ) or isset( Request::i()->tags ) or ( Request::i()->type == 'core_members' and Member::loggedIn()->canAccessModule( Module::get( 'core', 'members', 'front' ) ) ) )
		{
			$showForm = ( Request::i()->type == 'core_members' ) ? FALSE : ( !Request::i()->isAjax() and !Request::i()->q and !Request::i()->tags );

			if( Request::i()->type == 'core_members' AND !Member::loggedIn()->canAccessModule( Module::get( 'core', 'members', 'front' ) ) )
			{
				$showForm = TRUE;
			}

			if ( $showForm )
			{
				if ( isset( Request::i()->csrfKey ) )
				{
					$form->error = Member::loggedIn()->language()->addToStack('no_search_term');
					$form->hiddenValues['__advanced'] = true;

					Output::i()->title = Member::loggedIn()->language()->addToStack( 'advanced_search' );
					Output::i()->output = Theme::i()->getTemplate( 'search' )->search( $this->_splitTermsForDisplay(), FALSE, FALSE, FALSE, $baseUrl, FALSE, $form->customTemplate( array( Theme::i()->getTemplate( 'search' ), 'filters' ), $baseUrl, NULL ), 0, TRUE );
				}
				else
				{
					Output::i()->redirect( Url::internal( 'app=core&module=search&controller=search', 'front', 'search' ) );
				}
				return;
			}

			/* Data Layer Event */
			if ( Settings::i()->core_datalayer_enabled AND !Request::i()->isAjax() AND Request::i()->q )
			{
				DataLayer::i()->addEvent( 'search', array( 'query' => Request::i()->q, ) );
			}
			
			$this->_results();
		}
		/* Otherwise, show the advanced search form */
		else
		{
			$form->hiddenValues['__advanced'] = true;			

			Output::i()->title = Member::loggedIn()->language()->addToStack( 'advanced_search' );
			Output::i()->output = Theme::i()->getTemplate( 'search' )->search( $this->_splitTermsForDisplay(), FALSE, FALSE, FALSE, $baseUrl, FALSE, $form->customTemplate( array( Theme::i()->getTemplate( 'search' ), 'filters' ), $baseUrl, NULL ), 0, TRUE );
		}
	}

	/**
	 * @brief	Number of member search results to retrieve
	 */
	protected int $memberSearchResults	= 24;
	
	/**
	 * Get Results
	 *
	 * @return	void
	 */
	protected function _results() : void
	{
		/* Make sure we are not doing anything nefarious like passing an array as the "q" parameter, which generates errors */
		foreach( array( 'q', 'type' ) AS $parameter )
		{
			if ( isset( Request::i()->$parameter ) AND is_array( Request::i()->$parameter ) )
			{
				Request::i()->$parameter = NULL;
			}
		}
		
		/* Init */
		$baseUrl = Url::internal( 'app=core&module=search&controller=search', 'front', 'search' );
		if( Request::i()->q )
		{ 
			$baseUrl = $baseUrl->setQueryString( 'q', Request::i()->q );
		} 
		if ( Request::i()->tags )
		{ 
			$baseUrl = $baseUrl->setQueryString( 'tags', Request::i()->tags );
		}
		if (isset( Request::i()->eitherTermsOrTags))
		{ 
			$baseUrl = $baseUrl->setQueryString( 'eitherTermsOrTags', Request::i()->eitherTermsOrTags );
		}
		
		if( isset( Request::i()->quick ) )
		{ 
			$baseUrl = $baseUrl->setQueryString( 'quick', Request::i()->quick );
		}
		
		$types = static::contentTypes();

		/* Flood control */
		Request::floodCheck();

		/* Are we searching members? */
		if ( isset( Request::i()->type ) and Request::i()->type === 'core_members' and Member::loggedIn()->canAccessModule( Module::get( 'core', 'members', 'front' ) ) )
		{
			$baseUrl = $baseUrl->setQueryString( 'type', Request::i()->type );
			if ( Request::i()->q )
			{
				$where = array( array( 'LOWER(core_members.name) LIKE ?', '%' . mb_strtolower( trim( Request::i()->q ) ) . '%' ) );
			}
			else
			{
				$where = array( array( 'core_members.completed=?', true ) );
			}
			
			if ( isset( Request::i()->joinedDate ) and !isset( Request::i()->start_after ) )
			{
				$baseUrl = $baseUrl->setQueryString( 'joinedDate', Request::i()->joinedDate );
				
				Request::i()->start_after = Request::i()->joinedDate;
			}

			if ( ( isset( Request::i()->start_before ) ) or ( isset( Request::i()->start_after ) ) )
			{
				foreach ( array( 'before', 'after' ) as $l )
				{
					$$l = NULL;
					$key = "start_{$l}";
					if ( isset( Request::i()->$key ) AND Request::i()->$key != 'any' )
					{
						switch ( Request::i()->$key )
						{
							case 'day':
								$$l = DateTime::create()->sub( new DateInterval( 'P1D' ) );
								break;
								
							case 'week':
								$$l = DateTime::create()->sub( new DateInterval( 'P1W' ) );
								break;
								
							case 'month':
								$$l = DateTime::create()->sub( new DateInterval( 'P1M' ) );
								break;
								
							case 'six_months':
								$$l = DateTime::create()->sub( new DateInterval( 'P6M' ) );
								break;
								
							case 'year':
								$$l = DateTime::create()->sub( new DateInterval( 'P1Y' ) );
								break;
								
							default:
								$$l = DateTime::ts( Request::i()->$key );
								break;
						}
					}
				}
				
				if ( $before )
				{
					$where[] = array( 'core_members.joined<?', $before->getTimestamp() );
				}
				if ( $after )
				{
					$where[] = array( 'core_members.joined>?', $after->getTimestamp() );
				}
			}

			if ( isset( Request::i()->group ) )
			{
				/* Only exclude by group if the only value isn't __EMPTY **/
				if( !is_array( Request::i()->group ) OR ( count( Request::i()->group ) > 1 OR !isset( Request::i()->group['__EMPTY'] ) ) )
				{
					$groups = ( is_array( Request::i()->group ) ) ? array_filter( array_keys( Request::i()->group ), function( $val ){
						if( $val == '__EMPTY' )
						{
							return false;
						}

						return true;
					} ) : explode( ',', Request::i()->group );

					foreach( $groups as $_idx => $groupId )
					{
						try
						{
							if( Group::load( $groupId )->g_bitoptions['gbw_hide_group'] )
							{
								unset( $groups[ $_idx ] );
							}
						}
						catch( OutOfRangeException $e )
						{
							/* Group didn't exist, so let's ignore it */
							unset( $groups[ $_idx ] );
						}
					}
					$baseUrl = $baseUrl->setQueryString( 'group', Request::i()->group );
					$where[] = Db::i()->in( 'core_members.member_group_id', $groups );
				}
			}
			else
			{
				$exclude = array();
				/* Groups not specified but we still want to omit non searchable groups */
				foreach( Group::groups() as $group )
				{
					if ( $group->g_bitoptions['gbw_hide_group'] )
					{
						$exclude[] = $group->g_id;
					}
				}
				
				if ( count( $exclude ) )
				{
					$where[] = Db::i()->in( 'core_members.member_group_id', $exclude, TRUE );
				}
			}

			/* Figure out member custom field filters */
			foreach ( Field::fields( array(), Field::SEARCH ) as $group => $fields )
			{
				/* Fields */
				foreach ( $fields as $id => $field )
				{
					/* Work out the object type so we can show the appropriate field */
					$type = get_class( $field );

					switch ( $type )
					{
						case 'IPS\Helpers\Form\Text':
						case 'IPS\Helpers\Form\Tel':
						case 'IPS\Helpers\Form\Editor':
						case 'IPS\Helpers\Form\Email':
						case 'IPS\Helpers\Form\TextArea':
						case 'IPS\Helpers\Form\Url':
						case 'IPS\Helpers\Form\Number':
							$fieldName	= 'core_pfield_' . $id;

							if( isset( Request::i()->$fieldName ) )
							{
								if( Store::i()->profileFields['fields'][$group][$id]['pf_search_type'] == 'loose' )
								{
									$where[] = array( 'LOWER(core_pfields_content.field_' . $id . ') LIKE ?', '%' . mb_strtolower( Request::i()->$fieldName ) . '%' );
								}
								else
								{
									$where[] = array( 'LOWER(core_pfields_content.field_' . $id . ') = ?', mb_strtolower( Request::i()->$fieldName ) );
								}
								$baseUrl = $baseUrl->setQueryString( $fieldName, Request::i()->$fieldName );
							}
							break;
						case 'IPS\Helpers\Form\Date':
							$fieldName	= 'core_pfield_' . $id;
							if ( is_array( Request::i()->$fieldName ) AND Request::i()->$fieldName['start'] )
							{
								$start = new DateTime( Request::i()->$fieldName['start'] );

								$where[] = "core_pfields_content.field_{$id}>" . $start->getTimestamp();
							}
							if ( is_array( Request::i()->$fieldName ) AND Request::i()->$fieldName['end'] )
							{
								$end = new DateTime( Request::i()->$fieldName['end'] );

								$where[] = "core_pfields_content.field_{$id}<" . $end->getTimestamp();
							}
							break;
						case 'IPS\Helpers\Form\Select':
						case 'IPS\Helpers\Form\CheckboxSet':
						case 'IPS\Helpers\Form\Radio':
							$fieldName	= 'core_pfield_' . $id;

							if( isset( Request::i()->$fieldName ) )
							{
								if ( ( $type === 'IPS\Helpers\Form\CheckboxSet' ) or ( isset( $field->options['multiple'] ) AND $field->options['multiple'] == 1 ) )
								{
									$where[] = Db::i()->findInSet( 'core_pfields_content.field_' . $id ,array( mb_strtolower( Request::i()->$fieldName ) ) );
								}
								else
								{
									$where[] = array( 'core_pfields_content.field_' . $id . ' = ?', mb_strtolower( Request::i()->$fieldName ) );
								}

								$baseUrl = $baseUrl->setQueryString( $fieldName, Request::i()->$fieldName );
							}
							break;
					}
				}
			}

			if( isset( Request::i()->sortby ) AND in_array( mb_strtolower( Request::i()->sortby ), array( 'joined', 'name', 'member_posts', 'pp_reputation_points' ) ) )
			{
				$direction	= ( isset( Request::i()->sortdirection ) AND in_array( mb_strtolower( Request::i()->sortdirection ), array( 'asc', 'desc' ) ) ) ? Request::i()->sortdirection : 'asc';
				$order		= mb_strtolower( Request::i()->sortby ) . ' ' . $direction;

				$baseUrl = $baseUrl->setQueryString( array( 'sortby' => Request::i()->sortby, 'sortdirection' => Request::i()->sortdirection ) );
			}
			else
			{
				/* If we have a search query, we order by INSTR(name, q) ASC, LENGTH(name) ASC, name ASC so as to show "xyz" before "abcxyz" when searching for "xyz" - INSTR() will pull results
					starting with the search string first, then we order by length to match xyz before xyza, then finally we sort by the name itself */
				if( Request::i()->q )
				{
					$order = "INSTR( name, '" . Db::i()->escape_string( Request::i()->q ) . "' ) ASC, LENGTH( name ) ASC, name ASC";
				}
				else
				{
					$order = "name ASC";
				}
			}
			
			$page	= isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;

			if( $page < 1 )
			{
				$page = 1;
			}
			
			$where[] = array( "( core_validating.vid IS NULL ) " );
			
			$select	= Db::i()->select( 'COUNT(*)', 'core_members', $where );
			$select->join( 'core_pfields_content', 'core_pfields_content.member_id=core_members.member_id' );
			$select->join( 'core_validating', 'core_validating.member_id=core_members.member_id AND core_validating.new_reg = 1' );
			$count = $select->first();

			$select	= Db::i()->select( 'core_members.*', 'core_members', $where, $order, array( ( $page - 1 ) * $this->memberSearchResults, $this->memberSearchResults ) );
			$select->join( 'core_pfields_content', 'core_pfields_content.member_id=core_members.member_id' );
			$select->join( 'core_validating', 'core_validating.member_id=core_members.member_id AND core_validating.new_reg = 1' );
			
			$results	= new ActiveRecordIterator( $select, 'IPS\Member' );

			$pagination = trim( Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, ceil( $count / $this->memberSearchResults ), $page, $this->memberSearchResults, TRUE, 'page', $count > UPGRADE_MANUAL_THRESHOLD ) );
			if ( !Request::i()->q )
			{
				$title = Member::loggedIn()->language()->addToStack( 'members' );
			}
			else
			{
				$title = Member::loggedIn()->language()->addToStack( 'search_results_title_term_area', FALSE, array( 'sprintf' => array( Request::i()->q, Member::loggedIn()->language()->addToStack( 'core_members_pl' ) ) ) );
			}

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( array(
					'filters'	=> $this->_form()->customTemplate( array( Theme::i()->getTemplate( 'search' ), 'filters' ), $baseUrl, $count ),
					'content'	=> Theme::i()->getTemplate( 'search' )->results( $this->_splitTermsForDisplay(), $title, $results, $pagination, $baseUrl, $count ),
					'title'		=> $title,
					'css'		=> array()
				) );
			}
			else
			{
				Output::i()->title = $title;
				Output::i()->output = Theme::i()->getTemplate( 'search' )->search( $this->_splitTermsForDisplay(), $title, $results, $pagination, $baseUrl, $types, $this->_form()->customTemplate( array( Theme::i()->getTemplate( 'search' ), 'filters' ), $baseUrl, $count ), $count );
			}
			return;
		}
		
		/* Init */
		$query = Query::init();
		$titleConditions = array();
		$titleType = 'search_blurb_all_content';
				
		/* Set content type */
		if ( isset( Request::i()->type ) and array_key_exists( Request::i()->type, $types ) )
		{	
			$class = $types[ Request::i()->type ];

			if ( isset( Request::i()->item ) )
			{
				try
				{
					$item = $class::loadAndCheckPerms( (int) Request::i()->item );
					$query->filterByContent( array( ContentFilter::init( $class )->onlyInItems( array( Request::i()->item ) ) ) );
					$baseUrl = $baseUrl->setQueryString( 'item', intval( Request::i()->item ) );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_in', FALSE, array( 'sprintf' => array( $item->mapped('title') ) ) );
					$baseUrl = $baseUrl->setQueryString( 'type', Request::i()->type );
				}
				catch ( OutOfRangeException $e ) { }
			}
			else
			{
				$filter = ContentFilter::init( $types[ Request::i()->type ] );
				$baseUrl = $baseUrl->setQueryString( 'type', Request::i()->type );
				
				if ( isset( Request::i()->nodes ) and isset( $types[ Request::i()->type ]::$containerNodeClass ) )
				{
					/* It's ok to just pass the (potential) parent IDs into the URL */
					$baseUrl = $baseUrl->setQueryString( 'nodes', Request::i()->nodes );
					
					$nodeIds = explode( ',', Request::i()->nodes );
					$nodes = array();
					$nodeClass = $types[ Request::i()->type ]::$containerNodeClass;
					foreach ( $nodeIds as $id )
					{
						try
						{
							$thisNode = $nodeClass::loadAndCheckPerms( $id );
							$nodes[] = $thisNode->_title;
							
							if ( isset( Request::i()->quick ) AND $thisNode->childrenCount() )
							{
								foreach( $thisNode->children() as $child )
								{
									/* Avoid going too deep. Stop laughing at the back */
									if ( count( $nodeIds ) < 200 )
									{
										$nodeIds[] = $child->_id;
									}
								}
							}
						}
						catch ( OutOfRangeException $e ) { }
					}
					
					if ( !$nodes )
					{
						Output::i()->error( 'search_invalid_nodes', '2C205/5', 404, '' );
					}
					
					$filter->onlyInContainers( $nodeIds );
					
					$nodes = Member::loggedIn()->language()->formatList( $nodes );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_in', FALSE, array( 'sprintf' => array( $nodes ) ) );
				}
				else
				{
					$titleType = $types[ Request::i()->type ]::$title . '_pl_lc';
				}
				
				if ( isset( Request::i()->search_min_comments ) )
				{
					$filter->minimumComments( (int) Request::i()->search_min_comments );
					$baseUrl = $baseUrl->setQueryString( 'search_min_comments', (int) Request::i()->search_min_comments );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_min_comments', FALSE, array( 'sprintf' => array( (int) Request::i()->search_min_comments ) ) );
				}
				if ( isset( Request::i()->search_min_replies ) AND isset( $class::$commentClass ) )
				{
					$filter->minimumComments( (int) Request::i()->search_min_replies + 1 );
					$baseUrl = $baseUrl->setQueryString( 'search_min_replies', (int) Request::i()->search_min_replies );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_min_replies', FALSE, array( 'sprintf' => array( (int) Request::i()->search_min_replies ) ) );
				}
				if ( isset( Request::i()->search_min_reviews ) AND isset( $class::$reviewClass ) )
				{
					$filter->minimumReviews( (int) Request::i()->search_min_reviews );
					$baseUrl = $baseUrl->setQueryString( 'search_min_reviews', (int) Request::i()->search_min_reviews );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_min_reviews', FALSE, array( 'sprintf' => array( (int) Request::i()->search_min_reviews ) ) );
				}
				if ( isset( Request::i()->search_min_views ) AND isset( $class::$databaseColumnMap['views'] ) AND Index::i()->supportViewFiltering() )
				{
					$filter->minimumViews( (int) Request::i()->search_min_views );
					$baseUrl = $baseUrl->setQueryString( 'search_min_views', (int) Request::i()->search_min_views );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_min_views', FALSE, array( 'sprintf' => array( (int) Request::i()->search_min_views ) ) );
				}
				
				$query->filterByContent( array( $filter ) );
				
			}
		}
		
		/* Filter by author */
		if ( isset( Request::i()->author ) )
		{
			$author = Member::load( html_entity_decode( Request::i()->author, ENT_QUOTES, 'UTF-8' ), 'name' );
			if ( $author->member_id )
			{
				$query->filterByAuthor( $author );
				$baseUrl = $baseUrl->setQueryString( 'author', $author->name );
				$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_author', FALSE, array( 'sprintf' => array( $author->name ) ) );
			}
		}
		
		/* Filter by club */
		if ( isset( Request::i()->club ) AND Settings::i()->clubs )
		{
			$clubIds = array();
			$clubNames = array();
			
			foreach ( explode( ',', Request::i()->club ) as $clubId )
			{
				try
				{
					$club = Club::load( ltrim( $clubId, 'c' ) );
					if ( $club->canRead() )
					{
						$clubIds[] = $club->id;
						$clubNames[] = $club->name;
					}
				}
				catch ( OutOfRangeException $e ) { }
			}
			
			if ( count( $clubIds ) )
			{
				$query->filterByClub( $clubIds );
				$baseUrl = $baseUrl->setQueryString( 'club', Request::i()->club );
				$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_club', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $clubNames, Member::loggedIn()->language()->get('or_list_format') ) ) ) );
			}
		}
		
		/* Set time cutoffs */
		foreach ( array( 'start' => 'filterByCreateDate', 'updated' => 'filterByLastUpdatedDate' ) as $k => $method )
		{
			$beforeKey = "{$k}_before";
			$afterKey = "{$k}_after";
			
			if ( isset( Request::i()->$beforeKey ) )
			{
				$baseUrl = $baseUrl->setQueryString( $beforeKey, Request::i()->$beforeKey );
			}

			if ( isset( Request::i()->$afterKey ) )
			{
				$baseUrl = $baseUrl->setQueryString( $afterKey, Request::i()->$afterKey );
			}
			
			if ( isset( Request::i()->$beforeKey ) and Request::i()->$beforeKey != 'any' and isset( Request::i()->$afterKey ) and Request::i()->$afterKey != 'any' )
			{
				/* Javascript takes a date and turns it into a timestamp but does not apply any timezone offsetting, so we don't want to do so here or else the date we create may not be the same one the user chose. */
				$after	= DateTime::ts( Request::i()->$afterKey, TRUE );
				$before	= DateTime::ts( Request::i()->$beforeKey, TRUE );
				$titleConditions[] = Member::loggedIn()->language()->addToStack( "search_blurb_date_$k", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( "search_blurb_date_between", FALSE, array( 'sprintf' => array( $after->localeDate(), $before->localeDate() ) ) ) ) ) );

				$query->$method( $after, $before );
			}
			elseif ( isset( Request::i()->$beforeKey ) or isset( Request::i()->$afterKey ) )
			{
				foreach ( array( 'after', 'before' ) as $l )
				{
					$$l = NULL;
					$key = "{$l}Key";
					if ( isset( Request::i()->$$key ) AND Request::i()->$$key != 'any' )
					{
						if ( Index::i()->supportViewFiltering() )
						{
							$baseUrl = $baseUrl->setQueryString( 'search_min_views', Request::i()->search_min_views );
						}
						
						switch ( Request::i()->$$key )
						{
							case 'day':
								$$l = DateTime::create()->sub( new DateInterval( 'P1D' ) );
								$dateCondition = Member::loggedIn()->language()->addToStack( "search_blurb_date_rel_{$l}", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'search_blurb_date_rel_day' ) ) ) );
								break;
								
							case 'week':
								$$l = DateTime::create()->sub( new DateInterval( 'P1W' ) );
								$dateCondition = Member::loggedIn()->language()->addToStack( "search_blurb_date_rel_{$l}", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'search_blurb_date_rel_week' ) ) ) );
								break;
								
							case 'month':
								$$l = DateTime::create()->sub( new DateInterval( 'P1M' ) );
								$dateCondition = Member::loggedIn()->language()->addToStack( "search_blurb_date_rel_{$l}", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'search_blurb_date_rel_month' ) ) ) );
								break;
								
							case 'six_months':
								$$l = DateTime::create()->sub( new DateInterval( 'P6M' ) );
								$dateCondition = Member::loggedIn()->language()->addToStack( "search_blurb_date_rel_{$l}", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'search_blurb_date_rel_6months' ) ) ) );
								break;
								
							case 'year':
								$$l = DateTime::create()->sub( new DateInterval( 'P1Y' ) );
								$dateCondition = Member::loggedIn()->language()->addToStack( "search_blurb_date_rel_{$l}", FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'search_blurb_date_rel_year' ) ) ) );
								break;
								
							default:
								$$l = DateTime::ts( Request::i()->$$key );
								$dateCondition = Member::loggedIn()->language()->addToStack( "search_blurb_date_{$l}", FALSE, array( 'sprintf' => array( $$l->localeDate() ) ) );
								break;
						}
						
						$titleConditions[] = Member::loggedIn()->language()->addToStack( "search_blurb_date_{$k}", FALSE, array( 'sprintf' => array( $dateCondition ) ) );
					}
				}
				
				$query->$method( $after, $before );
			}
		}
		
		/* Work out the title */
		if ( Request::i()->tags )
		{
			/* @todo Remove when we fix \Http\Url as there are issues with urlencode/decoding */
			if ( ! Settings::i()->htaccess_mod_rewrite )
			{
				Request::i()->tags = urldecode( Request::i()->tags );
			}
			
			$tagList = array_map( function( $val )
			{
				return '\'' . htmlentities( $val, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) . '\'';
			}, explode( ',', Request::i()->tags ) );
		}
		$title = '';
		if ( Request::i()->q )
		{
			Pixel::i()->Search = array(
				'search_string' => Request::i()->q
			);
			
			if ( Request::i()->tags )
			{
				if ( isset( Request::i()->eitherTermsOrTags ) and Request::i()->eitherTermsOrTags === 'and' )
				{
					$title = Member::loggedIn()->language()->addToStack( 'search_blurb_term', FALSE, array( 'sprintf' => array( urldecode( Request::i()->q ) ) ) );
					$titleConditions[] = Member::loggedIn()->language()->addToStack( 'search_blurb_tag_condition', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $tagList, Member::loggedIn()->language()->get('or_list_format') ) ) ) );
				}
				else
				{
					$title = Member::loggedIn()->language()->addToStack( 'search_blurb_term_or_tag', FALSE, array( 'sprintf' => array( urldecode( Request::i()->q ), Member::loggedIn()->language()->formatList( $tagList, Member::loggedIn()->language()->get('or_list_format') ) ) ) );
				}
			}
			else
			{
				$title = Member::loggedIn()->language()->addToStack( 'search_blurb_term', FALSE, array( 'sprintf' => array( urldecode( Request::i()->q ) ) ) );
			}
		}
		elseif ( Request::i()->tags )
		{
			$title = Member::loggedIn()->language()->addToStack( 'search_blurb_tag', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->formatList( $tagList, Member::loggedIn()->language()->get('or_list_format') ) ) ) );
		}
		if ( count( $titleConditions ) )
		{
			$title = Member::loggedIn()->language()->addToStack( 'search_blurb_conditions', FALSE, array( 'sprintf' => array( $title, Member::loggedIn()->language()->addToStack( $titleType ), Member::loggedIn()->language()->formatList( $titleConditions ) ) ) );
		}
		elseif ( $titleType != 'search_blurb_all_content' )
		{
			$title = Member::loggedIn()->language()->addToStack( 'search_blurb_with_type', FALSE, array( 'sprintf' => array( $title, Member::loggedIn()->language()->addToStack( $titleType ) ) ) );
		}
		else
		{
			$title = Member::loggedIn()->language()->addToStack( 'search_blurb_no_conditions', FALSE, array( 'sprintf' => array( $title ) ) );
		}
		
		/* Set page */
		if ( isset( Request::i()->page ) AND intval( Request::i()->page ) > 0 )
		{
			$query->setPage( intval( Request::i()->page ) );
			$baseUrl = $baseUrl->setQueryString( 'page', intval( Request::i()->page ) );
		}
		
		/* Set Order */
		if ( ! isset( Request::i()->sortby ) )
		{
			Request::i()->sortby = $query->getDefaultSortMethod();
		}
		
		switch( Request::i()->sortby )
		{
			case 'newest':
				$query->setOrder( Query::ORDER_NEWEST_CREATED );
				break;

			case 'relevancy':
				$query->setOrder( Query::ORDER_RELEVANCY );
				break;
		}
		
		$baseUrl = $baseUrl->setQueryString( 'sortby', Request::i()->sortby );
		
		$flags = ( isset( Request::i()->eitherTermsOrTags ) and Request::i()->eitherTermsOrTags === 'and' ) ? Query::TERM_AND_TAGS : Query::TERM_OR_TAGS;
		$operator = NULL;
				
		if ( isset( Request::i()->search_and_or ) and in_array( Request::i()->search_and_or, array( Query::OPERATOR_OR, Query::OPERATOR_AND ) ) )
		{
			$operator = Request::i()->search_and_or;
			$baseUrl = $baseUrl->setQueryString( 'search_and_or', Request::i()->search_and_or );
		}
		
		if ( isset( Request::i()->search_in ) and Request::i()->search_in === 'titles' )
		{
			$flags = $flags | Query::TERM_TITLES_ONLY;
			$baseUrl = $baseUrl->setQueryString( 'search_in', Request::i()->search_in );
		}

		/* Run query */
		$results = $query->search(
			isset( Request::i()->q ) ? ( Request::i()->q ) : NULL,
			isset( Request::i()->tags ) ? explode( ',', Request::i()->tags ) : NULL,
			$flags + Query::TAGS_MATCH_ITEMS_ONLY,
			$operator
		);
				
		/* Get pagination */
		$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
		$count = $results->count( TRUE );
		$pages = ceil( $results->count( TRUE ) / $query->resultsToGet );

		if ( ! $count )
		{
			/* Enforce that we're on page 1 if there are no results to prevent page tampering */
			$page = 1;
		}
		else if( $pages and ( $page < 1 or $page > $pages ) )
		{
			/* There's no point resetting the page at this point as the search query has been run and no results have been found */
			Request::i()->setCookie( 'lastSearch', 0 );
			Output::i()->redirect( $baseUrl->setPage( 'page', 1 ), NULL, 303 );
		}

		$pagination = trim( Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl, $pages, $page, $query->resultsToGet ) );
		
		/* Search tracking, only if there's a term for a non-member search and we're on the first page */
		if( Request::i()->q AND $page == 1 )
		{
			if( !Member::loggedIn()->inGroup( json_decode( Settings::i()->searchlog_exclude_groups ) ) )
			{
				try
				{
					Db::i()->insert( 'core_statistics', array(
						'type'			=> 'search',
						'time'			=> time(),
						'value_4'		=> mb_substr( Request::i()->q, 0, 255 ), // Some search queries can be unnecessarily long
						'value_2'		=> $count,
						'extra_data'	=> md5( Member::loggedIn()->email . Member::loggedIn()->joined ) // Intentionally anonymized
					) );
				}
				catch( Exception $e ) {}
			}
		}

		/* Enable caching for the search results request */
		if ( !Member::loggedIn()->member_id )
		{
			$httpHeaders = array(   'Expires' => DateTime::create()->add( new DateInterval( 'PT3M' ) )->rfc1123(),
									'Cache-Control' => 'no-cache="Set-Cookie", max-age=' . $this->_cacheTimeout . ", s-maxage=" . $this->_cacheTimeout . ", public, stale-if-error, stale-while-revalidate" );

			Output::i()->httpHeaders += $httpHeaders;
		}

		/* Display results */
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array(
				'filters'	=> $this->_form()->customTemplate( array( Theme::i()->getTemplate( 'search' ), 'filters' ), $baseUrl, $count ),
				'hints' 	=> Theme::i()->getTemplate( 'search' )->hints( $baseUrl, $count ),
				'content'	=> Theme::i()->getTemplate( 'search' )->results( $this->_splitTermsForDisplay(), $title, $results, $pagination, $baseUrl, $count ),
				'title'		=> $title,
				'css'		=> array()
			) );
		}
		else
		{
			Output::i()->title = $title;
			Output::i()->output = Theme::i()->getTemplate( 'search' )->search( $this->_splitTermsForDisplay(), $title, $results, $pagination, $baseUrl, $types, $this->_form()->customTemplate( array( Theme::i()->getTemplate( 'search' ), 'filters' ), $baseUrl, $count ), $count );
		}
	}
	
	/**
	 * Get the search form
	 *
	 * @return	Form
	 */
	public function _form() : Form
	{
		/* Init */
		$form = new Form;
		
		/* Update filters sidebar will lose item as it is not part of the form #5772 */
		if ( isset( Request::i()->item ) )
		{
			$form->hiddenValues['item'] = Request::i()->item;
		}
		
		if ( isset( Request::i()->sortby ) )
		{
			$form->hiddenValues['sortby'] = Request::i()->sortby;
		}
		
		if ( isset( Request::i()->sortdirection ) )
		{
			$form->hiddenValues['sortdirection'] = Request::i()->sortdirection;
		}
		
		if ( isset( Request::i()->nodes ) )
		{
			$form->hiddenValues['nodes'] = Request::i()->nodes;
		}
		
		if ( isset( Request::i()->quick ) )
		{
			$form->hiddenValues['quick'] = Request::i()->quick;
		}

		/* If we do a quick search the type is set to 'all' but we want it as '' for the form to work properly */
		if( isset( Request::i()->type ) AND Request::i()->type === 'all' )
		{
			Request::i()->type = '';
		}
		
		/* Set up some sensible defaults */
		if ( ! isset( Request::i()->item ) and ! isset( Request::i()->updated_after ) )
		{
			Request::i()->updated_after = Query::init()->getDefaultDateCutOff();
		}

		/* Types */
		$types				= array( '' => 'search_everything' );
		$contentTypes		= static::contentTypes();
		$contentToggles		= array();
		$typeFields			= array();
		$typeFieldToggles	= array('' => array( 'club' ) );
		$haveCommentClass	= FALSE;
		$haveReplyClass		= FALSE;
		$haveReviewClass	= FALSE;
		$dateOptions = array(
			'any'			=> 'any',
			'day'			=> 'last_24hr',
			'week'			=> 'last_week',
			'month'			=> 'last_month',
			'six_months'	=> 'last_six_months',
			'year'			=> 'last_year',
			'custom'		=> 'custom'
		);

		/* Form tabs */
		$form->addTab( 'search_tab_all' );
		$form->addTab( 'search_tab_content' );
		$form->addTab( 'search_tab_member' );

		/* Figure out member fields to set toggles */
		$memberToggles	= array( 'joinedDate', 'core_members_group' );
		foreach ( Field::fields( array(), Field::SEARCH ) as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				switch ( get_class( $field ) )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
					case 'IPS\Helpers\Form\Date':
					case 'IPS\Helpers\Form\Number':
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\CheckboxSet':
					case 'IPS\Helpers\Form\Radio':
						$memberToggles[]	= 'core_pfield_' . $id;
						break;
				}
			}
		}

		/* Type select */
		foreach ( $contentTypes as $k => $class )
		{
			$types[ $k ] = $k . '_pl';
			if ( $k !== 'core_members' )
			{
				$typeFieldToggles[ $k ][] = $k . '_node';

				if( isset( $class::$databaseColumnMap['views'] ) and Index::i()->supportViewFiltering() )
				{
					$typeFieldToggles[ $k ][] = 'search_min_views';
				}

				if ( isset( $class::$commentClass ) )
				{
					if ( $class::$firstCommentRequired )
					{
						$haveReplyClass = TRUE;
						$typeFieldToggles[ $k ][] = 'search_min_replies';
					}
					else
					{
						$haveCommentClass = TRUE;
						$typeFieldToggles[ $k ][] = 'search_min_comments';
					}
				}
				if ( isset( $class::$reviewClass ) )
				{
					$haveReviewClass = TRUE;
					$typeFieldToggles[ $k ][] = 'search_min_reviews';
				}
			}
		}
		$form->add( new Radio( 'type', '', FALSE, array( 'options' => $types, 'toggles' => $typeFieldToggles ) ), NULL, 'search_tab_content' );

		/* Term */
		$form->add( new Text( 'q' ), NULL, 'search_tab_all' );

		$form->add( new Text( 'tags', Request::i()->tags, FALSE, array(
			'autocomplete' => array(
				'source' => Content\Tag::allTags(),
				'minimized' => FALSE,
				'forceLower' => (bool)Settings::i()->tags_force_lower,
				'freeChoice' => false
			) ), NULL, NULL, NULL, 'tags' ), NULL, 'search_tab_content' );
		$form->add( new Radio( 'eitherTermsOrTags', Request::i()->eitherTermsOrTags, FALSE, array( 'options' => array( 'or' => 'termsortags_or_desc', 'and' => 'termsortags_and_desc' ) ), NULL, NULL, NULL, 'eitherTermsOrTags' ), NULL, 'search_tab_content' );

		/* Author */
		$form->add( new FormMember( 'author', NULL, FALSE, array(), NULL, NULL, NULL, 'author' ), NULL, 'search_tab_content' );
		
		/* Club */
		if( Settings::i()->clubs )
		{
			if ( Club::clubs( Member::loggedIn(), NULL, 'name', TRUE, array(), NULL, TRUE ) )
			{
				$clubs = Club::clubs( Member::loggedIn(), NULL, 'name', TRUE );
				
				$clubOptions = array();
				foreach ( $clubs as $club )
				{
					$clubOptions[ "c{$club->id}" ] = $club->name;
				}
							
				$form->add( new Select( 'club', NULL, FALSE, array( 'options' => $clubOptions, 'parse' => 'normal', 'multiple' => TRUE, 'noDefault' => TRUE, 'class' => 'ipsInput--wide' ), NULL, NULL, NULL, 'club' ), NULL, 'search_tab_content' );
			}
		}
		
		/* Dates */
		$form->add( new Select( 'startDate', ( isset( Request::i()->start_before ) or ( isset( Request::i()->start_after ) and is_numeric( Request::i()->start_after ) ) ) ? 'custom' : Request::i()->start_after, FALSE, array( 'options' => $dateOptions, 'toggles' => array( 'custom' => array( 'elCustomDate_startDate' ) ) ), NULL, NULL, NULL, 'startDate' ), NULL, 'search_tab_content' );
		$form->add( new DateRange( 'startDateCustom', array( 'start' => ( isset( Request::i()->start_after ) and is_numeric(  Request::i()->start_after ) ) ? DateTime::ts( Request::i()->start_after, TRUE ) : NULL, 'end' => isset( Request::i()->start_before ) ? DateTime::ts( Request::i()->start_before, TRUE ) : NULL ), false, array(
			'start' => array( 'timezone' => new DateTimeZone( 'UTC' ), 'time' => FALSE ),
			'end' => array( 'timezone' => new DateTimeZone( 'UTC' ), 'time' => FALSE )
		) ), NULL, 'search_tab_content' );
		$form->add( new Select( 'updatedDate', ( isset( Request::i()->updated_before ) or ( isset( Request::i()->updated_after ) and is_numeric( Request::i()->updated_after ) ) ) ? 'custom' : Request::i()->updated_after, FALSE, array( 'options' => $dateOptions, 'toggles' => array( 'custom' => array( 'elCustomDate_updatedDate' ) ) ), NULL, NULL, NULL, 'updatedDate' ), NULL, 'search_tab_content' );
		$form->add( new DateRange( 'updatedDateCustom', array( 'start' => ( isset( Request::i()->updated_after ) and is_numeric( Request::i()->updated_after ) ) ? DateTime::ts( Request::i()->updated_after, TRUE ) : NULL, 'end' => isset( Request::i()->updated_before ) ? DateTime::ts( Request::i()->updated_before, TRUE ) : NULL ), false, array(
			'start' => array( 'timezone' => new DateTimeZone( 'UTC' ), 'time' => FALSE ),
			'end' => array( 'timezone' => new DateTimeZone( 'UTC' ), 'time' => FALSE )
		) ), NULL, 'search_tab_content' );

		/* Other filters */
		$form->add( new Radio( 'search_in', Request::i()->search_in, FALSE, array( 'options' => array( 'all' => 'titles_and_body', 'titles' => 'titles_only' ) ), NULL, NULL, NULL, 'searchIn' ), NULL, 'search_tab_content' );
		$form->add( new Radio( 'search_and_or', isset( Request::i()->search_and_or ) ? Request::i()->search_and_or : Settings::i()->search_default_operator, FALSE, array( 'options' => array( 'and' => 'search_and', 'or' => 'search_or' ) ), NULL, NULL, NULL, 'andOr' ), NULL, 'search_tab_content' );

		/* Nodes */
		foreach ( $contentTypes as $k => $class )
		{
			if ( isset( $class::$containerNodeClass ) )
			{
				$nodes = NULL;

				/* If we have a node type, we should only select nodes for that type */
				if( isset( Request::i()->type ) AND !empty( Request::i()->type ) )
				{
					$typeInfo	= explode( '_', Request::i()->type );
					$typeClass	= 'IPS\\' . $typeInfo[0] . '\\' . IPS::mb_ucfirst( $typeInfo[1] );

					if( $typeClass == $class )
					{
						$nodes = ( isset( Request::i()->nodes ) ) ? Request::i()->nodes : NULL;
					}
				}

				$nodeClass = $class::$containerNodeClass;
				$field = new Node( $k . '_node', $nodes, FALSE, array( 'class' => $nodeClass, 'subnodes' => FALSE, 'multiple' => TRUE, 'permissionCheck' => $nodeClass::searchableNodesPermission(), 'forceOwner' => FALSE, 'clubs' => ( Settings::i()->clubs AND IPS::classUsesTrait( $nodeClass, 'IPS\Content\ClubContainer' ) ) ), NULL, NULL, NULL, $k . '_node' );
				$field->label = Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle );
				$form->add( $field, NULL, 'search_tab_nodes' );
			}
		}

		/* Comments/Views */
		$queryClass = Query::init();
		if ( $queryClass::SUPPORTS_JOIN_FILTERS )
		{
			if ( $haveCommentClass )
			{
				$form->add( new Number( 'search_min_comments', isset( Request::i()->search_min_comments ) ? Request::i()->search_min_comments : 0, FALSE, array(), NULL, NULL, NULL, 'search_min_comments' ), NULL, 'search_tab_content' );
			}
			if ( $haveReplyClass )
			{
				$form->add( new Number( 'search_min_replies', isset( Request::i()->search_min_replies ) ? Request::i()->search_min_replies : 0, FALSE, array(), NULL, NULL, NULL, 'search_min_replies' ), NULL, 'search_tab_content' );
			}
			if ( $haveReviewClass )
			{
				$form->add( new Number( 'search_min_reviews', isset( Request::i()->search_min_reviews ) ? Request::i()->search_min_reviews : 0, FALSE, array(), NULL, NULL, NULL, 'search_min_reviews' ), NULL, 'search_tab_content' );
			}
			if ( Index::i()->supportViewFiltering() )
			{
				$form->add( new Number( 'search_min_views', isset( Request::i()->search_min_views ) ? Request::i()->search_min_views : 0, FALSE, array(), NULL, NULL, NULL, 'search_min_views' ), NULL, 'search_tab_content' );
			}
		}
		
		/* Member group and joined */
		$groups = Group::groups( TRUE, FALSE, TRUE );

		$form->add(new CheckboxSet('group', ( isset( Request::i()->group ) ) ? is_array( Request::i()->group) ? array_keys( Request::i()->group) : array( Request::i()->group ) : array_keys( $groups ), FALSE, array('options' => $groups, 'parse' => 'normal'), NULL, NULL, NULL, 'core_members_group'), NULL, 'search_tab_member' );
		$form->add(new Select('joinedDate', (isset(Request::i()->start_before) or (isset(Request::i()->start_after) and is_numeric(Request::i()->start_after))) ? 'custom' : Request::i()->start_after, FALSE, array('options' => $dateOptions, 'toggles' => array('custom' => array('elCustomDate_joinedDate'))), NULL, NULL, NULL, 'joinedDate'), NULL, 'search_tab_member' );
		$form->add(new DateRange('joinedDateCustom', array('start' => (isset(Request::i()->start_after) and is_numeric(Request::i()->start_after)) ? DateTime::ts(Request::i()->start_after, TRUE) : NULL, 'end' => isset(Request::i()->start_before) ? DateTime::ts(Request::i()->start_before, TRUE) : NULL)), NULL, 'search_tab_member' );

		/* Profile fields for member searches */
		$memberFields	= array();
		foreach ( Field::fields( array(), Field::SEARCH ) as $group => $fields )
		{
			$fieldsToAdd	= array();
			/* Fields */
			foreach ( $fields as $id => $field )
			{
				/* Only show to non-staff if available to view by all. */
				if ( Field::load( $id )->member_hide != 'all' AND ( !Member::loggedIn()->isAdmin() OR !Member::loggedIn()->modPermissions() ) )
				{
					continue;
				}
				
				/* Alias the lang keys */
				$realLangKey = "core_pfield_{$id}";

				/* Work out the object type so we can show the appropriate field */
				$type = get_class( $field );
				$helper = NULL;
				
				switch ( $type )
				{
					case 'IPS\Helpers\Form\Text':
					case 'IPS\Helpers\Form\Tel':
					case 'IPS\Helpers\Form\Editor':
					case 'IPS\Helpers\Form\Email':
					case 'IPS\Helpers\Form\TextArea':
					case 'IPS\Helpers\Form\Url':
						$helper = new Text( 'core_pfield_' . $id, NULL, FALSE, array(), NULL, NULL, NULL, 'core_pfield_' . $id );
						$memberFields[]	= 'core_pfield_' . $id;
						break;
					case 'IPS\Helpers\Form\Date':
						$helper = new DateRange( 'core_pfield_' . $id, NULL, FALSE, array(), NULL, NULL, NULL, 'core_pfield_' . $id );
						$memberFields[]	= 'core_pfield_' . $id;
						break;
					case 'IPS\Helpers\Form\Number':
						$helper = new Number( 'core_pfield_' . $id, -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'member_number_anyvalue' ), NULL, NULL, NULL, 'core_pfield_' . $id );
						$memberFields[]	= 'core_pfield_' . $id;
						break;
					case 'IPS\Helpers\Form\Select':
					case 'IPS\Helpers\Form\CheckboxSet':
					case 'IPS\Helpers\Form\Radio':
						$options = array( '' => "" );
						if( count( $field->options['options'] ) )
						{
							foreach ($field->options['options'] as $optionKey => $option )
							{
								$options[ $type === 'IPS\Helpers\Form\CheckboxSet' ? $optionKey : $option ] = $option;
							}
						}
						
						$helper = new Select( 'core_pfield_' . $id, NULL, FALSE, array( 'options' => $options ), NULL, NULL, NULL, 'core_pfield_' . $id );
						$memberFields[]	= 'core_pfield_' . $id;
						break;
				}
				
				if ( $helper )
				{
					$fieldsToAdd[] = $helper;
				}
			}
			
			if( count( $fieldsToAdd ) )
			{
				foreach( $fieldsToAdd as $field )
				{
					$form->add( $field, NULL, 'search_tab_member' );
				}
			}
		}		

		/* If they submitted the advanced search form, redirect back (searching is a GET not a POST) */
		if ( $values = $form->values() )
		{
			if( !Request::i()->isAjax() AND ( ( $values['q'] or $values['tags'] ) or $values['type'] == 'core_members' ) )
			{
				$url = Url::internal( 'app=core&module=search&controller=search', 'front', 'search' );
							
				if ( $values['q'] )
				{
					$url = $url->setQueryString( 'q', $values['q'] );
				}
				if ( $values['tags'] )
				{
					$url = $url->setQueryString( 'tags', implode( ',', $values['tags'] ) );
				}
				if ( $values['q'] and $values['tags'] )
				{
					$url = $url->setQueryString( 'eitherTermsOrTags', $values['eitherTermsOrTags'] );
				}
				if ( $values['type'] )
				{
					$url = $url->setQueryString( 'type', $values['type'] );
					
					if ( isset( $values[ $values['type'] . '_node' ] ) and !empty( $values[ $values['type'] . '_node' ] ) )
					{
						$url = $url->setQueryString( 'nodes', implode( ',', array_keys( $values[ $values['type'] . '_node' ] ) ) );
					}
					
					if ( isset( $values['search_min_comments'] ) and $values['search_min_comments'] )
					{
						$url = $url->setQueryString( 'comments', $values['search_min_comments'] );
					}
					if ( isset( $values['search_min_replies'] ) and $values['search_min_replies'] )
					{
						$url = $url->setQueryString( 'replies', $values['search_min_replies'] );
					}
					if ( isset( $values['search_min_reviews'] ) and $values['search_min_reviews'] )
					{
						$url = $url->setQueryString( 'reviews', $values['search_min_reviews'] );
					}
					if ( isset( $values['search_min_views'] ) and $values['search_min_views'] and Index::i()->supportViewFiltering() )
					{
						$url = $url->setQueryString( 'views', $values['search_min_views'] );
					}
				}
				if ( isset( $values['author'] ) and $values['author'] )
				{
					$url = $url->setQueryString( 'author', $values['author']->name );
				}
				if ( isset( $values['club'] ) and $values['club'] )
				{
					$url = $url->setQueryString( 'club', $values['club'] );
				}

				if ( isset( $values['group'] ) and $values['group'] )
				{

					$values['group']	= array_flip( $values['group'] );

					array_walk( $values['group'], function( &$value, $key ){
						$value = 1;
					} );

					$url = $url->setQueryString( 'group', $values['group'] );
				}

				foreach( $memberFields as $fieldName )
				{
					if( isset( $values[ $fieldName ] ) AND $values[ $fieldName ] )
					{
						$url = $url->setQueryString( $fieldName, $values[ $fieldName ] );
					}
				}
				
				if( isset( $values['joinedDate'] ) AND $values['joinedDate'] != 'custom' )
				{
					$url = $url->setQueryString( 'start_after', $values['joinedDate'] );
				}

				if( isset( $values['joinedDate'] ) AND $values['joinedDate'] == 'custom' AND isset( $values['joinedDateCustom']['start'] ) )
				{
					$url = $url->setQueryString( 'start_after', $values['joinedDateCustom']['start']->getTimestamp() );
				}

				if( isset( $values['joinedDate'] ) AND $values['joinedDate'] == 'custom' AND isset( $values['joinedDateCustom']['end'] ) )
				{
					$url = $url->setQueryString( 'start_before', $values['joinedDateCustom']['end']->getTimestamp() );
				}

				foreach ( array( 'start', 'updated' ) as $k )
				{
					if ( $values[ $k . 'Date' ] != 'any' )
					{
						if ( $values[ $k . 'Date' ] === 'custom' )
						{
							if ( $values[ $k . 'DateCustom' ]['start'] )
							{
								$url = $url->setQueryString( $k . '_after', $values[ $k . 'DateCustom' ]['start']->getTimestamp() );
							}
							if ( $values[ $k . 'DateCustom' ]['end'] )
							{
								$url = $url->setQueryString( $k . '_before', $values[ $k . 'DateCustom' ]['end']->getTimestamp() );
							}
						}
						else
						{
							$url = $url->setQueryString( $k . '_after', $values[ $k . 'Date' ] );
						}
					}
				}
				Output::i()->redirect( $url );
			}
		}

		return $form;
	}
	
	/**
	 * Handle quicksearch and redirect to correct results page
	 *
	 * @return	void
	 */
	public static function quicksearch() : void
	{
		$query = array();
		
		if ( Request::i()->q )
		{
			$query['q'] = Request::i()->q;
			$query['quick'] = 1;
			
			if ( Request::i()->type != 'all' )
			{
				if ( mb_substr( Request::i()->type, 0, 11 ) === 'contextual_' )
				{
					if ( $json = json_decode( mb_substr( Request::i()->type, 11 ), TRUE ) )
					{
						foreach ( $json as $k => $v )
						{
							$query[ $k ] = $v;
						}
					}
				}
				else
				{
					$query['type'] = Request::i()->type;
				}
			}
			
			if ( Request::i()->search_and_or != Settings::i()->search_default_operator )
			{
				$query['search_and_or'] = Request::i()->search_and_or;
			}
			
			if ( Request::i()->search_in == 'titles' )
			{
				$query['search_in'] = 'titles';
			}

			if( isset( Request::i()->startDate ) )
			{
				$query['start_after'] = Request::i()->startDate;
			}

			if( isset( Request::i()->updatedDate ) )
			{
				$query['updated_after'] = Request::i()->updatedDate;
			}
		}
		
		Output::i()->redirect( Url::internal( 'app=core&module=search&controller=search', 'front', 'search' )->setQueryString( $query ) );
	}
	
	/**
	 * Get the different content type extensions
	 *
	 * @param	bool|Member	$member		Check member access
	 * @return	array
	 */
	public static function contentTypes( Member|bool $member = TRUE ) : array
	{
		$types = array();
		foreach( Content\Search\SearchContent::searchableClasses() as $class )
		{
			if( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
			{
				$key = mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) );
				$types[ $key ] = $class;
			}
		}
		return $types;
	}
	
	/**
	 * Splits the search term into distinct matches
	 * e.g. one "two three" becomes ['one', 'two three']
	 *
	 * @return	string
	 */
	protected function _splitTermsForDisplay() : string
	{
		if( !isset( Request::i()->q ) ){
			return json_encode( array() );
		}

		$words = preg_split("/[\s]*\\\"([^\\\"]+)\\\"[\s]*|[\s]*'([^']+)'[\s]*|[\s]+/", Request::i()->q, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		
		$words = Index::i()->stemmedTerms( $words );

		foreach( $words as $idx => $word )
		{
			$words[ $idx ] = htmlentities( $word, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ); // ENT_QUOTES is because this will go in a HTML attribute (data-term="$value") so if you include a single quote in your search query, it can break
		}

		return json_encode( $words );
	}
	
	/**
	 * Global filter options (AJAX Request)
	 *
	 * @return	void
	 */
	protected function globalFilterOptions() : void
	{
		Output::i()->sendOutput( Theme::i()->getTemplate( 'search' )->globalSearchMenuOptions( explode( ',', Request::i()->exclude ) ) );
	}
}