<?php

/**
 * @brief        LoaderExtension
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/24/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;

if (!defined('\IPS\SUITE_UNIQUE_KEY'))
{
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
	exit;
}

abstract class LoaderAbstract
{
	/**
	 * Additional CSS files to load
	 *
	 * @return array<string|Url>
	 */
	public function css(): array
	{
		return [];
	}

	/**
	 * Additional JS files to load
	 *
	 * @return array<string|Url>
	 */
	public function js(): array
	{
		return [];
	}

	/**
	 * If a redirect is needed, return the URL
	 * or NULL to continue
	 *
	 * @return Url|null
	 */
	public function checkForRedirect() : Url|null
	{
		return null;
	}

	/**
	 * Show a custom error message or null to use the default
	 *
	 * @param string $message
	 * @param mixed $code
	 * @param int $httpStatusCode
	 * @return string|null
	 */
	public function customError( string $message, mixed $code, int $httpStatusCode ) : string|null
	{
		return null;
	}

	/**
	 * Run custom code right on Dispatcher::finish()
	 *
	 * @return void
	 */
	public function onFinish() : void
	{

	}
}