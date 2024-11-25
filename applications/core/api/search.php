<?php
/**
 * @brief		Search API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Oct 2017
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Api\Controller;
use IPS\Api\Response;
use IPS\Content\Search\ApiResponse;
use IPS\Content\Search\ContentFilter;
use IPS\Content\Search\Query;
use IPS\core\modules\front\search\search as FrontModuleSearch;
use IPS\DateTime;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Search API
 */
class search extends Controller
{
	/**
	 * GET /core/search
	 * Perform a search and get a list of results
	 *
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apiparam	string	q			String to search for
	 * @apiparam	string	tags		Comma-separated list of tags to search for
	 * @apiparam	string	type		Content type class to restrict searches to
	 * @apiparam	int		item		Restrict searches to comments or reviews made to the specified item
	 * @apiparam	string	nodes		Comma-separated list of node IDs to restrict searches to
	 * @apiparam	int		search_min_comments	Minimum number of comments search results must have for content types that support comments
	 * @apiparam	int		search_min_replies	Minimum number of comments search results must have for content types that require the first comment (e.g. topics)
	 * @apiparam	int		search_min_reviews	Minimum number of reviews search results must have
	 * @apiparam	int		search_min_views	Minimum number of views search results must have (note, not supported by elasticsearch)
	 * @apiparam	string	author		Restrict searches to results posted by this member (name)
	 * @apiparam	string	club		Comma-separated list of club IDs to restrict searches to
	 * @apiparam	string	start_before	Date period (from current time) that search results should start before
	 * @apiparam	string	start_after		Date period (from current time) that search results should start after
	 * @apiparam	string	updated_before	Date period (from current time) that search results should last be updated before
	 * @apiparam	string	updated_after	Date period (from current time) that search results should last be updated after
	 * @apiparam	string	sortby			Sort by method (newest or relevancy)
	 * @apiparam	string	eitherTermsOrTags	Whether to search both tags and search term ("and") or either ("or")
	 * @apiparam	string	search_and_or	Whether to perform an "and" search or an "or" search for all search terms
	 * @apiparam	string	search_in		Specify "titles" to search in titles only, otherwise titles and content are both searched
	 * @apiparam	int		search_as		Member ID to perform the search as (guest permissions will be used when this parameter is omitted)
	 * @apiparam	bool	doNotTrack		If doNotTrack is passed with a value of 1, the search will not be tracked for statistical purposes
	 * @apireturn		array
	 * @apiresponse	int		page		api_int_page
	 * @apiresponse	int		perPage		api_int_perpage
	 * @apiresponse	int		totalResults	api_int_totalresults
	 * @apiresponse	int		totalPages	api_int_totalpages
	 * @apiresponse	[\IPS\Content\Search\Result]	results		api_results_thispage
	 * @note	For requests using an OAuth Access Token for a particular member, only content the authorized user can view will be included and the "search_as" parameter will be ignored.
	 * @return ApiResponse
	 */
	public function GETindex(): ApiResponse
	{
		$memberPermissions = $this->member;

		if( !$this->member AND isset( Request::i()->search_as ) )
		{
			$memberPermissions = Member::load( Request::i()->search_as );

			if( !$memberPermissions->member_id )
			{
				$memberPermissions = NULL;
			}
		}

		/* Get valid content types */
		$contentTypes = FrontModuleSearch::contentTypes( $memberPermissions ?: TRUE );

		/* Initialize search */
		$query = Query::init( $memberPermissions ?: NULL );

		/* Set content type */
		if ( isset( Request::i()->type ) and array_key_exists( Request::i()->type, $contentTypes ) )
		{	
			if ( isset( Request::i()->item ) )
			{
				$class = $contentTypes[ Request::i()->type ];
				try
				{
					$item = $class::loadAndCheckPerms( Request::i()->item );
					$query->filterByContent( array( ContentFilter::init( $class )->onlyInItems( array( Request::i()->item ) ) ) );
				}
				catch ( OutOfRangeException $e ) { }
			}
			else
			{
				$filter = ContentFilter::init( $contentTypes[ Request::i()->type ] );
				
				if ( isset( Request::i()->nodes ) )
				{
					$filter->onlyInContainers( explode( ',', Request::i()->nodes ) );
				}
				
				if ( isset( Request::i()->search_min_comments ) )
				{
					$filter->minimumComments(  Request::i()->search_min_comments );
				}
				if ( isset( Request::i()->search_min_replies ) )
				{
					$filter->minimumComments(  Request::i()->search_min_replies + 1 );
				}
				if ( isset( Request::i()->search_min_reviews ) )
				{
					$filter->minimumReviews(  Request::i()->search_min_reviews );
				}
				if ( isset( Request::i()->search_min_views ) )
				{
					$filter->minimumViews(  Request::i()->search_min_views );
				}
				
				$query->filterByContent( array( $filter ) );
			}
		}
		
		/* Filter by author */
		if ( isset( Request::i()->author ) )
		{
			$author = Member::load( Request::i()->author, 'name' );
			if ( $author->member_id )
			{
				$query->filterByAuthor( $author );
			}
		}
		
		/* Filter by club */
		if ( isset( Request::i()->club ) AND Settings::i()->clubs )
		{
			$query->filterByClub( explode( ',', Request::i()->club ) );
		}
		
		/* Set time cutoffs */
		foreach ( array( 'start' => 'filterByCreateDate', 'updated' => 'filterByLastUpdatedDate' ) as $k => $method )
		{
			$beforeKey = "{$k}_before";
			$afterKey = "{$k}_after";
			if ( isset( Request::i()->$beforeKey ) or isset( Request::i()->$afterKey ) )
			{
				foreach ( array( 'before', 'after' ) as $l )
				{
					$$l = NULL;
					$key = "{$l}Key";
					if ( isset( Request::i()->$$key ) AND Request::i()->$$key != 'any' )
					{
						switch ( Request::i()->$$key )
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
								$$l = DateTime::ts( Request::i()->$$key );
								break;
						}
					}
				}
				
				$query->$method( $after, $before );
			}
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

		$flags = ( isset( Request::i()->eitherTermsOrTags ) and Request::i()->eitherTermsOrTags === 'and' ) ? Query::TERM_AND_TAGS : Query::TERM_OR_TAGS;
		$operator = NULL;
		
		if ( isset( Request::i()->search_and_or ) and in_array( Request::i()->search_and_or, array( Query::OPERATOR_OR, Query::OPERATOR_AND ) ) )
		{
			$operator = Request::i()->search_and_or;
		}
		
		if ( isset( Request::i()->search_in ) and Request::i()->search_in === 'titles' )
		{
			$flags = $flags | Query::TERM_TITLES_ONLY;
		}

		/* Return */
		return new ApiResponse(
			200,
			array( $query, $flags, isset( Request::i()->q ) ? ( Request::i()->q ) : NULL, isset( Request::i()->tags ) ? explode( ',', Request::i()->tags ) : NULL, $operator ),
			isset( Request::i()->page ) ? Request::i()->page : 1,
			NULL,
			0,
			$this->member,
			isset( Request::i()->perPage ) ? Request::i()->perPage : NULL
		);
	}

	/**
	 * GET /core/search/contenttypes
	 * Get list of content types that can be searched
	 *
	 * @clientapiparam	int	search_as	Member ID to perform the search as (by default, search results are based on guest permissions)
	 * @apireturn		array
	 * @apiresponse	array	contenttypes	Content types that can be used in /search requests in the 'type' parameter
	 * @return Response
	 */
	public function GETitem(): Response
	{
		$memberPermissions = $this->member;

		if( !$this->member AND isset( Request::i()->search_as ) )
		{
			$memberPermissions = Member::load( Request::i()->search_as );

			if( !$memberPermissions->member_id )
			{
				$memberPermissions = NULL;
			}
		}

		return new Response( 200, array( 'contenttypes' => array_keys( FrontModuleSearch::contentTypes( $memberPermissions ?: TRUE ) ) ) );
	}
}