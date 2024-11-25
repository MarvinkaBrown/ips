<?php
/**
 * @brief		Forum Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		08 Jan 2014
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\core\DataLayer;
use IPS\core\FrontNavigation;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\forums\Forum as ForumClass;
use IPS\forums\SavedAction;
use IPS\forums\Topic;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function array_merge;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_numeric;
use const IPS\Helpers\Table\SEARCH_SELECT;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forum Index
 */
class forums extends Controller
{
	protected mixed $themeGroup = NULL;

	protected string $nodeClass = 'IPS\forums\Forum';
	
	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage() : void
	{	
		$forum = NULL;
		try
		{
			$this->_forum( ForumClass::loadAndCheckPerms( Request::i()->id ) );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F176/1', 404, '' );
		}
	}

	protected array $childrenIds = [];

	protected function getChildrenIds( $forum ): array
	{
		$this->childrenIds[] = $forum->_id;

		foreach( $forum->children() as $node )
		{
			$this->childrenIds[] = $node->_id;
			foreach( $node->children() as $child )
			{
				$this->getChildrenIds( $child );
			}
		}

		return $this->childrenIds;
	}

	/**
	 * Show Forum
	 *
	 * @param ForumClass $forum	The forum to show
	 * @return	void
	 */
	public function _forum( ForumClass $forum ) : void
	{
		$forum->clubCheckRules();
				
		/* Is simple mode on? If so, redirect to the index page */
		if ( ForumClass::isSimpleView( $forum ) )
		{
			if ( ! isset( Request::i()->url()->hiddenQueryString['rss'] ) )
			{
				Output::i()->redirect( $forum->url(), '', 302 );
			}
		}

		/* Theme */
		$forum->setTheme();
		$this->themeGroup = Theme::i()->getTemplate( 'forums', 'forums', 'front' );
		
		/* Password protected */
		if ( $form = $forum->passwordForm() )
		{
			Output::i()->title = $forum->_title;
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forums', 'forums', 'front' ), 'forumPasswordPopup' ), $forum );
			return;
		}

		/* We can read? */
		if ( $forum->sub_can_post and !$forum->permission_showtopic and !$forum->can('read') )
		{
			Output::i()->error( $forum->errorMessage(), '1F176/3', 403, '' );
		}

        /* Users can see topics posted by other users? */
        $where = array();
        if ( !$forum->memberCanAccessOthersTopics( Member::loggedIn() ) )
        {
            $where[] = array( 'starter_id = ?', Member::loggedIn()->member_id );
        }
		
		$getReactions = $getFirstComment = $getFollowerCount = ( Member::loggedIn()->getLayoutValue('forums_topic') === 'snippet' );

		/* Do view update now - we want to include redirect forums */
		if ( ! Request::i()->isAjax() )
		{
			$forum->updateViews();
		}

		/* Redirect? */
		if ( $forum->redirect_url )
		{
			$forum->redirect_hits++;
			$forum->save();
			Output::i()->redirect( $forum->redirect_url );
		}

		/* Display */
		if ( $forum->isCombinedView() )
		{
			$childrenIds = $this->getChildrenIds( $forum );
			$where = array();

			if ( ForumClass::customPermissionNodes() )
			{
				$where['container'][] = array( 'forums_forums.password IS NULL' );
			}

			if ( !Settings::i()->club_nodes_in_apps OR !Member::loggedIn()->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
			{
				$where['container'][] = array( 'forums_forums.club_id IS NULL' );
			}

			$forumIds = array();
			$ids = $childrenIds;
			$urlParam = 'forumId' . $forum->_id;

			if ( isset( Request::i()->$urlParam ) )
			{
				$ids = explode( ',', Request::i()->$urlParam );
			}
			else if ( isset( Request::i()->cookie['forums_flowIdsRoots'] ) and $cookie = json_decode( Request::i()->cookie['forums_flowIdsRoots'], TRUE ) )
			{
				if ( isset( $cookie[ $forum->_id ] ) )
				{
					if ( is_array( $cookie[ $forum->_id ] ) )
					{
						$ids = $cookie[$forum->_id];
					}
				}
			}

			/* Inline forum IDs? */
			if ( ( is_array( $ids ) and ! count( $ids ) ) )
			{
				/* If we unselect all the filters, then there is nothing to show... */
				$where[] = [ "1=2" ];
			}
			else if ( count( $ids ) )
			{
				foreach( $ids as $id )
				{
					if ( ! in_array( $id, $childrenIds ) )
					{
						continue;
					}

					try
					{
						/* Panic not, they are all loaded into memory at this point */
						$_forum = ForumClass::load( $id );
						$forumIds[] = $_forum->id;

					}
					catch( Exception $ex ) { }
				}

				if ( count( $forumIds ) )
				{
					$where['container'][] = array( Db::i()->in( 'forums_forums.id', array_filter( $forumIds ) ) );
				}
			}

			/* Simplified view */
			$table = new Content( 'IPS\forums\Topic', $forum->url(), $where, $forum, NULL, 'read', TRUE, FALSE, NULL, FALSE, ( $getReactions and Member::loggedIn()->getLayoutValue('forums_topic') == 'snippet' ), ( $getFollowerCount and Member::loggedIn()->getLayoutValue('forums_topic') == 'snippet' ), FALSE );
			$table->tableTemplate = array( Theme::i()->getTemplate( 'index' ), 'simplifiedForumTable' );
			$table->classes = array( 'ipsData--topic-list' );
			$table->limit = Settings::i()->forums_topics_per_page;
			$table->enableRealtime = true;

			if ( Member::loggedIn()->getLayoutValue('forums_topic') == 'snippet' )
			{
				$table->rowsTemplate = array( Theme::i()->getTemplate( 'index' ), 'simplifiedTopicRowSnippet' );
			}
			else
			{
				$table->rowsTemplate = array( Theme::i()->getTemplate( 'index' ), 'simplifiedTopicRow' );
			}

			$table->honorPinned = Settings::i()->forums_fluid_pinned;
			$table->hover = TRUE;
			$table->sortOptions['num_replies']	= $table->sortOptions['num_comments'];
			unset( $table->sortOptions['num_comments'] );

			Output::i()->output = Theme::i()->getTemplate( 'index' )->simplifiedView( $table );

			if( ForumClass::theOnlyForum() === NULL )
			{
				Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'index' )->simplifiedViewForumSidebar( $forum );
			}

			if ( isset( $where['container'] ) )
			{
				$allForumIds = iterator_to_array( Db::i()->select( 'id', 'forums_forums', $where['container'] ) );
				Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack('forums_chosen_forums') ] = array( 'type' => 'forums_topic', 'nodes' => implode( ',', $allForumIds ) );
			}
		}
		else
		{
			/* Init table (it won't show anything until after the password check, but it sets navigation and titles) */
			$table = new Content( 'IPS\forums\Topic', $forum->url(), $where, $forum, NULL, 'view', isset( Request::i()->rss ) ? FALSE : TRUE, isset( Request::i()->rss ) ? FALSE : TRUE, NULL, $getFirstComment, $getFollowerCount, $getReactions );
			$table->tableTemplate = array(Theme::i()->getTemplate( 'forums', 'forums', 'front' ), 'forumTable');
			$table->classes = array('ipsData--topic-list');
			$table->limit = Settings::i()->forums_topics_per_page;
			$table->title = Member::loggedIn()->language()->addToStack('count_topics_in_forum', FALSE, array('pluralize' => array($forum->topics)) );

			if ( Member::loggedIn()->getLayoutValue('forums_topic') == 'snippet' )
			{
				$table->rowsTemplate = array($this->themeGroup, 'topicRowSnippet');
			}
			else
			{
				$table->rowsTemplate = array($this->themeGroup, 'topicRow');
			}

			Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack( 'search_contextual_item_forums' ) ] = array( 'type' => 'forums_topic', 'nodes' => $forum->_id );
		}

		/* If there's only one forum and we're not in a club, and we're not in a sub-forum, we actually don't want the nav */
		if ( $theOnlyForum = ForumClass::theOnlyForum() AND $theOnlyForum->_id == $forum->_id and !$forum->club() )
		{
			Output::i()->breadcrumb = isset( Output::i()->breadcrumb['module'] ) ? array('module' => Output::i()->breadcrumb['module']) : array();
		}
		
		/* We need to shift the breadcrumb if we are in a sub-forum and we have $theOnlyForum */
		if ( $theOnlyForum AND $theOnlyForum->_id != $forum->_id )
		{
			array_shift( Output::i()->breadcrumb );
			array_shift( Output::i()->breadcrumb );
		}

		$table->hover = TRUE;
		if ( isset( $table->sortOptions['num_comments'] ) )
		{
			$table->sortOptions['num_replies'] = $table->sortOptions['num_comments'];
			unset( $table->sortOptions['num_comments'] );
		}

		/* Custom Search */
		$filterOptions = array(
			'all' => 'all_topics',
			'open' => 'open_topics',
			'poll' => 'poll',
			'locked' => 'locked_topics',
			'moved' => 'moved_topics',
		);
		$timeFrameOptions = array(
			'show_all' => 'show_all',
			'today' => 'today',
			'last_5_days' => 'last_5_days',
			'last_7_days' => 'last_7_days',
			'last_10_days' => 'last_10_days',
			'last_15_days' => 'last_15_days',
			'last_20_days' => 'last_20_days',
			'last_25_days' => 'last_25_days',
			'last_30_days' => 'last_30_days',
			'last_60_days' => 'last_60_days',
			'last_90_days' => 'last_90_days',
		);

		if ( Member::loggedIn()->member_id )
		{
			$filterOptions['starter'] = 'topics_i_started';
			$filterOptions['replied'] = 'topics_i_posted_in';

			if ( Member::loggedIn()->member_id and Member::loggedIn()->last_visit )
			{
				$timeFrameOptions['since_last_visit'] = Member::loggedIn()->language()->addToStack( 'since_last_visit', FALSE, array('sprintf' => array(DateTime::ts( (int) Member::loggedIn()->last_visit ))) );
			}
		}

		if ( $forum->forums_bitoptions['bw_solved_set_by_moderator'] )
		{
			$table->filters = array(
				'solved_topics' => 'topic_answered_pid>0',
				'unsolved_topics' => 'topic_answered_pid=0',
			);
		}

		/* Are we a moderator? */
		if ( Topic::modPermission( 'unhide', NULL, $forum ) )
		{
			$filterOptions['queued_topics'] = 'queued_topics';
			$filterOptions['queued_posts'] = 'queued_posts';

			$table->filters['filter_hidden_topics'] = 'approved=-1';
			$table->filters['filter_hidden_posts_in_topics'] = 'topic_hiddenposts=1';
		}

		/* Assignments */
		if( $forum->forums_bitoptions['bw_enable_assignments'] AND Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			if( Topic::modPermission( 'assign', null, $forum ) or $teams = Member::loggedIn()->teams() or Member::loggedIn()->totalAssignments() )
			{
				$table->filters['filter_assigned_topics'] = 'assignment_id>0';
			}
		}

		/* Are we filtering by queued topics or posts? */
		if ( Request::i()->filter == 'queued_topics' or Request::i()->filter == 'queued_posts' )
		{
			Request::i()->advanced_search_submitted = 1;
			Request::i()->csrfKey = Session::i()->csrfKey;
			Request::i()->topic_type = Request::i()->filter;
		}

		$table->advancedSearch = array(
			'topic_type' => array(SEARCH_SELECT, array('options' => $filterOptions)),
			'sort_by' => array(SEARCH_SELECT, array('options' => array(
				'last_post' => 'last_post',
				'replies' => 'replies',
				'views' => 'views',
				'topic_title' => 'topic_title',
				'last_poster' => 'last_poster',
				'topic_started' => 'topic_started',
				'topic_starter' => 'topic_starter',
			))
			),
			'sort_direction' => array(SEARCH_SELECT, array('options' => array(
				'asc' => 'asc',
				'desc' => 'desc',
			))
			),
			'time_frame' => array(SEARCH_SELECT, array('options' => $timeFrameOptions)),
		);
		$table->advancedSearchCallback = function ( $table, $values ) {
			/* Type */
			switch ( $values['topic_type'] )
			{
				case 'open':
					$table->where[] = array('state=?', 'open');
					break;
				case 'poll':
					$table->where[] = array('poll_state<>0');
					break;
				case 'locked':
					$table->where[] = array('state=?', 'closed');
					break;
				case 'moved':
					$table->where[] = array('state=?', 'link');
					break;
				case 'starter':
					$table->where[] = array('starter_id=?', Member::loggedIn()->member_id);
					break;
				case 'replied':
					$table->joinComments = TRUE;
					$table->where[] = array('forums_posts.author_id=?', Member::loggedIn()->member_id);
					break;
				case 'answered':
					$table->where[] = array('topic_answered_pid<>0');
					break;
				case 'unanswered':
					$table->where[] = array('topic_answered_pid=0');
					break;
				case 'queued_topics':
					$table->where[] = array('approved=0');
					break;
				case 'queued_posts':
					$table->where[] = array('topic_queuedposts>0');
					break;
			}

			if ( !isset( $values['sort_by'] ) )
			{
				$values['sort_by'] = 'forums_topics.last_post';
			}

			/* Sort */
			switch ( $values['sort_by'] )
			{
				case 'last_post':
				case 'views':
					$table->sortBy = 'forums_topics.' . $values['sort_by'];
					break;
				case 'replies':
					$table->sortBy = 'posts';
					break;
				case 'topic_title':
				case 'title':
					$table->sortBy = 'title';
					break;
				case 'last_poster':
					$table->sortBy = 'last_poster_name';
					break;
				case 'topic_started':
					$table->sortBy = 'start_date';
					break;
				case 'topic_starter':
					$table->sortBy = 'starter_name';
					break;
			}
			$table->sortDirection = $values['sort_direction'];

			/* Cutoff */
			$days = NULL;

			if ( isset( $values['time_frame'] ) )
			{
				switch ( $values['time_frame'] )
				{
					case 'today':
						$days = 1;
						break;
					case 'last_5_days':
						$days = 5;
						break;
					case 'last_7_days':
						$days = 7;
						break;
					case 'last_10_days':
						$days = 10;
						break;
					case 'last_15_days':
						$days = 15;
						break;
					case 'last_20_days':
						$days = 20;
						break;
					case 'last_25_days':
						$days = 25;
						break;
					case 'last_30_days':
						$days = 30;
						break;
					case 'last_60_days':
						$days = 60;
						break;
					case 'last_90_days':
						$days = 90;
						break;
					case 'since_last_visit':
						$table->where[] = array('forums_topics.last_post>?', Member::loggedIn()->last_visit);
						break;
				}
			}

			if ( $days !== NULL )
			{
				$table->where[] = array('forums_topics.last_post>?', DateTime::create()->sub( new DateInterval( 'P' . $days . 'D' ) )->getTimestamp());
			}
		};
		Request::i()->sort_direction = Request::i()->sort_direction ?: mb_strtolower( $table->sortDirection );

		/* Saved actions */
		foreach ( SavedAction::actions( $forum ) as $action )
		{
			$table->savedActions[ $action->_id ] = $action->_title;
		}
		
		/* RSS */
		if ( Settings::i()->forums_rss and $forum->topics )
		{
			/* Show the link */
			$rssUrl = Url::internal( "app=forums&module=forums&controller=forums&id={$forum->_id}&rss=1", 'front', 'forums_rss', array( $forum->name_seo ) );

			if ( Member::loggedIn()->member_id )
			{
				$key = Member::loggedIn()->getUniqueMemberHash();

				$rssUrl = $rssUrl->setQueryString( array( 'member' => Member::loggedIn()->member_id , 'key' => $key ) );
			}

			$rssTitle = Member::loggedIn()->language()->addToStack( 'forum_rss_title_topics', FALSE, array( 'escape' => true, 'sprintf' => array( $forum->_title ) ) );

			Output::i()->rssFeeds[ $rssTitle ] = $rssUrl;
		}

		/* Online User Location */
		$permissions = $forum->permissions();
		Session::i()->setLocation( $forum->url(), explode( ",", $permissions['perm_view'] ), 'loc_forums_viewing_forum', array( "forums_forum_{$forum->id}" => TRUE ) );
		
		if ( Member::loggedIn()->getLayoutValue('forums_forum') === 'grid' )
		{
			ForumClass::populateFollowerCounts( $forum );
		}

		/* Data Layer Context */
		if ( !Request::i()->isAjax AND Settings::i()->core_datalayer_enabled )
		{
			foreach ( $forum->getDataLayerProperties() as $key => $value )
			{
				DataLayer::i()->addContextProperty( $key, $value );
				DataLayer::i()->addContextProperty( 'sort_direction', Request::i()->sort_direction ?: null );
				$sortby = Request::i()->sort_by ?: null;
				if ( $sortby AND !in_array( $sortby, ['asc', 'desc'] ) )
				{
					$sortby = null;
				}
				DataLayer::i()->addContextProperty( 'sort_by', $sortby );
				DataLayer::i()->addContextProperty( 'page_number', 'page' );
			}
		}
			
		/* Show Forum */
		if ( isset( Request::i()->advancedSearchForm ) )
		{
			Output::i()->output = (string) $table;
			return;
		}

		$forumOutput = '';

		if( $forum->sub_can_post or $forum->isCombinedView() )
		{
			$forumOutput = (string) $table;
		}

		$table = $this->postProcessTable( $table );
		
		/* Set default search to this forum */
		Output::i()->defaultSearchOption = array( 'forums_topic', 'forums_topic_el' );

		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_forum.js', 'forums' ) );

        if ( Application::appIsEnabled('cloud') )
        {
            Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_realtime.js', 'cloud', 'front' ) );
        }

		Output::i()->output	= $this->themeGroup->forumDisplay( $forum, $forumOutput );
	}

	/**
	 * Return the forum table
	 *
	 * @param Content $table
	 * @return Content
	 */
	protected function postProcessTable( Content $table ): Content
	{
		return $table;
	}
	
	/**
	 * Show Club Forums
	 *
	 * @return	void
	 */
	public function clubs() : void
	{
		if ( !Settings::i()->club_nodes_in_apps )
		{
			Output::i()->error( 'node_error', '2F176/4', 404, '' );
		}

		if ( ForumClass::isSimpleView() )
		{
			Output::i()->redirect( Url::internal( 'app=forums&module=forums&controller=index&forumId=clubs', 'front', 'forums' ), '', 302 );
		}
		
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('club_node_forums') );
		Output::i()->title = Member::loggedIn()->language()->addToStack('club_node_forums');
		Output::i()->output = Theme::i()->getTemplate( 'forums' )->clubForums();
	}

	/**
	 * Add Topic
	 *
	 * @return	void
	 */
	protected function add() : void
	{
		if ( !isset( Request::i()->id ) )
		{
			$this->_selectForum();
			return;
		}

		try
		{
			$forum = ForumClass::loadAndCheckPerms( Request::i()->id );
			$forum->setTheme();
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2F173/2', 403, 'no_module_permission_guest' );
		}
		
		$form = Topic::create( $forum );

		$hasModOptions = false;
		
		$canHide = ( Member::loggedIn()->group['g_hide_own_posts'] == '1' or in_array( 'IPS\forums\Topic', explode( ',', Member::loggedIn()->group['g_hide_own_posts'] ) ) );
		if ( Topic::modPermission( 'lock', NULL, $forum ) or
			 Topic::modPermission( 'pin', NULL, $forum ) or
			 Topic::modPermission( 'hide', NULL, $forum ) or
			 $canHide or 
			 Topic::modPermission( 'feature', NULL, $forum ) )
		{
			$hasModOptions = TRUE;
		}
		
		$formTemplate = $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'forums' ), 'createTopicForm' ), $forum, $hasModOptions, NULL );
		
		$guestPostBeforeRegister = ( !Member::loggedIn()->member_id ) ? !$forum->can( 'add', Member::loggedIn(), FALSE ) : FALSE;
		$modQueued = Topic::moderateNewItems( Member::loggedIn(), $forum, $guestPostBeforeRegister );
		if ( $guestPostBeforeRegister or $modQueued )
		{
			$formTemplate = Theme::i()->getTemplate( 'forms', 'core' )->postingInformation( $guestPostBeforeRegister, $modQueued, TRUE ) . $formTemplate;
		}

		$title = 'create_new_topic';

		/* Online User Location */
		$permissions = $forum->permissions();
		Session::i()->setLocation( $forum->url(), explode( ",", $permissions['perm_view'] ), 'loc_forums_creating_topic', array( "forums_forum_{$forum->id}" => TRUE ) );
		
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->linkTags['canonical'] = (string) $forum->url()->setQueryString( 'do', 'add' );
		Output::i()->output = Theme::i()->getTemplate( 'submit' )->createTopic( $formTemplate, $forum, $title );
		Output::i()->title = Member::loggedIn()->language()->addToStack( $title );
		
		if ( $club = $forum->club() )
		{
			FrontNavigation::$clubTabActive = TRUE;
			Output::i()->breadcrumb = array();
			Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') );
			Output::i()->breadcrumb[] = array( $club->url(), $club->name );
			Output::i()->breadcrumb[] = array( $forum->url(), $forum->_title );
			
			if ( Settings::i()->clubs_header == 'sidebar' )
			{
				Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'clubs', 'core' )->header( $club, $forum, 'sidebar' );
			}
		}
		elseif ( !ForumClass::theOnlyForum() )
		{
			try
			{
				foreach( $forum->parents() as $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
			}
			catch( UnderflowException $e ) {}
			Output::i()->breadcrumb[] = array( $forum->url(), $forum->_title );
		}
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('create_new_topic' ) );
	}
	
	/**
	 * Create Category Selector
	 *
	 * @return	void
	 */
	protected function createMenu() : void
	{
		$this->_selectForum();
	}
	
	/**
	 * Mark Read
	 *
	 * @return	void
	 */
	protected function markRead() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$forum		= ForumClass::load( Request::i()->id );
			$returnTo	= $forum;

			if( Request::i()->return )
			{
				$returnTo	= ForumClass::load( Request::i()->return );
			}
			
			if ( Request::i()->fromForum )
			{
				Topic::markContainerRead( $forum, NULL, FALSE );
			}
			else
			{
				Topic::markContainerRead( $forum );
			}

			Output::i()->redirect( ( Request::i()->return OR Request::i()->fromForum ) ? $returnTo->url() : Url::internal( 'app=forums&module=forums&controller=index', NULL, 'forums' ) );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2F173/3', 403, 'no_module_permission_guest' );
		}
	}

	/**
	 * Populate the combined fluid view start modal form elements
	 * @param array $nodes
	 * @param array $disabled
	 * @param ForumClass $node
	 * @param int $depth
	 * @return void
	 */
	protected function _selectForumPopulate( array &$nodes, array &$disabled, ForumClass $node, int $depth = 0 ) : void
	{
		if ( $node->can('view') )
		{
			$nodes[ $node->_id ] = str_repeat( '- ', $depth ) . $node->_title;

			if ( ! $node->can('add') )
			{
				$disabled[] = $node->_id;
			}

			foreach( $node->children() AS $child )
			{
				$this->_selectForumPopulate( $nodes, $disabled, $child, $depth + 1 );
			}
		}
	}

	/**
	 * Shows the forum selector for creating a topic outside of specific forum
	 *
	 * @return	void
	 */
	protected function _selectForum() : void
	{
		if( !ForumClass::canOnAny( 'add' ) )
		{
			Output::i()->error( 'no_module_permission', '2F176/5', 403, 'no_module_permission_guest' );
		}

		$form = new Form( 'select_forum', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--select-forum ipsForm--noLabels';

		if ( isset( Request::i()->root ) )
		{
			$root = ForumClass::load( Request::i()->root );
			$options = [];
			$disabled = [];
			$this->_selectForumPopulate( $options, $disabled, $root );

			$form->add( new Select( 'forum', $root->id, TRUE, [
				'options' => $options,
				'disabled' => $disabled
			] ) );
		}
		else
		{
			$form->add( new Node( 'forum', NULL, TRUE, array(
				'url' => Url::internal( 'app=forums&module=forums&controller=forums&do=createMenu' ),
				'class' => 'IPS\forums\Forum',
				'permissionCheck' => function ( $node ) {
					if ( $node->can( 'view' ) )
					{
						if ( $node->can( 'add' ) )
						{
							return TRUE;
						}

						return FALSE;
					}

					return NULL;
				},
				'clubs' => Settings::i()->club_nodes_in_apps
			) ) );
		}
		if ( $values = $form->values() )
		{
			if ( is_numeric( $values['forum'] ) )
			{
				$forum = ForumClass::load( $values['forum'] );
			}
			else
			{
				$forum = $values['forum'];
			}

			Output::i()->redirect( $forum->url()->setQueryString( 'do', 'add' ) );
		}
		
		Output::i()->title			= Member::loggedIn()->language()->addToStack( 'select_forum' );
		Output::i()->breadcrumb[]	= array( NULL, Member::loggedIn()->language()->addToStack( 'select_forum' ) );
		Output::i()->output		= Theme::i()->getTemplate( 'forums' )->forumSelector( $form );
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete(): void
	{
		if( Application::appIsEnabled( 'cms' ) )
		{
			/* Load forum and verify that it is not used for comments */
			/* @var ForumClass $nodeClass */
			$nodeClass = $this->nodeClass;
			if ( Request::i()->subnode )
			{
				$nodeClass = $nodeClass::$subnodeClass;
			}

			try
			{
				$node = $nodeClass::load( Request::i()->id );
			}
			catch (OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S101/J', 404, '' );
			}

			/* Is any database synced with this forum? */
			if ( $db = $node->isUsedByCms() )
			{
				Member::loggedIn()->language()->words['cms_forum_used'] = sprintf( Member::loggedIn()->language()->get('cms_forum_used'), $db->recordWord( 1 ) );

				Output::i()->error( 'cms_forum_used', '1T371/1', 403, '' );
			}
		}
	}

	/**
	 * Toggle the forum view
	 *
	 * @return void
	 */
	protected function setMethod() : void
	{
		Session::i()->csrfCheck();

		Member::loggedIn()->setLayoutValue( 'forums_topic', Request::i()->method );

		try
		{
			Output::i()->redirect( ForumClass::load( Request::i()->id )->url() );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2F177/1', 404, '' );
		}
	}
}
