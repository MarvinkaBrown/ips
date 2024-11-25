<?php
/**
 * @brief		Moderation
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Jul 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderation
 */
class moderation extends Controller
{
	/**
	 * Flag Member As Spammer
	 *
	 * @return	void
	 */
	protected function flagAsSpammer() : void
	{
		Session::i()->csrfCheck();
		
		$member = Member::load( Request::i()->id );

		$redirectTarget = ( Request::i()->referrer ) ? Url::createFromString( urldecode( Request::i()->referrer ) ) : $member->url();

		if ( $member->member_id and $member->member_id != Member::loggedIn()->member_id and Member::loggedIn()->modPermission('can_flag_as_spammer') and !$member->modPermission() and !$member->isAdmin() )
		{
			if ( Request::i()->s )
			{
				$member->flagAsSpammer();
				Session::i()->modLog( 'modlog__spammer_flagged', array( $member->name => FALSE ) );

				$actions = explode( ',', Settings::i()->spm_option );

				/* Redirect to the users profile if we're deleting the content to avoid that the moderator sees a 404 error message (unless soft delete is enabled) */
				if ( in_array( 'delete', $actions ) AND !( Member::loggedIn()->modPermission('can_manage_deleted_content') AND Settings::i()->dellog_retention_period ) )
				{
					$redirectTarget = $member->url();
				}
			}
			else
			{
				$member->unflagAsSpammer();
				Session::i()->modLog( 'modlog__spammer_unflagged', array( $member->name => FALSE ) );
			}
		}

		Output::i()->redirect( $redirectTarget, ( Request::i()->s ) ? 'account_flagged' : 'account_unflagged');
	}
}
