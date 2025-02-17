<?php
/**
 * @brief		Node Statistics Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Nov 2020
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Db;
use IPS\Member;
use function count;
use function defined;
use function is_array;

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
	 * @brief Cached array of IDs a member has posted in
	 */
	protected array $authorsPostedIn = array();

	/**
	 * Get authors posted in
	 *
	 * @param array $inSet The item_ids to check
	 * @return array
	 */
	public function authorsPostedIn( array $inSet ): array
	{
		if ( !isset( static::$contentItemClass ) )
		{
			return array();
		}

		$_key = md5( json_encode( $inSet ) );

		if( isset( $this->authorsPostedIn[ $_key ] ) )
		{
			return $this->authorsPostedIn[ $_key ];
		}

		$contentItemClass = static::$contentItemClass;
		$commentClass     = $contentItemClass::$commentClass;
		$itemIdField      = $contentItemClass::$databaseColumnId;

		if ( !$contentItemClass::$commentClass )
		{
			return array();
		}

		$items = iterator_to_array( Db::i()->select( 'map_item_id, map_member_id', 'core_item_member_map', array( array( 'map_class=?', $contentItemClass ), array( Db::i()->in( 'map_item_id', $inSet ) ) ) ) );
		$reload = FALSE;
		$itemIds = array();

		/* Do we have the items we need? */
		if ( count( $items ) )
		{
			foreach ( $items as $row )
			{
				$itemIds[] = $row['map_item_id'];
			}

			$diff = array_diff( $inSet, $itemIds );

			if ( count( $diff ) )
			{
				$reload = TRUE;
				$this->rebuildPostedIn( $diff );
			}
		}
		else
		{
			/* We got nothing */
			$reload = TRUE;
			$this->rebuildPostedIn( $inSet );
		}

		/* Do we need to reload? */
		if ( $reload )
		{
			$items = iterator_to_array( Db::i()->select( 'map_item_id, map_member_id', 'core_item_member_map', array(array('map_class=?', $contentItemClass), array(Db::i()->in( 'map_item_id', $inSet ) ) ) ) );
			$itemIds = [];

			if ( count( $items ) )
			{
				foreach ( $items as $row )
				{
					$itemIds[] = $row['map_item_id'];
				}
			}

			if ( $itemIds )
			{
				/* Check to see if there are any item IDs that can't be set because of missing posts, etc. This prevents an attempted rebuild from occuring over and over again */
				$diff = array_diff( $inSet, $itemIds );

				if ( count( $diff ) )
				{
					foreach( $diff as $id )
					{
						Db::i()->replace( 'core_item_member_map', array( 'map_class' => $contentItemClass, 'map_item_id' => $id, 'map_member_id' => NULL ), TRUE );
					}
				}
			}
		}

		$return = array();
		foreach( $items as $row )
		{
			$return[ $row['map_member_id'] ][] = $row['map_item_id'];
		}

		$this->authorsPostedIn[ $_key ] = $return;

		return $return;
	}

	/**
	 * @brief Cached array of IDs a member has posted in (calculated the expensive way so cached separately)
	 */
	protected array $contentPostedIn = array();

	/**
	 * Retrieve an array of IDs a member has posted in.
	 *
	 * @param Member|null $member	The member (NULL for currently logged in member)
	 * @param array|null $inSet	If supplied, checks will be restricted to only the ids provided
	 * @param array|null $additionalWhere    Additional where clause
	 * @param array|null $commentJoinWhere	Additional join clause for comments table
	 * @return	array				An array of content item ids
	 */
	public function contentPostedIn(Member $member=NULL, array $inSet=NULL, array $additionalWhere=NULL, array $commentJoinWhere=NULL ): array
	{
		if ( $member === NULL )
		{
			$member = Member::loggedIn();
		}

		if( !$member->member_id )
		{
			return array();
		}

		if ( !isset( static::$contentItemClass ) )
		{
			return array();
		}

		if ( is_array( $inSet ) AND $additionalWhere === NULL AND $commentJoinWhere === NULL )
		{
			/* Hooray, the easy efficient way */
			$members = $this->authorsPostedIn( $inSet );

			if ( isset( $members[ $member->member_id ] ) )
			{
				return array_values( $members[ $member->member_id ] );
			}

			return array();
		}
		else
		{
			/* The complicated way, which doesn't happen that often */
			/* @var Item $contentItemClass */
			$contentItemClass	= static::$contentItemClass;

			/* @var Comment $commentClass */
			$commentClass = $contentItemClass::$commentClass;
			$idColumn			= static::$databaseColumnId;
			$_key	= md5( $member->member_id . json_encode( $inSet ) );

			if( isset( $this->contentPostedIn[ $_key ] ) )
			{
				return $this->contentPostedIn[ $_key ];
			}

			$where = array();

			/* @var array $databaseColumnMap */
			if ( $contentItemClass::$firstCommentRequired )
			{
				$where[] = array( '(' . $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . '=? )', $member->member_id );
			}
			else
			{
				$where[] = array( $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['container'] . '=?', $this->$idColumn );
				$where[] = array( '(' . $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . '=? OR ' . $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['author'] . '=?)', $member->member_id, $member->member_id );
			}

			if( is_array( $inSet ) AND count( $inSet ) )
			{
				$where[] = array( $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId . ' IN(' . implode( ',', $inSet ) . ')' );
			}

			if ( $additionalWhere )
			{
				$where[] = $additionalWhere;
			}

			$joinClause = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId );

			$items = Db::i()->select( $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId, $contentItemClass::$databaseTable, $where, NULL, NULL, NULL, NULL, Db::SELECT_DISTINCT );
			$items->join( $commentClass::$databaseTable, ( $commentJoinWhere !== NULL ) ? array( $joinClause, $commentJoinWhere ) : $joinClause );

			$ids = array();
			foreach( $items AS $item )
			{
				$ids[$item] = $item;
			}

			$this->contentPostedIn[ $_key ]	= $ids;

			return $ids;
		}
	}

	/**
	 * Populate the item posted in data
	 *
	 * Note that we do fetch guests content here. That is to prevent content from being rebuilt on each click if it solely populated by guests
	 *
	 * @param 	array	$inSet		Array of item IDs
	 * @param array $members	Optional array of member objects to limit by\
	 * @return void
	 */
	public function rebuildPostedIn( array $inSet, array $members=array() ) : void
	{
		/* @var array $databaseColumnMap */
		$contentItemClass = static::$contentItemClass;
		$commentClass     = $contentItemClass::$commentClass;
		$commentItemField = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'];
		$authorColumn     = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'];
		$where = array();

		$deleteWhere = array( array( Db::i()->in( 'map_item_id', $inSet ) ) );
		if ( count( $members ) )
		{
			$memberIds = array();
			foreach ( $members as $member )
			{
				$memberIds[] = $member->member_id;
			}

			$where[] = array(Db::i()->in( $authorColumn, $memberIds ));
			$deleteWhere[] = array( Db::i()->in( 'map_member_id', $memberIds ) );
		}

		Db::i()->delete( 'core_item_member_map', $deleteWhere );

		/* Do the item first if the first comment is not required */
		if ( ! $contentItemClass::$firstCommentRequired )
		{
			$itemAuthorColumn     = $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['author'];
			$itemDateColumn       = $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['date'];

			if ( isset( $contentItemClass::$databaseColumnMap['approved'] ) )
			{
				$approvedColumn = $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['approved'];
				$itemWhere[] = array( Db::i()->in( $approvedColumn, array( 1, 2 ) ) ); # We want approved comments but also comments hidden because the item is hidden
			}
			if ( isset( $contentItemClass::$databaseColumnMap['hidden'] ) )
			{
				$hiddenColumn = $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['hidden'];
				$itemWhere[] = array( Db::i()->in( $hiddenColumn, array( 0, 2 ) ) ); # We want approved comments but also comments hidden because the item is hidden
			}

			$itemWhere[] = array( Db::i()->in( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId, $inSet ) );

			try
			{
				$seen = array();
				/* We don't want a complex query on a large table (forums_posts) hitting a write server, so split this for forums to select (read server) and insert (write server) */
				foreach( Db::i()->select( "`{$authorColumn}`, `{$commentItemField}`", $commentClass::$databaseTable, $where, NULL, 1 ) as $row )
				{
					$key = $row[ $authorColumn ] . '_' . $row[ $commentItemField ];
					if ( isset( $seen[ $key ] ) or ! $row[ $authorColumn ] )
					{
						continue;
					}
					$seen[ $key ] = true;

					Db::i()->insert( 'core_item_member_map', [
						'map_member_id' => $row[ $authorColumn ],
						'map_item_id' => $row[ $commentItemField ],
						'map_latest_date' => time(),
						'map_class' => $contentItemClass
					] );
				}
			}
			catch( Exception ) { }
		}

		/* If forums are being used for Pages comments, we don't want to look at those */
		if( mb_substr( $commentClass, 0, 32 ) === 'IPS\\cms\\Records\\CommentTopicSync' )
		{
			return;
		}

		/* Do the comments */
		if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
		{
			$approvedColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'];
			$where[] = array(Db::i()->in( $approvedColumn, array(1, 2) )); # We want approved comments but also comments hidden because the item is hidden
		}
		if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
		{
			$hiddenColumn = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'];
			$where[] = array(Db::i()->in( $hiddenColumn, array(0, 2) )); # We want approved comments but also comments hidden because the item is hidden
		}

		if ( $commentClass::commentWhere() !== NULL )
		{
			$where[] = $commentClass::commentWhere();
		}

		$where[] = array(Db::i()->in( $commentItemField, $inSet ));

		$insertFromSelect = Db::i()->select( "DISTINCT `{$authorColumn}`, `{$commentItemField}`, UNIX_TIMESTAMP(), '" . str_replace( '\\', '\\\\', $contentItemClass ) . "'", $commentClass::$databaseTable, $where );
		Db::i()->replace( 'core_item_member_map', [ 'map_member_id, map_item_id, map_latest_date, map_class', $insertFromSelect ], TRUE );
	}
}

