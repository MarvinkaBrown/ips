<?php
/**
 * @brief		Template Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Class
 */
abstract class Template
{
	/**
	 * @brief	Application key
	 */
	public ?string $app = NULL;
	
	/**
	 * @brief	Template Location
	 */
	public ?string $templateLocation = NULL;
	
	/**
	 * @brief	Template Name
	 */
	public ?string $templateName = NULL;
		
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
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
	}

	/**
	 * Return the app/location/group params
	 *
	 * @return array
	 */
	public function getParams(): array
	{
		return array( 'app' => $this->app, 'location' => $this->templateLocation, 'group' => $this->templateName );
	}

	/**
	 * htmlspecialchars cannot have string as null, so this wrapper makes it safe to avoid
	 * loads of errors
	 *
	 * @param string|null $string $string
	 * @param int $flags
	 * @param string|null $encoding
	 * @param bool $double_encode
	 * @return string
	 */
	public static function htmlspecialchars( null|string $string, int $flags = ENT_QUOTES|ENT_SUBSTITUTE, ?string $encoding = null, bool $double_encode = true): string
	{
		return htmlspecialchars( $string === null ? '' : $string, $flags, $encoding, $double_encode );
	}
}