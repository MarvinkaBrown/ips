<?php
/**
 * @brief		Content Filter for Search Queries
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 Jul 2015
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Filter for Search Queries
 */
class ContentFilter
{
	/**
	 * @brief	Item class
	 */
	public string $itemClass;
	
	/**
	 * @brief	Classes to include
	 */
	public array $classes = array();

	/**
	 * @brief	Container classes to include
	 * @note	NULL means not to filter by container classes
	 */
	public array|null $containerClasses = NULL;

	/**
	 * @brief	Classes to include when filtering by container classes
	 * @note	NULL means no special overrides
	 */
	public array|null $containerClassExclusions = NULL;
	
	/**
	 * @brief	Container ID filter
	 */
	public bool|null $containerIdFilter = NULL;
	
	/**
	 * @brief	Container IDs
	 */
	public array $containerIds = array();
	
	/**
	 * @brief	Item ID filter
	 */
	public bool|null $itemIdFilter = NULL;
	
	/**
	 * @brief	Item IDs
	 */
	public array $itemIds = array();
	
	/**
	 * @brief	Object ID filter
	 */
	public bool|null $objectIdFilter = NULL;
	
	/**
	 * @brief	Object IDs
	 */
	public array $objectIds = array();
	
	/**
	 * @brief	Minimum comments
	 */
	public int $minimumComments = 0;
	
	/**
	 * @brief	Minimum reviews
	 */
	public int $minimumReviews = 0;
	
	/**
	 * @brief	Minimum views
	 */
	public int $minimumViews = 0;
	
	/**
	 * @brief	Only include results which are the first comment on an item requiring a comment
	 */
	public bool $onlyFirstComment = FALSE;
	
	/**
	 * @brief	Only include results which are the last comment on an item requiring a comment
	 */
	public bool $onlyLastComment = FALSE;
			
	/**
	 * Constructor
	 *
	 * @param	string	$itemClass			The item class
	 * @param	bool	$includeItems		Include items in results?
	 * @param	bool	$includeComments	Include comments in results?
	 * @param	bool	$includeReviews		Include reviews in results?
	 * @return	static
	 */
	public static function init( string $itemClass, bool $includeItems=TRUE, bool $includeComments=TRUE, bool $includeReviews=TRUE ): static
	{		
		$obj = new static;
		$obj->itemClass = $itemClass;
		
		if ( $includeItems and ( !isset( $itemClass::$firstCommentRequired ) OR !$itemClass::$firstCommentRequired ) )
		{
			$obj->classes[] = $itemClass;
		}
		
		if ( $includeComments and isset( $itemClass::$commentClass ) )
		{
			$obj->classes[] = $itemClass::$commentClass;
		}
		
		if ( $includeReviews and isset( $itemClass::$reviewClass ) )
		{
			$obj->classes[] = $itemClass::$reviewClass;
		}
		
		return $obj;
	}
	
	/**
	 * Constructor
	 * init() adds in review classes, comment classes, etc. But if we want to filter based on just the comment class, we need this method
	 *
	 * @param	string	$class				The  class
	 * @return	static
	 */
	public static function initWithSpecificClass( string $class ): static
	{		
		$obj = new static;
		$obj->classes = array( $class );
		
		return $obj;
	}
	
	/**
	 * Only include results in containers
	 *
	 * @param	array	$ids	Acceptable container IDs
	 * @return	static	(for daisy chaining)
	 */
	public function onlyInContainers( array $ids ): static
	{
		$this->containerIdFilter = TRUE;
		$this->containerIds = $ids;
		
		return $this;
	}
	
	/**
	 * Exclude results in containers
	 *
	 * @param	array	$ids	Acceptable container IDs
	 * @return	static	(for daisy chaining)
	 */
	public function excludeInContainers( array $ids ): static
	{
		$this->containerIdFilter = FALSE;
		$this->containerIds = $ids;
		
		return $this;
	}
	
	/**
	 * Only include results in items
	 *
	 * @param	array	$ids	Acceptable item IDs
	 * @return	static	(for daisy chaining)
	 */
	public function onlyInItems( array $ids ): static
	{
		$this->itemIdFilter = TRUE;
		$this->itemIds = $ids;
		
		return $this;
	}
	
	/**
	 * Only include results with specific IDs
	 *
	 * @param	array	$ids	Acceptable object IDs
	 * @return	static	(for daisy chaining)
	 */
	public function onlyInIds( array $ids ): static
	{
		$this->objectIdFilter = TRUE;
		$this->objectIds = $ids;
		
		return $this;
	}
	
	/**
	 * Exclude results in items
	 *
	 * @param	array	$ids	Acceptable container IDs
	 * @return	static	(for daisy chaining)
	 */
	public function excludeInItems( array $ids ): static
	{
		$this->itemIdFilter = FALSE;
		$this->itemIds = $ids;
		
		return $this;
	}
	
	/**
	 * Set minimum number of comments
	 *
	 * @param	int	$minimumComments	The minimum number of comments
	 * @return	static	(for daisy chaining)
	 */
	public function minimumComments( int $minimumComments ): static
	{
		$this->minimumComments = $minimumComments;
		
		return $this;
	}
	
	/**
	 * Set minimum number of reviews
	 *
	 * @param	int	$minimumReviews	The minimum number of reviews
	 * @return	static	(for daisy chaining)
	 */
	public function minimumReviews( int $minimumReviews ): static
	{
		$this->minimumReviews = $minimumReviews;
		
		return $this;
	}
	
	/**
	 * Set minimum number of views
	 *
	 * @param	int	$minimumViews	The minimum number of views
	 * @return	static	(for daisy chaining)
	 */
	public function minimumViews( int $minimumViews ): static
	{
		$this->minimumViews = $minimumViews;
		
		return $this;
	}
	
	/**
	 * Only include results which are the first comment on an item requiring a comment
	 *
	 * @return	static	(for daisy chaining)
	 */
	public function onlyFirstComment(): static
	{
		$this->onlyFirstComment = TRUE;
		
		return $this;
	}
	
	/**
	 * Only include results which are the last comment on an item requiring a comment
	 *
	 * @return	static	(for daisy chaining)
	 */
	public function onlyLastComment(): static
	{
		$this->onlyLastComment = TRUE;
		
		return $this;
	}
}