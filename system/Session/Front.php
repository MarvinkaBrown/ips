<?php

/**
 * @brief		Front Session Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2013
 */

namespace IPS\Session;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use IPS\Api\OAuthClient;
use IPS\Application;
use IPS\Application\Module;
use IPS\Data\Store;
use IPS\Extensions\SSOAbstract;
use IPS\Platform\Bridge;
use IPS\core\ShareLinks\Service;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Http\Useragent;
use IPS\Login;
use IPS\Member;
use IPS\Member\Device;
use IPS\Request;
use IPS\Session;
use IPS\Session\Store as SessionStore;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use const IPS\CACHE_PAGE_TIMEOUT;
use const IPS\OAUTH_REQUIRES_HTTPS;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Session Handler
 */
class Front extends Session
{
	const LOGIN_TYPE_MEMBER = 0;
	const LOGIN_TYPE_ANONYMOUS = 1;
	const LOGIN_TYPE_GUEST = 2;
	const LOGIN_TYPE_SPIDER = 3;
	const LOGIN_TYPE_INCOMPLETE = 4;

	protected string $sessionId;
	protected bool|null|array $sessionData;

	/**
	 * Guess if the user is logged in
	 *
	 * This is a lightweight check that does not rely on other classes. It is only intended
	 * to be used by the guest caching mechanism so that it can check if the user is logged
	 * in before other classes are initiated.
	 *
	 * This method MUST NOT be used for other purposes as it IS NOT COMPLETELY ACCURATE.
	 *
	 * @return	bool
	 */
	public static function loggedIn(): bool
	{
		/* If we have a "member_id" cookie, we're probably logged in... */
		if ( isset( Request::i()->cookie['member_id'] ) and Request::i()->cookie['member_id'] )
		{
			return TRUE;
		}
		
		/* If the request sent an access token which has GraphQL acceess we'll need to check that */
		if ( isset( $_SERVER['HTTP_X_IPS_ACCESSTOKENMEMBER'] ) or isset( Request::i()->access_token_member ) )
		{
			return TRUE;
		}

		if ( ( $member = Bridge::i()->liveTopicDevPreviewMember() ) AND $member instanceof Member AND $member->member_id )
		{
			return TRUE;
		}
		
		/* Still here: assume not logged in */
		return FALSE;
	}
	
	/**
	 * @brief	Session Data
	 */
	protected array $data	= array();
	
	/**
	 * @brief	Needs saving?
	 */
	protected bool $save	= TRUE;
	
	/**
	 * Open Session
	 *
	 * @param string $savePath	Save path
	 * @param string $sessionName Session Name
	 * @return	bool
	 */
	public function open( string $savePath, string $sessionName ) : bool
	{
		return TRUE;
	}
	
	/**
	 * Read Session
	 *
	 * @param string $sessionId	Session ID
	 * @return	string
	 */
	public function read( string $sessionId ): string
	{
		$this->sessionId = $sessionId;
		
		/* Get user agent info */
		$this->userAgent = Useragent::parse();

		$session = method_exists( SessionStore::i(), 'loadSession' ) ? SessionStore::i()->loadSession( $this->sessionId ) : NULL;

		/* Only use sessions with matching IP address */
		if( $session and Settings::i()->match_ipaddress and $session['ip_address'] != Request::i()->ipAddress() )
		{
			$session = NULL;
		}

		/* Validate member_id cookie against member_id the session belongs to */
		if( $session and isset( Request::i()->cookie['member_id'] ) AND Request::i()->cookie['member_id'] != $session['member_id'] )
		{
			$session = FALSE;
		}
		/* If the session is for a member but the member_id cookie is not present, wipe the session */
		elseif( $session AND $session['member_id'] > 0 AND empty( Request::i()->cookie['member_id'] ) )
		{
			$session = FALSE;
		}

		/* We match bots by browser and IP address, so reset our session ID if we found a matching bot row */
		if( $session AND $session['uagent_type'] == 'search' AND $session['id'] != $this->sessionId )
		{
			$this->sessionId = $session['id'];
		}

		/* Store this so plugins can access */
		$this->sessionData	= $session;

		/* Got one? */
		if ( $session )
		{
			/* If this is a guest and the "running time" on this is less than the guest page cache, or if a member and less than 15 seconds ago, we don't need a database write */
			if ( ( !$session['member_id'] and $session['running_time'] < ( time() - CACHE_PAGE_TIMEOUT ) ) or ( $session['member_id'] and $session['running_time'] < ( time() - 15 ) ) )
			{
				$this->save = TRUE;
			}
			else
			{
				$this->save = FALSE;
			}

			/* Set member */
			try
			{
				$this->member = Member::load( (int) $session['member_id'] );
			}
			catch ( OutOfRangeException $e )
			{
				$this->member = new Member;
			}
		}
		/* We might be able to get the member from a cookie */
		else
		{
			$this->member = new Member;
		}
		
		/* If we don't have a member, but the request *did* send an access token which has GraphQL acceess (i.e. unfettered access to act as the user), then use that */
		if ( !$this->member->member_id and ( isset( $_SERVER['HTTP_X_IPS_ACCESSTOKENMEMBER'] ) or isset( Request::i()->access_token_member ) ) and $authorizationHeader = Request::i()->authorizationHeader() and mb_substr( $authorizationHeader, 0, 7 ) === 'Bearer ' and ( !OAUTH_REQUIRES_HTTPS or Request::i()->isSecure() ) )
		{
			$expectedMember = Member::load( $_SERVER['HTTP_X_IPS_ACCESSTOKENMEMBER'] ?? Request::i()->access_token_member );
			if ( $expectedMember->member_id )
			{
				/* Start by checking the access token is valid and for this member */
				try
				{					
					$accessToken = OAuthClient::accessTokenDetails( mb_substr( $authorizationHeader, 7 ) );
					$client = OAuthClient::load( $accessToken['client_id'] );
					if ( in_array($client->api_access, [ 'graphql', 'both'] ) AND $accessToken['member_id'] === $expectedMember->member_id )
					{
						$success = TRUE;
					}
					else
					{
						$success = FALSE;
					}
				}
				catch ( Exception $e )
				{
					$success = FALSE;
				}
				
				/* Because this is effectively a log in attempt, we need to make sure the account is not locked */
				try
				{
					Login::checkIfAccountIsLocked( $expectedMember, $success );
					
					/* If it isn't, we can either set that we are that member... */
					if ( $success )
					{
						$this->member = $expectedMember;
						$expectedMember->achievementAction( 'core', 'SessionStartDaily' );
					}
					/* Or if the access token wasn't valid, log it as a fail so that it can't be bruteforced */
					else
					{
						$expectedMember->failedLogin();
					}
				}
				catch ( Exception $e )
				{
					// Account is locked. Do nothing.
				}
			}
		}

		/* If we still don't have a member, check the cookies */
		$device = NULL;
		if ( !$this->member->member_id and isset( Request::i()->cookie['device_key'] ) and isset( Request::i()->cookie['member_id'] ) and isset( Request::i()->cookie['login_key'] ) )
		{
			/* Get the member we're trying to authenticate against - do not process cookie-based login if the account is locked */
			$member = Member::load( (int) Request::i()->cookie['member_id'] );
			if ( $member->member_id and $member->unlockTime() === FALSE )
			{
				/* Load and authenticate device device data */
				try
				{
					/* Authenticate */
					$device = Device::loadAndAuthenticate( Request::i()->cookie['device_key'], $member, Request::i()->cookie['login_key'] );

					/* Set member in session */
					$this->member = $member;

					/* Refresh the device key cookie */
					Request::i()->setCookie( 'device_key', Request::i()->cookie['device_key'], ( new DateTime )->add( new DateInterval( 'P1Y' ) ) );

					$member->recordLogin();
					$member->achievementAction( 'core', 'SessionStartDaily' );
					
					/* Update device */
					$device->updateAfterAuthentication( TRUE, NULL, FALSE );
				}
				/* If the device_key/login_key combination wasn't valid, this may be someone trying to bruteforce... */
				catch ( OutOfRangeException $e )
				{
					/* ... so log it as a failed login */
					if( isset( $expectedMember ) and $expectedMember instanceof Member )
					{
						$expectedMember->failedLogin();
					}
					
					/* Then set us as a guest and clear out those cookies */
					$this->member = new Member;
					Request::i()->clearLoginCookies();
				}
			}
			// If the member no longer exists, or the account is locked, set us as a guest and clear out those cookies
			else
			{
				$this->member = new Member;
				Request::i()->clearLoginCookies();
			}
		}

		/* Work out the type */
		if ( $this->member->member_id )
		{
			if ( $this->member->group['g_hide_online_list'] != 2 AND ( ( $session and $session['login_type'] === static::LOGIN_TYPE_ANONYMOUS ) or ( $this->member->members_bitoptions['is_anon'] ) OR $this->member->group['g_hide_online_list'] == 1 ) )
			{
				$type = static::LOGIN_TYPE_ANONYMOUS;
			}
			else if ( !$this->member->name or !$this->member->email )
			{
				$type = static::LOGIN_TYPE_INCOMPLETE;
			}
			else
			{
				$type = static::LOGIN_TYPE_MEMBER;
			}
		}
		else
		{			
			$type = $this->userAgent->bot ? static::LOGIN_TYPE_SPIDER : static::LOGIN_TYPE_GUEST;
		}

		/* Set data */
		$this->data = array(
			'id'						=> $this->sessionId,
			'member_name'				=> $this->member->member_id ? $this->member->name : '',
			'seo_name'					=> $this->member->member_id ? ( $this->member->members_seo_name ?: '' ) : '',
			'member_id'					=> $this->member->member_id ?: 0,
			'ip_address'				=> Request::i()->ipAddress(),
			'browser'					=> $_SERVER['HTTP_USER_AGENT'] ?? '',
			/* We do not want ajax calls to update running time as this affects appearance of being online. If no session exists, we do not want ajax polling to trigger an online list hit so we set running time for time - 31 minutes as
			   online lists look for running times less than 30 minutes. */
			'running_time'				=> ( Request::i()->isAjax() ) ? ( $session ? $session['running_time'] : time() - 1860 ) : time(),
			'login_type'				=> $type,
			'member_group'				=> ( $this->member->member_id ) ? $this->member->member_group_id : Settings::i()->guest_group,
			'current_appcomponent'		=> ( Request::i()->isAjax() ) ? ( $session ? $session['current_appcomponent'] : '' ) : '',
			'current_module'			=> ( Request::i()->isAjax() ) ? ( $session ? $session['current_module'] : '' ) : '',
			'current_controller'		=> ( Request::i()->isAjax() ) ? ( $session ? $session['current_controller'] : NULL ) : NULL,
			'current_id'				=> ( Request::i()->isAjax() ) ? ( $session ? $session['current_id'] : NULL ) : intval( Request::i()->id ),
			'uagent_key'				=> $this->userAgent->browser ?: '',
			'uagent_version'			=> $this->userAgent->browserVersion ?: '',
			'uagent_type'				=> $this->userAgent->bot ? 'search' : 'browser',
			'search_thread_id'			=> $session ? intval( $session['search_thread_id'] ) : 0,
			'search_thread_time'		=> $session ? $session['search_thread_time'] : 0,
			'data'						=> $session ? $session['data'] : '',
			'location_url'				=> $session ? $session['location_url'] : NULL,
			'location_lang'				=> $session ? $session['location_lang'] : NULL,
			'location_data'				=> $session ? $session['location_data'] : NULL,
			'location_permissions'		=> $session ? $session['location_permissions'] : NULL,
			'theme_id'					=> $session ? $session['theme_id'] : 0,
			'in_editor'					=> ( Request::i()->isAjax() ) ? ( $session ? $session['in_editor'] : 0 ) : 0,
			
		);

		/* Is this a spider? */
		if( $this->userAgent->bot )
		{
			/* Is this Facebook? Do we need to treat them as a user of a different group? */
			if( $this->userAgent->bot == 'facebook' )
			{
				if( Service::load( 'facebook', 'share_key' )->enabled )
				{
					if( $this->userAgent->facebookIpVerified( Request::i()->ipAddress() ) AND Settings::i()->fbc_bot_group != Settings::i()->guest_group )
					{
						$this->member->member_group_id	= Settings::i()->fbc_bot_group;
					}
				}
			}
		}

		/* Session read() method MUST return a string, or this can result in PHP errors */
		$result = (string) $this->data['data'];

		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() )
			{
				return $ext->onSessionRead( $this, $result );
			}
		}

		return $result;
	}

	/**
	 * Set Session Member
	 *
	 * @param Member $member	Member object
	 * @return	void
	 */
	public function setMember( Member $member ) : void
	{
		parent::setMember( $member );

		$member->recordLogin();
		$member->achievementAction( 'core', 'SessionStartDaily' );

		/* Make sure session handler saves during write() */
		$this->save = TRUE;
	}

	/**
	 * Write Session
	 *
	 * @param string $sessionId	Session ID
	 * @param string $data		Session Data
	 * @return	bool
	 */
	public function write( string $sessionId, string $data ): bool
	{
		if ( !isset( $this->data['data'] ) or $data !== $this->data['data'] or $this->data['member_id'] != $this->member->member_id )
		{
			$this->save = TRUE;
		}

		/* Don't update if instant notifications are checking to reduce overhead on the session table */
		if ( Request::i()->isAjax() and isset( Request::i()->app ) and Request::i()->app === 'core' and isset( Request::i()->controller ) and Request::i()->controller === 'ajax' and isset( Request::i()->do ) and Request::i()->do === 'instantNotifications' )
		{
			$this->save = FALSE;
		}

		/* Don't update if there is a hit on the manifest. Why do we use the url()? When page caching grabs and returns, the \IPS\Request::i()->do/controller variables are no populated */
		if ( isset( Request::i()->url()->hiddenQueryString['controller'] ) and Request::i()->url()->hiddenQueryString['controller'] === 'metatags' and isset( Request::i()->url()->hiddenQueryString['do'] ) and Request::i()->url()->hiddenQueryString['do'] === 'manifest' )
		{
			$this->save = FALSE;
		}

		/* Don't update if there is a hit on the serviceworker */
		if ( isset( Request::i()->app ) and Request::i()->app === 'core' and isset( Request::i()->controller ) and Request::i()->controller === 'serviceworker' )
		{
			$this->save = FALSE;
		}

		$this->data['member_name']	= $this->member->member_id ? $this->member->name : '';
		$this->data['member_id']	= $this->member->member_id ?: NULL;
		$this->data['data']			= $data;
		$this->setLocationData();

		if ( $this->save === TRUE and ( !empty( Request::i()->cookie ) or $this->userAgent->bot or $this->member->member_id ) ) // If a guest and cookies are disabled we do not write to database to prevent duplicate sessions unless it's a search engine, which we deal with separately
		{
			if( method_exists( '\IPS\Session\Store', 'updateSession' ) )
			{
				SessionStore::i()->updateSession( $this->data );
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Do not update sessions
	 *
	 * @return void
	 */
	public function noUpdate() : void
	{
		$this->save = FALSE;
	}
	
	/**
	 * @brief	Stored engine
	 */
	protected static mixed $engine = NULL;
		
	/**
	 * Clear sessions - abstracted so it can be called externally without initiating a session
	 *
	 * @param int $timeout	Sessions older than the number of seconds provided will be deleted
	 * @return void
	 */
	public static function clearSessions( int $timeout ) : void
	{
		/* Cannot change this from a static method as it is called on garbage collection */
		SessionStore::i()->clearSessions( $timeout );
	}
	
	/**
	 * Set the search start
	 *
	 * @return	void
	 */
	public function startSearch() : void
	{
		$this->data['search_thread_id']		= Db::i()->thread_id;
		$this->data['search_thread_time']	= time();
	}

	/**
	 * Set the search end
	 *
	 * @return	void
	 */
	public function endSearch() : void
	{
		$this->data['search_thread_id']		= 0;
		$this->data['search_thread_time']	= 0;
	}
	
	/**
	 * Set a theme ID
	 *
	 * @param int $themeId		The theme id, of course
	 * @return	void
	 */
	public function setTheme( int $themeId ) : void
	{
		if( !Dispatcher::hasInstance() OR Request::i()->isAjax() )
		{
			return;
		}
		
		$this->data['theme_id'] = $themeId;
		
		$this->save = TRUE;
	}

	/**
	 * Get the theme ID
	 *
	 * @return int|null
	 */
	public function getTheme(): ?int
	{
		if ( isset( $this->data['theme_id'] ) and $this->data['theme_id'] )
		{
			return $this->data['theme_id'];
		}
		
		return NULL;
	}
	
	/**
	 * Set basic location data
	 *
	 * @return	void
	 */
	public function setLocationData() : void
	{
		if( !Dispatcher::hasInstance() OR Request::i()->isAjax() )
		{
			return;
		}

		$this->data['current_appcomponent']	= Dispatcher::i()->application ? Dispatcher::i()->application->directory : '';
		$this->data['current_module']		= Dispatcher::i()->module ? Dispatcher::i()->module->key : '';
		$this->data['current_controller']	= Dispatcher::i()->controller;
		$this->data['current_id']			= intval( Request::i()->id );
	}
	
	/**
	 * Set user as editing
	 *
	 * @return	void
	 */
	public function setUsingEditor() : void
	{
		$this->data['in_editor'] = time();
	}
	
	/**
	 * Set the session location
	 *
	 * @param	Url	$url		URL
	 * @param mixed $groupIds	Permission data
	 * @param string $lang		Language string
	 * @param array $data		Language data. Keys are the words, value is a boolean indicating if it's a language key (TRUE) or should be displayed as-is (FALSE)
	 * @return	void
	 */
	public function setLocation( Url $url, mixed $groupIds, string $lang, array $data=array() ) : void
	{
		if( !Dispatcher::hasInstance() OR Request::i()->isAjax() )
		{
			return;
		}

		$this->data['location_url'] = (string) $url;
		$this->data['location_lang'] = $lang;
		$this->data['location_data'] = json_encode( $data );
        $this->data['current_id'] = intval( Request::i()->id );
		
		if ( !$this->data['current_appcomponent'] )
		{
			$this->setLocationData();
		}
		
		/* Some places use 0 to mean no permission at all but this is lost in the code below */
		if ( $groupIds === 0 )
		{
			$groupIds = (string) $groupIds;
		}		
	
		$groupIds = is_string( $groupIds ) ? explode( ',', $groupIds ) : ( $groupIds ?: NULL );
				
		$app = Application::load( $this->data['current_appcomponent'] );
		if ( !$app->enabled )
		{			
			$groupIds = $groupIds ? array_intersect( $groupIds, explode( ',', $app->disabled_groups ) ) : explode( ',', $app->disabled_groups );
		}
		
		$modulePermissions = Module::get( $this->data['current_appcomponent'], $this->data['current_module'], 'front' )->permissions();
		if ( $modulePermissions['perm_view'] !== '*' )
		{
			$groupIds = $groupIds ? array_intersect( $groupIds, explode( ',', $modulePermissions['perm_view'] ) ) : explode( ',', $modulePermissions['perm_view'] );
		}

		$this->data['location_permissions'] = ( $groupIds !== NULL ) ? ( is_string( $groupIds ) ? $groupIds : implode( ',', $groupIds ) ) : NULL;

		$this->save = TRUE;
	}
	
	/**
	 * Get the session location
	 * 
	 * @param array $row		Row from sessions
	 * @return	string|null
	 */
	public static function getLocation( array $row ): ?string
	{
		$location = NULL;

		if( !$row['location_lang'] )
		{
			return $location;
		}

		try
		{
			if ( $row['location_permissions'] === NULL or $row['location_permissions'] === '*' or Member::loggedIn()->inGroup( explode( ',', $row['location_permissions'] ), TRUE ) )
			{
				$sprintf = array();
				$data = json_decode( $row['location_data'], TRUE );

				if ( !empty( $data ) )
				{
					foreach ( $data as $key => $parse )
					{
						$value		= htmlspecialchars( $parse ? Member::loggedIn()->language()->get( $key ) : $key, ENT_DISALLOWED, 'UTF-8', FALSE );
						$sprintf[]	= $value;
					}
				}

				$location = Member::loggedIn()->language()->addToStack( htmlspecialchars( $row['location_lang'], ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $sprintf ) );

				$location	= "<a href='" . htmlspecialchars( $row['location_url'], ENT_DISALLOWED, 'UTF-8', FALSE ) . "'>" . $location . "</a>";
			}
		}
		catch ( UnderflowException $e ){ }
		
		return $location;
	}
	
	/**
	 * Set the session "login_type"
	 *
	 * @param int $type	Type as defined by the class constants
	 * @return	void
	 */
	public function setType( int $type ) : void
	{
		if ( $this->data['login_type'] !== $type )
		{
			$this->save = TRUE;
		}
		
		switch ( $type )
		{
			case static::LOGIN_TYPE_MEMBER:
			case static::LOGIN_TYPE_ANONYMOUS:
			case static::LOGIN_TYPE_GUEST:
			case static::LOGIN_TYPE_SPIDER:
			case static::LOGIN_TYPE_INCOMPLETE:
				$this->data['login_type'] = $type;
			break;
			default:
				throw new OutOfRangeException();

		}
	}
	
	/**
	 * Set the session as anonymous
	 *
	 * @return	void
	 */
	public function setAnon() : void
	{
		$this->setType( static::LOGIN_TYPE_ANONYMOUS );
	}
	
	/**
	 * Set the session as anonymous
	 *
	 * @return	bool
	 */
	public function getAnon() : bool
	{
		return (bool) $this->data['login_type'] == static::LOGIN_TYPE_ANONYMOUS;
	}
	
	/**
	 * Close Session
	 *
	 * @return	bool
	 */
	public function close() : bool
	{
		return TRUE;
	}
	
	/**
	 * Destroy Session
	 *
	 * @param string $sessionId	Session ID
	 * @return	bool
	 */
	public function destroy( string $sessionId ): bool
	{
		if ( isset( $_SESSION['wizardKey'] ) )
		{
			$dataKey = $_SESSION['wizardKey'];
			unset( Store::i()->$dataKey );
		}

		if( method_exists( '\IPS\Session\Store', 'deleteSession' ) )
		{
			SessionStore::i()->deleteSession( $sessionId );
		}

		return TRUE;
	}
	
	/**
	 * Garbage Collection
	 *
	 * @param int $lifetime	Number of seconds to consider sessions expired beyond
	 * @return	bool
	 */
	public function gc( int $lifetime ): bool
	{
		static::clearSessions( $lifetime );
		return TRUE;
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function init(): void
	{
		if ( ( $member = Bridge::i()->liveTopicDevPreviewMember() ) AND $member instanceof Member AND $member->member_id )
		{
			$this->setMember( $member );
		}

		parent::init();

		foreach( Application::allExtensions( 'core', 'SSO', FALSE ) as $ext )
		{
			/* @var SSOAbstract $ext */
			if( $ext->isEnabled() )
			{
				$ext->onSessionInit( $this );
			}
		}
	}
}