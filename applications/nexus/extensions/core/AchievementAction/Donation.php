<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Commerce
 * @since		16 Mar 2023
 */

namespace IPS\nexus\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Db\Select;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Donation\Goal;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class Donation extends AchievementActionAbstract // NOTE: Other classes exist to provided bases for common situations, like where node-based filters will be required
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

		$return['nodes'] = new Node( 'achievement_filter_Donation_nodes', ( $filters and isset( $filters['nodes'] ) and $filters['nodes'] ) ? $filters['nodes'] : 0, FALSE, [
			'url'				=> $url,
			'class'				=> 'IPS\nexus\Donation\Goal',
			'showAllNodes'		=> TRUE,
			'multiple' 			=> TRUE,
		], NULL, Member::loggedIn()->language()->addToStack( 'achievement_filter_NewContentItem_node_prefix', FALSE, [ 'sprintf' => [
			Member::loggedIn()->language()->addToStack( 'donation_lc', FALSE ),
			Member::loggedIn()->language()->addToStack( 'goals_sg', FALSE, [ 'strtolower' => TRUE ] )
		] ] ) );
		$return['nodes']->label = Member::loggedIn()->language()->addToStack( 'achievement_filter_NewContentItem_node', FALSE, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( 'goals_sg', FALSE, [ 'strtolower' => TRUE ] ) ] ] );

		$return['milestone'] = new Number( 'achievement_filter_Donation_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_Donation_nth_suffix') );
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
		if ( isset( $values['achievement_filter_Donation_nodes'] ) )
		{
			$return['nodes'] = array_keys( $values['achievement_filter_Donation_nodes'] );
		}
		if ( isset( $values['achievement_filter_Donation_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_Donation_nth'];
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
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	bool
	 */
	public function filtersMatch( Member $subject, array $filters, mixed $extra = NULL ): bool
	{
		if ( isset( $filters['nodes'] ) )
		{
			if ( !in_array( $extra->_id, $filters['nodes'] ) )
			{
				return FALSE;
			}
		}

		if ( isset( $filters['milestone'] ) )
		{
			$query = $this->getQuery( 'COUNT(*)', [ [ 'dl_member=?', $subject->member_id ] ], NULL, NULL, $filters );

			if ( $query->first() + 1 < $filters['milestone'] )
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
			'subject'	=> 'achievement_filter_Donation_donor',
		];
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
		return 'Donation:' . $extra->id . ':' . $subject->member_id;
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
			$goal = Goal::load( $exploded[1] );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $goal->url(), TRUE, '@todo goal title', FALSE )
			] ];
		}
		catch ( OutOfRangeException )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('modcp_deleted') ] ];
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__Donation_log', FALSE, $sprintf );
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
				'sprintf'		=> [ Member::loggedIn()->language()->addToStack('donation_lc') ]
			] );
		}
		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__Donation_title' ),
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
					$nodeNames[] = Goal::load( $id )->_title; //@todo
				}
				catch ( OutOfRangeException ) {}
			}
			if ( $nodeNames )
			{
				return Member::loggedIn()->language()->addToStack( 'achievements_title_filter_location', FALSE, [
					'htmlsprintf' => [
						Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'location',
							count( $nodeNames ) === 1 ? $nodeNames[0] : Member::loggedIn()->language()->addToStack( 'achievements_title_filter_location_val', FALSE, [ 'sprintf' => [
								count( $nodeNames ),
								Member::loggedIn()->language()->addToStack( 'goals', FALSE, [ 'strtolower' => TRUE ] ) //@todo
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

		/* Limit by node */
		if ( isset( $filters['nodes'] ) )
		{
			$joinContainers		= TRUE;
			$extraJoinCondition	= ' AND ' . Db::i()->in( 'nexus_donate_logs.dl_goal', $filters['nodes'] );
		}

		$query = Db::i()->select( $select, 'nexus_donate_logs', $where, $order, $limit );

		if ( $joinContainers )
		{
			$query->join( 'nexus_donate_goals', 'nexus_donate_logs.dl_goal=nexus_donate_goals.d_id' . $extraJoinCondition, 'INNER' );
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
			'table' => 'nexus_donate_logs',
			'pkey'  => 'dl_id'
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
		Member::load( $row['dl_member'] )->achievementAction( 'nexus', 'Donation', [ Goal::load( $row['dl_goal'] ) ] );
	}
}