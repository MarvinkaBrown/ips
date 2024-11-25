<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Calendar
 * @since		17 Mar 2021
 */

namespace IPS\calendar\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Calendar;
use IPS\calendar\Event;
use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Db\Select;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class Rsvp extends AchievementActionAbstract // NOTE: Other classes exist to provided bases for common situations, like where node-based filters will be required
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
		$return	= array();

		$return['nodes'] = new Node( 'achievement_filter_Rsvp_nodes', ( $filters and isset( $filters['nodes'] ) and $filters['nodes'] ) ? $filters['nodes'] : 0, FALSE, [
			'url'				=> $url,
			'class'				=> 'IPS\calendar\Calendar',
			'showAllNodes'		=> TRUE,
			'multiple' 			=> TRUE,
		], NULL, Member::loggedIn()->language()->addToStack( 'achievement_filter_NewContentItem_node_prefix', FALSE, [ 'sprintf' => [
			Member::loggedIn()->language()->addToStack( 'rsvp', FALSE ),
			Member::loggedIn()->language()->addToStack( 'calendars', FALSE, [ 'strtolower' => TRUE ] )
		] ] ) );
		$return['nodes']->label = Member::loggedIn()->language()->addToStack( 'achievement_filter_NewContentItem_node', FALSE, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( 'calendars', FALSE, [ 'strtolower' => TRUE ] ) ] ] );

		$return['milestone'] = new Number( 'achievement_filter_Rsvp_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_Rsvp_nth_suffix') );
		$return['milestone']->label = Member::loggedIn()->language()->addToStack('achievement_filter_NewContentItem_nth');
		
		return $return;
	}
	
	/**
	 * Format filter form values
	 *
	 * @param	array	$values	The values from the form
	 * @return	array
	 */
	public function formatFilterValues( array $values ): array
	{
		$return = [];
		if ( isset( $values['achievement_filter_Rsvp_nodes'] ) )
		{			
			$return['nodes'] = array_keys( $values['achievement_filter_Rsvp_nodes'] );
		}
		if ( isset( $values['achievement_filter_Rsvp_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_Rsvp_nth'];
		}
		return $return;
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
	 * @param mixed|null $extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	bool
	 */
	public function filtersMatch( Member $subject, array $filters, mixed $extra = NULL ): bool
	{
		if ( isset( $filters['nodes'] ) )
		{
			if ( !in_array( $extra->container()->_id, $filters['nodes'] ) )
			{
				return FALSE;
			}
		}

		if ( isset( $filters['milestone'] ) )
		{
			$query = $this->getQuery( 'COUNT(*)', [ [ 'rsvp_member_id=?', $subject->member_id ] ], NULL, NULL, $filters );
						
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
			'subject'	=> 'achievement_filter_Rsvp_receiver',
			'other'		=> 'achievement_filter_Rsvp_giver'
		];
	}

	/**
	 * Get the "other" people we need to award =stuff to
	 *
	 * @param mixed|null $extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @param	array|NULL	$filters	Current filter values
	 * @return	array
	 */
	public function awardOther( mixed $extra = NULL, ?array $filters = NULL ): array
	{
		return [ $extra->author() ];
	}
	
	/**
	 * Get identifier to prevent the member being awarded points for the same action twice
	 * Must be unique within within of this domain, must not exceed 32 chars.
	 *
	 * @param	Member	$subject	The subject member
	 * @param mixed|null $extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	string
	 */
	public function identifier( Member $subject, mixed $extra = NULL ): string
	{
		return 'Rsvp:' . $extra->id . ':' . $subject->member_id;
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

		try
		{
			$item = Event::load( $exploded[1] );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $item->url(), TRUE, $item->mapped('title') ?: $item->indefiniteArticle(), FALSE )
			] ];
		}
		catch ( OutOfRangeException $e )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('modcp_deleted') ] ];
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__Rsvp_log', FALSE, $sprintf );
	}
	
	/**
	 * Get "description" for rule
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	public function ruleDescription( Rule $rule ): ?string
	{
		$conditions = [];
		if ( isset( $rule->filters['milestone'] ) )
		{
			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'milestone', Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone_nth', FALSE, [ 'pluralize' => [ $rule->filters['milestone'] ] ] ) )
				],
				'sprintf'		=> [ Member::loggedIn()->language()->addToStack('rsvp') ]
			] );
		}
		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__Rsvp_title' ),
			$conditions
		);
	}

	/**
	 * Get "description" for rule (usually a description of the rule's filters)
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	protected function _nodeFilterDescription( Rule $rule ): ?string
	{
		if ( isset( $rule->filters['nodes'] ) )
		{
			$nodeNames = [];
			foreach ( $rule->filters['nodes'] as $id )
			{
				try
				{
					$nodeNames[] = Calendar::load( $id )->_title;
				}
				catch ( OutOfRangeException $e ) {}
			}
			if ( $nodeNames )
			{
				return Member::loggedIn()->language()->addToStack( 'achievements_title_filter_location', FALSE, [
					'htmlsprintf' => [
						Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'location',
							count( $nodeNames ) === 1 ? $nodeNames[0] : Member::loggedIn()->language()->addToStack( 'achievements_title_filter_location_val', FALSE, [ 'sprintf' => [
								count( $nodeNames ),
								Member::loggedIn()->language()->addToStack( 'calendars', FALSE, [ 'strtolower' => TRUE ] )
							] ] ),
							count( $nodeNames ) === 1 ? NULL : $nodeNames
						)
					],
				] );
			}
		}

		return NULL;
	}

	/**
	 * Get a query to use for multiple methods within this extension
	 * @param string $select		Select for the query
	 * @param array|null $where		Where for the query
	 * @param int|null $limit		Limit for the query
	 * @param string|null $order		Order by for the query
	 * @param array $filters	Rule filters
	 * @return	Select
	 */
	public function getQuery( string $select, ?array $where, ?int $limit, ?string $order, array $filters ): Select
	{
		$joinContainers		= FALSE;
		$extraJoinCondition	= NULL;
		$where				= is_array( $where ) ? $where : array();

		/* Limit by node */
		if ( isset( $filters['nodes'] ) )
		{
			$joinContainers		= TRUE;
			$extraJoinCondition	= ' AND ' . Db::i()->in( 'calendar_events.event_calendar_id', $filters['nodes'] );
		}

		$query = Db::i()->select( $select, 'calendar_event_rsvp', $where, $order, $limit );

		if ( $joinContainers )
		{
			$query->join( 'calendar_events', 'calendar_event_rsvp.rsvp_event_id=calendar_events.event_id' . $extraJoinCondition, 'INNER' );
		}

		return $query;
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'calendar_event_rsvp',
			'pkey'  => 'rsvp_id',
			'where' => [ [ 'status=?', Event::RSVP_YES ] ],
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
		$event = Event::load( $row['rsvp_event_id'] );
		$member = Member::load( $row['rsvp_member_id'] );
		$member->achievementAction( 'calendar', 'Rsvp', $event );
	}
}