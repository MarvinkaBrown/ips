<?php
/**
 * @brief		SearchContent extension: Packages
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		10 Jul 2023
 */

namespace IPS\nexus\extensions\core\SearchContent;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Content\Search\SearchContentAbstract;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Money;
use IPS\nexus\Package;
use IPS\nexus\Package\Item;
use IPS\nexus\Tax;
use IPS\Request;
use IPS\Theme;
use UnderflowException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SearchContent extension: Packages
 */
class Packages extends SearchContentAbstract
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
			Item::class
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
			try
			{
				$titles[] = $lang->get( "nexus_package_{$this->object->id}" );
			}
			catch( UnderflowException ){}
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
		$descriptions = array();
		foreach ( Lang::languages() as $lang )
		{
			try
			{
				$descriptions[] = $lang->get("nexus_package_{$this->object->id}_desc");
			}
			catch ( UnderflowException ) { }
		}
		return implode( ' ', $descriptions );
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
		$indexData['index_title'] = Member::loggedIn()->language()->addToStack( 'nexus_package_' . $indexData['index_item_id'] );
		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
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
		$url = static::urlFromIndexData( $indexData, $itemData );

		/* Work out the price to display */
		$customer = Customer::loggedIn();
		$currency = ( isset( Request::i()->cookie['currency'] ) and in_array( Request::i()->cookie['currency'], Money::currencies() ) ) ? Request::i()->cookie['currency'] : $customer->defaultCurrency();
		$renewOptions = $itemData['p_renew_options'] ? json_decode( $itemData['p_renew_options'], TRUE ) : array();
		$priceInfo = Package::fullPriceInfoFromData( $customer, $currency, $indexData['index_item_id'], $itemData['p_base_price'] ? json_decode( $itemData['p_base_price'], TRUE ) : [], $itemData['p_discounts'] ? json_decode( $itemData['p_discounts'], TRUE ) : [], $renewOptions, $itemData['p_stock'], $itemData['extra']['tax'] ? Tax::constructFromData( $itemData['extra']['tax'] ) : NULL, $itemData['p_initial_term'] ? new DateInterval("P{$itemData['p_initial_term']}") : NULL );

		/* Display */
		return Theme::i()->getTemplate( 'global', 'nexus', 'front' )->searchResultProductSnippet( $indexData, $itemData, $itemData['extra']['image'] ?? NULL, $url, $priceInfo, $view == 'condensed' );
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
		return $this->object->store ? $this->object->member_groups : '';
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
		return Url::internal( "app=nexus&module=store&controller=product&id={$indexData['index_item_id']}", 'front', 'store_product', Member::loggedIn()->language()->addToStack( 'nexus_package_' . $indexData['index_item_id'], FALSE, array( 'seotitle' => TRUE ) ) );
	}
}