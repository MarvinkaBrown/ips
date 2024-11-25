<?php
/**
 * @brief		Known Member Device Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Mar 2017
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\GeoLocation;
use IPS\Http\Useragent;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function is_numeric;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Known Member Device Model
 */
class Device extends ActiveRecord
{	
	/**
	 * @brief	Login keys are valid for 3 months
	 */
	const LOGIN_KEY_VALIDITY = 'P3M';
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_members_known_devices';
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'device_key';
	
	/**
	 * @brief	Is this a known device?
	 */
	public bool $known = TRUE;
	
	/**
	 * Load device for current request, or create one if there isn't one
	 *
	 * @param	Member	$member				The member being authenticated
	 * @param bool $sendNewDeviceEmail	If true, and the associated setting is enabled, and this is a new device, an email will be sent to the member. Only set to FALSE if the user is registering and while the MFA where the email would be redundant.
	 * @return    Device
	 */
	public static function loadOrCreate( Member $member, bool $sendNewDeviceEmail=TRUE ): Device
	{
		if ( isset( Request::i()->cookie['device_key'] ) and mb_strlen( Request::i()->cookie['device_key'] ) === 32 )
		{
			try
			{
				$device = static::loadAndAuthenticate( Request::i()->cookie['device_key'], $member );
			}
			catch ( OutOfRangeException $e )
			{
				$device = new static;
				$device->known = FALSE;
				$device->device_key = Request::i()->cookie['device_key'];
				$device->member_id = $member->member_id;
				
				if ( $sendNewDeviceEmail )
				{
					$device->sendNewDeviceEmail();
				}
			}
		}
		else
		{
			$device = static::createNew();
			$device->known = FALSE;
			$device->member_id = $member->member_id;
			
			if ( $sendNewDeviceEmail )
			{
				$device->sendNewDeviceEmail();
			}
		}
		
		Request::i()->setCookie( 'device_key', $device->device_key, ( new DateTime )->add( new DateInterval( 'P1Y' ) ) );
		
		return $device;
	}
	
	/**
	 * Load, but only if it is valid for a particular member and optionally login key
	 *
	 * @param string $deviceId	The device ID
	 * @param	Member	$member		The member
	 * @param string|null $loginKey	If you also want to authenticate by login key, the login key to check
	 * @return    Device
	 * @throws	OutOfRangeException
	 */
	public static function loadAndAuthenticate( string $deviceId, Member $member, string $loginKey = NULL ): Device
	{
		/* Load the device */
		$device = static::load( $deviceId, NULL, array( 'member_id=?', $member->member_id ) );
		
		/* Check the login key is valid */
		if ( $loginKey !== NULL )
		{
			/* Login keys expire after 3 months - if it has been more than 3 months since it was generted, do not authenticate */
			if ( $device->last_seen < ( new DateTime )->sub( new DateInterval( static::LOGIN_KEY_VALIDITY ) )->getTimestamp() )
			{
				throw new OutOfRangeException;
			}
			
			/* Validate login key is valid - if there is no login_key set for the device, it is because the device has been deauthorized */
			if ( !$device->login_key OR !Login::compareHashes( (string) $device->login_key, $loginKey ) )
			{
				throw new OutOfRangeException;
			}
		}
		
		/* Return */		
		return $device;
	}
	
	/**
	 * Create a new device with unique key
	 *
	 * @return    Device
	 */
	public static function createNew(): Device 	// we need the underscore version as long as we have monkey patching
	{
		do
		{
			$deviceKey = Login::generateRandomString();
			
			try
			{
				Db::i()->select( 'device_key', 'core_members_known_devices', array( 'device_key=?', $deviceKey ) )->first();
				$generatedDeviceKeyInUse = TRUE;
			}
			catch ( UnderflowException $e )
			{
				$generatedDeviceKeyInUse = FALSE;
			}
		}
		while ( $generatedDeviceKeyInUse );
		
		$object = new self;
		$object->device_key = $deviceKey;
		return $object;
	}

	/**
	 * Update after logging in / automatic authentication with current request's user agent / IP address, etc.
	 *
	 * @param bool|null $rememberMe			Remember me? NULL can be provided if the login is being processed from somewhere that doesn't ask
	 * @param	Handler|null	$loginHandler		The login handler which processed the login, or NULL if updating an existing login. Can also be empty string for logins that weren't processed by any handler (such as after registration or using the lost password feature)
	 * @param bool $refreshLoginKey	If login key should be refreshed (FALSE for automatic logins which will need the same login key subsequently)
	 * @param bool $setCookies			Should the cookies be set ( FALSE for ACP login )
	 * @return	void
	 */
	public function updateAfterAuthentication( ?bool $rememberMe, Handler $loginHandler=NULL, bool $refreshLoginKey=TRUE, bool $setCookies = TRUE ) : void
	{
		$this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? NULL;
		if ( $refreshLoginKey )
		{
			$this->login_key = $rememberMe ? Login::generateRandomString() : NULL;
		}
		$this->last_seen = time();
		if ( $loginHandler !== NULL )
		{
			$this->login_handler = $loginHandler->id;
		}
		$this->save();
		
		$this->logIpAddress( Request::i()->ipAddress() );

		if ( $setCookies )
		{
			$cookieExpiration = ( new DateTime )->add( new DateInterval( static::LOGIN_KEY_VALIDITY ) );
			if ( $rememberMe === NULL )
			{
				Request::i()->setCookie( 'member_id', $this->member_id, $cookieExpiration );
				Request::i()->setCookie( 'loggedIn', time(), $cookieExpiration, FALSE );
			}
			elseif ( $rememberMe === TRUE )
			{
				Request::i()->setCookie( 'member_id', $this->member_id, $cookieExpiration );
				Request::i()->setCookie( 'login_key', $this->login_key, $cookieExpiration );
				Request::i()->setCookie( 'loggedIn', time(), $cookieExpiration, FALSE );
			}
			else
			{
				Request::i()->setCookie( 'member_id', $this->member_id, NULL ); // Just tells the guest caching mechanism that we are logged in, so it can expire on the session end
				Request::i()->setCookie( 'login_key', NULL ); // Clear it in case they previously had chosen "Remember Me"
				Request::i()->setCookie( 'loggedIn', time(), $cookieExpiration, FALSE );
			}
		}
	}
	
	/**
	 * Log an IP address as been having used by this devuce
	 *
	 * @param string $ipAddress	The IP Address
	 * @return	void
	 */
	public function logIpAddress( string $ipAddress ) : void
	{
		Db::i()->insert( 'core_members_known_ip_addresses', array(
			'device_key'	=> $this->device_key,
			'member_id'		=> $this->member_id,
			'ip_address'	=> $ipAddress,
			'last_seen'		=> time()
		), TRUE );
	}
	
	/**
	 * Get user agent data
	 *
	 * @return	Useragent
	 */
	public function userAgent(): Useragent
	{
		return Useragent::parse( $this->user_agent );
	}
	
	/**
	 * Get login method
	 *
	 * @return	Handler|NULL
	 */
	public function loginMethod(): ?Handler
	{
		if ( is_numeric( $this->login_handler ) )
		{
			try
			{
				return Handler::load( $this->login_handler );
			}
			catch ( OutOfRangeException $e )
			{
				return NULL;
			}
		}
		return NULL;
	}
	
	/**
	 * Send email to user notifying them about the new device
	 *
	 * @return	void
	 */
	protected function sendNewDeviceEmail() : void
	{
		$member = Member::load( $this->member_id );

		if ( Settings::i()->new_device_email and $member->members_bitoptions['new_device_email'] )
		{			
			try
			{
				$location = GeoLocation::getRequesterLocation();
			}
			catch ( Exception $e )
			{
				$location = NULL;
			}
			
			Email::buildFromTemplate( 'core', 'new_device', array( $member, $this, $location ), Email::TYPE_TRANSACTIONAL )->send( $member );
		}
		
		$member->logHistory( 'core', 'login', array( 'type' => 'new_device', 'device' => $this->device_key, 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? NULL ), FALSE );
	}
	
	/**
	 * Get the WHERE clause for save()
	 *
	 * @return	array
	 */
	protected function _whereClauseForSave() : array
	{
		return array( 'device_key=? AND member_id=?', $this->device_key, $this->member_id );
	}
}