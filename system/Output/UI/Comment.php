<?php

namespace IPS\Output\UI;

/* To prevent PHP errors (extending class does not exist) revealing path */


use IPS\Content\Comment as BaseComment;
use IPS\Content\Item as BaseItem;
use IPS\Helpers\Form\FormAbstract;
use IPS\Helpers\Menu\MenuItem;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


abstract class Comment
{
	/**
	 * This needs to be declared in any child classes as well
	 *
	 * @var ?string
	 */
	public static ?string $class = NULL;

	/**
	 * Can be used to add additional css classes to the comment
	 *
	 * @param BaseComment $comment
	 * @return string
	 */
	public function css( BaseComment $comment ): string
	{
		return '';
	}

	/**
	 * Can be used to add additional data attributes to the comment
	 *
	 * @param BaseComment $comment
	 * @return string
	 */
	public function dataAttributes( BaseComment $comment ): string
	{
		return '';
	}

	/**
	 * Can be used to attach additional content to the author panel
	 *
	 * @param BaseComment $comment
	 * @return string
	 */
	public function authorPanel( BaseComment $comment ): string
	{
		return '';
	}

	/**
	 * returns additional menu items for the comment menu
	 *
	 * @param BaseComment $comment
	 * @return array<string,MenuItem>
	 */
	public function menuItems( BaseComment $comment ): array
	{
		return [];
	}

	/**
	 * Add elements to the comment form
	 *
	 * @param BaseComment|null $comment
	 * @param BaseItem $item
	 * @return array<string,FormAbstract>
	 */
	public function formElements( ?BaseComment $comment, BaseItem $item ): array
	{
		return [];
	}

	/**
	 * Triggered after the comment form is saved
	 *
	 * @param BaseComment $comment
	 * @param array $values
	 * @return void
	 */
	public function formPostSave( BaseComment $comment, array $values ): void
	{

	}
}