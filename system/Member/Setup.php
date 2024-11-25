<?php
/**
 * @brief		Dummy Member Model used by installer
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Jul 2013
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Lang;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dummy Member Model used by installer
 */
class Setup
{
	/**
	 * @brief	Instance
	 */
	protected static ?self $instance = NULL;
	
	/**
	 * Get instance
	 *
	 * @return    Setup
	 */
	public static function i() : static
	{
		if ( static::$instance === NULL )
		{
			static::$instance = new self;
		}
		return static::$instance;
	}
	
	/**
	 * @brief	Language data
	 */
	protected ?Lang $language = NULL;
	
	/**
	 * Is user an admin
	 *
	 * @return	boolean
	 */
	public function isAdmin(): bool
	{
		return FALSE;
	}
	
	/**
	 * Is the user logged in?
	 *
	 * @return static
	 */
	public function loggedIn(): static
	{
		return $this;
	}
	
	
	
	/**
	 * Get language
	 *
	 * @return	Lang|null
	 */
	public function language(): ?Lang
	{
		if ( $this->language === NULL )
		{
			$this->language = Lang::constructFromData( array() );
			require( \IPS\ROOT_PATH . '/admin/install/lang.php' );
			$this->language->words = $lang;
		}
		return $this->language;
	}
}