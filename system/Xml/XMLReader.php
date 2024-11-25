<?php
/**
 * @brief		Wrapper class for managing XMLReader objects
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 July 2016
 */

namespace IPS\Xml;

/* To prevent PHP errors (extending class does not exist) revealing path */

use XMLReader as PHPXMLReader;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Wrapper class for managing XMLReader objects
 */
class XMLReader extends PHPXMLReader
{
	/**
	 * Open a file or URL with XMLReader to read it
	 *
	 * @param string $uri		The URI/path to open
	 * @param string|null $encoding	The encoding to use, or NULL
	 * @param int $options	Bitmask of LIBXML_* constants
	 * @return    PHPXMLReader|bool
	 * @note	We are disabling network access while loading the content to prevent XXE
	 */
	public static function safeOpen( string $uri, string $encoding=NULL, int $options=0 ): PHPXMLReader|bool
	{
		if( $options === 0 )
		{
			$options = LIBXML_NONET;
		}

		return parent::open( $uri, $encoding, $options );
	}
}