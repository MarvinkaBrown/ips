<?php
/**
 * @brief		Achievement Action Extension: Answer a member in a Q&A Forum is marked as the best answer
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Forums
 * @since		18 Feb 2021
 */

namespace IPS\forums\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\DateTime;
use IPS\Db;
use IPS\forums\Forum;
use IPS\forums\Topic\Post;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension: Answer a member in a Q&A Forum is marked as the best answer
 */
class AnswerMarkedBest extends AchievementActionAbstract
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
		return [
			'nodes' => new Node( 'achievement_filter_AnswerMarkedBest_forum', ( $filters and isset( $filters['nodes'] ) and $filters['nodes'] ) ? $filters['nodes'] : NULL, FALSE, [
				'url'				=> $url,
				'class'				=> 'IPS\forums\Forum',
				'showAllNodes'		=> TRUE,
				'multiple' 			=> TRUE,
				'permissionCheck'	=> function( $forum ) {
					return $forum->forums_bitoptions['bw_enable_answers'];
				}
			], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_AnswerMarkedBest_forum_prefix') ),
			'helpful_or_solved' => new Select( 'achievement_filter_AnswerMarkedBest_helpful_or_solved', ( $filters and isset( $filters['helpful_or_solved'] ) and $filters['helpful_or_solved'] ) ? $filters['helpful_or_solved'] : NULL, NULL, [
				'options' => [
					'helpful' => Member::loggedIn()->language()->addToStack('achievement_filter_AnswerMarkedBest_helpful_or_solved_helpful'),
					'solved'  => Member::loggedIn()->language()->addToStack('achievement_filter_AnswerMarkedBest_helpful_or_solved_solved')
				]
			] ),
			'milestone' => new Number( 'achievement_filter_AnswerMarkedBest_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_AnswerMarkedBest_nth_suffix') )
		];
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
		if ( isset( $values['achievement_filter_AnswerMarkedBest_forum'] ) )
		{
			$return['nodes'] = array_keys( $values['achievement_filter_AnswerMarkedBest_forum'] );
		}
		if ( isset( $values['achievement_filter_AnswerMarkedBest_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_AnswerMarkedBest_nth'];
		}
		if ( isset( $values['achievement_filter_AnswerMarkedBest_helpful_or_solved'] ) )
		{
			$return['helpful_or_solved'] = $values['achievement_filter_AnswerMarkedBest_helpful_or_solved'];
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
		$post = $extra['post'];
		$type = $extra['type'] == 'helpful'? 'helpful' :'solved';
		if ( isset( $filters['nodes'] ) and !in_array( $post->container()->_id, $filters['nodes'] ) )
		{
			return FALSE;
		}
		
		if ( isset( $filters['milestone'] ) )
		{
			$where = [
				[ 'member_id=? AND app=? AND type=? AND hidden=0', $post->author()->member_id, 'forums', $type ],
			];
			if ( isset( $filters['nodes'] ) )
			{
				$where[] = [ Db::i()->in( 'forum_id', $filters['nodes'] ) ];
			}
			$query = Db::i()->select( 'COUNT(*)', 'core_solved_index', $where );
			if ( isset( $filters['nodes'] ) )
			{
				$query->join( 'forums_topics', 'core_solved_index.item_id=forums_topics.tid' );
			}			
			if ( ( $query->first() + 1 ) < $filters['milestone'] )
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
			'subject'	=> 'achievement_filter_AnswerMarkedBest_poster',
			'other'		=> 'achievement_filter_AnswerMarkedBest_asker'
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
		return [ $extra->item()->author() ];
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
		$post = $extra['post'];
		$type = $extra['type'] == 'helpful'? ':helpful' : ''; // Prevent existing rows from becoming obsolete by changing format to 123:solved

		return $post->pid . $type;
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
		$sprintf = [];
		if ( stristr( $identifier, ':') )
		{
			[ $identifier, $type ] = explode( ':', $identifier );
		}
		else
		{
			$type = 'solved';
		}

		try
		{
			$post = Post::load( $identifier );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $post->url(), TRUE, $post->item()->title, FALSE )
			] ];
		}
		catch ( OutOfRangeException $e )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('AchievementAction__AnswerMarkedBest_log_deleted') ] ];
		}
		
		if ( in_array( 'subject', $actor ) )
		{
			return $type === 'solved'
				? Member::loggedIn()->language()->addToStack( 'AchievementAction__AnswerMarkedBest_log_subject', FALSE, $sprintf )
				: Member::loggedIn()->language()->addToStack( 'AchievementAction__AnswerMarkedBest_log_subject_helpful', FALSE, $sprintf );
		}
		else
		{
			return $type === 'solved'
				? Member::loggedIn()->language()->addToStack( 'AchievementAction__AnswerMarkedBest_log_other', FALSE, $sprintf )
				: Member::loggedIn()->language()->addToStack( 'AchievementAction__AnswerMarkedBest_log_other_helpful', FALSE, $sprintf );
		}
	}
	
	/**
	 * Get "description" for rule
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|null
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
				'sprintf' => Member::loggedIn()->language()->addToStack( 'best_answer_post', FALSE, [ 'strtolower' => TRUE ] )
			] );
		}
		if ( isset( $rule->filters['nodes'] ) )
		{
			$forumNames = [];
			foreach ( $rule->filters['nodes'] as $id )
			{
				try
				{
					$forumNames[] = Forum::load( $id )->_title;
				}
				catch ( OutOfRangeException $e ) {}
			}
			if ( $forumNames )
			{
				$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_location', FALSE, [
					'htmlsprintf' => [
						Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'location',
							count( $forumNames ) === 1 ? $forumNames[0] : Member::loggedIn()->language()->addToStack( 'achievements_title_filter_location_val', FALSE, [ 'sprintf' => [
								count( $forumNames ),
								Member::loggedIn()->language()->addToStack( Forum::$nodeTitle, FALSE, [ 'strtolower' => TRUE ] )
							] ] ),
							count( $forumNames ) === 1 ? NULL : $forumNames
						)
					],
				] );
			}
		}
		$title = Member::loggedIn()->language()->addToStack( 'AchievementAction__AnswerMarkedBest_title' );
		if ( isset( $rule->filters['helpful_or_solved'] ) and $rule->filters['helpful_or_solved'] == 'helpful' )
		{
			$title = Member::loggedIn()->language()->addToStack( 'AchievementAction__AnswerMarkedHelpful_title' );
		}

		return Theme::i()->getTemplate( 'achievements' )->ruleDescription( $title, $conditions );
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'core_solved_index',
			'pkey'  => 'id',
			'date'  => 'solved_date',
			'where' => [ [ 'app=? AND type=?', 'forums', 'solved' ] ],
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
		$post = Post::load( $row['comment_id'] );
		$post->author()->achievementAction( 'forums', 'AnswerMarkedBest', $post, DateTime::ts( $row[ $data['date'] ] ) );
	}
}