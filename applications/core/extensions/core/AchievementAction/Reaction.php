<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		01 Mar 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\Content\Reaction as ContentReaction;
use IPS\core\Achievements\Actions\ContentAchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Db\Select;
use IPS\Helpers\Form\Custom;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Theme;
use OutOfRangeException;
use function class_exists;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_array;
use function is_integer;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class Reaction extends ContentAchievementActionAbstract
{
	protected static bool $includeItems = FALSE;

	/**
	 * @var bool    Exclude content items that require a first comment
	 */
	protected static bool $excludeItemsWithRequiredComment = TRUE;

	/**
	 * Get filter form elements
	 *
	 * @param	array|NULL		$filters	Current filter values (if editing)
	 * @param	Url	$url		The URL the form is being shown on
	 * @return	array
	 */
	public function filters( ?array $filters, Url $url ): array
	{
		$return = parent::filters( $filters, $url );
		
		$reactionFilter = new Node( 'achievement_filter_Reaction_reaction', ( $filters and isset( $filters['reactions'] ) and $filters['reactions'] ) ? $filters['reactions'] : 0, FALSE, [
			'url'				=> $url,
			'class'				=> 'IPS\Content\Reaction',
			'showAllNodes'		=> TRUE,
			'multiple' 			=> TRUE,
		], NULL, Member::loggedIn()->language()->addToStack( 'achievement_subfilter_Reaction_reaction_prefix' ) );
		$return['reactions'] = $reactionFilter;

		$return['milestone'] = new Custom( 'achievement_filter_Reaction_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : [], FALSE, array( 'getHtml' => function( $element )
		{
			/* 4.6.0 - 4.6.3 */
			if ( is_integer( $element->value ) )
			{
				$element->value = ['receiver', $element->value];
			}
			return Theme::i()->getTemplate( 'achievements' )->milestoneWithSubjectSwitch( $element->name, $element->value );
		} ), NULL, NULL, NULL, 'achievement_filter_Reaction_nth' );

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
		$return = parent::formatFilterValues( $values );
		if ( isset( $values['achievement_filter_Reaction_reaction'] ) )
		{
			$return['reactions'] = array_keys( $values['achievement_filter_Reaction_reaction'] );
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
		if ( !parent::filtersMatch( $subject, $filters, $extra['content'] ) )
		{
			return FALSE;
		}

		if ( isset( $filters['reactions'] ) )
		{
			if ( !in_array( $extra['reaction']->id, $filters['reactions'] ) )
			{
				return FALSE;
			}
		}

		if ( isset( $filters['milestone'] ) )
		{
			if ( is_array( $filters['milestone'] ) )
			{
				$field = ( $filters['milestone'][0] == 'giver' ) ? 'member_id' : 'member_received';
				$member = ( $filters['milestone'][0] == 'giver' ) ? $extra['giver'] : $subject;
				$milestone = $filters['milestone'][1];
			}
			else
			{
				/* 4.6.0 - 4.6.3 just had a milestone for member_received */
				$field = 'member_received';
				$milestone = $filters['milestone'];
				$member = $subject;
			}

			$query = $this->getQuery( 'COUNT(*)', [ [ $field . '=?', $member->member_id ] ], NULL, $filters );

			if ( ( $query->first() + 1 ) < $milestone )
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
		return get_class( $extra['content'] ) . ':' . $extra['content']->{$extra['content']::$databaseColumnId} . ':' . $extra['giver']->member_id;
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
			'subject' => 'achievement_filter_Reaction_receiver',
			'other'   => 'achievement_filter_Reaction_giver'
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
		return [ $extra['giver'] ];
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
				
		$reactionName = Member::loggedIn()->language()->addToStack('unknown');
		$contentLink = Member::loggedIn()->language()->addToStack('modcp_deleted');
		try
		{
			$class = $exploded[0];
			$content = $class::load( $exploded[1] );
			$contentLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $content->url(), TRUE, $content->indefiniteArticle(), FALSE );
			
			$reaction = $content->reacted( Member::load( $exploded[2] ) );
			$reactionName = $reaction->_title;
		}
		catch ( Exception $e ) {  }
		
		if ( in_array( 'subject', $actor ) )
		{
			return Member::loggedIn()->language()->addToStack( 'AchievementAction__Reaction_log_subject', FALSE, [ 'sprintf' => [ $reactionName ], 'htmlsprintf' => [ $contentLink ] ] );
		}
		else
		{
			return Member::loggedIn()->language()->addToStack( 'AchievementAction__Reaction_log_other', FALSE, [ 'sprintf' => [ $reactionName ], 'htmlsprintf' => [ $contentLink ] ] );
		}
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
			if ( is_integer( $rule->filters['milestone'] ) )
			{
				/* 4.6.0 - 4.6.3 */
				$milestone = $rule->filters['milestone'];
			}
			else
			{
				$milestone = $rule->filters['milestone'][1];
			}

			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'milestone', Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone_nth', FALSE, [ 'pluralize' => [ $milestone ] ] ) )
				],
				'sprintf' => Member::loggedIn()->language()->addToStack('AchievementAction__Reaction_title_generic')
			] );
		}
		if ( isset( $rule->filters['reactions'] ) )
		{
			$reactionNames = [];
			foreach ( $rule->filters['reactions'] as $id )
			{
				try
				{
					$reactionNames[] = ContentReaction::load( $id )->_title;
				}
				catch ( OutOfRangeException $e ) {}
			}
			if ( $reactionNames )
			{
				$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_type', FALSE, [
					'htmlsprintf' => [
						Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'other',
							count( $reactionNames ) === 1 ? $reactionNames[0] : Member::loggedIn()->language()->addToStack( 'AchievementAction__Reaction_types', FALSE, [ 'sprintf' => [
								count( $reactionNames ),
							] ] ),
							count( $reactionNames ) === 1 ? NULL : $reactionNames
						)
					],
				] );
			}
		}
		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}
		
		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			( ( isset( $rule->filters['milestone'] ) and ( ( is_array( $rule->filters['milestone'] ) and $rule->filters['milestone'][0] == 'giver' ) or is_numeric( $rule->filters['milestone'] ) ) ) or ! isset( $rule->filters['milestone']) )
			? Member::loggedIn()->language()->addToStack( 'AchievementAction__Reaction_title' )
			: Member::loggedIn()->language()->addToStack( 'AchievementAction__Reaction_title_received' ),
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
		$reaction = ContentReaction::load( $row['reaction'] );

		/* Give points */
		static::getAuthor( $object )->achievementAction( 'core', 'Reaction', [
			'giver'		=> Member::load( $row['member_id'] ),
			'content'	=> $object,
			'reaction'	=> $reaction
		] );
	}

	/**
	 * Get the author of the content / node
	 * 
	 * @param $object
	 * @return Member|null
	 */
	public static function getAuthor( $object ): ?Member
	{
		$owner = NULL;

		/* Figure out the owner of this - if it is content, it will be the author. If it is a node, then it will be the person who created it */
		if ( $object instanceof Content )
		{
			$owner = $object->author();
		}
		else if ( $object instanceof Model )
		{
			$owner = $object->owner();
		}

		return $owner;
	}

	/**
	 * Get a query to use for multiple methods within this extension
	 * @param	string		$select		Select for the query
	 * @param	array|NULL	$where		Where for the query
	 * @param	int|NULL	$limit		Limit for the query
	 * @param	array		$filters	Rule filters
	 * @return	Select
	 */
	public function getQuery( string $select, ?array $where, ?int $limit, array $filters ): Select
	{
		$joinContainers		= FALSE;
		$extraJoinCondition	= NULL;
		$where				= is_array( $where ) ? $where : array();

		/* Limit by type and node */
		if ( isset( $filters['type'] ) )
		{
			$where[] = ['rep_class=?', $filters['type']];
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

		/* Limit by reaction type */
		if ( isset( $filters['reactions'] ) )
		{
			$where[] = [ Db::i()->in( 'reaction', $filters['reactions'] ) ];
		}

		$query = Db::i()->select( $select, 'core_reputation_index', $where, NULL, $limit );

		if ( $joinContainers )
		{
			$query->join( $itemClass::$databaseTable, 'core_reputation_index.item_id=' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnId . $extraJoinCondition, 'INNER' );
		}

		return $query;
	}
}