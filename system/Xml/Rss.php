<?php
/**
 * @brief		Class for managing RSS documents
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Xml;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\DateTime;
use IPS\File;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Class for managing RSS documents
 */
class Rss extends SimpleXML
{	
	/**
	 * Create RSS document
	 *
	 * @param	Url	$url			URL to document
	 * @param string $title			Channel Title
	 * @param string $description	Channel Description
	 * @return	static
	 * @see		<a href='http://cyber.law.harvard.edu/rss/languages.html'>Allowable values for language in RSS</a>
	 */
	public static function newDocument( Url $url, string $title, string $description ) : static
	{
		$xml = new static( '<rss version="2.0" />' );
		
		$channel = $xml->addChild( 'channel' );
		$channel->addChild( 'title', $title );
		$channel->addChild( 'link', (string) $url );
		$channel->addChild( 'description', $description );
		
		/* Previously we were regexing and whitelisting language codes for some reason - we should just send the language code always */
		$locale = mb_strtolower( Member::loggedIn()->language()->short );
		$locale = mb_substr( $locale, 0, mb_strpos( str_replace( '-', '_', $locale ), '_' ) );
		$channel->addChild( 'language', $locale );
	
		return $xml;
	}
	
	/**
	 * Add Item
	 *
	 * @param string|null $title			Item title
	 * @param	Url|NULL	$link			Item link
	 * @param string|null $description	Item description/content
	 * @param	DateTime|NULL	$date			Item date
	 * @param string|null $guid			Item ID
	 * @param File|null $enclosure		Enclosure file object
	 * @return	void
	 * @todo	[Future] The feed will validate now, but unrecognized attribute values cause warnings when validating. Also, the validator recommends using an Atom feed with the atom:link attribute.
	 */
	public function addItem( string $title = NULL, Url $link = NULL, string $description = NULL, DateTime $date = NULL, string $guid = NULL, File $enclosure=NULL ) : void
	{
		if ( $title === NULL and $description === NULL )
		{
			throw new InvalidArgumentException;
		}
		
		$item = $this->channel->addChild( 'item' );
		
		if ( $title !== NULL )
		{
			$item->addChild( 'title', $title );
		}
		
		$item->addChild( 'link', (string) $link );
		
		if ( $description !== NULL )
		{
			$description = preg_replace_callback( "/\s+?(srcset|src)=(['\"])\/\/([^'\"]+?)(['\"])/ims", function( $matches ){
				$baseUrl = parse_url( Settings::i()->base_url );
	
				/* Try to preserve http vs https */
				if( isset( $baseUrl['scheme'] ) )
				{
					$url = $baseUrl['scheme'] . '://' . $matches[3];
				}
				else
				{
					$url = 'http://' . $matches[3];
				}
		
				return " {$matches[1]}={$matches[2]}{$url}{$matches[2]}";
			}, $description );
		
			$item->addChild( 'description', $description );
		}

		if ( $enclosure !== NULL and $enclosure->mediaType() == 'image' )
		{
			$enclosureUrl = Url::createFromString( $enclosure->url );
			$child = $item->addChild( 'enclosure' );
			$child->addAttribute( 'url', (string) $enclosureUrl->setScheme( ( Request::i()->isSecure() ) ? 'https' : 'http' ) );
			$child->addAttribute( 'length', (string) $enclosure->filesize() );
			$child->addAttribute( 'type', File::getMimeType( (string) $enclosure->url ) );
		}
		
		if ( $guid !== NULL )
		{
			$item->addChild( 'guid', $guid )->addAttribute( 'isPermaLink', 'false' );
		}
		
		if ( $date !== NULL )
		{
			$item->addChild( 'pubDate', $date->format('r') );
		}

	}
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return $this->channel->title;
	}
	
	/**
	 * Get articles
	 *
	 * @param mixed|null $guidKey	In previous versions, we encoded a key with the GUID. For legacy purposes, this can be passed here.
	 * @return	array
	 */
	public function articles( mixed $guidKey=NULL ): array
	{
		$articles	= array();
		$items		= $this->getItems();
		foreach ( $items as $item )
		{
			$link = NULL;
			if ( isset( $item->link ) AND $item->link )
			{
				try
				{
					$link = Url::external( (string) $item->link );
				}
				catch ( Exception $e ) {  }
			}
			
			if ( isset( $item->guid ) )
			{
				$guid = $item->guid;
			}
			else
			{
				$guid = '';
				foreach ( array( 'title', 'link', 'description' ) as $k )
				{
					if ( isset( $item->$k ) )
					{
						$guid .= $item->$k;
					}
				}
				$guid = preg_replace( "#\s|\r|\n#is", "", $guid );
			}
			$guid = md5( $guidKey . $guid );

			$text = isset( $item->description ) ? (string) $item->description : (string) $item->title;

			/* If there is a <content:encoded> tag, get the contents of that instead of description */
			if( count( $item->children( 'content', true ) ) AND count( $item->children( 'content', true )->encoded ) )
			{
				$text = (string) $item->children( 'content', true )->encoded[0];
			}

			/* Some feeds may not provide a pubDate for the Item or Channel in the feed. Work out which one exists and if neither do, use the current time */
			$pubDate = $this->getDate( $item );
			
			if ( $pubDate === NULL AND $this->channel->pubDate )
			{
				$pubDate = DateTime::ts( strtotime( $this->channel->pubDate ), TRUE );
			}

			$articles[ $guid ] = array(
				'title'		=> ( (string) $item->title ) ?: ( mb_substr( $text, 0, 47 ) . '...' ),
				'content'	=> $text,
				'date'		=> $pubDate ?: DateTime::create(),
				'link'		=> $link
			);

			if ( isset( $item->enclosure ) )
			{
				$attr = array();
				foreach( $item->enclosure->attributes() as $k => $v )
				{
					$attr[ $k ] = (string) $v;
				}

				$articles[ $guid ]['enclosure'] = $attr;
			}
		}
		return $articles;
	}

	/**
	 * Fetch the date
	 *
	 * @param object $item	RSS item
	 * @return	NULL|DateTime
	 */
	protected function getDate( object $item ): ?DateTime
	{
		$pubDate = NULL;
		if ( $item->pubDate )
		{
			$pubDate = DateTime::ts( strtotime( $item->pubDate ), TRUE );
		}

		return $pubDate;
	}

	/**
	 * Fetch the items
	 *
	 * @return	self
	 */
	protected function getItems(): self
	{
		return $this->channel->item;
	}
}