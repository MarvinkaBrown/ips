<?php
/**
 * @brief		Unlock Members Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		29 Sep 2016
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Unlock Members Task
 */
class unlockmembers extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return    mixed    Message to log or NULL
	 * @throws    Exception|\Exception
	 */
	public function execute() : mixed
	{
		if(  !Settings::i()->ipb_bruteforce_unlock or !Settings::i()->ipb_bruteforce_period or !Settings::i()->ipb_bruteforce_attempts )
		{
			return NULL;
		}

		/* Clear out old data */
		Db::i()->delete( 'core_login_failures', [ 'login_date<? AND login_ip_address IS NOT NULL AND login_member_id IS NOT NULL', ( new DateTime() )->sub( new DateInterval( 'PT' . Settings::i()->ipb_bruteforce_period . 'M' ) )->getTimestamp() ] );

		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( 'failed_login_count >= ?', Settings::i()->ipb_bruteforce_attempts ) ), 'IPS\Member') AS $member )
		{
			$where = [
				'login_date>=? AND login_ip_address IS NOT NULL AND login_member_id=?',
				( new DateTime )->sub( new DateInterval( 'PT' . Settings::i()->ipb_bruteforce_period . 'M' ) )->getTimestamp(),
				$this->member_id
			];

			$failedLogins = iterator_to_array( Db::i()->select( 'count(login_ip_address)', 'core_login_failures', $where, NULL, NULL, 'login_ip_address' ) );
			$member->failed_login_count = count( $failedLogins ) ? max( $failedLogins ) : 0;
			$member->save();
		}

		return NULL;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}