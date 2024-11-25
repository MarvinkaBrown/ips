<?php
/**
 * @brief		Login Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Data\Store;
use IPS\Events\Event;
use IPS\Extensions\SSOAbstract;
use IPS\Http\Url;
use IPS\Login\Exception;
use IPS\Login\Exception as LoginException;
use IPS\Login\Handler;
use IPS\Login\Success;
use IPS\Member\Device;
use IPS\Session\Front;
use OutOfRangeException;
use function count;
use function defined;
use function function_exists;
use function get_class;
use function in_array;
use function is_array;
use function is_string;
use function ord;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login Handler
 */
class Login
{
	/**
	 * @brief	Using username for login
	 */
	const AUTH_TYPE_USERNAME	= 1;
	
	/**
	 * @brief	Using email for login
	 */
	const AUTH_TYPE_EMAIL		= 2;

	/**
	 * @brief	Front end login form
	 */
	const LOGIN_FRONT = 1;

	/**
	 * @brief	AdminCP login form
	 */
	const LOGIN_ACP = 2;

	/**
	 * @brief	Login form shown on registration form
	 */
	const LOGIN_REGISTRATION_FORM = 3;

	/**
	 * @brief	Requesting reauthentication
	 */
	const LOGIN_REAUTHENTICATE = 4;

	/**
	 * @brief	Reauthentication required for account changes
	 */
	const LOGIN_UCP = 5;

	/**
	 * @brief	Username/password form
	 */
	const TYPE_USERNAME_PASSWORD = 1;

	/**
	 * @brief	Button form (i.e. for OAuth-style logins)
	 */
	const TYPE_BUTTON = 2;
	
	/**
	 * @brief	URL
	 */
	public ?Url $url = NULL;
		
	/**
	 * @brief	Login form type
	 */
	public ?int $type = NULL;
	
	/**
	 * @brief	Reauthenticating member
	 */
	public ?Member $reauthenticateAs = null;
	
	/**
	 * Constructor
	 *
	 * @param	Url|null	$url		The URL page for the login screen
	 * @param int $type		One of the LOGIN_* constants
	 * @return	void
	 */
	public function __construct( Url $url = NULL, int $type=1 )
	{
		$this->url = $url;
		$this->type = $type;
		
		if ( $type === static::LOGIN_REAUTHENTICATE or $type === static::LOGIN_UCP )
		{
			$this->reauthenticateAs = Member::loggedIn();
		}
	}
	
	/* !Methods */
	
	/**
	 * Get methods
	 *
	 * @return	array<Handler>
	 */
	public static function methods(): array
	{
		$return = array();
		foreach ( static::getStore() as $row )
		{
			try
			{
				$return[ $row['login_id'] ] = Handler::constructFromData( $row );
			}
			catch ( OutOfRangeException $e ) { }
		}
		return $return;
	}

	/**
	 * Login method Store
	 *
	 * @return	array
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->loginMethods ) )
		{
			Store::i()->loginMethods = iterator_to_array( Db::i()->select( '*', 'core_login_methods', array( 'login_enabled=1' ), 'login_order' )->setKeyField( 'login_id' ) );
		}
		
		return Store::i()->loginMethods;
	}
	
	/**
	 * Get methods
	 *
	 * @return	array
	 */
	protected function _methods(): array
	{
		$methods = array();
		foreach ( static::methods() as $k => $method )
		{
			if (
				( $this->type === static::LOGIN_FRONT and $method->front )
				or ( $this->type === static::LOGIN_UCP and $method->front )
				or ( $this->type === static::LOGIN_ACP and $method->acp )
				or ( $this->type === static::LOGIN_REGISTRATION_FORM and $method->register )
				or ( $this->type === static::LOGIN_REAUTHENTICATE and $method->canProcess( $this->reauthenticateAs ) and !( $method instanceof Handler ) )
			) {
				$methods[ $k ] = $method;
			}
		}
		
		return $methods;
	}
	
	/**
	 * Get methods which use a username and password
	 *
	 * @return	array
	 */
	public function usernamePasswordMethods(): array
	{
		$return = array();
		foreach ( $this->_methods() as $method )
		{
			if ( $method->type() === static::TYPE_USERNAME_PASSWORD )
			{
				$return[ $method->id ] = $method;
			}
		}
		return $return;
	}
	
	/**
	 * Get methods which use a button
	 *
	 * @return	array
	 */
	public function buttonMethods(): array
	{
		$return = array();
		foreach ( $this->_methods() as $method )
		{
			if ( $method->type() === static::TYPE_BUTTON )
			{
				$return[ $method->id ] = $method;
			}
		}
		return $return;
	}

	/**
	 * Return front SSO login URL
	 *
	 * @return string|null
	 */
	public function frontSsoUrl(): ?string
	{
		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() AND $url = $ext->loginUrl() )
			{
				return $url;
			}
		}
		return NULL;
	}
	
	/* !Authentication */

	/**
	 * Authenticate
	 *
	 * @param Handler|null $onlyCheck If provided, will only check the given method
	 * @return    Success|NULL
	 * @throws \Exception
	 */
	public function authenticate( Handler $onlyCheck = NULL ): ?Success
	{
		try
		{
			if ( isset( Request::i()->_processLogin ) )
			{
				Session::i()->csrfCheck();
				
				/* Username/Password */
				if ( Request::i()->_processLogin === 'usernamepassword' )
				{
					$leastOffensiveException = NULL;
					$success = NULL;
					$fails = array();
					$failsNoAccount = array();
					
					foreach ( $this->usernamePasswordMethods() as $method )
					{
						if ( !$onlyCheck or $method->id == $onlyCheck->id )
						{
							try
							{
								if ( $this->type === static::LOGIN_REAUTHENTICATE )
								{
									if ( $method->authenticatePasswordForMember( $this->reauthenticateAs, Request::i()->protect('password') ) )
									{
										$member = $this->reauthenticateAs;
									}
									else
									{
										throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_bad_password', FALSE ), Exception::BAD_PASSWORD, NULL, $this->reauthenticateAs );
									}
								}
								else
								{
									$member = $method->authenticateUsernamePassword( $this, Request::i()->auth, Request::i()->protect('password') );
									if ( $member === TRUE )
									{
										$member = $this->reauthenticateAs;
									}
								}
								
								if ( $member )
								{
									static::checkIfAccountIsLocked($member, TRUE);
									$success = new Login\Success( $member, $method, isset( Request::i()->remember_me ) );
									break;
								}
							}
							catch ( Exception $e )
							{
								if ( $e->getCode() === Exception::BAD_PASSWORD and $e->member )
								{
									$fails[ $e->member->member_id ] = $e->member;
								}
								elseif( $e->getCode() === Exception::NO_ACCOUNT and $e->member AND $e->member->email )
								{
									$failsNoAccount[ $e->member->email ] = $e->member;
								}
								
								if ( $leastOffensiveException === NULL or $leastOffensiveException->getCode() < $e->getCode() )
								{
									$leastOffensiveException = $e;
								}
							}
						}
					}
					
					foreach ( $fails as $failedMember )
					{
						if ( !$success or $success->member->member_id != $failedMember->member_id )
						{
							$failedMember->failedLogin();
						}
					}

					foreach( $failsNoAccount as $failedMember )
					{
						if ( !$success or $success->member->email != $failedMember->email )
						{
							$failedMember->failedLogin();
						}
					}
					
					if ( $success )
					{
						return $success;
					}
					elseif ( $leastOffensiveException )
					{
						throw $leastOffensiveException;
					}
					else
					{
						throw new Exception( 'generic_error', Exception::NO_ACCOUNT );
					}
				}
				/* Buttons */
				elseif ( isset( $this->buttonMethods()[ Request::i()->_processLogin ] ) and ( !$onlyCheck or $onlyCheck->id == $this->buttonMethods()[ Request::i()->_processLogin ]->id ) )
				{
					$method = $this->_methods()[ Request::i()->_processLogin ];
					
					if ( $member = $method->authenticateButton( $this ) )
					{
						if ( $this->type === static::LOGIN_REAUTHENTICATE and $member !== $this->reauthenticateAs )
						{
							throw new Exception( 'login_err_wrong_account', Exception::BAD_PASSWORD );
						}
						
						static::checkIfAccountIsLocked($member, TRUE);
						return new Login\Success( $member, $method );
					}
				}
			}
			/* Backwards Compatibility for login handlers created before 4.3 */
			elseif ( isset( Request::i()->loginProcess ) )
			{
				foreach ( $this->buttonMethods() as $method )
				{
					if ( $method instanceof Handler and get_class( $method ) === 'IPS\Login\\' . IPS::mb_ucfirst( Request::i()->loginProcess ) and ( !$onlyCheck or $method->id == $onlyCheck->id ) )
					{
						if ( $member = $method->authenticateButton( $this ) )
						{
							static::checkIfAccountIsLocked($member, TRUE);
							return new Login\Success( $member, $method, isset( Request::i()->remember_me ) );
						}
					}
				}
			}
		}
		catch ( Exception $e )
		{
			/* If we're about to say the password is incorrect, check if the account is locked and throw that error rather than a bad password error first */
			if ( $e->getCode() === Exception::BAD_PASSWORD and $e->member )
			{
				static::checkIfAccountIsLocked($e->member);
			}
			/* Or if the account doesn't exist but we've tried the brute-force number of times, show the account is locked even though it doesn't exist */
			elseif( $e->getCode() === Exception::NO_ACCOUNT and $e->member AND $e->member->email )
			{
				static::checkIfAccountIsLocked($e->member);
			}

			/* If we're still here, throw the error we got */
			throw $e;
		}

		return NULL;
	}
	
	/* !Account Management Utility Methods */
	
	/**
	 * After authentication (successful or failed) but before
	 * processing the login, check if the account is locked
	 *
	 * @param Member $member		The account
	 * @param bool $success	Boolean value indicating if the login was successful. If TRUE, and the account is not locked, failed logins will be removed.
	 * @note	The $member object may be a guest object with an email address set
	 * @return	void
	 * @throws	\Exception
	 */
	public static function checkIfAccountIsLocked( Member $member, bool $success=FALSE ) : void
	{
		/* Global attempts */
		if( Settings::i()->bruteforce_global_attempts )
		{
			$fromTimestamp = ( new DateTime )->sub( new DateInterval( 'PT' . Settings::i()->bruteforce_global_period .'M' ) )->getTimestamp();
			$globalFailures = Db::i()->select( 'count(*) as total', 'core_login_failures', [ 'login_date>? AND login_ip_address=?', $fromTimestamp, Request::i()->ipAddress() ] )->first();

			/* Potential password-spraying activity */
			if( $globalFailures >= (int) Settings::i()->bruteforce_global_attempts )
			{
				throw new LoginException( 'login_err_locked_nounlock', LoginException::ACCOUNT_LOCKED );
			}
		}

		$unlockTime = $member->unlockTime();
		if ( $unlockTime !== FALSE )
		{
			/* Notify the member if they've been locked */
			if( $member->failedLoginCount( Request::i()->ipAddress() ) == Settings::i()->ipb_bruteforce_attempts )
			{
				/* Can we get a physical location */
				try
				{
					$location = GeoLocation::getRequesterLocation();
				}
				catch ( \Exception $e )
				{
					$location = Request::i()->ipAddress();
				}

				if( $member->member_id )
				{
					Email::buildFromTemplate( 'core', 'account_locked', array( $member, $location, $unlockTime ?? NULL ), Email::TYPE_TRANSACTIONAL )->send( $member );
					$member->logHistory( 'core', 'login', array( 'type' => 'lock', 'count' => $member->failedLoginCount( Request::i()->ipAddress() ), 'unlockTime' => $unlockTime?->getTimestamp() ) );
				}
			}

			if ( Settings::i()->ipb_bruteforce_period and Settings::i()->ipb_bruteforce_unlock )
			{
				$diffValue = $unlockTime->diff( new DateTime() )->format('%i') ?: 1;

				throw new Exception( Member::loggedIn()->language()->addToStack( 'login_err_locked_unlock', FALSE, array( 'pluralize' => array( $diffValue ) ) ), Exception::ACCOUNT_LOCKED );
			}
			else
			{
				throw new Exception( 'login_err_locked_nounlock', Exception::ACCOUNT_LOCKED );
			}
		}
		elseif ( $success )
		{
			Db::i()->delete( 'core_login_failures', [ 'login_member_id=? AND login_ip_address=?', $member->member_id, Request::i()->ipAddress() ] );
		}
	}

	/**
	 * Check if a given username is allowed
	 *
	 * @param 	string 	$username	Desired username
	 * @return 	bool
	 */
	public static function usernameIsAllowed( string $username ): bool
	{
		if( Settings::i()->username_characters and !preg_match( Settings::i()->username_characters, $username ) )
		{
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Check if a given username is in use
	 * Returns string with error message or FALSE if not in use
	 *
	 * @param string $username	Desired username
	 * @param Member|null $exclude	If provided, that member will be excluded from the check
	 * @param bool|Member $admin		Boolean value indicating if error message can include details about which login method has claimed it
	 * @return	string|false
	 */
	public static function usernameIsInUse(string $username, Member $exclude = NULL, bool|Member $admin = FALSE ): bool|string
	{
		/* Check locally */
		$existingMember = Member::load( $username, 'name' );
		if ( $existingMember->member_id and ( !$exclude or $exclude->member_id != $existingMember->member_id ) )
		{
			return Member::loggedIn()->language()->addToStack('member_name_exists');
		}
		
		/* Check each handler */
		foreach( static::methods() as $k => $handler )
		{
			if( $handler->usernameIsInUse( $username, $exclude ) === TRUE )
			{
				if( $admin )
				{
					return Member::loggedIn()->language()->addToStack( 'member_name_exists_admin', FALSE, array('sprintf' => array( $handler->_title ) ) );
				}
				else
				{
					return Member::loggedIn()->language()->addToStack('member_name_exists');
				}
			}
		}
		
		/* Still here? We're good */
		return FALSE;
	}
	
	/**
	 * Check if a given email address is in use
	 * Returns string with error message or FALSE if not in use
	 *
	 * @param string $email		Desired email address
	 * @param Member|null $exclude	If provided, that member will be excluded from the check
	 * @param bool|Member $admin		Boolean value indicating if error message can include details about which login method has claimed it
	 * @return	string|false
	 */
	public static function emailIsInUse(string $email, Member $exclude = NULL, bool|Member $admin = FALSE ): bool|string
	{
		/* Check locally */
		$existingMember = Member::load( $email, 'email' );
		if ( $existingMember->member_id and ( !$exclude or $exclude->member_id != $existingMember->member_id ) )
		{
			$url = Url::internal( "app=core&module=system&controller=lostpass", "front", "lostpassword" );
			return Member::loggedIn()->language()->addToStack( 'member_email_exists', FALSE, array( 'sprintf' => array( (string) $url ) ) );
		}
		
		/* Check each handler */
		foreach( static::methods() as $k => $handler )
		{
			if( $handler->emailIsInUse( $email, $exclude ) === TRUE )
			{
				if( $admin )
				{
					return Member::loggedIn()->language()->addToStack( 'member_email_exists_admin', FALSE, array('sprintf' => array( $handler->_title ) ) );
				}
				else
				{
					$url = Url::internal( "app=core&module=system&controller=lostpass", "front", "lostpassword" );
					return Member::loggedIn()->language()->addToStack( 'member_email_exists', FALSE, array( 'sprintf' => array( (string) $url ) ) );
				}
			}
		}
		
		/* Still here? We're good */
		return FALSE;
	}
	
	/* !Misc Utility Methods */
	
	/**
	 * Compare hashes in fixed length, time constant manner.
	 *
	 * @param string|null $expected	The expected hash
	 * @param string|null $provided	The provided input
	 * @return	boolean
	 */
	public static function compareHashes( string $expected=NULL, string $provided=NULL ): bool
	{
		if ( !is_string( $expected ) || !is_string( $provided ) || $expected === '*0' || $expected === '*1' || $provided === '*0' || $provided === '*1' ) // *0 and *1 are failures from crypt() - if we have ended up with an invalid hash anywhere, we will reject it to prevent a possible vulnerability from deliberately generating invalid hashes
		{
			return FALSE;
		}
	
		$len = strlen( $expected );
		if ( $len !== strlen( $provided ) )
		{
			return FALSE;
		}
	
		$status = 0;
		for ( $i = 0; $i < $len; $i++ )
		{
			$status |= ord( $expected[ $i ] ) ^ ord( $provided[ $i ] );
		}
		
		return $status === 0;
	}
	
	/**
	 * Return a random string
	 *
	 * @param int $length		The length of the final string
	 * @return	string
	 */
	public static function generateRandomString( int $length=32 ): string
	{
		$return = '';

		if ( function_exists( 'random_bytes' ) )
		{
			$return = substr( bin2hex( random_bytes( $length ) ), 0, $length );
		}
		elseif( function_exists( 'openssl_random_pseudo_bytes' ) )
		{
			$return = substr( bin2hex( openssl_random_pseudo_bytes( ceil( $length / 2 ) ) ), 0, $length );
		}

		/* Fallback JUST IN CASE */
		if( !$return OR strlen( $return ) != $length )
		{
			$return = substr( md5( uniqid( '', true ) ) . md5( uniqid( '', true ) ), 0, $length );
		}

		return $return;
	}
	
	/**
	 * @brief	Cached registration type
	 */
	protected static ?string $_registrationType = NULL;

	/**
	 * Registration Type
	 *
	 * @return string|null
	 */
	public static function registrationType(): ?string
	{
		if ( static::$_registrationType === NULL )
		{
			/* If registrations are enabled */
			if ( Settings::i()->allow_reg )
			{
				switch( Settings::i()->allow_reg )
				{
					// just kept this here for legacy reasons, even if we have an upgrade step to change this now
					case 1 :
					case 'full':
						static::$_registrationType ='full';
						break;
					default:
						return Settings::i()->allow_reg;
				}
			}
			else
			{
				static::$_registrationType = 'disabled';
			}

			if ( in_array( static::$_registrationType, array( 'normal', 'full' ) ) and !Handler::findMethod( 'IPS\Login\Handler\Standard' ) )
			{
				static::$_registrationType = 'disabled';
			}
		}

		return static::$_registrationType;
	}

	/**
	 * Log a user out
	 *
	 * @param Url|null $redirectUrl The URL the user will be redirected to after logging out
	 * @return    void
	 */
	public static function logout( Url $redirectUrl = NULL ) : void
	{
		/* Do not allow the login_key to be re-used */
		if ( isset( Request::i()->cookie['device_key'] ) )
		{
			try
			{
				$device = Device::loadAndAuthenticate( Request::i()->cookie['device_key'], Member::loggedIn() );
				$device->login_key = NULL;
				$device->save();
			}
			catch ( OutOfRangeException $e ) { }
		}
		
		/* Clear cookies */
		Request::i()->clearLoginCookies();

		/* Destroy the session (we have to explicitly reset the session cookie, see http://php.net/manual/en/function.session-destroy.php) */
		$_SESSION = array();
		$params = session_get_cookie_params();
		setcookie( session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"] );
		session_destroy();
		session_start();

		/* Member sync callback */
		Event::fire( 'onLogout', Member::loggedIn(), array( $redirectUrl ?: Url::internal('') ) );

		/* Check SSO Extensions for overloads */
		if( Session::i() instanceof Front )
		{
			foreach ( Application::allExtensions( 'core', 'SSO', FALSE ) as $ext )
			{
				/* @var SSOAbstract $ext */
				if ( $ext->isEnabled() AND $url = $ext->logoutUrl( $redirectUrl ?: Url::internal('') ) )
				{
					Output::i()->redirect( $url );
					exit;
				}
			}
		}
	}
}