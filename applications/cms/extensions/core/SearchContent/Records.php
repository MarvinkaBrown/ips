<?php
/**
 * @brief		SearchContent extension: Records
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		10 Jul 2023
 */

namespace IPS\cms\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\cms\Fields;
use IPS\cms\Records as BaseRecords;
use IPS\cms\Records\Comment;
use IPS\Content\Search\SearchContentAbstract;
use Exception;
use IPS\Db;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Records
 */
class Records extends SearchContentAbstract
{
	/**
	 * @brief Store generated search index titles for efficiency
	 */
	protected static array $searchIndexTitles = array();

	/**
	 * Return all searchable classes in your application,
	 * including comments and/or reviews
	 *
	 * @return array
	 */
	public static function supportedClasses() : array
	{
		$return = [];
		foreach( Databases::databases() as $database )
		{
			if( $database->search )
			{
				$return[] = 'IPS\cms\Records' . $database->id;
				if( $database->options['comments'] )
				{
					$return[] = 'IPS\cms\Records\Comment' . $database->id;
				}
				if( $database->options['reviews'] )
				{
					$return[] = 'IPS\cms\Records\Review' . $database->id;
				}
			}
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
		if( is_subclass_of( $indexData['index_class'], Comment::class ) )
		{
			$commentClass = $indexData['index_class'];
			/* Ensure that the comment title is formatted correctly */
			try
			{
				$databases  = Databases::databases();

				if ( ! isset( static::$searchIndexTitles[ $itemData['primary_id_field'] ] ) )
				{
					$fields     = '\IPS\cms\Fields' .  $commentClass::$customDatabaseId;

					/* @var	$fields	Fields */
					static::$searchIndexTitles[ $itemData['primary_id_field'] ] = $fields::load( $databases[ $commentClass::$customDatabaseId ]->field_title )->displayValue( $itemData['field_' . $databases[ $commentClass::$customDatabaseId ]->field_title ] );
				}

				$itemData['field_' . $databases[ $commentClass::$customDatabaseId ]->field_title ] = static::$searchIndexTitles[ $itemData['primary_id_field'] ];
			}
			catch ( Exception ) { }
		}

		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
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
		if( $this->object instanceof BaseRecords )
		{
			/* We don't want to index items in databases with search disabled */
			if( !$this->object::database()->search )
			{
				return '';
			}

			return $this->object->deleteLogPermissions();
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
		if( is_subclass_of( $indexData['index_class'], BaseRecords::class ) )
		{
			if ( $view == 'expanded' OR ( $view == 'condensed' AND isset( $itemData['record_image'] ) ) )
			{
				$url = static::urlFromIndexData( $indexData, $itemData );
				return Theme::i()->getTemplate( 'global', 'cms', 'front' )->recordResultSnippet( $indexData, $itemData, $url, $view == 'condensed' );
			}
			else
			{
				return '';
			}
		}

		return parent::searchResultSnippet( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $view );
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
		$itemClass = $indexData['index_class'];
		if( is_subclass_of( $indexData['index_class'], Comment::class ) )
		{
			$itemClass = $indexData['index_class']::$itemClass;
		}

		if( is_subclass_of( $itemClass, BaseRecords::class ) )
		{
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

			if ( !isset( $itemClass::$pagePath ) or empty( $itemClass::$pagePath ) )
			{
				$itemClass::$pagePath = Db::i()->select( 'page_full_path', 'cms_pages', array( 'page_id=?', $itemClass::database()->page_id ) )->first();
			}

			$recordSlug = !$itemData['record_static_furl'] ? $itemData['record_dynamic_furl']  . '-r' . $itemData['primary_id_field'] : $itemData['record_static_furl'];

			if ( $itemClass::database()->use_categories )
			{
				$url = Url::internal( "app=cms&module=pages&controller=page&path=" . $itemClass::$pagePath . '/' . ( $itemData['extra'] ? $itemData['extra'] . '/' : '' ) . $recordSlug, 'front', 'content_page_path', $recordSlug );
			}
			else
			{
				$url = Url::internal( "app=cms&module=pages&controller=page&path=" . $itemClass::$pagePath . '/' . $recordSlug, 'front', 'content_page_path', $recordSlug );
			}

			if( $action )
			{
				$url = $url->setQueryString( 'do', $action );
			}

			return $url;
		}

		return parent::urlFromIndexData( $indexData, $itemData, $action );
	}
}