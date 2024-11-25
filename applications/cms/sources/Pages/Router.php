<?php
/**
 * @brief		Page Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Content
 * @since		15 Jan 2014
 */

namespace IPS\cms\Pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\cms\Categories;
use IPS\cms\Records;
use IPS\Db;
use IPS\Http\Url;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Page Model
 */
class Router extends ActiveRecord
{
	/**
	 * Load Pages Thing based on a URL.
	 * The URL is sometimes complex to figure out, so this will help
	 *
	 * @param	Url	$url	URL to load from
	 * @return    mixed
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): mixed
	{
		if ( ! isset( $url->queryString['path'] ) )
		{
			throw new OutOfRangeException();
		}
		
		$path = $url->queryString['path'];
		
		/* First, we need a page */
		$page = Page::loadFromPath( $path );
		
		/* What do we have left? */
		$whatsLeft = trim( preg_replace( '#' . $page->full_path . '#', '', $path, 1 ), '/' );
		
		if ( $whatsLeft )
		{
			/* Check databases */
			$databases = iterator_to_array( Db::i()->select( '*', 'cms_databases', array( 'database_page_id > 0' ) ) );
			foreach( $databases as $db )
			{
				$classToTry = 'IPS\cms\Records' . $db['database_id'];
				/* @var $classToTry Records */
				try
				{
					return $classToTry::loadFromSlug( $whatsLeft, FALSE, FALSE );
				}
				catch( Exception $ex ) { }
			}
			
			/* Check categories */
			foreach( $databases as $db )
			{
				$classToTry = 'IPS\cms\Categories' . $db['database_id'];
				/* @var $classToTry Categories */
				try
				{
					$category = $classToTry::loadFromPath( $whatsLeft );
					
					if ( $category !== NULL )
					{
						return $category;
					}
				}
				catch( Exception $ex ) { }
			}
		}
		else
		{
			/* It's a page */
			return $page;
		}
		
		/* No idea, sorry */
		throw new InvalidArgumentException;
	}
}