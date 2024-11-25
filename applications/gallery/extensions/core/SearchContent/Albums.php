<?php
/**
 * @brief		SearchContent extension: Albums
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
use IPS\gallery\Album\Comment;
use IPS\gallery\Album\Item;
use IPS\gallery\Album\Review;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Albums
 */
class Albums extends SearchContentAbstract
{
	/**
	 * @brief	A classname applied to the search result block
	 */
	public static string $searchResultClassName = 'cGalleryAlbumSearchResult';

	/**
	 * Return all searchable classes in your application,
	 * including comments and/or reviews
	 *
	 * @return array
	 */
	public static function supportedClasses() : array
	{
		return array(
			Item::class,
			Comment::class,
			Review::class
		);
	}

	/**
	 * Return the language string key to use in search results
	 *
	 * @note Normally we show "(user) posted a (thing) in (area)" but sometimes this may not be accurate, so this is abstracted to allow
	 *	content classes the ability to override
	 * @param	array 		$authorData		Author data
	 * @param	array 		$articles		Articles language strings
	 * @param	array 		$indexData		Search index data
	 * @param	array 		$itemData		Data about the item
	 * @param   bool        $includeLinks   Include links to member profile
	 * @return	string
	 */
	public static function searchResultSummaryLanguage( array $authorData, array $articles, array $indexData, array $itemData, bool $includeLinks = TRUE ): string
	{
		if( $indexData['index_class'] == Item::class )
		{
			return Member::loggedIn()->language()->addToStack( "album_user_own_activity_item", FALSE, array( 'sprintf' => array( $articles['indefinite'] ), 'htmlsprintf' => array( Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $authorData['member_id'], $authorData['name'], $authorData['members_seo_name'], $authorData['member_group_id'] ?? Settings::i()->guest_group ) ) ) );
		}

		return parent::searchResultSummaryLanguage( $authorData, $articles, $indexData, $itemData, $includeLinks );
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
		$images	= ( isset( $itemData['extra'] ) AND count( $itemData['extra'] ) ) ? $itemData['extra'] : array();

		if( $indexData['index_class'] == Item::class )
		{
			return Theme::i()->getTemplate( 'global', 'gallery', 'front' )->searchResultAlbumSnippet( $indexData, $itemData, $images, static::urlFromIndexData( $indexData, $itemData ), $view == 'condensed' );
		}
		else
		{
			return Theme::i()->getTemplate( 'global', 'gallery', 'front' )->searchResultAlbumCommentSnippet( $indexData, $itemData, $images, static::urlFromIndexData( $indexData, $itemData ), $reviewRating, $view == 'condensed' );
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
		if( $this->object instanceof Item )
		{
			return $this->object->asNode()->searchIndexPermissions();
		}

		return parent::searchIndexPermissions();
	}
}