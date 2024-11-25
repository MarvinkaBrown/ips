<?php

namespace  IPS\Helpers\Menu;


abstract class MenuItem
{
	public string $title = '';
	public ?string $icon = null;

	public string $menuItem = '';

	public ?string $position = null;


	abstract function __toString(): string;
}
