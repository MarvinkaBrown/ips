<?php
/**
 * @brief		Setup Skin Set
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jun 2013
 */

namespace IPS\Theme\Setup;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Theme as SystemTheme;
use IPS\Theme\Dev\Template as DevTemplate;
use IPS\Theme\Template;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IN_DEV Skin set
 */
class Theme extends SystemTheme
{
	/**
	 * Constructor
	 * Stops the DB being queried which doesn't exist yet
	 *
	 * @return	void
	 */
	public function __construct() { }
	
	/**
	 * Get template
	 *
	 * @param string $group			Template name
	 * @param string|null $app				Application key (NULL for current application)
	 * @param string|null $location	Template location (NULL for current template location)
	 * @return    Template
	 */
	public function getTemplate( string $group, string $app=NULL, string $location=NULL ): Template
	{
		$obj = new DevTemplate( $group, 'core', 'setup' );
		$obj->sourceFolder = \IPS\ROOT_PATH . "/admin/" . Dispatcher::i()->setupLocation . "/html/{$group}/";
		return $obj;
	}
}