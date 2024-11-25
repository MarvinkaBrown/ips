<?php
/**
 * @brief		Sitemap generator Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Aug 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Http\Url;
use XMLWriter;
use function count;
use function defined;
use function in_array;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sitemap generator class
 */
class Sitemap
{
	/**
	 * @brief	Maximum number of entries to include per file
	 * @deprecated  4.7.8 This will be removed in the future. Use now \IPS\SITEMAP_MAX_PER_FILE
	 */
	const MAX_PER_FILE = 500;

	/**
	 * @brief	Count options
	 */
	public static array $counts		= array( 0 => 0, 100 => 100, 500 => 500, 1000 => 1000, 5000 => 5000, 10000 => 10000 );

	/**
	 * @brief	Priority options
	 */
	public static array $priorities	= array( '1.0' => '1.0', '0.9' => '0.9', '0.8' => '0.8', '0.7' => '0.7', '0.6' => '0.6', '0.5' => '0.5', '0.4' => '0.4', '0.3' => '0.3', '0.2' => '0.2', '0.1' => '0.1' );

	/**
	 * @brief	"Log" entries for this execution
	 */
	public array $log	= array();

	/**
	 * @brief	URL to our sitemap index file
	 */
	public string $sitemapUrl	= '';

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		/* Figure out the sitemap URL */
		$this->sitemapUrl	= ( Settings::i()->sitemap_url ) ? rtrim( Settings::i()->sitemap_url, '/' ) : rtrim( Settings::i()->base_url, '/' ) . '/sitemap.php';
	}

	/**
	 * @brief	Store the sitemap files we can build
	 */
	protected ?array $sitemapFilesToBuild = NULL;

	/**
	 * Build the sitemap index file
	 *
	 * @return	bool
	 */
	public function buildNextSitemap(): bool
	{
		/* Get our extensions */
		$extensions	= Application::allExtensions( 'core', 'Sitemap', new Member, 'core' );
		
		/* If we haven't figured out which files we can/should build, do that first */
		if( $this->sitemapFilesToBuild === NULL )
		{
			$this->sitemapFilesToBuild = array();

			/* Figure out supported sitemap files */
			$files		= array();
			foreach ( $extensions as $extension )
			{
				$files	= array_merge( $files, $extension->getFilenames() );
			}
			
			/* Delete any that aren't supported */
			Db::i()->delete( 'core_sitemap', Db::i()->in( 'sitemap', $files, TRUE ) );

			/* Now figure out which one hasn't run in the longest period of time. */
			$sitemapsNotBuilt = array_diff( $files, iterator_to_array( Db::i()->select( 'sitemap', 'core_sitemap' ) ) );
			if ( count( $sitemapsNotBuilt ) )
			{
				$this->sitemapFilesToBuild = $sitemapsNotBuilt;
			}

			foreach(Db::i()->select( 'sitemap', 'core_sitemap', array( 'updated < ?', ( new DateTime)->sub( new DateInterval( 'PT1H' ) )->getTimestamp() ), 'updated ASC' ) as $sitemapFile )
			{
				$this->sitemapFilesToBuild[] = $sitemapFile;
			}
		}

		/* If there are no files to build, return now */
		if( !count( $this->sitemapFilesToBuild ) )
		{
			return FALSE;
		}

		$toBuild = array_shift( $this->sitemapFilesToBuild );
		
		/* Do it */
		if( $toBuild )
		{
			/* Call the plugin to generate this sitemap file */
			foreach( $extensions as $extension )
			{
				if( in_array( $toBuild, $extension->getFilenames() ) )
				{
					$extension->generateSitemap( $toBuild, $this );
				}
			}

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Build a sitemap file and store it
	 *
	 * @param string $filename	Filename
	 * @param array $entries	The entries to add.  Each entry should be an array with at least the key 'url'. Optional keys 'lastmod', 'priority' and 'changefreq' are also supported.
	 * @param int $lastId		The last ID we built. This is used for content items to allow us to more efficiently fetch the next batch of items to build.
	 * @param array $namespaces	Array of additional namespaces to define
	 * @return	void
	 */
	public function buildSitemapFile( string $filename, array $entries, int $lastId=0, array $namespaces=array() ) : void
	{
		/* Start XML Document, set encoding, and create the namespaced index element */
		$xmlWriter	= new XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->setIndent( TRUE );

		$xmlWriter->startDocument( '1.0', 'UTF-8' );
		$xmlWriter->startElementNS( NULL, 'urlset', "https://www.sitemaps.org/schemas/sitemap/0.9" );

		if( count( $namespaces ) )
		{
			foreach( $namespaces as $prefix => $urn )
			{
				$xmlWriter->writeAttributeNS( 'xmlns', $prefix, NULL, $urn );
			}
		}

		$defaultLanguage = Lang::load( Lang::defaultLanguage() );

		if( count( $entries ) )
		{
			foreach( $entries as $entry )
			{
				$xmlWriter->startElement( 'url' );

				$xmlWriter->startElement( 'loc' );
				$xmlWriter->text( preg_replace( '/^' . preg_quote( Settings::i()->base_url, '/' ) . '/', '{base_url}', $entry['url'] ) );
				$xmlWriter->endElement();

				if( isset( $entry['lastmod'] ) AND $entry['lastmod'] )
				{
					$xmlWriter->startElement( 'lastmod' );
					$xmlWriter->text( ( $entry['lastmod'] instanceof DateTime) ? $entry['lastmod']->format( 'c', $defaultLanguage ) : DateTime::ts( $entry['lastmod'] )->format( 'c', $defaultLanguage ) );
					$xmlWriter->endElement();
				}

				if( isset( $entry['priority'] ) AND $entry['priority'] )
				{
					$xmlWriter->startElement( 'priority' );
					$xmlWriter->text( $entry['priority'] );
					$xmlWriter->endElement();
				}

				if( isset( $entry['changefreq'] ) AND $entry['changefreq'] )
				{
					$xmlWriter->startElement( 'changefreq' );
					$xmlWriter->text( $entry['changefreq'] );
					$xmlWriter->endElement();
				}

				if( count( $namespaces ) )
				{
					foreach( $entry as $key => $value )
					{
						foreach( $namespaces as $prefix => $urn )
						{
							if( mb_strpos( $key, $prefix . ':' ) === 0 )
							{
								$pieces = explode( ':', $key );
								$xmlWriter->startElementNS( $pieces[0], $pieces[1], NULL );

								foreach( $value as $k => $v )
								{
									$pieces = explode( ':', $k );
									$xmlWriter->startElementNS( $pieces[0], $pieces[1], NULL );

									if( is_array( $v ) )
									{
										foreach( $v as $index => $value )
										{
											if( $index !== 0 )
											{
												$xmlWriter->writeAttribute( $index, $value );
											}
										}

										$xmlWriter->text( $v[0] );
									}
									else
									{
										$xmlWriter->text( $v );
									}

									$xmlWriter->endElement();
								}

								$xmlWriter->endElement();
							}
						}
					}
				}

				$xmlWriter->endElement();
			}

			/* End the XML document */
			$xmlWriter->endElement();
			$xmlWriter->endDocument();
			$content = $xmlWriter->outputMemory( TRUE );
		}
		else
		{
			$content = NULL;
		}

		/* Store */
		Db::i()->replace( 'core_sitemap', array(
			'sitemap'	=> $filename,
			'data'		=> $content,
			'updated'	=> time(),
			'last_id'	=> $lastId
		) );
	}
}