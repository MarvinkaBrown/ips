<?php
/**
 * @brief		Recount and Reset Tools
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Oct 2013
 */

namespace IPS\core\modules\admin\members;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Recount and Reset Tools
 */
class reset extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'member_recount_content' );
		parent::execute();
	}

	/**
	 * Queue the content recount task
	 *
	 * @return	void
	 */
	public function posts() : void
	{
		Session::i()->csrfCheck();
		Task::queue( 'core', 'RecountMemberContent', array(), 4 );
		Session::i()->log( 'acplog__recount_member_content' );
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=members' ), Member::loggedIn()->language()->addToStack( 'member_recount_content_process' ) );
	}

	/**
	 * Queue the reputation recount task
	 *
	 * @return	void
	 */
	public function rep() : void
	{
		Session::i()->csrfCheck();
		Task::queue( 'core', 'RecountMemberReputation', array(), 4 );
		Session::i()->log( 'acplog__recount_member_rep' );
		Output::i()->redirect( Url::internal( 'app=core&module=members&controller=members' ), Member::loggedIn()->language()->addToStack( 'member_recount_rep_process' ) );
	}
}