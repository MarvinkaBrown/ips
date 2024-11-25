<?php
/**
 * @brief		Forum Node
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		7 Jan 2014
 */

namespace IPS\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application as SystemApplication;
use IPS\cms\Databases;
use IPS\Content\ClubContainer;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\Content\ViewUpdates;
use IPS\Data\Store;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\downloads\Category;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\Url as FormUrl;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Lang;
use IPS\Login;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Group;
use IPS\Node\Colorize;
use IPS\Node\DelayedCount;
use IPS\Node\Icon;
use IPS\Node\Model;
use IPS\Node\Permissions;
use IPS\Node\Statistics;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\Widget;
use OutOfBoundsException;
use OutOfRangeException;
use OverflowException;
use UnderFlowException;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_shift;
use function array_slice;
use function count;
use function defined;
use function explode;
use function get_called_class;
use function In_array;
use function intval;
use function is_array;
use function json_decode;
use function json_encode;
use function krsort;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forum Node
 */
class Forum extends Model implements Permissions
{
	use ClubContainer;
	use Colorize;
	use DelayedCount;
	use Statistics;
	use ViewUpdates;
	use Icon;

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'forums_forums';
			
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static ?string $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Parent ID Root Value
	 * @note	This normally doesn't need changing though some legacy areas use -1 to indicate a root node
	 */
	public static int $databaseColumnParentRootValue = -1;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'forums';
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static ?array $restrictions = array(
		'app'		=> 'forums',
		'module'	=> 'forums',
		'prefix' 	=> 'forums_',
		'map'		=> array( 'permissions' => 'forums_perms' ),
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static ?string $permApp = 'forums';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static ?string $permType = 'forum';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static array $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'reply'				=> 4,
		'attachments'		=> 5
	);
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static string $permissionLangPrefix = 'perm_forum_';
	
	/**
	 * @brief	Bitwise values for forums_bitoptions field
	 */
	public static array $bitOptions = array(
		'forums_bitoptions' => array(
			'forums_bitoptions' => array(
				'bw_disable_tagging'		  => 1,
				'bw_disable_prefixes'		  => 2,
				'bw_enable_answers'			  => 4, //deprecated
				'bw_solved_set_by_member'	  => 8,
				'bw_solved_set_by_moderator' => 16,
				'bw_fluid_view'				  => 32,
				'bw_enable_assignments'		  => 64
			)
		)
	);

	/**
	 * Mapping of node columns to specific actions (e.g. comment, review)
	 * Note: Mappings can also reference bitoptions keys.
	 *
	 * @var array
	 */
	public static array $actionColumnMap = array(
		'tags'				=> 'bw_disable_tagging', // bitoption
		'prefix'			=> 'bw_disable_prefixes' // bitoption
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'forums_forum_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static ?string $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static string $modPerm = 'forums';
	
	/**
	 * @brief	Content Item Class
	 */
	public static ?string $contentItemClass = 'IPS\forums\Topic';
	
	/**
	 * @brief	Icon
	 */
	public static string $icon = 'comments';

	/**
	 * Determines if this class can be extended via UI Extension
	 *
	 * @var bool
	 */
	public static bool $canBeExtended = true;

	/**
	 * @brief	FileStorage extension
	 */
	public static string $iconStorageExtension = 'forums_Icons';

	/**
	 * @var string
	 */
	public static string $iconFormPrefix = 'forum_';
	
	/**
	 * Callback from \IPS\Http\Url\Inernal::correctUrlFromVerifyClass()
	 *
	 * This is called when verifying the *the URL currently being viewed* is correct, before calling self::loadFromUrl()
	 * Can be used if there is a more effecient way to load and cache the objects that will be used later on that page
	 *
	 * @param Url $url	The URL of the page being viewed, which belongs to this class
	 * @return	void
	 */
	public static function preCorrectUrlFromVerifyClass( Url $url ) : void
	{
		static::loadIntoMemory();
	}
	
	/**
	 * Form fields prefix with "forum_" but the database columns do not have this prefix - let's strip for the massChange feature
	 *
	 * @param mixed $key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( mixed $key, mixed $value ) : void
	{
		if( mb_strpos( $key, "forum_" ) === 0 AND $key !== 'forum_allow_rating' )
		{
			$key = preg_replace( "/^forum_(.+?)$/", "$1", $key );
			$this->$key	= $value;
			return;
		}

		parent::__set( $key, $value );
	}

	/**
	 * When setting parent ID to -1 (category) make sure sub_can_post is toggled off too
	 *
	 * @param int $val	Parent ID
	 * @return	void
	 */
	protected function set_parent_id( int $val ) : void
	{
		$this->_data['parent_id']	= $val;
		$this->changed['parent_id']	= $val;

		/* sub_can_post should get set to 0 for a category */
		if( $val == -1 )
		{
			$this->sub_can_post	= 0;
		}
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_seo(): string
	{
		if( !$this->_data['name_seo'] )
		{
			$this->name_seo	= Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'forums_forum_' . $this->id ) );
			$this->save();
		}

		return $this->_data['name_seo'] ?: Friendly::seoTitle( Lang::load( Lang::defaultLanguage() )->get( 'forums_forum_' . $this->id ) );
	}

	/**
	 * Get number of items
	 *
	 * @return	int|null
	 */
	protected function get__items(): ?int
	{
		return $this->topics;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Items
	 * @return	void
	 */
	protected function set__items( int $val ) : void
	{
		$this->topics = $val;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		if( $this->isCombinedView() )
		{
			return array( 0 => 'positive', 1 => 'combined_fluid_view' );
		}

		return parent::get__badge();
	}

	/**
	 * Get number of comments
	 *
	 * @return	int|null
	 */
	protected function get__comments(): ?int
	{
		return $this->posts;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Comments
	 * @return	void
	 */
	protected function set__comments( int $val ) : void
	{
		$this->posts = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return	int|null
	 */
	protected function get__unapprovedItems() : ?int
	{
		return $this->queued_topics;
	}

	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return int|null
	 */
	protected function get__unapprovedComments(): ?int
	{
		return $this->queued_posts;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @param	int	$val	Unapproved Items
	 * @return	void
	 */
	protected function set__unapprovedItems( int $val ) : void
	{
		$this->queued_topics = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @param	int	$val	Unapproved Comments
	 * @return	void
	 */
	protected function set__unapprovedComments( int $val ) : void
	{
		$this->queued_posts = $val;
	}
	
	/**
	 * Get default sort key
	 *
	 * @return	string|null
	 */
	public function get__sortBy(): ?string
	{
		return $this->sort_key ?: 'last_post';
	}

	/**
	 * Set last post array
	 *
	 * @param array $value
	 * @return void
	 */
	public function set__last_post_data( array $value ): void
	{
		krsort( $value );
		$this->_data['last_post_data'] = json_encode( $value );
	}

	/**
	 * Get last post array
	 *
	 * @return	array
	 */
	protected function get__last_post_data(): array
	{
		if ( $this->_data['last_post_data'] !== null and !is_array( $this->_data['last_post_data'] ) )
		{
			$this->_data['last_post_data'] = json_decode( $this->_data['last_post_data'], true );
		}

		return $this->_data['last_post_data'] ?? [];
	}

	/**
	 * Wrapper for the last_post field because we now have an array of data
	 *
	 * @return int|null
	 */
	public function get_last_post() : ?int
	{
		$timestamps = array_keys( $this->_last_post_data );
		sort( $timestamps );
		return count( $timestamps ) ? array_pop( $timestamps ) : null;
	}

	/**
	 * Check the action column map if the action is enabled in this node
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAction( string $action ) : bool
	{
		switch( $action )
		{
			case 'moderate_items':
				return $this->moderateNewItems();
			case 'moderate_comments':
				return $this->moderateNewComments();
		}

		$return = parent::checkAction( $action );

		/* Some actions here are reversed, we mark them as disabled instead of enabled */
		if( in_array( $action, array( 'tags', 'prefix' ) ) )
		{
			return !$return;
		}

		return $return;
	}

	/**
	 * Container-level check to see if items should be moderated
	 *
	 * @return bool
	 */
	public function moderateNewItems() : bool
	{
		return $this->preview_posts == 1 or $this->preview_posts == 2;
	}

	/**
	 * Container-level check to see if comments should be moderated
	 *
	 * @return bool
	 */
	public function moderateNewComments() : bool
	{
		return $this->preview_posts == 1 or $this->preview_posts == 3;
	}

	/**
	 * Set last comment
	 *
	 * @param Comment|null $comment The latest comment or NULL to work it out
	 * @param Item|null $updatedItem We sometimes run setLastComment() when an item has been edited, if so, that item will be here
	 * @return    void
	 */
	protected function _setLastComment( Comment $comment=null, Item $updatedItem=NULL ): void
	{
		/* If we were given an item, but it's older than the current last post, clear it */
		if( $updatedItem !== NULL AND $updatedItem->mapped('last_comment') < $this->last_post )
		{
			$updatedItem = null;
		}

		/* If we have an item, check if it is already in the last post data */
		$lastPostData = [];
		if( $updatedItem !== null )
		{
			if ( $updatedItem->last_poster_id and ! $updatedItem->last_poster_name )
			{
				$member = Member::load( $updatedItem->last_poster_id );
				if ( $member->member_id )
				{
					$updatedItem->last_poster_name = $member->name;
				}
				else
				{
					$updatedItem->last_poster_name = '';
					$updatedItem->last_poster_id = 0;
				}
			}

			if( $comment === null )
			{
				$comment = $updatedItem->comments( 1, 0, 'date', 'desc', null, false, null, null, true ); // Bypass any cached data
			}

			$lastPostData = [
				$updatedItem->last_post => [
					'last_post' => $updatedItem->last_post,
					'last_poster_id' => $updatedItem->last_poster_id,
					'last_poster_name' => $updatedItem->last_poster_name,
					'seo_last_name' => Friendly::seoTitle( $updatedItem->last_poster_name ),
					'last_title' => $updatedItem->title,
					'seo_last_title' => $updatedItem->title_seo,
					'last_id' => $updatedItem->tid,
					'last_poster_anon' => $updatedItem->last_poster_anon,
					'last_post_snippet' => $comment?->truncated( true, 350 ),
					'posts' => $updatedItem->posts
				]
			];

			/* Add the current last post data to the new array, but check if we have a duplicate */
			foreach( $this->_last_post_data as $k => $v )
			{
				if( $v['last_id'] != $updatedItem->tid )
				{
					$lastPostData[ $k ] = $v;
				}
			}
		}

		/* If we don't have a count of 5, get what we need */
		if( count( $lastPostData ) < 5 )
		{
			try
			{
				/*
				 * We prefer fetching post, joining topic, etc. but that is not efficient and causes a temp table and filesort against posts table so we'll lean on the cached last_post value for the topic
				 * We also need to fetch from the write server in the event that something has just been deleted and the comment hasn't been passed.
				 * We also cannot look for future entries since this code is used in older upgrades, but we can look for the date instead.
				 */
				foreach( Db::i()->select( '*', 'forums_topics', array( "forums_topics.forum_id=? AND forums_topics.approved=1 AND forums_topics.state != ? AND forums_topics.last_post<=? AND forums_topics.publish_date <=?", $this->id, 'link', time(), time() ), 'forums_topics.last_post DESC', array( count( $lastPostData ), ( 5 - count( $lastPostData ) ) ) ) as $row )
				{
					$topic = Topic::constructFromData( $row );
					$lastComment = $topic->comments( 1, 0, 'date', 'desc', null, false );
					$lastPost = [];
					if ( $topic->last_poster_id and ! $topic->last_poster_name )
					{
						$member = Member::load( $topic->last_poster_id );
						if ( $member->member_id )
						{
							$lastPost['last_poster_name'] = $member->name;
							$lastPost['last_poster_id'] = $member->member_id;
						}
						else
						{
							$lastPost['last_poster_name'] = '';
							$lastPost['last_poster_id'] = 0;
						}
					}
					else
					{
						$lastPost['last_poster_id'] = (int) $topic->last_poster_id;
						$lastPost['last_poster_name'] = $topic->last_poster_name;
					}

					$lastPost['last_post'] = ( $lastComment ? $lastComment->post_date : $topic->last_post );
					$lastPost['seo_last_name'] = Friendly::seoTitle( $lastPost['last_poster_name'] );
					$lastPost['last_title'] = $topic->title;
					$lastPost['seo_last_title'] = Friendly::seoTitle( $topic->title );
					$lastPost['last_id'] = $topic->tid;
					$lastPost['last_poster_anon'] = $topic->last_poster_anon;
					$lastPost['last_post_snippet'] = $lastComment?->truncated( true, 350 );
					$lastPost['posts'] = $topic->posts - 1;

					$lastPostData[ $topic->last_post ] = $lastPost;
				}
			}
			catch ( UnderflowException $e ){}
		}

		$this->_last_post_data = $lastPostData;
		$this->save();
	}

	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @param   Member|null    $member         MemberObject
	 * @return	DateTime|NULL
	 */
	public function getLastCommentTime( Member $member = NULL ): ?DateTime
	{
		$member = $member ?: Member::loggedIn();
        if( !$this->memberCanAccessOthersTopics( $member ) )
        {
            try
            {
                $select = Db::i()->select('*', 'forums_posts', array("forums_posts.queued=0 AND forums_topics.forum_id={$this->id} AND forums_topics.approved=1 AND forums_topics.starter_id=?", $member->member_id), 'forums_posts.post_date DESC', 1)->join('forums_topics', 'forums_topics.tid=forums_posts.topic_id')->first();
            }
            catch ( UnderflowException $e )
            {
                return NULL;
            }

            return $select['last_post'] ?  DateTime::ts( $select['last_post'] ) : NULL;
        }

		if( $lastPost = $this->last_post )
		{
			return DateTime::ts( $lastPost );
		}

		return null;
	}

	/**
	 * Get last post data
	 *
	 * @param int $count	Max number of items, most available is 5
	 * @return    array|null
	 */
	public function lastPost( int $count=1 ): ?array
	{
		if ( $count > 5 )
		{
			throw new OverflowException( 'Too many posts to return, maximum is 5' );
		}

		if ( !$this->loggedInMemberHasPasswordAccess() )
		{
			return null;
		}
		elseif ( !$this->memberCanAccessOthersTopics( Member::loggedIn() ) )
		{
			try
			{
				$results = [];
				foreach( Db::i()->select( '*', 'forums_posts', array( 'topic_id=? AND queued=0', Db::i()->select( 'tid', 'forums_topics', array( 'forum_id=? AND approved=1 AND starter_id=? AND is_future_entry=0', $this->_id, Member::loggedIn()->member_id ), 'last_post DESC', 1 )->first() ), 'post_date DESC', $count ) as $post )
				{
					$results[ $post->post_date ] = [
						'author'		    => $post->author(),
						'topic_url'		    => $post->item()->url(),
						'topic_title'	    => $post->item()->title,
						'date'			    => $post->post_date,
						'last_poster_anon'  => $post->mapped('is_anon'),
						'last_post_snippet' => $post->truncated( true, 350 ),
						'posts' 			=> $post->item()->posts - 1
					];
				}
			}
			catch ( UnderflowException $e )
			{
				$results = [];
			}

			foreach( $this->children() as $child )
			{
				/* We need to merge all children and pick the top 5 */
				if ( $childLastPost = $child->lastPost( $count ) )
				{
					$results = $results +  $childLastPost;
				}
			}

			if ( count( $results ) )
			{
				krsort( $results );
				if ( $count === 1 )
				{
					/* return the first from the array */
					return array_shift( $results );
				}

				/* array_slice re-indexes, and we'd lose the krsort timestamp key */
				return array_intersect_key( $results, array_flip( array_slice( array_keys( $results ), 0, $count ) ) );
			}
			else
			{
				return null;
			}
		}
		elseif ( !$this->permission_showtopic and !$this->can('view') )
		{
			$results = [];

			if( !$this->sub_can_post )
			{
				foreach( $this->children() as $child )
				{
					/* We need to merge all children and pick the top 5 */
					if ( $childLastPost = $child->lastPost( $count ) )
					{
						$results = $results +  $childLastPost;
					}
				}
			}

			if ( count( $results ) )
			{
				krsort( $results );
				if ( $count === 1 )
				{
					/* return the first from the array */
					return array_shift( $results );
				}

				/* array_slice re-indexes, and we'd lose the krsort timestamp key */
				return array_intersect_key( $results, array_flip( array_slice( array_keys( $results ), 0, $count ) ) );
			}
			else
			{
				return null;
			}
		}
		else
		{
			$results = [];

			/* Do we have any data, or do we need to update? The default or cleared state is NULL, forums without topics should have [] */
			if ( $this->sub_can_post and $this->posts and ! $this->club_id and ! $this->_data['last_post_data'] )
			{
				$this->setLastComment();

				/* We can't use save as dynamic variables have now been assigned which confuses save() */
				Db::i()->update( 'forums_forums', [ 'last_post_data' => $this->_data['last_post_data'] ], [ 'id=?', $this->_id ] );
			}

			$lastPostData = $this->_last_post_data;
			if ( count( $lastPostData ) )
			{
				foreach( $lastPostData as $lastPostTime => $lastPost )
				{
					if ( $lastPost['last_poster_id'] )
					{
						$lastAuthor = Member::load( $lastPost['last_poster_id'] );
					}
					else
					{
						$lastAuthor = new Member;
						if ( $lastPost['last_poster_name'] )
						{
							$lastAuthor->name = $lastPost['last_poster_name'];
						}
					}

					if ( $this->sub_can_post and !$this->permission_showtopic and ! $this->can('read') )
					{
						$lastPost['last_title'] = null;
					}
					$results[ $lastPostTime ] = array(
						'author'		    => $lastAuthor,
						'topic_url'		    => Url::internal( "app=forums&module=forums&controller=topic&id={$lastPost['last_id']}", 'front', 'forums_topic', array( $lastPost['seo_last_title'] ) ),
						'topic_title'	    => $lastPost['last_title'],
						'date'			    => $lastPostTime,
						'last_poster_anon'  => $lastPost['last_poster_anon'],
						'last_post_snippet' => $lastPost['last_post_snippet'] ?? null,
						'posts' 			=> $lastPost['posts'] ?? 0
					);
				}
			}

			foreach( $this->children() as $child )
			{
				/* @var Forum $child */
				/* We need to merge all children and pick the top 5 */
				if ( $childLastPost = $child->lastPost( $count ) )
				{
					if( is_array( $childLastPost ) )
					{
						if( isset( $childLastPost['date'] ) )
						{
							$results[$childLastPost['date']] = $childLastPost;
						}
						else
						{
							$results = $results + $childLastPost;
						}
					}
				}
			}

			if ( count( $results ) )
			{
				krsort( $results );
				if ( $count === 1 )
				{
					/* return the first from the array */
					return array_shift( $results );
				}
				/* array_slice re-indexes, and we'd lose the krsort timestamp key */
				return array_intersect_key( $results, array_flip( array_slice( array_keys( $results ), 0, $count ) ) );
			}
			else
			{
				return null;
			}
		}
	}
	
	/**
	 * Permission Types
	 *
	 * @return	array
	 */
	public function permissionTypes():array
	{
		if ( !$this->sub_can_post )
		{
			return array( 'view' => 'view' );
		}
		return static::$permissionMap;
	}
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns(): array
	{
		$return = parent::basicDataColumns();
		$return[] = 'forums_bitoptions';
		$return[] = 'password';
		$return[] = 'password_override';
		$return[] = 'club_id';
		return $return;
	}
	
	/**
	 * Check if the currently logged in member has access to a password protected forum
	 *
	 * @return	bool
	 */
	public function loggedInMemberHasPasswordAccess(): bool
	{
		if ( $this->password === NULL )
		{
			return TRUE;
		}
		
		if ( Member::loggedIn()->inGroup( explode( ',', $this->password_override ) ) )
		{
			return TRUE;
		}
		
		if ( isset( Request::i()->cookie[ 'ipbforumpass_' . $this->id ] ) and Login::compareHashes( md5( $this->password ), Request::i()->cookie[ 'ipbforumpass_' . $this->id ] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Password Form
	 *
	 * @return	Form|NULL
	 * @note	Return of NULL indicates password has been provided correctly
	 */
	public function passwordForm(): ?Form
	{
		/* Already have access? */
		if ( $this->loggedInMemberHasPasswordAccess() && !isset( Request::i()->passForm ) )
		{
			return NULL;
		}
		
		/* Build form */
		$password = $this->password;
		$form = new Form( 'forum_password', 'continue' );
		$form->class = 'ipsForm--vertical ipsForm--password-form';
		$form->add( new Password( 'password', NULL, TRUE, array(), function( $val ) use ( $password )
		{
			if ( $val != $password )
			{
				throw new DomainException( 'forum_password_bad' );
			}
		} ) );
		
		/* If we got the value, it's fine */
		if ( $form->values() )
		{
			/* Set Cookie */
			$this->setPasswordCookie( $password );
			
			/* If we have a topic ID, redirect to it */
			if ( isset( Request::i()->topic ) )
			{
				try
				{
					Output::i()->redirect( Topic::loadAndCheckPerms( Request::i()->topic )->url() );
				}
				catch ( OutOfRangeException $e ) { }
			}
			
			/* Make sure passForm isn't returned on the URL if viewing the forum */
			if ( isset( Request::i()->module ) and isset( Request::i()->controller ) and Request::i()->module === 'forums' and Request::i()->controller === 'forums' )
			{
				Output::i()->redirect( $this->url() );
			}
			
			/* Return */
			return NULL;
		}
		
		/* Return */
		return $form;
	}
	
	/**
	 * Set Password Cookie
	 *
	 * @param string $password	Password to set for forum
	 * @return	void
	 */
	public function setPasswordCookie( string $password ) : void
	{
		Request::i()->setCookie( 'ipbforumpass_' . $this->id, md5( $password ), DateTime::create()->add( new DateInterval( 'P7D' ) ) );
	}
	
	/**
	 * Set Theme
	 *
	 * @return	void
	 */
	public function setTheme() : void
	{
		if ( $this->skin_id )
		{
			try
			{
				Theme::switchTheme( $this->skin_id );
			}
			catch ( Exception $e ) { }
		}
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		/* @todo remove this temp fix */
		if( !isset( Store::i()->_forumsChecked ) OR !Store::i()->_forumsChecked )
		{
			Db::i()->update( 'forums_forums', array( 'parent_id' => '-1' ), array( 'parent_id=?', 0 ) );
			Store::i()->_forumsChecked = 1;
		}

		return parent::roots( $permissionCheck, $member, $where, $limit );
	}
	
	/**
	 * Load into memory (taking permissions into account)
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	array				$where				Additional where clause
	 * @return	void
	 */
	public static function loadIntoMemory( ?string $permissionCheck='view', ?Member $member=NULL, array $where = array() ) : void
	{
		/* @todo remove this temp fix */
		if( !isset( Store::i()->_forumsChecked ) OR !Store::i()->_forumsChecked )
		{
			Db::i()->update( 'forums_forums', array( 'parent_id' => '-1' ), array( 'parent_id=?', 0 ) );
			Store::i()->_forumsChecked = 1;
		}

		$member = $member ?: Member::loggedIn();
		
		if ( in_array( $permissionCheck, array( 'add', 'reply' ) ) )
		{
			$where[] = array( 'sub_can_post=1' );

			if ( static::customPermissionNodes() )
			{
				$whereString = 'password=? OR ' . Db::i()->findInSet( 'forums_forums.password_override', $member->groups );
				$whereParams = array( NULL );
				if ( $member->member_id === Member::loggedIn()->member_id )
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
				$where[] = array_merge( array( '( ' . $whereString . ' )' ), $whereParams );
			}
		}
		
		parent::loadIntoMemory( $permissionCheck, $member, $where );
	}
	
	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission						A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param Group|Member|null $member							The member or group to check (NULL for currently logged in member)
	 * @param bool $considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public function can( mixed $permission, Group|Member $member=NULL, bool $considerPostBeforeRegistering = TRUE ): bool
	{
		if ( !$this->sub_can_post and in_array( $permission, array( 'add', 'reply' ) ) )
		{
			return FALSE;
		}
						
		$return = parent::can( $permission, $member, $considerPostBeforeRegistering );
		
		if ( $return === TRUE and $this->password !== NULL and in_array( $permission, array( 'read', 'add' ) ) and ( ( $member !== NULL and $member->member_id !== Member::loggedIn()->member_id ) or !$this->loggedInMemberHasPasswordAccess() ) )
		{
			return FALSE;
		}
		
		return $return;
	}
	
	/**
	 * Get "No Permission" error message
	 *
	 * @return	string
	 */
	public function errorMessage(): string
	{
		if ( Member::loggedIn()->language()->checkKeyExists( "forums_forum_{$this->id}_permerror" ) )
		{
			$message = trim( Member::loggedIn()->language()->get( "forums_forum_{$this->id}_permerror" ) );
			if ( $message and $message != '<p></p>' )
			{
				return Theme::i()->getTemplate('global', 'core', 'global')->richText( $message, array('') );
			}
		}
		
		return 'node_error_no_perm';
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @param Url $url		Base URL
	 * @param bool $subnode	Is this a subnode?
	 * @return    array
	 */
	public function getButtons( Url $url, bool $subnode=FALSE ): array
	{
		$buttons = parent::getButtons( $url, $subnode );
		
		if ( isset( $buttons['permissions'] ) )
		{
			$buttons['permissions']['data'] = NULL;
		}
		
		if ( !$this->sub_can_post and isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'forums_add_child_cat';
		}

		if ( isset( $buttons['delete'] ) AND $this->isUsedByADownloadsCategory() )
		{
			unset( $buttons['delete']['data'] );
		}

		if ( isset( $buttons['delete'] ) AND $this->isUsedByCms() )
		{
			unset( $buttons['delete']['data'] );
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$groups = array();
		foreach ( Group::groups() as $k => $v )
		{
			$groups[ $k ] = $v->name;
		}

		$form->class = 'ipsForm--horizontal ipsForm--forum-settings';
		$form->addTab( 'forum_settings' );
		$form->addHeader( 'forum_settings' );
		$form->add( new Translatable( 'forum_name', NULL, TRUE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}" : NULL ) ) ) );
		$form->add( new Translatable( 'forum_description', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_desc" : NULL ), 'editor' => array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-forum-{$this->id}" : "forums-new-forum" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'forum_description_placeholder' ) ) ) );
		
		$type = 'normal';
		if ( $this->id )
		{
			if ( $this->redirect_url )
			{
				$type = 'redirect';
			}
			elseif ( !$this->sub_can_post )
			{
				$type = 'category';
			}
		}
		elseif ( !isset( Request::i()->parent ) )
		{
			$type = 'category';
		}
				
		$id = $this->id ?: 'new';
		$form->add( new Radio( 'forum_type', $type, TRUE, array(
			'options' => array(
				'normal' 	=> 'forum_type_normal',
				'category'	=> 'forum_type_category',
				'redirect'	=> 'forum_type_redirect'
			),
			'toggles'	=> array(
				'normal'	=> array(
					'forum_password_on',
					'forum_ipseo_priority',
					'forum_can_view_others',
					'forum_permission_showtopic',
					'forum_permission_custom_error',
					"form_{$id}_header_permissions",
					"form_{$id}_tab_forum_display",
					'forum_disable_sharelinks',
					"form_{$id}_tab_posting_settings",
					"form_{$id}_header_forum_display_topic",
					"form_{$id}_tab_permissions",
					'forum_preview_posts',
					'forum_icon_choose',
					'forum_sort_key',
					'forum_feature_color'
				),
				'category'	=> array(
					"form_{$id}_tab_forum_display",
					'forum_feature_color'
				),
				'redirect'	=> array(
					'forum_password_on',
					'forum_redirect_url',
					'forum_redirect_hits',
					'forum_icon_choose',
					'forum_feature_color'
				),
			)
		) ) );

		$class = get_called_class();

		$form->add( new Node( 'forum_parent_id', ( !$this->id AND $this->parent_id === -1 ) ? NULL : ( $this->parent_id === -1 ? 0 : $this->parent_id ), FALSE, array(
			'class'		      	=> '\IPS\forums\Forum',
			'disabled'	      	=> array(),
			'zeroVal'         	=> 'node_no_parentf',
			'zeroValTogglesOff'	=> array( 'form_new_forum_type', 'forum_icon', 'forum_card_image' ),
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}

				return !isset( Request::i()->id ) or ( $node->id != Request::i()->id and !$node->isChildOf( $node::load( Request::i()->id ) ) );
			}
		), function( $val )
		{
			if ( !$val and Request::i()->forum_type !== 'category' )
			{
				throw new DomainException('forum_parent_id_error');
			}
		} ) );

		$form->add( new FormUrl( 'forum_redirect_url', $this->id ? $this->redirect_url : array(), FALSE, array( 'placeholder' => 'http://www.example.com/' ),
			function( $val )
			{
				if ( !$val and Request::i()->forum_type === 'redirect' )
				{
					throw new DomainException('form_required');
				}
			}, NULL, NULL, 'forum_redirect_url' ) );
		$form->add( new Number( 'forum_redirect_hits', $this->id ? $this->redirect_hits : 0, FALSE, array(), NULL, NULL, NULL, 'forum_redirect_hits' ) );
		$form->add( new YesNo( 'forum_password_on', $this->id ? ( $this->password !== NULL ) : FALSE, FALSE, array( 'togglesOn' => array( 'forum_password', 'forum_password_override' ) ), NULL, NULL, NULL, 'forum_password_on' ) );
		$form->add( new Password( 'forum_password', $this->password, FALSE, array(), NULL, NULL, NULL, 'forum_password' ) );
		$form->add( new CheckboxSet( 'forum_password_override', $this->id ? explode( ',', $this->password_override ) : array(), FALSE, array( 'options' => $groups, 'multiple' => TRUE ), NULL, NULL, NULL, 'forum_password_override' ) );
		if ( count( Theme::themes() ) > 1 )
		{
			$themes = array( 0 => 'forum_skin_id_default' );
			foreach ( Theme::themes() as $theme )
			{
				$themes[ $theme->id ] = $theme->_title;
			}
			$form->add( new Select( 'forum_skin_id', $this->id ? $this->skin_id : 0, FALSE, array( 'options' => $themes ), NULL, NULL, NULL, 'forum_skin_id' ) );
		}

		$form->addHeader( 'forum_solved_options' );

		$mode = 'off';
		if ( $this->id )
		{
			if ( $this->forums_bitoptions['bw_solved_set_by_moderator'] and $this->forums_bitoptions['bw_solved_set_by_member'] )
			{
				$mode = 'starter_and_mods';
			}
			else if ( $this->forums_bitoptions['bw_solved_set_by_moderator'] )
			{
				$mode = 'mods';
			}
		}

		$form->add( new Radio( 'forum_solved_mode', $this->id ? $mode : 'off', FALSE, array(
			'options' => [
				'off' => 'forum_solved_mode_off',
				'mods' => 'forum_solved_mode_mods',
				'starter_and_mods' => 'forum_solved_mode_starter'
			]
		), NULL, NULL, NULL, 'forum_solved_mode' ) );

		if( Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			$form->add( new YesNo( 'forum_enable_assignments', $this->forums_bitoptions['bw_enable_assignments'], false, array(), null, null, null, 'forum_enable_assignments' ) );
		}

		$form->addHeader( 'forum_display_forum' );

		$sortOptions = array( 'last_post' => 'sort_updated', 'last_real_post' => 'sort_last_comment', 'posts' => 'sort_num_comments', 'views' => 'sort_views', 'title' => 'sort_title', 'starter_name' => 'sort_author_name', 'last_poster_name' => 'sort_last_comment_name', 'start_date' => 'sort_date' );

		$form->add( new Select( 'forum_sort_key', $this->id ? $this->sort_key : 'last_post', FALSE, array( 'options' => $sortOptions ), NULL, NULL, NULL, 'forum_sort_key' ) );

		/* Show the combined fluid view option if we have children */
		$disabledFluidView = FALSE;
		if ( ! $this->hasChildren() )
		{
			$disabledFluidView = TRUE;
			$this->forums_bitoptions['bw_fluid_view'] = FALSE;

			Member::loggedIn()->language()->words['bw_fluid_view_warning'] = Member::loggedIn()->language()->addToStack( 'bw_fluid_view__warning' );
		}

		$form->add( new YesNo( 'bw_fluid_view', $this->id ? $this->forums_bitoptions['bw_fluid_view'] : FALSE, FALSE, array( 'disabled' => $disabledFluidView ), NULL, NULL, NULL, 'bw_fluid_view' ) );

		/* Customi(s|z)ations */
		$form->addTab( 'forum_customizations' );
		$form->addHeader( 'forum_customizations' );

		$form->add( new Color( 'forum_feature_color', $this->feature_color ?: '', FALSE, array( 'allowNone' => true ), NULL, NULL, NULL, 'forum_feature_color' ) );

		/* Icon fields */
		$this->iconFormFields( $form );

		$form->add( new Upload( 'forum_card_image', $this->card_image ? File::get( 'forums_Cards', $this->card_image ) : NULL, FALSE, array( 'image' => array( 'maxWidth' => 800, 'maxHeight' => 800 ), 'storageExtension' => 'forums_Cards', 'allowStockPhotos' => TRUE ), NULL, NULL, NULL, 'forum_card_image' ) );
		
		$form->addTab( 'permissions' );
		$form->addHeader( 'permissions' );

		$previewPosts = array();
		if ( $this->id )
		{
			switch ( $this->preview_posts )
			{
				case 1:
					$previewPosts = array( 'topics', 'posts' );
					break;
				case 2:
					$previewPosts = array( 'topics' );
					break;
				case 3:
					$previewPosts = array( 'posts' );
					break;
			}
		}

		$form->add( new CheckboxSet( 'forum_preview_posts', $previewPosts, FALSE, array( 'options' => array( 'topics' => 'forum_preview_posts_topics', 'posts' => 'forum_preview_posts_posts' ) ), NULL, NULL, NULL, 'forum_preview_posts' ) );
		$form->add( new YesNo( 'forum_can_view_others', $this->id ? $this->can_view_others : TRUE, FALSE, array(), NULL, NULL, NULL, 'forum_can_view_others' ) );
		$form->add( new YesNo( 'forum_permission_showtopic', $this->permission_showtopic ?: 0, FALSE, array(), NULL, NULL, NULL, 'forum_permission_showtopic' ) );
		$form->add( new Translatable( 'forum_permission_custom_error', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_permerror" : NULL ), 'editor' => array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-permerror-{$this->id}" : "forums-new-permerror" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'permerror' ) : NULL, 'minimize' => 'forum_permerror_placeholder' ) ), NULL, NULL, NULL, 'forum_permission_custom_error' ) );
		$form->add( new Select( 'forum_ipseo_priority', $this->id ? $this->ipseo_priority : '-1', FALSE, array(
			'options' => array(
				'1'		=> '1',
				'0.9'	=> '0.9',
				'0.8'	=> '0.8',
				'0.7'	=> '0.7',
				'0.6'	=> '0.6',
				'0.5'	=> '0.5',
				'0.4'	=> '0.4',
				'0.3'	=> '0.3',
				'0.2'	=> '0.2',
				'0.1'	=> '0.1',
				'0'		=> 'sitemap_do_not_include',
				'-1'	=> 'sitemap_default_priority'
			)
		), NULL, NULL, NULL, 'forum_ipseo_priority' ) );

		/* Rules */
		$form->addTab( 'forum_rules' );
		$form->addHeader( 'forum_rules' );

		$form->add( new Radio( 'forum_show_rules', $this->id ? $this->show_rules : 0, FALSE, array(
			'options' => array(
				0	=> 'forum_show_rules_none',
				1	=> 'forum_show_rules_link',
				2	=> 'forum_show_rules_full'
			),
			'toggles'	=> array(
				1	=> array(
					'forum_rules_title',
					'forum_rules_text'
				),
				2	=> array(
					'forum_rules_title',
					'forum_rules_text'
				),
			)
		) ) );

		$form->add( new Translatable( 'forum_rules_title', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_rulestitle" : NULL ) ), NULL, NULL, NULL, 'forum_rules_title' ) );
		$form->add( new Translatable( 'forum_rules_text', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_rules" : NULL ), 'editor' => array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-rules-{$this->id}" : "forums-new-rules" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'rules' ) : NULL ) ), NULL, NULL, NULL, 'forum_rules_text' ) );

		$form->addTab( 'topic_and_post_settings' );
		$form->addHeader( 'forum_display_topic' );

		$form->add( new YesNo( 'forum_disable_sharelinks', $this->id ? !$this->disable_sharelinks : TRUE, FALSE, array(), NULL, NULL, NULL, 'forum_disable_sharelinks' ) );
		$form->add( new Interval( 'solved_stats_from_cutoff', $this->id ? $this->solved_stats_from_cutoff : 0, FALSE, array( 'valueAs'	 => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'alltime' ), NULL, NULL, NULL, 'solved_stats_from_cutoff' ) );

		$form->addHeader('posts');
		$form->add( new YesNo( 'forum_inc_postcount', $this->id ? $this->inc_postcount : TRUE, FALSE, array() ) );
		$form->add( new YesNo( 'allow_anonymous', $this->id ? $this->allow_anonymous : FALSE, FALSE, array() ) );

		$form->addHeader( 'polls' );
		$form->add( new YesNo( 'forum_allow_poll', $this->id ? $this->allow_poll : TRUE, FALSE, array() ) );

		parent::form( $form );
	}
	
	/**
	 * [Node] Can this node have children?
	 *
	 * @return bool
	 */
	public function canAdd(): bool
	{
		if ( $this->redirect_on )
		{
			return FALSE;
		}
		return parent::canAdd();
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		/* Type */
		if ( isset( $values['forum_parent_id'] ) AND $values['forum_parent_id'] === 0 )
		{
			$values['forum_type'] = 'category';
		}
		
		if ( isset( $values['forum_type'] ) )
		{
			if( $values['forum_type'] !== 'redirect' )
			{
				$values['sub_can_post'] = ( $values['forum_type'] !== 'category' );
				$values['redirect_on'] = FALSE;
				$values['forum_redirect_url'] = NULL;
			}
			else
			{
				$values['sub_can_post'] = FALSE;
				$values['redirect_on'] = TRUE;
			}
		}

		/* Figure out solved mode */
		if ( isset( $values['forum_solved_mode'] ) )
		{
			$values['bw_solved_set_by_moderator'] = false;
			$values['bw_solved_set_by_member'] = false;

			if ( $values['forum_solved_mode'] == 'starter_and_mods' )
			{
				$values['bw_solved_set_by_moderator'] = true;
				$values['bw_solved_set_by_member'] = true;
			}
			else if ( $values['forum_solved_mode'] == 'mods' )
			{
				$values['bw_solved_set_by_moderator'] = true;
			}

			unset( $values['forum_solved_mode'] );
		}

		if( isset( $values['forum_enable_assignments'] ) )
		{
			$values['bw_enable_assignments'] = $values['forum_enable_assignments'];
			unset( $values['forum_enable_assignments'] );
		}

		if ( isset( $values['forum_parent_id'] ) )
		{
			if ( $values['forum_parent_id'] )
			{
				$values['forum_parent_id'] = is_scalar( $values['forum_parent_id'] ) ? intval( $values['forum_parent_id'] ) : intval( $values['forum_parent_id']->id );
			}
			else
			{
				$values['forum_parent_id'] = -1;
			}
		}
		
		/* Bitwise */
		foreach ( array( 'bw_solved_set_by_member', 'bw_solved_set_by_moderator', 'bw_fluid_view', 'bw_enable_assignments' ) as $k )
		{
			if( isset( $values[ $k ] ) )
			{
				/* If were disabling bw_solved_set_by_moderator for discussion forums, we need to make sure bw_solved_set_by_member is also disabled */
				if ( $values['forum_type'] == 'normal' AND $k == 'bw_solved_set_by_moderator' AND !$values[ $k ] )
				{
					$values['forums_bitoptions']['bw_solved_set_by_moderator'] = FALSE;
					$values['forums_bitoptions']['bw_solved_set_by_member'] = FALSE;
					unset( $values['bw_solved_set_by_moderator'], $values['bw_solved_set_by_member'] );
				}
				else
				{
					$values['forums_bitoptions'][ $k ] = ( in_array( $k, array( 'bw_disable_tagging', 'bw_disable_prefixes' ) ) ) ? !$values[ $k ] : $values[ $k ];
					unset( $values[ $k ] );
				}
			}
		}


		if ( isset( $values['solved_stats_from_cutoff'] ) )
		{
			/* If answers is disabled, set this to no. */
			if ( !$values['forums_bitoptions']['bw_solved_set_by_moderator'] )
			{
				$values['solved_stats_from_cutoff'] = 0;
			}
			else
			{
				/* Otherwise we need to reset the current time so it rebuilds */
				$values['solved_stats_from'] = 0;
			}
		}

		/* Save icon fields */
		$values = $this->formatIconFieldValues( $values );

		/* Remove forum_ prefix */
		$_values = $values;
		$values = array();
		foreach ( $_values as $k => $v )
		{
			if( mb_substr( $k, 0, 6 ) === 'forum_' )
			{
				$values[ mb_substr( $k, 6 ) ] = $v;
			}
			else
			{
				$values[ $k ]	= $v;
			}
		}
		
		/* Implode */
		if( isset( $values['password_override'] ) )
		{
			$values['password_override'] = is_array( $values['password_override'] ) ? implode( ',', $values['password_override'] ) : $values['password_override'];
		}

		/* Set forum password to NULL if not there */
		if ( isset( $values['password'] ) AND ( $values['password'] === '' or !$values['password_on'] ) )
		{
			$values['password'] = NULL;
		}

		/* Reset password and can view others if toggling back to a category */
		if( isset( $values['type'] ) AND in_array( $values['type'], array( 'category', 'redirect' ) ) )
		{
			$values['password'] = NULL;
			$values['can_view_others'] = TRUE;
		}
		
		/* Reverse */
		if( isset( $values['disable_sharelinks'] ) )
		{
			$values['disable_sharelinks'] = !$values['disable_sharelinks'];
		}
		
		/* Moderation */
		if( isset( $values['preview_posts'] ) )
		{
			if ( in_array( 'topics', $values['preview_posts'] ) and in_array( 'posts', $values['preview_posts'] ) )
			{
				$values['preview_posts'] = 1;
			}
			elseif ( in_array( 'topics', $values['preview_posts'] ) )
			{
				$values['preview_posts'] = 2;
			}
			elseif ( in_array( 'posts', $values['preview_posts'] ) )
			{
				$values['preview_posts'] = 3;
			}
			else
			{
				$values['preview_posts'] = 0;
			}
		}
		
		/* Feature color */
		if ( isset( $values['use_feature_color'] ) )
		{
			if ( ! $values['use_feature_color'] )
			{
				$values['feature_color'] = NULL;
			}
			
			unset( $values['use_feature_color'] );
		}
		
		if ( !$this->id )
		{
			$this->save();
		}

		foreach ( array( 'name' => "forums_forum_{$this->id}", 'description' => "forums_forum_{$this->id}_desc", 'rules_title' => "forums_forum_{$this->id}_rulestitle", 'rules_text' => "forums_forum_{$this->id}_rules", 'permission_custom_error' => "forums_forum_{$this->id}_permerror" ) as $fieldKey => $langKey )
		{
			if ( array_key_exists( $fieldKey, $values ) )
			{
				Lang::saveCustom( 'forums', $langKey, $values[ $fieldKey ] );
				
				if ( $fieldKey === 'name' )
				{
					$this->name_seo = Friendly::seoTitle( $values[ $fieldKey ][ Lang::defaultLanguage() ] );
					$this->save();
				}
				
				unset( $values[ $fieldKey ] );
			}
		}
		
		/* Just for toggles */
		foreach ( array( 'type', 'password_on' ) as $k )
		{
			if( isset( $values[ $k ] ) )
			{
				unset( $values[ $k ] );
			}
		}
		
		/* Update index */
		if( $this->can_view_others !== NULL and array_key_exists( 'can_view_others', $values ) and $values['can_view_others'] != $this->can_view_others )
		{
			$this->can_view_others = $values['can_view_others'];
			$this->updateSearchIndexPermissions();
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		unset( Store::i()->forumsCustomNodes );
		
		File::claimAttachments( 'forums-new-forum', $this->id, NULL, 'description', TRUE );
		File::claimAttachments( 'forums-new-permerror', $this->id, NULL, 'permerror', TRUE );
		File::claimAttachments( 'forums-new-rules', $this->id, NULL, 'rules', TRUE );

        parent::postSaveForm( $values );
	}
	
	/**
	 * Can a value be copied to this node?
	 *
	 * @param	string	$key	Setting key
	 * @param	mixed	$value	Setting value
	 * @return	bool
	 */
	public function canCopyValue( string $key, mixed $value ): bool
	{
		if ( mb_strpos( $key, 'forum_' ) === 0 )
		{
			$key = mb_substr( $key, 6 );
		}
		return parent::canCopyValue( $key, $value );
	}

	/**
	 * @brief	Cached URL
	 */
	protected mixed $_url = NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlBase = 'app=forums&module=forums&controller=forums&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static string $urlTemplate = 'forums_forum';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static string $seoTitleColumn = 'name_seo';
	
	/**
	 * Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		unset( Store::i()->forumsCustomNodes );
		
		parent::delete();
		
		foreach ( array( 'rules_title' => "forums_forum_{$this->id}_rulestitle", 'rules_text' => "forums_forum_{$this->id}_rules", 'permission_custom_error' => "forums_forum_{$this->id}_permerror" ) as $fieldKey => $langKey )
		{
			Lang::deleteCustom( 'forums', $langKey );
		}

		if( $this->card_image )
		{
			try
			{
				File::get( 'forums_Cards', $this->card_image )->delete();
			}
			catch( Exception $e ){}
		}
	}

	/**
	 * Get template for node tables
	 *
	 * @return callable|array
	 */
	public static function nodeTableTemplate(): callable|array
	{
		return array( Theme::i()->getTemplate( 'index', 'forums' ), 'forumTableRow' );
	}

	/**
	 * Get template for managing this nodes follows
	 *
	 * @return callable|array
	 */
	public static function manageFollowNodeRow(): callable|array
	{
		return array( Theme::i()->getTemplate( 'global', 'forums' ), 'manageFollowNodeRow' );
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}
		
		$oldId = $this->id;
		$oldGridImage = $this->card_image;
		
		$this->show_rules = 0;

		parent::__clone();

		foreach ( array( 'rules_title' => "forums_forum_{$this->id}_rulestitle", 'rules_text' => "forums_forum_{$this->id}_rules", 'permission_custom_error' => "forums_forum_{$this->id}_permerror" ) as $fieldKey => $langKey )
		{
			$oldLangKey = str_replace( $this->id, $oldId, $langKey );
			Lang::saveCustom( 'forums', $langKey, iterator_to_array( Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', $oldLangKey ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}
		
		if ( $oldGridImage )
		{
			try
			{
				$gridImg = File::get( 'forums_Cards', $oldGridImage );
				$newImage = File::create( 'forums_Cards', $gridImg->originalFilename, $gridImg->contents() );
				$this->card_image = (string) $newImage;
			}
			catch ( Exception $e )
			{
				$this->card_image = NULL;
			}

			$this->save();
		}
	}

	/**
	 * If there is only one forum (and it isn't a redirect forum or password protected), that forum, or NULL
	 *
	 * @return    Forum|NULL
	 */
	public static function theOnlyForum(): ?Model
	{
		return static::theOnlyNode( array( 'redirect_url' => FALSE, 'password' => FALSE ), FALSE );
	}
	
	/**
	 * Can a given member view topics created by other members in this forum?
	 * 
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	public function memberCanAccessOthersTopics( Member $member ): bool
	{
		/* If everyone can view topics posted in this forum by others then this whole check is irrelevant */
		if ( $this->can_view_others )
		{
			return TRUE;
		}
		/* If this is a club forum, we defer to if the member is a moderator of it (which will include members who have the global "Has leader privileges in all clubs?" permission") */
		elseif ( $club = $this->club() )
		{
			return $club->isModerator( $member );
		}
		/* Otherwise, it depends on if this user is a moderator with the "Can view all topics/questions?" permission for THIS forum */
		else
		{
			if ( $member->modPermission('can_read_all_topics') )
			{
				$forumsTheMemberIsModeratorOf = $member->modPermission('forums');
								
				if ( $forumsTheMemberIsModeratorOf === TRUE OR $forumsTheMemberIsModeratorOf == -1 ) // All forums
				{
					return TRUE;
				}
				elseif ( is_array( $forumsTheMemberIsModeratorOf ) and in_array( $this->_id, $forumsTheMemberIsModeratorOf ) ) // This forum specifically
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				return FALSE;
			}
		}
	}

	/**
	 * Get which permission keys can access all topics in a forum which
	 * can normally only show topics to the author
	 * 
	 * @return	array
	 */
	public function permissionsThatCanAccessAllTopics(): array
	{
		$normal		= $this->searchIndexPermissions();
		$return		= array();
		$members	= array();
		
		if ( $club = $this->club() )
		{
			$return[] = 'cm';
			$members[] = "cm{$club->id}";
		}
		else
		{
			foreach ( Db::i()->select( '*', 'core_moderators' ) as $moderator )
			{
				$json = json_decode( $moderator['perms'], TRUE );

				if ( $moderator['perms'] === '*' OR
					(
						!empty( $json['can_read_all_topics'] ) AND ( !empty( $json['forums'] ) AND ( $json['forums'] === -1 OR in_array( $this->_id, $json['forums'] ) ) )
					) )
				{
					if( $moderator['type'] === 'g' )
					{
						$return[] = $moderator['id'];
					}
					else
					{
						$members[] = "m{$moderator['id']}";
					}
				}
			}
		}

		$return = ( $normal == '*' ) ? array_unique( $return ) : array_intersect( explode( ',', $normal ), array_unique( $return ) );
		
		if( count( $members ) )
		{
			$return = array_merge( $return, $members );
		}

		return $return;
	}
	
	/**
	 * Update search index permissions
	 *
	 * @return  void
	 */
	protected function updateSearchIndexPermissions() : void
	{
		if ( $this->can_view_others )
		{
			parent::updateSearchIndexPermissions();
		}
		else
		{
			$permissions = implode( ',', $this->permissionsThatCanAccessAllTopics() );
			Index::i()->massUpdate( 'IPS\forums\Topic', $this->_id, NULL, $permissions, NULL, NULL, NULL, NULL, NULL, TRUE );
			Index::i()->massUpdate( 'IPS\forums\Topic\Post', $this->_id, NULL, $permissions, NULL, NULL, NULL, NULL, NULL, TRUE );
		}
	}
	
	/**
	 * Mass move content items in this node to another node
	 *
	 * @param Model|null $node	New node to move content items to, or NULL to delete
	 * @param array|null $data	Additional filters to mass move by
	 * @return	NULL|int
	 */
	public function massMoveorDelete( Model $node=NULL, array $data=NULL ): ?int
	{
		/* If we are mass deleting, let parent handle it. Also do this the slow way if we can't view other topics in the destination forum, because we need to
			adjust search index permissions on a row-by-row basis in that case. */
		if( !$node OR !$node->can_view_others )
		{
			return parent::massMoveorDelete( $node, $data );
		}

		/* If this is not a true mass move of contents of one container to another, then let parent handle it normally */
		if( isset( $data['additional'] ) AND 
			( isset( $data['additional']['author'] ) OR ( isset( $data['additional']['no_comments'] ) AND $data['additional']['no_comments'] > 0 ) OR
			( isset( $data['additional']['num_comments'] ) AND $data['additional']['num_comments'] >= 0 ) OR isset( $data['additional']['state'] ) OR
			( isset( $data['additional']['pinned'] ) AND $data['additional']['pinned'] === TRUE ) OR ( isset( $data['additional']['featured'] ) AND $data['additional']['featured'] === TRUE ) OR ( isset( $data['additional']['last_post'] ) AND $data['additional']['last_post'] > 0 ) OR ( isset( $data['additional']['date'] ) AND $data['additional']['date'] > 0 ) ) 
		)
		{
			return parent::massMoveorDelete( $node, $data );
		}

		/* Can we allow the mass move? */
		if(	!$node->sub_can_post or $node->redirect_url )
		{
			throw new InvalidArgumentException;
		}

		/* Adjust the node counts */
		$contentItemClass = static::$contentItemClass;

		if( $this->_futureItems !== NULL )
		{
			$node->_futureItems		= $node->_futureItems + $this->_futureItems;
			$this->_futureItems		= 0;
		}

		if ( $this->_items !== NULL )
		{
			$node->_items			= $node->_items + $this->_items;
			$this->_items			= 0;
		}

		if ( $this->_unapprovedItems !== NULL )
		{
			$node->_unapprovedItems	= $node->_unapprovedItems + $this->_unapprovedItems;
			$this->_unapprovedItems	= 0;
		}

		if ( isset( $contentItemClass::$commentClass ) and $this->_comments !== NULL )
		{
			$node->_comments		= $node->_comments + $this->_comments;
			$this->_comments		= 0;

			if( $this->_unapprovedComments !== NULL and isset( $contentItemClass::$databaseColumnMap['unapproved_comments'] ) )
			{
				$node->_unapprovedComments	= $node->_unapprovedComments + $this->_unapprovedComments;
				$this->_unapprovedComments	= 0;
			}
		}
		if ( isset( $contentItemClass::$reviewClass ) and $this->_reviews !== NULL )
		{
			$node->_reviews			= $node->_reviews + $this->_reviews;
			$this->_reviews			= 0;

			if( $this->_unapprovedReviews !== NULL and isset( $contentItemClass::$databaseColumnMap['unapproved_reviews'] ) )
			{
				$node->_unapprovedReviews	= $node->_unapprovedReviews + $this->_unapprovedReviews;
				$this->_unapprovedReviews	= 0;
			}
		}

		/* Do the move */
		Db::i()->update( 'forums_topics', array( 'forum_id' => $node->_id ), array( 'forum_id=?', $this->_id ) );
		Db::i()->update( 'forums_question_ratings', array( 'forum' => $node->_id ), array( 'forum=?', $this->_id ) );

		/* Rebuild tags */
		if ( in_array( 'IPS\Content\Taggable', class_implements( $contentItemClass ) ) )
		{
			Db::i()->update( 'core_tags', array(
				'tag_aap_lookup'		=> md5( static::$permApp . ';' . static::$permType . ';' . $node->_id ),
				'tag_meta_parent_id'	=> $node->_id
			), array( 'tag_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );

			if ( isset( static::$permissionMap['read'] ) )
			{
				Db::i()->update( 'core_tags_perms', array(
					'tag_perm_aap_lookup'	=> md5( static::$permApp . ';' . static::$permType . ';' . $node->_id ),
					'tag_perm_text'			=> Db::i()->select( 'perm_' . static::$permissionMap['read'], 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', static::$permApp, static::$permType, $node->_id ) )->first()
				), array( 'tag_perm_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );
			}
		}

		/* Rebuild node data */
		$node->setLastComment();
		$node->setLastReview();
		$node->save();
		$this->setLastComment();
		$this->setLastReview();
		$this->save();

		/* Add to search index */
		if( SearchContent::isSearchable( $contentItemClass ) )
		{
			/* Grab permissions...we already account for !can_view_others by letting the parent handle this the old fashioned way in that case at the start of the method */
			$permissions = $node->searchIndexPermissions();

			/* Do the update */
			Index::i()->massUpdate( $contentItemClass, $this->_id, NULL, $permissions, NULL, $node->_id );

			foreach ( array( 'commentClass', 'reviewClass' ) as $class )
			{
				if ( isset( $contentItemClass::$$class ) )
				{
					$className = $contentItemClass::$$class;
					if( SearchContent::isSearchable( $className ) )
					{
						Index::i()->massUpdate( $className, $this->_id, NULL, $permissions, NULL, $node->_id );
					}
				}
			}
		}

		/* Update caches */
		Widget::deleteCaches( NULL, static::$permApp );

		/* Log */
		if ( Dispatcher::hasInstance() )
		{
			/* @var Topic $contentItemClass */
			Session::i()->modLog( 'modlog__action_massmove', array( $contentItemClass::$title . '_pl_lc' => TRUE, $node->url()->__toString() => FALSE, $node->_title => FALSE ) );
		}

		return NULL;
	}
	
	/**
	 * Number of unapproved topics/posts in forum and all subforums
	 *
	 * @return	array
	 */
	public function unapprovedContentRecursive(): array
	{
		$return = array( 'topics' => $this->queued_topics, 'posts' => $this->queued_posts );
		
		foreach ( $this->children() as $child )
		{
			$childCounts = $child->unapprovedContentRecursive();
			$return['topics'] += $childCounts['topics'];
			$return['posts'] += $childCounts['posts'];
		}
		
		return $return;
	}

	/**
	 * Disabled permissions
	 * Allow node classes to define permissions that are unselectable in the permission matrix
	 *
	 * @return array	array( {group_id} => array( 'read', 'view', 'perm_7' );
	 */
	public function disabledPermissions(): array
	{
		if( $this->sub_can_post and !$this->can_view_others )
		{
			return array( Settings::i()->guest_group => array( 2, 3, 4, 5 ) );
		}

		return array();
	}
	
	/**
	 * The permission key or function used when building a node selector
	 * in search or stream functions.
	 *
	 * @return string|callable function
	 */
	public static function searchableNodesPermission(): callable|string
	{
		return function( $node )
		{
			if ( $node->can( 'view' ) and $node->sub_can_post )
			{
				return TRUE;
			}
			
			return FALSE;
		};
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse			int					id				ID number
	 * @apiresponse			string				name			Forum name
	 * @apiresponse			string				path			Forum name including parents (e.g. "Example Category > Example Forum")
	 * @apiresponse			string				type			The type of forum: "discussions", "questions", "category", or "redirect"
	 * @apiresponse			int					topics			Number of topics in forum
	 * @apiresponse			string				url				URL
	 * @apiresponse			int|null			parentId		Parent Node ID
	 * @clientapiresponse	object|null		permissions		Node permissions
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$path = array();
		foreach( $this->parents() AS $parent )
		{
			$path[] = $parent->_title;
		}
		$path[] = $this->_title;
		$type = 'discussions';
		if ( $this->redirect_url )
		{
			$type = 'redirect';
		}
		elseif ( !$this->sub_can_post )
		{
			$type = 'category';
		}
		elseif ( $this->forums_bitoptions['bw_enable_answers'] )
		{
			$type = 'questions';
		}

		$return = array(
			'id'			=> $this->id,
			'name'			=> $this->_title,
			'path'			=> implode( ' > ', $path ),
			'type'			=> $type,
			'topics'		=> $this->topics,
			'url'			=> (string) $this->url(),
			'parentId'		=> static::$databaseColumnParent ? $this->{static::$databaseColumnParent} : NULL
		);

		if( $authorizedMember === NULL )
		{
			$return['permissions']	= $this->permissions();
		}

		if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\ClubContainer' ) )
		{
			if( $this->club() )
			{
				$return['public'] = ( $this->isPublic() ) ? 1 : 0;
				$return['club'] = $this->club()->apiOutput( $authorizedMember );
			}
			else
			{
				$return['club'] = 0;
			}
		}

		return $return;
	}

	/**
	 * Return the time the first topic was marked as solved in this forum
	 *
	 * @return int
	 */
	public function getFirstSolvedTime(): int
	{
		if ( ! $this->solved_stats_from )
		{
			try
			{
				$where = [['forum_id=?', $this->_id]];
				if ( $this->solved_stats_from_cutoff )
				{
					$cutoff = DateTime::create()->sub( new DateInterval( 'P' . $this->solved_stats_from_cutoff . 'D' ) )->getTimestamp();
					$where[] = ['start_date>?', $cutoff];
				}

				$post = Db::i()->select( '*', 'forums_posts', [
					'pid IN (?)', Db::i()->select( 'topic_answered_pid', 'forums_topics', $where )
				], 'post_date ASC', 1 )->first();

				try
				{
					$this->solved_stats_from = Db::i()->select( 'solved_date', 'core_solved_index', [ 'app=? and comment_id=? AND type=? AND hidden=0', 'forums', $post['pid'], 'solved' ] )->first();
				}
				catch( Exception $e )
				{
					/* If that goes wrong... */
					$this->solved_stats_from = $post['post_date'];
				}
			}
			catch( UnderflowException $e )
			{
				/* If all that goes wrong, we have nothing marked so just store now to prevent it rebuilding over and over */
				$this->solved_stats_from = time();
			}

			$this->save();
		}

		return $this->solved_stats_from;
	}
	
	/* !Clubs */

	/**
	 * Set form for creating a node of this type in a club
	 *
	 * @param Form $form Form object
	 * @param Club $club
	 * @return    void
	 */
	public function clubForm( Form $form, Club $club ) : void
	{
		/* @var Topic $itemClass */
		$itemClass = static::$contentItemClass;
		$form->add( new Text( 'club_node_name', $this->_id ? $this->_title : Member::loggedIn()->language()->addToStack( $itemClass::$title . '_pl' ), TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new Editor( 'club_node_description', $this->_id ? Member::loggedIn()->language()->get( static::$titleLangPrefix . $this->_id . '_desc' ) : NULL, FALSE, array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-forum-{$this->id}" : "forums-new-forum" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'forum_description_placeholder' ) ) );
		$form->add( new YesNo( 'forum_can_view_others_club', $this->id ? $this->can_view_others : TRUE ) );
		
		if( $club->type == 'closed' )
		{
			$form->add( new Radio( 'club_node_public', $this->id ? $this->isPublic() : 0, TRUE, array( 'options' => array( '0' => 'club_node_public_no', '1' => 'club_node_public_view', '2' => 'club_node_public_participate' ) ) ) );
		}

		$mode = 'off';
		if ( $this->id )
		{
			if ( $this->forums_bitoptions['bw_solved_set_by_moderator'] and $this->forums_bitoptions['bw_solved_set_by_member'] )
			{
				$mode = 'starter_and_mods';
			}
			else if ( $this->forums_bitoptions['bw_solved_set_by_moderator'] )
			{
				$mode = 'mods';
			}
		}

		$field = new Radio( 'forum_solved_mode', $this->id ? $mode : 'off', FALSE, array(
			'options' => [
				'off' => 'forum_solved_mode_off',
				'mods' => 'forum_solved_mode_mods',
				'starter_and_mods' => 'forum_solved_mode_starter'
			]
		), NULL, NULL, NULL, 'forum_solved_mode' );

		/* The default description includes an ACP link */
		if( Dispatcher::i()->controllerLocation == 'front' )
		{
			$field->description = Member::loggedIn()->language()->addToStack( 'forum_solved_mode_desc_front' );
		}

		$form->add( $field );
	}
	
	/**
	 * Class-specific routine when saving club form
	 *
	 * @param	Club	$club	The club
	 * @param	array				$values	Values
	 * @return	void
	 */
	public function _saveClubForm( Club $club, array $values ) : void
	{
		if ( $values['club_node_name'] )
		{
			$this->name_seo	= Friendly::seoTitle( $values['club_node_name'] );
		}
		
		if( $this->can_view_others != $values['forum_can_view_others_club'] )
		{
			$this->can_view_others = $values['forum_can_view_others_club'];
			
			if ( $this->_id )
			{
				$this->updateSearchIndexPermissions();
			}
		}

		/* Figure out solved mode */
		if ( isset( $values['forum_solved_mode'] ) )
		{
			$values['bw_solved_set_by_moderator'] = false;
			$values['bw_solved_set_by_member'] = false;

			if ( $values['forum_solved_mode'] == 'starter_and_mods' )
			{
				$values['bw_solved_set_by_moderator'] = true;
				$values['bw_solved_set_by_member'] = true;
			}
			else if ( $values['forum_solved_mode'] == 'mods' )
			{
				$values['bw_solved_set_by_moderator'] = true;
			}

			unset( $values['forum_solved_mode'] );
		}

		foreach ( array( 'bw_solved_set_by_member', 'bw_solved_set_by_moderator' ) as $k )
		{
			if( isset( $values[ $k ] ) )
			{
				$this->forums_bitoptions[ $k ] = $values[ $k ];
			}
		}

		/* Use default priority for sitemaps */
		$this->ipseo_priority = -1;
		
		if ( !$this->_id )
		{
			$this->save();
			File::claimAttachments( 'forums-new-forum', $this->id, NULL, 'description' );
		}
	}

	/**
	 * Is this forum set to show topics from this forum and any children in one
	 * combined 'simple' view?
	 *
	 * @return boolean
	 */
	public function isCombinedView(): bool
	{
		return (bool) $this->forums_bitoptions['bw_fluid_view'];
	}

	/* !Simple view */

	/**
	 * Is simple view one? Calculates admin settings and user's choice
	 *
	 * @param Forum|NULL $forum The forum objectr
	 * @return boolean
	 * @throws Exception
	 */
	public static function isSimpleView( ?Forum $forum=NULL ): bool
	{
		$simpleView = false;
		
		/* Clubs cannot be simple mode or it breaks out of the club container */
		if ( $forum and $forum->club() )
		{
			return false;
		}

		/* If this was called via CLI (e.g. tasks ran via cron), then use the default */
		if( !Dispatcher::hasInstance() )
		{
			return false;
		}

		if ( Member::loggedIn()->getLayoutValue('forums_forum') === 'fluid' )
		{
			$simpleView = true;
		}
		
		return $simpleView;
	}

	/**
	 * Return if this node has custom permissions
	 *
	 * @return null|array
	 */
	public static function customPermissionNodes(): ?array
	{
		if ( ! isset( Store::i()->forumsCustomNodes ) )
		{
			$data = [ 'count' => 0, 'password' => [], 'cannotViewOthersItems' => [] ];

			foreach( Db::i()->select( '*', 'forums_forums', array( 'password IS NOT NULL or can_view_others=0' ) ) as $forum )
			{
				$data['count']++;
				if ( $forum['password'] )
				{
					$data['password'][] = $forum['id'];
				}

				if ( ! $forum['can_view_others'] )
				{
					$data['cannotViewOthersItems'][] = $forum['id'];
				}
			}

			Store::i()->forumsCustomNodes = $data;
		}
		
		return ( Store::i()->forumsCustomNodes['count'] ) ? Store::i()->forumsCustomNodes : NULL;
	}

	/**
	 * Get URL
	 *
	 * @return Url|string|null
	 */
	public function url(): Url|string|null
	{
		if ( static::isSimpleView() and ! $this->club() )
		{
			return Url::internal( 'app=forums&module=forums&controller=index&forumId=' . $this->id, 'front', 'forums' );
		}
		
		return parent::url();
	}

	/**
	 * @brief   The class of the ACP \IPS\Node\Controller that manages this node type
	 */
	protected static ?string $acpController = "IPS\\forums\\modules\\admin\\forums\\forums";
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @return    Url
	 */
	public static function urlFromIndexData( array $indexData, array $itemData, ?array $containerData ): Url
	{
		if ( static::isSimpleView() and ! $containerData['club_id'] )
		{
			return Url::internal( 'app=forums&module=forums&controller=index&forumId=' . $indexData['index_container_id'], 'front', 'forums' );
		}
		
		return parent::urlFromIndexData( $indexData, $itemData, $containerData );
	}
	
	/**
	 * [Node] Get type
	 *
	 * @return	string
	 */
	protected function get__forum_type(): string
	{
		if ( $this->redirect_url )
		{
			$type = 'redirect';
		}
		elseif ( !$this->sub_can_post )
		{
			$type = 'category';
		}
		else
		{
			$type = 'normal';
		}
		
		return $type;
	}
	
	/**
	 * Content was held for approval by container
	 * Allow node classes that can determine if content should be held for approval in individual nodes
	 *
	 * @param	string				$content	The type of content we are checking (item, comment, review).
	 * @param	Member|NULL	$member		Member to check or NULL for currently logged in member.
	 * @return	bool
	 */
	public function contentHeldForApprovalByNode( string $content, ?Member $member = NULL ): bool
	{
		/* If members group bypasses, then no. */
		$member = $member ?: Member::loggedIn();
		if ( $member->group['g_avoid_q'] )
		{
			return FALSE;
		}
		
		switch( $content )
		{
			case 'item':
				return ( in_array( $this->preview_posts, array( 1, 2 ) ) );
			
			case 'comment':
				return ( in_array( $this->preview_posts, array( 1, 3 ) ) );
		}

		return FALSE;
	}

	/**
	 * Is this Forum used by any downloads app category ?
	 *
	 * @return bool|Category
	 */
	public function isUsedByADownloadsCategory(): Category|bool
	{
		if( !SystemApplication::appIsEnabled( 'downloads' ) )
		{
			return false;
		}

		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'downloads_categories' ), 'IPS\downloads\Category' ) AS $category )
		{
			if ( $category->forum_id and $category->forum_id == $this->id )
			{
				return $category;
			}
		}
		return FALSE;
	}

	/**
	 * Is this Forum used by any cms category for record/comment topics?
	 *
	@return bool|\IPS\cms\Databases
	 */
	public function isUsedByCms()
	{
		if( !SystemApplication::appIsEnabled( 'cms' ) )
		{
			return false;
		}

		foreach ( Databases::databases() as $database )
		{
			if ( $database->forum_record and $database->forum_forum and $database->forum_forum == $this->id )
			{
				return $database;
			}
		}
		return FALSE;
	}

	/**
	 * Get the expert members from this forum
	 *
	 * @return array|null
	 */
	public function getExperts(): array|null
	{
		if( !Bridge::i()->featureIsEnabled( 'experts' ) )
		{
			return null;
		}

		$key = 'forums_expert_users_' . $this->_id;

		try
		{
			$experts = Store::i()->$key;
		}
		catch( Exception )
		{
			$experts = array();
			foreach( Db::i()->select( '*', 'core_expert_users', array( 'node_id=?', $this->_id ) ) as $expert )
			{
				$experts[] = $expert['member_id'];
			}

			Store::i()->$key = $experts;
		}

		return count( $experts ) ? $experts : null;
	}

	/**
	 * Allow for individual classes to override and
	 * specify a primary image. Used for grid views, etc.
	 *
	 * @return File|null
	 */
	public function primaryImage() : ?File
	{
		if( $this->card_image )
		{
			return File::get( 'forums_Cards', $this->card_image );
		}

		return parent::primaryImage();
	}

	/**
	 * Count all comments, items, etc
	 *
	 * @return void
	 */
	protected function recount() : void
	{
		$this->_items = (int) Db::i()->select( 'count(*)', 'forums_topics', [ 'forum_id=? and approved=? and state != ?', $this->_id, 1, 'link' ] )->first();
		$this->_unapprovedItems = (int) Db::i()->select( 'count(*)', 'forums_topics', [ 'forum_id=? and approved = ? and state !=?', $this->_id, 0, 'link' ] )->first();
		$this->_comments = (int) Db::i()->select( 'count(*)', 'forums_posts', [ 'queued=? and forum_id=? and approved=? and state != ?', 0, $this->_id, 1, 'link' ] )
			->join( 'forums_topics', 'forums_posts.topic_id=forums_topics.tid' )
			->first();
		$this->_unapprovedComments = (int) Db::i()->select( 'count(*)', 'forums_posts', [ 'queued =? and forum_id=? and approved=? and state != ?', 1, $this->_id, 1, 'link' ] )
			->join( 'forums_topics', 'forums_posts.topic_id=forums_topics.tid' )
			->first();
		$this->save();
	}
}