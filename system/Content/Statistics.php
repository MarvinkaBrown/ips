<?php
/**
 * @brief		Statistics Trait
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 February 2020
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use	IPS\DateTime as IPSDateTime; // DateTime aliased to avoid conflicts with base DateTime class to ensure \IPS\DateTime is actually being used.
use IPS\Db;
use IPS\Db\Select;
use IPS\IPS;
use IPS\Patterns\ActiveRecordIterator;
use LogicException;
use OutOfRangeException;
use UnderflowException;
use UnexpectedValueException;
use BadMethodCallException;
use function array_push;
use function array_slice;
use function defined;
use function explode;
use function get_class;
use function in_array;
use function is_callable;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function md5;
use function stristr;
use function time;
use function array_merge;
use function implode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Trait
 */
trait Statistics
{

	/**
	 * Get stats for many items at once. Trying caches first to minimise queries
	 *
	 * @param string $type
	 * @param array $items
	 * @return array
	 */
	public static function getMany( string $type, array $items ): array
	{
		$itemIds = [];
		$class = null;
		$return = [];

		if ( ! count( $items ) )
		{
			return [];
		}

		foreach( $items as $item )
		{
			/* This is genuis and you can't tell me otherwise */
			if ( $class === null )
			{
				$class = get_class( $item );
			}

			/* @var Item $class */
			$idColumn = $class::$databaseColumnId;
			$itemIds[ $item->$idColumn ] = $item;
		}

		foreach( Db::i()->select( '*', 'core_item_statistics_cache', [ [ 'cache_class=?', $class ], [ Db::i()->in( 'cache_item_id', array_keys( $itemIds ) ) ] ] ) as $cache )
		{
			if ( $cache['cache_added'] > time() - 86400 )
			{
				$json = json_decode( $cache['cache_contents'], TRUE );

				if ( isset( $json[ $type ] ) )
				{
					$return[ $cache['cache_item_id'] ] = $json[ $type ];
				}
				else
				{
					/* We may have multiple types such as topPosters_4 or topPosters_10, so we make a guess here if the type sent is just 'topPosters' */
					foreach( $json as $key => $data )
					{
						if ( strpos( $key, $type ) === 0 )
						{
							$return[ $cache['cache_item_id'] ] = $data;
							break;
						}
					}
				}
			}
		}

		$diff = array_diff( array_keys( $itemIds ), array_keys( $return ) );

		if ( count( $diff ) )
		{
			foreach( $diff as $id )
			{
				/* Make sure the method is 'topPosters' and not 'topPosters_10' */
				$method = preg_replace( '#([a-zA-Z0-9]*)(_*|$)#', '$1', $type );
				if ( method_exists( $class, $method ) )
				{
					$return[ $id ] = $itemIds[ $id ]->$type();
				}
				else
				{
					$return[ $id ] = [];
				}
			}
		}

		return $return;
	}

	/**
	 * Most downloaded attachments
	 *
	 * @param int $count The number of results to return
	 * @return    array
	 * @throw BadMethodCallException
	 * @throws Exception
	 */
	public function topAttachments( int $count = 5 ): array
	{
		$attachments = $this->_getAllAttachments( array(), $count, 'attach_hits DESC', 'topAttachments' );

		return array_slice( $attachments, 0, $count );
	}

	/**
	 * Get all image attachments
	 *
	 * @param int $count The number of results to return
	 * @return    array
	 * @throw BadMethodCallException
	 * @throws Exception
	 */
	public function imageAttachments( int $count = 10 ): array
	{
		$attachments = $this->_getAllAttachments( array( 'attach_is_image=1' ), $count, 'attachment_id DESC', 'imageAttachments' );
		return array_slice( $attachments, 0, $count );
	}

	/**
	 * Members with most posts
	 *
	 * @param int $count The number of results to return
	 * @return    array
	 * @throws Exception
	 * @throw BadMethodCallException
	 */
	public function topPosters( int $count = 10 ): array
	{
		$commentClass = static::$commentClass;

		if ( !isset( $commentClass::$databaseColumnMap['author'] ) )
		{
			throw new BadMethodCallException();
		}

		$authorColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'];
		$cacheKey = 'topPosters_' . $count;

		try
		{
			$members = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			$where = $this->_getVisibleWhere();
			$where[] = [ $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . '!=?', 0];

			$members = iterator_to_array( Db::i()->select( "count(*) as sum, {$authorColumn}", $commentClass::$databaseTable, $where, 'sum DESC', array( 0, $count ), array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] ) ) );

			$this->_storeCached( $cacheKey, $members );
		}

		$contributors = array();
		$counts = array();
		foreach ( $members as $member )
		{
			$contributors[] = $member[$authorColumn];
			$counts[ $member[$authorColumn] ] = $member['sum'];
		}

		if ( empty( $contributors ) )
		{
			return array();
		}

		$return = array();
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $contributors ) ) ), 'IPS\Member' ) as $member )
		{
			$return[] = array( 'member' => $member, 'count' => $counts[ $member->member_id ] );
		}

		usort($return, function ( $member, $member2 )
		{
			return $member2['count'] <=> $member['count'];
		});

		return $return;
	}
	
	/**
	 * Most Recent Participans
	 *
	 * @param	string|NULL		$name		If we are looking for a specific user.
	 * @param	int				$limit		The amount of results to return.
	 * @param	bool			$incBanned	Include banned members? (Used to exclude from recent mentions)
	 * @return	ActiveRecordIterator
	 */
	public function mostRecent( string|null $name = NULL, int $limit = 10, bool $incBanned = true ): ActiveRecordIterator
	{
		$commentClass = static::$commentClass;
		
		if ( !isset( $commentClass::$databaseColumnMap['author'] ) )
		{
			throw new BadMethodCallException;
		}
		
		if ( $name )
		{
			$cacheKey = 'mostRecent_' . $limit . '_' . $name . '_' . (int) $incBanned;
		}
		else
		{
			$cacheKey = 'mostRecent_' . $limit . '_' . (int) $incBanned;
		}
		
		try
		{
			$members = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			/* Get the ten most recent posters in this content that match input. */
			$where = $this->_getVisibleWhere();
			if ( $name )
			{
				$subWhere = array();
				$subWhere[] = Db::i()->like( 'core_members.name', $name );
				if ( !$incBanned )
				{
					$subWhere[] = array( "core_members.temp_ban=?", 0 );
				}
				$subQuery = Db::i()->select( 'core_members.member_id', 'core_members', $subWhere );
				$where[] = [ $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . ' IN(?)', $subQuery ];
			}

			$members = iterator_to_array( Db::i()->select(
				$commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'],
				$commentClass::$databaseTable,
				$where,
				NULL,
			$limit ) );

			$this->_storeCached( $cacheKey, $members );
		}
		
		return new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( Db::i()->in( 'member_id', $members ) ) ), 'IPS\Member' );
	}

	/**
	 * Get reactions and the count of the times used
	 *
	 * @return  array
	 * @throws Exception
	 */
	protected function allReactions(): array
	{
		$idField = static::$databaseColumnId;
		$cacheKey = 'allReactions';
		$return = [];
		$enabledReactions = Reaction::enabledReactions();

		if ( ! $enabledReactions )
		{
			return [];
		}

		try
		{
			$return = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			/* Get reactions from all comments. Using distinct or group by is instant death by a thousand seconds (of execution time). Max of 100k to prevent memory exhaustion */
			$reactions = iterator_to_array( Db::i()->select( 'reaction', 'core_reputation_index', [ [ 'rep_class=? and type_id IN(?)', static::$commentClass, $this->_subQueryVisibleComments( $this->$idField ) ] ], null, 100000 ) );

			/* Get link to comments */
			foreach( $reactions as $reaction )
			{
				if( isset( $enabledReactions[ $reaction ] ) )
				{
					if ( !isset( $return[ $reaction ] ) )
					{
						$return[ $reaction ] = 0;
					}

					$return[ $reaction ]++;
				}
			}

			$this->_storeCached( $cacheKey, $return );
		}

		return $return;
	}

	/**
	 * Members with most posts
	 *
	 * @param int $count The number of results to return, max 100
	 * @return    array
	 * @throw BadMethodCallException
	 * @throws Exception
	 */
	public function topReactedPosts( int $count = 5 ): array
	{
		$commentClass = static::$commentClass;
		$commentIdField = $commentClass::$databasePrefix . $commentClass::$databaseColumnId;
		$idField = static::$databaseColumnId;

		if ( !IPS::classUsesTrait( $commentClass, 'IPS\Content\Reactable' ) )
		{
			throw new BadMethodCallException();
		}
		
		if ( $count > 100 )
		{
			throw new BadMethodCallException();
		}

		$cacheKey = 'topReactedPosts_' . $count;

		try
		{
			$posts = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			$where = [
				[ 'app=?', $commentClass::$application ],
				[ 'type=?', $commentClass::reactionType() ],
				[ 'item_id=?', $this->$idField ]
			];

			/* Exclude the first post */
			if( $firstComment = $this->mapped( 'first_comment_id' ) )
			{
				$where[] = [ 'type_id <> ?', $firstComment ];
			}

			$posts = Db::i()->select( "count(*) as sum, type_id", 'core_reputation_index', $where, NULL, NULL, array( 'type_id' ) );
			
			$posts = iterator_to_array( $posts );
			
			usort( $posts, function( $item1, $item2 )
			{
				return $item2['sum'] <=> $item1['sum'];
			} );
			
			/* Just store the top 100 posts, they are already sorted by highest to lowest */
			$posts = array_slice( $posts, 0, 100 );
			$this->_storeCached( $cacheKey, $posts );
		}

		$postIds = array();
		$counts = array();
		foreach ( $posts as $post )
		{
			$postIds[] = $post['type_id'];
			$counts[$post['type_id']] = $post['sum'];
		}

		if ( empty( $postIds ) )
		{
			return array();
		}

		return $this->getComments( $commentClass, $commentIdField, $idField, $postIds, $count, $counts );
	}

	/**
	 * Most helpful posts
	 *
	 * @param int $count The number of results to return, max 100
	 * @return    array
	 * @throw BadMethodCallException
	 * @throws Exception
	 */
	public function helpfulPosts( int $count = 5 ): array
	{
		$commentClass = static::$commentClass;
		$commentIdField = $commentClass::$databasePrefix . $commentClass::$databaseColumnId;
		$idField = static::$databaseColumnId;

		if ( !IPS::classUsesTrait( $commentClass, 'IPS\Content\Helpful' ) )
		{
			throw new BadMethodCallException();
		}

		if ( $count > 100 )
		{
			throw new BadMethodCallException();
		}

		$cacheKey = 'helpfulPosts_' . $count;

		try
		{
			$posts = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			$where = array( 'app=? and type=? and item_id=? and hidden=0', $commentClass::$application, 'helpful', $this->$idField );
			$posts = Db::i()->select( "count(*) as sum, comment_id", 'core_solved_index', $where, NULL, NULL, array( 'comment_id' ) );

			$posts = iterator_to_array( $posts );

			usort( $posts, function( $item1, $item2 )
			{
				return $item2['sum'] <=> $item1['sum'];
			} );

			/* Just store the top 100 posts, they are already sorted by highest to lowest */
			$posts = array_slice( $posts, 0, 100 );
			$this->_storeCached( $cacheKey, $posts );
		}

		$postIds = array();
		$counts = array();
		foreach ( $posts as $post )
		{
			$postIds[] = $post['comment_id'];
			$counts[$post['comment_id']] = $post['sum'];
		}

		if ( empty( $postIds ) )
		{
			return array();
		}

		return $this->getComments( $commentClass, $commentIdField, $idField, $postIds, $count, $counts );
	}

	protected $commentCache = array();

	/**
	 * Get the comments, storing them for later use in the same request
	 *
	 * @param string $commentClass
	 * @param string $commentIdField
	 * @param string $idField
	 * @param array $postIds
	 * @param int $count
	 * @param array $counts
	 * @return array
	 */
	protected function getComments( string $commentClass, string $commentIdField, string $idField, array $postIds, int $count, array $counts ): array
	{
		$key = md5( $commentClass . $commentIdField . $idField . implode( ',', $postIds ) . $count . json_encode( $counts ) );

		if ( ! isset( $this->commentCache[ $key ] ) )
		{
			$this->commentCache[ $key ] = [];
			foreach ( new ActiveRecordIterator( Db::i()->select( '*', $commentClass::$databaseTable, [Db::i()->in( $commentIdField, $postIds )], "FIND_IN_SET( {$commentIdField}, '" . implode( ",", $postIds ) . "' )", [0, $count] ), $commentClass ) as $comment )
			{
				if ( $comment->canView() and $comment->mapped( 'item' ) == $this->$idField and isset( $counts[$comment->$commentIdField] ) )
				{
					$this->commentCache[ $key ][] = ['comment' => $comment, 'count' => $counts[$comment->$commentIdField]];
				}
			}
		}

		return $this->commentCache[ $key ];
	}
	/**
	 * Fetch the top 10 popular days for posts
	 *
	 * @param int $count	Number of days to return
	 * @return array
	 * @throws Exception
	 */
	public function popularDays( int $count=10 ): array
	{
		$return = array();
		$commentClass = static::$commentClass;
		$commentIdField = $commentClass::$databasePrefix . $commentClass::$databaseColumnId;
		$rows = array();
		$commentIds = array();

		$cacheKey = 'popularDays_' . $count;

		try
		{
			$posts = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			/* @var $databaseColumnMap array */
			$dateColumn = $commentClass::$databasePrefix . ( $commentClass::$databaseColumnMap['updated'] ?? $commentClass::$databaseColumnMap['date'] );
			$where = $this->_getVisibleWhere();

			$posts = iterator_to_array( Db::i()->select( "COUNT(*) AS count, MIN({$commentIdField}) as commentId, (DATE_FORMAT( FROM_UNIXTIME( IFNULL( {$dateColumn}, 0 ) ), '%Y-%c-%e' ) ) as time", $commentClass::$databaseTable, $where, 'count desc', array( 0, $count ), array( 'time' ) ) );

			$this->_storeCached( $cacheKey, $posts );
		}

		foreach ( $posts as $row )
		{
			if ( ! in_array( $row['time'], $rows ) )
			{
				$rows[ $row['time'] ] = 0;
			}

			$rows[ $row['time'] ] += $row['count'];
			$commentIds[ $row['time'] ] = $row['commentId'];
		}

		foreach ( $rows as $time => $val )
		{
			$datetime = new IPSDateTime;
			$datetime->setTime( 12, 0, 0 );
			$exploded = explode( '-', $time );
			$datetime->setDate( $exploded[0], $exploded[1], $exploded[2] );

			$return[ $time ] = array( 'date' => $datetime, 'count' => $val, 'commentId' => $commentIds[ $time ] );
		}
		
		return $return;
	}

	/**
	 * Clear any cached stats
	 *
	 * @return void
	 * @throws Exception
	 */
	public function clearCachedStatistics(): void
	{
		$idField = static::$databaseColumnId;
		Db::i()->delete( 'core_item_statistics_cache', array( 'cache_class=? and cache_item_id=?', get_class( $this ), $this->$idField ) );
	}

	/**
	 * @brief	Loaded Extensions
	 */
	protected static $loadedExtensions = array();

	/**
	 * Get all attachments for this item
	 *
	 * @param array|null $extraWhere Additional where clause
	 * @param int $limit Number to return/limit to
	 * @param string|NULL $orderBy Order by clause (optional)
	 * @param string $cacheName
	 * @return  array
	 * @throws Exception
	 */
	protected function _getAllAttachments( array|null $extraWhere=NULL, int $limit=10, string|null $orderBy=NULL, $cacheName='allAttachments' ): array
	{
		$idField = static::$databaseColumnId;
		$cacheKey = $cacheName . '_' . md5( json_encode( $extraWhere ) . $limit . $orderBy );
		$return = array();

		try
		{
			$return = $this->_getCached( $cacheKey );
		}
		catch( OutOfRangeException $e )
		{
			/* This is more efficient and avoids a very slow table join */
			if ( stristr( $orderBy, 'attach_date' ) )
			{
				$orderBy = str_replace( 'attach_date', 'attachment_id', $orderBy );
			}

			$where = array( array( 'location_key=? and id1=?', static::$application . '_' . IPS::mb_ucfirst( static::$module ), $this->$idField ) );

			if ( $extraWhere !== NULL )
			{
				array_push( $where, $extraWhere );
			}

			/* Get attachments from all comments */
			$commentClass = static::$commentClass;
			$commentTableJoin = [ 'id2=' . $commentClass::$databasePrefix . $commentClass::$databaseColumnId ];
			if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
			{
				$approvedColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'];
				$commentTableJoin[] = "{$approvedColumn} = 1";
			}
			if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
			{
				$hiddenColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'];
				$commentTableJoin[] = "{$hiddenColumn} = 0";
			}

			$return = iterator_to_array(
				Db::i()->select( '*', 'core_attachments_map', $where, $orderBy, $limit )
					->join( 'core_attachments', array( 'attach_id=attachment_id' ) )
					->join( $commentClass::$databaseTable, array( implode( ' AND ', $commentTableJoin ) ), 'INNER' )
			);

			/* Get link to comments */
			foreach( $return as $k => $map )
			{
				/* Get the attachment extension if we don't already have it */
				if ( !isset( static::$loadedExtensions[ $map['location_key'] ] ) )
				{
					$exploded = explode( '_', $map['location_key'] );
					try
					{
						$extensions = Application::load( $exploded[0] )->extensions( 'core', 'EditorLocations' );
						if ( isset( $extensions[ $exploded[1] ] ) )
						{
							static::$loadedExtensions[ $map['location_key'] ] = $extensions[ $exploded[1] ];
						}
					}
					catch ( OutOfRangeException | UnexpectedValueException $e ) { }
				}
				
				if ( isset( static::$loadedExtensions[ $map['location_key'] ] ) )
				{
					try
					{
						$url = static::$loadedExtensions[ $map['location_key'] ]->attachmentLookup( $map['id1'], $map['id2'], $map['id3'] );

						$return[ $k ]['commentUrl'] = (string) $url->url();
					}
					catch ( LogicException|BadMethodCallException $e ) { }
				}
			}

			$this->_storeCached( $cacheKey, $return );
		}

		return $return;
	}

	/**
	 * Return a sub query to fetch only visible posts
	 *
	 * @param	int		$id		Content item ID
	 * @return Select
	 */
	protected function _subQueryVisibleComments( int $id ): Select
	{
		$commentClass = static::$commentClass;
		/* @var $databaseColumnMap array */
		return Db::i()->select( $commentClass::$databasePrefix . $commentClass::$databaseColumnId, $commentClass::$databaseTable, array_merge( $this->_getVisibleWhere(), array( array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . $id ) ) ) );
	}

	/**
	 * Return the where query to ensure we only select visible comments
	 *
	 * @return array
	 */
	protected function _getVisibleWhere(): array
	{
		/* @var $databaseColumnMap array */
		$commentClass = static::$commentClass;

		$lookFor = 'IPS\cms\Records\CommentTopicSync';

		if ( mb_substr( $commentClass, 0, mb_strlen( $lookFor ) ) === $lookFor )
		{
			$idField = 'record_topicid';
		}
		else
		{
			$idField = static::$databaseColumnId;
		}

		$commentItemField = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'];

		$where = array();
		if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
		{
			$approvedColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'];
			$where[] = array( "{$approvedColumn} = 1" );
		}
		if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
		{
			$hiddenColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'];
			$where[] = array( "{$hiddenColumn} = 0" );
		}

		/* Exclude first posts */
		if ( isset( $commentClass::$databaseColumnMap['first'] ) )
		{
			$firstCommentColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['first'];
			$where[] = array( "{$firstCommentColumn} = 0" );
		}

		if ( $commentClass::commentWhere() !== NULL )
		{
			$where[] = $commentClass::commentWhere();
		}

		$where[] = array( $commentItemField . '=?', $this->$idField );

		return $where;
	}

	/**
	 * @brief Cached data
	 */
	public static array|null $cachedActivity = NULL;

	/**
	 * Get the cached data
	 *
	 * @param	string	$key	Key to get
	 * @throws Exception
	 * @return mixed|NULL
	 */
	protected function _getCached( string $key ): mixed
	{
		$idField = static::$databaseColumnId;

		$class = get_class( $this );
		$arrayKey = $class.'.'.$this->$idField;
		if ( !isset( static::$cachedActivity[$arrayKey] ) )
		{
			try
			{
				$cache = Db::i()->select( '*', 'core_item_statistics_cache', array( 'cache_class=? and cache_item_id=?', $class, $this->$idField ) )->first();
				
				if ( $cache['cache_added'] > time() - 86400 )
				{
					static::$cachedActivity[ $arrayKey ] = json_decode( $cache['cache_contents'], TRUE );
				}
				else
				{
					Db::i()->delete( 'core_item_statistics_cache', array( 'cache_class=? and cache_item_id=?', get_class( $this ), $this->$idField ) );
				}
			}
			catch( UnderflowException $e ) { }
		}

		if ( isset(static::$cachedActivity[ $arrayKey ] ) and isset( static::$cachedActivity[ $arrayKey ][ $key ] ) )
		{
			return static::$cachedActivity[ $arrayKey ][ $key ];
		}
		else
		{
			if( !isset( static::$cachedActivity[ $arrayKey ] ) )
			{
				static::$cachedActivity[ $arrayKey ] = array();
			}

			static::$cachedActivity[ $arrayKey ][ $key ] = array();
			throw new OutOfRangeException;
		}
	}

	/**
	 * @brief	Should we store the data in the cache?
	 */
	protected array|null $storeCache = NULL;

	/**
	 * Set cached data
	 *
	 * @param string $key Key to store
	 * @param mixed $value Value to store
	 *
	 * @return	void
	 * @throws Exception
	 */
	protected function _storeCached( string $key, mixed $value ): void
	{
		try
		{
			$this->_getCached( $key );
		}
		catch( Exception $e ) { }

		$idField = static::$databaseColumnId;
		$class = get_class( $this );
		$arrayKey = $class.'.'.$this->$idField;

		static::$cachedActivity[ $arrayKey ][ $key ] = $value;

		$this->storeCache[ $arrayKey ][ $key ] = $this->$idField;
	}

	/**
	 * Store the cache during destruction
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if( $this->storeCache )
		{
			foreach( $this->storeCache as $key => $data )
			{
				Db::i()->insert( 'core_item_statistics_cache', array(
					'cache_class'    => get_class( $this ),
					'cache_item_id'  => str_replace( get_class($this) . '.', '', $key),
					'cache_contents' => json_encode( static::$cachedActivity[$key] ),
					'cache_added'	 => time()
				), TRUE );
			}
		}

		if( is_callable( 'parent::__destruct' ) )
		{
			parent::__destruct();
		}
	}
}