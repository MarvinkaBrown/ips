<?php
/**
 * @brief		Trait for login handlers which redirect the user after clicking a button
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 May 2017
 */

namespace IPS\Login\Handler;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Login Handler for handlers which redirect the user after clicking a button
 */
trait ButtonHandler
{
	/**
	 * Get type
	 *
	 * @return	int
	 */
	public function type(): int
	{
		return Login::TYPE_BUTTON;
	}
	
	/**
	 * Get button
	 *
	 * @return	string
	 */
	public function button(): string
	{
		return Theme::i()->getTemplate( 'login', 'core', 'global' )->loginButton( $this );
	}
		
	/**
	 * Authenticate
	 *
	 * @param	Login	$login				The login object
	 * @throws	Exception
	 */
	abstract public function authenticateButton( Login $login );
	
	/**
	 * Get the button color
	 *
	 * @return	string
	 */
	abstract public function buttonColor(): string;
	
	/**
	 * Get the button icon
	 *
	 * @return	string
	 */
	abstract public function buttonIcon(): string;
	
	/**
	 * Get button text
	 *
	 * @return	string
	 */
	abstract public function buttonText(): string;

	/**
	 * Get button class
	 *
	 * @return	string
	 */
	public function buttonClass(): string
	{
		return '';
	}
	
	/**
	 * Get logo to display in user cp sidebar
	 *
	 * @return	Url|string|null
	 */
	public function logoForUcp(): Url|string|null
	{
		return $this->buttonIcon();
	}

}