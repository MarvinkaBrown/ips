<?php

namespace IPS\Output\UI;

/* To prevent PHP errors (extending class does not exist) revealing path */


use IPS\Helpers\Form\FormAbstract;
use IPS\Node\Model;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


abstract class Node
{
	/**
	 * This needs to be declared in any child classes as well
	 *
	 * @var string|null
	 */
	public static ?string $class = NULL;

	/**
	 * Can be used to add additional css classes to the node
	 * 
	 * @param Model $node
	 * @return array
	 */
	public function css( Model $node ): array
	{
		return [];
	}

	/**
	 * Can be used to add additional data attributes to the node
	 * 
	 * @param Model $node
	 * @return string
	 */
	public function dataAttributes( Model $node ): string
	{
		return '';
	}

	/**
	 * Can be used to add buttons to the ACP tree row
	 * and to the view page in the ACP
	 * 
	 * @param Model $node
	 * @return array
	 */
	public function rowButtons( Model $node ): array
	{
		return [];
	}

	/**
	 * Returns additional HTML to be displayed in the ACP tree row
	 *
	 * @param Model $node
	 * @return string
	 */
	public function rowHtml( Model $node ) : string
	{
		return '';
	}

	/**
	 * Return a badge to be displayed in the ACP tree row
	 *
	 * @param Model $node
	 * @return null|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function rowBadge( Model $node ) : array|null
	{
		return null;
	}

	/**
	 * Add elements to the Node form
     * This method returns all elements that will be added.
     * By default, all elements will be added in order at the end of the form.
	 * To specify placement within the form, @see FormAbstract::setPosition().
	 *
	 * @param Model $node
	 * @return array<string,FormAbstract>
	 */
	public function formElements( Model $node ): array
	{
		return [];
	}

	/**
	 * Called after the Node form is saved
	 *
	 * @param Model $node
	 * @param array $values
	 * @return void
	 */
	public function formPostSave( Model $node, array $values ): void
	{

	}
}