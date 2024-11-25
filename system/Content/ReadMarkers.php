<?php
/**
 * @brief		Read/Unread Tracking Trait for Content Models
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		8 Jul 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Node\Model;
use IPS\Member;
use IPS\Db;
use IPS\DateTime;
use IPS\IPS;
use OutOfRangeException;

use function defined;
use function header;
use function get_called_class;
use function is_array;
use function is_null;
use function is_numeric;
use function count;
use function max;
use function json_encode;
use function implode;
use function array_keys;
use function in_array;
use function array_merge;
use function time;
use function md5;
use function serialize;
use function array_unique;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Read/Unread Tracking Trait for Content Models
 *
 * @note	Content classes will gain special functionality by implementing this interface
 */
trait ReadMarkers
{
	/**
	 * The maximum number of IDs to store in core_item_markers.item_read_array
	 *
	 * As a JSON-encoded array of ID => Timestamp values, the storage required
	 * is (14+l)n + 1 bytes, where l is the length in bytes of any given ID and n is the number of IDs.
	 * We store the value in a MEDIUMTEXT field, so the maximum length is 16 megabytes. Assuming
	 * l is always 5 (NB: but it isn't), this means this constant could be increased to a theoretic
	 * maximum of 883,011.
	 */
	protected static int $storageCutoff = 100000;
	
	/**
	 * Read Marker cache
	 */
	protected ?int $unread = NULL;
	
	/**
	 * Does a container contain unread items?
	 *
	 * @param	Model		$container	The container
	 * @param	Member|NULL	$member		The member (NULL for currently logged in member)
	 * @param	bool				$children	Check children for unread items
	 * @return	bool|NULL
	 */
	public static function containerUnread( Model $container, ?Member $member = NULL, bool $children=TRUE ): bool|NULL
	{
		$member = $member ?: Member::loggedIn();

		/* We only do this if the thing is tracking markers */
		if ( !$member->member_id )
		{
			return NULL;
		}
		
		/* What was the last time something was posted in here? */
		$lastCommentTime = $container->getLastCommentTime();

		if ( $lastCommentTime === NULL )
		{
			/* Do we have any children to be concerned about? */
			if( $children )
			{
				foreach ( $container->children( 'view', $member ) AS $child )
				{
					if ( static::containerUnread( $child, $member ) )
					{
						return TRUE;
					}
				}
			}
			
			return FALSE;
		}
		
		/* Was that after the last time we marked this forum read? */
		$markers = $member->markersResetTimes( static::$application );

		if ( isset( $markers[ $container->_id ] ) )
		{
			if ( $markers[ $container->_id ] < $lastCommentTime->getTimestamp() )
			{
				return TRUE;
			}
		}
		else if ( $member->marked_site_read >= $lastCommentTime->getTimestamp() )
		{
			if( $children )
			{
				/* This forum has nothing new, but do children? */
				foreach ( $container->children( 'view', $member ) as $child )
				{
					if ( static::containerUnread( $child, $member ) )
					{
						return TRUE;
					}
				}
			}
			
			return FALSE;
		}
		else
		{
			if( $container->_items !== 0 or $container->_comments !== 0 )
			{
				return TRUE;
			}
		}
		
		/* Check children */
		if( $children )
		{
			foreach ( $container->children( 'view', $member ) as $child )
			{
				if ( static::containerUnread( $child, $member ) )
				{
					return TRUE;
				}
			}
		}
		
		/* Still here? It's read */
		return FALSE;
	}
	
	/**
	 * Is unread?
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	int|NULL	0 = read. -1 = never read. 1 = updated since last read. NULL = unsupported
	 * @note	When a node is marked read, we stop noting which individual content items have been read. Therefore, -1 vs 1 is not always accurate but rather goes on if the item was created
	 */
	public function unread( ?Member $member = NULL ): int|null
	{
		if ( $this->unread === NULL )
		{
			$latestThing = 0;
			foreach ( array( 'updated', 'last_comment', 'last_review' ) as $k )
			{
				if ( isset( static::$databaseColumnMap[ $k ] ) and ( $this->mapped( $k ) <= time() AND $this->mapped( $k ) > $latestThing ) )
				{
					$latestThing = $this->mapped( $k );
				}
			}
			
			$idColumn = static::$databaseColumnId;
			$container = $this->containerWrapper();
			
			$this->unread = static::unreadFromData( $member, $latestThing, $this->mapped('date'), $this->$idColumn, $container?->_id );
		}
		
		return $this->unread;
	}
	
	/**
	 * Calculate unread status from data
	 *
	 * @param	Member|NULL	$member			The member (NULL for currently logged in member)
	 * @param	int					$updateDate		Timestamp of when item was last updated or replied to
	 * @param	int					$createDate		Timestamp of when item was created
	 * @param	int					$itemId			The item ID
	 * @param	int|null			$containerId	The container ID
	 * @param	bool				$limitToApp		If FALSE, will load all item markers into memopry rather than just what's in this app. This should be used in views which combine data from multiple apps like streams.
	 * @return	int|NULL	0 = read. -1 = never read. 1 = updated since last read. NULL = unsupported
	 * @note	When a node is marked read, we stop noting which individual content items have been read. Therefore, -1 vs 1 is not always accurate but rather goes on if the item was created
	 */
	public static function unreadFromData( ?Member $member, int $updateDate, int $createDate, int $itemId, ?int $containerId, bool $limitToApp = TRUE ): int|null
	{
		/* Get the member */
		$member = $member ?: Member::loggedIn();
		
		/* We only do this if the thing is tracking markers and the user is logged in */
		if ( !$member->member_id )
		{
			return NULL;
		}
		
		/* Get the markers */
		if ( $limitToApp )
		{
			$resetTimes = $member->markersResetTimes( static::$application );
		}
		else
		{
			$resetTimes = $member->markersResetTimes( NULL );
			$resetTimes = $resetTimes[static::$application] ?? array();
		}
		$markers = $member->markersItems( static::$application, static::makeMarkerKey( $containerId ) );
		
		/* If we do not have a marker for this item... */
		if( !isset( $markers[ $itemId ] ) )
		{
			/* Figure the reset time - i.e. when the user marked either the container or the whole site as read */
			if( $containerId )
			{
				$resetTime = ( isset( $resetTimes[ $containerId ] ) AND $resetTimes[ $containerId ] > $member->marked_site_read ) ? $resetTimes[ $containerId ] : $member->marked_site_read;
			}
			else
			{
				$resetTime = ( !is_array( $resetTimes ) AND $resetTimes > $member->marked_site_read ) ? $resetTimes : $member->marked_site_read;
			}
			
			/* If the reset time is after when this item was updated, it's read */
			if ( !is_null( $resetTime ) and $resetTime >= $updateDate )
			{
				return 0;
			}
			/* Otherwise it's unread */
			else
			{
				/* If we have a reset time, but it's after when this item was created, it's been updated since we read it */
				if ( !is_null( $resetTime ) and $resetTime > $createDate )
				{
					return 1;
				}
				/* Otherwise it's completely new to us */
				else
				{
					return -1;
				}
			}
		}
		/* If we do have a marker, but the thing has been updated since our marker, it's updated */
		elseif( $markers[ $itemId ] < $updateDate )
		{
			return 1;
		}
		/* Otherwise it's read */
		else
		{
			return 0;
		}
	}
	
	/**
	 * @brief	Time last read cache
	 */
	protected array $timeLastRead = array();
	
	/**
	 * Time last read
	 *
	 * @param	Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	DateTime|NULL
	 */
	public function timeLastRead( ?Member $member = NULL ): DateTime|null
	{
		/* Work out the member */
		$member = $member ?: Member::loggedIn();
		if ( !$member->member_id )
		{
			return NULL;
		}
		
		/* Get it */
		if ( !isset( $this->timeLastRead[ $member->member_id ] ) )
		{
			/* Check the time the entire site was marked read */
			$times = array();
			$times[] =  $member->marked_site_read;

			$containerId = NULL;
			
			/* Check the reset time */
			if ( $container = $this->containerWrapper() )
			{
				$resetTimes = $member->markersResetTimes( static::$application );
				if ( isset( $resetTimes[ $container->_id ] ) and is_numeric( $resetTimes[ $container->_id ] ) )
				{
					$times[] = $resetTimes[ $container->_id ];
				}

				$containerId = $container->_id;
			}
	
			/* Check the actual item */
			$markers = $member->markersItems( static::$application, static::makeMarkerKey( $containerId ) );
			$idColumn = static::$databaseColumnId;
			if ( isset( $markers[ $this->$idColumn ] ) )
			{
				$times[] = ( is_array( $markers[ $this->$idColumn ] ) ) ? max( $markers[ $this->$idColumn ] ) : $markers[ $this->$idColumn ];
			}
			
			/* Set the highest of those */
			$this->timeLastRead[ $member->member_id ] = ( count( $times ) ? max( $times ) : NULL );
		}
		
		/* Return */
		return $this->timeLastRead[ $member->member_id ] ? DateTime::ts( $this->timeLastRead[ $member->member_id ] ) : NULL;
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

		if ( ( $this->unread() OR $force ) and $member->member_id )
		{
			/* Mark this one read */
			$idColumn	= static::$databaseColumnId;
			$container = $this->containerWrapper();
			$key		= static::makeMarkerKey( $container?->_id );
			$readArray	= $member->markersItems( static::$application, $key );

			if ( isset( $member->markers[ static::$application ][ $key ] ) )
			{
				$marker = $member->markers[ static::$application ][ $key ];

				/* We've already read this topic more recently */
				if( isset( $readArray[ $this->$idColumn ] ) AND $readArray[ $this->$idColumn ] >= $time )
				{
					return;
				}

				$readArray[ $this->$idColumn ] = $time;

				$readArray = array_slice( $readArray, ( count( $readArray ) > static::$storageCutoff ) ? (int) '-' . static::$storageCutoff : 0, ( count( $readArray ) > static::$storageCutoff ) ? NULL : static::$storageCutoff, TRUE );

				$toStore	= array( 'update', array( 'item_read_array' => json_encode( $readArray ) ), array( 'item_key=? AND item_member_id=? AND item_app=?', $key, $member->member_id, static::$application ) );
            }
			else
			{
				$readArray = array( $this->$idColumn => $time );
				$marker = array(
					'item_key'			=> $key,
					'item_member_id'	=> $member->member_id,
					'item_app'			=> static::$application,
					'item_read_array'	=> json_encode( $readArray ),
					'item_global_reset'	=> $member->marked_site_read ?: 0,
					'item_app_key_1'	=> $this->mapped('container') ?: 0,
					'item_app_key_2'	=> static::getItemMarkerKey( 2 ),
					'item_app_key_3'	=> static::getItemMarkerKey( 3 ),
				);

				$toStore	= array( 'insert', $marker );
			}

			/* Reset cached markers in the member object */
			$member->markers[ static::$application ][ $key ] = $marker;
			
			/* Have we now read the whole node? */
			$whereClause = array();

			/* Ignore linked content if linked content is supported */
			if ( isset( static::$databaseColumnMap['state'] ) )
			{
				$whereClause[] = array( static::$databaseTable . '.' . static::$databaseColumnMap['state'] . '!=?', 'link' );
				$whereClause[] = array( static::$databaseTable . '.' . static::$databaseColumnMap['state'] . '!=?', 'merged' );
			}

			if ( count( $readArray ) > 0 )
			{
				$whereClause[] = array( static::$databaseTable . '.' . static::$databasePrefix . $idColumn . ' NOT IN(' . implode( ',', array_keys( $readArray ) ) . ')' );
			}

			if( $this->containerWrapper() )
			{
				$whereClause[]	= array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['container'] . '=?', $this->container()->_id );
			}

			if ( IPS::classUsesTrait( get_called_class(), 'IPS\Content\Hideable' ) )
			{
				if ( !static::canViewHiddenItems( $member, $this->containerWrapper() ) )
				{
					if ( isset( static::$databaseColumnMap['approved'] ) )
					{
						$whereClause[] = array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'] . '=?', 1 );
					}
					elseif ( isset( static::$databaseColumnMap['hidden'] ) )
					{
						$whereClause[] = array( static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'] . '=?', 0 );
					}
				}

				/* No matter if we can or cannot view hidden items, we do not want these to show: -2 is queued for deletion and -3 is posted before register */
				if ( isset( static::$databaseColumnMap['hidden'] ) )
				{
					$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['hidden'];
					$whereClause[] = array( "{$col}!=-2 AND {$col} !=-3" );
				}
				else if ( isset( static::$databaseColumnMap['approved'] ) )
				{
					$col = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap['approved'];
					$whereClause[] = array( "{$col}!=-2 AND {$col}!=-3" );
				}
			}

            if( $extraContainerWhere !== NULL )
            {
                if ( !is_array( $extraContainerWhere ) or !is_array( $extraContainerWhere[0] ) )
                {
                    $extraContainerWhere = array( $extraContainerWhere );
                }
                $whereClause = array_merge( $whereClause, $extraContainerWhere );
            }

			if ( isset( $marker['item_global_reset'] ) )
			{
				$subWhere	= array();
				$checked	= array();
				foreach ( array( 'last_comment', 'last_review', 'updated' ) as $k )
				{
					/* If we already hit last_comment and/or last_review, skip updated since we don't mark as unread when stuff is updated */
					if( count( $checked ) AND $k == 'updated' )
					{
						break;
					}

					if ( isset( static::$databaseColumnMap[ $k ] ) )
					{
						if ( is_array( static::$databaseColumnMap[ $k ] ) )
						{
							if( !in_array( static::$databaseColumnMap[ $k ][0], $checked ) )
							{
								$subWhere[] = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap[ $k ][0] . '>' . $marker['item_global_reset'];
								$checked[] = static::$databaseColumnMap[ $k ][0];
							}
						}
						else
						{
							if( !in_array( static::$databaseColumnMap[ $k ], $checked ) )
							{
								$subWhere[] = static::$databaseTable . '.' . static::$databasePrefix . static::$databaseColumnMap[ $k ] . '>' . $marker['item_global_reset'];
								$checked[] = static::$databaseColumnMap[ $k ];
							}
						}
					}
				}

				if( count( $subWhere ) )
				{
					$whereClause[]	= array( '(' . implode( ' OR ', $subWhere ) . ')' );
				}
			}

			$unreadCount = Db::i()->select(
				'COUNT(*) as count',
				static::$databaseTable,
				$whereClause
			)->first();

			if ( !$unreadCount AND $this->containerWrapper() )
			{
				static::markContainerRead( $this->containerWrapper(), $member, FALSE );
			}
			elseif( $toStore !== NULL )
			{
				if( $toStore[0] == 'update' )
				{
					Db::i()->update( 'core_item_markers', $toStore[1], $toStore[2] );
				}
				else
				{
					Db::i()->replace( 'core_item_markers', $toStore[1] );
				}
			}
		}
	}
	
	/**
	 * Mark container as read
	 *
	 * @param	Model		$container	The container
	 * @param	Member|NULL	$member		The member (NULL for currently logged in member)
	 * @param	bool				$children	Whether to mark children as read (default) or not as well
	 * @return	void
	 */
	public static function markContainerRead( Model $container, ?Member $member = NULL, bool $children = TRUE ): void
	{
		$member = $member ?: Member::loggedIn();
		if ( $member->member_id )
		{		
			$key = static::makeMarkerKey( $container->_id );

			$data = array(
				'item_key'			=> $key,
				'item_member_id'	=> $member->member_id,
				'item_app'			=> static::$application,
				'item_read_array'	=> json_encode( array() ),
				'item_global_reset'	=> time(),
				'item_app_key_1'	=> $container->_id,
				'item_app_key_2'	=> static::getItemMarkerKey( 2 ),
				'item_app_key_3'	=> static::getItemMarkerKey( 3 ),
			);

			Db::i()->replace( 'core_item_markers', $data );

			/* Update container caches */
			$member->setMarkerResetTimes( $data );
			$member->haveAllMarkers = FALSE;
			unset( $member->markersResetTimes[ static::$application ] );
			
			if( $children )
			{
				foreach( $container->children( 'view', $member, false ) as $child )
				{
					static::markContainerRead( $child, $member );
				}
			}
		}
	}
	
	/**
	 * Make key
	 *
	 * @param	int|NULL	$containerId	The container ID
	 * @return	string
	 * @note	We use serialize here which is usually not allowed, however, the value is encoded and never unserialized so there is no security issue.
	 */
	public static function makeMarkerKey( ?int $containerId = NULL ): string
	{
		$keyData = array();
		if ( $containerId )
		{
			$keyData['item_app_key_1'] = $containerId;
		}
		
		return md5( serialize( $keyData ) );
	}

	/**
	 * Find out if there are any unread items in the same container
	 *
	 * @return	bool
	 * @throws	OutOfRangeException
	 */
	public function containerHasUnread(): bool
	{
		/* What container are we in? */
		$container = $this->container();

		/* If the whole container is read or there's a guest, we know we have nothing */
		if ( static::containerUnread( $container, NULL, FALSE ) !== TRUE  OR !Member::loggedIn()->member_id )
		{
			throw new OutOfRangeException;
		}

		return TRUE;
	}

	/**
	 * Fetch next unread item in the same container
	 *
	 * @return	static
	 * @throws	OutOfRangeException
	 */
	public function nextUnread(): static
	{
		/* What container are we in? */
		$container = $this->container();

		/* Check container has unread */
		$this->containerHasUnread();
		
		/* Otherwise we need to query... */
		$where = array();		
		$where[] = array( static::$databaseTable . '.' . static::$databaseColumnMap['container'] . '=?', $container->_id );

        /* Exclude links */
        if ( isset( static::$databaseColumnMap['state'] ) )
        {
            $where[] = array( static::$databaseTable . '.' .static::$databaseColumnMap['state'] . '!=?', 'link' );
            $where[] = array( static::$databaseTable . '.' .static::$databaseColumnMap['state'] . '!=?', 'merged' );
        }

		/* What are we going by? */
		$fields = array();
		foreach ( array( 'updated', 'last_comment', 'last_review' ) as $k )
		{
			if ( isset( static::$databaseColumnMap[ $k ] ) )
			{
				if ( is_array( static::$databaseColumnMap[ $k ] ) )
				{
					foreach ( static::$databaseColumnMap[ $k ] as $_k )
					{
						$fields[] = 'IFNULL(`' . static::$databaseTable . '`.`' . static::$databasePrefix . $_k . '`,0)';
					}
				}
				else
				{
					$fields[] = 'IFNULL(`' . static::$databaseTable . '`.`' . static::$databasePrefix . static::$databaseColumnMap[ $k ] . '`,0)';
				}
			}
		}
		$fields = array_unique( $fields );
		$fields = ( count( $fields ) > 1 ) ? ( 'GREATEST( ' . implode( ', ', $fields ) . ' )' ) : $fields;
		
		/* We need only items that have been updated since we reset the container (or the site, if that was more recent) */
		$resetTimes = Member::loggedIn()->markersResetTimes( static::$application );
		$resetTime = NULL;
		if( isset( $resetTimes[ $container->_id ] ) )
		{
			$resetTime = $resetTimes[ $container->_id ];
		}
		if ( is_null( $resetTime ) or $resetTime < Member::loggedIn()->marked_site_read )
		{
			$resetTime = Member::loggedIn()->marked_site_read;
		}
		if ( $resetTime )
		{
			$where[] = array( $fields . ' > ?', $resetTime );
		}
		
		/* And we don't want this one */
		$idColumn = static::$databaseColumnId;
		$where[] = array( static::$databasePrefix . static::$databaseColumnId . '<> ?', $this->$idColumn );
		
		/* Find one */
		$markers = Member::loggedIn()->markersItems( static::$application, static::makeMarkerKey( $container->_id ) );
		foreach (static::getItemsWithPermission( $where, static::$databasePrefix . $this->getDateColumn() . ' DESC', 5000, 'read', Filter::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, FALSE, NULL, $container, FALSE, FALSE, FALSE ) as $item )
		{
			/* If we have never read it, return it */
			if( !isset( $markers[ $item->$idColumn ] ) )
			{
				return $item;
			}
			
			/* Otherwise, check when it was updated... */
			$latestThing = 0;
			foreach ( array( 'updated', 'last_comment', 'last_review' ) as $k )
			{
				if ( isset( static::$databaseColumnMap[ $k ] ) and ( $item->mapped( $k ) < time() AND $item->mapped( $k ) > $latestThing ) )
				{
					$latestThing = $item->mapped( $k );
				}
			}
			
			/* And return it if that was after the last time we read it */
			if ( $latestThing > $markers[ $item->$idColumn ] )
			{
				return $item;
			}
		}
				
		/* Or throw an exception saying we have nothing if we're still here */
		throw new OutOfRangeException;
	}
	
	/**
	 * Retrieve any custom item_app_key_x values for item marking
	 *
	 * @param int $key	2 or 3 for respective column
	 * @return	int
	 * @note	This is abstracted to make it easier for apps to override
	 */
	public static function getItemMarkerKey( int $key ): int
	{
		return 0;
	}
}