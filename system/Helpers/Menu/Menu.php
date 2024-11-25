<?php

namespace IPS\Helpers;


use IPS\Helpers\Menu\MenuItem;
use IPS\Helpers\Menu\Separator;
use IPS\Helpers\Menu\TitleField;
use IPS\Member;
use IPS\Theme;

class Menu
{
	/**
	 * Menu ID
	 * @var string
	 */
	public string $id;

	/**
	 * Menu Icon
	 * 
	 * @var string|null
	 */
	public ?string $icon;

	/**
	 * Menu Name
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Menu Elements(items)
	 * 
	 * @var array
	 */
	public array $elements = [];

	/**
	 * additional CSS CLasses
	 * 
	 * @var string
	 */
	public string $css = '';

	/**
	 * WHere should the menu appear
	 * 
	 * @var string|mixed|null
	 */
	public ?string $appendTo = NULL;

	/**
	 * Menu size - auto|normal|narrow
	 * @var string
	 */
	public string $menuType = 'auto';
	
	/**
	 * @var string Extra HTML to add before the menu
	 */
	public string $extraHtmlBeforeLinks = '';

	/**
	 * @var string Extra HTML to add after the menu
	 */
	public string $extraHtmlAfterLinks = '';

	/**
	 * @var string Override the default menu button content with custom HTML
	 */
	public string $customLinkHtml = '';
	
	/**
	 * Show the menu caret icon
	 * 
	 * @var false|string|void
	 */
	public bool $showCaret = TRUE;

	/**
	 * @var string|null
	 */
	public ?string $tooltip = NULL;

	/**
	 * @breif 	If this is true, menus with only one element will show as a button
	 * @var bool
	 */
	public bool $shrinkToButton = true;

	/**
	 * @param string $name
	 * @param string|null $icon
	 * @param string $css
	 * @param null $appendTo
	 * @param string|null $tooltip
	 */
	public function __construct( string $name, ?string $icon = NULL, string $css = 'ipsButton ipsButton--text', $appendTo = NULL, ?string $tooltip = NULL )
	{
		$this->elements = [];
		$this->icon = $icon;
		$this->name = $name;
		$this->id = 'el' . $name;
		$this->css = $css;
		$this->appendTo = $appendTo;
		$this->tooltip = $tooltip;
	}

	/**
	 * Add a menu item to the stack
	 * 
	 * @param MenuItem $link
	 * @param string|null $after
	 * @return $this
	 */
	public function add( MenuItem $link, ?string $after=null ): self
	{
		$inserted = false;
		$after = $after ?? $link->position;
		if( $after !== null )
		{
			$elements = [];
			foreach( $this->elements as $key => $element )
			{
				/* @var MenuItem $element */
				$elements[ $key ] = $element;
				if( $element->menuItem == $after )
				{
					$elements[ $link->menuItem ] = $link;
					$inserted = true;
				}
			}
			$this->elements = $elements;
		}

		/* Just in case the key did not exist */
		if( !$inserted )
		{
			$this->elements[ $link->menuItem ] = $link;
		}

		return $this;
	}

	/**
	 * Getter
	 * 
	 * @param $key
	 * @return string|void
	 */
	public function __get( $key )
	{
		if ( $key == 'title' )
		{
			return Member::loggedIn()->language()->addToStack( $this->name );
		}
	}

	/**
	 * Adds a seperator element to the stack
	 * 
	 * @return $this
	 */
	public function addSeparator(): self
	{
		$this->elements[] = new Separator;
		return $this;
	}

	/**
	 * Adds a title field to the stack
	 * 
	 * @param string $title
	 * @return $this
	 */
	public function addTitleField( string $title ): self
	{
		$this->elements[] = new TitleField( $title );
		return $this;
	}

	/**
	 * Adds HTML code to the stack
	 * @param string $html
	 * @return $this
	 */
	public function addHtml(string $html ): self
	{
		$this->elements[] =  $html;
		return $this;
	}

	/**
	 * Returns all menu elements
	 * 
	 * @return array
	 */
	public function getLinks(): array
	{
		return $this->elements;
	}

	/**
	 * Returns a row wrapper which can contain custom HTML code
	 * @param string $content
	 * @return string
	 */
	public function rowWrapper( string $content ): string
	{
		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->rowWrapper( $content );
	}

	/**
	 * Returns the link
	 * 
	 * @return string
	 */
	public function linkHtml(): string
	{
		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->link( $this );
	}

	/**
	 * Returns the parsed menu content
	 * 
	 * @return string
	 */
	public function contentHtml(): string
	{
		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->content( $this );
	}

	/**
	 * Returns the parsed menu
	 * @return string
	 */
	public function __toString(): string
	{
		/* If we have no elements, show nothing */
		if( !$this->hasContent() )
		{
			return '';
		}

		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->menu( $this );
	}

	/*
	 * Do we have any elements in this menu?
	 */
	public function hasContent(): bool
	{
		return count( $this->elements ) > 0;
	}

	/**
	 * Move an item to the top of the menu
	 *
	 * @param $item
	 * @return void
	 */
	public function moveToStart( $item ): void
	{
		$currentElements = $this->elements;
		$newElements = [];

		foreach( $currentElements as $key => $row )
		{
			if( $row instanceof Menu\Link and $item->id == $row->id )
			{
				$newElements[ $key ] = $item;
				unset( $currentElements[ $key ] );
			}
		}

		$this->elements = array_merge( $newElements, $currentElements );
	}
}