<?php
/**
 * @brief		Magic Template Class for BASIC mode
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Oct 2013
 */

namespace IPS\Theme\System;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Theme\Dev\Template as DevTemplate;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Magic Template Class for BASIC mode
 */
class Template extends DevTemplate
{
	/**
	 * @brief	Source Folder
	 */
	public ?string $sourceFolder = NULL;
	
	/**
	 * Contructor
	 *
	 * @param string $app				Application Key
	 * @param string $templateLocation	Template location (admin/public/etc.)
	 * @param string $templateName		Template Name
	 * @return	void
	 */
	public function __construct( string $app, string $templateLocation, string $templateName )
	{
		parent::__construct( $app, $templateLocation, $templateName );
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
		
		$this->sourceFolder = \IPS\ROOT_PATH . "/applications/{$app}/data/html/{$templateLocation}/{$templateName}/";
	}
}