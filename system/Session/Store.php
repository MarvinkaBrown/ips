<?php
/**
 * @brief		Storage Class for sessions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 September 2017
 */

namespace IPS\Session;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Session\Store\Database;
use IPS\Session\Store\Redis;
use RedisException;
use RuntimeException;
use function defined;
use const IPS\CACHE_CONFIG;
use const IPS\CACHE_METHOD;
use const IPS\REDIS_CONFIG;
use const IPS\REDIS_ENABLED;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Session Handler
 */
abstract class Store
{
	/**
	 * @brief	Just return a count
	 */
	const ONLINE_COUNT_ONLY = 1;
	
	/**
	 * @brief	Return members
	 */
	const ONLINE_MEMBERS = 2;
	
	/**
	 * @brief	Return guests
	 */
	const ONLINE_GUESTS = 4;
	
	/**
	 * @brief	Instance
	 */
	protected static mixed $instance = NULL;
	
	/**
	 * Returns the engine object
	 *
	 * @return	mixed
	 */
	public static function i(): mixed
	{ 
		if ( static::$instance === NULL )
		{
			if ( \IPS\Redis::isEnabled() )
			{ 
				try
				{
					/* Try and use Redis */
					$readConnection = \IPS\Redis::i()->connection('read');
					$writeConnection = \IPS\Redis::i()->connection('read');

					if( !$writeConnection OR !$readConnection )
					{
						throw new RedisException;
					}
					
					/* No exceptions means it worked */
					static::$instance = new Redis;
				}
				catch( Exception $e )
				{
					/* Something went wrong, so fall back */
					static::$instance = new Database;
				}
			}
			else
			{
				static::$instance = new Database;
			}	
		}

		return static::$instance;
	}
	
	/**
	 * Load the session from the storage engine 
	 *
	 * @param string $sessionId	Session ID
	 * @return	array|null
	 */
	abstract public function loadSession( string $sessionId ): ?array;
	
	/**
	 * Update the session storage engine
	 *
	 * @param array $data	Session data to store
	 * @return void
	 */
	abstract public function updateSession( array $data ) : void;

	/**
	 * Delete from the session engine
	 *
	 * @param string $sessionId	Session ID
	 * @return	void
	 */
	abstract public function deleteSession( string $sessionId ) : void;
	
	/**
	 * Delete from the session engine
	 *
	 * @param	int			$memberId	You can probably figure this out right?
	 * @param	string|NULL	$userAgent	User Agent [optional]
	 * @param	array|NULL	$keepSessionIds	Array of session ids to keep [optional]
	 * @return	void
	 */
	abstract public function deleteByMember( int $memberId, string $userAgent=NULL, array $keepSessionIds=NULL ) : void;
	
	/**
	 * Delete from the session engine
	 *
	 * @param int $memberId	You can probably figure this out right?
	 * @return	array|FALSE
	 */
	abstract public function getLatestMemberSession( int $memberId ): array|FALSE;
	
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
	abstract public function getOnlineUsers( int $flags=0, string $sort='desc', array $limit=NULL, int $memberGroup=NULL, bool $showAnonymous=FALSE ): array|int;
	
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
	abstract public function getOnlineMembersByLocation( string $app, string $module, string $controller, ?int $id, string $url ): array;
	
	/**
	 * Clear sessions - abstracted so it can be called externally without initiating a session
	 *
	 * @param int $timeout	Sessions older than the number of seconds provided will be deleted
	 * @return void
	 */
	public static function clearSessions( int $timeout )
	{
		/* Session engines can overload this */
	}
}