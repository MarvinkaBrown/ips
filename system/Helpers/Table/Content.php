<?php
/**
 * @brief		Table Builder using an \IPS\Content\Item class datasource
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Jul 2013
 */

namespace IPS\Helpers\Table;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use http\Exception\InvalidArgumentException;
use IPS\Content\Comment;
use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\Content\Taggable;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Output;
use IPS\Output\UI\UiExtension;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfBoundsException;
use OutOfRangeException;
use function call_user_func;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_callable;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List Table Builder using an \IPS\Content\Item class datasource
 */
class Content extends Table
{
	/**
	 * @brief	Database Table
	 */
	protected string $class;
	
	/**
	 * @brief	Initial WHERE clause
	 */
	public ?array $where;
	
	/**
	 * @brief	Container
	 */
	protected ?Model $container;
	
	/**
	 * @brief	Permission key to check
	 */
	protected ?string $permCheck;

	/**
	 * @brief	Include hidden content flag with results
	 */
	protected ?bool $includeHiddenContent	= NULL;
	
	/**
	 * @brief	Sort options
	 */
	public array $sortOptions = array();

	/**
	 * @brief	Honor the pinned flag for sorting. Will be set to false in stream-like views
	 */
	public bool $honorPinned	= TRUE;
	
	/**
	 * @brief	Show moved links in the result set. This is desirable in controller generated views but not streams or widgets, etc.
	 */
	protected bool $showMovedLinks = FALSE;
	
	/**
	 * Number of results
	 */
	public int $count = 0;

	/**
	 * @brief	Join container data in getItemsWithPermission
	 */
	public bool $joinContainer = FALSE;

	/**
	 * @brief	Join comment data in getItemsWithPermission
	 */
	public bool $joinComments = FALSE;
	
	/**
	 * @brief	Join review data in getItemsWithPermission
	 */
	public bool $joinReviews = FALSE;
	
	/**
	 * @brief	Advanced search callback
	 */
	public mixed $advancedSearchCallback = NULL;
	
	/**
	 * Saved Actions (for multi-moderation)
	 */
	public array $savedActions = array();
	
	/**
	 * @brief	Joins
	 */
	public array $joins = array();
	
	/**
	 * @brief	Array of item IDs the current $member has posted in
	 */
	public array $contentPostedIn = array();
	
	/**
	 * @brief	Callback method to adjust rows
	 */
	protected $callback = NULL;
	
	/**
	 * @brief	Get first comment
	 */
	protected bool $getFirstComment = FALSE;

	/**
	 * @brief	Get follower count
	 */
	protected bool $getFollowerCount = FALSE;
	
	/**
	 * @brief	Get reactions for content item if supported
	 */
	protected bool $getReactions = FALSE;

	/**
	 * @brief	If passing a container, limit content by that container
	 */
	protected mixed $limitByContainer = TRUE;
	
	/**
	 * Constructor
	 *
	 * @param string $class				Content Class Name
	 * @param	Url			$baseUrl			Base URL
	 * @param array|null $where				WHERE clause (To restrict to a node, use $container instead)
	 * @param	Model|NULL	$container			The container
	 * @param bool $includeHidden		Flag to pass to getItemsWithPermission() method for $includeHiddenContent, defaults to NULL
	 * @param string|null $permCheck			Permission key to check
	 * @param bool $honorPinned		Show pinned topics at the top of the table
	 * @param bool $showMovedLinks		Show moved links in the result set.
	 * @param callable|null $callback			Method to call to prepare the returned rows
	 * @param bool $getFirstComment	Get the first comment for this item
	 * @param bool $getFollowerCount	Get the follower counts for this item
	 * @param bool $getReactions		Get reactions for this item
	 * @return	void
	 */
	public function __construct(string $class, Url $baseUrl, array $where=NULL, Model $container=NULL, bool|null $includeHidden=Filter::FILTER_AUTOMATIC, ?string $permCheck='view', bool $honorPinned=TRUE, bool $showMovedLinks=FALSE, callable $callback=NULL, bool $getFirstComment=FALSE, bool $getFollowerCount=FALSE, bool $getReactions=FALSE, bool $limitByContainer=TRUE )
	{
		/* Init */
		$this->include = array();
		$this->class = $class;
		$this->where = $where;
		$this->container = $container;
		$this->includeHiddenContent	= $includeHidden;
		$this->honorPinned	= $honorPinned;
		$this->showMovedLinks = $showMovedLinks;
		$this->permCheck = $permCheck;
		$this->callback = $callback;
		$this->getFirstComment = $getFirstComment;
		$this->getFollowerCount = $getFollowerCount;
		$this->getReactions = $getReactions;
		$this->limitByContainer = $limitByContainer;

		/* Init */
		parent::__construct( $baseUrl );

		/* @var Item $class */
		/* @var array $databaseColumnMap */
		$this->rowsTemplate = $class::contentTableTemplate();

		/* Set container */
		if ( $container !== NULL )
		{
			if ( $this->limitByContainer )
			{
				$this->where[] = array($class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['container'] . '=?', $container->_id);
			}

			$this->sortDirection = ( $this->sortDirection !== NULL ) ? $this->sortDirection : $container->_sortOrder;

			if ( !$this->sortBy and ! empty( $container->_sortBy ) )
			{
				$this->sortBy =  $class::$databaseTable . '.' . $class::$databasePrefix . $container->_sortBy;
			}
			if ( !$this->filter )
			{
				$this->filter = $container->_filter;
			}
			
			if ( $this->includeHiddenContent === Filter::FILTER_AUTOMATIC AND IPS::classUsesTrait( $class, 'IPS\Content\Hideable' ) )
			{
				$this->includeHiddenContent = $class::canViewHiddenItems( Member::loggedIn(), $container );
			}
			
			/* Set breadcrumb */
			if ( IPS::classUsesTrait( $container, 'IPS\Content\ClubContainer' ) and $club = $container->club() )
			{
				$club->setBreadcrumbs( $container );
			}
			else
			{
				foreach ( $container->parents() as $parent )
				{
					Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}

				Output::i()->breadcrumb[] = array( NULL, $container->_title );
			}
			
			/* We do want the page in the canonical link otherwise Google won't index past page 1 */
			$canonicalUrl = $baseUrl;

			if ( $this->page > 1 )
			{
				$canonicalUrl = $canonicalUrl->setPage( $this->paginationKey, $this->page );
			}

			/* Meta tags */
			Output::i()->title = ( $this->page > 1 ) ? Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $container->metaTitle(), $this->page ) ) ) : $container->metaTitle();
			Output::i()->metaTags['title'] = $container->metaTitle();
			Output::i()->metaTags['description'] = $container->metaDescription();
			Output::i()->metaTags['og:title'] = $container->metaTitle();
			Output::i()->metaTags['og:description'] = $container->metaDescription();
			Output::i()->linkTags['canonical'] = (string) $canonicalUrl;
			Output::i()->metaTags['og:url'] = (string) $canonicalUrl;
		}

		/* Set available sort options */
		foreach ( array( 'updated', 'last_comment', 'title', 'rating', 'date', 'num_comments', 'num_reviews', 'views' ) as $k )
		{
			if ( isset( $class::$databaseColumnMap[ $k ] ) and !isset( $this->sortOptions[ $k ] ) )
			{
				$column = is_array( $class::$databaseColumnMap[ $k ] ) ? $class::$databaseColumnMap[ $k ][0] : $class::$databaseColumnMap[ $k ];

				/* In some circumstances `updated` and `last_comment` may be the same column, but we don't want two sort options */
				if( !in_array( $class::$databasePrefix . $column, $this->sortOptions ) )
				{
					$this->sortOptions[$k] = $class::$databasePrefix . $column;
				}
			}
		}

		/* Check Extensions for additional options */
		foreach( UiExtension::i()->run( $class, 'contentTableSortOptions', array( $this ) ) as $k => $v )
		{
			$this->sortOptions[$k] = $v;
		}

		if ( !$this->sortBy )
		{
			if ( isset( $class::$databaseColumnMap['updated'] ) )
			{
				$this->sortBy = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['updated'];
			}
			elseif ( isset( $class::$databaseColumnMap['last_comment'] ) )
			{
				$this->sortBy = $class::$databaseTable . '.' . $class::$databasePrefix . ( is_array( $class::$databaseColumnMap['last_comment'] ) ? $class::$databaseColumnMap['last_comment'][0] : $class::$databaseColumnMap['last_comment'] );
			}
			else
			{
				$this->sortBy = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['date'];
			}
		}

		/* Make sure we initialize the default sort fields so that we can properly get the column sort direction */
		$this->defaultSortBy = $this->sortBy;
		$this->defaultSortDirection = $this->sortDirection;

		/* Check extensions for custom filters */
		foreach( UiExtension::i()->run( $class, 'contentTableFilters', array( $this ) ) as $k => $v )
		{
			$this->filters[$k] = $v;
		}

		/* Do any multi-mod */
		if ( isset( Request::i()->modaction ) )
		{
			$this->multiMod();
		}
	}
	
	/**
	 * Get rows
	 *
	 * @param	array|null	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( array $advancedSearchValues = NULL ): array
	{
		/* Init */
		/* @var Item $class */
		$class = $this->class;
		
		/* Check sortBy */
		$defaultSort = NULL;
		foreach ( array( 'last_comment', 'last_review', 'date' ) as $k )
		{
			if ( isset( $class::$databaseColumnMap[ $k ] ) )
			{
				if ( is_array( $class::$databaseColumnMap[ $k ] ) )
				{
					$cols = $class::$databaseColumnMap[ $k ];
					$defaultSort = $class::$databaseTable . '.' . $class::$databasePrefix . array_pop( $cols );
				}
				else
				{
					$defaultSort = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap[ $k ];
				}
				break;
			}
		}
		
		/* If we are limiting to a single container, check to see if this user can view them */
		if ( ( $this->container and $this->limitByContainer ) and $this->permCheck )
		{
			try
			{
				if ( ! $this->container->can( $this->permCheck ) )
				{
					$this->count = 0;
					return array();
				}
			}
			catch( OutOfBoundsException )
			{
				$this->count = 0;
				return array();
			}
		}
		
		$compareSortBy = $this->sortBy;
		$lookFor       = $class::$databaseTable . '.' . $class::$databasePrefix;
		
		if ( mb_substr( $this->sortBy, 0, mb_strlen( $lookFor ) ) === $lookFor )
		{
			$compareSortBy = mb_substr( $this->sortBy, mb_strlen( $lookFor ) );
		}

		if( $this->sortBy AND in_array( $this->sortBy, $class::$databaseColumnMap ) )
		{
			$len = mb_strlen( $class::$databaseTable );
			$this->sortBy = ( mb_substr( $this->sortBy, 0, $len ) == $class::$databaseTable ) ? $this->sortBy : $class::$databaseTable . '.' . $this->sortBy;
		}

		$this->sortBy = in_array( $compareSortBy, $this->sortOptions ) ? $this->sortBy :
			( array_key_exists( $compareSortBy, $this->sortOptions ) ? $this->sortOptions[ $compareSortBy ] : $defaultSort );

		/* Callback? */
		if ( $this->advancedSearchCallback and !empty( $advancedSearchValues ) )
		{
			$obj = $this;
			$advancedSearchCallback = $this->advancedSearchCallback;
			$advancedSearchCallback( $obj, $advancedSearchValues );
		}

		/* What are we sorting by? */
		/* @var Item $class */
		/* @var array $databaseColumnMap */
		$sortBy = $this->sortBy . ' ' . ( mb_strtolower( $this->sortDirection ) == 'asc' ? 'asc' : 'desc' );
		if ( IPS::classUsesTrait( $class, 'IPS\Content\Pinnable' ) and $this->honorPinned )
		{
			$column = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['pinned'];
			$sortBy = "{$column} DESC, {$sortBy}";
		}
		
		/* Specify filter in where clause */
		$where = $this->where ? ( is_array( $this->where ) ? $this->where : array( $this->where ) ) : array();
		if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
		{
			$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
		}

		/* Check extensions for custom where clause */
		foreach( UiExtension::i()->run( $class, 'contentTableWhere', array( $this ) ) as $customWhere )
		{
			$where[] = $customWhere;
		}

		/* Get results */
		$this->count = $class::getItemsWithPermission( $where, $sortBy, NULL, $this->permCheck, $this->includeHiddenContent, 0, NULL, $this->joinContainer, $this->joinComments, $this->joinReviews, TRUE, $this->joins, ( $this->container and $this->limitByContainer ) ? $this->container : FALSE, TRUE, TRUE, TRUE, $this->showMovedLinks );
		$it = $class::getItemsWithPermission( $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ), $this->permCheck, $this->includeHiddenContent, 0, NULL, $this->joinContainer, $this->joinComments, $this->joinReviews, FALSE, $this->joins, ( $this->container and $this->limitByContainer ) ? $this->container : FALSE, TRUE, TRUE, TRUE, $this->showMovedLinks );
		$this->pages = ceil( $this->count / $this->limit );
		$rows = iterator_to_array( $it );
		$idField = $class::$databaseColumnId;
		$nodeClass = $class::$containerNodeClass ?? NULL;

		if ( isset( $this->getFirstComment ) and isset( $class::$databaseColumnMap['first_comment_id'] ) and $class::$firstCommentRequired  )
		{

			$commentIds = array();
			$archivedCommentIds = [];
			$firstCommentField = $class::$databaseColumnMap['first_comment_id'];

			foreach( $rows as $item )
			{
				/* Get us the archived comment ids*/
				if ( isset( $class::$archiveClass ) and method_exists( $class, 'isArchived' ) AND $item->isArchived() )
				{
					$archivedCommentIds[ $item->$idField ] = $item->$firstCommentField;
				}
				else
				{
					$commentIds[ $item->$idField ] = $item->$firstCommentField;
				}
			}
			$comments = array();

			if ( count( $archivedCommentIds ) )
			{
				$archivedCommentClass = $class::$archiveClass;
				$commentField = $archivedCommentClass::$databaseColumnId;

				foreach( $archivedCommentClass::getItemsWithPermission( array( Db::i()->in( $archivedCommentClass::$databasePrefix . $commentField, array_values( $archivedCommentIds ) ) ), NULL, $this->limit ) as $cObj )
				{
					$comments[ $cObj->$commentField ] = $cObj;
				}

				foreach( $rows as $id => $item )
				{
					if ( isset( $archivedCommentIds[ $item->$idField ] ) and isset( $comments[ $archivedCommentIds[ $item->$idField ] ] ) )
					{
						$rows[ $id ]->firstComment = $comments[ $archivedCommentIds[ $item->$idField ] ];
					}
				}
			}

			
			if ( count( $commentIds ) )
			{
				/* @var Comment $commentClass */
				$commentClass = $class::$commentClass;
				$commentField = $commentClass::$databaseColumnId;
				
				foreach( $commentClass::getItemsWithPermission( array( Db::i()->in( $commentClass::$databasePrefix . $commentField, array_values( $commentIds ) ) ), NULL, $this->limit ) as $cObj )
				{
					$comments[ $cObj->$commentField ] = $cObj;
				}

				foreach( $rows as $id => $item )
				{
					if ( isset( $commentIds[ $item->$idField ] ) and isset( $comments[ $commentIds[ $item->$idField ] ] ) )
					{
						$rows[ $id ]->firstComment = $comments[ $commentIds[ $item->$idField ] ];
					}
				}
			}
		}

		if ( $this->getFollowerCount and IPS::classUsesTrait( $class, 'IPS\Content\Followable' ) )
		{
			$followers = array();
			foreach( $class::followersCounts( $rows ) as $follow )
			{
				$followers[ $follow['follow_rel_id'] ] = $follow['count'];
			}
			
			foreach( $rows as $id => $item )
			{
				if ( isset( $followers[ $item->$idField ] ) )
				{
					$rows[ $id ]->followerCount = $followers[ $item->$idField ];
				}
				else
				{
					$rows[ $id ]->followerCount = 0;
				}
			}
		}

		if ( $this->getReactions and IPS::classUsesTrait( $class, 'IPS\Content\Reactable' ) )
		{
			/* Get all reactions */
			$allReputation = $class::getMany( 'allReactions', $rows );
			foreach( $rows as $id => $item )
			{
				if ( isset( $allReputation[ $item->$idField ] ) )
				{
					$rows[ $id ]->reputation = $allReputation[ $item->$idField ];
				}
			}
		}


		/* Pre load the contentPostedIn data to save separate DISTINCT queries later and a query for each unread item */
		if ( isset( $class::$databaseColumnMap['container'] ) )
		{
			$containerItemIds = array();
			foreach ( $rows as $item )
			{
				$colContainer = $class::$databaseColumnMap['container'];
				$colPrimary = $class::$databaseColumnId;

				$containerItemIds[$item->$colContainer][] = $item->$colPrimary;
			}
			$memberIds = array();
			$memberPostedIn = array();

			foreach ( $containerItemIds as $container => $ids )
			{
				try
				{
					$node = $nodeClass::load( $container );

					if ( IPS::classUsesTrait( $node, 'IPS\Node\Statistics' ) )
					{
						$this->contentPostedIn = array_merge( $node->contentPostedIn( NULL, $ids ), $this->contentPostedIn );

						foreach ( $node->authorsPostedIn( $ids ) as $memberId => $itemIds )
						{
							if ( $memberId > 0 )
							{
								$memberIds[$memberId] = $memberId;

								foreach ( $itemIds as $id )
								{
									$memberPostedIn[$id][] = $memberId;
								}
							}
						}
					}
				}
				catch ( OutOfRangeException ) { }
			}

			if ( count( $memberIds ) )
			{
				/* Get the groups */
				$groups = array();
				foreach( Db::i()->select( 'member_id, member_group_id, mgroup_others', 'core_members', array( Db::i()->in( 'member_id', $memberIds ) ) ) as $row )
				{
					$groups[ $row['member_id'] ] = array( 'primary' => $row['member_group_id'], 'secondary' => $row['mgroup_others'] );
				}

				$thisRow = array();
				foreach( $memberPostedIn as $itemId => $members )
				{
					foreach( $members as $m )
					{
						if ( isset( $groups[ $m ]['primary'] ) )
						{
							$thisRow[ $itemId ]['primary'][ $groups[ $m ]['primary'] ] = $groups[ $m ]['primary'];
						}

						if ( isset( $groups[ $m ]['secondary'] ) )
						{
							foreach( explode( ',', $groups[ $m ]['secondary'] ) as $secondary )
							{
								if ( $secondary )
								{
									$thisRow[$itemId]['secondary'][$secondary] = $secondary;
								}
							}
						}
					}
				}

				foreach( $rows as $id => $item )
				{
					$rows[$id]->groupsPosted = isset( $thisRow[$item->$idField] ) ? Group::postedIn( ($thisRow[$item->$idField]['primary'] ?? array()), ($thisRow[$item->$idField]['secondary'] ?? array()) ) : FALSE;
				}
			}
		}

		/* Pull in extra data */
		if ( method_exists( $class, 'tableGetRows' ) )
		{
			$class::tableGetRows( $rows );
		}
		
		if ( $this->callback != NULL and is_callable( $this->callback ) )
		{
			$rows = call_user_func( $this->callback, $rows );
		}

		/* Return */
		return $rows;
	}

	/**
	 * @brief	Return table filters
	 */
	public bool $showFilters	= TRUE;

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public function getFilters(): array
	{
		if( $this->showFilters === FALSE )
		{
			return array();
		}

		/* @var Item $class */
		$class = $this->class;

		if( method_exists( $class, 'getTableFilters' ) )
		{
			return $class::getTableFilters();
		}
		
		return array();
	}
	
	/**
	 * @brief	Disable moderation?
	 */
	public bool $noModerate = FALSE;
	
	/**
	 * Does the user have permission to use the multi-mod checkboxes?
	 *
	 * @param string|null $action		Specific action to check (hide/unhide, etc.) or NULL for a generic check
	 * @return	bool
	 */
	public function canModerate( string $action=NULL ): bool
	{
		if ( $this->noModerate )
		{
			return FALSE;
		}
		
		$class = $this->class;
		/* @var \IPS\Content $class */
		if ( $action )
		{
			return $class::modPermission( $action, Member::loggedIn(), $this->container );
		}
		else
		{
			return $class::canSeeMultiModTools( Member::loggedIn(), $this->container );
		}
	}

	/**
	 * What multimod actions are available
	 *
	 * @param object $item	Item
	 * @return	array
	 */
	public function multimodActions( object $item ): array
	{
		$return = array();
		
		if ( $item instanceof Item )
		{
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Pinnable' ) )
			{	
				if ( $item->mapped('pinned') and $item->canUnpin() )
				{
					$return[] = 'unpin';
				}
				elseif ( $item->canPin() )
				{
					$return[] = 'pin';
				}
			}
			
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Hideable' ) )
			{	
				if ( $item->hidden() === -1 and $item->canUnhide() )
				{
					$return[] = 'unhide';
				}
				elseif ( $item->hidden() === 1 )
				{
					if( $item->canUnhide() )
					{
						$return[] = 'approve';
					}

					if( $item->canHide() )
					{
						$return[] = 'hide';
					}
				}
				elseif ( $item->canHide() )
				{
					$return[] = 'hide';
				}
			}
			
			if ( IPS::classUsesTrait( $item, 'IPS\Content\Lockable' ) )
			{	
				if ( $item->locked() AND $item->canUnlock() )
				{
					$return[] = 'unlock';
				}
				elseif ( $item->canLock() )
				{
					$return[] = 'lock';
				}
			}
					
			if ( isset( $item::$databaseColumnMap['container'] ) )
			{
				if ( $item->canMove() )
				{
					$return[] = 'move';
				}
			}
			
			if ( $item->canMerge() )
			{
				$return[] = 'merge';
			}
			
			if ( $item->canDelete() )
			{
				$return[] = 'delete';
			}

			if( IPS::classUsesTrait( $item, Taggable::class ) and $item->canTag() )
			{
				$return[] = 'tag';
				$return[] = 'untag';
			}

			/* Do we have any custom actions? */
			$return	= array_merge( $return, $item->customMultimodActions() );
		}
		
		foreach ( $this->savedActions as $k => $v )
		{
			$return[] = "savedAction-{$k}";
		}
		
		return $return;		
	}

	/**
	 * What custom multimod actions are available
	 *
	 * @return	array
	 */
	public function customActions(): array
	{
		/* @var \IPS\Content $class */
		$class = $this->class;
		return $class::availableCustomMultimodActions();
	}
	
	/**
	 * Multimod
	 *
	 * @return	void
	 */
	protected function multimod() : void
	{
		if ( $this->noModerate )
		{
			return;
		}
		
		Session::i()->csrfCheck();

		/* Basic check that we selected something */
		if( !isset( Request::i()->moderate ) OR empty( Request::i()->moderate ) )
		{
			Output::i()->error( 'nothing_mm_selected', '1S330/1', 403, '' );
		}

		$class = $this->class;
		$params = array();

		/* Permission check for the items we have specific actions for here, modActions will take care of permissions for the rest */
		if( in_array( Request::i()->modaction, array( 'hide', 'move', 'merge', 'tag', 'untag' ) ) )
		{
			$options = array();
			$ids = array();
			foreach ( Request::i()->moderate as $id => $empty )
			{
				/* @var Item $class */
				$item = $class::load( $id );

				$action = ( Request::i()->modaction == 'untag' ) ? 'canTag' : 'can' . ucwords( Request::i()->modaction );
				if( $item->$action() )
				{
					$ids[] = $id;
					$options[ $id ] = $item->mapped('title');
					$descriptions[ $id ] = Member::loggedIn()->language()->addToStack( 'byline_merge', FALSE, array( 'htmlsprintf' => array( $item->author()->name,  DateTime::ts( $item->mapped('date') )->html() ) ) );
				}
			}

			/* The user doesn't have permission to perform the action on any of the content */
			if ( !count( $options ) )
			{
				throw new OutOfRangeException;
			}
		}
		
		/* Move: to where? */
		if ( Request::i()->modaction == 'move' )
		{
			/* The method will return an HTML string, or an array of parameters to pass to modAction */
			$params = $this->getMoveForm();

			/* This is the form instead */
			if( !is_array( $params ) )
			{
				return;
			}
		}

		/* Tags */
		if( Request::i()->modaction == 'tag' or Request::i()->modaction == 'untag' )
		{
			$params = $this->getTagForm();
			if( !is_array( $params ) )
			{
				return;
			}
		}
		
		/* Hide: ask for reason */
		if ( Request::i()->modaction == 'hide' )
		{
			$form = new Form( 'form', 'hide' );
			$form->class = 'ipsForm--vertical ipsForm--hide-reason';
			$form->hiddenValues['modaction']	= 'hide';
			$form->hiddenValues['moderate']	= Request::i()->moderate;
			$form->add( new Text( 'hide_reason' ) );
			if ( $values = $form->values() )
			{
				$params = $values['hide_reason'];
			}
			else
			{
				Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
				
				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Output::i()->output  );
				}
				else
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
				}
			}
		}
				
		/* Merge: what's the master? */
		if ( Request::i()->modaction == 'merge' )
		{
			if ( count( $options ) === 1 )
			{
				foreach ( $options as $id => $title )
				{
					/* @var Item $class */
					$item = $class::load( $id );

					$form = $item->mergeForm();

					if ( $values = $form->values() )
					{
						$item->mergeIn( array( $class::loadFromUrl( $values['merge_with'] ) ), $values['move_keep_link'] ?? FALSE);
					}
					else
					{
						Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );

						if ( Request::i()->isAjax() )
						{
							Output::i()->sendOutput( Output::i()->output );
						}
						else
						{
							Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
						}
					}
				}
			}
			else
			{
				$form = new Form( 'form', 'merge' );
				$form->class = 'ipsForm--vertical ipsForm--merge';
				$form->hiddenValues['modaction']	= 'merge';
				$form->hiddenValues['moderate']	= Request::i()->moderate;
				$form->add( new Radio( 'merge_master', NULL, TRUE, array( 'options' => $options, 'descriptions' => $descriptions, 'parse' => 'normal' ) ) );
				if ( isset( $class::$databaseColumnMap['moved_to'] ) )
				{
					$form->add( new Checkbox( 'move_keep_link' ) );
					
					if ( Settings::i()->topic_redirect_prune )
					{
						Member::loggedIn()->language()->words['move_keep_link_desc'] = Member::loggedIn()->language()->addToStack( '_move_keep_link_desc', FALSE, array( 'pluralize' => array( Settings::i()->topic_redirect_prune ) ) );
					}
				}
				if ( $values = $form->values() )
				{					
					$otherItems = array();
					foreach ( $ids as $id )
					{
						if ( $id != $values['merge_master'] )
						{
							$otherItem = $class::load( $id );
							if( $otherItem->canMerge() )
							{
								$otherItems[] = $otherItem;
							}
						}
					}
					
					$class::load( $values['merge_master'] )->mergeIn( $otherItems, $values['move_keep_link'] ?? FALSE);
				}
				else
				{
					Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
					
					if ( Request::i()->isAjax() )
					{
						Output::i()->sendOutput( Output::i()->output );
					}
					else
					{
						Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
					}
				}
			}
		}

		/* Handle Tags */
		elseif( Request::i()->modaction == 'tag' or Request::i()->modaction == 'untag' )
		{
			foreach( array_keys( Request::i()->moderate ) as $id )
			{
				try
				{
					$object = $class::loadAndCheckPerms( $id );

					$modified = false;
					$currentTags = $object->tags();
					if( $currentPrefix = $object->prefix() )
					{
						$currentTags['prefix'] = $currentPrefix;
					}

					foreach( $params[ $class::$formLangPrefix . 'tags' ] as $tag )
					{
						if( Request::i()->modaction == 'tag' )
						{
							if( !in_array( $tag, $currentTags ) )
							{
								$currentTags[] = $tag;
								$modified = true;
							}
						}
						else
						{
							$index = array_search( $tag, $currentTags );
							if( $index !== false )
							{
								unset( $currentTags[ $index ] );
								$modified = true;
							}
						}
					}

					if( $modified )
					{
						$object->setTags( $currentTags );
					}
				}
				catch( OutOfRangeException ){}
			}
		}
				
		/* Everything else: just do it */
		else
		{
			$containers = array();

			foreach ( array_keys( Request::i()->moderate ) as $id )
			{
				try
				{
					$object = $class::loadAndCheckPerms( $id );

					/* If this item is read, we need to re-mark it as such after moving */
					if( IPS::classUsesTrait( $object, 'IPS\Content\ReadMarkers' ) )
					{
						$unread = $object->unread();
					}

					/* Turn off updating of containers for each action and get container data */
					try
					{
						$object->skipContainerRebuild = TRUE;
						$containers[ $object->container()->_id ] = $object->container();
					}
					catch( BadMethodCallException ){}

					$object->modAction( Request::i()->modaction, Member::loggedIn(), $params);

					/* Turn back on updating of container data for each action. Also get container again in the event item was moved and it's new */
					try
					{
						$object->skipContainerRebuild = FALSE;
						$containers[ $object->container()->_id ] = $object->container();
					}
					catch( BadMethodCallException ){}

					/* Mark it as read */
					if( IPS::classUsesTrait( $object, 'IPS\Content\ReadMarkers' ) and Request::i()->modaction == 'move' AND $unread == 0 )
					{
						$object->markRead();
					}
				}
				catch ( Exception ) {}
			}

			/* Now update the containers in one go */
			foreach( $containers as $container )
			{
				$container->setLastComment();
				$container->setLastReview();
				$container->resetCommentCounts();
				$container->save();
			}
		}
		
		Output::i()->redirect( $this->baseUrl );
	}

	/**
	 * Get a form to tag or untag items
	 *
	 * @return array|string
	 */
	protected function getTagForm() : array|string
	{
		/* @var Item $class */
		$class = $this->class;
		if( !IPS::classUsesTrait( $class, Taggable::class ) )
		{
			throw new InvalidArgumentException;
		}

		$form = new Form;
		$form->class = 'ipsForm--vertical';
		$form->hiddenValues['modaction'] = Request::i()->modaction;
		$form->hiddenValues['moderate'] = Request::i()->moderate;

		$field = $class::tagsFormField( null, $this->container );
		$field->label = Member::loggedIn()->language()->addToStack( ( Request::i()->modaction == 'tag' ) ? 'add_single_tag' : 'remove_single_tag' );
		$form->add( $field );

		if( $values = $form->values() )
		{
			return $values;
		}
		else
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );

			if ( Request::i()->isAjax() )
			{
				Output::i()->sendOutput( Output::i()->output  );
			}
			else
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
			}
		}
	}

	/**
	 * Get the form to move items
	 *
	 * @return string|array
	 */
	protected function getMoveForm(): array|string
	{
		$class = $this->class;
		$params = array();

		$form = new Form( 'form', 'move' );
		$form->class = 'ipsForm--vertical ipsForm--move-content';
		$form->hiddenValues['modaction']	= 'move';
		$form->hiddenValues['moderate']	= Request::i()->moderate;

		/* @var Item $class */
		$currentContainer = $this->container;
		$form->add( new Node( 'move_to', NULL, TRUE, array(
			'class' => $class::$containerNodeClass,
			'url' => Request::i()->url()->setQueryString( 'modaction', 'move' ),
			'permissionCheck'	=> function( $node ) use ( $currentContainer, $class )
			{
				if( !$currentContainer or $currentContainer->id != $node->id )
				{
					try
					{
						/* If the item is in a club, only allow moving to other clubs that you moderate */
						if ( $currentContainer and IPS::classUsesTrait( $currentContainer, 'IPS\Content\ClubContainer' ) and $currentContainer->club()  )
						{
							return $class::modPermission( 'move', Member::loggedIn(), $node ) and $node->can( 'add' ) ;
						}
						
						if ( $node->can( 'add' ) )
						{
							return true;
						}
					}
					catch( OutOfBoundsException ) { }
				}
				
				return false;
			},
			'clubs'		=> TRUE
		) ) );
							
		if ( isset( $class::$databaseColumnMap['moved_to'] ) )
		{
			$form->add( new Checkbox( 'move_keep_link' ) );
			
			if ( Settings::i()->topic_redirect_prune )
			{
				Member::loggedIn()->language()->words['move_keep_link_desc'] = Member::loggedIn()->language()->addToStack('_move_keep_link_desc', FALSE, array( 'pluralize' => array( Settings::i()->topic_redirect_prune ) ) );
			}
		}
		
		if ( $values = $form->values() )
		{
			$params[] = $values['move_to'];
			$params[] = ( isset( $values['move_keep_link'] ) and $values['move_keep_link'] );

			return $params;
		}
		else
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
			
			if ( Request::i()->isAjax() )
			{
				Output::i()->sendOutput( Output::i()->output  );
			}
			else
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( Output::i()->title, Output::i()->output, array( 'app' => Dispatcher::i()->application->directory, 'module' => Dispatcher::i()->module->key, 'controller' => Dispatcher::i()->controller ) ) );
			}
		}
	}
	
	/**
	 * Return the table headers
	 *
	 * @param	array|NULL	$advancedSearchValues	Advanced search values
	 * @return	array
	 */
	public function getHeaders( array $advancedSearchValues=NULL ): array
	{
		return array();
	}
	
	/**
	 * Return the container
	 *
	 * @return	Model|null
	 */
	public function container(): ?Model
	{
		return $this->container;
	}

	/**
	 * Return the sort direction to use for links
	 *
	 * @note	Abstracted so other table helper instances can adjust as needed
	 * @param string $column		Sort by string
	 * @return	string|null [asc|desc]
	 */
	public function getSortDirection( string $column ): ?string
	{
		/* @var \IPS\Content $class */
		$class = $this->class;

		/* ID and Title columns should be ascending */
		if( isset( $class::$databaseColumnMap[ 'title' ] ) AND $column != $this->defaultSortBy AND ( $column == $class::$databaseColumnId OR $column == $class::$databaseColumnMap[ 'title' ] ) )
		{
			return 'asc';
		}

		/* Check extensions - is this a custom sort? */
		foreach( UiExtension::i()->run( $class, 'contentTableSortDirection', array( $this, $column ) ) as $direction )
		{
			if( $direction !== null )
			{
				return $direction;
			}
		}

		return parent::getSortDirection( $column );
	}
}