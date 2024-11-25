<?php
/**
 * @brief		Print out sitemap
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Aug 2013
 */

namespace IPS\core\modules\front\sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Controller;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Xml\SimpleXML;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Print out sitemap
 */
class sitemap extends Controller
{
	/**
	 * Print out the requested sitemap
	 *
	 * @return	mixed
	 */
	protected function manage() : mixed
	{
		if ( isset( Request::i()->file ) )
		{
			try
			{
				$content = Db::i()->select( 'data', 'core_sitemap', array( 'data IS NOT NULL AND sitemap=?', Request::i()->file ) )->first();
				$content = str_replace( '<loc>{base_url}', '<loc>' . Settings::i()->base_url, $content );

				/* http://www.w3.org/TR/REC-xml/#charsets */
				$content = preg_replace ('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $content);
			}
			catch ( UnderflowException $e )
			{
				Output::i()->error( 'sitemap_not_found', '2C152/1', 404, '' );
			}
		}
		else
		{
			$sitemapUrl = Url::external( Settings::i()->sitemap_url ? rtrim( Settings::i()->sitemap_url, '/' ) : rtrim( Settings::i()->base_url, '/' ) . '/sitemap.php' );
			
			$content = SimpleXML::create( 'sitemapindex', 'https://www.sitemaps.org/schemas/sitemap/0.9' );
			foreach ( Db::i()->select( array( 'sitemap', 'updated' ), 'core_sitemap', array( 'data IS NOT NULL' ) ) as $sitemap )
			{
				$content->addChild( 'sitemap', array( 'loc' => $sitemapUrl->setQueryString( 'file', $sitemap['sitemap'] ), 'lastmod' => DateTime::ts( $sitemap['updated'] )->format('c') ) );
			}
			$content = $content->asXML();
		}

		Output::i()->sendOutput( $content, 200, 'text/xml' );
	}
}