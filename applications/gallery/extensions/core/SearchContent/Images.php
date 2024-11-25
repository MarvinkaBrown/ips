<?php
/**
 * @brief		SearchContent extension: Images
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		10 Jul 2023
 */

namespace IPS\gallery\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Search\SearchContentAbstract;
use IPS\Content\Search\Query;
use IPS\Dispatcher;
use IPS\gallery\Album;
use IPS\gallery\Album\Item;
use IPS\gallery\Application;
use IPS\gallery\Category;
use IPS\gallery\Image;
use IPS\gallery\Image\Comment;
use IPS\gallery\Image\Review;
use IPS\Theme;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Images
 */
class Images extends SearchContentAbstract
{
	/**
	 * Return all searchable classes in your application,
	 * including comments and/or reviews
	 *
	 * @return array
	 */
	public static function supportedClasses() : array
	{
		return array(
			Image::class,
			Comment::class,
			Review::class
		);
	}

	/**
	 * Give class a chance to inspect and manipulate search engine filters for streams
	 *
	 * @param	array 						$filters	Filters to be used for activity stream
	 * @param	Query	$query		Search query object
	 * @return	void
	 */
	public static function searchEngineFiltering( array &$filters, Query &$query ): void
	{
		/* Make sure our CSS is output */
		if( Dispatcher::hasInstance() )
		{
			Application::outputCss();
		}

		/* Loop through and see if we are also including albums */
		$includingAlbums = FALSE;

		if( !count( $filters ) )
		{
			$includingAlbums = TRUE;
		}
		else
		{
			foreach( $filters as $filter )
			{
				if( $filter->itemClass == Item::class )
				{
					$includingAlbums = TRUE;
				}
			}
		}

		if( $includingAlbums === TRUE )
		{
			if( count( $filters ) )
			{
				foreach( $filters as $k => $filter )
				{
					if( $filter->itemClass == Image::class )
					{
						/* container class can be category or album */
						$filter->containerClasses = array( Category::class );
						$filter->containerClassExclusions = array( Comment::class, Review::class );
					}
				}
			}
			else
			{
				$query->filterByContainerClasses( array( Album::class ), array( Comment::class, Review::class ) );
			}
		}
	}

	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions(): string
	{
		/* If this is a private album, only the author can view in search */
		if ( method_exists( $this->object, 'directContainer' ) and $this->object->directContainer() instanceof Album and $this->object->directContainer()->type != Album::AUTH_TYPE_PUBLIC )
		{
			if ( $this->object->member_id )
			{
				return "m{$this->object->member_id}";
			}

			return '';
		}

		return parent::searchIndexPermissions();
	}

	/**
	 * Get snippet HTML for search result display
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	string		$view			'expanded' or 'condensed'
	 * @return	callable
	 */
	public static function searchResultSnippet( array $indexData, array $authorData, array $itemData, array|null $containerData, array $reputationData, int|null $reviewRating, string $view ): string
	{
		if( $indexData['index_class'] == Image::class )
		{
			return Theme::i()->getTemplate( 'global', 'gallery', 'front' )->searchResultImageSnippet( $indexData, $itemData, ($itemData['extra'] ?? NULL), $itemData['image_small_file_name'], static::urlFromIndexData( $indexData, $itemData ), $view == 'condensed' );
		}
		else
		{
			return Theme::i()->getTemplate( 'global', 'gallery', 'front' )->searchResultCommentSnippet( $indexData, $itemData, $itemData['image_small_file_name'], static::urlFromIndexData( $indexData, $itemData ), $reviewRating, $view == 'condensed' );
		}
	}
}