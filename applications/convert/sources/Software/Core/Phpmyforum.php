<?php

/**
 * @brief		Converter PhpMyForum Core Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		6 December 2016
 * @note		Only redirect scripts are supported right now
 */

namespace IPS\convert\Software\Core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application\Module;
use IPS\convert\Software;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PhpMyForum Core Converter
 */
class Phpmyforum extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "PhpMyForum";
	}

	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "phpmyforum";
	}

	/**
	 * Content we can convert from this software.
	 *
	 * @return    array|NULL
	 */
	public static function canConvert(): ?array
	{
		return NULL;
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		/* If we can't access profiles, don't bother trying to redirect */
		if( !Member::loggedIn()->canAccessModule( Module::get( 'core', 'members' ) ) )
		{
			return NULL;
		}

		$url = Request::i()->url();

		if( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'profile.php' ) !== FALSE )
		{
			try
			{
				$data = (string) $this->app->getLink( Request::i()->id, array( 'members', 'core_members' ) );
				return Member::load( $data )->url();
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}

		return NULL;
	}
}