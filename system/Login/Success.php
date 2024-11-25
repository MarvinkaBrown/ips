<?php
/**
 * @brief		Successful Login Result
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Events\Event;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Member\Device;
use IPS\MFA\MFAHandler;
use IPS\Request;
use IPS\Session;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Successful Login Result
 */
class Success
{
	/**
	 * @brief	The member who has successfully logged in
	 */
	public Member $member;
	
	/**
	 * @brief	The device
	 */
	public Device $device;
	
	/**
	 * @brief	The handler that processed the login
	 */
	public Handler $handler;
	
	/**
	 * @brief	If the "remember me" box was checked
	 */
	public bool $rememberMe = TRUE;
	
	/**
	 * @brief	If the "sign in anonymously" box was checked
	 */
	public bool $anonymous = FALSE;
	
	/**
	 * Constructor
	 *
	 * @param	Member			$member		The member who has successfully logged in
	 * @param Handler $handler	The handler that processed the login
	 * @param bool $rememberMe	If the "remember me" box was checked
	 * @param bool|null $anonymous	If, NULL, honour the member's anonymous setting, if TRUE or FALSE then override with that value
	 * @param bool $sendNewDeviceEmail	Should a new device email be sent.
	 * @return	void
	 */
	public function __construct( Member $member, Handler $handler, bool $rememberMe = TRUE, bool $anonymous = NULL, bool $sendNewDeviceEmail = TRUE )
	{
		$this->member = $member;
		$this->device = Device::loadOrCreate( $member, $sendNewDeviceEmail );
		$this->handler = $handler;
		$this->rememberMe = $rememberMe;
		$this->anonymous = ( $anonymous === NULL ) ? ( $member->members_bitoptions['is_anon'] or $member->group['g_hide_online_list'] ) : $anonymous;
	}
	
	/**
	 * Get two-factor authentication form required to process login
	 *
	 * @param string|null $area	The area being accessed or NULL to automatically use AuthenticateFront(Known)
	 * @return	string|NULL
	 */
	public function mfa( string $area = NULL ): ?string
	{
		MFAHandler::resetAuthentication();
		
		if ( !$area )
		{
			$area = $this->device->known ? 'AuthenticateFrontKnown' : 'AuthenticateFront';
		}
		
		return MFAHandler::accessToArea( 'core', $area, Url::internal( '' ), $this->member );
	}
	
	/**
	 * Process the login - set the session data, and send required cookies
	 *
	 * @return	void
	 */
	public function process() : void
	{
		/* First, if we are already logged in as someone, log out */
		if ( Member::loggedIn()->member_id )
		{
			Login::logout();
		}
		
		/* Log in */
		Session::i()->setMember( $this->member );
		if ( $this->anonymous )
		{
			Session::i()->setAnon();
		}

		$this->member->last_visit	= $this->member->last_activity;
		$this->member->save();

		/* Log device */
		$this->device->anonymous = $this->anonymous;
		$this->device->updateAfterAuthentication( $this->rememberMe, $this->handler );
		
		/* Remove any noCache cookies */
		Request::i()->setCookie( 'noCache', 0, DateTime::ts( time() - 86400 ) );
		
		/* Member sync */
		Event::fire( 'onLogin', $this->member );
		$this->member->profileSync();
	}
}