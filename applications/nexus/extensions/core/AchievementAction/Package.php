<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Commerce
 * @since		01 Oct 2021
 */

namespace IPS\nexus\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\nexus\Package as NexusPackage;
use IPS\nexus\Package\Group;
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
class Package extends AchievementActionAbstract // NOTE: Other classes exist to provided bases for common situations, like where node-based filters will be required
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

		$return['nodes'] = new Node( 'achievement_filter_Package', ( $filters and isset( $filters['nodes'] ) and $filters['nodes'] ) ? $filters['nodes'] : 0, FALSE, [
			'url'				=> $url,
			'class'				=> 'IPS\nexus\Package\Group',
			'subnodes'			=> FALSE,
			'showAllNodes'		=> TRUE,
			'multiple' 			=> TRUE
		], NULL, Member::loggedIn()->language()->addToStack( 'achievement_filter_Package_node_prefix', FALSE, [ 'sprintf' => [
			Member::loggedIn()->language()->addToStack( 'nexus_sub_package_id', FALSE ),
			Member::loggedIn()->language()->addToStack( 'purchase_groups_lc', FALSE )
		] ] ) );

		$return['milestone'] = new Number( 'achievement_filter_Package_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_Package_nth_suffix') );
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
		if ( isset( $values['achievement_filter_Package'] ) )
		{
			$return['nodes'] = [];

			if ( is_array( $values['achievement_filter_Package'] ) )
			{
				foreach ( $values['achievement_filter_Package'] as $node )
				{
					$return['nodes'][] = $node->id;
				}
			}
		}
		if ( isset( $values['achievement_filter_Package_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_Package_nth'];
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
		if ( isset( $filters['nodes'] ) and is_array( $filters['nodes'] ) and count( $filters['nodes'] ) )
		{
			if ( !in_array( $extra->item()->container()->_id, $filters['nodes'] ) )
			{
				return FALSE;
			}
		}

		if ( isset( $filters['milestone'] ) )
		{
			if ( Db::i()->select( 'COUNT(*)', 'nexus_purchases', [ 'ps_cancelled !=1 AND ps_member=?', $subject->member_id ] )->first() < $filters['milestone'] )
			{
				return FALSE;
			}
		}
		
		return TRUE;
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
		return $extra->id . '.' . $subject->member_id;
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
		try
		{
			list( $identifier, $memberId ) = explode( '.', $identifier );
			$package = NexusPackage::load( $identifier );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $package->url(), TRUE, $package->_title, FALSE )
			] ];
		}
		catch ( OutOfRangeException )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('modcp_deleted') ] ];
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__Package_log', FALSE, $sprintf );
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
				'sprintf'		=> [ Member::loggedIn()->language()->addToStack('AchievementAction__Package_title_generic') ]
			] );
		}

		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__Package_title' ),
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
					$nodeNames[] = Group::load( $id )->_title;
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
								Member::loggedIn()->language()->addToStack( 'purchase_groups_lc', FALSE )
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
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'nexus_purchases',
			'pkey'  => 'ps_id',
			'date'  => 'ps_start',
			'where' => [ [ 'ps_cancelled != 1' ] ],
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
		Member::load( $row['ps_member'] )->achievementAction( 'nexus', 'Package', NexusPackage::load( $row['ps_item_id'] ) );
	}

}