<?php

/**
 * @brief        ContentIndexExtension
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        6/29/2023
 */

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\Content\Reaction;
use IPS\Content\Review;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;

if (!defined('\IPS\SUITE_UNIQUE_KEY'))
{
	header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
	exit;
}

abstract class SearchContentAbstract
{
	/**
	 * @brief	Object being indexed
	 */
	protected Content|null $object = null;

	/**
	 * @brief	Item associated with the content (used for comments/reviews)
	 */
	protected Item|null $item = null;

	/**
	 * @brief	Base item class for the extension [REQUIRED]
	 */
	protected static ?string $itemClass = null;

	/**
	 * @brief	A classname applied to the search result block
	 */
	public static string $searchResultClassName = '';

	/**
	 * Set the content object that we are indexing
	 *
	 * @param Content $object
	 * @return void
	 */
	public function setObject( Content $object ) : void
	{
		$this->object = $object;
		$this->item = ( $object instanceof Comment ) ? $object->item() : $object;
	}

	/**
	 * Return all searchable classes in your application,
	 * including comments and/or reviews
	 *
	 * @return array
	 */
	abstract public static function supportedClasses() : array;

	/**
	 * Title for search index
	 *
	 * @return	string
	 */
	public function searchIndexTitle(): string
	{
		return $this->item->mapped( 'title' );
	}

	/**
	 * Content for search index
	 *
	 * @return	string
	 */
	public function searchIndexContent(): string
	{
		return $this->object->mapped('content');
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
		/* Intentionally left blank but child classes can override */
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
		$objectClass = $indexData['index_class'];

		/* Item details */
		$itemClass = $indexData['index_class'];
		if( is_subclass_of( $objectClass, Comment::class ) )
		{
			$itemClass = $objectClass::$itemClass;
		}

		if( IPS::classUsesTrait( $itemClass, 'IPS\Content\ReadMarkers' ) )
		{
			$unread = $itemClass::unreadFromData( NULL, $indexData['index_date_updated'], $indexData['index_date_created'], $indexData['index_item_id'], $indexData['index_container_id'], FALSE );
		}
		else
		{
			$unread = NULL;
		}

		$itemUrl = static::urlFromIndexData( $indexData, $itemData, 'getPrefComment' );

		/* Object URL */
		if( is_subclass_of( $objectClass, Comment::class ) )
		{
			if( is_subclass_of( $objectClass, Review::class ) )
			{
				$objectUrl = $itemUrl->setQueryString( array( 'do' => 'findReview', 'review' => $indexData['index_object_id'] ) );
				$showRepUrl = $itemUrl->setQueryString( array( 'do' => 'showReactionsReview', 'review' => $indexData['index_object_id'] ) );
			}
			else
			{
				$objectUrl = $itemUrl->setQueryString( array( 'do' => 'findComment', 'comment' => $indexData['index_object_id'] ) );
				$showRepUrl = $itemUrl->setQueryString( array( 'do' => 'showReactionsComment', 'comment' => $indexData['index_object_id'] ) );
			}
		}
		else
		{
			$objectUrl = $itemUrl;
			$showRepUrl = $itemUrl->setQueryString( 'do', 'showReactions' );
		}

		/* Articles language */
		$articles = static::articlesFromIndexData( $objectClass, $containerData );
		$summaryLanguage = static::searchResultSummaryLanguage( $authorData, $articles, $indexData, $itemData );

		/* Container details */
		$containerUrl = NULL;
		$containerTitle = NULL;
		if ( isset( $itemClass::$containerNodeClass ) )
		{
			$containerClass	= $itemClass::$containerNodeClass;
			$containerTitle	= $containerClass::titleFromIndexData( $indexData, $itemData, $containerData );
			$containerUrl	= $containerClass::urlFromIndexData( $indexData, $itemData, $containerData );
		}

		/* Reputation - if we are showing the total value, then we need to load them up and total up all the values */
		if ( Settings::i()->reaction_count_display == 'count' )
		{
			$repCount = 0;
			foreach( $reputationData AS $memberId => $reactionId )
			{
				try
				{
					$repCount += Reaction::load( $reactionId )->value;
				}
				catch( OutOfRangeException ) {}
			}
		}
		else
		{
			$repCount = count( $reputationData );
		}

		/* Snippet */
		$snippet = static::searchResultSnippet( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $view );

		if ( $template === NULL )
		{
			$template = array( Theme::i()->getTemplate( 'system', 'core', 'front' ), 'searchResult' );
		}

		/* Return */
		return $template( $indexData, $summaryLanguage, $authorData, $itemData, $unread, $asItem ? $itemUrl : $objectUrl, $itemUrl, $containerUrl, $containerTitle, $repCount, $showRepUrl, $snippet, $iPostedIn, $view, $canIgnoreComments, $reactions, static::$searchResultClassName );
	}

	/**
	 * Search Result Block
	 *
	 * @return	array
	 */
	public static function searchResultBlock(): array
	{
		return array( Theme::i()->getTemplate( 'widgets', 'core', 'front' ), 'streamItem' );
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
		return $view == 'expanded' ? Theme::i()->getTemplate( 'system', 'core', 'front' )->searchResultSnippet( $indexData, $itemData ) : '';
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
		if( $includeLinks )
		{
			$authorTemplate = Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $authorData['member_id'], $authorData['name'], $authorData['members_seo_name'], $authorData['member_group_id'] ?? Settings::i()->guest_group, NULL, $indexData['index_is_anon'] );
		}
		else
		{
			$authorTemplate = Theme::i()->getTemplate( 'global', 'core', 'front' )->userNameFromData( $authorData['name'], $authorData['member_group_id'] ?? Settings::i()->guest_group, NULL, $indexData['index_is_anon'] );
		}

		if( in_array( 'IPS\Content\Comment', class_parents( $indexData['index_class'] ) ) )
		{
			$itemClass = $indexData['index_class']::$itemClass;
			if( isset( $itemData['author'] ) )
			{
				if( $includeLinks )
				{
					$itemAuthorTemplate = Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $itemData['author']['member_id'], $itemData['author']['name'], $itemData['author']['members_seo_name'], $itemData['author']['member_group_id'] ?? Settings::i()->guest_group );
				}
				else
				{
					$itemAuthorTemplate = Theme::i()->getTemplate( 'global', 'core', 'front' )->userNameFromData( $itemData['author']['name'], $itemData['author']['member_group_id'] ?? Settings::i()->guest_group );
				}
			}

			if( in_array( 'IPS\Content\Review', class_parents( $indexData['index_class'] ) ) )
			{
				if( isset( $itemData['author'] ) AND isset( $itemAuthorTemplate ) )
				{
					return Member::loggedIn()->language()->addToStack( "user_other_activity_review", FALSE, array( 'sprintf' => array( $articles['definite'] ), 'htmlsprintf' => array( $authorTemplate, $itemAuthorTemplate ) ) );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack( "user_own_activity_review", FALSE, array( 'sprintf' => array( $articles['indefinite'] ), 'htmlsprintf' => array( $authorTemplate ) ) );
				}
			}
			else
			{
				if( $itemClass::$firstCommentRequired )
				{
					if( $indexData['index_title'] )
					{
						return Member::loggedIn()->language()->addToStack( "user_own_activity_item", FALSE, array( 'sprintf' => array( $articles['indefinite'] ), 'htmlsprintf' => array( $authorTemplate ) ) );
					}
					else
					{
						if( isset( $itemData['author'] ) AND isset( $itemAuthorTemplate ) )
						{
							return Member::loggedIn()->language()->addToStack( "user_other_activity_reply", FALSE, array( 'sprintf' => array( $articles['definite'] ), 'htmlsprintf' => array( $authorTemplate, $itemAuthorTemplate ) ) );
						}
						else
						{
							return Member::loggedIn()->language()->addToStack( "user_own_activity_reply", FALSE, array( 'sprintf' => array( $articles['indefinite'] ), 'htmlsprintf' => array( $authorTemplate ) ) );
						}
					}
				}
				else
				{
					if( isset( $itemData['author'] ) AND isset( $itemAuthorTemplate ) )
					{
						return Member::loggedIn()->language()->addToStack( "user_other_activity_comment", FALSE, array( 'sprintf' => array( $articles['definite'] ), 'htmlsprintf' => array( $authorTemplate, $itemAuthorTemplate ) ) );
					}
					else
					{
						return Member::loggedIn()->language()->addToStack( "user_own_activity_comment", FALSE, array( 'sprintf' => array( $articles['indefinite'] ), 'htmlsprintf' => array( $authorTemplate ) ) );
					}
				}
			}
		}
		else
		{
			if ( isset( $indexData['index_class']::$databaseColumnMap['author'] ) )
			{
				return Member::loggedIn()->language()->addToStack( "user_own_activity_item", FALSE, array( 'sprintf' => array( $articles['indefinite'] ), 'htmlsprintf' => array( $authorTemplate ) ) );
			}
			else
			{
				return Member::loggedIn()->language()->addToStack( "generic_activity_item", FALSE, array( 'sprintf' => array( $articles['definite_uc'] ) ) );
			}
		}
	}

	/**
	 * Return the container class to store in the search index
	 *
	 * @return Model|NULL
	 */
	public function searchIndexContainerClass(): Model|NULL
	{
		try
		{
			return $this->item->directContainer();
		}
		catch( BadMethodCallException ){}

		return null;
	}

	/**
	 * Get container ID for search index
	 *
	 * @return	int
	 */
	public function searchIndexContainer(): int
	{
		if( isset( $this->item::$databaseColumnMap['container'] ) )
		{
			return $this->item->mapped( 'container' );
		}

		return 0;
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
		try
		{
			return $this->item->directContainer()->searchIndexPermissions();
		}
		catch ( BadMethodCallException )
		{
			return '*';
		}
	}

	/**
	 * Get language articles for the given container
	 *
	 * @param 	string 		$itemClass 		The classname of this content
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 */
	public static function articlesFromIndexData( string $itemClass, ?array $containerData ): array
	{
		/* @var Content $itemClass */
		$indefiniteArticle = $itemClass::_indefiniteArticle( $containerData );
		$definiteArticle = $itemClass::_definiteArticle( $containerData );
		$definiteArticleUc = $itemClass::_definiteArticle( $containerData, NULL, array( 'ucfirst' => TRUE ) );

		return array( 'indefinite' => $indefiniteArticle, 'definite' => $definiteArticle, 'definite_uc' => $definiteArticleUc );
	}

	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	string|NULL	$action			Action
	 * @return    Url
	 */
	public static function urlFromIndexData( array $indexData, array $itemData, string|null $action = NULL ): Url
	{
		$objectClass = $indexData['index_class'];

		if( is_subclass_of( $objectClass, Comment::class ) )
		{
			$objectClass = $objectClass::$itemClass;
		}

		if( $action == 'getPrefComment' )
		{
			$pref = Member::loggedIn()->linkPref() ?: Settings::i()->link_default;

			switch( $pref )
			{
				case 'unread':
					$action = Member::loggedIn()->member_id ? 'getNewComment' : NULL;
					break;

				case 'last':
					$action = 'getLastComment';
					break;

				default:
					$action = NULL;
					break;
			}
		}
		elseif( !Member::loggedIn()->member_id AND $action == 'getNewComment' )
		{
			$action = NULL;
		}

		/* @var array $databaseColumnMap */
		$url = Url::internal( $objectClass::$urlBase . $indexData['index_item_id'], 'front', $objectClass::$urlTemplate, Friendly::seoTitle( $indexData['index_title'] ?: $itemData[ $objectClass::$databasePrefix . $objectClass::$databaseColumnMap['title'] ] ) );

		if( $action )
		{
			$url = $url->setQueryString( 'do', $action );
		}

		return $url;
	}

	/**
	 * Get title from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the author. Only includes columns returned by container::basicDataColumns()
	 * @return	string
	 */
	public static function titleFromIndexData( array $indexData, array $itemData, array|null $containerData ): string
	{
		return Member::loggedIn()->language()->addToStack( $indexData['index_container_class']::$titleLangPrefix . $indexData['index_container_id'] );
	}
}