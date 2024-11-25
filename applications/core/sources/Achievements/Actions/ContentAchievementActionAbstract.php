<?php
/**
 * @brief		Abstract Achievement Action Extension for content-related things
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		24 Feb 2021
 */

namespace IPS\core\Achievements\Actions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Content;
use IPS\core\Achievements\Rule;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_called_class;
use function get_class;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Achievement Action Extension for content-related things
 */
abstract class ContentAchievementActionAbstract extends AchievementActionAbstract
{	
	protected static bool $includeItems = TRUE;
	protected static bool $includeComments = TRUE;
	protected static bool $includeReviews = TRUE;

	/**
	 * @var bool    Exclude content items that require a first comment
	 */
	protected static bool $excludeItemsWithRequiredComment = false;
	
	/**
	 * Get filter form elements
	 *
	 * @param	array|NULL		$filters	Current filter values (if editing)
	 * @param	Url	$url		The URL the form is being shown on
	 * @return	array
	 */
	public function filters( ?array $filters, Url $url ): array
	{
		$classKey = explode( '\\', get_called_class() )[5];
		
		$contentTypeOptions = [];
		$contentTypeToggles = [];
		$_return = [];
		
		$defaultApp = NULL;
		foreach( Application::applications() as $directory => $application )
		{
			if( $application->default )
			{
				$defaultApp	= $directory;
				break;
			}
		}
		foreach ( Application::allExtensions( 'core', 'ContentRouter', FALSE, $defaultApp ) as $extension )
		{
			foreach ( $extension->classes as $class )
			{
				if ( isset( $class::$databaseColumnMap['author'] ) )
				{
					$nodeFilterKey = NULL;
					$haveMatchingClass = FALSE;
					if ( isset( $class::$containerNodeClass ) )
					{
						$nodeFilterKey = 'achievement_subfilter_' . $classKey . '_type_' . str_replace( '\\', '-', $class );
						$contentTypeToggles[ $class ][] = $nodeFilterKey;
					}
					if ( static::$includeItems OR ( !static::$includeItems AND static::$excludeItemsWithRequiredComment AND !$class::$firstCommentRequired ) )
					{
						$contentTypeOptions[ $class ] = $class::$title;
						$contentTypeToggles[ $class ] = $nodeFilterKey ? [ $nodeFilterKey ] : [];
						$haveMatchingClass = TRUE;
					}
					if ( static::$includeComments and isset( $class::$commentClass ) )
					{
						$commentClass = $class::$commentClass;
						$contentTypeOptions[ $commentClass ] = $commentClass::$title;
						$contentTypeToggles[ $commentClass ] = $nodeFilterKey ? [ $nodeFilterKey ] : [];
						$haveMatchingClass = TRUE;
					}
					if ( static::$includeReviews and isset( $class::$reviewClass ) )
					{
						$reviewClass = $class::$reviewClass;
						$contentTypeOptions[ $reviewClass ] = $reviewClass::$title;
						$contentTypeToggles[ $reviewClass ] = $nodeFilterKey ? [ $nodeFilterKey ] : [];
						$haveMatchingClass = TRUE;
					}
					
					if ( $nodeFilterKey and $haveMatchingClass )
					{
						try
						{
							$nodeTitle = Member::loggedIn()->language()->get( ($class::$containerNodeClass)::$nodeTitle . '_sg_lc' );
						}
						catch( UnderflowException $e )
						{
							$nodeTitle = ($class::$containerNodeClass)::$nodeTitle . '_sg';
						}

						$nodeFilter = new Node( $nodeFilterKey, ( $filters and isset( $filters[ 'nodes_' . str_replace( '\\', '-', $class ) ] ) and $filters[ 'nodes_' . str_replace( '\\', '-', $class ) ] ) ? $filters[ 'nodes_' . str_replace( '\\', '-', $class ) ] : 0, FALSE, [
							'url'				=> $url,
							'class'				=> $class::$containerNodeClass,
							'showAllNodes'		=> TRUE,
							'multiple' 			=> TRUE,
						], NULL, Member::loggedIn()->language()->addToStack( 'achievement_filter_NewContentItem_node_prefix', FALSE, [ 'sprintf' => [
							Member::loggedIn()->language()->addToStack( $class::_definiteArticle(), FALSE ),
							Member::loggedIn()->language()->addToStack( $nodeTitle, FALSE )
						] ] ) );
						$nodeFilter->label = Member::loggedIn()->language()->addToStack( 'achievement_filter_NewContentItem_node', FALSE, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( $nodeTitle, FALSE ) ] ] );
						$_return[ "nodes_" . str_replace( '\\', '-', $class ) ] = $nodeFilter;
					}
				}
			}
		}
		
		$typeFilter = new Select( 'achievement_filter_' . $classKey . '_type', ( $filters and isset( $filters['type'] ) and $filters['type'] ) ? $filters['type'] : NULL, FALSE, [ 'options' => $contentTypeOptions, 'toggles' => $contentTypeToggles ], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_NewContentItem_type_prefix') );
		$typeFilter->label = Member::loggedIn()->language()->addToStack('achievement_filter_NewContentItem_type');

		$nthFilter = new Number( 'achievement_filter_' . $classKey . '_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_' . $classKey . '_nth_suffix') );
		$nthFilter->label = Member::loggedIn()->language()->addToStack('achievement_filter_NewContentItem_nth');

		$return = [ 'type' => $typeFilter ];
		foreach ( $_return as $k => $v )
		{
			$return[ $k ] = $v;
		}
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
		$classKey = explode( '\\', get_called_class() )[5];
		
		$return = [];
		if ( isset( $values['achievement_filter_' . $classKey . '_type'] ) )
		{			
			$return['type'] = $values['achievement_filter_' . $classKey . '_type'];
			
			$itemClass = $return['type'];
			if ( in_array( 'IPS\Content\Comment', class_parents( $return['type'] ) ) )
			{
				$itemClass = $return['type']::$itemClass;
			}
			
			if ( isset( $values[ 'achievement_subfilter_' . $classKey . '_type_' . str_replace( '\\', '-', $itemClass ) ] ) )
			{
				$return[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] = array_keys( $values[ 'achievement_subfilter_' . $classKey . '_type_' . str_replace( '\\', '-', $itemClass ) ] );
			}
		}
		if ( isset( $values['achievement_filter_' . $classKey . '_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_' . $classKey . '_nth'];
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
		if ( isset( $filters['type'] ) )
		{
			if ( !( $extra instanceof $filters['type'] ) )
			{
				return FALSE;
			}
			
			$item = $extra;
			if ( in_array( 'IPS\Content\Comment', class_parents( $filters['type'] ) ) )
			{
				$item = $extra->item();
			}

			if ( isset( $filters[ 'nodes_' . str_replace( '\\', '-', get_class( $item ) ) ] ) )
			{
				if ( !in_array( $item->container()->_id, $filters[ 'nodes_' . str_replace( '\\', '-', get_class( $item ) ) ] ) )
				{
					return FALSE;
				}
			}

			return TRUE;
		}
		else
		{
			/* Make sure the class is a contentRouter approved class, mostly to stop private messages being awarded points */
			foreach ( Application::allExtensions( 'core', 'ContentRouter', FALSE ) as $extension )
			{
				foreach ( $extension->classes as $class )
				{
					if ( get_class( $extra ) == $class )
					{
						return TRUE;
					}
					elseif ( isset( $class::$commentClass ) and get_class( $extra ) == $class::$commentClass )
					{
						return TRUE;
					}
					elseif ( isset( $class::$reviewClass ) and get_class( $extra ) == $class::$reviewClass )
					{
						return TRUE;
					}
				}
			}

			return FALSE;
		}
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
		return get_class( $extra ) . ':' . $extra->{$extra::$databaseColumnId};
	}
	
	/**
	 * Get "description" for rule (usually a description of the rule's filters)
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	protected function _nodeFilterDescription( Rule $rule ): ?string
	{
		if ( isset( $rule->filters['type'] ) )
		{
			$itemClass = $rule->filters['type'];
			if ( in_array( 'IPS\Content\Comment', class_parents( $rule->filters['type'] ) ) )
			{
				$itemClass = $itemClass::$itemClass;
			}
			
			if ( isset( $rule->filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) )
			{
				$nodeClass = $itemClass::$containerNodeClass;
				
				$nodeNames = [];
				foreach ( $rule->filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] as $id )
				{
					try
					{
						$nodeNames[] = $nodeClass::load( $id )->_title;
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
									Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle, FALSE )
								] ] ),
								count( $nodeNames ) === 1 ? NULL : $nodeNames
							)
						],
					] );
				}
			}
		}
		return NULL;
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData() : array
	{
		$return = [];
		foreach ( Application::allExtensions( 'core', 'ContentRouter', FALSE ) as $extension )
		{
			foreach ( $extension->classes as $class )
			{
				if ( isset( $class::$databaseColumnMap['author'] ) )
				{
					if ( static::$includeItems )
					{
						$return[] = [
							'table' => $class::$databaseTable,
							'pkey'  => $class::$databasePrefix . $class::$databaseColumnId,
							'date'  => ( isset( $class::$databaseColumnMap['date'] ) ) ? $class::$databasePrefix . $class::$databaseColumnMap['date'] : NULL,
							'where' => [],
							'class' => $class
						];
					}
					if ( static::$includeComments and isset( $class::$commentClass ) )
					{
						$commentClass = $class::$commentClass;
						$return[] = [
							'table' => $commentClass::$databaseTable,
							'pkey'  => $commentClass::$databasePrefix . $commentClass::$databaseColumnId,
							'date'  => ( isset( $commentClass::$databaseColumnMap['date'] ) ) ? $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] : NULL,
							'where' => [],
							'class' => $commentClass
						];
					}
					if ( static::$includeReviews and isset( $class::$reviewClass ) )
					{
						$reviewClass = $class::$reviewClass;
						$return[] = [
							'table' => $reviewClass::$databaseTable,
							'pkey'  => $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId,
							'date'  => ( isset( $reviewClass::$databaseColumnMap['date'] ) ) ? $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['date'] : NULL,
							'where' => [],
							'class' => $reviewClass
						];
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Get a where clause to check based on the type/class
	 *
	 * @param	array				$filters	The value returned by formatFilterValues()
	 * @param	string				$class		Class we are working with
	 * @param	DateTime|null	$time		Any time limit to add
	 * @return	array
	 */
	protected function getWherePerType( array $filters, string $class, ?DateTime $time=NULL ): array
	{
		/* @var Content $class */
		/* @var array $databaseColumnMap */
		$where = ( $time ? [ [ $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' >= ?', $time->getTimestamp() ] ] : [] );
		$itemClass = $class;

		if ( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
		{
			$itemClass = $class::$itemClass;
		}

		if ( isset( $filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) )
		{
			if ( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
			{
				$itemClass = $class::$itemClass;
				$where[] = [ $class::$databasePrefix . $class::$databaseColumnMap['item'] . ' IN(?)',
					Db::i()->select( $itemClass::$databasePrefix . $itemClass::$databaseColumnId, $itemClass::$databaseTable, [ Db::i()->in( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'], $filters[ 'nodes_' . str_replace( '\\', '-', $itemClass ) ] ) ] ) ];
			}
			else
			{
				$where[] = [ Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['container'], $filters[ 'nodes_' . str_replace( '\\', '-', $class ) ] ) ];
			}
		}

		return $where;
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
		$classCalled = explode( '\\', get_called_class() );
		$class = $data['class'];
		$item = $class::constructFromData( $row );
		$item->author()->achievementAction( 'core', array_pop( $classCalled ), $item );
	}
}