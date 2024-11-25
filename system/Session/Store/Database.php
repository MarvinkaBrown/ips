<?php
/**
 * @brief		Database Session Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 September 2017
 */

namespace IPS\Session\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Http\Useragent;
use IPS\Member;
use IPS\Request;
use IPS\Session\Front;
use IPS\Session\Store;
use UnderflowException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Session Handler
 */
class Database extends Store
{
	/**
	 * Load the session from the storage engine 
	 *
	 * @param string $sessionId	Session ID
	 * @return	array|NULL
	 */
	public function loadSession( string $sessionId ): ?array
	{ 
		$session = NULL;
		/* Get from the database */
		try
		{
			/* If it looks like we're logged in, join the member row to save a query later */
			if ( Front::loggedIn() )
			{ 
				$session = Db::i()->select( '*', 'core_sessions', array( 'id=?', $sessionId ), NULL, NULL, NULL, NULL, Db::SELECT_MULTIDIMENSIONAL_JOINS )->join( 'core_members', 'core_members.member_id=core_sessions.member_id' )->first();
				if ( $session['core_members']['member_id'] )
				{
					Member::constructFromData( $session['core_members'], FALSE );
				}
				$session = $session['core_sessions'];
			}
			/* If we're not logged in, just look at the session */
			else
			{
				$userAgent = Useragent::parse();
				
				/* Spiders match by IP and useragent */
				if ( $userAgent->bot )
				{
					$session = Db::i()->select( '*', 'core_sessions', array( 'id=? OR ( ip_address=? AND browser=? )', $sessionId, Request::i()->ipAddress(), $_SERVER['HTTP_USER_AGENT'] ) )->first();
				}
				/* Normal users don't */
				else
				{
					$session = Db::i()->select( '*', 'core_sessions', array( 'id=?', $sessionId ) )->first();
				}
			}
		}
		catch ( UnderflowException $e ) { }
		
		return $session;
	}
		
	/**
	 * Update the session storage engine
	 *
	 * @param array $data		Session Data
	 * @return void
	 */
	public function updateSession( array $data ) : void
	{
		Db::i()->insert( 'core_sessions', $data, TRUE );
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param string $sessionId	Session ID
	 * @return	void
	 */
	public function deleteSession( string $sessionId ) : void
	{
		Db::i()->delete( 'core_sessions', array( 'id=?', $sessionId ) );
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
		$where = array( array( 'member_id=?', $memberId ) );
		
		if ( $userAgent )
		{
			$where[] = array( 'browser=?', $userAgent );
		}
		
		if ( is_array( $keepSessionIds ) AND count( $keepSessionIds ) )
		{
			$where[] = array( Db::i()->in( 'id', $keepSessionIds, TRUE ) );
		}
		
		Db::i()->delete( 'core_sessions', $where );
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param int $memberId	You can probably figure this out right?
	 * @return	array|FALSE
	 */
	public function getLatestMemberSession( int $memberId ): array|FALSE
	{
		try
		{
			return Db::i()->select( '*', 'core_sessions', array( 'member_id=?', $memberId ), 'running_time DESC' )->first();
		}
		catch ( UnderflowException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Fetch all active session keys
	 *
	 * @return	array or session IDs
	 */
	public function getSessionIds(): array
	{
		return iterator_to_array( Db::i()->select( 'id', 'core_sessions' ) );
	}
	
	/**
	 * Clear sessions - abstracted so it can be called externally without initiating a session
	 *
	 * @param int $timeout	Sessions older than the number of seconds provided will be deleted
	 * @return void
	 */
	public static function clearSessions( int $timeout ) : void
	{
		Db::i()->delete( 'core_sessions', array( 'running_time<?', ( time() - $timeout ) ) );
	}
	
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
		/* Query */
		$where = array(
			array( 's.running_time>?', DateTime::create()->sub( new DateInterval( 'PT30M' ) )->getTimeStamp() ),
			array( "s.login_type!=?", Front::LOGIN_TYPE_SPIDER )
		);
		
		if ( ! $showAnonymous )
		{
			if( Member::loggedIn()->member_id )
			{
				$where[] = array( "(s.login_type!=? OR s.member_id=?)", Front::LOGIN_TYPE_ANONYMOUS, Member::loggedIn()->member_id );
			}
			else
			{
				$where[] = array( "s.login_type!=?", Front::LOGIN_TYPE_ANONYMOUS );
			}
		}

		if ( ! $flags and ! $limit )
		{
			/* Simple query for PHP processing */
			return iterator_to_array( Db::i()->select( 's.id,s.member_id,s.member_name,s.seo_name,s.member_group,s.login_type', array( 'core_sessions', 's' ), $where, 's.running_time ' . $sort )->setKeyField('id') );
		}
		else
		{
			/* Complex group by mode with all the lovely trimmings yum */
			$guestSubWhere = $where;
			$guestSubWhere[] = 's.member_id IS NULL';
			
			$memberSubWhere = $where;
			$memberSubWhere[] = 's.member_id IS NOT NULL';

			/* Ok, this looks odd, but the ONLY_FULL_GROUP_BY bites us here, so selecting max(id) allows us to return a session ID even though we're grouping on member_id */
			if ( $flags AND ! ( $flags & static::ONLINE_GUESTS ) )
			{
				$where = array(
					array(
						"core_sessions.id IN(?)",
						Db::i()->select( 'MAX(id)', array( 'core_sessions', 's' ), $memberSubWhere, NULL, NULL, 'member_id' ),
					)
				);
			}
			elseif ( $flags AND ! ( $flags & static::ONLINE_MEMBERS ) )
			{
				$where = array(
					array(
						"core_sessions.id IN(?)",
						Db::i()->select( 'MAX(id)', array( 'core_sessions', 's' ), $guestSubWhere, NULL, NULL, 'ip_address' )
					)
				);
			}
			else
			{
				$where = array(
					array(
						"( core_sessions.id IN(?) OR core_sessions.id IN(?) )",
						Db::i()->select( 'MAX(id)', array( 'core_sessions', 's' ), $memberSubWhere, NULL, NULL, 'member_id' ),
						Db::i()->select( 'MAX(id)', array( 'core_sessions', 's' ), $guestSubWhere, NULL, NULL, 'ip_address' )
					)
				);
			}
			
			/* Limiting to a user group? */
			if ( $memberGroup )
			{
				$where[] = array( 'core_sessions.member_group=?', $memberGroup );
			}
			
			/* Just looking for guests? */
			if ( $flags AND ! ( $flags & static::ONLINE_MEMBERS ) )
			{
				$where[] = array( '( core_sessions.member_id IS NULL )' );
			}
			
			if ( $flags AND ! ( $flags & static::ONLINE_GUESTS ) )
			{
				/* No guests */
				$where[] = array( 'core_sessions.member_id IS NOT NULL' );
			}

			/* Just fetching a count? */
			if ( $flags & static::ONLINE_COUNT_ONLY )
			{
				return Db::i()->select( 'COUNT(*)', 'core_sessions', $where )->first();
			}

			return iterator_to_array( Db::i()->select( '*', 'core_sessions', $where, 'core_sessions.running_time ' . $sort, $limit )->setKeyField('id') );
		}
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
		$where = array(
			array( 'core_sessions.login_type=' . Front::LOGIN_TYPE_MEMBER ),
			array( 'core_sessions.current_appcomponent=?', $app ),
			array( 'core_sessions.current_module=?', $module ),
			array( 'core_sessions.current_controller=?', $controller ),
			array( 'core_sessions.running_time>' . DateTime::create()->sub( new DateInterval( 'PT30M' ) )->getTimeStamp() ),
			array( 'core_sessions.location_url IS NOT NULL AND location_url LIKE ?', "{$url}%" ),
			array( 'core_sessions.member_id IS NOT NULL' )
		);

		if( $id )
		{
			$where[] = array( 'core_sessions.current_id = ?', Request::i()->id );
		}

		foreach( Db::i()->select( 'core_sessions.member_id,core_sessions.member_name,core_sessions.seo_name,core_sessions.member_group,core_sessions.login_type,core_sessions.in_editor', 'core_sessions', $where, 'core_sessions.running_time DESC' ) as $row )
		{
			if( $row['login_type'] == Front::LOGIN_TYPE_MEMBER and $row['member_name'] )
			{
				$members[ $row['member_id'] ] = $row;
			}
		}
		
		return $members;
	}
}