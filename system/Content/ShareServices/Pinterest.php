<?php
/**
 * @brief		Pinterest share link
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		01 Dec 2016
 */

namespace IPS\Content\ShareServices;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Content\ShareServices;
use IPS\Http\Url;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Pinterest share link
 */
class Pinterest extends ShareServices
{
	/**
	 * @brief	Ccontent item
	 */
	protected Content|null $item	= NULL;
		
	/**
	 * Constructor
	 *
	 * @param	Url|null	$url	URL to the content [optional - if omitted, some services will figure out on their own]
	 * @param	string|null			$title	Default text for the content, usually the title [optional - if omitted, some services will figure out on their own]
	 * @param	Content|NULL	$item	Content item (or comment) to share
	 * @return	void
	 */
	public function __construct( Url|null $url=NULL, string|null $title=NULL, Content|null $item=NULL )
	{
		$this->item = $item;
		
		parent::__construct( $url, $title );
	}
		
	/**
	 * Return the HTML code to show the share link
	 *
	 * @return	string
	 */
	public function __toString(): string
	{
		if ( $this->item )
		{
			return Theme::i()->getTemplate( 'sharelinks', 'core' )->pinterest( Url::external( 'https://pinterest.com/pin/create/button/' )->setQueryString( 'url', (string) $this->url )->setQueryString( 'media', $this->item->shareImage() ) );
		}
		else
		{
			return '';
		}
	}
}