<?php

/**
 * @brief        Table
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        1/15/2024
 */

namespace IPS\core\Assignments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Helpers\Table\Table as TableHelper;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Table extends TableHelper
{
	/**
	 * @brief	Title
	 */
	public string $title = 'modcp_assignments';

	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues=NULL ): array
	{
		$this->sortBy	= in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'assign_date';
		$sortBy			= $this->sortBy . ' ' . ( $this->sortDirection and strtolower( $this->sortDirection ) == 'asc' ? 'asc' : 'desc' );

		/* If we can assign content, we can see all assignments.
		Otherwise, find anything assigned to the logged in member */
		$where = [];
		if( !Assignment::canAssignOnAny() or ( isset( Request::i()->filter ) and Request::i()->filter == 'assign_mine' ) )
		{
			$clause = [
				"(assign_type=? and assign_to=?)"
			];
			$binds = [
				Assignment::ASSIGNMENT_MEMBER,
				Member::loggedIn()->member_id
			];

			if( $teams = Member::loggedIn()->teams() )
			{
				$clause[] = "(assign_type=? and " . Db::i()->in( 'assign_to', array_keys( $teams ) ) . ")";
				$binds[] = Assignment::ASSIGNMENT_TEAM;
			}

			$where = array_merge( array( implode( " OR ", $clause ) ), $binds );
		}

		if( isset( Request::i()->filter ) )
		{
			switch( Request::i()->filter )
			{
				case 'assign_mine':
					/* As we handle the main logic above, we can fall through and use the assign_open filter */
				case 'assign_open':
					$where[] = [ 'assign_closed=?', 0 ];
					break;

				case 'assign_replied':
					$where[] = [ 'assign_reply_time > ?', 0 ];
					break;

				case 'assign_closed':
					$where[] = [ 'assign_closed > ?', 0 ];
					break;
			}
		}
		else
		{
			$where[] = [ 'assign_closed=?', 0 ];
		}

		if ( $advancedSearchValues )
		{
			if ( isset( $advancedSearchValues['assign_item_class'] ) AND $advancedSearchValues['assign_item_class'] != 'all' )
			{
				$where[] = array( "assign_item_class=?", $advancedSearchValues['assign_item_class'] );
			}

			if ( !empty( $advancedSearchValues['assign_team'] ) )
			{
				$where[] = [ 'assign_type=?', Assignment::ASSIGNMENT_TEAM ];
				$where[] = [ 'assign_to=?', $advancedSearchValues['assign_team']];
			}

			if( !empty( $advancedSearchValues['assign_member'] ) )
			{
				$where[] = [ 'assign_type=?', Assignment::ASSIGNMENT_MEMBER ];
				$where[] = [ 'assign_to=?', $advancedSearchValues['assign_member']->member_id ];
			}

			if( !empty( $advancedSearchValues['assign_has_reply'] ) and $advancedSearchValues['assign_has_reply'] === 1 )
			{
				$where[] = [ 'assign_reply_id > ?', 0 ];
			}
		}

		$count = Db::i()->select( 'count(assign_id)', 'core_assignments', $where )->first();

		$this->pages = ceil( $count / $this->limit );
		$it = new ActiveRecordIterator( Db::i()->select( '*', 'core_assignments', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) ), 'IPS\core\Assignments\Assignment' );

		$return = array();
		foreach( iterator_to_array( $it ) AS $row )
		{
			$return[ $row->id ] = $row;
		}

		return $return;
	}

	/**
	 * Return the table headers
	 *
	 * @param	array|NULL	$advancedSearchValues	Advanced search values
	 * @return	array
	 */
	public function getHeaders( array $advancedSearchValues=NULL ): array
	{
		return array();
	}

	/**
	 * Multimod Actions
	 *
	 * @return	array
	 */
	public function multimodActions(): array
	{
		return array(
			'delete'
		);
	}

	/**
	 * Can Moderate
	 *
	 * @param	NULL|string	$action	Action to take
	 * @return	bool
	 */
	public function canModerate( string $action=NULL ): bool
	{
		return Assignment::canAssignOnAny();
	}
}