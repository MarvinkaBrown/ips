<?php

/**
 * @brief        SitemapAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/21/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Sitemap;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class SitemapAbstract
{
	/**
	 * @brief	Recommended Settings
	 */
	public array $recommendedSettings = array();

	/**
	 * Add settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	abstract public function settings(): array;

	/**
	 * Save settings for ACP configuration
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	abstract public function saveSettings( array $values ) : void;

	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	abstract public function getFilenames(): array;

	/**
	 * Generate the sitemap
	 *
	 * @param	string		$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	Sitemap 	$sitemap	Sitemap object reference
	 * @return	int|null
	 */
	abstract public function generateSitemap( string $filename, Sitemap $sitemap ) : ?int;
}