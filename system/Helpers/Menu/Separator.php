<?php

namespace  IPS\Helpers\Menu;

use IPS\Theme;

class Separator extends MenuItem
{
	public function __toString(): string
	{
		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->separator( );
	}
}
