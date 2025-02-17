<?php
/**
 * @brief		Sameboxed Template
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Error;
use ErrorException;
use IPS\Log;
use Throwable;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Sameboxed Template Class
 */
class SandboxedTemplate
{
	/**
	 * @brief	Template
	 */
	public Template $template;
	
	/**
	 * Contructor
	 *
	 * @param Template $template	Template to use
	 * @return	void
	 */
	public function __construct( Template $template )
	{
		$this->template = $template;
	}
	
	/**
	 * Call
	 *
	 * @param string $name	Method name
	 * @param array $args	Method arguments
	 * @return	string
	 */
	public function __call( string $name, array $args )
	{
		try
		{
			if ( !method_exists( $this->template, $name ) )
			{
				/* It doesn't exist in this theme or master, so ignore, as it's likely been removed from master during an upgrade */
				return '';
			}
			else
			{
				try
				{
					return $this->template->$name( ...$args );
				}
				catch ( ErrorException $e )
				{
					throw new Error( $e );
				}
			}
		}
		catch( Throwable $e )
		{
			Log::log( $e, 'template_error' );

			return "<span style='background:black;color:white;padding:6px;'>[[Template {$this->template->app}/{$this->template->templateLocation}/{$this->template->templateName}/{$name} is throwing an error. This theme may be out of date. Run the support tool in the AdminCP to restore the default theme.]]</span>";
		}
	}
}