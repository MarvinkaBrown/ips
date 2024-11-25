<?php
/**
 * @brief		streams
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Jul 2015
 */

namespace IPS\core\modules\front\discover;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\Content;
use IPS\Content\Search\Query;
use IPS\Content\Search\Result\Content as ResultContent;
use IPS\core\Stream;
use IPS\core\Stream\Subscription;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Login;
use IPS\Member;
use IPS\Member\Club;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use UnderFlowException;
use function count;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * streams
 */
class streams extends Controller
{
	/**
	 * @brief These properties are used to specify datalayer context properties.
	 *
	 */
	public static array $dataLayerContext = array(
		'community_area' =>  [ 'value' => 'activity_stream', 'odkUpdate' => 'true']
	);

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( isset( Request::i()->_nodeSelectName ) )
		{
			$this->getContainerNodeElement();
			return;
		}
		
		/* Initiate the breadcrumb */
		Output::i()->breadcrumb = array( array( Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' ), Member::loggedIn()->language()->addToStack('activity') ) );

		/* Necessary CSS/JS */
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_streams.js', 'core' ) );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/streams.css' ) );

		/* Add any global CSS from other apps */
		foreach( Application::applications() as $app )
		{
			$app::outputCss();
		}
		
		/* Execute */
		parent::execute();
	}
	
	/**
	 * View Stream
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* If this request is from an auto-poll, kill it and exit */
		if ( !Settings::i()->auto_polling_enabled && Request::i()->isAjax() and isset( Request::i()->after ) )
		{
			Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
		}
		
		/* RSS validate? */
		$member = NULL;

		if ( isset( Request::i()->rss ) AND isset( Request::i()->member ) AND isset( Request::i()->key ) )
		{
			$member = Member::load( Request::i()->member );

			if ( !$member->member_id OR !Login::compareHashes( $member->getUniqueMemberHash(), (string) Request::i()->key ) )
			{
				$member = NULL;
			}

			/* If we do not have a specific member, and this member is not allowed to view the site, throw an error as they do not have permission */
			if ( ! $member and ! Member::loggedIn()->group['g_view_board'] )
			{
				Output::i()->error( 'stream_no_permission', '2C280/G', 403, '' );
			}
		}
		else if ( ! Member::loggedIn()->group['g_view_board'] )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' )->setQueryString( 'ref', base64_encode( Request::i()->url() ) ) );
		}


		$form = NULL;
		/* Viewing a particular stream? */
		if ( isset( Request::i()->id ) )
		{
			/* Get it */
			try
			{
				$stream = Stream::load( Request::i()->id );
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2C280/1', 404, '' );
			}

			/* In order to make most streams more efficient, if we are looking at newest items first, and we haven't set a custom date, then just look for the last 12 months max */
			if ( Settings::i()->search_method != 'elastic' and $stream->date_type == 'all' and $stream->sort == 'newest' )
			{
				$stream->date_relative_days = 365;
				$stream->date_type = 'relative';
			}
			
			/* Suitable for guests? */
			if ( !Member::loggedIn()->member_id and !$member and !( ( $stream->ownership == 'all' or $stream->ownership == 'custom' ) and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' ) )
			{
				Output::i()->error( 'stream_no_permission', '2C280/3', 403, '' );
			}
			
			if ( Member::loggedIn()->member_id and isset( Request::i()->default ) )
			{
				Session::i()->csrfCheck();
				
				if ( Request::i()->default )
				{
					Member::loggedIn()->defaultStream = $stream->_id;
				}
				else
				{
					Member::loggedIn()->defaultStream = NULL;
				}
				
				if ( Request::i()->isAjax() )
				{
					$defaultStream = Stream::defaultStream();
					
					if ( ! $defaultStream )
					{
						Output::i()->json( array( 'title' => NULL ) );
					}
					else
					{
						Output::i()->json( array(
							'url'   	=> $defaultStream->url(),
							'title' 	=> htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ),
							'tooltip'	=> $defaultStream->_title, // We need to pass this individually for the tooltip that shows, but the JS itself will escape any entities
							'id'    	=> $defaultStream->_id
						 ) );
					}
				}
				
				Output::i()->redirect( $stream->url() );
			}
			
			$form = $this->_buildForm( $stream );
			
			/* Set title and breadcrumb */
			
			Output::i()->breadcrumb[] = array( $stream->url(), $stream->_title );
			Output::i()->title = $stream->_title;
		}
		
		/* Or just everything? */
		else
		{
			if ( Member::loggedIn()->member_id and isset( Request::i()->default ) )
			{
				Session::i()->csrfCheck();

				if ( Request::i()->default )
				{
					Member::loggedIn()->defaultStream = 0;
				}
				else
				{
					Member::loggedIn()->defaultStream = NULL;
				}
				
				if ( Request::i()->isAjax() )
				{
					$defaultStream = Stream::defaultStream();
					
					if ( ! $defaultStream )
					{
						Output::i()->json( array( 'title' => NULL ) );
					}
					else
					{
						Output::i()->json( array(
							'url'   => Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' ),
							'title' => htmlspecialchars( $defaultStream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ),
							'id'    => $defaultStream->_id
						 ) );
					}
				}
				
				Output::i()->redirect( Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' ) );
			}

			/* Start with a blank stream */
			$stream = Stream::allActivityStream();
			$stream->default_view = "expanded";

			/* Set the title to "All Activity" */
			Output::i()->title = Member::loggedIn()->language()->addToStack('all_activity');
		}
		
		/* Store the URL before we add query strings to it, etc */
		$streamUrl = $stream->url();
		
		/* Look for url params that can come from view switch or load more button */	
		/* but only if we haven't submitted the form on this request */
		if( !Request::i()->stream_submitted )
		{
			$streamFields = array( 'include_comments', 'classes', 'ownership', 'custom_members', 'read', 'follow', 'followed_types', 'date_type', 'date_start', 'date_end', 'date_relative_days', 'sort', 'tags', 'solved' );

			/* Clubs enabled? */
			if( Settings::i()->clubs )
			{
				$streamFields[]	= 'clubs';
			}
			
			/* Build and format field values */
			$_values = array();
			foreach ( Request::i() as $requestKey => $requestField )
			{
				$field = str_replace( 'stream_', '', $requestKey );
				
				if ( $field == 'custom_members' and isset( Request::i()->stream_custom_members ) )
				{
					$members = NULL;
					foreach( str_replace( "\r", '', explode( "\n", Request::i()->stream_custom_members ) ) as $name )
					{
						try
						{
							$members[] = Member::load( $name, 'name' );
						}
						catch( OutOfRangeException $e ) { }
					}
					
					$_values['stream_custom_members'] = $members;
				}
				else if ( in_array( $field, $streamFields ) && ( $field == 'classes' || $field == 'followed_types' ) && is_array( Request::i()->{ 'stream_' . $field } ) )
				{
					/* Some array values will come in as key=1 params, so we only need the keys here */
					$_values[ $requestKey ] = array_keys( $requestField );
				}
				else
				{
					$_values[ $requestKey ] = $requestField;
				}				
			}
			
			if ( isset( $_values['stream_club_filter'] ) and !isset( $_values['stream_club_select'] ) )
			{
				$_values['stream_club_select'] = 'select';
			}
			
			$formattedValues = $stream->formatFormValues( $_values );
			
			$rebuildForm = FALSE;

			/* Overwrite stream config if present in the request */
			foreach ( $streamFields as $k )
			{	
				$requestKey = 'stream_' . $k;
				
				if ( isset( Request::i()->$requestKey ) or ( $k === 'clubs' and ( isset( Request::i()->stream_club_select ) or isset( Request::i()->stream_club_filter ) ) ) )
				{
					if ( $stream->$k != $formattedValues[ $k ] )
					{
						$stream->$k = $formattedValues[ $k ];
						$stream->baseUrl = $stream->baseUrl->setQueryString( 'stream_' . $k, Request::i()->$requestKey );
						$rebuildForm = TRUE;
					}
				}			
			}
			
			/* Containers are special */
			if ( isset( Request::i()->stream_containers ) and is_array( Request::i()->stream_containers ) )
			{ 
				/* Remove null/'' values as we no longer want to restrict by container for this class if that occurs */
				$cleanedContainers = array();
				foreach( Request::i()->stream_containers as $class => $containers )
				{
					if ( $containers )
					{
						$cleanedContainers[ $class ] = $containers;
					}
				}
				
				if ( count( array_diff_assoc( $cleanedContainers, $stream::containersToUrl( $stream->containers ) ) ) )
				{
					$stream->containers = $stream::containersFromUrl( $cleanedContainers );
					$stream->baseUrl = $stream->baseUrl->setQueryString( 'stream_containers', $cleanedContainers );
					$rebuildForm = TRUE;
				}
			}
			
			if ( isset( Request::i()->id ) AND $rebuildForm )
			{
				/* reset the form to account for all the modifications to $stream class variables */
				$form = $this->_buildForm( $stream );
			}
		}

		/* Condensed or expanded? */
		$view = 'expanded';
		$streamID = ( Request::i()->id ) ? Request::i()->id : 'all';

		if( isset( Request::i()->view ) AND Login::compareHashes( Session::i()->csrfKey, (string) Request::i()->csrfKey ) )
		{
			$view = Request::i()->view;

			Request::i()->setCookie( 'stream_view_' . $streamID, Request::i()->view, DateTime::create()->add( new DateInterval( 'P1Y' ) ) );

			if( !Request::i()->isAjax() )
			{
				Output::i()->redirect( $stream->url() );
			}
		}
		elseif ( isset( Request::i()->cookie['stream_view_' . $streamID] ) )
		{
			$view =  Request::i()->cookie['stream_view_' . $streamID];
		}
		else
		{
			$view = $stream->default_view;
		}

		/* Ensure correct params are set for view mode */
		if ( $view === 'condensed' )
		{
			$stream->include_comments = FALSE;
		}

		/* Build the query */
		$query = $stream->query( $member );

		/* Set page or the before/after date */
		$currentPage = 1;
		if ( isset( Request::i()->page ) AND intval( Request::i()->page ) > 0 )
		{
			$currentPage = Request::i()->page;
			$query->setPage( $currentPage );
		}
		
		$before = ( isset( Request::i()->before ) and is_numeric( Request::i()->before ) ) ? (int) Request::i()->before : null;
		$after  = ( isset( Request::i()->after ) and is_numeric( Request::i()->after ) ) ? (int) Request::i()->after : null;
		
		/* If we sort by oldest, then we need to switch these values */
		if ( $stream->sort == 'oldest' )
		{
			$tmp = $after;
			$after  = $before;
			$before = $tmp;
			unset( $tmp );
		}
		
		if ( isset( Request::i()->latest ) )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( DateTime::ts( Request::i()->latest )  );
			}
			else
			{
				$query->filterByCreateDate( DateTime::ts( Request::i()->latest ) );
			}
			
			$query->setLimit(350);
		}
		else if ( $before )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( NULL, DateTime::ts( $before ) );
			}
			else
			{
				$query->filterByCreateDate( NULL, DateTime::ts( $before ) );
			}
		}
		if ( $after )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( DateTime::ts( $after ) );
			}
			else
			{
				$query->filterByCreateDate( DateTime::ts( $after ) );
			}
		}

		/* Get the results */
		$results = $query->search( NULL, $stream->tags ? explode( ',', $stream->tags ) : NULL, ( $stream->include_comments ? Query::TAGS_MATCH_ITEMS_ONLY + Query::TERM_OR_TAGS : Query::TERM_OR_TAGS ) );
		
		/* Load data we need like the authors, etc */
		$results->init();
		
		/* Add in extra stuff? */
		if ( !isset( Request::i()->id ) )
		{
			/* Is anything turned on? */
			$extra = array();
			foreach ( array( 'register', 'follow_member', 'follow_content', 'photo', 'like', 'votes', 'react', 'clubs' ) as $k )
			{
				$key = "all_activity_{$k}";
				if ( Settings::i()->$key )
				{
					$extra[] = $k;
				}
			}
			if ( !empty( $extra ) )
			{
				$results = $results->addExtraItems( $extra, NULL, ( Request::i()->isAjax() and isset( Request::i()->after ) ) ? DateTime::ts( Request::i()->after ) : NULL, ( Request::i()->isAjax() and isset( Request::i()->before ) ) ? DateTime::ts( Request::i()->before ) : NULL );
			}
		}

		/* If this is an AJAX request, just show the results */
		if ( Request::i()->isAjax() )
		{
			$output = Theme::i()->getTemplate('streams')->streamItems( $results, TRUE, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );

			$return = array(
				'title' => htmlspecialchars( $stream->_title, ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ),
				'blurb' => $stream->blurb(),
				'config' => json_encode( $stream->config() ),
				'count' => count( $results ),
				'results' => $output,
				'id' => ( $stream->id ) ? $stream->id : '',
				'url' => $stream->url()
			);

			Output::i()->json( $return );
		}
		
		/* Display - RSS */
		if ( Settings::i()->activity_stream_rss and isset( Request::i()->rss ) )
		{
			$document = Rss::newDocument( $stream->baseUrl, $stream->_title, sprintf( Member::loggedIn()->language()->get( 'stream_rss_title' ), Settings::i()->board_name, $stream->_title ) );
			
			foreach ( $results as $result )
			{
				if ( $result instanceof ResultContent )
				{
					$result->addToRssFeed( $document );
				}
			}
			
			Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
		}
		
		/* Display - HTML */
		else
		{			
			/* What's the RSS Link? */
			$rssLink = NULL;
			if ( Settings::i()->activity_stream_rss )
			{
				if ( isset( Request::i()->id ) )
				{
					$rssLink = Url::internal( "app=core&module=discover&controller=streams&id={$stream->id}", 'front', 'discover_rss' );
					if ( Member::loggedIn()->member_id )
					{
						$rssLink = $rssLink->setQueryString( 'member', Member::loggedIn()->member_id )->setQueryString( 'key', Member::loggedIn()->getUniqueMemberHash() );
					}
				}
				else
				{
					/* It's all activity! */
					$rssLink = Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_rss_all_activity' );
				}
			}
			
			/* Display */
			$output = Theme::i()->getTemplate('streams')->stream( $stream, $results, !$stream->id, TRUE, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );
			
			Output::i()->linkTags['canonical'] = (string) $streamUrl;
			Output::i()->jsVars['stream_config'] = $stream->config();
			Output::i()->output = Theme::i()->getTemplate('streams')->streamWrapper( $stream, $output, $form, $rssLink, isset( Request::i()->id ) and $stream->member and Member::loggedIn()->member_id and $stream->member != Member::loggedIn()->member_id );
		}
	}
	
	/**
	 * Create a new stream
	 *
	 * @return	void
	 */
	public function create() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C280/A', 403, '' );
		}
		
		$stream = new Stream;
		$stream->member = Member::loggedIn()->member_id;
		
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'create_new_stream' );
		Output::i()->output = $this->_buildForm( $stream );
	}
	
	/**
	 * Edit a stream's title
	 *
	 * @return void
	 */
	public function edit() : void
	{
		Session::i()->csrfCheck();
		
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C280/6', 403, '' );
		}
		
		try
		{
			$stream = Stream::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C280/7', 404, '' );
		}
		
		if ( $stream->member != Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission', '2C280/8', 403, '' );
		}
		
		$form = new Form;
		$form->class = 'ipsForm--vertical ipsForm--edit-stream-title';
		$form->add( new Text( 'stream_title', $stream->title, NULL, array( 'maxLength' => 255 ) ) );
		
		if ( $values = $form->values() )
		{
			if ( isset( $values['stream_title'] ) and $values['stream_title'] )
			{
				$stream->title = $values['stream_title'];
				$stream->save();
				$this->_rebuildStreams();
				Output::i()->redirect( $stream->url() );
			}	
		}
		
		/* Output */
		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->genericBlock( $form, NULL, 'i-padding_3' ) );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'stream_edit_title' );
		Output::i()->output = $form;
	}
	
	/**
	 * Copy a stream
	 *
	 * @return	void
	 */
	public function copy() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C280/4', 403, '' );
		}
		
		Session::i()->csrfCheck();
		
		try
		{
			$stream = clone Stream::load( Request::i()->id );
			$stream->member = Member::loggedIn()->member_id;
			$stream->save();
			$this->_rebuildStreams();
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C280/5', 404, '' );
		}

		Output::i()->redirect( $stream->url() );
	}

	/**
	 * Deletes a new stream
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		Request::i()->confirmedDelete();
		
		try
		{
			$stream = Stream::load( Request::i()->id );
			if ( !$stream->member or $stream->member != Member::loggedIn()->member_id )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C280/2', 404, '' );
		}
		
		$stream->delete();
		$this->_rebuildStreams();

		Output::i()->redirect( Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' ) );
	}
	
	/**
	 * Get the container Node form element HTML
	 *
	 * @return string|null
	 */
	protected function getContainerNodeElement() : ?string
	{
		$currentContainers = array();
		$stream = NULL;
		if ( isset( Request::i()->id ) )
		{
			$stream = Stream::load( Request::i()->id );
			$currentContainers = $stream->containers ? json_decode( $stream->containers, TRUE ) : array();
			
			if ( isset( Request::i()->stream_containers ) )
			{
				$currentContainers = json_decode( $stream::containersFromUrl( Request::i()->stream_containers ), TRUE );
			}
		}

		foreach( Content\Search\SearchContent::searchableClasses() as $class )
		{
			$classes[ $class ] = $class::$title . '_pl';
			if ( isset( $class::$containerNodeClass ) and $class == Request::i()->className )
			{
				$url = $stream ? $stream->baseUrl->setQueryString( 'className', $class ) : Request::i()->url()->setQueryString( 'className', $class );
				$containerClass = $class::$containerNodeClass;
				$field = new Node( 'stream_containers_' . $class::$title, $currentContainers[$class] ?? array(), NULL, array( 'url' => $url, 'class' => $class::$containerNodeClass, 'multiple' => TRUE, 'permissionCheck' => $containerClass::searchableNodesPermission(), 'clubs' => ( Settings::i()->clubs AND IPS::classUsesTrait( $containerClass, 'IPS\Content\ClubContainer' ) ) ), NULL, NULL, NULL, 'stream_containers_' . $class::$title );
				$field->label = Member::loggedIn()->language()->addToStack( 'stream_narrow_by_container_label', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $containerClass::$nodeTitle ) ) ) );

				return Output::i()->json( array( 'node' => Theme::i()->getTemplate('streams')->filterFormContentTypeContent( $field, $class, Request::i()->key ) ) );
			}
		}

		return NULL;
	}
	
	/**
	 * Get the club selector form element HTML
	 *
	 * @return void
	 */
	protected function getClubElement() : void
	{
		if( !Settings::i()->clubs or !Club::clubs( Member::loggedIn(), NULL, 'name', TRUE, array(), Settings::i()->clubs_require_approval ? array( 'approved=1' ) : NULL, TRUE ) )
		{
			Output::i()->json( array( 'field' => '' ) );
		}

		$clubOptions = array();
		foreach ( Club::clubs( Member::loggedIn(), NULL, 'name', TRUE, array(), Settings::i()->clubs_require_approval ? array( 'approved=1' ) : NULL ) as $club )
		{
			$clubOptions[ "c{$club->id}" ] = $club->name;
		}
		
		if ( isset( Request::i()->stream_club_select ) )
		{
			switch ( Request::i()->stream_club_select )
			{
				case 'all':
					$value = 0;
					break;
				
				case 'none':
					$value = array();
					break;
					
				default:
					$value = explode( ',', Request::i()->stream_club_filter );
					break;
			}
		}
		else
		{
			$value = 0;
		}
										
		$field = new Select( 'stream_club_filter_dummy', $value, FALSE, array( 'options' => $clubOptions, 'parse' => 'normal', 'multiple' => TRUE, 'noDefault' => TRUE, 'unlimited' => 0, 'unlimitedLang' => 'stream_club_select_all' ), NULL, NULL, NULL, 'stream_club_filter' );
		$field->label = Member::loggedIn()->language()->addToStack('stream_club_filter');
		
		Output::i()->json( array( 'field' => Theme::i()->getTemplate('streams')->filterFormClubs( $field ) ) );
	}
	
	/**
	 * Build form
	 *
	 * @param	Stream	$stream	The stream
	 * @return	string
	 */
	protected function _buildForm( Stream &$stream ) : string
	{
		/* Build form */
		$form = new Form( 'stream', 'continue', ( $stream->id ? $stream->url() : NULL ) );
		$form->class = 'ipsForm--vertical ipsForm--steam-form ipsForm--noLabels';
				
		$stream->form( $form, 'Text', !$stream->id );
		$redirectAfterSave = FALSE;		
		
		/* Note if it's custom */
		if ( $stream->member && Member::loggedIn()->member_id )
		{
			$form->hiddenValues['__custom_stream'] = TRUE;
		}
		
		if ( $stream->member )
		{
			$form->hiddenValues['__stream_owner'] = $stream->member;
		}
		
		if ( $stream->default_view )
		{
			$form->hiddenValues['stream_default_view'] = $stream->default_view;
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* As the node container form elements are not in the actual form, we need to work some magic. 
				And by magic, I do mean a sort of hacky thing. First, if we are editing a stream, we need to get our existing stream filters
				and put these into an array. Then we loop over the input and overwrite the existing filters with what was submitted. If the user
				never loaded the container filters we'll retain what we had (expected) but if they did and adjusted or removed container filters
				then we'll update with what they submitted (expected).
			*/
			if( $stream->id )
			{
				$currentContainers = $stream->containers ? json_decode( $stream->containers, TRUE ) : array();

				foreach( $currentContainers as $class => $containers )
				{
					$values['stream_containers_' . $class::$title ] = array_combine( $containers, $containers );
				}
			}

			foreach ( Request::i() as $k => $v )
			{
				if ( mb_substr( $k, 0, 18 ) == 'stream_containers_' )
				{
					if( $v )
					{
						$vals = explode( ',', $v );
						$values[ $k ] = array_combine( $vals, $vals );
					}
					elseif( isset( $values[ $k ] ) )
					{
						$values[ $k ] = NULL;
					}
				}
			}

			/* Update only */
			if ( isset( Request::i()->updateOnly ) )
			{
				$formattedValues = $stream->formatFormValues( $values );

				foreach ( array( 'include_comments', 'classes', 'containers', 'clubs', 'ownership', 'custom_members', 'read', 'follow', 'followed_types', 'date_type', 'date_start', 'date_end', 'date_relative_days', 'sort', 'tags', 'solved' ) as $k )
				{
					$requestKey = 'stream_' . $k;

					if ( array_key_exists( $k, $formattedValues ) AND $stream->$k != $formattedValues[ $k ] )
					{
						$stream->$k = $formattedValues[ $k ];
						$stream->baseUrl = $stream->baseUrl->setQueryString( 'stream_' . $k, Request::i()->$requestKey );
					}
				}
			}			
			/* Update & Save */
			else
			{
				if ( !$stream->id )
				{
					$stream->position = Db::i()->select( 'MAX(position)', 'core_streams', array( '`member`=?', Member::loggedIn()->member_id )  )->first() + 1;
					$redirectAfterSave = TRUE;
				}
				else
				{
					if ( !$stream->member or $stream->member != Member::loggedIn()->member_id )
					{
						Output::i()->error( 'no_module_permission', '2C280/9', 403, '' );
					}
				}
			
				$stream->saveForm( $stream->formatFormValues( $values ) );
				
				$this->_rebuildStreams();
				
				if( $redirectAfterSave )
				{
					Output::i()->redirect( $stream->url() );
				}
			}
		}
		
		/* Display */
		return $form->customTemplate( array( Theme::i()->getTemplate( 'streams', 'core' ), $stream->id ? 'filterInlineForm' : 'filterCreateForm' ) );
	}

	/**
	 *
	 */
	protected function subscribe() : void
	{
		/* Get it */
		try
		{
			$stream = Stream::load( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C280/C', 404, '' );
		}

		if( !$stream->canSubscribe() )
		{
			Output::i()->error( 'stream_no_permission', '2C280/D', 403, '' );
		}

		$form = new Form('subscribe', 'stream_subscribe_button');
		$form->class = 'ipsForm--vertical ipsForm--stream-subscribe';
		$form->add( new Radio('stream_subscription_frequency', NULL , TRUE, ['options' => ['daily' => 'stream_daily', 'weekly' => 'stream_weekly']] ));

		if( $values = $form->values() )
		{
			$streamSub = new Subscription();
			$streamSub->member_id = Member::loggedIn()->member_id;
			$streamSub->stream_id = $stream->id;
			$streamSub->frequency = $values['stream_subscription_frequency'];

			/* Set the fake last sent time to get the next run populated correct */
			$streamSub->sent =  $values['stream_subscription_frequency'] == 'daily' ? DateTime::create()->sub( new DateInterval( 'P1D' ) )->getTimestamp() : DateTime::create()->sub( new DateInterval( 'P1W' ) )->getTimestamp();
			$streamSub->added = time();
			$streamSub->save();

			/* Enable the task */
			$taskKey =  $values['stream_subscription_frequency'] == 'daily' ? 'dailyStreamSubscriptions' : 'weeklyStreamSubscriptions';

			Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', $taskKey ) );

			Output::i()->redirect( $stream->url(), 'subscribed' );
		}
		Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );

		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( Output::i()->output  );
		}
		else
		{
			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
		}
	}

	/**
	 * Unsubscribe from a stream
	 */
	protected function unsubscribe() : void
	{
		/* Get it */
		try
		{
			$stream = Stream::load( Request::i()->id );
		}
		catch ( OutOfRangeException| UnderflowException $e )
		{
			Output::i()->error( 'node_error', '2C280/F', 404, '' );
		}
		Request::i()->confirmedDelete( 'stream_unsubscribe', 'confirm_stream_unsubscribe', 'stream_unsubscribe_button');
		if ( $streamSubscription = Subscription::loadByStreamAndMember( $stream ) )
		{
			$streamSubscription->delete();
		}
		else
		{
			Output::i()->error( 'stream_no_permission', '2C280/E', 403, '' );
		}
		
		Output::i()->redirect( $stream->url(), 'unsubscribed' );
 	}

	/**
	 * Unsubscribe from a stream or from all streams from email
	 * If we're logged in, we can send them right to the normal form.
	 * Otherwise, they get a special guest page using the gkey as an authentication key.
	 *
	 * @return void
	 */
	protected function unSubscribeFromEmail() : void
	{
		/* Logged in? */
		if ( Member::loggedIn()->member_id )
		{
			/* Go to the normal page */
			Output::i()->redirect( $this->url->setQueryString('do','unsubscribe')->setQueryString('id', Request::i()->id));
		}

		if ( !empty( Request::i()->gkey ) )
		{
			try
			{
				$current = Db::i()->select( '*', 'core_stream_subscriptions', [ 'id=?', Request::i()->id ])->first();
			}
			catch ( UnderFlowException $e )
			{
				Output::i()->error('node_error', '2C280/E');
			}

			$member = Member::load( $current['member_id'] );

			if ( md5( $member->email . ';' . $member->ip_address . ';' . $member->joined->getTimestamp() ) != Request::i()->gkey )
			{
				Output::i()->error( 'stream_no_permission', '2C280/E', 403, '' );
			}

			$form = new Form( '', 'update_stream_subscriptions' );
			$form->class = 'ipsForm--vertical ipsForm--stream-unsubscribe';

			if ( $streams = Subscription::getSubscribedStreams( $member ) AND $count = count( $streams ) AND $count == 1 )
			{
				$title = Subscription::constructFromData($current)->stream->_title;
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'streamsubscription_guest_thing', FALSE, array('sprintf' => array($title)) );
				$form->add( new Checkbox( 'guest_unsubscribe_single', 'single', FALSE, array('disabled' => true) ) );
				Member::loggedIn()->language()->words['guest_unsubscribe_single'] = Member::loggedIn()->language()->addToStack( 'stream_subscription_unsubscribe_thing', FALSE, array('sprintf' => array($title)) );
			}
			else
			{
				$title = Member::loggedIn()->language()->addToStack( 'stream_subscriptions');
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'streamsubscription_guest_all' );
				$form->add( new Radio( 'guest_unsubscribe_choice', 'single', FALSE, array(
					'options' => array(
						'single' => Member::loggedIn()->language()->addToStack( 'streamsubscription_guest_thing', FALSE, array('sprintf' => array( Subscription::constructFromData($current)->stream->_title  ) ) ),
						'all' => Member::loggedIn()->language()->addToStack( 'streamsubscription_guest_all', FALSE, array('pluralize' => array( $count )) ),
					),
					'descriptions' => array(
						'single' => Member::loggedIn()->language()->addToStack( 'follow_guest_unfollow_thing_desc' ),
						'all' => Member::loggedIn()->language()->addToStack( 'follow_guest_unfollow_all_desc', FALSE, array('sprintf' => array(base64_encode( Url::internal( 'app=core&module=system&controller=followed' ) ))) )
					)
				) ) );
			}

			if ( $values = $form->values() )
			{
				if( isset( $values['guest_unsubscribe_choice'] ) and $values['guest_unsubscribe_choice'] == 'all' )
				{
					foreach ( Subscription::getSubscribedStreams( $member ) as $subscription )
					{
						$subscription->delete();
					}
				}
				else
				{
					$subscription = Subscription::loadByStreamAndMember( Stream::load( $current['stream_id'] ), $member );
					$subscription->delete();
				}
				Output::i()->redirect( Url::internal( '' ), 'unsubscribed' );
			}

			Output::i()->sidebar['enabled'] = FALSE;
			Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			Output::i()->output = Theme::i()->getTemplate( 'streams' )->unsubscribeStream( $title, $member, $form, !isset( Request::i()->activity_stream_subscription ) ? FALSE : Request::i()->activity_stream_subscription );
		}
	}

	
	/**
	 * Rebuild logged in member's streams
	 *
	 * @return	void
	 */
	protected function _rebuildStreams() : void
	{
		$default = Member::loggedIn()->defaultStream;
		Member::loggedIn()->member_streams = json_encode( array( 'default' => $default, 'streams' => iterator_to_array( Db::i()->select( 'id, title', 'core_streams', array( '`member`=?', Member::loggedIn()->member_id ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->setKeyField('id')->setValueField('title') ) ) );
		Member::loggedIn()->save();
	}
}
