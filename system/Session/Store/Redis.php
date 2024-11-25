<?php
/**
 * @brief		Redis Session Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 September 2017
 */

namespace IPS\Session\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use IPS\Member\Group;
use IPS\Redis as RedisClass;
use IPS\Session\Front;
use IPS\Session\Store;
use IPS\Settings;
use RedisException;
use function array_slice;
use function count;
use function defined;
use function in_array;
use function is_array;
use function is_null;
use const IPS\TEXT_ENCRYPTION_KEY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis Session Handler
 */
class Redis extends Store
{
	/**
	 * @brief	Default expiration for keys in seconds
	 */
	static protected int $ttl = 1800; #30 mins

	/**
	 * Return the hash we will use to obfuscate the session ID
	 *
	 * @return string
	 */
	public static function getHash(): string
	{
		if ( TEXT_ENCRYPTION_KEY )
		{
			return TEXT_ENCRYPTION_KEY;
		}

		return Settings::i()->sql_pass;
	}
	
	/**
	 * Load the session from the storage engine 
	 *
	 * @param string $sessionId	Session ID
	 * @return	array|NULL
	 */
	public function loadSession( string $sessionId ): ?array
	{
		if ( $result = RedisClass::i()->hGetAll( static::_key( 'session_id_' . md5( $sessionId . static::getHash() ) ) ) )
		{
			try
			{
				return RedisClass::i()->decode( $result['data'] );
			}
			catch( RedisException $e ){}
		}
		
		return NULL;
	}
	
	/**
	 * Update the session storage engine
	 *
	 * @param array $data	Session data to store
	 * @return void
	 */
	public function updateSession( array $data ) : void
	{
		/* Groups are loaded into memory so this does not cause a query */
		$group = Group::load( $data['member_group'] );

		/* Update the specific session */
		RedisClass::i()->del( static::_key( 'session_id_' . md5( $data['id'] . static::getHash() ) ) );
		RedisClass::i()->hMSet( static::_key( 'session_id_' . md5( $data['id'] . static::getHash() ) ), array(
			'member_id'		=> $data['member_id'],
			'member_name'	=> $data['member_name'],
			'seo_name'		=> $data['seo_name'],
			'member_group'	=> $data['member_group'],
			'login_type'	=> $data['login_type'],
			'in_editor'	    => $data['in_editor'],
			'data' 			=> RedisClass::i()->encode( $data )
		), static::$ttl );
		
		/* Update the list of sessions for the online list [ microtime => sessionID ] */
		RedisClass::i()->zAdd( static::_key( 'session_map' ), time(), 'session_id_' . md5( $data['id'] . static::getHash() ), static::$ttl );
		
		/* Update users list */
		if ( $data['uagent_type'] == 'search' )
		{
			/* Make a unique row based on IP and user-agent to prevent multiple rows for each spider */
			RedisClass::i()->zAdd( static::_key( 'session_online_spiders' ), time(), md5( $data['ip_address'] . $data['browser'] ), static::$ttl );
		}
		else if ( $data['member_id'] )
		{
			RedisClass::i()->zAdd( static::_key( 'session_online_users' ), time(), $data['member_id'] . '__' . 'session_id_' . md5( $data['id'] . static::getHash() ), static::$ttl );
		}
		else
		{
			/* A guest may have one ip address but multiple devices, but we don't really need to track that */
			RedisClass::i()->zAdd( static::_key( 'session_online_guests' ), time(), md5( $data['ip_address'] ), static::$ttl );
		}
				
		/* Delete old items */
		if ( ! RedisClass::i()->get( static::_key( 'session_cleanup' ) ) )
		{
			/* Do a little clean up */
			RedisClass::i()->zRemRangeByScore( static::_key( 'session_map' ), 0, time() - static::$ttl );
			RedisClass::i()->zRemRangeByScore( static::_key( 'session_online_spiders' ), 0, time() - static::$ttl );
			RedisClass::i()->zRemRangeByScore( static::_key( 'session_online_users' ), 0, time() - static::$ttl );
			RedisClass::i()->zRemRangeByScore( static::_key( 'session_online_guests' ), 0, time() - static::$ttl );
			
			/* And do it again in 3ish mins */
			RedisClass::i()->setEx( static::_key( 'session_cleanup' ), 180, time() );
		}
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param string $sessionId	Session ID
	 * @return	void
	 */
	public function deleteSession( string $sessionId ) : void
	{
		$data = $this->loadSession( $sessionId );
		RedisClass::i()->del( static::_key( 'session_id_' . md5( $sessionId . static::getHash() ) ) );
		RedisClass::i()->zRem( static::_key( 'session_map' ), 'session_id_' . md5( $sessionId . static::getHash() ) );
		RedisClass::i()->zRem( static::_key( 'session_online_spiders' ), md5( $data['ip_address'] . $data['browser'] ) );
		RedisClass::i()->zRem( static::_key( 'session_online_users' ), $data['member_id'] . '__' . 'session_id_' . md5( $data['id'] . static::getHash() ) );
		RedisClass::i()->zRem( static::_key( 'session_online_guests' ), md5( $data['ip_address'] ) );
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param	int			$memberId	You can probably figure this out right?
	 * @param	string|NULL	$userAgent	User Agent [optional]
	 * @param	array|NULL	$keepSessionIds	Array of session ids to keep [optional]
	 * @return	void
	 */
	public function deleteByMember( int $memberId, string $userAgent=NULL, array $keepSessionIds=NULL ) : void
	{
		if ( ! is_array( $keepSessionIds ) and ! is_null( $keepSessionIds ) )
		{
			$keepSessionIds = array( $keepSessionIds );
		}
		
		$sessionMap = RedisClass::i()->zRange( static::_key( 'session_online_users' ), 0, -1 );

		foreach( $sessionMap as $index => $redisKey )
		{
			$key = explode( '__', $redisKey );
			$session = RedisClass::i()->hMGet( $key[1], array( 'data' ) );

			try
			{
				$sessionMap[ $index ] = RedisClass::i()->decode( $session['data'] );
			}
			catch( RedisException $e ){}
		}

		foreach( $sessionMap as $session )
		{
			/* SessionMap may not link to a valid session, remove if that is the case */
			if( !is_array( $session ) )
			{
				RedisClass::i()->zRem( static::_key( 'session_map' ), $session );
				continue;
			}

			$delete = false;
			if ( $session['member_id'] == $memberId )
			{
				$delete = true;
			}
			
			if ( $userAgent and $userAgent != $session['browser'] )
			{
				$delete = false;
			}
			  
			if ( $keepSessionIds and in_array( $session['id'], $keepSessionIds ) )
			{
				$delete = false;
			}
			
			if ( $delete )
			{
				$this->deleteSession( $session['id'] );
			}
		}
	}
	
	/**
	 * Fetch all active session keys
	 *
	 * @return	array of session IDs
	 */
	public function getSessionIds(): array
	{
		if ( $result = RedisClass::i()->zRangeByScore( static::_key( 'session_map' ), '-inf', '+inf', array( 'withscores' => false ) ) )
		{
			return $result;
		}
		
		return array();
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param int $memberId	You can probably figure this out right?
	 * @return	array|FALSE
	 */
	public function getLatestMemberSession( int $memberId ): array|FALSE
	{
		$redis = RedisClass::i()->zRevRangeByScore( static::_key( 'session_online_users' ), '+inf', '-inf', array('withscores' => FALSE, 'alpha' => TRUE ) );

		if( is_array( $redis ) )
		{
			foreach ( $redis as $data )
			{
				[ $id, $sessionKey ] = explode( '__', $data );

				if ( $id == $memberId )
				{
					if ( $result = RedisClass::i()->hGetAll( static::_key( $sessionKey ) ) )
					{
						try
						{
							return RedisClass::i()->decode( $result['data'] );
						}
						catch ( RedisException $e ) {}
					}
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Clear sessions - abstracted so it can be called externally without initiating a session
	 *
	 * @param int $timeout	Sessions older than the number of seconds provided will be deleted
	 * @return void
	 */
	public static function clearSessions( int $timeout ) : void
	{
		/* Remove the public facing items. This is only called by PHP's session gc so individual sessions do not need removing as they are cleaned by Redis' TTL */
		RedisClass::i()->zRemRangeByScore( static::_key( 'session_map' ), 0, time() - $timeout );
		RedisClass::i()->zRemRangeByScore( static::_key( 'session_online_spiders' ), 0, time() - $timeout );
		RedisClass::i()->zRemRangeByScore( static::_key( 'session_online_users' ), 0, time() - $timeout );
		RedisClass::i()->zRemRangeByScore( static::_key( 'session_online_guests' ), 0, time() - $timeout );
		RedisClass::i()->del( static::_key( 'session_onlinelist' ) );
	}
	
	/**
	 * Redis key
	 */
	protected static ?string $_redisKey = NULL;
	
	/**
	 * Returns a key to be stored with Redis
	 *
	 * @param string $key		Key suffix
	 * @return	string
	 */
	protected static function _key( string $key ): string
	{
		/* Only manage the session_onlist key which is prone to corruption. We don't want to wipe out the online list each time this file fails */
		if ( $key == 'session_onlinelist' )
		{
			if ( !static::$_redisKey )
			{
				/* Last access ensures that the data is not stale if we fail back to MySQL and then go back to Redis later */
				if ( !( static::$_redisKey = RedisClass::i()->get( 'redisKey_session' ) ) OR ! RedisClass::i()->get( 'redisStore_lastAccess' ) )
				{
					static::$_redisKey = md5( mt_rand() );
					RedisClass::i()->setex( 'redisKey_session', 604800, static::$_redisKey );
					RedisClass::i()->setex( 'redisStore_lastAccess', ( 3 * 3600 ), time() );
				}
			}
	
			return static::$_redisKey . '_' . $key;
		}
		else
		{
			return $key;
		}
	}
	
	/**
	 * Resets the session key to force ignore any previous redis data stored
	 *
	 * @return	void
	 */
	protected static function resetKey() : void
	{
		static::$_redisKey = md5( mt_rand() );
		RedisClass::i()->setex( 'redisKey_session', 604800, static::$_redisKey );
	}
	
	/**
	 * Redis key
	 */
	protected int $fetchAttempt = 0;
	
	/**
	 * Fetch all online users (but not spiders)
	 *
	 * @param int $flags				Bitwise flags
	 * @param string $sort				Sort direction
	 * @param array|null $limit				Limit [ offset, limit ]
	 * @param int|null $memberGroup		Limit by a specific member group ID
	 * @param boolean $showAnonymous		Show anonymously logged in peoples?
	 * @return array|int
	 */
	public function getOnlineUsers( int $flags=0, string $sort='desc', array $limit=NULL, int $memberGroup=NULL, bool $showAnonymous=FALSE ): array|int
	{
		try
		{
			$results = RedisClass::i()->lRange( static::_key( 'session_onlinelist' ), 0, -1 );
		}
		catch ( RedisException $e )
		{
			/* Something went wrong, so reset the key to force a new file */
			static::resetKey();
			
			$results = NULL;
		}
			
		if ( ! $results )
		{
			/* Ensure file is deleted */
			try
			{
				RedisClass::i()->del( static::_key( 'session_onlinelist' ) );
			}
			catch ( RedisException $e )
			{
				/* Something went wrong, so reset the key to force a new file */
				static::resetKey();
			}

			$options = array(
				'sort'  => $sort === 'asc' ? 'asc' : 'desc',
				'store' => RedisClass::i()->prefix . static::_key( 'session_onlinelist' ),
				'alpha' => true,
				'by'    => 'nosort ' . $sort === 'asc' ? 'asc' : 'desc',
				'ttl'   => 30, /* This ensures the stored file session_online only lasts for 30 seconds */
				'get'   => array(
					static::_key( RedisClass::i()->prefix ) . '*->member_id',
					static::_key( RedisClass::i()->prefix ) . '*->member_name',
					static::_key( RedisClass::i()->prefix ) . '*->seo_name',
					static::_key( RedisClass::i()->prefix ) . '*->member_group',
					static::_key( RedisClass::i()->prefix ) . '*->login_type',
					static::_key( RedisClass::i()->prefix ) . '*->data'
				)
			);
			
			RedisClass::i()->sort( static::_key( 'session_map' ), $options );
			
			try
			{
				/* Force expiration in 30 seconds to prevent stale caches hanging around */
				RedisClass::i()->expire( static::_key( 'session_onlinelist' ), 30 );
			
				$results = RedisClass::i()->lRange( static::_key( 'session_onlinelist' ), 0, -1 );
			}
			catch ( RedisException $e )
			{
				$this->fetchAttempt++;
				
				/* Something went wrong, so reset the key to force a new file */
				static::resetKey();
				
				if ( $this->fetchAttempt < 2 )
				{
					/* And try again, but only once more to prevent an infinite loop */
					return $this->getOnlineUsers($flags, $sort, $limit, $memberGroup, $showAnonymous);
				}
				else
				{
					$results = array();
				}
			}
		}
		
		/* Reset the fetch attempt */
		$this->fetchAttempt = 0;

		/* Sort returns a flat array, so [ 1, matt, matt, 4, 0, 2, Joe, joe, 3, 0 .. ] so we need to build that into an associative array we can work with */
		$return = array();
		$i = 0;
		
		while( $i < count( $results ) )
		{
			$fields = array();
			$data = NULL;
			foreach( array( 'member_id', 'member_name', 'seo_name', 'member_group', 'login_type', 'data' ) as $field )
			{
				if ( $field === 'data' )
				{
					try
					{
						$data = RedisClass::i()->decode( $results[ $i++ ] );
					}
					catch( RedisException $e )
					{
						$data = NULL;
					}
				}
				/* login_type must be cast as an integer or else anonymous state can be lost when adjustSessions() runs */
				elseif( $field === 'login_type' )
				{
					$fields[ $field ] = (int) $results[ $i++ ];
				}
				else
				{
					$fields[ $field ] = $results[ $i++ ];
				}
			}
			
			if ( is_array( $data ) AND count( $data ) )
			{
				/* Have we already fetched this member? */
				if ( $fields['member_id'] and isset( $return[ $fields['member_id'] ] ) )
				{
					continue;
				}
				
				/* Cut off is 30 mins */
				if ( $data['running_time'] < ( time() - ( 30 * 60 ) ) )
				{
					continue;
				}

				/* Ignore spiders */
				if ( ! $fields['member_id'] and ! ( $data['login_type'] == Front::LOGIN_TYPE_GUEST or $data['login_type'] == Front::LOGIN_TYPE_INCOMPLETE ) )
				{
					continue;
				}

				$return[ $fields['member_id'] ?: $data['ip_address'] ] = array_merge( $data, $fields );
			}
		}

		if ( $flags )
		{
			$members = array();
			foreach( $return as $id => $data )
			{
				/* No members */
				if ( ! ( $flags & static::ONLINE_MEMBERS ) )
				{
					if ( $data['member_id'] )
					{
						continue;
					}
				}
				
				if ( ! ( $flags & static::ONLINE_GUESTS ) )
				{
					if ( ! $data['member_id'] )
					{
						continue;
					}
				}
				
				if ( $memberGroup and $data['member_group'] != $memberGroup )
				{
					continue;
				}
				
				if ( ! $showAnonymous and $data['login_type'] == Front::LOGIN_TYPE_ANONYMOUS and ( !Member::loggedIn()->member_id OR $data['member_id'] != Member::loggedIn()->member_id ) )
				{
					continue;
				}
				
				$members[ $id ] = $data;
			}
			
			/* Count only? */
			if ( $flags & static::ONLINE_COUNT_ONLY )
			{
				return count( $members );
			}
			
			/* Hooray for PHP 7 */
			usort($members, function ($m1, $m2 )
			{
				return $m2['running_time'] <=> $m1['running_time'];
			} );
			
			if ( $limit )
			{
				return array_slice( $members, $limit[0], $limit[1], TRUE );
			}
			
			return $members;
		}
		
		return $return;
	}
	
	/**
	 * Fetch all members active at a specific location
	 *
	 * @param string $app		Application directory (core, forums, etc)
	 * @param string $module		Module
	 * @param string $controller Controller
	 * @param int $id			Current item ID (empty if none)
	 * @param string $url		Current viewing URL
	 * @return array
	 */
	public function getOnlineMembersByLocation( string $app, string $module, string $controller, ?int $id, string $url ): array
	{
		$members = array();
		
		foreach($this->getOnlineUsers(static::ONLINE_MEMBERS, 'desc') as $member )
		{
			if ( $member['current_appcomponent'] == $app and $member['current_module'] == $module and $member['current_controller'] == $controller and $member['current_id'] == $id )
			{
				if ( $url and mb_stristr( $member['location_url'], $url ) )
				{
					$members[ $member['member_id'] ] = array(
						'member_id'		=> $member['member_id'],
						'member_name'	=> $member['member_name'],
						'seo_name'		=> $member['seo_name'],
						'member_group'	=> $member['member_group'],
						'login_type'	=> $member['login_type'],
						'in_editor'		=> $member['in_editor']
					);
				}
			}
		}
			
		return $members;
	}
}