<?php
/**
 * @brief		Offline page output
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Feb 2021
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Offline page Controller
 */
class offline extends Controller
{	
	/**
	 * View Notifications
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->metaTags['robots'] = 'noindex';
        Output::i()->sendOutput( Theme::i()->getTemplate( 'global', 'core', 'global' )->offline() );
    }
}