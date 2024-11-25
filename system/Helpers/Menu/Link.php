<?php

namespace  IPS\Helpers\Menu;

use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;

class Link extends MenuItem
{
	/**
	 * Target
	 * 
	 * @var Url|string
	 */
	public Url|string $url = '';
	public string $extraHtml = '';
	public string $identifier = '';
	public string $notificationIcon = '';
	public array $dataAttributes = [];
	public array $wrapperDataAttributes = [];
	public ?string $id = null;
	public ?string $css = null;

	/**
	 * Add an attribute to the link
	 *
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function addAttribute( string $key, string $value = '' ): self
	{
		$this->dataAttributes[ $key ] = $value;
		return $this;
	}

	/**
	 * Adds all necessary attributes for a confirmation box
	 * @param $desc
	 * @return $this
	 */
	public function requiresConfirm( $desc ): self
	{
		$this->addAttribute( 'data-confirm');
		$this->addAttribute( 'data_confirmSubmessage', Member::loggedIn()->language()->addToStack( $desc ) );
		return $this;
	}

	/**
	 * Adds all necessary attributes for a dialog
	 * 
	 * @param string|NULL $title
	 * @param string $size
	 * @param bool $destruct
	 * @param string|NULL $contentSelector
	 * @param bool $remoteSubmit
	 * @return $this
	 */
	public function opensDialog( string $title = NULL, string $size = 'medium',  bool $destruct = FALSE, string $contentSelector = NULL, bool $remoteSubmit = FALSE ): self
	{
		$this->addAttribute( 'data-ipsDialog')
			 ->addAttribute( 'data-ipsDialog-size', $size )
			 ->addAttribute( 'data-ipsDialog-title', Member::loggedIn()->language()->addToStack( $title ?? $this->title ) );

		if( $contentSelector )
		{
			$this->addAttribute( 'data-ipsDialog-content', $contentSelector );
		}

		if( $destruct )
		{
			$this->addAttribute( 'data-ipsDialog-destructOnClose', 'true' );
		}

		if( $remoteSubmit )
		{
			$this->addAttribute( 'data-ipsDialog-remoteSubmit', 'true' );
		}
		
		return $this;
	}

	/**
	 * @param Url|string $url
	 * @param string $languageString
	 * @param string $css
	 * @param array $dataAttributes
	 * @param bool $opensDialog
	 * @param string|null $icon
	 * @param string|NULL $id
	 * @param string $identifier
	 */
	public function __construct( Url|string $url, string $languageString, string $css ='ipsMenu_item', array $dataAttributes = [], bool $opensDialog = FALSE, ?string $icon = NULL, string $id = NULL, string $identifier = '' )
	{
		$this->url = $url;
		$this->title = $languageString;
		$this->css = $css;
		$this->dataAttributes = $dataAttributes;
		$this->icon = $icon;

		$this->menuItem = $identifier ?: $languageString;

		if( !$id )
		{
			$id = 'menuLink_' . md5($languageString ) . '_' . $this->identifier;
		}
		$this->id = $id;
		if( $opensDialog )
		{
			$this->opensDialog();
		}

		$this->addAttribute( 'data-menuItem', $languageString);
	}

	/**
	 * Return the parsed element
	 * 
	 * @return string
	 */
	function __toString(): string
	{
		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->row( $this );
	}
}
