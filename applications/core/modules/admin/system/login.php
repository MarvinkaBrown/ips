<?php
/**
 * @brief		Admin CP Login
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Mar 2013
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\extensions\core\AdminNotifications\ConfigurationError;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Login as LoginClass;
use IPS\Login\Exception;
use IPS\Member;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function is_null;
use function substr;
use const IPS\CIC;
use const IPS\IN_DEV;
use const IPS\RECOVERY_MODE;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Login
 */
class login extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Log In
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Do we have an unfinished upgrade? */
		if ( !IN_DEV and ( !RECOVERY_MODE OR !isset( Request::i()->noWarning ) ) and Settings::i()->setup_in_progress )
		{
			/* Don't allow the upgrade in progress page to be cached, it will only be displayed for a very short period of time */
			foreach( Output::getNoCacheHeaders() as $headerKey => $headerValue )
			{
				header( "{$headerKey}: {$headerValue}" );
			}
			include( ROOT_PATH . '/admin/upgrade/upgradeStarted.php' );
			session_abort();
			exit;
		}

		/* Do we have an upgrade available to install? */
		if ( !IN_DEV and !isset( Request::i()->noWarning ) )
		{
			if( Application::load('core')->long_version < Application::getAvailableVersion('core') and Application::load('core')->version != Application::getAvailableVersion( 'core', TRUE ) )
			{
				/* Force no caching */
				@header( "Cache-control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0" );
				@header( "Expires: 0" );

				if ( CIC )
				{
					include( ROOT_PATH . '/admin/upgrade/upgradeAvailableCic.php' );
				}
				else
				{
					include( ROOT_PATH . '/admin/upgrade/upgradeAvailable.php' );
				}
				session_abort();
				exit;
			}
		}

		/* Init login class */
		$url = Url::internal( "app=core&module=system&controller=login", 'admin' );
		if ( isset( Request::i()->noWarning ) )
		{
			$url = $url->setQueryString( 'noWarning', 1 );
		}
		if ( isset( Request::i()->ref ) )
		{
			$url = $url->setQueryString( 'ref', Request::i()->ref );
		}
		$login = new LoginClass( $url, LoginClass::LOGIN_ACP );
		
		/* Authenticate */
		$error = NULL;
		try
		{
			/* If we were successful... */
			if ( $success = $login->authenticate() )
			{
				/* Check we can actually access the ACP */
				if ( $success->member->isAdmin() )
				{
					/* If we need to do two-factor authentication, do that */
					if ( $success->mfa( 'AuthenticateAdmin' ) )
					{
						$_SESSION['processing2FA'] = array( 'memberId' => $success->member->member_id );
						
						$url = Url::internal( 'app=core&module=system&controller=login&do=mfa', 'admin', 'login' );
						if ( isset( Request::i()->ref ) )
						{
							$url = $url->setQueryString( 'ref', Request::i()->ref );
						}
						if ( isset( Request::i()->auth ) )
						{
							$url = $url->setQueryString( 'auth', Request::i()->auth );
						}
						Output::i()->redirect( $url );
					}

					$success->device->updateAfterAuthentication( FALSE, $success->handler, FALSE, FALSE );
					/* Otherwise go ahead */
					$this->_doLogin( $success->member );
				}
				/* ... otherwise show an error */
				else
				{
					$error = 'no_access_cp';
					$this->_log( 'fail' );
				}
			}
		}
		catch ( Exception $e )
		{
			$error = $e->getMessage();
			$this->_log( 'fail' );
		}
		
		/* Have we been sent here because of an IP address mismatch? */
		if ( is_null( $error ) AND isset( Request::i()->error ) )
		{
			switch( Request::i()->error )
			{
				case 'BAD_IP':
					$error = Member::loggedIn()->language()->addToStack( 'cp_bad_ip' );
				break;
				
				case 'NO_ACPACCESS':
					$error = Member::loggedIn()->language()->addToStack( 'no_access_cp' );
				break;
			}
		}

		/* Display Login Form */
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_system.js', 'core', 'admin' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/login.css', 'core', 'admin' ) );
		Output::i()->sendOutput( Theme::i()->getTemplate( 'system' )->login( $login, $error, FALSE ) );
	}
	
	/**
	 * MFA
	 *
	 * @param	string|null	$mfaOutput	If coming from _doLogin, the existing MFA output
	 * @return	void
	 */
	protected function mfa( ?string $mfaOutput=NULL ) : void
	{
		/* Have we logged in? */
		$member = NULL;
		if ( isset( $_SESSION['processing2FA']  ) )
		{
			$member = Member::load( $_SESSION['processing2FA']['memberId'] );
		}
		if ( !$member AND !$member->member_id )
		{
			Output::i()->redirect( Url::internal( '', 'admin' ) );
		}
		
		/* Set the referer in the URL */
		$url = Url::internal( 'app=core&module=system&controller=login&do=mfa', 'admin', 'login' );
		if ( isset( Request::i()->ref ) )
		{
			$url = $url->setQueryString( 'ref', Request::i()->ref );
		}
		if ( isset( Request::i()->auth ) )
		{
			$url = $url->setQueryString( 'auth', Request::i()->auth );
		}
		
		/* Have we already done 2FA? */
		$output = $mfaOutput ?: MFAHandler::accessToArea( 'core', 'AuthenticateAdmin', $url, $member );
		if ( !$output )
		{			
			$this->_doLogin( $member, TRUE );
		}
		
		/* Nope, displau the 2FA form over the login page */
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/login.css', 'core', 'admin' ) );
		Output::i()->sendOutput( Theme::i()->getTemplate( 'system' )->mfaLogin( $output ) );
	}
	
	/**
	 * Process log in
	 *
	 * @param	Member		$member			The member
	 * @param	bool			$bypass2FA		If true, will not perform 2FA check
	 * @return	void
	 */
	protected function _doLogin( Member $member, bool $bypass2FA = FALSE ) : void
	{
		/* Check if we need to send any ACP notifications */
		ConfigurationError::runChecksAndSendNotifications();
		
		/* Set the referer in the URL */
		$url = Url::internal( 'app=core&module=system&controller=login&do=mfa', 'admin', 'login' );
		if ( isset( Request::i()->ref ) )
		{
			$url = $url->setQueryString( 'ref', Request::i()->ref );
		}
		if ( isset( Request::i()->auth ) )
		{
			$url = $url->setQueryString( 'auth', Request::i()->auth );
		}

		/* Do we need to do 2FA? */
		if ( !$bypass2FA and $output = MFAHandler::accessToArea( 'core', 'AuthenticateAdmin', $url, $member ) )
		{
			$_SESSION['processing2FA'] = array( 'memberId' => $member->member_id );

			$this->mfa( $output );
			return;
		}
		
		/* Set the member */
		Session::i()->setMember( $member );
		
		/* Log */
		$this->_log( 'ok' );

		/* Clean out any existing session ID in the URL */
		$queryString = array();
		if( isset( Request::i()->ref ) )
		{
			parse_str( base64_decode( Request::i()->ref ), $queryString );
		}

		/* Do we need to show the installation onboard screen? */
		if( isset( Settings::i()->onboard_complete ) AND ( Settings::i()->onboard_complete == 0 OR ( Settings::i()->onboard_complete != 1 AND Settings::i()->onboard_complete < time() ) ) )
		{
			/* We flag that onboarding is complete now so that if the admin clicks away from the page they're not immediately taken back. This is supposed to be helpful, not a hindrance. */
			Settings::i()->changeValues( array( 'onboard_complete' => 1 ) );

			Output::i()->redirect( Url::internal( "app=core&module=overview&controller=onboard&do=initial", 'admin' )->csrf() );
		}
				
		/* Boink - if we're in recovery mode, go there */
		if ( RECOVERY_MODE )
		{
			Output::i()->redirect( Url::internal( "app=core&module=support&controller=recovery" )->csrf(), '', 303 );
		}
		else
		{
			Output::i()->redirect( Url::internal( http_build_query( $queryString, '', '&' ) ), '', 303 );
		}
	}
		
	/**
	 * Log Out
	 *
	 * @return void
	 */
	protected function logout() : void
	{
		Session::i()->csrfCheck();
		
		session_destroy();
		Output::i()->redirect( Url::internal( "app=core&module=system&controller=login&_fromLogout=1" ) );
	}
	
	/**
	 * Log
	 *
	 * @param	string	$status	Status ['fail','ok']
	 * @return void
	 */
	protected function _log( string $status ) : void
	{
		/* Generate request details */
		foreach( Request::i() as $k => $v )
		{
			if ( $k == 'password' AND mb_strlen( $v ) > 1 )
			{
				$v = $v ? ( (mb_strlen( $v ) - 1) > 0 ? str_repeat( '*', mb_strlen( $v ) - 1 ) : '' ) . mb_substr( $v, -1, 1 ) : '';
			}
			$request[ $k ] = $v;
		}
		
		$save = array(
			'admin_ip_address'		=> Request::i()->ipAddress(),
			'admin_username'		=> Request::i()->auth ? substr( Request::i()->auth, 0, 255 ) : '',
			'admin_time'			=> time(),
			'admin_success'			=> ( $status == 'ok' ) ? 1 : 0,
			'admin_request'	=> json_encode( $request ),
		);
		
		Db::i()->insert( 'core_admin_login_logs', $save );
	}

	/**
	 * Return current CSRF token
	 *
	 * @return void
	 */
	public function getCsrfKey() : void
	{
		/* Don't cache the CSRF key */
		Output::setCacheTime( false );

		Output::i()->json( [ 'key' => Session::i()->csrfKey ] );
	}
}