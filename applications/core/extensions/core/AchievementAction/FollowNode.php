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

use IPS\core\Achievements\Actions\NodeAchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class FollowNode extends NodeAchievementActionAbstract
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
			if ( isset( $class::$contentItemClass ) and  !IPS::classUsesTrait( $class::$contentItemClass, 'IPS\Content\Followable' ) )
			{
				unset( $filters['type']->options['options'][ $class ] );
				unset( $filters['nodes_' . str_replace( '\\', '-', $class ) ] );
			}
		}

		return $filters;
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
			$node = $exploded[0]::load( $exploded[1] );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $node->url(), TRUE, $node->_title, FALSE )
			] ];
		}
		catch ( OutOfRangeException $e )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('modcp_deleted') ] ];
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowNode_log', FALSE, $sprintf );
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
				'sprintf'		=> [ $type ? Member::loggedIn()->language()->addToStack( $type::fullyQualifiedType(), FALSE, [ 'strtolower' => TRUE ] ) : Member::loggedIn()->language()->addToStack('AchievementAction__NewContentItem_title_generic') ]
			] );
		}
		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			$type ? Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowNode_title_t', FALSE, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( $type::fullyQualifiedType() ) ] ] ) : Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowNode_title' ),
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
		$class = 'IPS\\' . $row['follow_app'] . '\\' . IPS::mb_ucfirst( $row['follow_area'] );
		if ( class_exists( $class ) AND in_array( 'IPS\Node\Model', class_parents( $class ) ) )
		{
			Member::load( $row['follow_member_id'] )->achievementAction( 'core', 'FollowNode', $class::load( $row['follow_rel_id'] ) );
		}
	}
}