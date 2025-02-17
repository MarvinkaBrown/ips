<?php
/**
 * @brief		Editor AJAX functions Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Apr 2013
 */
 
namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\modules\front\system\editor as SystemEditor;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor AJAX functions Controller
 */
class editor extends SystemEditor
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	// This class only exists to extend the front-end controller
	// so that within the ACP we can call this endpoint and the
	// CSRF checks will work
}