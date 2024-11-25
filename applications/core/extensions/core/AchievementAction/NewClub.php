<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		03 Mar 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
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
class NewClub extends AchievementActionAbstract // NOTE: Other classes exist to provided bases for common situations, like where node-based filters will be required
{
	/**
	 * Can use this rule?
	 *
	 * @return boolean
	 */
	public function canUse(): bool
	{
		return parent::canUse() and Settings::i()->clubs;
	}
	
	/**
	 * Get filter form elements
	 *
	 * @param	array|NULL		$filters	Current filter values (if editing)
	 * @param	Url	$url		The URL the form is being shown on
	 * @return	array
	 */
	public function filters( ?array $filters, Url $url ): array
	{
		$nthFilter = new Number( 'achievement_filter_club_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_NewClub_nth_suffix') );
		$nthFilter->label = Member::loggedIn()->language()->addToStack('achievement_filter_NewClub_nth');

		$return = array();
		
		$return['milestone'] = $nthFilter;
		
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

		if ( isset( $values['achievement_filter_club_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_club_nth'];
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
		if ( isset( $filters['milestone'] ) )
		{
			$where = [];
			$where[] = [ 'owner=? and approved=?', $subject->member_id, 1 ];
					
			$count = Db::i()->select( 'COUNT(*)', 'core_clubs', $where )->first();
			
			if ( $count < $filters['milestone'] )
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
		return (string) $extra->id;
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
		$club = Club::load( $identifier );
		$clubLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $club->url(), TRUE, $club->name, FALSE );
			
		return Member::loggedIn()->language()->addToStack( 'AchievementAction__NewClub_log', FALSE, [ 'htmlsprintf' => [ $clubLink ] ] );
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
				'sprintf'		=> [ Member::loggedIn()->language()->addToStack('AchievementAction__NewClub_title_generic') ]
			] );
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__NewClub_title' ),
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
			'table' => 'core_clubs',
			'pkey'  => 'id',
			'date'  => 'created',
			'where' => [ [ 'approved=1' ] ],
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
		Member::load( $row['owner'] )->achievementAction( 'core', 'NewClub', Club::load( $row['id'] ) );
	}
}