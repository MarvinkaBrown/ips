<?php
/**
 * @brief		SearchContent extension: Pages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		10 Jul 2023
 */

namespace IPS\cms\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cms\Pages\Page;
use IPS\cms\Pages\PageItem;
use IPS\Content\Search\SearchContentAbstract;
use IPS\Db;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Text\DOMParser;
use IPS\Widget\Area;
use IPS\Xml\DOMDocument;
use UnderflowException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Pages
 */
class Pages extends SearchContentAbstract
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
			PageItem::class
		);
	}

	/**
	 * Title for search index
	 *
	 * @return	string
	 */
	public function searchIndexTitle(): string
	{
		$titles = array();
		foreach ( Lang::languages() as $lang )
		{
			$titles[] = $lang->get("cms_page_{$this->object->id}");
		}
		return implode( ' ', $titles );
	}

	/**
	 * Content for search index
	 *
	 * @return	string
	 */
	public function searchIndexContent(): string
	{
		if ( $this->object->type == 'builder' )
		{
			$content = array();
			foreach( Db::i()->select( '*', 'cms_page_widget_areas', array( 'area_page_id=?', $this->object->id ) ) as $widgetArea )
			{
				if( !$widgetArea['area_tree'] )
				{
					continue;
				}

				$area = new Area( json_decode( $widgetArea['area_tree'], true ), $widgetArea['area_area'] );
				foreach( $area->getAllWidgets() as $widget )
				{
					if ( $widget['app'] == 'cms' and $widget['key'] == 'Wysiwyg' and isset( $widget['configuration']['content'] ) )
					{
						$content[] = trim( $widget['configuration']['content'] );
					}
				}
			}

			return implode( ' ', $content );
		}
		else
		{
			/* Remove {tags="foo"} */
			$this->object->content = preg_replace( '#\{([a-z]+?=([\'"]).+?\\2 ?+)}#', '', $this->object->content );

			/* Convert {{}} logic into html tags */
			$this->object->content = preg_replace( '#{{(if|foreach)([^{]+?)}}#', '<ips$1 data="$2">', $this->object->content );
			$this->object->content = preg_replace( '#{{end(if|foreach)}}#', '</ips$1>', $this->object->content );

			/* Remove custom PHP {{$foo = $this->object->test();}} */
			$this->object->content = preg_replace( '#{{(.+?)}}#', '', $this->object->content );

			$source = new DOMDocument( '1.0', 'UTF-8' );
			$source->loadHTML( DOMDocument::wrapHtml( $this->object->content ) );

			/* And then remove the HTML logic */
			$domElemsToRemove = array();
			foreach ( $source->getElementsByTagname('ipsif') as $domElement )
			{
				$domElemsToRemove[] = $domElement;
			}

			foreach ( $source->getElementsByTagname('ipsforeach') as $domElement )
			{
				$domElemsToRemove[] = $domElement;
			}

			foreach( $domElemsToRemove as $domElement )
			{
				$domElement->parentNode->removeChild($domElement);
			}

			return DOMParser::getDocumentBodyContents( $source );
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
		try
		{
			return Db::i()->select( 'perm_view', 'core_permission_index', array( "app='cms' AND perm_type='pages' AND perm_type_id=?", $this->object->id ) )->first();
		}
		catch ( UnderflowException )
		{
			return '';
		}
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
		$indexData['index_title'] = Member::loggedIn()->language()->addToStack( 'cms_page_' . $indexData['index_item_id'] );
		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
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
		if ( Application::load('cms')->default AND $itemData['page_default'] AND !$itemData['page_folder_id'] )
		{
			/* No - that's easy */
			return Url::internal( '', 'front' );
		}
		else
		{
			return Url::internal( 'app=cms&module=pages&controller=page&path=' . $itemData['page_full_path'], 'front', 'content_page_path', array( $itemData['page_full_path'] ) );
		}
	}
}