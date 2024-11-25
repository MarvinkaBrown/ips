<?php

/**
 * @brief        Assignable
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        1/8/2024
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\core\Assignments\Assignment;
use IPS\Db;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Form\Text;
use IPS\Member;
use IPS\Member\Team;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Platform\Bridge;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function is_string;
use function strpos;
use function substr;
use function trim;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

trait Assignable
{
	/**
	 * Container has assignable enabled
	 *
	 * @return	bool
	 */
	abstract public function containerAllowsAssignable(): bool;

	/**
	 * @return Assignment|null
	 */
	public function get_assignment() : ?Assignment
	{
		/* Do we have a column? */
		if( $this->isAssigned() )
		{
			if( isset( static::$databaseColumnMap['assigned'] ) )
			{
				$column = static::$databaseColumnMap['assigned'];
				try
				{
					return Assignment::load( $this->$column );
				}
				catch( OutOfRangeException ){}
			}
			else
			{
				try
				{
					return Assignment::loadByItem( $this );
				}
				catch( OutOfRangeException ){}
			}
		}

		return null;
	}

	/**
	 * Can user assign this item?
	 *
	 * @param Member|null $member The member (null for currently logged in member)
	 * @return    bool
	 */
	public function canAssign( ?Member $member = NULL ): bool
	{
		if( !Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			return false;
		}

		return $this->containerAllowsAssignable() AND static::modPermission( 'assign', $member, $this->container() );
	}

	/**
	 * Can this member be assigned to this item?
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canBeAssigned( Member $member ) : bool
	{
		if( !Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			return false;
		}

		/* The user must have permission to reply to this item */
		if( $this->containerAllowsAssignable() and $this->canComment( $member, false ) )
		{
			/* Make sure the member is a moderator */
			$moderators = static::getModerators( $this->containerWrapper( true ) );
			if( in_array( $member->member_id, $moderators['m'] ) )
			{
				return true;
			}

			return $member->inGroup( $moderators['g'] );
		}

		return false;
	}

	/**
	 * Get current assignment
	 *
	 * @return Member|Team|null
	 */
	public function assignedTo() : Member|Team|null
	{
		try
		{
			return Assignment::loadByItem( $this )->assignedTo();
		}
		catch( OutOfRangeException )
		{
			return null;
		}
	}

	/**
	 * @var array
	 */
	protected static array $_memberAssignments = [];

	/**
	 * Get all items assigned to this member
	 *
	 * @param Member|null $member
	 * @return array
	 */
	protected static function memberAssignments( ?Member $member=null ) : array
	{
		$member = $member ?: Member::loggedIn();
		if( ! isset( static::$_memberAssignments[ $member->member_id ] ) )
		{
			$teamIds = $member->teams() ? array_keys( $member->teams() ): [];
			static::$_memberAssignments[ $member->member_id ] = iterator_to_array(
				new ActiveRecordIterator(
					Db::i()->select( '*', 'core_assignments', [ 'assign_item_class=? and ( ( assign_type=? and assign_to=? ) or ( assign_type=? and assign_to in(?) ) )', get_called_class(), Assignment::ASSIGNMENT_MEMBER, $member->member_id, Assignment::ASSIGNMENT_TEAM, implode( ",", $teamIds ) ] ),
					Assignment::class
				)
			);
		}

		return is_array( static::$_memberAssignments[ $member->member_id ] ) ? static::$_memberAssignments[ $member->member_id ] : [];
	}

	/**
	 * Determines if the item is assigned to this member
	 *
	 * @param Member|null $member
	 * @param bool 	$checkClosed
	 * @return bool
	 */
	public function isAssignedToMember( ?Member $member = null, bool $checkClosed=false ) : bool
	{
		$member = $member ?: Member::loggedIn();

		$idColumn = static::$databaseColumnId;
		foreach( static::memberAssignments( $member ) as $assignment )
		{
			if( $assignment->item_id == $this->$idColumn )
			{
				if( !$checkClosed and $assignment->closed )
				{
					return false;
				}

				return true;
			}
		}

		/* If it wasn't in the member assignment list, we're not assigned */
		return false;
	}

	/**
	 * Does this item have an assignment?
	 *
	 * @return bool
	 */
	public function isAssigned() : bool
	{
		if( !Bridge::i()->featureIsEnabled( 'assignments' ) )
		{
			return false;
		}

		if( isset( static::$databaseColumnMap['assigned'] ) )
		{
			$column = static::$databaseColumnMap['assigned'];
			return $this->$column !== null;
		}

		try
		{
			Assignment::loadByItem( $this );
			return true;
		}
		catch( OutOfRangeException )
		{
			return false;
		}
	}

	/**
	 * Assign this item to a member or team
	 *
	 * @param Member|Team|string $assignTo
	 * @return Member|Team|bool		FALSE if the assignment did not change
	 */
	public function assign( Member|Team|string $assignTo ) : Member|Team|bool
	{
		try
		{
			/* If we already have an assignment we'll just update it */
			$assignment = Assignment::loadByItem( $this );
		}
		catch( OutOfRangeException )
		{
			$assignment = new Assignment;
			$assignment->item = $this;
		}

		if( is_string( $assignTo ) )
		{
			if( $pos = strpos( $assignTo, ':' ) )
			{
				try
				{
					$assignTo = Team::load( trim( substr( $assignTo, $pos + 1 ) ), 'team_name' );
				}
				catch( OutOfRangeException )
				{
					return false;
				}
			}
			else
			{
				$assignTo = Member::load( $assignTo, 'name' );
				if( !$assignTo->member_id )
				{
					return false;
				}
			}
		}

		/* If the assignment did not change, stop here */
		if( !$assignment->closed AND $assignTo === $assignment->assignedTo() )
		{
			return false;
		}

		/* Reopen the assignment */
		$assignment->closed = 0;
		$assignment->to = $assignTo;
		$assignment->save();

		/* If the item has a column, set the value */
		if( isset( static::$databaseColumnMap['assigned'] ) )
		{
			$column = static::$databaseColumnMap['assigned'];
			$this->$column = $assignment->id;
			$this->save();
		}

		return $assignTo;
	}

	/**
	 * Remove the assignment from this item
	 *
	 * @return void
	 */
	public function unassign() : void
	{
		try
		{
			Assignment::loadByItem( $this )->close();
		}
		catch( OutOfRangeException ){}
	}

	/**
	 * Log the reply for this item so that we can generate reports
	 *
	 * @param Comment $reply
	 * @return void
	 */
	public function trackReply( Comment $reply ) : void
	{
		try
		{
			Assignment::loadByItem( $this )->logReply( $reply );
		}
		catch( OutOfRangeException ){}
	}

	/**
	 * Get a list of all the moderators
	 *
	 * @param Model|null $container
	 * @return array
	 */
	protected static function getModerators( ?Model $container=null ) : array
	{
		if( $container !== null )
		{
			return $container::moderators( $container );
		}

		return [
			'g' => iterator_to_array( Db::i()->select( 'id', 'core_moderators', [ 'perms=?', '*' ] ) ),
			'm' => []
		];
	}

	/**
	 * Generate a field for the assignment options
	 *
	 * @param Item $item
	 * @param Model|null $container
	 * @param string|null	$fieldName	Optional override for the field name
	 * @return FormAbstract
	 */
	public static function assignmentFormField( Item $item, ?Model $container=null, ?string $fieldName=null ) : FormAbstract
	{
		/* Preload some language strings */
		Member::loggedIn()->language()->get( ['assignment_team', 'assign_to'] );

		$prefix = Member::loggedIn()->language()->get( 'assignment_team' );
		$currentValue = null;
		if( $currentAssignment = $item->assignment )
		{
			if( !$currentAssignment->closed and $currentValue = $item->assignedTo() )
			{
				if( $currentValue instanceof Team )
				{
					$currentValue = $prefix . ': ' . $currentValue->name;
				}
				else
				{
					$currentValue = $currentValue->name;
				}
			}
		}

		$options = [
			'autocomplete' => [
				'minimized' => false,
				'maxItems' => 1,
				'forceLower' => false,
				'minAjaxLength' => 1,
				'addTokenText' => Member::loggedIn()->language()->get( 'assign_to' ),
				'addTokenTemplate' => 'core.autocomplete.assignmentAddToken'
			]
		];

		/* Do we have a very large list of options? */
		$containerModerators = static::getModerators( $container );

		$total = (int) Db::i()->select( 'count(member_id)', 'core_members', ( count( $containerModerators['m'] ) ? Db::i()->in( 'member_id', $containerModerators['m'] ) . ' OR ' : '' ) . Db::i()->in( 'member_group_id', $containerModerators['g'] ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $containerModerators['g'] ) )->first();
		$total += count( Team::teams() );

		if( $total >= 100 )
		{
			$source = 'app=cloud&module=assignments&controller=assignments&do=findAssignmentOptions&class=' . get_called_class();

			if( $container )
			{
				$source .= '&container=' . $container->_id;
			}

			$options['autocomplete']['freeChoice'] = true;
			$options['autocomplete']['resultItemTemplate'] = 'core.autocomplete.assignmentResultItem';
		}
		else
		{
			$source = [];
			foreach( Db::i()->select( '*', 'core_members', ( count( $containerModerators['m'] ) ? Db::i()->in( 'member_id', $containerModerators['m'] ) . ' OR ' : '' ) . Db::i()->in( 'member_group_id', $containerModerators['g'] ) . ' OR ' . Db::i()->findInSet( 'mgroup_others', $containerModerators['g'] ) ) as $mem )
			{
				$member = Member::constructFromData( $mem );
				if( $container !== null and $container->can( 'reply', $member, false ) )
				{
					$source[ $member->name ] = Theme::i()->getTemplate( 'global', 'core', 'front' )->memberAssignment( $member );
				}
			}

			/* Now teams */
			foreach( Team::teams() as $team )
			{
				$source[ $prefix . ': ' . $team->name ] = Theme::i()->getTemplate( 'global', 'core', 'front' )->teamAssignment( $team );
			}

			$options['autocomplete']['formatSource'] = true;
			$options['autocomplete']['freeChoice'] = false;
			$options['autocomplete']['resultItemTemplate'] = 'core.autocomplete.resultItem';
		}

		$options['autocomplete']['source'] = $source;
		$options['autocomplete']['suggestionsOnly'] = true;

		return new Text( $fieldName ?? 'assign_content_to', $currentValue, false, $options, function( $val ) use ( $item ){
			if( !empty( $val ) )
			{
				/* This is a team */
				if( $pos = strpos( $val, ':' ) )
				{
					return true;
				}

				$member = Member::load( $val, 'name' );
				if( !$item->canBeAssigned( $member ) )
				{
					throw new DomainException( 'form_err_bad_assignment' );
				}
			}

			return true;
		} );
	}
}