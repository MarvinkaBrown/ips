<?php
/**
 * @brief		Tree Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Feb 2013
 */

namespace IPS\Helpers\Tree;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\IPS;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use Throwable;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Tree Table
 */
class Tree
{
	/**
	 * @brief	Title for tree table
	 */
	public ?string $title = '';
	
	/**
	 * @brief	URL where the tree table is displayed
	 */
	public mixed $url = NULL;
	
	/**
	 * @brief	Callback function to get the root rows
	 */
	public $getRoots;
	
	/**
	 * @brief	Callback function to get a single row by ID
	 */
	public $getRow;
	
	/**
	 * @brief	Callback function to get the parent ID for a row
	 */
	public $getRowParentId;

	/**
	 * @brief	Callback function to get the root buttons
	 */
	public $getRootButtons;
	
	/**
	 * @brief	Callback function to get the child rows for a row
	 */
	public $getChildren;
	
	/**
	 * @brief	Searchable?
	 */
	public bool $searchable = FALSE;
	
	/**
	 * @brief	If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 */
	protected bool $lockParents = FALSE;
	
	/**
	 * @brief	If true, root cannot be turned into sub-items, and other items cannot be turned into roots
	 */
	protected bool $protectRoots = FALSE;
	
	/**
	 * @brief	Number of roots to show per page (NULL for unlimited). In most cases this doesn't make sense, since it makes re-ordering impossible. But for trees which are not orderable and which may contain a lot of roots, you can set this value
	 */
	public ?int $rootsPerPage = NULL;

	/**
	 * @brief	If using $rootsPerPage, a callback function that returns the total number of roots
	 */
	public $getTotalRoots = NULL;

	/**
	 * Constructor
	 *
	 * @param mixed $url URL where the tree table is displayed
	 * @param string|null $title Tree Table title
	 * @param callable|null $getRoots Callback function to get the root rows
	 * @param callable|null $getRow Callback function to get a single row by ID
	 * @param callable|null $getRowParentId Callback function to get the parent ID for a row
	 * @param callable|null $getChildren Callback function to get the child rows for a row
	 * @param callback|null $getRootButtons Callback function to get the root buttons
	 * @param callback|bool $searchable Show the search bar?
	 * @param bool $lockParents If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 * @param bool $protectRoots If true, root cannot be turned into sub-items, and other items cannot be turned into roots
	 * @param int|null $rootsPerPage Number of roots to show per page (NULL for unlimited). In most cases this doesn't make sense, since it makes re-ordering impossible. But for trees which are not orderable and which may contain a lot of roots, you can set this value
	 * @param callback|null $getTotalRoots If using $rootsPerPage, a callback function that returns the total number of roots
	 */
	public function __construct( mixed $url, ?string $title, ?callable $getRoots, ?callable $getRow, ?callable $getRowParentId, ?callable $getChildren, ?callable $getRootButtons=NULL, callable|bool $searchable=FALSE, bool $lockParents=FALSE, bool $protectRoots=FALSE, int $rootsPerPage = NULL, callable $getTotalRoots = NULL )
	{
		$this->url = $url;
		$this->title = $title;
		$this->getRoots = $getRoots;
		$this->getRow = $getRow;
		$this->getRowParentId = $getRowParentId;
		$this->getChildren = $getChildren;
		$this->getRootButtons = $getRootButtons ?: function(){ return array(); };
		$this->searchable = $searchable;
		$this->lockParents = $lockParents;
		$this->protectRoots = $protectRoots;
		$this->rootsPerPage = $rootsPerPage;
		$this->getTotalRoots = $getTotalRoots;
	}
	
	/**
	 * Display Table
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{
			/* Get rows */
			$page = isset( Request::i()->page ) ? intval( Request::i()->page ) : 1;
			$root = NULL;
			$rootParent = NULL;

			if( !Request::i()->root )
			{
				$getRootsFunction = $this->getRoots;
				$rows = $getRootsFunction( $this->rootsPerPage ? array( ( $page - 1 ) * $this->rootsPerPage, $this->rootsPerPage ) : NULL );
			}
			else
			{
				$getChildrenFunction = $this->getChildren;
				$rows = $getChildrenFunction( Request::i()->root );

				if ( Request::i()->isAjax() )
				{
					Output::i()->sendOutput( Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, mt_rand() ) );
				}
				
				$getRowFunction = $this->getRow;
				$root = $getRowFunction( Request::i()->root, TRUE );
				$getRowParentIdFunction = $this->getRowParentId;
				$rootParent = $getRowParentIdFunction( Request::i()->root );
			}
			
			/* Pagination? */
			$pagination = '';
			if ( $this->rootsPerPage )
			{
				$getTotalRootsFunction = $this->getTotalRoots;
				$totalNumber = $getTotalRootsFunction();
				if ( $totalNumber )
				{
					$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $this->url, ceil( $totalNumber / $this->rootsPerPage ), $page, $this->rootsPerPage );
				}
			}
										
			/* Display */
			$getRootButtonsFunction = $this->getRootButtons;
			return Theme::i()->getTemplate( 'trees', 'core' )->template( $this->url, $this->title, $root, $rootParent, $rows, $getRootButtonsFunction(), $this->lockParents, $this->protectRoots, $this->searchable, $pagination );
		}
		catch ( Exception | Throwable $e )
		{
			IPS::exceptionHandler( $e );
		}
		return '';
	}
}