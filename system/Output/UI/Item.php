<?php

namespace IPS\Output\UI;

/* To prevent PHP errors (extending class does not exist) revealing path */


use IPS\Content\Item as BaseItem;
use IPS\Helpers\Badge\Icon;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Table\Content;
use IPS\Node\Model;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


abstract class Item
{
	/**
	 * This needs to be declared in any child classes as well
	 *
	 * @var string|null
	 */
	public static ?string $class = NULL;

	/**
	 * Can be used to add additional css classes to the item
	 *
	 * @param BaseItem $item
	 * @return string
	 */
	public function css( BaseItem $item ): string
	{
		return '';
	}

	/**
	 * Can be used to add additional data attributes to the item
	 *
	 * @param BaseItem $item
	 * @return string
	 */
	public function dataAttributes( BaseItem $item ): string
	{
		return '';
	}

	/**
	 * returns additional menu items for the moderation menu
	 *
	 * @param BaseItem $item
	 * @return array
	 */
	public function menuItems( BaseItem $item ): array
	{
		return [];
	}

    /**
     * Returns additional badge icons to display in the header
     *
     * @param BaseItem $item
     * @return array<Icon>
     */
    public function badges( BaseItem $item ) : array
    {
        return [];
    }

	/**
	 * Add elements to the item form.
     * This method returns all elements that will be added.
     * By default, all elements will be added in order at the end of the form.
     * To specify placement within the form, @see FormAbstract::setPosition().
	 *
	 * @param BaseItem|null $item
	 * @param Model|null $container
	 * @return array<string,FormAbstract>
	 */
	public function formElements( ?BaseItem $item, ?Model $container ): array
	{
		return [];
	}

	/**
	 * Called after the Item form is saved
	 *
	 * @param BaseItem $item
	 * @param array $values
	 * @return void
	 */
	public function formPostSave( BaseItem $item, array $values ): void
	{

	}

	/**
	 * Display content in the contextual sidebar
	 *
	 * @param BaseItem $item
	 * @return string
	 */
	public function sidebar( BaseItem $item ) : string
	{
		return "";
	}

	/**
	 * Add custom sort options to the content table for this class
	 * @note $item will ALWAYS be null.
	 *
	 * @param BaseItem|null $item
	 * @param Content $table
	 * @return array
	 */
	public function contentTableSortOptions( ?BaseItem $item, Content $table ) : array
	{
		return [];
	}

	/**
	 * Return the sort direction for the selected sort option,
	 * or NULL to use the default parent method.
	 *
	 * @note $item will ALWAYS be null.
	 *
	 * @param BaseItem|null $item
	 * @param Content $table
	 * @param string $sortBy
	 * @return string|null
	 */
	public function contentTableSortDirection( ?BaseItem $item, Content $table, string $sortBy ) : string|null
	{
		return null;
	}

	/**
	 * Add custom filters to the content table for this class
	 * @note $item will ALWAYS be null.
	 *
	 * @param BaseItem|null $item
	 * @param Content $table
	 * @return array
	 */
	public function contentTableFilters( ?BaseItem $item, Content $table ) : array
	{
		return [];
	}

	/**
	 * Add custom conditions to the where clause for a content table.
	 * @note $item will ALWAYS be null.
	 *
	 * @param BaseItem|null $item
	 * @param Content $table
	 * @return array
	 */
	public function contentTableWhere( ?BaseItem $item, Content $table ) : array
	{
		return [];
	}
}