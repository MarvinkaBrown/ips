<?php
/**
 * @brief		Admin Session Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Mar 2013
 */

namespace IPS\Session;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Http\Useragent;
use IPS\Member;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use function defined;
use const IPS\ACP_SESSION_TIMEOUT;
use const IPS\BYPASS_ACP_IP_CHECK;
use const IPS\DEV_DISABLE_ACP_SESSION_TIMEOUT;
use const IPS\IN_DEV;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin Session Handler
 */
class Admin extends Session
{
	/**
	 * @brief	Unix Timestamp of log in time
	 */
	public int $logInTime;
				
	/**
	 * Open Session
	 *
	 * @param	string	$savePath	Save path
	 * @param	string	$sessionName Session Name
	 * @return	bool
	 */
	public function open( string $savePath, string $sessionName ) : bool
	{
		return TRUE;
	}
	
	/**
	 * Read Session
	 *
	 * @param	string	$sessionId	Session ID
	 * @return	string
	 */
	public function read( string $sessionId ) : string
	{
		/* Get user agent info */
		$this->userAgent	= Useragent::parse();

		try
		{
			$where = array( array( 'session_id=?', $sessionId ) );

			if( !IN_DEV OR !DEV_DISABLE_ACP_SESSION_TIMEOUT )
			{
				$where[] = array( 'session_running_time>=?', ( time() - ACP_SESSION_TIMEOUT ) );
			}

			/* Load session */
			$session = Db::i()->select( '*', 'core_sys_cp_sessions', $where )->first();
			$this->logInTime = $session['session_log_in_time'];
			
			/* Store this so plugins can access */
			$this->sessionData	= $session;

			/* Load member */
			$this->member = $session['session_member_id'] ? Member::load( $session['session_member_id'] ) : new Member;
			if ( $this->member->member_id and !$this->member->isAdmin() )
			{
				throw new DomainException('NO_ACPACCESS');
			}
									
			/* Check IP address */
			if ( ( defined( '\IPS\BYPASS_ACP_IP_CHECK' ) and !BYPASS_ACP_IP_CHECK ) and Settings::i()->match_ipaddress and $session['session_ip_address'] !== Request::i()->ipAddress() )
			{
				throw new DomainException('BAD_IP');
			}
			
			/* Return data */
			return $session['session_app_data'];
		}
		catch ( Exception $e )
		{
			$this->member = new Member;
			$this->logInTime = 0;
			$this->error = $e;
			return $this->sessionData['session_app_data'] ?? '';
		}
	}
	
	/**
	 * Write Session
	 *
	 * @param	string	$sessionId	Session ID
	 * @param	string	$data		Session Data
	 * @return	bool
	 */
	public function write( string $sessionId, string $data ) : bool
	{
		Db::i()->replace( 'core_sys_cp_sessions', array(
			'session_id'				=> $sessionId,
			'session_ip_address'		=> Request::i()->ipAddress(),
			'session_member_name'		=> $this->member->name ?: '-',
			'session_member_id'			=> $this->member->member_id ?: 0,
			'session_location'			=> 'app=' . ( Dispatcher::i()->application ? Dispatcher::i()->application->directory : '' ) . '&module=' . ( Dispatcher::i()->module ? Dispatcher::i()->module->key : '' ) . '&controller=' . Dispatcher::i()->controller,
			'session_log_in_time'		=> $this->logInTime,
			'session_running_time'		=> time(),
			'session_url'				=> Request::i()->url(),
			'session_app_data'			=> $data
		) );
		
		return TRUE;
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
	 * @param	string	$sessionId	Session ID
	 * @return	bool
	 */
	public function destroy( string $sessionId ) : bool
	{
		Db::i()->delete( 'core_sys_cp_sessions', array( 'session_id=?', $sessionId ) );
		return TRUE;
	}
	
	/**
	 * Garbage Collection
	 *
	 * @param	int		$lifetime	Number of seconds to consider sessions expired beyond
	 * @return	bool
	 */
	public function gc( int $lifetime ) : bool
	{
		/* We ignore $lifetime because we explicitly control how long sessions are valid for via a constant */
		$lifetime = ACP_SESSION_TIMEOUT;

		Db::i()->delete( 'core_sys_cp_sessions', array( 'session_running_time<?', ( time() - $lifetime ) ) );
		return TRUE;
	}
}