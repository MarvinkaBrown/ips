<?php
/**
 * @brief		Topic View
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		08 Jan 2014
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DateTimeInterface;
use Exception;
use IPS\Application;
use IPS\Content\Comment;
use IPS\Content\Controller;
use IPS\Content\Item;
use IPS\Content\Reaction;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\forums\Forum;
use IPS\forums\SavedAction;
use IPS\forums\Topic as TopicClass;
use IPS\forums\Topic\Post;
use IPS\Helpers\Form;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use OutOfRangeException;
use Throwable;
use function array_merge;
use function count;
use function defined;
use function in_array;
use function intval;
use const IPS\LARGE_TOPIC_REPLIES;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Topic View
 */
class topic extends Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static string $contentModel = 'IPS\forums\Topic';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_topic.js', 'forums' ), Output::i()->js('front_helpful.js', 'core' ) );

        if ( Application::appIsEnabled('cloud') )
        {
            Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_realtime.js', 'cloud', 'front' ) );
        }
        
		parent::execute();
	}

	/**
	 * View Topic
	 *
	 * @return    mixed
	 * @throws Exception
	 */
	protected function manage() : mixed
	{
		/* Load topic */
		$topic = parent::manage();
		Member::loggedIn()->language()->words['submit_comment'] = Member::loggedIn()->language()->addToStack( 'submit_reply', FALSE );

		/* If it failed, it might be because we want a password */
		if ( $topic === NULL )
		{
			$forum = NULL;
			try
			{
				$topic = TopicClass::load( Request::i()->id );
				$forum = $topic->container();
				if ( $forum->can('view') and !$forum->loggedInMemberHasPasswordAccess() )
				{
					Output::i()->redirect( $forum->url()->setQueryString( 'topic', Request::i()->id ) );
				}
				
				if ( !$topic->canView() )
				{
					if ( IPS::classUsesTrait( $topic, 'IPS\Content\Hideable' ) and $topic->hidden() )
					{
						/* If the item is hidden we don't want to show the custom no permission error as the conditions may not apply */
						Output::i()->error( 'node_error', '2F173/O', 404, '' );
					}
					else
					{
						Output::i()->error(  $forum ? $forum->errorMessage() : 'node_error_no_perm', '2F173/H', 403, '' );
					}
				}
			}
			catch ( OutOfRangeException $e )
			{
				/* Nope, just a generic no access error */
				Output::i()->error( 'node_error', '2F173/1', 404, '' );
			}
		}
		
		$topic->container()->clubCheckRules();
		
		/* If there's only one forum and we're not in a club, and we're not in a sub-forum, we actually don't want the nav */
		$theOnlyForum = NULL;
		if ( count( Output::i()->breadcrumb ) AND !$topic->container()->club() AND ( ( $theOnlyForum = Forum::theOnlyForum() AND $theOnlyForum->_id == $topic->container()->_id ) or Forum::isSimpleView() ) )
		{
			$topicBreadcrumb = array_pop( Output::i()->breadcrumb );
			Output::i()->breadcrumb = isset( Output::i()->breadcrumb['module'] ) ? array( 'module' => Output::i()->breadcrumb['module'] ) : array();
			Output::i()->breadcrumb[] = $topicBreadcrumb;
		}
		
		/* We need to shift the breadcrumb if we are in a sub-forum and we have $theOnlyForum */
		if ( $theOnlyForum AND $theOnlyForum->_id != $topic->container()->_id )
		{
			array_shift( Output::i()->breadcrumb );
			array_shift( Output::i()->breadcrumb );
		}

		/* Legacy findpost redirect */
		if ( Request::i()->findpost )
		{
			Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => Request::i()->findpost ) ), NULL, 301 );
		}
		elseif ( Request::i()->p )
		{
			Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => Request::i()->p ) ), NULL, 301 );
		}
		elseif ( Request::i()->pid )
		{
			Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => Request::i()->pid ) ), NULL, 301 );
		}
		
		if ( Request::i()->view )
		{
			$this->_doViewCheck();
		}

		/* If the topic is locked and scheduled to unlock already, or vice versa, do that */
		if( $topic->locked() && $topic->topic_open_time && $topic->topic_open_time < time() )
		{
			$topic->state = 'open';
			$topic->save();
		}
		elseif( !$topic->locked() && $topic->topic_close_time && $topic->topic_close_time < time() )
		{
			$topic->state = 'closed';
			$topic->save();
		}

		/* If this is an AJAX request fetch the comment form now. The HTML will be cached so calling here and then again in the template has no overhead
			and this is necessary if you entered into a topic with &queued_posts=1, approve the posts, then try to reply. Otherwise, clicking into the
			editor produces an error when the getUploader=1 call occurs, and submitting a reply results in an error. */
		if ( Request::i()->isAjax() and ( !isset( Request::i()->preview ) OR !Request::i()->preview ) )
		{
			$topic->commentForm();
		}
	
		/* AJAX hover preview? */
		if ( Request::i()->isAjax() and Request::i()->preview )
		{
			$postClass = '\IPS\forums\Topic\Post';

			if( $topic->isArchived() )
			{
				$postClass = '\IPS\forums\Topic\ArchivedPost';
			}
			
			/* If this topic was moved or merged, load that up in case someone loads the preview after that happens but before they reload the page */
			$previewTopic = $topic;
			if ( in_array( $topic->state, array( 'merged', 'link' ) ) )
			{
				$movedTo = explode( '&', $topic->moved_to );
				
				try
				{
					$previewTopic = TopicClass::loadAndCheckPerms( $movedTo[0] );
				}
				catch( OutOfRangeException $e )
				{
					/* I can't help you */
					Output::i()->error( 'node_error', '2F173/Q', 404, '' );
				}
			}

			$firstPost = $postClass::load( $previewTopic->topic_firstpost );
			
			$topicOverview = array( 'firstPost' => array( 'first_post', $firstPost ) );

			if ( $previewTopic->posts > 1 )
			{
				$latestPost = $previewTopic->comments( 1, 0, 'date', 'DESC' );
				$topicOverview['latestPost'] = array('latest_post', $latestPost );
			
				$timeLastRead = $previewTopic->timeLastRead();
				if ( $timeLastRead instanceof DateTime AND $previewTopic->unread() !== 0 )
				{
					$firstUnread = $previewTopic->comments( 1, NULL, 'date', 'asc', NULL, NULL, $timeLastRead );
					if( $firstUnread instanceof Post AND $firstUnread->date !== $latestPost->date AND $firstUnread->date !== $firstPost->date )
					{
						$topicOverview['firstUnread'] = array( 'first_unread_post_hover', $previewTopic->comments( 1, NULL, 'date', 'asc', NULL, NULL, $timeLastRead ) );
					}
				}			
			}

			if ( $previewTopic->topic_answered_pid )
			{
				$topicOverview['bestAnswer'] = array( 'best_answer_post', Post::load( $previewTopic->topic_answered_pid ) );
			}

			Output::i()->sendOutput( Theme::i()->getTemplate( 'forums' )->topicHover( $previewTopic, $topicOverview ) );
		}
		
		$topic->container()->setTheme();
		
		/* Watch for votes */
		if ( $poll = $topic->getPoll() )
		{
			$poll->attach( $topic );
		}
				
		/* How are we sorting posts? */
		$offset = NULL;
		$order = 'date';
		$orderDirection = 'asc';
		$where = NULL;
		$firstPost = null;
		$paginationKeys = array( 'sortby' );

		if ( $topic->hasSummary() AND Request::i()->topicSummary )
		{
			$pagesCount = 1;
		}
		else if( Request::i()->show == 'helpful' )
		{
			$where = [ '( topic_id=? AND ( new_topic=? OR pid IN(?) ) )', $topic->tid , 1, Db::i()->select( 'comment_id', 'core_solved_index', [ 'app=? and item_id=? and type=?', 'forums', $topic->tid, 'helpful' ] ) ];

			$pagesCount = ceil( Db::i()->select( 'COUNT(*)', 'forums_posts', $where )->first() / $topic->getCommentsPerPage() );
			$paginationKeys = array( 'queued_posts', 'sortby' );
		}
		else
		{
			$customPaginationWhere = array();
			if( TopicClass::modPermission( 'unhide', NULL, $topic->container() ) AND Request::i()->queued_posts )
			{
				if ( $topic->isArchived() )
				{
					$where = 'archive_queued=1';
				}
				else
				{
					$where = 'queued=1';
				}
				array_unshift( $paginationKeys, 'queued_posts' );

				$customPaginationWhere[] = array( 'topic_id=? AND queued=1', $topic->id );
			}
			$customPaginationWhere = Bridge::i()->modifyTopicCommentFilter( $topic, $customPaginationWhere );
			if ( !empty( $customPaginationWhere ) )
			{
				$customPaginationWhere[] = array( "topic_id=?", $topic->id );
				$authorCol = Post::$databasePrefix . Post::$databaseColumnMap['author'];
				if ( $topic->canViewHiddenComments() )
				{
					$col = $topic::$databasePrefix . Post::$databaseColumnMap['hidden'];
					$customPaginationWhere[] = array( "( {$col}=0 OR ( {$col}=1 AND ( {$authorCol}=" . Member::loggedIn()->member_id . " ) ) )" );
				}
				else
				{
					$col = Post::$databasePrefix . Post::$databaseColumnMap['hidden'];
					$customPaginationWhere[] = array( "{$col}=0" );
				}
				$pagesCount = ceil( Db::i()->select( 'COUNT(*)', 'forums_posts', $customPaginationWhere )->first() / $topic->getCommentsPerPage() );
			}
			else
			{
				$pagesCount = $topic->commentPageCount();
			}
		}

		if ( Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) )
		{
			$firstPost = $topic->comments( 1, 0 );
			$page = ( isset( Request::i()->page ) ) ? intval( Request::i()->page ) : 1;

			if( $page < 1 )
			{
				$page = 1;
			}

			$offset	= ( ( $page - 1 ) * $topic::getCommentsPerPage() ) + 1;

			if ( ( ! isset( Request::i()->sortby ) or Request::i()->sortby != 'date' ) )
			{
				if ( $topic->isArchived() )
				{
					$order = "archive_is_first desc, archive_bwoptions";
					$orderDirection = 'desc';
				}
				else
				{
					$order = "new_topic DESC, post_bwoptions DESC, post_field_int DESC, post_date";
					$orderDirection = 'ASC';
				}
			}

			$pagination = ( $topic->commentPageCount() > 1 ) ? $topic->commentPagination( array( 'sortby' ), 'pagination', $pagesCount ) : NULL;
		}
		else
		{
			$pagination = ( $pagesCount > 1 ) ? $topic->commentPagination( $paginationKeys, 'pagination', $pagesCount ) : NULL;
		}

		$where = Bridge::i()->modifyTopicCommentFilter( $topic, $where );

		$comments = $topic->comments( NULL, $offset, $order, $orderDirection, NULL, NULL, NULL, $where, FALSE, ( isset( Request::i()->showDeleted ) ) );
		$current  = current( $comments );
		reset( $comments );

		if( !count( $comments ) and !Request::i()->show == 'helpful' and ! Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) )
		{
			Output::i()->error( 'no_posts_returned', '2F173/L', 404, '' );
		}

		/* Mark read */
		if( !$topic->isLastPage() AND $topic->unread() !== 0 )
		{
			$maxTime	= 0;

			foreach( $comments as $comment )
			{
				$maxTime	= ( $comment->mapped('date') > $maxTime ) ? $comment->mapped('date') : $maxTime;
			}

			if( $topic->timeLastRead() === NULL OR $maxTime > $topic->timeLastRead()->getTimestamp() )
			{
				$topic->markRead( NULL, $maxTime );
			}
		}
		elseif( $topic->isLastPage() )
		{
			/* See if the last comment is hidden or pending approval. If so, force the topic read because it won't be done so automatically. */
			$lastComment = end( $comments );

			if( $lastComment and $lastComment->hidden() !== 0 )
			{
				$topic->markRead( NULL, NULL, NULL, TRUE );
			}

			reset( $comments );
		}

		/* Preload the follow state of the viewing member and any community experts in this forum, this avoids multiple queries */
		$viewerIsFollowingTheseExperts = [];
		if ( Member::loggedIn()->member_id and $experts = $topic->container()->getExperts() )
		{
			$authorsInCommentsThatAreExperts = [];
			foreach ( $comments as $comment )
			{
				if ( in_array( $comment->mapped('author'), $experts ) )
				{
					$authorsInCommentsThatAreExperts[] = $comment->mapped('author');
				}
			}

			if ( count( $authorsInCommentsThatAreExperts ) )
			{
				/* Get the follow state of viewer to experts */
				foreach( Member::loggedIn()->isFollowing( $authorsInCommentsThatAreExperts ) as $expertId => $following )
				{
					if( $following )
					{
						$viewerIsFollowingTheseExperts[] = $expertId;
					}
				}
			}
		}

		/* A convenient hook point to do any further set up */
		$this->finishManage( $topic );

		$votes		= array();
		$topicVotes = array();

		/* Online User Location */
		Session::i()->setLocation( $topic->url(), ( $topic->container()->password or !$topic->container()->can_view_others ) ? 0 : $topic->onlineListPermissions(), 'loc_forums_viewing_topic', array( $topic->title => FALSE ) );

		/* Next unread */
		try
		{
			$nextUnread	= $topic->containerHasUnread();
		}
		catch( Exception $e )
		{
			$nextUnread	= NULL;
		}

		/* Sidebar? */
		if ( $topic->showSummaryOnDesktop() == 'sidebar' )
		{
			Output::i()->sidebar['contextual'] = Theme::i()->getTemplate( 'topics' )->activity( $topic, 'sidebar' );
		}

		/* Add Json-LD */
		$isQuestion = ( $topic->isSolved() );

		/* The purpose of QAPage is to provide data on the question and suggested answer (if avaiable), or all comments if there is no answer.
		   However, a very large topic that has not been solved, is likely to confuse Google ("The QAPage type indicates that the page is focused on a specific question and its answer(s)"),
		   so if the topic is very long, we will only show this metadata if the topic has been solved. */
		/** @noinspection PhpUndefinedConstantInspection */
		if ( $isQuestion and ( $topic->posts < LARGE_TOPIC_REPLIES or $topic->isSolved() ) )
		{
			Output::i()->jsonLd['topic'] = array(
				'@context'		=> "https://schema.org",
				'@type'			=> 'QAPage',
				'@id'			=> (string) $topic->url(),
				'url'			=> (string) $topic->url(),
				'mainEntity' => [
					'@type'	=> "Question",
					'name'	=> $topic->title,
					'text'  => $firstPost ? $firstPost->truncated( TRUE, NULL ) : $topic->comments( 1, 0 )->truncated( TRUE, NULL ),
					'dateCreated' => DateTime::ts( $topic->start_date )->format( DateTimeInterface::ATOM ),
					'answerCount' => ( ( $topic->posts > 1 ) ? ( $topic->posts - 1 ) : 0 ),
					'author' => [
						'@type' => 'Person',
						'name' => $topic->author()->name,
						'image'	=> $topic->author()->get_photo( TRUE, TRUE )
					]
				]
			);

			if( $topic->author()->member_id )
			{
				Output::i()->jsonLd['topic']['mainEntity']['author']['url'] = (string) $topic->author()->url();
			}

			if( $topic->topic_answered_pid )
			{
				try
				{
					$postClass = '\IPS\forums\Topic\Post';
		
					if( $topic->isArchived() )
					{
						$postClass = '\IPS\forums\Topic\ArchivedPost';
					}
					
					$answer = $postClass::load( $topic->topic_answered_pid );
					
					/* Set up our column names */
					/* @var $databaseColumnMap array */
					$authorIdColumn = $answer::$databaseColumnMap['author'];
					$dateColumn = $answer::$databaseColumnMap['date'];
					
					if ( $truncatedAnswer = $answer->truncated( TRUE, NULL ) )
					{
						Output::i()->jsonLd['topic']['mainEntity']['acceptedAnswer'] = array(
							'@type'		=> 'Answer',
							'text'		=> $truncatedAnswer,
							'url'		=> (string) $answer->url(),
							'dateCreated' => DateTime::ts( $answer->$dateColumn )->format( DateTimeInterface::ATOM ),
							'upvoteCount' => ( $topic->isArchived() ) ? $answer->field_int : $answer->post_field_int,
							'author'	=> array(
								'@type'		=> 'Person',
								'name'		=> Member::load( $answer->$authorIdColumn )->name,
								'image'		=> Member::load( $answer->$authorIdColumn )->get_photo( TRUE, TRUE )
							),
						);
	
						if( $answer->author_id )
						{
							Output::i()->jsonLd['topic']['mainEntity']['acceptedAnswer']['author']['url']	= (string) Member::load( $answer->$authorIdColumn )->url();
						}
					}
				}
				catch( OutOfRangeException $e ){}
			}
			else if ( $topic->posts > 1 )
			{
				/* We have no accepted answer, but we have replies, so google still wants to see here some meta data, so let's show the last comment https://developers.google.com/search/docs/appearance/structured-data/qapage#answercount */
				try
				{
					$lastComment = $topic->comments( 1, 0, 'date', 'desc' );

					/* Set up our column names */
					/* @var $databaseColumnMap array */
					$authorIdColumn = $lastComment::$databaseColumnMap['author'];
					$dateColumn = $lastComment::$databaseColumnMap['date'];

					Output::i()->jsonLd['topic']['mainEntity']['suggestedAnswer'] = array(
						'@type'		=> 'Answer',
						'text'		=> $lastComment->truncated( TRUE, NULL ),
						'url'		=> (string) $lastComment->url(),
						'dateCreated' => DateTime::ts( $lastComment->$dateColumn )->format( DateTime::ISO8601 ),
						'upvoteCount' => ( $topic->isArchived() ) ? $lastComment->field_int : $lastComment->post_field_int,
						'author'	=> array(
						'@type'		=> 'Person',
						'name'		=> Member::load( $lastComment->$authorIdColumn )->name,
						'image'		=> Member::load( $lastComment->$authorIdColumn )->get_photo( TRUE, TRUE )
						),
					);
				}
				catch( OutOfRangeException $e ){}
			}
		}
		else
		{
			Output::i()->jsonLd['topic'] = array(
				'@context'		=> "https://schema.org",
				'@type'			=> 'DiscussionForumPosting',
				'@id'			=> (string) $topic->url(),
				'isPartOf'		=> array(
					'@id' => Settings::i()->base_url . '#website'
				),
				'publisher'		=> array(
					'@id' => Settings::i()->base_url . '#organization'
				),
				'url'			=> (string) $topic->url(),
				'discussionUrl'	=> (string) $topic->url(),
				'mainEntityOfPage' => array(
					'@type'	=> 'WebPage',
					'@id'	=> (string) $topic->url()
				),
				'pageStart'		=> 1,
				'pageEnd'		=> $topic->commentPageCount(),
			);
		}

		/* Add in comments */
		if( $topic->posts > 1 )
		{
			if ( $isQuestion )
			{
				Output::i()->jsonLd['topic']['mainEntity']['suggestedAnswer'] = [];
			}
			else
			{
				Output::i()->jsonLd['topic']['comment'] = [];
			}

			$i = 0;
			$commentJson = [];
			foreach( $comments as $comment )
			{
				/* Set up our column names */
				$idColumn = $comment::$databaseColumnId;
				/* @var array $databaseColumnMap */
				$authorIdColumn = $comment::$databaseColumnMap['author'];
				$dateColumn = $comment::$databaseColumnMap['date'];
				
				// Don't include the first post as a "comment"
				if( $comment->$idColumn == $topic->topic_firstpost )
				{
					continue;
				}

				// Don't include the answer as the suggested answer
				if( $isQuestion and ( $comment->$idColumn === $topic->mapped('solved_comment_id') ) )
				{
					continue;
				}

				if ( $truncatedComment = $comment->truncated( TRUE, NULL ) )
				{
					$url = $topic->url()->setPage( 'page', Request::i()->page );

					$commentJson[$i] = array(
						'@type' => $isQuestion ? 'Answer' : 'Comment',
						'@id' => (string)$url->setFragment( 'comment-' . $comment->$idColumn ),
						'url' => (string)$url->setFragment( 'comment-' . $comment->$idColumn ),
						'author' => array(
							'@type' => 'Person',
							'name' => Member::load( $comment->$authorIdColumn )->name,
							'image' => Member::load( $comment->$authorIdColumn )->get_photo( TRUE, TRUE )
						),
						'dateCreated' => DateTime::ts( $comment->$dateColumn )->format( DateTimeInterface::ATOM ),

						'text' => $truncatedComment,
					);

					if ( $comment->$authorIdColumn )
					{
						$commentJson[$i]['author']['url'] = (string)Member::load( $comment->$authorIdColumn )->url();
					}

					if( $isQuestion )
					{
						$commentJson[$i]['upvoteCount'] = ( $topic->isArchived() ) ? $comment->field_int : $comment->post_field_int;
					}
					else
					{
						$commentJson[$i]['upvoteCount'] = $comment->reactionCount();
					}

					$i++;
				}
			}

			if ( $isQuestion )
			{
				Output::i()->jsonLd['topic']['mainEntity']['suggestedAnswer'] = $commentJson;
			}
			else
			{
				Output::i()->jsonLd['topic']['comment'] = $commentJson;
			}
		}

		/* Do we have a real author */
		if( $topic->starter_id )
		{
			Output::i()->jsonLd['topic']['author']['url'] = (string) Member::load( $topic->starter_id )->url();

			Output::i()->jsonLd['topic']['publisher']['member'] = array(
				'@type'		=> "Person",
				'name'		=> Member::load( $topic->starter_id )->name,
				'image'		=> (string) Member::load( $topic->starter_id )->get_photo( TRUE, TRUE ),
				'url'		=> (string) Member::load( $topic->starter_id )->url(),
			);
		}

		/* Enable caching for archived topics */
		if( $topic->isArchived() AND !Member::loggedIn()->member_id )
		{
			/* We do not want to use the \IPS\CACHE_PRIVATE_TIMEOUT constant here, as we explicitly want to cache archived topics for longer times */
			$httpHeaders = array( 'Expires'		=> DateTime::create()->add( new DateInterval( 'PT12H' ) )->rfc1123() ,
								  'Cache-Control'	=> 'no-cache="Set-Cookie", max-age=' . ( 60 * 60 * 12 ) . ", s-maxage=" . ( 60 * 60 * 12 ) . ", public, stale-if-error, stale-while-revalidate" );

			Output::i()->httpHeaders += $httpHeaders;
		}

		/* $current might be null if the topic has no replies AND we are featuring the first post */
		if( empty( $current ) and Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) )
		{
			$current = $firstPost;
		}

		if( $current )
		{
			Output::i()->jsonLd['topic'] = array_merge_recursive( array(
				'name'			=> $topic->mapped('title'),
				'headline'		=> $topic->mapped('title'),
				'text'			=> $current->truncated( TRUE, NULL ),
				'dateCreated'	=> DateTime::ts( $topic->start_date )->format( DateTimeInterface::ATOM ),
				'datePublished'	=> DateTime::ts( $topic->start_date )->format( DateTimeInterface::ATOM ),
				'dateModified'	=> DateTime::ts( $topic->last_post )->format( DateTimeInterface::ATOM ),
				/* Image is required, but we don't have "topic images", so we'll use topic starter's profile photo for now */
				'image'			=> (string) Member::load( $topic->starter_id )->get_photo( TRUE, TRUE ),
				'author'		=> array(
					'@type'		=> 'Person',
					'name'		=> Member::load( $topic->starter_id )->name,
					'image'		=> Member::load( $topic->starter_id )->get_photo( TRUE, TRUE )
				),
				'interactionStatistic'	=> array(
					array(
						'@type'					=> 'InteractionCounter',
						'interactionType'		=> "https://schema.org/ViewAction",
						'userInteractionCount'	=> $topic->views
					),
					array(
						'@type'					=> 'InteractionCounter',
						'interactionType'		=> "https://schema.org/CommentAction",
						'userInteractionCount'	=> $topic->posts - 1 // We subtract one to account for the "first post"
					)
				)
			), Output::i()->jsonLd['topic'] );
		}

		if( !$topic->isArchived() )
		{
			Output::i()->jsonLd['topic']['interactionStatistic'][] = [
				'@type'					=> 'InteractionCounter',
				'interactionType'		=> "http://schema.org/FollowAction",
				'userInteractionCount'	=> $topic->followersCount()
			];
		}

		/* Add og:image meta tags */
		if( count( $file = $topic->imageAttachments(1 ) ) )
		{
			$object = File::get( 'core_Attachment', $file[0]['attach_location'] );
			Output::i()->metaTags['og:image'] = (string) $object->url->setScheme( ( mb_substr( Settings::i()->base_url, 0, 5 ) === 'https' ) ? 'https' : 'http' );
		}

		/* Noindex if helpful filter applied */
		if( isset( Request::i()->show ) )
		{
			Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Set default search to this topic */
		Output::i()->defaultSearchOption = array( 'forums_topic', 'forums_topic_el' );

		/* Show topic */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'topics.css', 'forums' ) );
		Output::i()->output .= Theme::i()->getTemplate( 'topics' )->topic( $topic, $comments, $votes, $nextUnread, $pagination, $topicVotes, $firstPost, $viewerIsFollowingTheseExperts );
		return null;
	}

	/**
	 * Check our view method and act accordingly (redirect if appropriate)
	 *
	 * @return	void
	 */
	protected function _doViewCheck() : void
	{
		try
		{
			/* @var TopicClass $class */
			$class	= static::$contentModel;
			$topic	= $class::loadAndCheckPerms( Request::i()->id );
			
			switch( Request::i()->view )
			{
				case 'getnewpost':
					Output::i()->redirect( $topic->url( 'getNewComment' ) );
				break;
				
				case 'getlastpost':
					Output::i()->redirect( $topic->url( 'getLastComment' ) );
				break;
			}
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/F', 403, '' );
		}
	}
	
	/**
	 * Edit topic
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		try
		{
			/* @var TopicClass $class */
			$class = static::$contentModel;
			$topic = $class::loadAndCheckPerms( Request::i()->id );
			$forum = $topic->container();
			$forum->setTheme();
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2F173/D', 403, 'no_module_permission_guest' );
		}
		
		// We check if the form has been submitted to prevent the user loosing their content
		if ( isset( Request::i()->form_submitted ) )
		{
			if ( ! $topic->couldEdit() )
			{
				Output::i()->error( 'edit_no_perm_err', '2F173/E', 403, '' );
			}
		}
		else
		{
			if ( ! $topic->canEdit() )
			{
				Output::i()->error( 'edit_no_perm_err', '2F173/E', 403, '' );
			}
		}
		
		$formElements = $class::formElements( $topic, $forum );

		$hasModOptions = FALSE;
		/* We used to just check against the ability to lock, however this may not be enough - a moderator could pin, for example, but not lock */
		foreach( array( 'lock', 'pin', 'feature' ) AS $perm )
		{
			if ( $class::modPermission( $perm, NULL, $forum ) )
			{
				$hasModOptions = TRUE;
				break;
			}
		}
		if( $topic->canHide() )
		{
		    $hasModOptions = TRUE;
		}
		
		$form = $topic->buildEditForm();
		
		if ( $values = $form->values() )
		{
			if ( $topic->canEdit() )
			{
				/* @var $databaseColumnMap array */
				$titleField = $topic::$databaseColumnMap['title'];
				$oldTitle = $topic->$titleField;

                $topic->processBeforeEdit( $values );
				$topic->processForm( $values );
				$topic->save();
				$topic->processAfterEdit( $values );

				/* Moderator log */
				$toLog = array( $topic::$title => FALSE, $topic->url()->__toString() => FALSE, $topic::$title => TRUE, $topic->mapped( 'title' ) => FALSE );
					
				if ( $oldTitle != $topic->$titleField )
				{
					$toLog[ $oldTitle ] = false; 
				}
				
				Session::i()->modLog( 'modlog__item_edit', $toLog, $topic );

				Output::i()->redirect( $topic->url() );
			}
			else
			{
				$form->error = Member::loggedIn()->language()->addToStack('edit_no_perm_err');
			}
		}

		$formTemplate = $form->customTemplate( array( Theme::i()->getTemplate( 'submit', 'forums' ), 'createTopicForm' ), $forum, $hasModOptions, $topic );

		$title = 'edit_topic';

		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->output = Theme::i()->getTemplate( 'submit' )->createTopic( $formTemplate, $forum, $title );
		Output::i()->title = Member::loggedIn()->language()->addToStack( $title );
		
		if ( !Forum::theOnlyForum() and ! Forum::isSimpleView() )
		{
			try
			{
				foreach( $forum->parents() AS $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				Output::i()->breadcrumb[] = array( $forum->url(), $forum->_title );
			}
			catch( Exception $e ) {}
		}
		
		Output::i()->breadcrumb[] = array( $topic->url(), $topic->mapped('title') );
		Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( $title ) );
	}

	/**
	 * Unarchive
	 *
	 * @return	void
	 */
	public function unarchive() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
			if ( !$topic->canUnarchive() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/B', 404, '' );
		}
		
		$topic->topic_archive_status = TopicClass::ARCHIVE_RESTORE;
		$topic->save();
		
		/* Make sure the task is enabled */
		Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'unarchive' ) );

		/* Log */
		Session::i()->modLog( 'modlog__unarchived_topic', array( $topic->url()->__toString() => FALSE, $topic->mapped( 'title' ) => FALSE ), $topic );
		
		Output::i()->redirect( $topic->url() );
	}

	/**
	 * Remove the archive exclude flag
	 *
	 * @return void
	 */
	public function removeArchiveExclude() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
			if ( !$topic->canRemoveArchiveExcludeFlag() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/P', 404, '' );
		}

		$topic->topic_archive_status = TopicClass::ARCHIVE_NOT;
		$topic->save();

		/* Log */
		Session::i()->modLog( 'modlog__removed_archive_exclude_topic', array( $topic->url()->__toString() => FALSE, $topic->mapped( 'title' ) => FALSE ), $topic );

		Output::i()->redirect( $topic->url() );
	}
	
	/**
	 * Rate Question
	 *
	 * @return	void
	 */
	public function rateQuestion() : void
	{
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Get the question */
		try
		{
			$question = TopicClass::loadAndCheckPerms( Request::i()->id );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/8', 404, '' );
		}
		
		/* Voting up or down? */
		$rating = intval( Request::i()->rating );
		if ( $rating !== 1 and $rating !== -1 )
		{
			Output::i()->error( 'form_bad_value', '2F173/A', 403, '' );
		}
		
		/* Check we can cast this vote */
		if ( !$question->canVote( $rating ) )
		{
			Output::i()->error( 'no_module_permission', '2F173/9', 403, '' );
		}
		
		/* If we have an existing vote, remove it first */
		$ratings = $question->votes();
		if ( isset( $ratings[ Member::loggedIn()->member_id ] ) )
		{
			Db::i()->delete( 'forums_question_ratings', array( 'topic=? AND `member`=?', $question->tid, Member::loggedIn()->member_id ) );
		}

		/* Revoting for the same thing you already voted for should remove your vote - so don't insert if we voted for the same thing we did before */
		if ( !isset( $ratings[ Member::loggedIn()->member_id ] ) OR $ratings[ Member::loggedIn()->member_id ] != $rating )
		{
			Db::i()->insert( 'forums_question_ratings', array(
				'topic'		=> $question->tid,
				'forum'		=> $question->forum_id,
				'member'	=> Member::loggedIn()->member_id,
				'rating'	=> $rating,
				'date'		=> time()
			), TRUE );
		}
		
		/* Rebuild count */
		$question->question_rating = Db::i()->select( 'SUM(rating)', 'forums_question_ratings', array( 'topic=?', $question->tid ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$question->save();
		
		/* Redirect back */
		Output::i()->redirect( $question->url() );
	}
	
	/**
	 * Rate Answer
	 *
	 * @return	void
	 */
	public function rateAnswer() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$question = TopicClass::loadAndCheckPerms( Request::i()->id );
			$answer = Post::loadAndCheckPerms( Request::i()->answer );
		}
		catch ( Exception $e )
		{
			Output::i()->error( 'node_error', '2F173/4', 404, '' );
		}

		$rating = intval( Request::i()->rating );

		if ( !$answer->item()->can('read') or !$answer->canVote( $rating ) )
		{
			Output::i()->error( 'no_module_permission', '2F173/5', 403, '' );
		}
		
		$rating = intval( Request::i()->rating );
		if ( $rating !== 1 and $rating !== -1 )
		{
			Output::i()->error( 'form_bad_value', '2F173/6', 403, '' );
		}

		$ratings = $question->answerVotes( Member::loggedIn() );

		/* If we've already rated the answer, remove that first */
		Db::i()->delete( 'forums_answer_ratings', array( 'topic=? AND post=? AND `member`=?', $question->tid, $answer->pid, Member::loggedIn()->member_id ) );

		/* Revoting for the same thing you already voted for should remove your vote - so don't insert if we voted for the same thing we did before */
		if ( !isset( $ratings[ $answer->pid ] ) OR $ratings[ $answer->pid ] != $rating )
		{
			Db::i()->insert( 'forums_answer_ratings', array(
				'post'		=> $answer->pid,
				'topic'		=> $question->tid,
				'member'	=> Member::loggedIn()->member_id,
				'rating'	=> $rating,
				'date'		=> time()
			), TRUE );
		}

		$answer->post_field_int = (int) Db::i()->select( 'SUM(rating)', 'forums_answer_ratings', array( 'post=?', $answer->pid ), NULL, NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$answer->save();

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'votes' => $answer->post_field_int, 'canVoteUp' => $answer->canVote(1), 'canVoteDown' => $answer->canVote(-1) ) );
		}
		else
		{
			Output::i()->redirect( $answer->url() );
		}
	}
	
	/**
	 * Set Best Answer
	 *
	 * @return	void
	 */
	public function bestAnswer() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
			$post = Post::loadAndCheckPerms( Request::i()->answer );
			
			if ( !$topic->canSetBestAnswer() )
			{
				throw new OutOfRangeException;
			}
			
			if ( $post->item() !== $topic )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/7', 404, '' );
		}

		$topic->toggleSolveComment( $post->pid, TRUE );
		
		/* Log */
		if ( Member::loggedIn()->modPermission('can_set_best_answer') )
		{
			Session::i()->modLog( 'modlog__best_answer_set', array( $post->pid => FALSE ), $topic );
		}
		
		Output::i()->redirect( $post->url() );
	}
	
	/**
	 * Unset Best Answer
	 *
	 * @return	void
	 */
	public function unsetBestAnswer() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
			$post = Post::loadAndCheckPerms( Request::i()->answer );
			
			if ( !$topic->canSetBestAnswer() )
			{
				throw new OutOfRangeException;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F173/G', 404, '' );
		}

		if ( $post->item() !== $topic )
		{
			throw new OutOfRangeException;
		}

		try
		{
			$topic->toggleSolveComment( $post->pid, FALSE );
			
			if ( Member::loggedIn()->modPermission('can_set_best_answer') )
			{
				Session::i()->modLog( 'modlog__best_answer_unset', array( $post->pid => FALSE ), $topic );
			}
		}
		catch ( Exception $e ) {}
	
		Output::i()->redirect( $post->url() );
	}
	
	/**
	 * Saved Action
	 *
	 * @return	void
	 */
	public function savedAction() : void
	{
		try
		{
			Session::i()->csrfCheck();
			
			$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
			$action = SavedAction::load( Request::i()->action );
			$action->runOn( $topic );
			
			/* Log */
			Session::i()->modLog( 'modlog__saved_action', array( 'forums_mmod_' . $action->mm_id => TRUE, $topic->url()->__toString() => FALSE, $topic->mapped( 'title' ) => FALSE ), $topic );
			Output::i()->redirect( $topic->url() );
		}
		catch ( LogicException $e )
		{
			
		}
	}

	/**
	 * Mark Topic Read
	 *
	 * @return	void
	 */
	public function markRead() : void
	{
		Session::i()->csrfCheck();
		
		try
		{
			$topic = TopicClass::load( Request::i()->id );
			$topic->markRead();

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( "OK" );
			}
			else
			{
				Output::i()->redirect( $topic->url() );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'no_module_permission', '2F173/C', 403, 'no_module_permission_guest' );
		}
	}
	
	/**
	 * We need to use the custom widget poll template for ajax methods
	 *
	 * @return void
	 */
	public function widgetPoll() : void
	{
		try
		{
			$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
		}
		catch( OutOfRangeException $ex )
		{
			Output::i()->error( 'node_error', '2F173/N', 403, '' );
		}
		
		$poll  = $topic->getPoll();
		$poll->displayTemplate = array( Theme::i()->getTemplate( 'widgets', 'forums', 'front' ), 'pollWidget' );
		$poll->url = $topic->url();
		
		Output::i()->output .= Theme::i()->getTemplate( 'widgets', 'forums', 'front' )->poll( $topic, $poll );
	}

	/**
	 * Show a single comment requested by ajax
	 *
	 * @return	void
	 */
	public function ajaxShowComment() : void
	{
		try
		{
			if ( ! Request::i()->isAjax() )
			{
				throw new BadMethodCallException();
			}

			Session::i()->csrfCheck();

			try
			{
				$topic = TopicClass::loadAndCheckPerms( Request::i()->id );
			}
			catch( OutOfRangeException $ex )
			{
				Output::i()->error( 'node_error', '2F173/N', 403, '' );
			}

			$comment = Post::load( Request::i()->showComment );

			if ( ! $comment->canView() )
			{
				throw new BadMethodCallException();
			}

			Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( Theme::i()->getTemplate( 'global', 'core' )->commentContainer( $topic, $comment ), 200, 'text/html' ) );
		}
		catch( Exception $e )
		{
			
		}
	}
	
	/**
	 * Edit Comment/Review
	 *
	 * @param	string					$commentClass	The comment/review class
	 * @param	Comment	$comment		The comment/review
	 * @param	Item		$item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _edit( string $commentClass, Comment $comment, Item $item ) : void
	{
		Member::loggedIn()->language()->words['edit_comment']		= Member::loggedIn()->language()->addToStack( 'edit_reply', FALSE );

		parent::_edit( $commentClass, $comment, $item );
	}
	
	/**
	 * Stuff that applies to both comments and reviews
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 */
	public function __call( string $method, mixed $args )
	{
		$class = static::$contentModel;
		
		try
		{
			/* @var TopicClass $class */
			$item = $class::load( Request::i()->id );
			if ( !$item->canView() )
			{
				$forum = $item->container();
				Output::i()->error( $forum ? $forum->errorMessage() : 'node_error_no_perm', '2F173/K', 403, '' );
			}
			
			if ( $item->isArchived() )
			{
				$class::$commentClass = $class::$archiveClass;
			}
			
			return parent::__call( $method, $args );
		}
		catch( OutOfRangeException $e )
		{
			if ( isset( Request::i()->do ) AND Request::i()->do === 'findComment' AND isset( Request::i()->comment ) )
			{
				try
				{
					/* @var Comment $commentClass */
					$commentClass = $class::$commentClass;
					$comment = $commentClass::load( Request::i()->comment );
					$topic   = TopicClass::load( $comment->topic_id );
					
					Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => Request::i()->comment ) ), NULL, 301 );
				}
				catch( Exception $e )
				{
					Output::i()->error( 'node_error', '2F173/M', 404, '' );
				}
			}
		}
		catch ( Exception $e )
		{
			Log::log( $e, 'topic_call' );
			Output::i()->error( 'node_error', '2F173/I', 404, '' );
		}
		
		return null;
	}

	/**
	 * Form for splitting
	 *
	 * @param Item $item The item
	 * @param null $comment
	 * @return    Form
	 */
	protected function _splitForm( Item $item, $comment = NULL  ) : Form
	{
		$form = parent::_splitForm( $item, $comment );

		if ( isset( $form->elements['']['topic_create_state'] ) )
		{
			unset( $form->elements['']['topic_create_state'] );
		}
		
		return $form;
	}

    protected function finishManage( Item $item ): void
    {
        Bridge::i()->topicsFinishManage( $item );
    }

    /**
     * @return void
     */
    protected function splitQuestion(): void
    {
        Bridge::i()->topicsSplitQuestion( $this );
    }

	/**
	 * @return void
	 */
    protected function getQuestionQuoteDataForEditor(): void
    {
        Bridge::i()->getQuestionQuoteDataForEditor();
    }

	/**
	 * Find a Comment / Review (do=findComment/findReview)
	 *
	 * @param	string		$commentClass	The comment/review class
	 * @param 	Comment 	$comment		The comment/review
	 * @param 	Item 		$item			The item
	 *
	 * @return	void
	 */
	public function _find( string $commentClass, Comment $comment, Item $item ) : void
	{
		if ( Bridge::i()->featureIsEnabled( 'topic_summary' ) )
		{
			$idField = $comment::$databaseColumnId;
			Bridge::i()->trackPostRankingEvent( $comment->$idField, 'linked' );
		}
		parent::_find( $commentClass, $comment, $item );
	}

	/**
	 * React to a comment/review
	 *
	 * @param string $commentClass	The comment/review class
	 * @param Comment $comment		The comment/review
	 * @param Item $item			The item
	 * @return	void
	 * @throws	LogicException
	 */
	protected function _react( string $commentClass, Comment $comment, Item $item ): void
	{
		if ( Bridge::i()->featureIsEnabled( 'topic_summary' ) )
		{
			try
			{
				Session::i()->csrfCheck();
				Reaction::load( Request::i()->reaction ); // we do this to make sure the reaction exists; At this point in the core method the comment is reacted upon @todo consider making this part of the listener system
				$idField = $comment::$databaseColumnId;
				Bridge::i()->trackPostRankingEvent( $comment->$idField, 'reaction' );
			}
			catch ( Throwable ) {}
		}
		parent::_react( $commentClass, $comment, $item );
	}

	/**
	 * Add or remove the post from the summary
	 * @return void
	 */
	protected function addOrRemovePostFromSummary() : void
	{
		Bridge::i()->addOrRemovePostFromSummary();
	}
}