<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		07 Mar 2024
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\core\Achievements\Actions\ContentAchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class Highlight extends ContentAchievementActionAbstract // NOTE: Other classes exist to provided bases for common situations, like where node-based filters will be required
{
	protected static bool $includeItems = FALSE;

	/**
	 * @var bool    Exclude content items that require a first comment
	 */
	protected static bool $excludeItemsWithRequiredComment = TRUE;

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
		if ( !parent::filtersMatch( $subject, $filters, $extra['content'] ) )
		{
			return FALSE;
		}

		if( $extra['content']->isHighlighted() )
		{
			if( isset( $filters['milestone'] ) )
			{
				$joinContainers = false;
				$where = [ [ 'member_received=?', $subject->member_id ] ];

				$itemClass = $filters['type'];
				if ( in_array( 'IPS\Content\Comment', class_parents( $filters['type'] ) ) )
				{
					$itemClass = $filters['type']::$itemClass;
				}

				/* Limit by type and node */
				if ( isset( $filters['type'] ) )
				{
					$where[] = [ 'rep_class=?', $filters['type'] ];
				}

				$query = Db::i()->select( 'rep_class, item_id, SUM(rep_rating)', 'core_reputation_index', $where, NULL, null, [ 'rep_class', 'item_id' ], [ 'SUM(rep_rating) > ?', Settings::i()->reputation_highlight ] );

				if ( isset( $filters['nodes_' . str_replace( '\\', '-', $itemClass )] ) )
				{
					/* @var array $databaseColumnMap */
					$query->join( $itemClass::$databaseTable, 'core_reputation_index.item_id=' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnId . ' AND ' . Db::i()->in( $itemClass::$databaseTable . '.' . $itemClass::$databaseColumnMap['container'], $filters['nodes_' . str_replace( '\\', '-', $itemClass )] ), 'INNER' );
				}

				if( $query->count() + 1 < $filters['milestone'] )
				{
					return false;
				}
			}

			return true;
		}

		return false;
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
		return get_class( $extra['content'] ) . ':' . $extra['content']->{$extra['content']::$databaseColumnId};
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
			/* @var Content $class */
			$class = $exploded[0];
			$content = $class::load( $exploded[1] );
			$contentLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $content->url(), TRUE, $content->indefiniteArticle(), FALSE );
		}
		catch ( Exception $e )
		{
			$contentLink = Member::loggedIn()->language()->addToStack('modcp_deleted');
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__Highlight_log_subject', false, [ 'htmlsprintf' => [ $contentLink ] ] );
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
				'sprintf' => Member::loggedIn()->language()->addToStack('AchievementAction__Highlight_title')
			] );
		}

		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription( Member::loggedIn()->language()->addToStack( 'AchievementAction__Highlight_title' ), $conditions );
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'core_reputation_index',
			'pkey'  => 'id',
			'date'  => 'rep_date',
			'where' => [],
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
		if ( !$row['rep_class'] OR !class_exists( $row['rep_class'] ) )
		{
			/* Class either isn't set or doesn't exist, so move on. */
			return;
		}

		$object = $row['rep_class']::load( $row['type_id'] );

		/* Give points */
		$object->author()->achievementAction( 'core', 'Highlight', [ 'content' => $object ] );
	}
}