<?php
/**
 * @brief		Post Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		8 Jan 2014
 */

namespace IPS\forums\Topic;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content\Anonymous;
use IPS\Content\Comment;
use IPS\Content\EditHistory;
use IPS\Content\Embeddable;
use IPS\Content\Filter;
use IPS\Content\Helpful;
use IPS\Content\Hideable;
use IPS\Content\IntersectionViewTracking;
use IPS\Content\Item;
use IPS\Content\Featurable;
use IPS\Content\Reactable;
use IPS\Content\Recognizable;
use IPS\Content\Reportable;
use IPS\Content\Shareable;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\forums\Topic;
use IPS\Member;
use IPS\Node\Model;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use IPS\Application;
use IPS\cms\Records;
use OutOfRangeException;
use function chr;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Post Model
 */
class Post extends Comment implements Embeddable,
	Filter
{
	use	Reactable,
		Reportable,
		Recognizable,
		Anonymous,
		Shareable,
		EditHistory,
		Helpful,
		Hideable,
		IntersectionViewTracking,
		Featurable
		{
			Hideable::onHide as public _onHide;
			Hideable::onUnhide as public _onUnhide;
		}
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'pid';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static ?string $itemClass = 'IPS\forums\Topic';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'forums_posts';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = '';
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'forums';

	/**
	 * @brief	Title
	 */
	public static string $title = 'post';
	
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'item'				=> 'topic_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'post',
		'date'				=> 'post_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_show'			=> 'append_edit',
		'edit_member_name'	=> 'edit_name',
		'edit_reason'		=> 'post_edit_reason',
		'hidden'			=> 'queued',
		'first'				=> 'new_topic',
		'is_anon'			=> 'post_field_t2',
		'featured'			=> 'post_featured'
	);
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'comments';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static array $commentTemplate = array( array( 'topics', 'forums', 'front' ), 'postContainer' );
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static ?string $hideLogKey = 'post';
	
	/**
	 * @brief	Bitwise values for post_bwoptions field
	 */
	public static array $bitOptions = array(
		'post_bwoptions' => array(
			'post_bwoptions' => array(
				'best_answer'	=> 1
			)
		)
	);
	
	/**
	 * Join profile fields when loading comments?
	 */
	public static bool $joinProfileFields = TRUE;

	/**
	 * @brief   Used for the datalayer
	 */
	public static string $commentType = 'reply';

	/**
	 * @brief	Value to set for the 'tab' parameter when redirecting to the comment (via _find())
	 */
	public static ?array $tabParameter	= NULL; /* We do not want ?tab=comments appended to links in from findComment links */

	/**
	 * Create comment
	 *
	 * @param Item $item The content item just created
	 * @param string $comment The comment
	 * @param bool $first Is the first comment?
	 * @param string|null $guestName If author is a guest, the name to use
	 * @param bool|null $incrementPostCount Increment post count? If NULL, will use static::incrementPostCount()
	 * @param Member|null $member The author of this comment. If NULL, uses currently logged in member.
	 * @param DateTime|null $time The time
	 * @param string|null $ipAddress The IP address or NULL to detect automatically
	 * @param int|null $hiddenStatus NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @param int|null $anonymous NULL for no value, 0 or 1 for a value (0=no, 1=yes)
	 * @return Post|null
	 */
	public static function create( Item $item, string $comment, bool $first=false, string|null $guestName=null, bool|null $incrementPostCount= null, Member|null $member= null, DateTime|null $time= null, string|null $ipAddress= null, int|null $hiddenStatus= null, int|null $anonymous= null ): static|null
	{
		$comment = parent::create( $item, $comment, $first, $guestName, $incrementPostCount, $member, $time, $ipAddress, $hiddenStatus, $anonymous );
		
		if( Application::appIsEnabled( 'cms' ) )
		{
			static::recordSync( $item );
		}
		
		return $comment;
	}

	/**
	 * Syncing to run when hiding
	 *
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onHide( Member|false|null $member ) : void
	{
		$this->_onHide( $member );

		if( Application::appIsEnabled( 'cms' ) )
		{
			static::recordSync( $this->item() );
		}
	}

	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool					$approving	If true, is being approved for the first time
	 * @param	Member|NULL|FALSE	$member	The member doing the action (NULL for currently logged in member, FALSE for no member)
	 * @return	void
	 */
	public function onUnhide( bool $approving, Member|false|null $member ) : void
	{
		$this->_onUnhide( $approving, $member );

		if( Application::appIsEnabled( 'cms' ) )
		{
			static::recordSync( $this->item() );
		}
	}
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 * @see		incrementPostCount
	 */
	public static function incrementPostCount( Model $container = NULL ): bool
	{
		return !$container or $container->inc_postcount;
	}

	/**
	 * Post count for member
	 *
	 * @param Member $member The memner
	 * @param bool $includeNonPostCountIncreasing
	 * @param bool $includeHiddenAndPendingApproval
	 * @return    int
	 */
	public static function memberPostCount( Member $member, bool $includeNonPostCountIncreasing = FALSE, bool $includeHiddenAndPendingApproval = TRUE ): int
	{
		$where = [];
		$where[] = [ 'author_id=?', $member->member_id ];
		
		if ( !$includeNonPostCountIncreasing )
		{
			$where[] = [ 'forums_topics.forum_id IN(?)', Db::i()->select( 'id', 'forums_forums', 'inc_postcount=1' ) ];
		}
		if ( !$includeHiddenAndPendingApproval )
		{
			$where[] = [ 'queued=0' ];
		}
		
		$query = Db::i()->select( 'COUNT(*)', 'forums_posts', $where );
		if ( !$includeNonPostCountIncreasing )
		{
			$query = $query->join( 'forums_topics', 'tid=topic_id' );
		}
		
		return $query->first();
	}

	/**
	 * Get items with permission check
	 *
	 * @param array $where Where clause
	 * @param string|null $order MySQL ORDER BY clause (NULL to order by date)
	 * @param int|array|null $limit Limit clause
	 * @param string|null $permissionKey A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param mixed $includeHiddenComments
	 * @param int $queryFlags Select bitwise flags
	 * @param Member|null $member The member (NULL to use currently logged in member)
	 * @param bool $joinContainer If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinComments If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param bool $joinReviews If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param bool $countOnly If true will return the count
	 * @param array|null $joins Additional arbitrary joins for the query
	 * @return    array|NULL|Comment        If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public static function getItemsWithPermission( array $where=array(), string $order= null, int|array|null $limit=10, string|null $permissionKey='read', mixed $includeHiddenComments= Filter::FILTER_AUTOMATIC, int $queryFlags=0, Member|null $member= null, bool $joinContainer=FALSE, bool $joinComments=FALSE, bool $joinReviews=FALSE, bool $countOnly=FALSE, array|null $joins= null ): mixed
	{
		$where = Topic::getItemsWithPermissionWhere( $where, $permissionKey, $member, $joinContainer );
		
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenComments, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins );
	}

	/**
	 * Check if a specific action is available for this Content.
	 * Default to TRUE, but used for overrides in individual Item/Comment classes.
	 *
	 * @param string $action
	 * @param Member|null $member
	 * @return bool
	 */
	public function actionEnabled( string $action, ?Member $member=null ) : bool
	{
		switch( $action )
		{
			case 'feature':
				if( !$this->item()->container()->can_view_others )
				{
					return FALSE;
				}
				break;

			case 'edit':
				if ( Application::appIsEnabled( 'cms' ) and $this->isFirst() AND Records::getLinkedRecord( $this->item() ) )
				{
					return FALSE;
				}
				break;
		}

		return parent::actionEnabled( $action, $member );
	}
	
	/* !Questions & Answers */
	
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
		
		/* Downvoting disabled? */
		if ( $rating === -1 and !Settings::i()->forums_answers_downvote )
		{
			return FALSE;
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
		
		/* Check the forum settings */
		if ( $this->item()->container()->qa_rate_answers !== NULL and $this->item()->container()->qa_rate_answers != '*' and !$member->inGroup( explode( ',', $this->item()->container()->qa_rate_answers ) ) )
		{
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 * [ActiveRecord]	Save
	 *
	 * @return    void
	 */
	public function save(): void
	{
		parent::save();
		$this->item()->clearVotes();
	}

    /**
     * Delete Post
     *
     * @return    void
     */
    public function delete(): void
	{
		/* It is possible to delete a post that is orphaned, so let's try to protect against that */
		try
		{
			$item	= $this->item();

			if( Application::appIsEnabled( 'cms' ) )
			{
				static::recordSync( $item );
			}
		}
		catch( OutOfRangeException $e )
		{
			$item	= NULL;
		}

        parent::delete();

    }
    
    /**
	 * Get the snippet (stripped of HTML)
	 *
	 * @param	int		$length		The length of the snippet to return
	 * @return string
	 */
	public function snippet( int $length=300 ) : string
	{
		$contentField = static::$databaseColumnMap['content'];
		$content = trim( str_replace( chr(0xC2) . chr(0xA0), ' ', strip_tags( preg_replace( "/(<br(?: \/)?>|<\/p>)/i", ' ', preg_replace( "#<blockquote(?:[^>]+?)>.+?(?<!<blockquote)</blockquote>#s", " ", preg_replace( "#<script(.*?)>(.*)</script>#uis", "", ' ' . $this->$contentField . ' ' ) ) ) ) ) );
		return mb_substr( $content, 0, $length ) . ( mb_strlen( $content ) > $length ? '&hellip;' : '' );
	}

	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		/* Set forum theme if it has been overridden */
		$this->item()->container()->setTheme();

		return parent::html();
	}
	
	/**
	 * Force HTML posting abilities to TRUE for this comment
	 * This is usually determined by the member group and Editor extension.
	 * Here it can be overridden on a per comment basis
	 *
	 * @note Used currently in applications/core/extensions/core/Queue/RebuildPosts when rebuilding
	 *
	 * @return boolean
	 */
	public function htmlParsingEnforced(): bool
	{
		return (boolean) $this->post_htmlstate > 0;
	}
	
	/**
	 * Reaction type
	 *
	 * @return	string
	 */
	public static function reactionType(): string
	{
		return 'pid';
	}

	/**
	 * Set this comment as solved
	 *
	 * @param 	boolean	$value		TRUE/FALSE value
	 * @return	void
	 */
	public function setSolved( bool $value ) : void
	{
		$this->post_bwoptions['best_answer'] = $value;
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( array $params ): string
	{
		$idField = static::$databaseColumnId;

		if ( $this->$idField == $this->item()->firstComment()->$idField )
		{
			if ( @$params['do'] === 'findComment' or @$params['embedDo'] === 'findComment' )
			{
				unset( $params['do'] );
				unset( $params['embedDo'] );
			}
			unset( $params['comment'] );
			unset( $params['embedComment'] );
			return $this->item()->embedContent( $params );
		}

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'embed.css', 'forums', 'front' ) );
		return Theme::i()->getTemplate( 'global', 'forums' )->embedPost( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}

	/**
	 * Create a query to fetch the "top members"
	 *
	 * @note	The intention is to formulate a query that will fetch the members with the most contributions
	 * @note	The group by confuses MySQL and it ends up using the 'queued' index, when the 'author_id' index is far better
	 * @param int $limit	The number of members to return
	 * @return	Select
	 */
	public static function topMembersQuery( int $limit ): Select
	{
		$query = parent::topMembersQuery( $limit );

		return $query->forceIndex( 'author_id' );
	}
	
	/**
	 * Webhook filters
	 *
	 * @return	array
	 */
	public function webhookFilters(): array
	{
		$return = parent::webhookFilters();
		try
		{
			$return['forums'] = $this->item()->container()->_id;
			$return['hasBestAnswer'] = (bool) $this->item()->topic_answered_pid;
		}
		catch( OutOfRangeException $e ) {}

		return $return;
	}

	/**
	 * Is this a future entry?
	 *
	 * @return bool
	 */
	public function isFutureDate(): bool
	{
		return $this->item()->isFutureDate();
	}

	/**
	 * Sync up the topic
	 *
	 * @param Item $item		Topic object
	 *
	 * @return void
	 */
	protected static function recordSync( Item $item ) : void
	{
		if( !Application::appIsEnabled( 'cms' ) )
		{
			return;
		}

		$synced = array();

		/* We used to restrict by forum ID in these two queries, but if you move a topic to a new forum then the counts no longer sync properly */
		foreach( Db::i()->select( '*', 'cms_database_categories', array( 'category_forum_record=? AND category_forum_comments=?', 1, 1 ) ) as $category )
		{
			try
			{
				if ( ! in_array( $category['category_database_id'], $synced ) )
				{
					$class    = '\IPS\cms\Records' . $category['category_database_id'];
					if( class_exists( $class ) )
					{
						$object	  = $class::load( $item->tid, 'record_topicid' );
						$object->syncRecordFromTopic( $item );

						/* Successful sync (no exception thrown, so lets skip this database from now on */
						$synced[] = $category['category_database_id'];
					}

				}
			}
			catch( OutOfRangeException  )
			{
			}
		}

		foreach( Db::i()->select( '*', 'cms_databases', array( 'database_forum_record=? AND database_forum_comments=?', 1, 1 ) ) as $database )
		{
			try
			{
				if ( ! in_array( $database['database_id'], $synced ) )
				{
					$class = '\IPS\cms\Records' . $database['database_id'];
					if( class_exists( $class ) )
					{
						$object = $class::load( $item->tid, 'record_topicid' );
						$object->syncRecordFromTopic( $item );
					}
				}
			}
			catch( OutOfRangeException )
			{
			}
		}
	}
}