<?php
/**
 * @brief		Content Extension Generator
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Dec 2013
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Dispatcher;
use IPS\Lang;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Extension Generator
 */
abstract class ExtensionGenerator
{
	/**
	 * @brief	If TRUE, will prevent comment classes being included
	 */
	protected static bool $contentItemsOnly = FALSE;

	/**
	 * @brief	If TRUE, will include archive classes
	 */
	protected static bool $includeArchive = FALSE;
	
	/**
	 * Generate Extensions
	 *
	 * @return	array
	 */
	public static function generate(): array
	{
		$return = array();
		
		foreach ( Content::routedClasses( FALSE, static::$includeArchive, static::$contentItemsOnly ) as $_class )
		{
			$obj = new static;
			$obj->class = $_class;
			
			if ( Dispatcher::hasInstance()  )
			{
				$language = Member::loggedIn()->language();
			}
			else
			{
				$language = Lang::load( Lang::defaultLanguage() );
			}

			$language->words[ 'ipAddresses__core_Content_' . str_replace( '\\', '_', mb_substr( $_class, 4 ) ) ] = $language->addToStack( ( ( isset( $_class::$archiveTitle ) ) ? $_class::$archiveTitle : $_class::$title ) . '_pl', FALSE );
			$return[ 'Content_' . str_replace( '\\', '_', mb_substr( $_class, 4 ) ) ] = $obj;
		}
		
		return $return;
	}
	
	/**
	 * @brief	Content Class
	 */
	public string $class;
}