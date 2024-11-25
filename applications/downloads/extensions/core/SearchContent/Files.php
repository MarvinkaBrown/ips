<?php
/**
 * @brief		SearchContent extension: Files
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		10 Jul 2023
 */

namespace IPS\downloads\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Search\SearchContentAbstract;
use IPS\downloads\File;
use IPS\downloads\File\Comment;
use IPS\downloads\File\Review;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Files
 */
class Files extends SearchContentAbstract
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
			File::class,
			Comment::class,
			Review::class
		);
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
		$screenshot = NULL;
		if ( isset( $itemData['extra'] ) )
		{
			$screenshot = $itemData['extra']['record_thumb'] ?? $itemData['extra']['record_location'];
		}

		if( $indexData['index_class'] == File::class )
		{
			$price = File::_price( $itemData['file_cost'], $itemData['file_nexus'] );

			return Theme::i()->getTemplate( 'global', 'downloads', 'front' )->searchResultFileSnippet( $indexData, $itemData, $screenshot, static::urlFromIndexData( $indexData, $itemData ), $price, $view == 'condensed' );
		}
		else
		{
			return Theme::i()->getTemplate( 'global', 'downloads', 'front' )->searchResultCommentSnippet( $indexData, $screenshot, static::urlFromIndexData( $indexData, $itemData ), $reviewRating, $view == 'condensed' );
		}
	}
}