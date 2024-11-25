<?php
/**
 * @brief		Followable Trait for Content Models/Comments
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		02 Dec 2013
 */

namespace IPS\Content;

use ArrayObject;
use BadMethodCallException;
use IPS\Content;
use IPS\Db;
use IPS\IPS;
use IPS\Notification;
use IPS\Application;
use	IPS\DateTime;
use IPS\Node\Model;
use IPS\Db\Select;
use ArrayIterator;
use Iterator;
use UnderflowException;
use function defined;
use function header;
use function mb_strtolower;
use function mb_substr;
use function mb_strrpos;
use function get_called_class;
use function get_class;

/* To prevent PHP errors (extending class does not exist) revealing path */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Followable Trait for Content Models/Comments
 */
trait Followable
{
	/**
	 * @brief	Cache for current follow data, used on "My Followed Content" screen
	 */
	public ?array $_followData = array();

	/**
	 * Followers
	 *
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|NULL		$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @param	int|array|NULL			$limit			LIMIT clause
	 * @param	string|NULL				$order			Column to order by
	 * @param	bool					$countOnly		Return only the count
	 * @return	Select|int
	 * @throws	BadMethodCallException
	 */
	public function followers(int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL, int|array|null $limit=array( 0, 25 ), string|null $order=NULL, bool $countOnly=FALSE ): Select|int
	{
		$idColumn = static::$databaseColumnId;

		return static::_followers( mb_strtolower( mb_substr( get_called_class(), mb_strrpos( get_called_class(), '\\' ) + 1 ) ), $this->$idColumn, $privacy, $frequencyTypes, $date, $limit, $order, $countOnly, null );
	}

	/**
	 * Followers Count
	 *
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|NULL	$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @return	int
	 * @throws	BadMethodCallException
	 */
	public function followersCount( int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL ): int
	{
		return $this->followers( $privacy, $frequencyTypes, $date, NULL, NULL, TRUE );
	}

	/**
	 * Followers Count
	 *
	 * @param	array					$items			Array of \IPS\Content\Item
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|null	$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @return	array|Iterator|int
	 * @throws	BadMethodCallException
	 */
	public static function followersCounts( array $items, int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL ): array|Iterator|int
	{
		$ids = array();
		$idField = NULL;
		foreach( $items as $item )
		{
			if ( $idField === NULL )
			{
				$idField = static::$databaseColumnId;
			}
			$ids[] = $item->$idField;
		}

		return static::_followersCount( mb_strtolower( mb_substr( get_called_class(), mb_strrpos( get_called_class(), '\\' ) + 1 ) ), $ids, $privacy, $frequencyTypes, $date );
	}
	
	/**
	 * Container Followers
	 *
	 * @param	Model			$container		The container
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|NULL	$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @param	int|array|NULL			$limit			LIMIT clause
	 * @param	string|NULL				$order			Column to order by
	 * @param	bool					$countOnly		Return only the count
	 * @return	Select|int
	 */
	public static function containerFollowers( Model $container, int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL, int|array|null $limit=array( 0, 25 ), string|null $order=NULL, bool $countOnly=FALSE ): Select|int
	{
		return static::_followers( mb_strtolower( mb_substr( get_class( $container ), mb_strrpos( get_class( $container ), '\\' ) + 1 ) ), $container->_id, $privacy, $frequencyTypes, $date, $limit, $order, $countOnly, null );
	}

	/**
	 * Container Follower Count
	 *
	 * @param Model $container The container
	 * @param int $privacy Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param array $frequencyTypes array( 'immediate', 'daily', 'weekly' )
	 * @param DateTime|int|null $date Only users who started following before this date will be returned. NULL for no restriction
	 * @return int|ArrayIterator
	 */
	public static function containerFollowerCount( Model $container, int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL ): int|ArrayIterator
	{
		/* Return the count */
		return static::_followersCount( mb_strtolower( mb_substr( get_class( $container ), mb_strrpos( get_class( $container ), '\\' ) + 1 ) ), $container->_id, $privacy, $frequencyTypes, $date );
	}

	/**
	 * Container Follower Count
	 *
	 * @param	array					$containers		Array of \IPS\Node\Model
	 * @param	int						$privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'immediate', 'daily', 'weekly' )
	 * @param	DateTime|int|NULL	$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @return	int|ArrayIterator
	 */
	public static function containerFollowerCounts( array $containers, int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), DateTime|int|null $date=NULL ): int|ArrayIterator
	{
		$ids = array();
		$class = NULL;
		foreach( $containers as $node )
		{
			if ( $class === NULL )
			{
				$class = get_class( $node );
			}

			$ids[] = $node->_id;
		}

		/* Return the count */
		return static::_followersCount( mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) ), $ids, $privacy, $frequencyTypes, $date );
	}

	/**
	 * Return the count of followers for all tags in this item
	 *
	 * @param int $privacy
	 * @param array $frequencyTypes
	 * @return int|ArrayIterator
	 */
	public function tagsFollowerCount( int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ) ) : int|ArrayIterator
	{
		if( !IPS::classUsesTrait( $this, Taggable::class ) )
		{
			return 0;
		}

		$ids = Tag::getTagIdsForItem( $this );
		if( !count( $ids ) )
		{
			return 0;
		}

		/* Return the count */
		$total = 0;
		foreach( static::_followersCount( 'tag', $ids, $privacy, $frequencyTypes, $this->mapped( 'date' ), 'core' ) as $k => $v )
		{
			$total += $v['count'];
		}

		return $total;
	}

	/**
	 * Return the followers for all tags in this item
	 *
	 * @param int $privacy
	 * @param array $frequencyTypes
	 * @param int|array|null $limit
	 * @param string|null $order
	 * @param bool $countOnly
	 * @return int|Iterator|array
	 */
	public function tagsFollowers( int $privacy=3, array $frequencyTypes=array( 'none', 'immediate', 'daily', 'weekly' ), int|array|null $limit=array( 0, 25 ), string|null $order=NULL, bool $countOnly=FALSE ): int|Iterator|array
	{
		if( !IPS::classUsesTrait( $this, Taggable::class ) )
		{
			return 0;
		}

		$tagIds = Tag::getTagIdsForItem( $this );
		return static::_followers( 'tag', $tagIds, $privacy, $frequencyTypes, $this->mapped( 'date' ), $limit, $order, $countOnly, 'core' );
	}

	/**
	 * Users to receive immediate notifications
	 *
	 * @param int|array|null $limit LIMIT clause
	 * @param bool $countOnly Just return the count
	 * @return Select|int
	 */
	public function notificationRecipients( int|array|null $limit=array( 0, 25 ), bool $countOnly=FALSE ): Select|int
	{
		/* Do we only want the count? */
		if( $countOnly )
		{
			$count	= 0;
			$count	+= $this->author()->followersCount( 3, array( 'immediate' ), $this->mapped('date') );
			$count	+= static::containerFollowerCount( $this->container(), 3, array( 'immediate' ), $this->mapped('date') );
			return $count;
		}

		$unionFollowers = [];
		$memberFollowersCount = $this->author()->followersCount( 3, array( 'immediate' ), $this->mapped( 'date' ) );
		if( $memberFollowersCount )
		{
			$unionFollowers[] = $this->author()->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL );
		}

		if( count( $unionFollowers ) )
		{
			$unions	= array_merge(
				array(
					static::containerFollowers( $this->container(), 3, array( 'immediate' ), $this->mapped('date'), NULL )
				),
				$unionFollowers
			);

			return Db::i()->union( $unions, 'follow_added', $limit );
		}
		else
		{
			return static::containerFollowers( $this->container(), Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS, array( 'immediate' ), $this->mapped('date'), $limit, 'follow_added' );
		}
	}
	
	/**
	 * Create Notification
	 *
	 * @param	mixed		$extra		Additional data
	 * @return	Notification
	 */
	public function createNotification( mixed $extra=NULL ): Notification
	{
		// New content is sent with itself as the item as we deliberately do not group notifications about new content items. Unlike comments where you're going to read them all - you might scan the notifications list for topic titles you're interested in
		return new Notification( Application::load( 'core' ), 'new_content', $this, array( $this ), $extra );
	}

	/**
	 * @brief	Cache followers count query to prevent running it multiple times
	 */
	protected static array $followerCountCache = array();

	/**
	 * Get follow data
	 *
	 * @param string $area			Area
	 * @param array|int $id				ID or array of IDs
	 * @param int $privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param array $frequencyTypes	array( 'none', 'immediate', 'daily', 'weekly' )
	 * @param int|DateTime|null $date			Only users who started following before this date will be returned. NULL for no restriction
	 * @param array|int|null $limit			LIMIT clause
	 * @param string|null $order			Column to order by
	 * @param bool $countOnly		Return only the count
	 * @param string|null $app
	 * @return	Iterator|int|array
	 * @throws	BadMethodCallException
	 */
	public static function _followers( string $area, array|int $id, int $privacy, array $frequencyTypes, int|DateTime $date=NULL, array|int $limit=NULL, string $order=NULL, bool $countOnly = FALSE, ?string $app = null ): int|Iterator|array
	{
		/* We might need to override this - for tags, specifically */
		if( $app === null )
		{
			$app = static::$application;
		}

		/* Normalize the input */
		sort( $frequencyTypes );

		/* Can we use the cache table? */
		$canCache = FALSE;
		$cached = array();
		if ( count( $frequencyTypes ) == 4 and $countOnly and ( $privacy == Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS ) )
		{
			$canCache = TRUE;

			if ( is_array( $id ) )
			{
				foreach( Db::i()->select( 'id, count', 'core_follow_count_cache', array( 'class=? and ' . Db::i()->in( 'id', $id ), 'IPS\\' . $app . '\\' . ucfirst( $area ) ) ) as $row )
				{
					$cached[ $row['id'] ] = array( 'count' => $row['count'], 'follow_rel_id' => $row['id'] );
				}

				/* Got everything? */
				if ( count( $id ) == count( $cached ) )
				{
					$obj = new ArrayObject( $cached );
					return $obj->getIterator();
				}
			}
			else
			{
				$_key = md5( $app . $area . $id );

				if( isset( static::$followerCountCache[ $_key ] ) )
				{
					return static::$followerCountCache[ $_key ];
				}

				try
				{
					static::$followerCountCache[ $_key ] = (int) Db::i()->select( 'count', 'core_follow_count_cache', array('class=? and id=?', 'IPS\\' . $app . '\\' . ucfirst( $area ), $id ) )->first();

					return static::$followerCountCache[ $_key ];
				}
				catch ( UnderflowException ){}
			}
		}

		/* We need to use a group by if $id is an array, but otherwise not */
		$groupBy = NULL;

		/* Initial where clause */
		if( is_array( $id ) )
		{
			$where[]	= array( 'follow_app=? AND follow_area=? AND follow_rel_id IN(' . implode( ',', $id ) . ')', $app, $area );

			if( $area != 'tag' )
			{
				$groupBy	= 'follow_rel_id';
			}
		}
		else
		{
			$where[] = array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', $app, $area, $id );
		}

		/* Public / Anonymous */
		if ( !( $privacy & Content::FOLLOW_PUBLIC ) )
		{
			$where[] = array( 'follow_is_anon=1' );
		}
		elseif ( !( $privacy & Content::FOLLOW_ANONYMOUS ) )
		{
			$where[] = array( 'follow_is_anon=0' );
		}

		/* Specific type */
		if ( count( array_diff( array( 'immediate', 'daily', 'weekly', 'none' ), $frequencyTypes ) ) )
		{
			$where[] = array( Db::i()->in( 'follow_notify_freq', $frequencyTypes ) );
		}

		/* Since */
		if( $date !== NULL )
		{
			$where[] = array( 'follow_added<?', ( $date instanceof DateTime ) ? $date->getTimestamp() : intval( $date ) );
		}

		/* We don't need order or limit if we're doing a count only, which makes the query more efficient */
		if( $countOnly === TRUE )
		{
			$limit = NULL;
			$order = NULL;
		}

		/* Cache the results as this may be called multiple times in one page load */
		static $cache	= array();
		$_hash			= md5( json_encode( func_get_args() ) );

		if( isset( $cache[ $_hash ] ) )
		{
			return $cache[ $_hash ];
		}

		/* Get */
		if ( $order === 'name' )
		{
			$cache[ $_hash ]	= Db::i()->select( 'core_follow.*, core_members.name', 'core_follow', $where, 'name ASC', $limit )->join( 'core_members', array( 'core_members.member_id=core_follow.follow_member_id' ) );
		}
		else
		{
			$cache[ $_hash ]	= Db::i()->select( $countOnly ? ( is_array( $id ) ? 'COUNT(*) as count, follow_rel_id' : 'COUNT(*)' ) : 'core_follow.*', 'core_follow', $where, $order, $limit, $groupBy );
		}

		/* If we only want the count, fetch it and store it now */
		if( $countOnly )
		{
			if( is_array( $id ) )
			{
				$args = func_get_args();

				foreach( $cache[ $_hash ] as $result )
				{
					$args[1] = $result['follow_rel_id'];
					$cache[ md5( json_encode( $args ) ) ] = $result;

					if ( $canCache and ! isset( $cached[ $result['follow_rel_id'] ] ) )
					{
						Db::i()->replace( 'core_follow_count_cache', array(
							'id'	 => $result['follow_rel_id'],
							'class'  => 'IPS\\' . $app . '\\' . ucfirst( $area ),
							'count'  => $result['count'],
							'added'  => time()
						) );
					}

					$cached[ $result['follow_rel_id'] ] = $result;
				}

				/* And then any that do not exist were not found in the query, so they're 0 */
				foreach( $id as $_id )
				{
					$args[1] = $_id;
					$cache[ md5( json_encode( $args ) ) ] = array( 'follow_rel_id' => $id, 'count' => 0 );

					if ( $canCache and ! isset( $cached[ $_id ] ) )
					{
						Db::i()->replace( 'core_follow_count_cache', array(
							'id'	 => $_id,
							'class'  => 'IPS\\' . $app . '\\' . ucfirst( $area ),
							'count'  => 0,
							'added'  => time()
						) );
					}
				}
			}
			else
			{
				$cache[ $_hash ] = $cache[ $_hash ]->first();

				if ( $canCache )
				{
					Db::i()->replace( 'core_follow_count_cache', array(
						'id'	 => $id,
						'class'  => 'IPS\\' . $app . '\\' . ucfirst( $area ),
						'count'  => $cache[ $_hash ],
						'added'  => time()
					) );
				}
			}
		}

		if ( $canCache AND isset( $cached ) AND is_array( $id ) )
		{
			$obj = new ArrayObject( $cached );
			return $obj->getIterator();
		}

		return $cache[ $_hash ];
	}

	/**
	 * Get follower count
	 *
	 * @param string $area			Area
	 * @param array|int $id				ID or array of IDs
	 * @param int $privacy		Content::FOLLOW_PUBLIC + Content::FOLLOW_ANONYMOUS
	 * @param array $frequencyTypes	array( 'immediate', 'daily', 'weekly' )
	 * @param int|DateTime|null $date			Only users who started following before this date will be returned. NULL for no restriction
	 * @param string|null $app
	 * @return	array|Iterator|int
	 * @throws	BadMethodCallException
	 */
	public static function _followersCount( string $area, array|int $id, int $privacy=3, array $frequencyTypes=array( 'immediate', 'daily', 'weekly', 'none' ), int|DateTime|null $date=NULL, ?string $app = null ): array|Iterator|int
	{
		return static::_followers( $area, $id, $privacy, $frequencyTypes, $date, NULL, NULL, TRUE, $app );
	}
}