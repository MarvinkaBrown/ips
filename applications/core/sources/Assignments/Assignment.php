<?php

/**
 * @brief        Assignment
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        1/8/2024
 */

namespace IPS\core\Assignments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\Db;
use IPS\Member;
use IPS\Member\Team;
use IPS\Patterns\ActiveRecord;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use OutOfRangeException;
use UnderflowException;
use function get_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Assignment extends ActiveRecord
{
	const ASSIGNMENT_MEMBER = 'm';
	const ASSIGNMENT_TEAM = 't';

	/**
	 * @brief       Database Table
	 */
	public static ?string $databaseTable = 'core_assignments';

	/**
	 * @brief       Database Prefix
	 */
	public static string $databasePrefix = 'assign_';

	/**
	 * @brief       Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @var array
	 */
	protected static array $multitonMap = [];

	/**
	 * @return Item
	 * @throws OutOfRangeException
	 */
	public function get_item() : Item
	{
		/* @var Item $class */
		$class = $this->item_class;
		return $class::load( $this->item_id );
	}

	/**
	 * @param Item $item
	 * @return void
	 */
	public function set_item( Item $item ) : void
	{
		$this->item_class = get_class( $item );
		$idColumn = $item::$databaseColumnId;
		$this->item_id = $item->$idColumn;

		if( isset( $item::$containerNodeClass ) )
		{
			$this->container_id = $item->mapped( 'container' );
		}
	}

	/**
	 * @param Member|Team $val
	 * @return void
	 */
	public function set_to( Member|Team $val ) : void
	{
		if( $val instanceof Team )
		{
			$this->type = static::ASSIGNMENT_TEAM;
			$this->_data['to'] = $val->id;
		}
		else
		{
			$this->type = static::ASSIGNMENT_MEMBER;
			$this->_data['to'] = $val->member_id;
		}
	}

	/**
	 * Return the current assignment for this item
	 *
	 * @param Item $item
	 * @return static
	 * @throws OutOfRangeException
	 */
	public static function loadByItem( Item $item ) : static
	{
		/* If assignments are disabled, stop here */
		if( !$item->containerAllowsAssignable() )
		{
			throw new OutOfRangeException;
		}

		$idColumn = $item::$databaseColumnId;

		$key = get_class( $item ) . '-' . $item->$idColumn;
		if( isset( static::$multitonMap['items'][ $key ] ) )
		{
			return static::load( static::$multitonMap['items'][ $key ] );
		}

		try
		{
			$row = Db::i()->select( '*', static::$databaseTable, [ 'assign_item_class=? and assign_item_id=?', get_class( $item ), $item->$idColumn ] )->first();
			static::$multitonMap['items'][ $key ] = $row['assign_id'];
			static::$multitons[ $row['assign_id' ] ] = static::constructFromData( $row );
			return static::$multitons[ $row['assign_id' ] ];
		}
		catch( UnderflowException )
		{
			throw new OutOfRangeException;
		}
	}

	/**
	 * Can this user assign any kind of content?
	 *
	 * @param Member|null $member
	 * @return bool
	 */
	public static function canAssignOnAny( ?Member $member = null ) : bool
	{
		if( !Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			return false;
		}
		$member = $member ?: Member::loggedIn();
		if( $member->modPermission('can_assign_content') )
		{
			return true;
		}

		$perms = $member->modPermissions();
		foreach ( Content::routedClasses( Member::loggedIn(), FALSE, TRUE ) as $_class )
		{
			if( isset( $perms[ "can_assign_content_{$_class::$title}" ] ) and $perms[ "can_assign_content_{$_class::$title}" ] )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the member or team to which this item is assigned
	 *
	 * @return Member|Team|null
	 */
	public function assignedTo() : Member|Team|null
	{
		if( empty( $this->type ) or empty( $this->to ) )
		{
			return null;
		}

		if( $this->type == static::ASSIGNMENT_MEMBER )
		{
			return Member::load( $this->to );
		}

		return Team::load( $this->to );
	}

	/**
	 * Log the first reply
	 *
	 * @param Comment $comment
	 * @return void
	 */
	public function logReply( Comment $comment ) : void
	{
		/* Do nothing if the assignment is closed */
		if( $this->closed )
		{
			return;
		}

		/* If we already have reply data, stop here */
		if( $this->reply_time )
		{
			return;
		}

		/* is it assigned to this member? */
		$assignedTo = $this->assignedTo();
		if( $assignedTo instanceof Team )
		{
			if( !in_array( $comment->author()->member_id, $assignedTo->members ) )
			{
				return;
			}
		}
		elseif( $comment->author()->member_id != $assignedTo->member_id )
		{
			return;
		}

		$this->reply_time = $comment->mapped( 'date' );
		$this->reply_by = $comment->author()->member_id;

		$idColumn = $comment::$databaseColumnId;
		$this->reply_id = $comment->$idColumn;
		$this->save();
	}

	/**
	 * Close the assignment
	 *
	 * @return void
	 */
	public function close() : void
	{
		/* Clear the assignment flag if the item has a column for it */
		if( $item = $this->item )
		{
			if( isset( $item::$databaseColumnMap['assignment'] ) )
			{
				$column = $item::$databaseColumnMap['assignment'];
				$item->$column = null;
				$item->save();
			}
		}

		$this->closed = time();
		$this->save();
	}

	/**
	 * Calculate the time to first reply
	 *
	 * @return string
	 */
	public function timeToFirstReply() : string
	{
		if( !$this->reply_time )
		{
			return '';
		}

		return DateTime::ts( $this->reply_time )->roundedDiff( DateTime::ts( $this->date ) );
	}

	/**
	 * Get all assignments for this member or team
	 *
	 * @param Member|Team $assigned
	 * @param array|null $limit
	 * @return array
	 */
	public static function getAssignments( Member|Team $assigned, ?array $limit=null ) : array
	{
		$where = [];
		if( $assigned instanceof Team )
		{
			$where[] = [ 'assign_type=?', static::ASSIGNMENT_TEAM ];
			$where[] = [ 'assign_to=?', $assigned->id ];
		}
		else
		{
			$where[] = [ 'assign_type=?', static::ASSIGNMENT_MEMBER ];
			$where[] = [ 'assign_to=?', $assigned->member_id ];
		}

		return iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', static::$databaseTable, $where, 'assign_date', $limit ),
				Assignment::class
			)
		);
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		if( $this->_new )
		{
			$this->by = Member::loggedIn()->member_id;
			$this->date = time();
		}

		parent::save();
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* Clear the assignment flag if the item has a column for it */
		if( $item = $this->item )
		{
			if( isset( $item::$databaseColumnMap['assignment'] ) )
			{
				$column = $item::$databaseColumnMap['assignment'];
				$item->$column = null;
				$item->save();
			}
		}

		parent::delete();
	}
}