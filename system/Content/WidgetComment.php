<?php
/**
 * @brief		Content Item Feed Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	forums
 * @since		16 Oct 2014
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Rating;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\IPS;
use IPS\Member;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function is_array;
use const IPS\UPGRADE_LARGE_TABLE_SIZE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Comment Feed Widget
 */
abstract class WidgetComment extends PermissionCache implements Customizable
{
	/**
	 * @brief	Class
	 */
	protected static string $class;

	/**
	 * @brief	Moderator permission to generate caches on [optional]
	 */
	protected array $moderatorPermissions	= array();
	
	/**
	 * Skip the getItemsWithPermission check?
	 */
	protected static string $permissionCheck = 'read';

	/**
	 * Skip the getItemsWithPermission check?
	 */
	protected static bool $skipPermissions = FALSE;

	/**
	 * Constructor
	 *
	 * @param String $uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param array|string|null $access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param string|null $orientation			Orientation (top, bottom, right, left)
	 * @param string $layout
	 * @return	void
	 */
	public function __construct(string $uniqueKey, array $configuration, array|string $access=null, string $orientation=null, string $layout='table' )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation, $layout );

		if( count( $this->moderatorPermissions ) )
		{
			$cacheKeyChecks	= array();

			foreach( $this->moderatorPermissions as $permission )
			{
				$cacheKeyChecks[]	= Member::loggedIn()->modPermission( $permission );
			}

			$this->cacheKey .= '_' . implode( '_', $cacheKeyChecks );
		}
	}

	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

		foreach( $this->formElements() as $element )
		{
			$form->add( $element );
		}
        
 		return $form;
 	}

 	/**
 	 * Return the form elements to use
 	 *
 	 * @return array
 	 */
 	protected function formElements(): array
 	{
		 /* @var Comment $class */
		/* Init */
		$class		= static::$class;
		$itemClass	= $class::$itemClass;
		$return		= array();

 		/* Block title */ 		
		$return['title'] = new Text( 'widget_feed_title', $this->configuration['widget_feed_title'] ?? Member::loggedIn()->language()->addToStack( $class::$title . '_pl' ) );
		
		/* Container */
		if ( isset( $itemClass::$containerNodeClass ) )
		{
			$return['container'] = new Node( 'widget_feed_container_' . $itemClass::$title, $this->configuration['widget_feed_container'] ?? 0, FALSE, array(
				'class'           => $itemClass::$containerNodeClass,
				'zeroVal'         => 'all',
				'permissionCheck' => 'view',
				'multiple'        => TRUE,
				'clubs'			  => TRUE
			) );

			/* Use permissions? */
			if( in_array( 'IPS\Node\Permissions', class_implements( $itemClass::$containerNodeClass ) ) )
			{
				$return['permissions'] = new YesNo( 'widget_feed_use_perms', $this->configuration['widget_feed_use_perms'] ?? TRUE, FALSE );
			}
		}
		
		/* Types */
		if ( IPS::classUsesTrait( $itemClass, 'IPS\Content\Lockable' ) )
		{
			$types = array(
				'any'    => 'mod_confirm_either',
				'open'   => 'mod_confirm_unlock',
				'closed' => 'mod_confirm_lock'
			);

			$return['locked'] = new Radio( 'widget_feed_item_status_locked', $this->configuration['widget_feed_item_status_locked'] ?? 'any', FALSE, array( 'options' => $types ) );
		}
		if ( IPS::classUsesTrait( $itemClass, 'IPS\Content\Pinnable' ) )
		{
			$types = array(
				'any'       => 'mod_confirm_either',
				'pinned'    => 'mod_confirm_pin',
				'notpinned' => 'mod_confirm_unpin'
			);

			$return['pinned'] = new Radio( 'widget_feed_item_status_pinned', $this->configuration['widget_feed_item_status_pinned'] ?? 'any', FALSE, array( 'options' => $types ) );
		}
		if ( IPS::classUsesTrait( $itemClass, 'IPS\Content\Featurable' ) )
		{
			$types = array(
				'any'         => 'mod_confirm_either',
				'featured'    => 'mod_confirm_feature',
				'notfeatured' => 'mod_confirm_unfeature'
			);

			$return['featured'] = new Radio( 'widget_feed_item_status_featured', $this->configuration['widget_feed_item_status_featured'] ?? 'any', FALSE, array( 'options' => $types ) );
		}
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
		{
			$types = array(
				'any'         => 'mod_confirm_either',
				'visible'     => 'mod_confirm_visible',
				'hidden'      => 'mod_confirm_hidden'
			);
	
			$return['hidden'] = new Radio( 'widget_feed_comment_status_visible', $this->configuration['widget_feed_comment_status_visible'] ?? 'any', FALSE, array( 'options' => $types ) );
		}
		if ( IPS::classUsesTrait( $itemClass, 'IPS\Content\Solvable' ) )
		{
			$types = array(
				'any'       => 'solved_either',
				'solved'    => 'solved_solved',
				'unsolved'  => 'solved_unsolved'
			);
	
			$return['solved'] = new Radio( 'widget_feed_status_solved', $this->configuration['widget_feed_status_solved'] ?? 'any', FALSE, array( 'options' => $types ) );
		}
		/* Author */
		$author = NULL;
		try
		{
			if ( isset( $this->configuration['widget_feed_item_author'] ) and is_array( $this->configuration['widget_feed_item_author'] ) )
			{
				foreach( $this->configuration['widget_feed_item_author']  as $id )
				{
					$author[ $id ] = Member::load( $id );
				}
			}
		}
		catch( OutOfRangeException $ex ) { }
		$return['author'] = new FormMember( 'widget_feed_item_author', $author, FALSE, array( 'multiple' => true ) );
		
		/* Minimum comments/reviews */
		if ( isset( $class::$commentClass ) )
		{
			if ( $class::$firstCommentRequired )
			{
				$return['min_posts'] = new Number( 'widget_feed_item_min_posts', $this->configuration['widget_feed_item_min_posts'] ?? 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) );
			}
			else
			{
				$return['min_comments'] = new Number( 'widget_feed_item_min_comments', $this->configuration['widget_feed_item_min_comments'] ?? 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) );
			}
		}
		if ( isset( $class::$reviewClass ) )
		{
			$return['min_reviews'] = new Number( 'widget_feed_item_min_reviews', $this->configuration['widget_feed_item_min_reviews'] ?? 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) );
		}
		
		/* Rating */
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Ratings' ) and isset( $class::$databaseColumnMap['rating_average'] )  )
		{
			$return['rating'] = new Rating( 'widget_feed_item_min_rating', $this->configuration['widget_feed_item_min_rating'] ?? 0, FALSE, array() );
		}

		if ( isset( $class::$databaseColumnMap['date'] ) )
		{
			$return['date_restrict'] = new Select( 'widget_feed_comment_date', isset( $this->configuration['widget_feed_comment_date'] ) ? (int) $this->configuration['widget_feed_comment_date'] : 0, FALSE, array(
				'options' => array(
					0	=> $class::databaseTableCount( TRUE ) > UPGRADE_LARGE_TABLE_SIZE ? 'search_year' : 'show_all',
					1	=> 'today',
					5	=> 'last_5_days',
					7	=> 'last_7_days',
					10	=> 'last_10_days',
					15	=> 'last_15_days',
					20	=> 'last_20_days',
					25	=> 'last_25_days',
					30	=> 'last_30_days',
					60	=> 'last_60_days',
					90	=> 'last_90_days',
				)
			) );
		}

		/* Number to show */
 		$return['show'] = new Number( 'widget_feed_show', $this->configuration['widget_feed_show'] ?? 5, TRUE );
 		
 		/* Sort */
		$return['sort'] = new Select( 'widget_feed_sort_dir', $this->configuration['widget_feed_sort_dir'] ?? 'desc', FALSE, array(
            'options' => array(
	            'desc'   => 'descending',
	            'asc'    => 'ascending'
            )
        ) );

		/* Fix up some language strings */
		$langs = array( 'widget_feed_item_status_locked', 'widget_feed_item_status_pinned', 'widget_feed_item_status_featured', 'widget_feed_comment_status_visible', 'widget_feed_item_author', 'widget_feed_item_min_posts', 'widget_feed_item_min_comments', 'widget_feed_item_min_reviews', 'widget_feed_item_min_rating' );
		$words = Member::loggedIn()->language()->get( $langs );
		
		foreach( $langs as $lang )
		{
			Member::loggedIn()->language()->words[ $lang ] = sprintf( $words[ $lang ], Member::loggedIn()->language()->addToStack( $itemClass::$title ) );
		}

		return $return;
 	}
 	
 	/**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( array $values ): array
 	{
		 /* @var Comment $class */
	 	$class     = static::$class;
		$itemClass = $class::$itemClass;
	 	
 		if ( is_array( $values[ 'widget_feed_container_' . $itemClass::$title ] ) )
 		{
	 		$values['widget_feed_container'] = array_keys( $values[ 'widget_feed_container_' . $itemClass::$title ] );
			unset( $values[ 'widget_feed_container_' . $itemClass::$title ] );
 		}
 		
 		if ( is_array( $values['widget_feed_item_author'] ) )
 		{
	 		$members = array();
	 		foreach( $values['widget_feed_item_author'] as $member )
	 		{
		 		$members[] = $member->member_id;
	 		}
	 		
	 		$values['widget_feed_item_author'] = $members;
 		}
 		
 		return $values;
 	}
 	
 	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere(): array
	{
		/* @var array $databaseColumnMap */
		/* @var Comment $class */
		$class     = static::$class;
		$itemClass = $class::$itemClass;
		$where     = array();
		
		/* Reset now as this could have been previously set to TRUE if using multiple Content widgets */
		static::$permissionCheck = 'read';
		
		/* Container */
		if ( isset( $itemClass::$containerNodeClass ) and !empty( $this->configuration['widget_feed_container'] ) )
		{
			$nodeIds = array();
			
			if ( ! empty( $this->configuration['widget_feed_use_perms'] ) )
			{
				static::$skipPermissions = TRUE;
			}

			foreach( $this->configuration['widget_feed_container'] as $id )
			{
				try
				{
					if ( ! empty( $this->configuration['widget_feed_use_perms'] ) )
					{
						if ( $itemClass::$containerNodeClass::load( $id )->can('read') )
						{
							$nodeIds[] = $id;
						}
					}
					else
					{
						$nodeIds[] = $id;
					}
				}
				catch( Exception $e )
				{
					
				}
			}
			
			$where['item'][] = array( Db::i()->in( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'], $nodeIds ) );
		}

		/* Locked or open status */
		if ( isset( $this->configuration['widget_feed_item_status_locked'] ) and IPS::classUsesTrait( $itemClass, 'IPS\Content\Lockable' ) )
		{
			if ( $this->configuration['widget_feed_item_status_locked'] == 'closed' )
			{
				$where['item'][] = isset( $itemClass::$databaseColumnMap['locked'] ) ? array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['locked'] . '=?', 1 ) : array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['status'] . '=?', 'closed' );
			}
			elseif ( $this->configuration['widget_feed_item_status_locked'] == 'open' )
			{
				$where['item'][] = isset( $itemClass::$databaseColumnMap['locked'] ) ? array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['locked'] . '=?', 0 ) : array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['status'] . '=?', 'open' );
			}
		}

		/* Featured or unfeatured */
		if ( isset( $this->configuration['widget_feed_item_status_featured'] ) and IPS::classUsesTrait( $itemClass, 'IPS\Content\Featurable' ) )
		{
			if ( $this->configuration['widget_feed_item_status_featured'] == 'notfeatured' )
			{
				$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['featured'] . '=0' );
			}
			elseif ( $this->configuration['widget_feed_item_status_featured'] == 'featured' )
			{
				$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['featured'] . '=1' );
			}
		}
		
		if ( isset( $this->configuration['widget_feed_status_solved'] ) and $this->configuration['widget_feed_status_solved'] != 'any' )
		{
			$where['item'][] = $this->configuration['widget_feed_status_solved'] == 'solved' ? array( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['solved_comment_id'] . '>0' ) : array( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['solved_comment_id'] . '=0' );
			
			if ( $this->configuration['widget_feed_status_solved'] == 'solved' )
			{
				$where[] = array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnId . '=' . $itemClass::$databaseColumnMap['solved_comment_id'] );
			}
		}
		
		/* Pinned or unpinned */
		if ( isset( $this->configuration['widget_feed_item_status_pinned'] ) and IPS::classUsesTrait( $itemClass, 'IPS\Content\Pinnable' ) )
		{
			if ( $this->configuration['widget_feed_item_status_pinned'] == 'notpinned' )
			{
				$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['pinned'] . '=0' );
			}
			elseif ( $this->configuration['widget_feed_item_status_pinned'] == 'pinned' )
			{
				$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['pinned'] . '=1' );
			}
		}
		
		/* Author */
		if ( isset( $this->configuration['widget_feed_item_author'] ) and is_array( $this->configuration['widget_feed_item_author'] ) and count( $this->configuration['widget_feed_item_author'] ) )
		{
			$where['item'][] = array( Db::i()->in( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'], $this->configuration['widget_feed_item_author'] ) );
		}
		
		/* Min posts/comments/reviews */
		if ( isset( $this->configuration['widget_feed_item_min_posts'] ) and $this->configuration['widget_feed_item_min_posts'] )
		{
			$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] . '>?', (int) $this->configuration['widget_feed_item_min_posts'] );
		}
		if ( isset( $this->configuration['widget_feed_item_min_comments'] ) and $this->configuration['widget_feed_item_min_comments'] )
		{
			$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_comments'] . '>?', (int) $this->configuration['widget_feed_item_min_comments'] );
		}
		if ( isset( $this->configuration['widget_feed_item_min_reviews'] ) and $this->configuration['widget_feed_item_min_reviews'] )
		{
			$where['item'][] = array( $itemClass::$databaseTable . '.' .  $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['num_reviews'] . '>?', (int) $this->configuration['widget_feed_item_min_reviews'] );
		}
		
		/* Rating */
		if ( isset( $this->configuration['widget_feed_item_min_rating'] ) and $this->configuration['widget_feed_item_min_rating'] )
		{
			$where['item'][] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['rating_average'] . '>?', (int) $this->configuration['widget_feed_item_min_rating'] );
		}

		/* Future */
		if ( IPS::classUsesTrait( $itemClass, 'IPS\Content\FuturePublishing' ) )
		{
			$where['item'][] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['is_future_entry'] . '=0' );
		}
		
		/* Start date */
		if ( isset( $this->configuration['widget_feed_comment_date'] ) and $this->configuration['widget_feed_comment_date'] > 0 )
		{
			$where[] = array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['date'] . '>?',  DateTime::create()->sub( new DateInterval( 'P' . $this->configuration['widget_feed_comment_date'] . 'D' ) )->getTimestamp() );
		}
		else if ( $class::databaseTableCount( TRUE ) > UPGRADE_LARGE_TABLE_SIZE )
		{
			$where[] = array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['date'] . '>?',  DateTime::create()->sub( new DateInterval( 'P1Y' ) )->getTimestamp() );
		}

		return $where;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		/* @var array $databaseColumnMap */
		/* @var Comment $class */
		$class = static::$class;

		/* What visible status are we checking? */
		$hidden	= Filter::FILTER_AUTOMATIC;

		if( isset( $this->configuration['widget_feed_comment_status_visible'] ) )
		{
			switch( $this->configuration['widget_feed_comment_status_visible'] )
			{
				case 'visible':
					$hidden	= Filter::FILTER_PUBLIC_ONLY;
				break;

				case 'hidden':
					$hidden	= Filter::FILTER_ONLY_HIDDEN;
				break;
			}
		}

		$items = $class::getItemsWithPermission(
			$this->buildWhere(),
			( isset( $this->configuration['widget_feed_sort_on'] ) and isset( $this->configuration['widget_feed_sort_dir'] ) ) ? ( $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' ' . $this->configuration['widget_feed_sort_dir'] ) : NULL,
			( isset( $this->configuration['widget_feed_show'] ) AND $this->configuration['widget_feed_show'] ) ? $this->configuration['widget_feed_show'] : 5,
			( !isset( $this->configuration['widget_feed_use_perms'] ) or $this->configuration['widget_feed_use_perms'] ) ? static::$permissionCheck : NULL,
			$hidden
		);

		if ( count( $items ) )
		{
			return $this->output( $items, $this->configuration['widget_feed_title'] ?? Member::loggedIn()->language()->addToStack( $class::$title . '_pl' ) );
		}
		else
		{
			return '';
		}
	}
}