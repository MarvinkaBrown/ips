<?php
/**
 * @brief		Task to generate sitemaps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Aug 2013
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Log;
use IPS\Sitemap;
use IPS\Task;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Task to generate sitemaps
 */
class sitemapgenerator extends Task
{
	/**
	 * Execute
	 *
	 * @return	mixed
	 */
	public function execute() : mixed
	{
		$generator	= new Sitemap;

		$this->runUntilTimeout( function() use( $generator )
		{
			try
			{
				/* If it returns false, we're done for now */
				return $generator->buildNextSitemap();
			}
			catch( Exception $e )
			{
				Log::log( $e, 'sitemap_generator' );
				$generator->log[] = $e->getMessage();
				return FALSE;
			}
		} );

		if( count( $generator->log ) )
		{
			return $generator->log;
		}
		else
		{
			return null;
		}
	}
}