<?php
/**
 * @brief		Legacy 3.x findpost
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		09 Jul 2015
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\forums\Topic\Post;
use IPS\Output;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Legacy 3.x findpost
 */
class findpost extends Controller
{
	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage() : void
	{		
		try
		{
			Output::i()->redirect( Post::loadAndCheckPerms( Request::i()->pid )->url(), NULL );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2F284/1', 404, '' );
		}
	}
}