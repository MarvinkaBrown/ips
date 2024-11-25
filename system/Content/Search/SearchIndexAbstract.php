<?php

/**
 * @brief        SearchIndexAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/21/2023
 */

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class SearchIndexAbstract
{
	/**
	 * Alter index_content before it is stored
	 *
	 * @param	Content	    $object		The class object (eg: \IPS\forums\Topic\Post)
	 * @param	string		$content	The HTML stripped content
	 * @return	string
	 */
	abstract public function content(  Content $object, string $content ): string;
}