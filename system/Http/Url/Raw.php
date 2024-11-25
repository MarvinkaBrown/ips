<?php
/**
 * @brief		Raw (data) URL
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		October 2023
 */

namespace IPS\Http\Url;

use IPS\Http\Url;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


class Raw extends Url
{
	const TYPE_PLAIN = 'text/plain';
	const TYPE_SVG = 'image/svg+xml';
	const TYPE_PNG = 'image/png';
	const TYPE_JPG = 'image/jpeg';
	const TYPE_HTML = 'text/html';
	const TYPE_CSV = 'text/csv';
	const TYPE_GIF = 'image/gif';


	protected string $mimeType = Raw::TYPE_PLAIN;
	protected string $rawData = '';

	/**
	 * Creates a raw URL (e.g. a data URL)
	 * @param string $data
	 * @param string $mimeType
	 */
	public function __construct( string $data, string $mimeType=Raw::TYPE_PLAIN )
	{
		$this->setData( $data, $mimeType );
	}

	/**
	 * Get the url as a string when accessing the `url` property. This is because the raw url is often a replacement for a file attahcment, for which the url property is referenced
	 * @param string $key
	 * @return string|void
	 */
	public function __get( string $key )
	{
		if ($key === 'url') {
			return (string) $this;
		}
	}


	/**
	 * Set the data of this url
	 *
	 * @param string $data
	 * @param ?string $mimeType		The mime type. Pass NULL to not override the currently in use mime type
	 *
	 * @return void
	 */
	public function setData( string $data, ?string $mimeType=null ) : void
	{
		$this->rawData = $data;
		$this->mimeType = $mimeType ?? $this->mimeType;
	}


	/**
	 * Get the data
	 *
	 * @return string
	 */
	public function getData() : string
	{
		return $this->rawData;
	}

	/**
	 * @return string
	 */
	public function __toString() : string
	{
		$encoded = rawurlencode( $this->rawData );
		return "data:{$this->mimeType},{$encoded}";
	}
}