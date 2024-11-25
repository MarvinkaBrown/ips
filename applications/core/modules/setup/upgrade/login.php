<?php
/**
 * @brief		Upgrader: Login
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\Setup\Upgrade;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Login as LoginClass;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use const IPS\BYPASS_UPGRADER_LOGIN;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Login
 */
class login extends Controller
{
	/**
	 * Anything lower than this will cause the upgrader to disable
	 * non-1st party apps
	 *
	 * @var int
	 */
	protected static int $minimumv5 = 5000015;

	/**
	 * Show login form and/or process login form
	 *
	 * @todo	[Upgrade] Will also need to account for things in the input (e.g. password) that would be replaced, like & to &amp;
	 * @return	void
	 */
	public function manage() : void
	{
		/* Clear previous session data */
		if( !isset( Request::i()->sessionCheck ) AND count( $_SESSION ) )
		{
			foreach( $_SESSION as $k => $v )
			{
				unset( $_SESSION[ $k ] );
			}
		}

		/* Store a session variable and then check it on the next page load to make sure PHP sessions are working */
		if( !isset( Request::i()->sessionCheck ) )
		{
			$_SESSION['sessionCheck'] = TRUE;
			Output::i()->redirect( Request::i()->url()->setQueryString( 'sessionCheck', 1 ), NULL, 307 ); // 307 instructs the browser to resubmit the form as a POST request maintaining all the values from before
		}
		else
		{
			if( !isset( $_SESSION['sessionCheck'] ) OR !$_SESSION['sessionCheck'] )
			{
				Output::i()->error( 'session_check_fail', '5C289/1', 500, '' );
			}
		}

		/* Are we automatically logging in? */
		if ( isset( Request::i()->autologin ) and isset( Request::i()->cookie['IPSSessionAdmin'] ) )
		{
			try
			{
				$session = Db::i()->select( '*', 'core_sys_cp_sessions', array( 'session_id=?', Request::i()->cookie['IPSSessionAdmin'] ) )->first();
				$member = $session['session_member_id'] ? Member::load( $session['session_member_id'] ) : new Member;
				if ( $member->member_id and $this->_memberHasUpgradePermission( $member ) and ( !Settings::i()->match_ipaddress or ( $session['session_ip_address'] === Request::i()->ipAddress() ) ) )
				{
					$_SESSION['uniqueKey'] =  LoginClass::generateRandomString();
					Output::i()->redirect( Url::internal( "controller=systemcheck" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
				}
			}
			catch( UnderflowException $e ) {}
		}
		if ( BYPASS_UPGRADER_LOGIN )
		{
			$_SESSION['uniqueKey']	=  LoginClass::generateRandomString();
			Output::i()->redirect( Url::internal( "controller=systemcheck" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		}

		/* Drop the data store before we continue */
		
		/* Before going any further, make sure there is an actual upgrade to be done. This both provides a nicer experience (so
			people don't log in ust to be told there's nothing to upgrade) and prevents having a permenantly available login
			screen which doesn't use locking (which could be use to bruteforce an account) */
		$canUpgrade = FALSE;
		$currentBaseVersion = Db::i()->select( 'app_long_version', 'core_applications', [ 'app_directory=?', 'core' ] )->first();

		/* If we are upgrading from a version earlier than v5, disable and lock anything that isn't 1st party */
		if( $currentBaseVersion < static::$minimumv5 )
		{
			Db::i()->update( 'core_applications', [ 'app_enabled' => 0, 'app_requires_manual_intervention' => 1 ], Db::i()->in( 'app_directory', IPS::$ipsApps, true ) );
			Cache::i()->clearAll();
			Store::i()->clearAll();
		}

		foreach( Application::getStore() as $data )
		{
			$app = $data['app_directory'];

			/* If we are upgrading from a version earlier than v5, only check 1st party apps */
			if( $currentBaseVersion < static::$minimumv5 and !in_array( $app, IPS::$ipsApps ) )
			{
				continue;
			}

			/* Skip anything that is locked */
			if( $data['app_requires_manual_intervention'] )
			{
				continue;
			}

			$path = Application::getRootPath( $app ) . '/applications/' . $app;
			
			if ( $app != 'chat' and is_dir( $path . '/data' ) )
			{
				$currentVersion		= Application::load( $app )->long_version;
				$availableVersion	= Application::getAvailableVersion( $app );
	
				if ( empty( $errors ) AND $availableVersion > $currentVersion )
				{
					$canUpgrade = TRUE;
				}
			}
		}

		/* We need to allow logins if the previous upgrade wasn't finished */
		if( Db::i()->checkForTable( 'upgrade_temp' ) )
		{
			$canUpgrade = TRUE;
		}

		if ( !$canUpgrade )
		{
			Output::i()->output .= Theme::i()->getTemplate( 'forms' )->noapps();

			/* We do this just to be 100% certain the flag didn't get "stuck" and needs to be reset. */
			Upgrade::setUpgradingFlag( FALSE );
			return;
		}
		
		/* Nope, show a form */
		$error = NULL;
		if ( isset( Request::i()->auth ) and isset( Request::i()->password ) )
		{
			$table = 'core_members';
			if ( Db::i()->checkForTable( 'members' ) AND !Db::i()->checkForTable( 'core_members' ) )
			{
				$table = 'members';
			}
			
			$memberRows = Db::i()->select( '*', $table, array( 'email=? OR email=?', Request::i()->auth, Request::legacyEscape( Request::i()->auth ) ) );
			if ( count( $memberRows ) )
			{
				foreach( $memberRows as $memberRow )
				{
					$member = Member::constructFromData( $memberRow );
					
					if ( password_verify( Request::i()->password, $member->members_pass_hash ) or $member->verifyLegacyPassword( Request::i()->protect('password') ) )
					{
						if ( $this->_memberHasUpgradePermission( $member ) )
						{
							$_SESSION['uniqueKey']	=  LoginClass::generateRandomString();
							IPS::resyncIPSCloud('Beginning upgrade');
							Output::i()->redirect( Url::internal( "controller=systemcheck" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
						}
						else
						{
							$error = 'login_upgrader_no_permission';
						}
					}
					else
					{
						$error = 'login_err_bad_password';
					}
				}
			}
			else
			{
				$error = 'login_err_no_account';
			}
		}

		if ( $error )
		{
			$error = Member::loggedIn()->language()->addToStack( $error, FALSE, array( 'pluralize' => array( 3 ) ) );
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('login');
		Output::i()->output 	.= Theme::i()->getTemplate( 'forms' )->login( $error );
	}
	
	/**
	 * Can member log into upgrader?
	 *
	 * @param	Member	$member	The member
	 * @return	bool
	 */
	protected function _memberHasUpgradePermission( Member $member ) : bool
	{
		/* 4.x */
		if ( Db::i()->checkForTable( 'core_admin_permission_rows' ) )
		{
			/* This permission was added in 4.1.6, so if we have it, use it */
			if ( Application::load('core')->long_version > 101021 )
			{
				return $member->hasAcpRestriction( 'core', 'overview', 'upgrade_manage' );
			}
			/* Otherwise, let them in if they're an admin */
			else
			{
				return $member->isAdmin();
			}
		}
		/* 3.x */
		else
		{
			/* Does our primary group have permission? */
			try
			{
				$admin = (bool) Db::i()->select( 'g_access_cp', 'groups', array( 'g_id=?', $member->member_group_id ) )->first();
			}
			catch( UnderflowException $e )
			{
				throw new OutOfRangeException( 'upgrade_group_not_exist' );
			}

			if( $admin )
			{
				return TRUE;
			}

			/* Check secondary groups as well */
			if( $member->mgroup_others )
			{
				/* In some versions we stored as ",1,2," with trailing/preceeding commas, so account for that */
				foreach( explode( ',', trim( $member->mgroup_others, ',' ) ) as $group )
				{
					try
					{
						$admin = (bool) Db::i()->select( 'g_access_cp', 'groups', array( 'g_id=?', $group ) )->first();

						if( $admin )
						{
							return TRUE;
						}
					}
					/* It is possible the user has an old group that no longer exists defined as a secondary group */
					catch( UnderflowException $e ){}
				}
			}

			/* Still here? No permission */
			return FALSE;
		}
	}		
}