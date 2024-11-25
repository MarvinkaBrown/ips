<?php
/**
 * @brief		Login
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		7 Jun 2013
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Extensions\SSOAbstract;
use IPS\Http\Url;
use IPS\Http\Url\Internal;
use IPS\Login as LoginClass;
use IPS\Login\Exception;
use IPS\Login\Handler;
use IPS\Login\Success;
use IPS\Member;
use IPS\Member\Device;
use IPS\MFA\MFAHandler;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login
 */
class login extends Controller
{
	/**
	 * @brief	Is this for displaying "content"? Affects if advertisements may be shown
	 */
	public bool $isContentPage = FALSE;

	/**
	 * Log In
	 *
	 * @return    void
	 * @throws \Exception
	 */
	protected function manage() : void
	{
		Output::setCacheTime( false );

		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() AND $url = $ext->loginUrl() )
			{
				Output::i()->redirect( $url );
			}
		}

		/* Init login class */
		$login = new LoginClass( Url::internal( "app=core&module=system&controller=login", 'front', 'login' ) );
		
		/* What's our referrer? */
		$postBeforeRegister = NULL;
		$ref = Request::i()->referrer();

		if ( !$ref and isset( Request::i()->cookie['post_before_register'] ) )
		{
			try
			{
				$postBeforeRegister = Db::i()->select( '*', 'core_post_before_registering', array( 'secret=?', Request::i()->cookie['post_before_register'] ) )->first();
			}
			catch( UnderflowException $e ){}
		}
		
		/* Process */
		$error = NULL;
		try
		{
			if ( $success = $login->authenticate() )
			{
				if ( Request::i()->referrer( FALSE, TRUE ) )
				{
					$ref = Request::i()->referrer( FALSE, TRUE );
				}
				elseif ( $postBeforeRegister )
				{
					try
					{
						$class = $postBeforeRegister['class'];
						$ref = $class::load( $postBeforeRegister['id'] )->url();
					}
					catch ( OutOfRangeException $e )
					{
						$ref = Url::internal('');
					}
				}
				elseif( !empty( $_SERVER['HTTP_REFERER'] ) )
				{
					$_ref = Url::createFromString( $_SERVER['HTTP_REFERER'] );
					$ref = ( $_ref instanceof Internal and ( !isset( $_ref->queryString['do'] ) or $_ref->queryString['do'] != 'validating' ) ) ? $_ref : Url::internal('');
				}
				else
				{
					$ref = Url::internal( '' );
				}
				
				if ( $success->mfa() )
				{
					$_SESSION['processing2FA'] = array( 'memberId' => $success->member->member_id, 'anonymous' => $success->anonymous, 'remember' => $success->rememberMe, 'destination' => (string) $ref, 'handler' => $success->handler->id );
					Output::i()->redirect( $ref->setQueryString( '_mfaLogin', 1 ) );
				}
				$success->process();
								
				Output::i()->redirect( $ref->setQueryString( '_fromLogin', 1 ) );
			}
		}
		catch ( Exception $e )
		{
			if ( $e->getCode() === Exception::MERGE_SOCIAL_ACCOUNT )
			{
				$_SESSION['linkAccounts'] = json_encode( [ 'member' => $e->member->member_id, 'handler' => $e->handler->id ] );

				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login&do=link', 'front', 'login' )->setQueryString( 'ref', $ref ), '', 303 );
			}
			
			$error = $e->getMessage();
		}

		/* Are we already logged in? */
		if ( Member::loggedIn()->member_id AND ( !Request::i()->_err OR Request::i()->_err != 'login_as_user_login' ) )
		{
			Output::i()->redirect( Url::internal('') );
		}

		/* If there is only one button handler, redirect */
        if ( !isset( Request::i()->_processLogin ) and !$login->usernamePasswordMethods() and count( $login->buttonMethods() ) == 1 )
		{
			$buttonMethod = $login->buttonMethods()[ array_key_first( $login->buttonMethods() ) ];
			if( method_exists( $buttonMethod, 'authenticateButton' ) )
			{
				$buttonMethod->authenticateButton( $login );
			}
		}

		/* Display Login Form */
		Output::i()->allowDefaultWidgets = FALSE;
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->title = Member::loggedIn()->language()->addToStack('login');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->login( $login, base64_encode( $ref ), $error );
		
		/* Don't cache for a short while to ensure sessions work */
		Request::i()->setCookie( 'noCache', 1 );
		
		/* Set Session Location */
		Session::i()->setLocation( Url::internal( 'app=core&module=system&controller=login', NULL, 'login' ), array(), 'loc_logging_in' );
	}
		
	/**
	 * MFA
	 *
	 * @return	void
	 */
	protected function mfa() : void
	{		
		/* Have we logged in? */
		$member = NULL;
		if ( isset( $_SESSION['processing2FA']  ) )
		{
			$member = Member::load( $_SESSION['processing2FA']['memberId'] );
		}
		if ( !$member->member_id )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		
		/* Where do we want to go? */
		$destination = Url::internal( '' );
		try
		{
			$destination = Url::createFromString( $_SESSION['processing2FA']['destination'] );
		}
		catch ( \Exception $e ) { }	
		
		/* Have we already done 2FA? */
		$device = Device::loadOrCreate( $member, FALSE );
		$output = MFAHandler::accessToArea( 'core', $device->known ? 'AuthenticateFrontKnown' : 'AuthenticateFront', Url::internal( 'app=core&module=system&controller=login&do=mfa', 'front', 'login' ), $member );
		if ( !$output )
		{
			( new Success( $member, Handler::load( $_SESSION['processing2FA']['handler'] ), $_SESSION['processing2FA']['remember'], $_SESSION['processing2FA']['anonymous'], FALSE ) )->process();
			Output::i()->redirect( $destination->setQueryString( '_fromLogin', 1 ), '', 303 );
		}
		
		/* Nope, just send us where we want to go not logged in */
		$qs = array( '_mfaLogin' => 1 );
		if ( isset( Request::i()->_mfa ) )
		{
			$qs['_mfa'] = Request::i()->_mfa;
			if ( isset( Request::i()->_mfaMethod ) )
			{
				$qs['_mfaMethod'] = Request::i()->_mfaMethod;
			}
		}
		elseif ( isset( Request::i()->mfa_auth ) )
		{
			$qs['mfa_auth'] = Request::i()->mfa_auth;
		}
		elseif ( isset( Request::i()->mfa_setup ) )
		{
			$qs['mfa_setup'] = Request::i()->mfa_setup;
		}
		Output::i()->redirect( $destination->setQueryString( $qs ) );
	}
	
	/**
	 * Link Accounts
	 *
	 * @return	void
	 */
	protected function link() : void
	{
		/* Get the member we're linking with */
		if ( !isset( $_SESSION['linkAccounts'] ) )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		$details = json_decode( $_SESSION['linkAccounts'], TRUE );
		$member = Member::load( $details['member'] );
		if ( !$member->member_id )
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		
		/* And then handler to link with */
		$handler = Handler::load( $details['handler'] );
		
		/* Init reauthentication */
		$login = new LoginClass( Url::internal( 'app=core&module=system&controller=login&do=link', 'front', 'login' )->setQueryString( 'ref', isset( Request::i()->ref ) ? Request::i()->ref : NULL ),  LoginClass::LOGIN_REAUTHENTICATE );
		$login->reauthenticateAs = $member;
		$error = NULL;
		
		/* If successful (or if there's no way to reauthenticate which would only happen if a login handler has been deleted)) complete the link... */
		try
		{
			if ( $success = $login->authenticate() or ( !count( $login->usernamePasswordMethods() ) and !count( $login->buttonMethods() ) ) )
			{	
				$handler->completeLink( $member, $details['details'] );
				
				unset( $_SESSION['linkAccounts'] );			
				
				$destination = Request::i()->referrer( FALSE, TRUE ) ?: Url::internal( '' );
				
				$success = new Success( $member, $handler );
				if ( $success->mfa() )
				{
					$_SESSION['processing2FA'] = array( 'memberId' => $success->member->member_id, 'anonymous' => $success->anonymous, 'remember' => $success->rememberMe, 'destination' => (string) $destination, 'handler' => $success->handler->id );
					Output::i()->redirect( $destination->setQueryString( '_mfaLogin', 1 ) );
				}
				$success->process();
				Output::i()->redirect( $destination->setQueryString( '_fromLogin', 1 ) );
			}
		}
		catch ( Exception $e )
		{
			$error = $e->getMessage();
		}
		
		/* Otherwise show the reauthenticate form */
		Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		Output::i()->sidebar['enabled'] = FALSE;
		Output::setCacheTime( false );
		Output::i()->title = Member::loggedIn()->language()->addToStack('login');
		Output::i()->output = Theme::i()->getTemplate( 'system' )->mergeSocialAccount( $handler, $member, $login, $error );
	}
	
	/**
	 * Log Out
	 *
	 * @return void
	 */
	protected function logout() : void
	{
		$member = Member::loggedIn();
		
		/* CSRF Check */
		Session::i()->csrfCheck();
		
		/* Work out where we will be going after log out */
		if( !empty( $_SERVER['HTTP_REFERER'] ) )
		{
			$referrer = Url::createFromString( $_SERVER['HTTP_REFERER'] );
			$redirectUrl = ( $referrer instanceof Internal and ( !isset( $referrer->queryString['do'] ) or $referrer->queryString['do'] != 'validating' ) ) ? $referrer : Url::internal('');
		}
		else
		{
			$redirectUrl = Url::internal( '' );
		}
		
		/* Are we logging out back to an admin user? */
		if( isset( $_SESSION['logged_in_as_key'] ) )
		{
			$key = $_SESSION['logged_in_as_key'];
			unset( Store::i()->$key );
			unset( $_SESSION['logged_in_as_key'] );
			unset( $_SESSION['logged_in_from'] );
			
			Output::i()->redirect( $redirectUrl );
		}
		
		/* Do it */
		LoginClass::logout( $redirectUrl );
		
		/* Redirect */
		Output::i()->redirect( $redirectUrl->setQueryString( '_fromLogout', 1 ) );
	}
	
	/**
	 * Log in as user
	 *
	 * @return void
	 */
	protected function loginas() : void
	{
		if ( !Request::i()->key or ! LoginClass::compareHashes( (string) Store::i()->admin_login_as_user, (string) Request::i()->key ) )
		{
			Output::i()->error( 'invalid_login_as_user_key', '3S167/1', 403, '' );
		}
	
		/* Load member and admin user */
		$member = Member::load( Request::i()->id );
		$admin 	= Member::load( Request::i()->admin );
		
		/* Not logged in as admin? */
		if ( $admin->member_id != Member::loggedIn()->member_id )
		{
			Output::i()->redirect( Url::internal( "app=core&module=system&controller=login", 'front', 'login' )->addRef( (string) Request::i()->url() )->setQueryString( '_err', 'login_as_user_login' ) );
		}
		
		/* Do it */
		$_SESSION['logged_in_from']			= array( 'id' => $admin->member_id, 'name' => $admin->name );
		$unique_id							=  LoginClass::generateRandomString();
		$_SESSION['logged_in_as_key']		= $unique_id;
		Store::i()->$unique_id	= $member->member_id;
		
		/* Ditch the key */
		unset( Store::i()->admin_login_as_user );
		
		/* Redirect */
		Output::i()->redirect( Url::internal( '' ) );
	}
}