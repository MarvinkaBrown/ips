<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		05 Mar 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\core\Achievements\Actions\ContentAchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\core\Feature;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;
use function get_class;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class ContentPromotion extends ContentAchievementActionAbstract
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
		$return = parent::filters( $filters, $url );

		$promoFilter = new CheckboxSet( 'achievement_filter_ContentPromotion_promotype', ( $filters and isset( $filters['promotype'] ) and $filters['promotype'] ) ? $filters['promotype'] : 0, FALSE, [
			'options' => [
				'feature' => 'achievement_filter_ContentPromotion_promotype_feature',
				'recommend' => 'achievement_filter_ContentPromotion_promotype_recommend'
			]
		], NULL, Member::loggedIn()->language()->addToStack( 'achievement_subfilter_ContentPromotion_promotype_prefix' ) );
		$return['promotype'] = $promoFilter;

		if ( isset( $return['milestone'] ) )
		{
			$return['milestone']->suffix = Member::loggedIn()->language()->addToStack('achievement_filter_ContentPromotion_nth_suffix');
		}

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
		if ( isset( $values['achievement_filter_ContentPromotion_promotype'] ) )
		{
			$return['promotype'] = $values['achievement_filter_ContentPromotion_promotype'];
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

		if ( isset( $filters['promotype'] ) and ! in_array( $extra['promotype'], $filters['promotype'] ) )
		{
			return FALSE;
		}

		if ( isset( $filters['milestone'] ) )
		{
			if ( ( $this->getQuery( 'COUNT(*)', $filters, $extra['promotype'], $subject )->first() + 1 ) < $filters['milestone'] ) 
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	
	/**
	 * Get identifier to prevent the member being awarded points for the same action twice
	 * Must be unique within this domain, must not exceed 32 chars.
	 *
	 * @param	Member	$subject	The subject member
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	string
	 */
	public function identifier( Member $subject, mixed $extra = NULL ): string
	{
		return get_class( $extra['content'] ) . ':' . $extra['content']->{$extra['content']::$databaseColumnId} . ':' . $extra['promotype'];
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

		$contentLink = Member::loggedIn()->language()->addToStack('modcp_deleted');
		try
		{
			/* @var ActiveRecord $class */
			$class = $exploded[0];
			$content = $class::load( $exploded[1] );
			$contentLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $content->url(), TRUE, $content->mapped('title'), FALSE );
			$promoType = Member::loggedIn()->language()->addToStack( 'achievement_filter_ContentPromotion_promotype_' . $exploded[2] );
		}
		catch ( OutOfRangeException $e ) {  }

		return Member::loggedIn()->language()->addToStack( 'AchievementAction__ContentPromotion_log_subject', FALSE, [ 'sprintf' => [ $promoType ], 'htmlsprintf' => [ $contentLink ] ] );
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
				'sprintf' => Member::loggedIn()->language()->addToStack('AchievementAction__ContentPromotion_title_generic')
			] );
		}
		if ( isset( $rule->filters['promotype'] ) )
		{
			$promoTypes = [];
			foreach ( $rule->filters['promotype'] as $type )
			{
				try
				{
					$promoTypes[] = Member::loggedIn()->language()->addToStack( 'achievement_filter_ContentPromotion_promotype_' . $type );
				}
				catch ( OutOfRangeException $e ) {}
			}
			if ( $promoTypes )
			{
				$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_type', FALSE, [
					'htmlsprintf' => [
						Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'other',
							count( $promoTypes ) === 1 ? $promoTypes[0] : Member::loggedIn()->language()->addToStack( 'AchievementAction__ContentPromotion_types', FALSE, [ 'sprintf' => [
								count( $promoTypes ),
							] ] ),
							count( $promoTypes ) === 1 ? NULL : $promoTypes
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
			Member::loggedIn()->language()->addToStack( 'AchievementAction__ContentPromotion_title' ),
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
			'table' => 'core_content_meta',
			'pkey'  => 'meta_id',
			'date'  => 'meta_added',
			'where' => [ ['meta_type=?', 'core_FeaturedComments'] ],
		],
		[
			'table' => 'core_content_promote',
			'pkey'  => 'promote_id',
			'date'  => 'promote_added',
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
		switch ( $data['table'] )
		{
			case 'core_content_meta':
				$metaData = json_decode( $row['meta_data'], TRUE );
				$commentClass = $row['meta_class']::$commentClass;
				$comment = $commentClass::load( $metaData['comment'] );
				$comment->author()->achievementAction( 'core', 'ContentPromotion', [
					'content' => $comment,
					'promotype' => 'recommend'
				]  );
				break;
			case 'core_content_promote':
				$promote = Feature::load( $row['promote_id'] );
				if( $author = $promote->object()->author() )
				{
					$author->achievementAction( 'core', 'ContentPromotion', [
						'content' => $promote->object(),
						'promotype' => 'promote'
					] );
				}

				break;
		}
	}

	/**
	 * Get the data based on the things.
	 *
	 * @param string $select
	 * @param array $filters
	 * @param string $type
	 * @param Member|null $subject
	 * @param int|null $lastId
	 * @param DateTime|null $time
	 * @param string|null $order
	 * @param int|null $limit
	 * @return Select
	 */
	protected function getQuery( string $select, array $filters, string $type, ?Member $subject=NULL, ?int $lastId=NULL, ?DateTime $time=NULL, ?string $order=NULL, ?int $limit=NULL ) : Select
	{
		$where = [];
		$subWhere = [];
		if( isset( $filters['type'] ) )
		{
			$itemClass = $filters['type'];

			if ( isset( $itemClass::$databaseColumnMap['hidden'] ) )
			{
				$subWhere = [ [ $itemClass::$databaseColumnMap['hidden'] . '=0' ] ];
			}
			elseif ( isset( $itemClass::$databaseColumnMap['approved'] ) )
			{
				$subWhere = [ [ $itemClass::$databaseColumnMap['approved'] . '=1' ] ];
			}
		}
		
		/* Recommended (but really called featured in the code just to confuse me) */
		if ( $type === 'recommend' )
		{
			if ( $subject )
			{
				$where = [
					['meta_item_author=? and meta_type=?', $subject->member_id, 'core_FeaturedComments'],
				];
			}
			else
			{
				$where = [
					['meta_type=?', 'core_FeaturedComments'],
				];
			}

			if ( $time )
			{
				$where[] = [ 'meta_added >= ' . $time->getTimestamp() ];
			}

			if ( $lastId )
			{
				$where[] = [ 'meta_id > ' . $lastId ];
			}

			if ( $order )
			{
				$order = 'meta_id ASC';
			}

			if ( isset( $filters['type'] ) )
			{
				$where[] = [ 'meta_class=?', $filters['type'] ];

				if ( isset( $filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) )
				{
					$subWhere[] = [ Db::i()->in( $itemClass::$databaseColumnId, $filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) ];
				}

				$where[] = [ 'meta_item_id IN(?)', Db::i()->select( $itemClass::$databaseColumnId, $itemClass::$databasePrefix . $itemClass::$databaseTable, $subWhere ) ];
			}

			return Db::i()->select( $select, 'core_content_meta', $where );
		}
		else if ( $type === 'promote' )
		{
			if ( $subject )
			{
				$where = [
					['promote_author_id=?', $subject->member_id],
				];
			}

			if ( $time )
			{
				$where[] = [ 'promote_added >= ' . $time->getTimestamp() ];
			}

			if ( $lastId )
			{
				$where[] = [ 'promote_id > ' . $lastId ];
			}

			if ( $order )
			{
				$order = 'promote_id ASC';
			}

			if ( isset( $filters['type'] ) )
			{
				$where[] = [ 'promote_hide=0 and promote_class=?', $filters['type'] ];

				if ( isset( $filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) )
				{
					$subWhere[] = [ Db::i()->in( $itemClass::$databaseColumnId, $filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) ];
				}

				$where[] = [ 'promote_class_id IN(?)', Db::i()->select( $itemClass::$databaseColumnId, $itemClass::$databasePrefix . $itemClass::$databaseTable, $subWhere ) ];
			}

			return Db::i()->select( $select, 'core_content_promote', $where, $order, $limit );
		}

		throw new InvalidArgumentException;
	}
}