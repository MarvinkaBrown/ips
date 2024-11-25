<?php
/**
 * @brief		Topic Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		8 Jan 2014
 */

namespace IPS\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use DateInterval;
use DomainException;
use InvalidArgumentException;
use IPS\cms\Databases;
use IPS\cms\Records;
use IPS\Content\Anonymous;
use IPS\Content\Assignable;
use IPS\Content\Comment;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Followable;
use IPS\Content\FuturePublishing;
use IPS\Content\Helpful;
use IPS\Content\Hideable;
use IPS\Content\Item;
use IPS\Content\Lockable;
use IPS\Content\MetaData;
use IPS\Content\Featurable;
use IPS\Content\Pinnable;
use IPS\Content\Polls;
use IPS\Content\Reactable;
use IPS\Content\Reaction;
use IPS\Content\ReadMarkers;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\Content\Solvable;
use IPS\Content\Statistics;
use IPS\Content\Taggable;
use IPS\Content\ViewUpdates;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Exception;
use IPS\Dispatcher;
use IPS\File;
use IPS\forums\tasks\unarchive;
use IPS\forums\Topic\ArchivedPost;
use IPS\forums\Topic\LiveTopic;
use IPS\forums\Topic\Post;
use IPS\Helpers\Badge;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Password;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Poll;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use RuntimeException;
use SplObserver;
use SplSubject;
use function array_slice;
use function count;
use function defined;
use function func_get_args;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function json_decode;
use const IPS\LARGE_TOPIC_REPLIES;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Topic Model
 */
class Topic extends Item implements Embeddable,
	Filter,
	SplObserver
{
	use LiveTopic,
		Reactable,
		Reportable,
		Statistics,
		Pinnable,
		Anonymous,
		Solvable,
		Followable,
		Lockable,
		FuturePublishing,
		MetaData,
		Polls,
		Shareable,
		Taggable,
		EditHistory,
		ReadMarkers,
		Helpful,
		Hideable,
		ViewUpdates,
		Featurable,
		Assignable
		{
			Polls::canCreatePoll as public _canCreatePoll;
			ReadMarkers::markRead as public _markRead;
			Reactable::reactionClass as public _reactionClass;
			Solvable::toggleSolveComment as protected _toggleSolveComment;
		}
	
	/**
	 * @brief	Not archived
	 */
	const ARCHIVE_NOT = 0;

	/**
	 * @brief	Archiving completed
	 */
	const ARCHIVE_DONE = 1;

	/**
	 * @brief	In the process of being archived
	 */
	const ARCHIVE_WORKING = 2;

	/**
	 * @brief	Excluded from archiving
	 */
	const ARCHIVE_EXCLUDE = 3;

	/**
	 * @brief	Flagged to restore from archive
	 */
	const ARCHIVE_RESTORE = 4;
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'tid';

	/**
	 * @brief	Application
	 */
	public static string $application = 'forums';
	
	/**
	 * @brief	Module
	 */
	public static string $module = 'forums';

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'forums_topics';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = '';
			
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'author'				=> 'starter_id',
		'author_name'			=> 'starter_name',
		'container'				=> 'forum_id',
		'date'					=> 'start_date',
		'title'					=> 'title',
		'num_comments'			=> 'posts',
		'unapproved_comments'	=> 'topic_queuedposts',
		'hidden_comments'		=> 'topic_hiddenposts',
		'first_comment_id'		=> 'topic_firstpost',
		'last_comment'			=> array( 'last_post', 'last_real_post' ),
		'last_comment_by'		=> 'last_poster_id',
		'last_comment_name'		=> 'last_poster_name',
		'views'					=> 'views',
		'approved'				=> 'approved',
		'pinned'				=> 'pinned',
		'poll'					=> 'poll_state',
		'status'				=> 'state',
		'moved_to'				=> 'moved_to',
		'moved_on'				=> 'moved_on',
		'featured'				=> 'featured',
		'state'					=> 'state',
		'updated'				=> 'last_post',
		'meta_data'				=> 'topic_meta_data',
		'solved_comment_id'		=> 'topic_answered_pid',
		'is_anon'				=> 'is_anon',
		'last_comment_anon'		=> 'last_poster_anon',
		'is_future_entry'		=> 'is_future_entry',
		'future_date'           => 'publish_date',
		'last_vote'				=> 'last_vote',
		'assigned'				=> 'assignment_id'
	);

	/**
	 * @brief	Title
	 */
	public static string $title = 'topic';
	
	/**
	 * @brief	Node Class
	 */
	public static ?string $containerNodeClass = 'IPS\forums\Forum';
	
	/**
	 * @brief	[Content\Item]	Comment Class
	 */
	public static ?string $commentClass = 'IPS\forums\Topic\Post';

	/**
	 * @brief	Archived comment class
	 */
	public static string $archiveClass = 'IPS\forums\Topic\ArchivedPost';

	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static bool $firstCommentRequired = TRUE;
	
	/**
	 * @brief	[Content\Comment]	Language prefix for forms
	 */
	public static string $formLangPrefix = 'topic_';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'comment';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'topic';
	
	/**
	 * @brief	Hover preview
	 */
	public ?bool $tableHoverUrl = true;

	/**
	 * @brief Setting name for show_meta
	 */
	public static ?string $showMetaSettingKey = 'forums_topics_show_meta';
	
	/**
	 * Callback from \IPS\Http\Url\Inernal::correctUrlFromVerifyClass()
	 *
	 * This is called when verifying the *the URL currently being viewed* is correct, before calling self::loadFromUrl()
	 * Can be used if there is a more effecient way to load and cache the objects that will be used later on that page
	 *
	 * @param	Url	$url	The URL of the page being viewed, which belongs to this class
	 * @return	void
	 */
	public static function preCorrectUrlFromVerifyClass( Url $url ) : void
	{
		Forum::loadIntoMemory();
	}

	/**
	 * Check the request for legacy parameters we may need to redirect to
	 *
	 * @return	NULL|Url
	 */
	public function checkForLegacyParameters(): ?Url
	{
		/* Check for any changes in the parent, i.e. st=20 */
		$url = parent::checkForLegacyParameters();

		$paramsToSet	= array();
		$paramsToUnset	= array();

		/* view=findpost needs to go to do=findComment */
		if( isset( Request::i()->view ) AND Request::i()->view == 'findpost' )
		{
			$paramsToSet['do']		= 'findComment';
			$paramsToUnset[]		= 'view';
		}

		/* p=123 needs to go to comment=123 */
		if( isset( Request::i()->p ) )
		{
			$paramsToSet['do']		= 'findComment';
			$paramsToSet['comment']		= Request::i()->p;
			$paramsToUnset[]		= 'p';
		}

		/* Did we have any? */
		if( count( $paramsToSet ) )
		{
			if( $url === NULL )
			{
				$url = $this->url();
			}

			if( count( $paramsToUnset ) )
			{
				$url = $url->stripQueryString( $paramsToUnset );
			}

			return $url->setQueryString( $paramsToSet );
		}

		return $url;
	}

	/**
	 * Set custom posts per page setting
	 *
	 * @return int
	 */
	public static function getCommentsPerPage(): int
	{
		return Settings::i()->forums_posts_per_page;
	}

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return    boolean
	 * @see        Post::incrementPostCount
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return FALSE;
	}

	/**
	 * Get items with permission check
	 *
	 * @param array $where Where clause
	 * @param string|null $order MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit Limit clause
	 * @param string $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param int|bool|null $includeHiddenItems Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param int $queryFlags Select bitwise flags
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly If true will return the count
	 * @param array|null $joins Additional arbitrary joins for the query
	 * @param bool|Model $skipPermission If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param bool $joinTags If true, will join the tags table
	 * @param bool $joinAuthor If true, will join the members table for the author
	 * @param bool $joinLastCommenter If true, will join the members table for the last commenter
	 * @param bool $showMovedLinks If true, moved item links are included in the results
	 * @param array|null $location Array of item lat and long
	 * @return    ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( array $where=array(), string $order=null, int|array|null $limit=10, string $permissionKey='read', int|bool|null $includeHiddenItems= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member $member=null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins=null, bool|Model $skipPermission=FALSE, bool $joinTags=TRUE, bool $joinAuthor=TRUE, bool $joinLastCommenter=TRUE, bool $showMovedLinks=FALSE, array|null $location=null ): ActiveRecordIterator|int
	{
		$where = static::getItemsWithPermissionWhere( $where, $permissionKey, $member, $joinContainer, $skipPermission );
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
	}
	
	/**
	 * Additional WHERE clauses for Follow view
	 *
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	array		$joins				Other joins
	 * @return	array
	 */
	public static function followWhere( bool &$joinContainer, array &$joins ): array
	{
		$joinContainer = FALSE;
		return array_merge( parent::followWhere( $joinContainer, $joins ), static::getItemsWithPermissionWhere( array(), 'read', NULL, $joinContainer ) );
	}
	
	/**
	 * WHERE clause for getItemsWithPermission
	 *
	 * @param array $where				Current WHERE clause
	 * @param string $permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param Member|null $member				The member (NULL to use currently logged in member)
	 * @param bool $joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip container-based permission. You must still specify this in the $where clause
	 * @return	array
	 */
	public static function getItemsWithPermissionWhere( array $where, string $permissionKey, ?Member $member, bool &$joinContainer, bool|Model $skipPermission=FALSE ): array
	{
		/* Don't show topics in password protected forums */
		if ( !$skipPermission and in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$joinContainer = TRUE;
			$member = $member ?: Member::loggedIn();

			/* @var Forum $containerClass */
			$containerClass = static::$containerNodeClass;
			
			if ( $containerClass::customPermissionNodes() )
			{		
				$whereString = 'forums_forums.password=? OR ' . Db::i()->findInSet( 'forums_forums.password_override', $member->groups );
				$whereParams = array( NULL );
				if ( Dispatcher::hasInstance() AND $member->member_id === Member::loggedIn()->member_id )
				{
					foreach ( Request::i()->cookie as $k => $v )
					{
						if ( mb_substr( $k, 0, 13 ) === 'ipbforumpass_' )
						{
							$whereString .= ' OR ( forums_forums.id=? AND MD5(forums_forums.password)=? )';
							$whereParams[] = (int) mb_substr( $k, 13 );
							$whereParams[] = $v;
						}
					}
				}
				
				$where['container'][] = array_merge( array( '( ' . $whereString . ' )' ), $whereParams );
			}
		}
				
		/* Don't show topics from forums in which topics only show to the poster */
		if ( $skipPermission !== TRUE and in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$member = $member ?: Member::loggedIn();
			if ( $skipPermission instanceof Forum)
			{
				if ( !$skipPermission->can_view_others )
				{
					if ( !$member->member_id )
					{
						return array( '1=0' );
					}
					if ( $club = $skipPermission->club() )
					{
						if ( !$club->isModerator( $member ) )
						{
							$where['item'][] = array( 'forums_topics.starter_id=?', $member->member_id );
						}
					}
					elseif ( !$member->modPermission('can_read_all_topics') or ( is_array( $member->modPermission('forums') ) and !in_array( $skipPermission->_id, $member->modPermission('forums') ) ) )
					{
						$where['item'][] = array( 'forums_topics.starter_id=?', $member->member_id );
					}
				}
			}
			elseif ( !$member->modPermission('can_read_all_topics') or is_array( $member->modPermission('forums') ) or !$member->modPermission('can_access_all_clubs') )
			{
				$joinContainer = TRUE;
				
				if ( !$member->member_id )
				{
					$where[] = array( 'forums_forums.can_view_others=1' );
				}
				else
				{
					$whereClause = array( 'forums_forums.can_view_others=1 OR forums_topics.starter_id=?', (int) $member->member_id );
					$ors = array();
					
					if ( $member->modPermission('can_read_all_topics') )
					{
						$forums = $member->modPermission('forums');
						if ( isset( $forums ) and is_array( $forums ) )
						{
							$ors[] = '( forums_forums.club_id IS NULL AND ' . Db::i()->in( 'forums_topics.forum_id', $forums ) . ')';
						}
						else
						{
							$ors[] = 'forums_forums.club_id IS NULL';
						}
					}
					if ( $member->modPermission('can_access_all_clubs') )
					{
						$ors[] = 'forums_forums.club_id IS NOT NULL';
					}
					elseif ( $moderatorClubIds = $member->clubs( FALSE, TRUE ) )
					{
						$ors[] = Db::i()->in( 'forums_forums.club_id', $moderatorClubIds );
					}
					
					if ( $ors )
					{
						$whereClause[0] = "( {$whereClause[0]} OR " . implode( ' OR ', $ors ) . ' )';
					}
					else
					{
						$whereClause[0] = "( {$whereClause[0]} )";
					}
					
					$where[] = $whereClause;
				}				
			}
		}
		
		/* Return */
		return $where;
	}
	
	/**
	 * Total item \count(including children)
	 *
	 * @param	Model	$container			The container
	 * @param	bool			$includeItems		If TRUE, items will be included (this should usually be true)
	 * @param	bool			$includeComments	If TRUE, comments will be included
	 * @param	bool			$includeReviews		If TRUE, reviews will be included
	 * @param	int				$depth				Used to keep track of current depth to avoid going too deep
	 * @return	int|NULL|string	When depth exceeds 10, will return "NULL" and initial call will return something like "100+"
	 * @note	This method may return something like "100+" if it has lots of children to avoid exahusting memory. It is intended only for display use
	 * @note	This method includes counts of hidden and unapproved content items as well
	 */
	public static function contentCount( Model $container, bool $includeItems=TRUE, bool $includeComments=FALSE, bool $includeReviews=FALSE, int $depth=0 ): int|NULL|string
	{
		return parent::contentCount( $container, FALSE, TRUE, $includeReviews, $depth );
	}

	/**
	 * Total item, items only \count(including children)
	 *
	 * @param Model $container The container
	 * @return    int|NULL|string    When depth exceeds 10, will return "NULL" and initial call will return something like "100+"
	 * @note    This method may return something like "100+" if it has lots of children to avoid exahusting memory. It is intended only for display use
	 * @note    This method includes counts of hidden and unapproved content items as well
	 */
	public static function contentCountItemsOnly( Model $container ) : int|string|null
	{
		return parent::contentCount( $container );
	}
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @return	array
	 */
	public static function formElements( Item $item=NULL, Model $container=NULL ): array
	{
		$formElements = parent::formElements( $item, $container );
		
		/* Password protected */
		if ( $container !== NULL AND !$container->loggedInMemberHasPasswordAccess() )
		{
			$password = $container->password;
			$formElements['password'] = new Password( 'password', NULL, TRUE, array(), function( $val ) use ( $password )
			{
				if ( $val != $password )
				{
					throw new DomainException( 'forum_password_bad' );
				}
			} );
		}

		/* Build the topic state toggles */
		$options = array();
		$toggles = array();
		$current = array();
		if ( static::modPermission( 'lock', NULL, $container ) )
		{
			$options['lock'] = 'create_topic_locked';
			$toggles['lock'] = array( 'create_topic_locked' );
			if( $item and $item->locked() )
			{
				$current[] = 'lock';
			}
		}
		
		if ( static::modPermission( 'pin', NULL, $container ) )
		{
			$options['pin'] = 'create_topic_pinned';
			$toggles['pin'] = array( 'create_topic_pinned' );
			if( $item and $item->mapped('pinned') )
			{
				$current[] = 'pin';
			}
		}
		$canHide = ( $item ) ? $item->canHide() : ( Member::loggedIn()->group['g_hide_own_posts'] == '1' or in_array( 'IPS\forums\Topic', explode( ',', Member::loggedIn()->group['g_hide_own_posts'] ) ) );
		if ( static::modPermission( 'hide', NULL, $container ) or $canHide )
		{
			$options['hide'] = 'create_topic_hidden';
			$toggles['hide'] = array( 'create_topic_hidden' );
			if( $item and $item->hidden() === -1 )
			{
				$current[] = 'hide';
			}
		}
		
		if ( static::modPermission( 'feature', NULL, $container ) )
		{
			$options['feature'] = 'create_topic_featured';
			$toggles['feature'] = array( 'create_topic_featured' );
			if( $item and $item->mapped('featured') )
			{
				$current[] = 'feature';
			}
		}

		if ( count( $options ) or count( $toggles ) )
		{
			$formElements['topic_state'] = new CheckboxSet( 'topic_create_state', $current, FALSE, array(
				'options' 	=> $options,
				'toggles'	=> $toggles,
				'multiple'	=> TRUE
			) );	
		}		
		
		if ( static::modPermission( 'lock', NULL, $container ) )
		{
			/* Add lock/unlock options */
			if ( static::modPermission( 'unlock', NULL, $container ) )
			{
				$formElements['topic_open_time'] = new Date( 'topic_open_time', ( $item and $item->topic_open_time ) ? DateTime::ts( $item->topic_open_time ) : NULL, FALSE, array( 'time' => TRUE ) );
			}
			$formElements['topic_close_time'] = new Date( 'topic_close_time', ( $item and $item->topic_close_time ) ? DateTime::ts( $item->topic_close_time ) : NULL, FALSE, array( 'time' => TRUE ) );
		}

		/* Poll always needs to go on the end */
		if ( isset( $formElements['poll'] ) )
		{
			$poll = $formElements['poll'];
			unset( $formElements['poll'] );
			$formElements['poll'] = $poll;
		}

		return $formElements;
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( array $values ): void
	{
		parent::processForm( $values );
		
		if ( isset( $values['password'] ) )
		{
			/* Set Cookie */
			$this->container()->setPasswordCookie( $values['password'] );
		}

		/* Moderator actions */
		if ( isset( $values['topic_create_state'] ) )
		{
			if ( static::modPermission( 'lock', NULL, $this->container() ) )
			{
				$this->state = ( in_array( 'lock', $values['topic_create_state'] ) ) ? 'closed' : 'open';
			}
			
			if ( static::modPermission( 'pin', NULL, $this->container() ) )
			{
				$this->pinned = ( in_array( 'pin', $values['topic_create_state'] ) ) ? 1 : 0;
			}

			if ( static::modPermission( 'feature', NULL, $this->container() ) )
			{
				$this->featured = ( in_array( 'feature', $values['topic_create_state'] ) ) ? 1 : 0;
			}
		}

		if ( static::modPermission( 'lock', NULL, $this->container() ) )
		{
			/* Set open/close time */
			$this->topic_open_time = !empty( $values['topic_open_time'] ) ? $values['topic_open_time']->getTimestamp() : 0;
			$this->topic_close_time = !empty( $values['topic_close_time'] ) ? $values['topic_close_time']->getTimestamp() : 0;
			
			if( isset( $values['topic_create_state'] ) and !in_array( 'lock', $values['topic_create_state'] ) )
			{
				$this->state = 'open';
			}

			/* If open time is before close time, close now */
			if ( $this->topic_open_time and $this->topic_close_time and $this->topic_open_time < $this->topic_close_time )
			{
				$this->state = 'closed';
			}

			/* If we specified an unlock time, but no lock time, make sure the topic is locked */
			if ( $this->topic_open_time and !$this->topic_close_time )
			{
				$this->state = 'closed';
			}
		}
	}

	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( Comment|null $comment, array $values ): void
	{
		$this->processAfterCreateOrEdit( $values );
		
		parent::processAfterCreate( $comment, $values );
	}
	
	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @param array $values		Values from form
	 * @return	void
	 */
	public function processAfterEdit( array $values ): void
	{
		$this->processAfterCreateOrEdit( $values );
		
		/* Initial Comment */
		parent::processAfterEdit( $values );
		
		/* Topic changed? */
		if ( ! $this->hidden() )
		{
			$this->container()->setLastComment();
			$this->container()->save();
			
			foreach( $this->container()->parents() AS $parent )
			{
				$this->container()->setLastComment();
				$parent->save();
			}
		}
	}
	
	/**
	 * Process after the object has been edited or created on the front-end
	 *
	 * @param	array	$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreateOrEdit( array $values ) : void
	{
		/* Moderator actions */
		if ( isset( $values['topic_create_state'] ) )
		{
			if( in_array( 'hide', $values['topic_create_state'] ) )
			{
				if ( $this->canHide() )
				{
					$this->hide( NULL );
				}
			}
			elseif( $this->hidden() and $this->hidden() !== 1 and $this->canUnhide() )
			{
				$this->unhide( NULL );
			}
		}
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=forums&module=forums&controller=topic&id=';
	
	/**
	 * @brief	URL Template
	 */
	public static string $urlTemplate = 'forums_topic';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'title_seo';

	/**
	 * Stats for table view
	 *
	 * @param bool $includeFirstCommentInCommentCount	Determines whether the first comment should be inlcluded in the comment \count(e.g. For "posts", use TRUE. For "replies", use FALSE)
	 * @return	array
	 */
	public function stats( bool $includeFirstCommentInCommentCount=TRUE ): array
	{
		$stats = parent::stats( $includeFirstCommentInCommentCount );

		if( !$includeFirstCommentInCommentCount )
		{
			if( isset( $stats['comments'] ) )
			{
				$stats = array_reverse( $stats );

				$stats['forums_comments']	= $stats['comments'];

				unset( $stats['comments'] );
				$stats = array_reverse( $stats );
			}
		}

		return $stats;
	}
	
	/**
	 * Set name
	 *
	 * @param	string	$title	Title
	 * @return	void
	 */
	public function set_title( string $title ) : void
	{
		$this->_data['title'] = $title;
		$this->_data['title_seo'] = Friendly::seoTitle( $title );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_title_seo(): string
	{
		if( !$this->_data['title_seo'] )
		{
			$this->title_seo	= Friendly::seoTitle( $this->title );
			$this->save();
		}

		return $this->_data['title_seo'] ?: Friendly::seoTitle( $this->title );
	}

	/**
	 * Check if a specific action is available for this Content.
	 * Default to TRUE, but used for overrides in individual Item/Comment classes.
	 *
	 * @param string $action
	 * @param Member|null	$member
	 * @return bool
	 */
	public function actionEnabled( string $action, ?Member $member=null ) : bool
	{
		$skipOnArchive = array(
			'comment', 'feature', 'unfeature', 'lock', 'unlock', 'hide', 'unhide', 'move', 'featureComment', 'onMessage', 'toggleItemModeration', 'merge'
		);

		if( $this->isArchived() and in_array( $action, $skipOnArchive ) )
		{
			return false;
		}

		switch( $action )
		{
			case 'merge':
				if( $this->moved_to )
				{
					return false;
				}
				break;
			case 'edit':
				if( Application::appIsEnabled( 'cms' ) and Records::getLinkedRecord( $this ) )
				{
					return false;
				}
				break;

			case 'feature':
				if( !$this->container()->can_view_others )
				{
					return false;
				}
				break;
		}

		return parent::actionEnabled( $action, $member );
	}

	/**
	 * Can view?
	 *
	 * @param	Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( Member $member=null ): bool
	{
		if( !parent::canView( $member ) )
		{
			return FALSE;
		}
		
		$member = $member ?: Member::loggedIn();
		if ( $member !== $this->author() and !$this->container()->memberCanAccessOthersTopics( $member ) )
		{
			return FALSE;
		}

		/* Whitelist which types of do we allow */
		$do = array( 'editComment' );

		if( Application::appIsEnabled( 'cms' ) )
		{
			/* Check to see if it's attached to a database record and we are not a guest */
			if ( isset( Request::i()->do ) and in_array( Request::i()->do, $do ) and $record = Records::getLinkedRecord( $this ) and $member->member_id )
			{
				return $record->canView();
			}
		}
				
		return TRUE;
	}
	
	/**
	 * Can create polls?
	 *
	 * @param	Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canCreatePoll( Member $member = NULL, Model $container = NULL ) : bool
	{
		return static::_canCreatePoll( $member, $container ) and ( $container === NULL or $container->allow_poll );
	}
	
	/**
	 * SplObserver notification that poll has been voted on
	 *
	 * @param	SplSubject	$subject	Subject
	 * @return	void
	 */
	public function update( SplSubject $subject ): void
	{
		$this->updateLastVote( $subject );
	}
	
	/**
	 * Get template for content tables
	 *
	 * @return	array
	 */
	public static function contentTableTemplate(): array
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'forums.css', 'forums', 'front' ) );
		return array( Theme::i()->getTemplate( 'global', 'forums', 'front' ), 'rows' );
	}

	/**
	 * Table: Get rows
	 *
	 * @param array $rows	Rows to show
	 * @return    void
	 */
	public static function tableGetRows( array $rows ): void
	{
		$openIds = array();
		$closeIds = array();
		$timeNow = time();
		
		foreach ( $rows as $topic )
		{
			if ( $topic->state != 'link' )
			{
				$locked = $topic->locked();
				if ( $locked and $topic->topic_open_time and $topic->topic_open_time < $timeNow )
				{
					$openIds[] = $topic->tid;
					$topic->state = 'open';
				}
				if ( !$locked and $topic->topic_close_time and $topic->topic_close_time < $timeNow )
				{
					$closeIds[] = $topic->tid;
					$topic->state = 'closed';
				}
			}
		}

        if ( !empty( $openIds ) )
        {
            Db::i()->update( 'forums_topics', array( 'state' => 'open', 'topic_open_time' => 0 ), Db::i()->in( 'tid', $openIds ) );
        }
        if ( !empty( $closeIds ) )
        {
            Db::i()->update( 'forums_topics', array( 'state' => 'closed', 'topic_close_time' => 0 ), Db::i()->in( 'tid', $closeIds ) );
        }
	}
	
	/**
	 * Move
	 *
	 * @param	Model	$container	Container to move to
	 * @param bool $keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 */
	public function move( Model $container, bool $keepLink=FALSE ): void
	{
		if(	!$container->sub_can_post or $container->redirect_url )
		{
			throw new InvalidArgumentException;
		}

		parent::move( $container, $keepLink );
		Db::i()->update( 'forums_question_ratings', array( 'forum' => $container->_id ), array( 'topic=?', $this->tid ) );

		/* While you can't normally move archived topics, when you mass manage content from the AdminCP by using the menu next to the forum,
			this still allows topics to be moved. If we don't update the archive forum database the forum counts will be off */
		if( $this->isArchived() )
		{
			try
			{
				ArchivedPost::db()->update( 'forums_archive_posts', array( 'archive_forum_id' => $container->_id ), array( 'archive_topic_id=?', $this->tid ) );
			}
			/* catch db exceptions if e.g. if the connection credentials didn't work or if the database doesn't exist anymore */
			catch ( Exception $e ){}
		}
	}
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		parent::delete();

		if( in_array( $this->archive_status, array( static::ARCHIVE_DONE, static::ARCHIVE_WORKING, static::ARCHIVE_RESTORE ) ) )
		{
			try
			{
				ArchivedPost::db()->delete( 'forums_archive_posts', array( 'archive_topic_id=?', $this->tid ) );
			}
			/* catch db exceptions if e.g. if the connection credentials didn't work or if the database doesn't exist anymore */
			catch ( Exception $e ){}
		}

		Db::i()->delete( 'forums_question_ratings', array( 'topic=?', $this->tid ) );
		Db::i()->delete( 'forums_answer_ratings', array( 'topic=?', $this->tid ) );

		/* Delete any moved topic links that point to this topic - moved_on>? is for query optimisation purposes */
		Db::i()->delete( 'forums_topics', array( "moved_on>? AND moved_to LIKE CONCAT( ?, '%' ) AND state=?", 0, $this->tid . '&', 'link' ) );
	}

	/**
	 * Merge other items in (they will be deleted, this will be kept)
	 *
	 * @param	array	$items	Items to merge in
	 * @param bool $keepLinks	Retain redirect links for the items that were merge in
	 * @return	void
	 */
	public function mergeIn( array $items, bool $keepLinks=FALSE ): void
	{
		/* If mark solved is enabled we need to make sure we only have one best answer (at most) post-merge */
		if( $this->container()->forums_bitoptions['bw_solved_set_by_member'] or $this->container()->forums_bitoptions['bw_solved_set_by_moderator'] )
		{
			/* Does this topic already have a best answer? */
			if( $this->topic_answered_pid )
			{
				/* Then we need to make sure none of the items also has a best answer */
				foreach( $items as $item )
				{
					/* Reset best answer for this topic */
					if( $item->topic_answered_pid )
					{
						try
						{
							$post = Post::load( $item->topic_answered_pid );
							$post->post_bwoptions['best_answer'] = FALSE;
							$post->save();
						}
						catch( OutOfRangeException $e ){}
					}
				}
			}
			/* The topic doesn't have a best answer, but we still need to make sure we only have one best answer total post-merge */
			else
			{
				$bestAnswerSeen	= FALSE;

				foreach( $items as $item )
				{
					if( $item->topic_answered_pid )
					{
						/* Have we seen a best answer yet? If not, then we're ok. */
						if( $bestAnswerSeen === FALSE )
						{
							/* This topic had no best answer flag set, so set it now */
							$this->topic_answered_pid = $item->topic_answered_pid;
							$this->save();

							$bestAnswerSeen = TRUE;
							continue;
						}

						/* If we have though, reset any others */
						try
						{
							$post = Post::load( $item->topic_answered_pid );
							$post->post_bwoptions['best_answer'] = FALSE;
							$post->save();
						}
						catch( OutOfRangeException $e ){}
					}
				}
			}
		}

		parent::mergeIn( $items, $keepLinks );
	}

	/* !Saved Actions */
	
	/**
	 * Get available saved actions for this topic
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in)
	 * @return	array
	 */
	public function availableSavedActions( Member $member = NULL ) : array
	{
		return SavedAction::actions( $this->container(), $member );
	}
		
	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @param	bool				$immediately Delete Immediately
	 * @return	void
	 * @throws	OutOfRangeException|InvalidArgumentException|RuntimeException
	 */
	public function modAction( string $action, Member $member = NULL, mixed $reason = NULL, bool $immediately=FALSE ): void
	{
		if ( mb_substr( $action, 0, 12 ) === 'savedAction-' )
		{
			$action = SavedAction::load( intval( mb_substr( $action, 12 ) ) );
			$action->runOn( $this );
			
			/* Log */
			Session::i()->modLog( 'modlog__saved_action', array( 'forums_mmod_' . $action->mm_id => TRUE, $this->url()->__toString() => FALSE, $this->mapped( 'title' ) => FALSE ), $this );
		}
		else
		{
			if ( Application::appIsEnabled( 'cms' ) and $action === 'delete' )
			{
				/* We used to restrict by forum ID but if you move a topic to a new forum then the forum ID will no longer match */
				foreach( Db::i()->select( '*', 'cms_database_categories', array( 'category_forum_record=? AND category_forum_comments=?', 1, 1 ) ) as $category )
				{
					try
					{
						$class    = '\IPS\cms\Records' . $category['category_database_id'];

						if( class_exists( $class ) )
						{
							$class::load( $this->tid, 'record_topicid' );

							$database = Databases::load( $category['category_database_id'] );
							Member::loggedIn()->language()->words['cms_delete_linked_topic'] = sprintf( Member::loggedIn()->language()->get('cms_delete_linked_topic'), $database->recordWord( 1 ) );

							Output::i()->error( 'cms_delete_linked_topic', '1T281/1', 403, '' );
						}

					}
					catch( OutOfRangeException | Exception $ex ) { }
				}

				foreach( Db::i()->select( '*', 'cms_databases', array( 'database_forum_record=? AND database_forum_comments=?', 1, 1 ) ) as $database )
				{
					try
					{
						/* @var Records $class */
						$class = '\IPS\cms\Records' . $database['database_id'];

						$class::load( $this->tid, 'record_topicid' );

						$database = Databases::constructFromData( $database );
						Member::loggedIn()->language()->words['cms_delete_linked_topic'] = sprintf( Member::loggedIn()->language()->get('cms_delete_linked_topic'), $database->recordWord( 1 ) );

						Output::i()->error( 'cms_delete_linked_topic', '1T281/1', 403, '' );
					}
					catch( OutOfRangeException | Exception $ex ) { }
				}
			}

			parent::modAction( $action, $member, $reason, $immediately );

			/* Prevent topics with an open time re-opening again after being locked */
			if ( $action == 'lock' )
			{
				$this->topic_open_time = 0;
				$this->save();
			}

			/* And prevent it from relocking if we are unlocking */
			if( $action == 'unlock' )
			{
				$this->topic_close_time = 0;
				$this->save();
			}

			if ( Application::appIsEnabled( 'cms' ) and ( $action === 'lock' or $action === 'unlock' ) )
			{
				foreach( Db::i()->select( '*', 'cms_databases', array( 'database_forum_record=? AND database_forum_comments=?', 1, 1 ) ) as $database )
				{
					try
					{
						/* @var Records $class */
						$class = '\IPS\cms\Records' . $database['database_id'];
						$record = $class::load( $this->tid, 'record_topicid' );

						$record->record_locked = ( $action === 'lock' ) ? 1 : 0;
						$record->save();
					}
					catch(OutOfRangeException $ex ) { }
				}
			}
		}

	}
	
	/* !Questions & Answers */

	/**
	 * Any container has solvable enabled?
	 *
	 * @return	boolean
	 */
	public static function anyContainerAllowsSolvable() : bool
	{
		return (bool) Db::i()->select( 'COUNT(*)', 'forums_forums', '( ' . Db::i()->bitwiseWhere( Forum::$bitOptions['forums_bitoptions'], 'bw_solved_set_by_moderator' ) . ' )' )->first();
	}
	
	
	/**
	 * Container has solvable enabled
	 *
	 * @return	bool
	 */
	public function containerAllowsSolvable() : bool
	{
		return $this->container()->forums_bitoptions['bw_solved_set_by_moderator'];
	}

	/**
	 * Container has solvable enabled
	 *
	 * @return	bool
	 */
	public function containerAllowsMemberSolvable() : bool
	{
		return ( $this->containerAllowsSolvable() AND $this->container()->forums_bitoptions['bw_solved_set_by_member'] );
	}

	/**
	 * Toggle the solve value of a comment
	 *
	 * @param 	int		$commentId	The comment ID
	 * @param 	boolean	$value		TRUE/FALSE value
	 * @param	Member|null	$member	The member (null for currently logged in member)
	 *
	 * @return	void
	 */
	public function toggleSolveComment( int $commentId, bool $value, ?Member $member = NULL ): void
	{
		if ( $value )
		{
			$post = Post::load( $commentId );
			$post->author()->achievementAction( 'forums', 'AnswerMarkedBest', [ 'post' => $post, 'type' => 'solved' ] );
		}
		
		$this->_toggleSolveComment( $commentId, $value, $member );
	}

	/**
	 * Can user set the best answer?
	 *
	 * @param Member|null $member The member (null for currently logged in member)
	 * @return    bool
	 */
	public function canSetBestAnswer( Member $member = NULL ): bool
	{
		/* Archived topics cannot be modified */
		if ( $this->isArchived() )
		{
			return FALSE;
		}

		$member = $member ?: Member::loggedIn();

		/* If we asked this question, we can set the best answer */
		if ( $member === $this->author() and $this->container()->forums_bitoptions['bw_solved_set_by_member'] )
		{
			return TRUE;
		}

		/* Or if we're a moderator */
		if
		(
			$member->modPermission( 'can_set_best_answer' )
			and
			(
				( $member->modPermission( Forum::$modPerm ) === TRUE or $member->modPermission( Forum::$modPerm ) === -1 )
				or
				(
					is_array( $member->modPermission( Forum::$modPerm ) )
					and
					in_array( $this->container()->_id, $member->modPermission( Forum::$modPerm ) )
				)
			)
		) {
			return TRUE;
		}
		
		/* Otherwise no */
		return FALSE;
	}
	
	/**
	 * @brief	Answer Votes
	 */
	protected array $answerVotes = array();
	
	/**
	 * Answer Votes
	 *
	 * @param	Member	$member	The member
	 * @return	array
	 */
	public function answerVotes( Member $member ) : array
	{
		if ( !isset( $this->answerVotes[ $member->member_id ] ) )
		{
			$this->answerVotes[ $member->member_id ] = iterator_to_array(
				Db::i()->select( 'post,rating', 'forums_answer_ratings', array( 'topic=? AND `member`=?', $this->tid, $member->member_id ) )
				->setKeyField( 'post' )
				->setValueField( 'rating' )
			);
		}
		
		return $this->answerVotes[ $member->member_id ];
	}
	
	/**
	 * Get Best Answer
	 *
	 * @return	Post|NULL
	 */
	public function bestAnswer() : ?Post
	{
		if ( $this->topic_answered_pid )
		{
			try
			{
				return Post::load( $this->topic_answered_pid );
			}
			catch ( OutOfRangeException $e ){}
		}
		return NULL;
	}
	
	/**
	 * Can the user rate answers?
	 *
	 * @param	int					$rating		1 for positive, -1 for negative, 0 for either
	 * @param	Member|NULL	$member		The member (NULL for currently logged in member)
	 * @return	bool
	 * @throws	InvalidArgumentException
	 */
	public function canVote( int $rating=0, ?Member $member=NULL ) : bool
	{
		/* Is $rating valid */
		if ( !in_array( $rating, array( -1, 0, 1 ) ) )
		{
			throw new InvalidArgumentException;
		}
				
		/* Guests can't vote */
		$member = $member ?: Member::loggedIn();
		if ( !$member->member_id )
		{
			return FALSE;
		}
		
		/* Can't vote your own answers */
		if ( $member === $this->author() )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * @brief	Votes
	 */
	protected ?array $votes = NULL;
	
	/**
	 * Votes
	 *
	 * @return	array
	 */
	public function votes() : array
	{
		if ( $this->votes === NULL )
		{
			$this->votes = iterator_to_array(
				Db::i()->select( '`member`,rating', 'forums_question_ratings', array( 'topic=?', $this->tid ) )
				->setKeyField( 'member' )
				->setValueField( 'rating' )
			);
		}
		
		return $this->votes;
	}
	
	/**
	 * Clear Votes Cache
	 *
	 * @return	void
	 * @note	This is necessary so that when voting on a question or answer, the cached votes ($votes and $answerVotes) are reloaded properly.
	 */
	public function clearVotes() : void
	{
		$this->votes		= NULL;
		$this->answerVotes	= array();
	}

	/**
	 * Container has assignable enabled
	 *
	 * @return	bool
	 */
	public function containerAllowsAssignable(): bool
	{
		return (bool) $this->container()->forums_bitoptions['bw_enable_assignments'];
	}
	
	/**
	 * [ActiveRecord]	Save
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		$this->clearVotes();
	}

	/* !Sitemap */
	
	/**
	 * WHERE clause for getting items for sitemap (permissions are already accounted for)
	 *
	 * @return    array
	 */
	public static function sitemapWhere(): array
	{
		return array( array( 'forums_forums.ipseo_priority<>?', 0 ) );
	}
	
	/**
	 * Sitemap Priority
	 *
	 * @return    int|null    NULL to use default
	 */
	public function sitemapPriority(): ?int
	{
		$priority = $this->container()->ipseo_priority;
		if ( $priority === NULL or $priority == -1 )
		{
			return NULL;
		}
		return $priority;
	}
	
	/* !Archiving */
	
	/**
	 * Is archived?
	 *
	 * @return	bool
	 */
	public function isArchived() : bool
	{
		return in_array( $this->topic_archive_status, array( static::ARCHIVE_DONE, static::ARCHIVE_WORKING, static::ARCHIVE_RESTORE ) );
	}
	
	/**
	 * Can unarchive?
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canUnarchive( ?Member $member=NULL ) : bool
	{
		if ( $this->isArchived() and $this->topic_archive_status !== static::ARCHIVE_RESTORE )
		{
			$member = $member ?: Member::loggedIn();
			return $member->hasAcpRestriction( 'forums', 'forums', 'archive_manage' );
		}
		return FALSE;
	}

	/**
	 * Should this topic be archived again?
	 *
	 * @param Member|NULL $member	The member (NULL for currently logged in member)
	 * @return bool
	 */
	public function canRemoveArchiveExcludeFlag( ?Member $member=NULL ) : bool
	{
		$member = $member ?: Member::loggedIn();

		if ( $member->hasAcpRestriction( 'forums', 'forums', 'archive_manage' ) AND $this->topic_archive_status == static::ARCHIVE_EXCLUDE )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Unarchive confirm message
	 *
	 * @return	string
	 */
	public function unarchiveBlurb() : string
	{
		$taskData = Db::i()->select( '*', 'core_tasks', array( '`key`=? AND app=?', 'archive', 'forums' ) )->first();
		
		$time = DateTime::ts( $taskData['next_run'] );
		$postsToBeUnarchived = Db::i()->select( 'SUM(posts) + count(*)', 'forums_topics', array( 'topic_archive_status=?', static::ARCHIVE_RESTORE ) )->first();

		if ( $postsToBeUnarchived AND $postsToBeUnarchived > unarchive::PROCESS_PER_BATCH )
		{
			$total = $postsToBeUnarchived / unarchive::PROCESS_PER_BATCH;
			$interval = new DateInterval( $taskData['frequency'] );
			foreach ( range( 1, $total ) as $i )
			{
				$time->add( $interval );
			}
		}
		
		return Member::loggedIn()->language()->addToStack( 'unarchive_confirm', FALSE, array( 'pluralize' => array( ceil( ( $time->getTimestamp() - time() ) / 60 ) ) ) );
	}

	/**
	 * Adjust based on the theme preferences
	 *
	 * @return int
	 */
	public function commentCount(): int
	{
		$count = parent::commentCount();

		if ( Member::loggedIn()->getLayoutValue( 'forum_topic_view_firstpost' ) )
		{
			$count--;
		}

		return $count;
	}

	/**
	 * Actions to show in comment multi-mod
	 *
	 * @param	Member|NULL	$member	Member (NULL for currently logged in member)
	 * @return	array
	 */
	public function commentMultimodActions( ?Member $member = NULL ): array
	{
		if ( $this->isArchived() )
		{
			return array();
		}
		
		return parent::commentMultimodActions( $member );
	}
	
	/**
	 * Get comments
	 *
	 * @param	int|NULL			$limit					The number to get (NULL to use static::getCommentsPerPage())
	 * @param	int|NULL			$offset					The number to start at (NULL to examine \IPS\Request::i()->page)
	 * @param	string				$order					The column to order by
	 * @param	string				$orderDirection			"asc" or "desc"
	 * @param	Member|NULL	$member					If specified, will only get comments by that member
	 * @param	bool|NULL			$includeHiddenComments	Include hidden comments or not? NULL to base of currently logged in member's permissions
	 * @param	DateTime|NULL	$cutoff					If an \IPS\DateTime object is provided, only comments posted AFTER that date will be included
	 * @param	mixed				$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @param	bool|NULL			$bypassCache			Used in cases where comments may have already been loaded i.e. splitting comments on an item.
	 * @param	bool				$includeDeleted			Include Deleted Content
	 * @param	bool|NULL			$canViewWarn			TRUE to include Warning information, NULL to determine automatically based on moderator permissions.
	 * @return	array|NULL|Comment	If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public function comments( int|null $limit=NULL, int|null $offset=NULL, string $order='date', string $orderDirection='asc', Member|null $member=NULL, bool|null $includeHiddenComments=NULL, DateTime|null $cutoff=NULL, mixed $extraWhereClause=NULL, bool|null $bypassCache=FALSE, bool $includeDeleted=FALSE, bool|null $canViewWarn=NULL ): array|NULL|Comment
	{
		static $comments	= array();
		$idField			= static::$databaseColumnId;
		$_hash				= md5( $this->$idField . json_encode( func_get_args() ) );
		$expectingSingleComment = $limit === 1;

		if( !$bypassCache and isset( $comments[ $_hash ] ) )
		{
			return $comments[ $_hash ];
		}
		
		$includeWarnings	= $canViewWarn;
		$commentClass		= NULL;

		if ( $this->isArchived() )
		{
			/* We need to set $commentClass to the archive class, otherwise the includeHidden checks in _comments fail, as they verify $class == static::$commentClass */
			$class			= static::$archiveClass;
			$commentClass	= static::$commentClass;

			static::$commentClass = $class;

			$includeWarnings = FALSE;

			if( $extraWhereClause !== NULL )
			{
				if( is_array( $extraWhereClause ) )
				{
					foreach( $extraWhereClause as $k => $v )
					{
						$extraWhereClause[ $k ]	= preg_replace( "/^author_id /", "archive_author_id ", $v );
					}
				}
				else
				{
					$extraWhereClause	= preg_replace( "/^author_id /", "archive_author_id ", $extraWhereClause );
				}
			}
		}
		else
		{
			$class = static::$commentClass;
		}
		
		try 
		{
			$alreadyDone = false;
			$originalLimit = $limit;
			$originalOffset = $offset;

			if ( $this->isArchived() and $limit === 1 and $order === 'date' )
			{
				$minMax = ( $orderDirection === 'asc' ) ? 'MIN' : 'MAX';
				/* Do some mojo to get the lowest comment and highest comment for archived topics */
				$row = ArchivedPost::db()->select( "{$minMax}( CONCAT( archive_content_date, '.', archive_id) ) as pid", 'forums_archive_posts', array( [ 'archive_topic_id=?', $this->tid, ArchivedPost::db()->in( 'archive_queued', [0,2] ) ] ) )->first();
				$pid = (int) explode( '.', $row )[1];

				if( is_array( $extraWhereClause ) or is_null( $extraWhereClause ) )
				{
					$extraWhereClause[] = [ 'archive_id=?', $pid ];
				}
				else if ( is_string( $extraWhereClause ) )
				{
					$extraWhereClause = [
						[ $extraWhereClause ],
						[ 'archive_id=?', $pid ]
					];
				}

				$data = $this->_comments( $class, 1, 0, ( isset( $class::$databaseColumnMap[$order] ) ? ( $class::$databasePrefix . $class::$databaseColumnMap[$order] ) : $order ) . ' ' . $orderDirection, $member, $includeHiddenComments, $cutoff, $canViewWarn, $extraWhereClause, $includeDeleted );
				$comments[ $_hash ] = is_array( $data ) ? array_reverse( $data, true ) : $data;

				$alreadyDone = true;
			}
			else if ( $this->mapped('num_comments') > LARGE_TOPIC_REPLIES and $order === 'date' and $orderDirection === 'asc' and ( $originalLimit === null or $originalLimit === static::getCommentsPerPage() ) )
			{
				/* This is a large topic, if we're requesting the last 50% of pages, we will reverse sort to reduce the offset */
				$page = ( Request::i()->page ? intval( Request::i()->page ) : 1 );
				$pagePercent = ceil( ( $page / $this->commentPageCount() ) * 100 );
				$commentCount = $this->commentCount();

				if ( $pagePercent > 50 )
				{
					$postsOnLastPage = $commentCount % static::getCommentsPerPage();
					$pageFromEnd = $this->commentPageCount() - $page;
					$offset = $pageFromEnd > 1 ? ( $pageFromEnd - 1 ) * static::getCommentsPerPage() : 0;

					/* Are we on the last page, if so the limit should be on the number of posts on the last page */
					if ( $page == $this->commentPageCount() )
					{
						$limit = $postsOnLastPage;
					}
					else
					{
						/* We need to adjust the offset to be from the end of the topic keeping in mind the last page may have fewer posts than a full 25 */
						$offset += $postsOnLastPage;
					}

					$data = $this->_comments( $class, $limit ?: $this->getCommentsPerPage(), $offset, ( isset( $class::$databaseColumnMap[$order] ) ? ( $class::$databasePrefix . $class::$databaseColumnMap[$order] ) : $order ) . ' desc', $member, $includeHiddenComments, $cutoff, $canViewWarn, $extraWhereClause, $includeDeleted );
					$comments[ $_hash ] = is_array( $data ) ? array_reverse( $data, true ) : $data;

					/* When we set an implicit limit of 1, we just want a Post object but if the sql returns just 1 item but we could take more, then we want an array
					   yeah this is bad code but it's been here for years so... */
					if ( ! is_array( $data ) and ! $expectingSingleComment )
					{
						$comments[ $_hash ] = [ $data->pid => $data ];
					}

					$alreadyDone = true;
				}
				else if ( $this->isArchived() )
				{
					/* When viewing the first handful of pages in archived topics with hundreds of thousands of replies, it's more efficient to get the ID first */
					$archiveIds = [];

					if ( $offset === NULL )
					{
						$_pageValue = ( Request::i()->page ? intval( Request::i()->page ) : 1 );

						if( $_pageValue < 1 )
						{
							$_pageValue = 1;
						}

						$offset	= ( $_pageValue - 1 ) * static::getCommentsPerPage();
					}

					foreach( ArchivedPost::db()->select( "archive_id", 'forums_archive_posts', array( [ 'archive_topic_id=?', $this->tid, ArchivedPost::db()->in( 'archive_queued', [0,2] ) ] ), 'archive_id ' . $orderDirection, [ $offset, static::getCommentsPerPage() ] ) as $row )
					{
						$archiveIds[] = $row;
					}

					if( is_array( $extraWhereClause ) or is_null( $extraWhereClause ) )
					{
						$extraWhereClause[] = [ ArchivedPost::db()->in( 'archive_id', $archiveIds ) ];
					}
					else if ( is_string( $extraWhereClause ) )
					{
						$extraWhereClause = [
							[ $extraWhereClause ],
							[ ArchivedPost::db()->in( 'archive_id', $archiveIds ) ]
						];
					}

					$comments[ $_hash ] = $this->_comments( $class, static::getCommentsPerPage(), 0, ( isset( $class::$databaseColumnMap[$order] ) ? ( $class::$databasePrefix . $class::$databaseColumnMap[$order] ) : $order ) . ' ' . $orderDirection, $member, $includeHiddenComments, $cutoff, $includeWarnings, $extraWhereClause, $includeDeleted );

					$alreadyDone = true;
				}
			}

			if ( ! $alreadyDone )
			{
				$comments[ $_hash ] = $this->_comments( $class, $originalLimit ?: static::getCommentsPerPage(), $originalOffset, ( isset( $class::$databaseColumnMap[$order] ) ? ( $class::$databasePrefix . $class::$databaseColumnMap[$order] ) : $order ) . ' ' . $orderDirection, $member, $includeHiddenComments, $cutoff, $includeWarnings, $extraWhereClause, $includeDeleted );
			}
		}
		catch( Exception $e )
		{
			$post = new Post;
			$post->topic_id = $this->tid;
			$post->post = '<p><em>' . Member::loggedIn()->language()->addToStack('archived_topic_missing_posts') . '</em></p>';
			$post->post_date = $this->start_date;
			$post->author_id = $this->starter_id;
			
			if ( Member::loggedIn()->isAdmin() )
			{
				$post->post .= "<p>" . Member::loggedIn()->language()->addToStack('archived_topic_missing_posts_admin') . "</p><p><strong>{$e->getMessage()}<br><textarea>" . var_export( $e, TRUE ) . '</textarea></p>';
			}

			$comments[ $_hash ] = $expectingSingleComment ? $post : array( $post );
		}
		
		/* Restore comment class now */
		if( $commentClass )
		{
			static::$commentClass	= $commentClass;
		}
		return $comments[ $_hash ];
	}
	
	/**
	 * Resync the comments/unapproved comment counts
	 *
	 * @param string|null $commentClass	Override comment class to use
	 * @return void
	 */
	public function resyncCommentCounts( string $commentClass=NULL ): void
	{
		parent::resyncCommentCounts( $this->isArchived() ? static::$archiveClass : NULL );
	}
	
	/**
	 * Return the first comment on the item
	 *
	 * @return Comment|NULL
	 */
	public function firstComment(): Comment|null
	{
		if ( $this->isArchived() )
		{
			try 
			{
				return parent::firstComment();
			}
			catch( Exception $e )
			{

			}
		}
		else
		{
			return parent::firstComment();
		}

		return null;
	}

	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( string $type, ?Member $member = NULL, ?Model $container = NULL ): bool
	{		
		/* Load Member */
		$member = $member ?: Member::loggedIn();
		
		/* Compatibility checks */
		if ( in_array( $type, array( 'use_saved_actions', 'set_best_answer' ) ) )
		{
			/* @var Forum $containerClass */
			$containerClass = get_class( $container );
			$title = static::$title;
			if
			(
				$member->modPermission( $containerClass::$modPerm ) === -1
				or
				(
					is_array( $member->modPermission( $containerClass::$modPerm ) )
					and
					in_array( $container->_id, $member->modPermission( $containerClass::$modPerm ) )
				)
			)
			{
				return TRUE;
			}
		}
		
		return parent::modPermission( $type, $member, $container );
	}

	/**
	 * Mark as read
	 *
	 * @param	Member|NULL	$member					The member (NULL for currently logged in member)
	 * @param	int|NULL			$time					The timestamp to set (or NULL for current time)
	 * @param	mixed				$extraContainerWhere	Additional where clause(s) (see \IPS\Db::build for details)
	 * @param	bool				$force					Mark as unread even if we already appear to be unread?
	 * @return	void
	 */
	public function markRead( ?Member $member = NULL, ?int $time = NULL, mixed $extraContainerWhere = NULL, bool $force = FALSE ): void
	{
        $member = $member ?: Member::loggedIn();
        $time	= $time ?: time();

        if ( !$this->container()->memberCanAccessOthersTopics( $member ) )
        {
            $extraContainerWhere = array( 'starter_id = ?', $member->member_id );
        }

        $this->_markRead( $member, $time, $extraContainerWhere, $force );
    }

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse	int						id				ID number
	 * @apiresponse	string					title			Title
	 * @apiresponse	\IPS\forums\Forum		forum			Forum
	 * @apiresponse	int						posts			Number of posts
	 * @apiresponse	int						views			Number of views
	 * @apiresponse	string					prefix			The prefix tag, if there is one
	 * @apiresponse	[string]				tags			The tags
	 * @apiresponse	\IPS\forums\Topic\Post	firstPost		The first post in the topic
	 * @apiresponse	\IPS\forums\Topic\Post	lastPost		The last post in the topic
	 * @apiresponse	\IPS\forums\Topic\Post	bestAnswer		The best answer, if this is a question and there is one
	 * @apiresponse	bool					locked			Topic is locked
	 * @apiresponse	bool					hidden			Topic is hidden
	 * @apiresponse	bool					pinned			Topic is pinned
	 * @apiresponse	bool					featured		Topic is featured
	 * @apiresponse	bool					archived		Topic is archived
	 * @apiresponse	\IPS\Poll				poll			Poll data, if there is one
	 * @apiresponse	string					url				URL
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$firstPost = $this->comments( 1, 0, 'date', 'asc' );
		$lastPost = $this->comments( 1, 0, 'date', 'desc' );
		$bestAnswer = $this->bestAnswer();
		return array(
			'id'			=> $this->tid,
			'title'			=> $this->title,
			'forum'			=> $this->container()->apiOutput( $authorizedMember ),
			'posts'			=> $this->posts,
			'views'			=> $this->views,
			'prefix'		=> $this->prefix(),
			'tags'			=> $this->tags(),
			'firstPost'		=> $firstPost?->apiOutput( $authorizedMember ),
			'lastPost'		=> $lastPost?->apiOutput( $authorizedMember ),
			'bestAnswer'	=> $bestAnswer?->apiOutput( $authorizedMember ),
			'locked'		=> $this->locked(),
			'hidden'		=> (bool) $this->hidden(),
			'pinned'		=> (bool) $this->mapped('pinned'),
			'featured'		=> (bool) $this->mapped('featured'),
			'archived'		=> $this->isArchived(),
			'poll'			=> $this->poll_state ? Poll::load( $this->poll_state )->apiOutput( $authorizedMember ) : null,
			'url'			=> (string) $this->url(),
			'is_future_entry'	=> $this->is_future_entry,
			'publish_date'	=> $this->publish_date ? DateTime::ts( $this->publish_date )->rfc3339() : NULL
		);
	}

	/**
	 * Returns the content
	 *
	 * @return	string
	 * @throws	BadMethodCallException
	 * @note	This is overridden for performance reasons - selecting a post by a PID is more efficient than select * from posts order by date desc limit 1
	 */
	public function content(): string
	{
		$firstComment = $this->firstComment();
		return $firstComment ? $firstComment->content() : '';
	}
	
	/**
	 * Supported Meta Data Types
	 *
	 * @return	array
	 */
	public static function supportedMetaDataTypes(): array
	{
		return array( 'core_FeaturedComments', 'core_ContentMessages', 'core_ItemModeration' );
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'forums', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'forums' )->embedTopic( $this, $this->url()->setQueryString( $params ) );
	}
	
	/* ! Reactions */
	
	/**
	 * Reaction type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		/* Because of firstCommentRequired, the reaction on the item will always be the reaction for a comment */
		return Post::reactionType();
	}

	/**
	 * Reaction class
	 *
	 * @return	string
	 */
	public static function reactionClass(): string
	{
		return Post::class;
	}

	/**
	 * Reaction Where Clause [needs to overload reactable as it's the only item level reaction type]
	 *
	 * @param	Reaction|array|int|NULL	$reactions			This can be any one of the following: An \IPS\Content\Reaction object, an array of \IPS\Content\Reaction objects, an integer, or an array of integers, or NULL
	 * @param	bool									$enabledTypesOnly 	If TRUE, only reactions of the enabled reaction types will be included (must join core_reactions)
	 * @return	array
	 */
	public function getReactionWhereClause( Reaction|array|int|null $reactions = NULL, bool $enabledTypesOnly=TRUE ) : array
	{
		$idColumn = static::$databaseColumnId;
		$where = array( array( 'rep_class=? and item_id=?', static::$commentClass, $this->$idColumn ) );

		if ( $enabledTypesOnly )
		{
			$where[] = array( 'reaction_enabled=1' );
		}

		if ( $reactions !== NULL )
		{
			if ( !is_array( $reactions ) )
			{
				$reactions = array( $reactions );
			}

			$in = array();
			foreach( $reactions AS $reaction )
			{
				if ( $reaction instanceof Reaction )
				{
					$in[] = $reaction->id;
				}
				else
				{
					$in[] = $reaction;
				}
			}

			if ( count( $in ) )
			{
				$where[] = array( Db::i()->in( 'reaction', $in ) );
			}
		}

		return $where;
	}

	/**
	 * Show the topic summary feature?
	 *
	 * @param	$key	string		Key to check (topPost, popularDays, uploads)
	 * @return boolean
	 */
	public function showSummaryFeature( string $key ) : bool
	{
		if ( Settings::i()->forums_topic_activity_features )
		{
			$features = json_decode( Settings::i()->forums_topic_activity_features, TRUE );
			if ( $features and in_array( $key, $features ) )
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Show the topic summary on desktop? (if so where)
	 *
	 * @return string|bool [sidebar,post]
	 */
	public function showSummaryOnDesktop() : string|bool
	{
		/* Hide the summary for future topics */
		if ( $this->isFutureDate() )
		{
			return FALSE;
		}
		if ( ! Settings::i()->forums_topics_activity_pages_show OR ( (int) Settings::i()->forums_topics_activity_pages_show <= $this->commentPageCount() ) )
		{
			$viewSettings = json_decode( Settings::i()->forums_topic_activity, TRUE );
			if ( $viewSettings and in_array( 'desktop', $viewSettings ) and isset( Settings::i()->forums_topic_activity_desktop ) )
			{
				return Settings::i()->forums_topic_activity_desktop;
			}
		}

		return FALSE;
	}

	/**
	 * Show the topic summary on mobile?
	 *
	 * @return boolean
	 */
	public function showSummaryOnMobile() : bool
	{
		/* Hide the summary for future topics */
		if ( $this->isFutureDate() )
		{
			return FALSE;
		}
		if ( ! Settings::i()->forums_topics_activity_pages_show OR ( (int) Settings::i()->forums_topics_activity_pages_show <= $this->commentPageCount() ) )
		{
			$viewSettings = json_decode( Settings::i()->forums_topic_activity, TRUE );
			if ( $viewSettings and in_array( 'mobile', $viewSettings ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

	/**
	 * We need to force an index so that super long topics load when fetching IDs before running the main query
	 *
	 * @return string|null
	 */
	protected function forceIndexForPaginatedIds() : ?string
	{
		if ( ! $this->isArchived() )
		{
			return 'first_post';
		}
		else
		{
			return 'archive_topic_id';
		}
	}

	/**
	 * WHERE clause for getting items for ACP overview statistics
	 *
	 * @return	array
	 */
	public static function overviewStatisticsWhere() : array
	{
		return array( array( Db::i()->in( 'state', array( 'link', 'merged' ), TRUE ) ) );
	}

	public function badges(): array
	{
		$return = parent::badges();

		if( $this->topic_open_time and $this->topic_open_time > time() )
		{
			$return['unlock'] = new Badge( 'ipsBadge--unlocks', Member::loggedIn()->language()->addToStack( 'topic_unlocks_at_short', true, array(
				'sprintf' => array( DateTime::ts( $this->topic_open_time )->relative( 1 ) )
			) ), '', '', array( 'ipsBadge--text' ) );
		}
		elseif( !$this->locked() and $this->topic_close_time and $this->topic_close_time > time() )
		{
			$return['lock'] = new Badge('ipsBadge--locks', Member::loggedIn()->language()->addToStack('topic_locks_at_short', true, array(
				'sprintf' => array( DateTime::ts($this->topic_close_time)->relative(1))
			)), '', 'clock',  array('ipsBadge--text'));
		}

		return $return;
	}

	/**
	 * Get the post summary blurb (returns empty string when not on cloud/when no summary exists)
	 *
	 * @return string
	 */
	public function get_postSummaryBlurb() : string
	{
		return Bridge::i()->topicPostSummaryBlurb( $this );
	}

	/**
	 * Get the estimated read time in MINUTES for this topic
	 *
	 * @return int|null
	 */
	public function get_estimatedReadTime() : int|null
	{
		static $readTime = false;
		if ( $readTime === false )
		{
			$readTime = Bridge::i()->topicEstimatedReadTimeMinutes( $this );
		}
		return $readTime;
	}

	/**
	 * Get the estimated time in MINUTES to read the summary of this topic
	 *
	 * @return int|null
	 */
	public function get_estimatedSummaryReadTime() : int|null
	{
		static $readSTime = false;
		if ( $readSTime === false )
		{
			$readSTime = Bridge::i()->topicEstimatedReadTimeMinutes( $this, true );
		}
		return $readSTime;
	}

	/**
	 * Check if this topic has a summary
	 *
	 * @return bool
	 */
	public function hasSummary() : bool
	{
		return Bridge::i()->_topicHasSummary( $this );
	}

	/**
	 * @return int|null
	 */
	public function get_summarySize() : int|null
	{
		return Bridge::i()->topicSummarySize( $this );
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		if( $contentImage = $this->contentImages(1) )
		{
			$attachType = key( $contentImage[0] );
			try
			{
				return File::get( $attachType, $contentImage[0][ $attachType ] );
			}
			catch( \Exception ){}
		}

		return parent::primaryImage();
	}

	/**
	 * Overrides the parent method so we respect the ACP setting
	 *
	 * @return bool
	 */
	public function commentsUseCommentEditor (): bool
	{
		return (bool) Settings::i()->forum_post_use_minimal_editor;
	}
}