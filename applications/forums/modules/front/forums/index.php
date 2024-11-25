<?php
/**
 * @brief		index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		28 Jul 2014
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\forums\Forum;
use IPS\Helpers\Table\Content;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * index
 */
class index extends Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		if ( isset( Request::i()->forumId ) and ! Forum::isSimpleView() )
		{
			/* This is a simple view URL, but we're not using simple view, so redirect */
			$ids = explode( ',', Request::i()->forumId );
			$firstId = array_shift( $ids );
			if ( $firstId )
			{
				try
				{
					$forum = Forum::loadAndCheckPerms( $firstId );
					Output::i()->redirect( $forum->url(), '', 302 );
				}
				catch( Exception $ex ) { }
			}
		}
		
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('front_browse.js', 'gallery' ) );
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js('front_forum.js', 'forums' ) );

        if ( Application::appIsEnabled('cloud') )
        {
            Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_realtime.js', 'cloud', 'front' ) );
        }
		
		parent::execute();
	}
	
	/**
	 * Handle forum things that are directed here when simple view is on
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 */
	public function __call( string $method, mixed $args )
	{
		if ( Forum::isSimpleView() and isset( Request::i()->do ) and isset( Request::i()->forumId ) and ! mb_stristr( Request::i()->forumId, ',' ) )
		{
			/* If we have a specific do action that this controller does not handle, then we really want the full forum view to handle it */
			try
			{
				/* Panic not, they are all loaded into memory at this point */
				$controller = new forums( $this->url );
				Request::i()->id = Request::i()->forumId;
				$controller->execute();
				
			}
			catch( Exception $ex ) { }
		}
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Load into memory */
		Forum::loadIntoMemory();
			
		/* Is there only one forum? But don't redirect if it's simple view mode... */
		if ( $theOnlyForum = Forum::theOnlyForum() AND !Forum::isSimpleView() )
		{
			$controller = new forums( $this->url );
			$controller->_forum( $theOnlyForum );
			return;
		}
		
		/* Prepare output */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'global_core.js', 'core', 'global' ) );
		if ( Forum::isSimpleView() )
		{
			Output::i()->title = ( isset( Request::i()->page ) AND Request::i()->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( 'forums' ), Request::i()->page ) ) ) : Member::loggedIn()->language()->addToStack( 'forums' );
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'forums' );
		}
		Output::i()->linkTags['canonical'] = (string) Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' );
		Output::i()->metaTags['og:title'] = Settings::i()->board_name;
		Output::i()->metaTags['og:type'] = 'website';
		Output::i()->metaTags['og:url'] = (string) Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' );
		
		/* Set Online Location */
		$permissions = Dispatcher::i()->module->permissions();
		Session::i()->setLocation( Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' ), explode( ',', $permissions['perm_view'] ), 'loc_forums_index' );
		
		/* Display */
		if ( Forum::isSimpleView() )
		{
			$where = array();
			
			if ( Forum::customPermissionNodes() )
			{
				$where = array( 'container' => array( array( 'forums_forums.password IS NULL' ) ) );
			}
			
			if ( !Settings::i()->club_nodes_in_apps OR !Member::loggedIn()->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) )
			{
				$where['container'][] = array( 'forums_forums.club_id IS NULL' );
			}
			$forumIds = array();
			$map = array();
			$ids = array();
			
			if ( isset( Request::i()->forumId ) )
			{
				$ids = explode( ',', Request::i()->forumId );
			}
			else if ( isset( Request::i()->cookie['forums_flowIds'] ) )
			{
				$ids = explode( ',', Request::i()->cookie['forums_flowIds'] );
			}

			/* Inline forum IDs? */
			if ( count( $ids ) )
			{
				$toUnset = [];
				foreach( $ids as $id )
				{
					try
					{
						if ( $id == 'clubs' )
						{
							$map['clubs'] = 'clubs';
							foreach ( Forum::clubNodes() as $child )
							{
								$forumIds[ $child->id ] = $child->id;
							}
						}
						else
						{						
							/* Panic not, they are all loaded into memory at this point */
							$forum = Forum::load( $id );
	
							$map[ $forum->parent_id ][] = $forum->_id;
							$forumIds[] = $forum->id;
						}
					}
					catch( Exception $ex )
					{
						$toUnset[] = $id;
					}
				}

				/* Redirect to a page with valid forum ids */
				if( $ids and count($forumIds ) AND $toUnset )
				{
					$newUrl = $this->url;
					$newUrl = $newUrl->setQueryString( 'forumId', implode( ',', array_diff( $ids, $toUnset ) ) );
					Output::i()->redirect( $newUrl );
				}

				if ( count( $forumIds ) )
				{
					$where['container'][] = array( Db::i()->in( 'forums_forums.id', array_filter( $forumIds ) ) );
				}
			}
						
			/* Simplified view */
			$table = new Content( 'IPS\forums\Topic', Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' ), $where, NULL, NULL, 'read' );
			$table->tableTemplate = array( Theme::i()->getTemplate( 'index' ), 'simplifiedForumTable' );
			$table->classes = array( 'ipsData--topic-list' );
			$table->limit = Settings::i()->forums_topics_per_page;

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

			if( Forum::theOnlyForum() === NULL )
			{
				Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'index' )->simplifiedViewSidebar( $forumIds, $map );
			}

			if ( isset( $where['container'] ) )
			{
				$allForumIds = iterator_to_array( Db::i()->select( 'id', 'forums_forums', $where['container'] ) );
				Output::i()->contextualSearchOptions[ Member::loggedIn()->language()->addToStack('forums_chosen_forums') ] = array( 'type' => 'forums_topic', 'nodes' => implode( ',', $allForumIds ) );
			}					
		}
		else
		{
			/* Merge in follower counts to the immediately visible forums */
			if ( Member::loggedIn()->getLayoutValue('forums_forum') )
			{
				Forum::populateFollowerCounts();
			}

			Output::i()->output = Theme::i()->getTemplate( 'index' )->index();
		}
	}
}