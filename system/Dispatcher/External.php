<?php
/**
 * @brief		Front-end Dispatcher
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dispatcher that doesn't really dispatch but sets things up for external scripts like Pages external blocks
 */
class External extends Standard
{
	/**
	 * Controller Location
	 */
	public string $controllerLocation = 'front';
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init() : void
	{
		/* Base CSS */
		static::baseCss();

		/* Base JS */
		static::baseJs();
		
		/* Run global init */
		try
		{
			parent::init();
			
			/* Don't update sessions for this hit as it will wipe location data */
			Session::i()->noUpdate();
		}
		catch ( DomainException $e )
		{	
			Output::i()->error( $e->getMessage(), '2S100/' . $e->getCode(), $e->getCode() === 4 ? 403 : 404, '' );
		}
	}

	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs() : void
	{
		parent::baseJs();

		/* Stuff for output */
		if ( !Request::i()->isAjax() )
		{
			Output::i()->globalControllers[] = 'core.front.core.app';
			Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front.js' ) );
		}
	}

	/**
	 * Base CSS
	 *
	 * @return	void
	 */
	public static function baseCss() : void
	{
		parent::baseCss();

		/* Stuff for output */
		if ( !Request::i()->isAjax() )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'core.css', 'core', 'front' ) );
		}
	}
}