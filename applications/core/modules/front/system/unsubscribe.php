<?php
/**
 * @brief		Allow users to unsubscribe from site updates
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Jun 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use UnderflowException;
use function defined;
use function implode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Unsubscribe
 */
class unsubscribe extends Controller
{
	/**
	 * Unsubscribe the user
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Prevent the email and secure key from being exposed in referrers */
		Output::i()->sendHeader( "Referrer-Policy: origin" );

		/* Get the member being requested */
		if( empty( Request::i()->email ) )
		{
			Output::i()->error( 'no_user_to_unsubscribe', '2S127/3', 404, '' );
		}

		$member	= Member::load( Request::i()->email, 'email' );

		if( !$member->member_id )
		{
			Output::i()->error( 'no_user_to_unsubscribe', '2S127/2', 404, '' );
		}

		/* Verify the key is correct */
		if ( Login::compareHashes( md5( $member->email . ':' . $member->members_pass_hash ), (string) Request::i()->key ) )
		{
			$action = 'bulkEmail';
			if ( isset( Request::i()->action ) and Request::i()->action === 'markSolved' )
			{
				$action = 'markSolved';
				$member->members_bitoptions['no_solved_reenage'] = 1;
				$member->save();
			}
			elseif ( isset( Request::i()->action ) and Request::i()->action === 'expertNudge' )
			{
				$action = 'expertNudge';

				/* Update the core_notification_preferences entry to remove email */
				try
				{
					$pref = Db::i()->select( '*', 'core_notification_preferences', array( "member_id=? and notification_key=?", $member->member_id, 'new_topics_to_review' ) )->first();
					$methods = explode( ',', $pref['preference'] );
					$methods = array_diff( $methods, array( 'email' ) );

					Db::i()->update( 'core_notification_preferences', array( 'preference' => implode( ',', $methods ) ), array( "member_id=? and notification_key=?", $member->member_id, 'new_topics_to_review' ) );
				}
				catch( UnderflowException )
				{
					Db::i()->insert( 'core_notification_preferences', array(
						'member_id'			=> $member->member_id,
						'notification_key'	=> 'new_topics_to_review',
						'preference'		=> 'inline,push'
					), TRUE );
				}
			}
			else
			{
				/* Set the member not to receive future emails */
				$member->allow_admin_mails	= 0;
				$member->save();
	
				/* Log it */
				$member->logHistory( 'core', 'admin_mails', array( 'enabled' => FALSE ) );
			}
			
			/* And then show them a confirmation screen */
			Output::i()->output = Theme::i()->getTemplate( 'system' )->unsubscribed( $action );
			Output::i()->title = Member::loggedIn()->language()->addToStack('unsubscribed');
			
		}
		else
		{
			/* Key did not match */
			Output::i()->error( 'no_user_to_unsubscribe', '3S127/4', 403, '' );
		}
	}
}