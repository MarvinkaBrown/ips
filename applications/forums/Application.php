<?php
/**
 * @brief		Forums Application Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	Forums
 * @since		07 Jan 2014
 * @version		
 */
 
namespace IPS\forums;

use DateInterval;
use Exception;
use IPS\Application as SystemApplication;
use IPS\Content\Filter;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\Dispatcher;
use IPS\forums\Topic\Post;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Log;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\Rss;
use OutOfRangeException;
use function array_merge;
use function IPS\Cicloud\getForumArchiveWhere;
use function is_numeric;

/**
 * Forums Application Class
 */
class Application extends SystemApplication
{

	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init() : void
	{
		/* Handle RSS requests */
		if ( Request::i()->module == 'forums' and Request::i()->controller == 'forums' and isset( Request::i()->rss ) )
		{
			$member = NULL;
			if( Request::i()->member AND Request::i()->key )
			{
				$member = Member::load( Request::i()->member );
				if( !Login::compareHashes( $member->getUniqueMemberHash(), (string) Request::i()->key ) )
				{
					$member = NULL;
				}
			}

			$this->sendForumRss( $member ?? new Member );

			if( !Member::loggedIn()->group['g_view_board'] )
			{
				Output::i()->error( 'node_error', '2F219/1', 404, '' );
			}
		}
	}

	/**
	 * Send the forum's RSS feed for the indicated member
	 *
	 * @param Member $member		Member
	 * @return	void
	 */
	protected function sendForumRss( Member $member ) : void
	{
		try
		{
			$forum = Forum::load( Request::i()->id );

			if( !$forum->can( 'view', $member ) )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			/* We'll let the regular controller handle the error */
			return;
		}

		/* RSS feeds are not available if the setting is off, or there is nothing to include */
		if ( !Settings::i()->forums_rss OR !$forum->topics )
		{
			return;
		}

        /* Users can see topics posted by other users? Also, exclude moved links. */
        $where = array( array( 'forums_topics.moved_to IS NULL' ), array( 'forums_topics.forum_id=?', $forum->id ) );
        if ( !$forum->memberCanAccessOthersTopics( $member ) )
        {
            $where[] = array( 'starter_id = ?', $member->member_id );
        }

		/* Init table (it won't show anything until after the password check, but it sets navigation and titles) */
		$iterator = Topic::getItemsWithPermission( $where, 'forums_topics.last_post DESC', Settings::i()->forums_topics_per_page, 'view', Filter::FILTER_PUBLIC_ONLY, 0, $member, FALSE, FALSE, FALSE, FALSE, NULL, $forum, TRUE, TRUE, TRUE, FALSE );

		/* Set the title */
		$rssTitle = sprintf( $member->language()->get( 'forum_rss_title_topics'), $member->language()->get( "forums_forum_{$forum->id}" ) );

		/* Can we view the content (permission_showtopic may allow us to view the list, but not the content)? */
		$canViewContent = $forum->can('read', $member );

		/* Build the document */
		$document = Rss::newDocument( $forum->url(), $rssTitle, $rssTitle );
		foreach ( $iterator as $topic )
		{
			try
			{
				$content = NULL;
				if ( $canViewContent )
				{
					/* @var $topic Topic */
					$content = $topic->content();
					Output::i()->parseFileObjectUrls( $content );
				}

				$document->addItem( $topic->title, $topic->url(), $canViewContent ? $topic->content() : NULL, DateTime::ts( $topic->start_date ), $topic->tid );
			}
			catch ( Exception $e ) { }
		}
		
		/* Display - note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		Output::i()->sendOutput( $document->asXML(), 200, 'text/xml', array(), TRUE );
	}
	
	/**
	 * Archive Query
	 *
	 * @param array $rules	Rules
	 * @return	array
	 */
	public static function archiveWhere( array $rules ): array
	{
		$where = array();
		
		if ( Application::appIsEnabled('cloud') )
		{
			return getForumArchiveWhere();
		}

		foreach ( $rules as $rule )
		{
			$clause = NULL;
			
			switch ( $rule['archive_field'] )
			{
				case 'lastpost':
					/* If the data is bad, log and don't throw an error, but don't allow anything to be archived. */
					if( !$rule['archive_text'] OR !$rule['archive_unit'] )
					{
						Log::log( 'Forum archiving missing time period or archive unit', 'forum_archive' );
						$clause = array( '0=?', '1' );
					}
					else
					{
						$clause = array( '(last_post > 0 AND last_post ' . $rule['archive_value'] . ' ?)', DateTime::create()->sub( new DateInterval( 'P' . trim( $rule['archive_text'] ) . mb_strtoupper( $rule['archive_unit'] ) ) )->getTimestamp() );
					}
					break;
				
				case 'forum':
					if ( $rule['archive_text'] )
					{
						$clause = array( 'forum_id ' . ( $rule['archive_value'] == '+' ? 'IN' : 'NOT IN' ) . '(' . $rule['archive_text'] . ')' );
					}
					break;
					
				case 'pinned':
				case 'featured':
				case 'state':
				case 'approved':
					$clause = array( $rule['archive_field'] . '=?', $rule['archive_value'] );
					break;
				
				case 'poll':
					if ( $rule['archive_value'] )
					{
						$clause = array( 'poll_state>0' );
					}
					else
					{
						$clause = array( '(poll_state=0 or poll_state IS NULL)' );
					}
					break;
					
				case 'post':
				case 'view':
					$clause = array( $rule['archive_field'] . 's' . $rule['archive_value'] . '?', $rule['archive_text'] );
					break;
				
				case 'rating':
					$clause = array( 'ROUND(topic_rating_total/topic_rating_hits)' . $rule['archive_value'] . '?', $rule['archive_text'] );
					break;
				
				case 'member':
					$clause = array( 'starter_id ' . ( $rule['archive_value'] == '+' ? 'IN' : 'NOT IN' ) . '(' . $rule['archive_text'] . ')' );
					break;
				
			}
			
			if ( $clause )
			{
				if ( $rule['archive_skip'] )
				{
					$clause[0] = ( '!(' . $clause[0] . ')' );
				}
				$where[] = $clause;
			}
		}
		
		return $where;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return    string
	 */
	protected function get__icon(): string
	{
		return 'comments';
	}
	
	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther() : void
	{
		Index::i()->index( Topic::load( 1 ) );
		Index::i()->index( Post::load( 1 ) );
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation(): array
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Forums' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters() : void
	{
		/* Convert &showtopic= (link) */
		if ( isset( Request::i()->showtopic ) and is_numeric( Request::i()->showtopic ) )
		{
			$base        = NULL;
			$seoTemplate = NULL;
			$seoTitles   = array();

			try
			{
				$topic = Topic::load( Request::i()->showtopic );

				if ( $topic->canView() )
				{
					$base        = 'front';
					$seoTemplate = 'forums_topic';
					$seoTitles   = array( $topic->title_seo );
				}
			} catch( Exception $e ) {}

			$url = Url::internal( 'app=forums&module=forums&controller=topic&id=' . Request::i()->showtopic, $base, $seoTemplate, $seoTitles );

			if ( isset( Request::i()->p ) or isset( Request::i()->findpost ) or isset( Request::i()->comment ) )
			{
				$url = $url->setQueryString( array( 'do' => 'findComment', 'comment' => Request::i()->p ?: ( Request::i()->findpost ?: Request::i()->comment ) ) );
			}
			elseif ( isset( Request::i()->page ) )
			{
				$url = $url->setPage( 'page', Request::i()->page );
			}
			Output::i()->redirect( $url );
		}

		/* Convert &showforum= */
		if ( isset( Request::i()->showforum ) and is_numeric( Request::i()->showforum ) )
		{
			$base        = NULL;
			$seoTemplate = NULL;
			$seoTitles   = array();

			try
			{
				$forum = Forum::load( Request::i()->showforum );

				if ( $forum->can( 'view' ) )
				{
					$base        = 'front';
					$seoTemplate = 'forums_forum';
					$seoTitles   = array( $forum->name_seo );
				}
			} catch ( Exception $e ) {}

			$url = Url::internal( 'app=forums&module=forums&controller=forums&id=' . Request::i()->showforum, $base, $seoTemplate, $seoTitles );
			Output::i()->redirect( $url );
		}
		
		/* Convert /topic/123-example/&p= */
		if ( isset( Request::i()->p ) AND is_numeric( Request::i()->p ) AND ( Request::i()->url() instanceof Friendly ) AND Request::i()->url()->seoTemplate == 'forums_topic' )
		{
			/* We do this a little differently as the topic seo title is already known at this point */
			try
			{
				$post = Post::loadAndCheckPerms( Request::i()->p );
				Output::i()->redirect( $post->url() );
			}
			catch( Exception $e ) {}
		}
	}

	/**
	 * Returns a list of essential cookies which are set by this app.
	 * Wildcards (*) can be used at the end of cookie names for PHP set cookies.
	 *
	 * @return string[]
	 */
	public function _getEssentialCookieNames(): array
	{
		return [ 'forumpass_*' ];
	}

	/**
	 * Get all possible layout values for this page and app
	 *
	 * @return array
	 */
	public function getThemeLayoutOptionsForThisPage(): array
	{
		$return = [];

		if ( Dispatcher::i()->application->directory === 'forums' and Dispatcher::i()->module->key === 'forums' and Dispatcher::i()->controller === 'index' )
		{
			$return[] = 'forums_forum';
		}
		else if ( Dispatcher::i()->application->directory === 'forums' and Dispatcher::i()->module->key === 'forums' and Dispatcher::i()->controller === 'forums' )
		{
			$return[] = 'forums_topic';
		}
		else if ( Dispatcher::i()->application->directory === 'forums' and Dispatcher::i()->module->key === 'forums' and Dispatcher::i()->controller === 'topic' )
		{
			$return[] = 'forums_post';
			$return[] = 'forum_topic_view_firstpost';
		}

		return $return;
	}

	/**
	 * Output CSS files
	 *
	 * @return void
	 */
	public static function outputCss() : void
	{
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation === 'front' )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'forums.css', 'forums', 'front' ) );
		}
	}
}