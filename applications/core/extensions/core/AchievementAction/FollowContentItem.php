<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		04 Mar 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\ContentAchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Content\Item;
use IPS\Db;
use IPS\Db\Select;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function get_class;
use function in_array;
use function is_array;
use function mb_strtolower;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class FollowContentItem extends ContentAchievementActionAbstract
{	
	/**
	 * Get filter form elements
	 *
	 * @param	array|NULL		$filters	Current filter values (if editing)
	 * @param	Url	$url		The URL the form is being shown on
	 * @return	array
	 */
	public function filters( ?array $filters, Url $url ): array
	{
		$filters = parent::filters( $filters, $url );

		foreach( $filters['type']->options['options'] as $class => $value )
		{
			if ( !IPS::classUsesTrait( $class, 'IPS\Content\Followable' ) )
			{
				unset( $filters['type']->options['options'][ $class ] );
				unset( $filters['nodes_' . str_replace( '\\', '-', $class ) ] );
			}
		}

		return $filters;
	}
	
	/**
	 * Work out if the filters applies for a given action
	 *
	 * Important note for milestones: consider the context. This method is called by \IPS\Member::achievementAction(). If your code 
	 * calls that BEFORE making its change in the database (or there is read/write separation), you will need to add
	 * 1 to the value being considered for milestones
	 *
	 * @param	Member	$subject	The subject member
	 * @param	array		$filters	The value returned by formatFilterValues()
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	bool
	 */
	public function filtersMatch( Member $subject, array $filters, mixed $extra = NULL ): bool
	{
		/* The parent already matches content type and node, so do that first */
		if( !parent::filtersMatch( $subject, $filters, $extra['item'] ) )
		{
			return FALSE;
		}

		if ( isset( $filters['milestone'] ) )
		{
			$query = $this->getQuery( 'COUNT(*)', [ [ 'follow_member_id=?', $subject->member_id ] ], NULL, NULL, $filters );
						
			if ( $query->first() < $filters['milestone'] )
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Get the labels for the people this action might give awards to
	 *
	 * @param	array|NULL		$filters	Current filter values
	 *
	 * @return	array
	 */
	public function awardOptions( ?array $filters ): array
	{
		return [
			'subject'	=> 'achievement_filter_FollowContentItem_follower',
			'other'		=> 'achievement_filter_FollowContentItem_author'
		];
	}

	/**
	 * Get the "other" people we need to award =stuff to
	 *
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @param	array|NULL	$filters	Current filter values
	 * @return	array
	 */
	public function awardOther( mixed $extra = NULL, ?array $filters = NULL ): array
	{
		return [ $extra['author'] ];
	}
	
	/**
	 * Get identifier to prevent the member being awarded points for the same action twice
	 * Must be unique within within of this domain, must not exceed 32 chars.
	 *
	 * @param	Member	$subject	The subject member
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	string
	 */
	public function identifier( Member $subject, mixed $extra = NULL ): string
	{
		return get_class( $extra['item'] ) . ':' . $extra['item']->{$extra['item']::$databaseColumnId} . ':' . $subject->member_id;
	}
	
	/**
	 * Return a description for this action to show in the log
	 *
	 * @param	string	$identifier	The identifier as returned by identifier()
	 * @param	array	$actor		If the member was the "subject", "other", or both
	 * @return	string
	 */
	public function logRow( string $identifier, array $actor ): string
	{
		$exploded = explode( ':', $identifier );

		$sprintf = [];
		try
		{
			/* @var Item $item */
			$item = $exploded[0]::load( $exploded[1] );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $item->url(), TRUE, $item->mapped('title') ?: $item->indefiniteArticle(), FALSE )
			] ];
		}
		catch ( OutOfRangeException $e )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('modcp_deleted') ] ];
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowContentItem_log', FALSE, $sprintf );

	}
	
	/**
	 * Get "description" for rule
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	public function ruleDescription( Rule $rule ): ?string
	{
		$type = $rule->filters['type'] ?? NULL;

		$conditions = [];
		if ( isset( $rule->filters['milestone'] ) )
		{
			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'milestone', Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone_nth', FALSE, [ 'pluralize' => [ $rule->filters['milestone'] ] ] ) )
				],
				'sprintf'		=> [ $type ? Member::loggedIn()->language()->addToStack( $type::$title, FALSE, [ 'strtolower' => TRUE ] ) : Member::loggedIn()->language()->addToStack('AchievementAction__NewContentItem_title_generic') ]
			] );
		}
		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			$type ? Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowContentItem_title_t', FALSE, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( $type::$title ) ] ] ) : Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowContentItem_title' ),
			$conditions
		);
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'core_follow',
			'pkey'  => 'follow_id',
			'date'  => 'follow_added',
			'where' => [ [ '(follow_app !=? and follow_area !=?)', 'core', 'member' ] ],
		] ];
	}

	/**
	 * Process the rebuild row
	 *
	 * @param array		$row	Row from database
	 * @param array		$data	Data collected when starting rebuild [table, pkey...]
	 * @return void
	 */
	public static function rebuildRow( array $row, array $data ) : void
	{
		$className = 'IPS\\' . $row['follow_app'] . '\\' . IPS::mb_ucfirst( $row['follow_area'] );

		if ( class_exists( $className ) AND in_array( 'IPS\Content\Item', class_parents( $className ) ) )
		{
			$item = $className::load( $row['follow_rel_id'] );
			Member::load( $row['follow_member_id'] )->achievementAction( 'core', 'FollowContentItem', [
				'item' => $item,
				'author' => $item->author()
			] );
		}
	}

	/**
	 * Get a query to use for multiple methods within this extension
	 * @param	string		$select		Select for the query
	 * @param	array|NULL	$where		Where for the query
	 * @param	int|NULL	$limit		Limit for the query
	 * @param	string|NULL	$order		Order by for the query
	 * @param	array		$filters	Rule filters
	 * @return	Select
	 */
	public function getQuery( string $select, ?array $where, ?int $limit, ?string $order, array $filters ): Select
	{
		$joinContainers		= FALSE;
		$extraJoinCondition	= NULL;
		$where				= is_array( $where ) ? $where : array();

		/* Limit by type and node */
		if ( isset( $filters['type'] ) )
		{
			[ $ns, $app, $area ] = explode( '\\', $filters['type'] );
			$where[] = [ 'follow_app=? and follow_area=?', mb_strtolower( $app ), mb_strtolower( $area ) ];

			$itemClass = $filters['type'];
			if ( in_array( 'IPS\Content\Comment', class_parents( $filters['type'] ) ) )
			{
				$itemClass = $filters['type']::$itemClass;
			}

			if ( isset( $filters['nodes_' . str_replace( '\\', '-', $itemClass )] ) )
			{
				/* @var array $databaseColumnMap */
				$joinContainers		= TRUE;
				$extraJoinCondition	= ' AND ' . Db::i()->in( $itemClass::$databaseTable . '.' . $itemClass::$databaseColumnMap['container'], $filters['nodes_' . str_replace( '\\', '-', $itemClass )] );
			}
		}
		else
		{
			$where[] = [ '(follow_app !=? and follow_area !=?)', 'core', 'member' ];
		}

		$query = Db::i()->select( $select, 'core_follow', $where, $order, $limit );

		if ( $joinContainers )
		{
			$query->join( $itemClass::$databaseTable, 'core_follow.follow_rel_id=' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnId . $extraJoinCondition, 'INNER' );
		}

		return $query;
	}
}