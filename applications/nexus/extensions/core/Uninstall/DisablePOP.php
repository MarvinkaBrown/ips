<?php
/**
 * @brief		Uninstall callback
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		02 Nov 2018
 */

namespace IPS\nexus\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\UninstallAbstract;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Uninstall callback
 */
class DisablePOP extends UninstallAbstract
{
	/**
	 * Code to execute after the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	void
	 */
	public function postUninstall( string $application ) : void
	{

	}
}