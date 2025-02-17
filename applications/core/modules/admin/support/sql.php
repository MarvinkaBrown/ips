<?php
/**
 * @brief		sql
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 May 2014
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * sql
 */
class sql extends Controller
{
	/**
	 * @brief    Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Execute
	 *
	 * @return    void
	 */
	public function manage() : void
	{
		// Intentionally blank
	}
}