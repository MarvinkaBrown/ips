<?php
/**
 * @brief		Poll View Voters Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Jan 2014
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Poll as PollClass;
use IPS\Request;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Poll View Voters Controller
 */
class poll extends Controller
{
	/**
	 * View log
	 *
	 * @return	void
	 */
	protected function voters() : void
	{
		Output::i()->metaTags['robots'] = 'noindex';
		try
		{
			$poll = PollClass::load( Request::i()->id );
			if ( !$poll->canSeeVoters() )
			{
				Output::i()->error( 'node_error', '2C174/2', 403, '' );
			}
			
			Output::i()->output = Theme::i()->getTemplate( 'global', 'core', 'global' )->pollVoters( $poll->getVotes( Request::i()->question, Request::i()->option ) );
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C174/1', 404, '' );
		}		
	}
}