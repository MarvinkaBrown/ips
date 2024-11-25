<?php

namespace  IPS\Helpers\Menu;


use IPS\Theme;

class TitleField extends MenuItem
{
	/**
	 * Sets the title
	 * 
	 * @param string $title
	 */
	public function __construct( string $title )
	{
		$this->title = $title;
	}

	public function __toString(): string
	{
		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->titleField( $this );
	}
}