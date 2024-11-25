<?php
/**
 * @brief		Calendars
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		18 Dec 2013
 */

namespace IPS\calendar\modules\admin\calendars;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Output;
use IPS\Request;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Calendars
 */
class calendars extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\calendar\Calendar';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'calendars_manage' );
		parent::execute();
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form() : void
	{
		parent::form();

		if ( Request::i()->id )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('edit_calendar') . ': ' . Output::i()->title;
		}
		else
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack('add_calendar');
		}
	}
}