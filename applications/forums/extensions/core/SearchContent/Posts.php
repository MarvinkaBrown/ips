<?php
/**
 * @brief		SearchContent extension: Posts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		01 Jul 2023
 */

namespace IPS\forums\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Search\SearchContentAbstract;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Request;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Topics
 */
class Posts extends SearchContentAbstract
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
			Topic::class,
			Post::class
		);
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
		$topic = $this->object instanceof Post ? $this->object->item() : $this->object;

		$return = $topic->container()->searchIndexPermissions();

		if ( !$topic->container()->can_view_others )
		{
			/* If the search index permissions are empty, just return now because no one can see content in this forum */
			if( !$return )
			{
				return $return;
			}

			$return = $topic->container()->permissionsThatCanAccessAllTopics();

			if ( $topic->starter_id )
			{
				$return[] = "m{$topic->starter_id}";
			}

			$return = implode( ',', $return );
		}

		return $return;
	}

	/**
	 * Get HTML for search result display
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	bool		$iPostedIn		If the user has posted in the item
	 * @param	string		$view			'expanded' or 'condensed'
	 * @param	bool		$asItem	Displaying results as items?
	 * @param	bool		$canIgnoreComments	Can ignore comments in the result stream? Activity stream can, but search results cannot.
	 * @param	array|null	$template	Optional custom template
	 * @param	array		$reactions	Reaction Data
	 * @return	string
	 */
	public static function searchResult( array $indexData, array $authorData, array $itemData, array|null $containerData, array $reputationData, int|null $reviewRating, bool $iPostedIn, string $view, bool $asItem, bool $canIgnoreComments=FALSE, array|null $template=null, array $reactions=array() ): string
	{
		if( $indexData['index_class'] == Post::class )
		{
			/* Password protected */
			if (
				$containerData['password'] // There is a password
				and !Member::loggedIn()->inGroup( explode( ',', $containerData['password_override'] ) ) // We can't bypass it
				and (
					!isset( Request::i()->cookie[ 'ipbforumpass_' . $indexData['index_container_id'] ] )
					or
					!Login::compareHashes( md5( $containerData['password'] ), Request::i()->cookie[ 'ipbforumpass_' . $indexData['index_container_id'] ] )
				) // We don't have the correct password
			)
			{
				return Theme::i()->getTemplate( 'global', 'forums' )->searchNoPermission(
					Member::loggedIn()->language()->addToStack('no_perm_post_password'),
					Url::internal( Forum::$urlBase . $indexData['index_container_id'], 'front', Forum::$urlTemplate, array( $containerData[ Forum::$databasePrefix . Forum::$seoTitleColumn ] ) )->setQueryString( 'topic', $indexData['index_item_id'] )
				);
			}
		}

		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
	}
}