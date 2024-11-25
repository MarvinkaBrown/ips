<?php
/**
 * @brief		Achievement Action Extension: Member visits the community, and has not previously done so that day
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		24 Feb 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DateTimeZone;
use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form\Interval;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension: Member visits the community, and has not previously done so that day
 */
class SessionStartDaily extends AchievementActionAbstract
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
		$return['milestone_registered'] = new Interval( 'achievement_filter_SessionStartDaily', ( $filters and isset( $filters['milestone_registered'] ) and $filters['milestone_registered'] ) ? $filters['milestone_registered'] : 0, FALSE, [ 'valueAs' => Interval::DAYS, 'min' => NULL ], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_SessionStartDaily_prefix') );
		$return['milestone_concurrent'] = new Number( 'achievement_filter_SessionStartDaily_concurrent', ( $filters and isset( $filters['milestone_concurrent'] ) and $filters['milestone_concurrent'] ) ? $filters['milestone_concurrent'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_SessionStartDaily_concurrent_prefix'), Member::loggedIn()->language()->addToStack('achievement_filter_SessionStartDaily_concurrent_suffix') );

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
		if ( isset( $values['achievement_filter_SessionStartDaily'] ) )
		{
			$return['milestone_registered'] = $values['achievement_filter_SessionStartDaily'];
		}
		if ( isset( $values['achievement_filter_SessionStartDaily_concurrent'] ) )
		{
			$return['milestone_concurrent'] = $values['achievement_filter_SessionStartDaily_concurrent'];
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
		if ( isset( $filters['milestone_registered'] ) )
		{
			if ( $subject->joined->getTimestamp() > ( time() - ( 86400 * $filters['milestone_registered'] ) ) )
			{
				return FALSE;
			}
		}

		if ( isset( $filters['milestone_concurrent'] ) )
		{
			/* Get the count of all logins since milestone ago. If it matches the count with milestone, then we have one for each day */
			if ( $subject->timezone )
			{
				$date = ( new \DateTime( 'now', new DateTimeZone( $subject->timezone ) ) )->sub( new DateInterval( 'P' . $filters['milestone_concurrent'] . 'D' ) )->format( 'Y-m-d' );
			}
			else
			{
				$date = ( new \DateTime( 'now' ) )->sub( new DateInterval( 'P' . $filters['milestone_concurrent'] . 'D' ) )->format( 'Y-m-d' );
			}
			if ( $filters['milestone_concurrent'] > (int) Db::i()->select( 'COUNT(*)', 'core_members_logins', [ 'member_id=? and member_date >=?', $subject->member_id, $date ] )->first() )
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
		if ( $subject->timezone )
		{
			return $subject->member_id . '.' . ( new \DateTime( 'now', new DateTimeZone( $subject->timezone ) ) )->format( 'Y-m-d' ); // User's own timezone: Jordan's idea
		}
		else
		{
			return $subject->member_id . '.' . ( new \DateTime( 'now' ) )->format( 'Y-m-d' );
		}
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
		list( $id, $time ) = explode( '.', $identifier );
		return Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_log', FALSE, array( 'sprintf' => ( new DateTime( $time ) )->dayAndMonth() ) );
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
		if ( isset( $rule->filters['milestone_registered'] ) )
		{
			$conditions[] = Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'milestone_registered', Member::loggedIn()->language()->addToStack( 'f_days', FALSE, [ 'pluralize' => [ $rule->filters['milestone_registered'] ] ] ) )
				],
			] );
		}
		elseif ( isset( $rule->filters['milestone_concurrent'] ) )
		{
			$conditions[] = Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_concurrent_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'milestone', Member::loggedIn()->language()->addToStack( 'f_days', FALSE, [ 'pluralize' => [ $rule->filters['milestone_concurrent'] ] ] ) )
				],
			] );
		}

		return Theme::i()->getTemplate( 'achievements', 'core', 'admin' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_title' ),
			$conditions
		);
	}

	/**
	 * Get a user friendly description of why this was awarded
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	public function awardDescription( Rule $rule ): ?string
	{
		if ( isset( $rule->filters['milestone_registered'] ) )
		{
			return Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_registered_badge_blurb', FALSE, [
				'pluralize' => [
					$rule->filters['milestone_registered']
				],
			] );
		}
		elseif ( isset( $rule->filters['milestone_concurrent'] ) )
		{
			return Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_concurrent_badge_blurb', FALSE, [
				'pluralize' => [
					$rule->filters['milestone_registered']
				],
			] );
		}

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__SessionStartDaily_badge_blurb' );
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'core_members',
			'pkey'  => 'member_id',
			'date'  => 'joined',
			'where' => [ [ 'last_visit > 0 AND ( ! ' . Db::i()->bitwiseWhere( Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) . ')' ] ],
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
		Member::constructFromData( $row )->achievementAction( 'core', 'SessionStartDaily' );
	}

}