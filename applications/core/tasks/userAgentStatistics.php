<?php
/**
 * @brief		User agent statistics task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Jan 2018
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Http\Useragent;
use IPS\Session\Store;
use IPS\Task;
use IPS\Task\Exception;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * User agent statistics task
 */
class userAgentStatistics extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	Exception
	 */
	public function execute() : mixed
	{
		$desktops	= 0;
		$tablets	= 0;
		$mobiles	= 0;
		$consoles	= 0;

		foreach( Store::i()->getOnlineUsers( Store::ONLINE_MEMBERS, 'desc', NULL, NULL, TRUE, TRUE ) as $row )
		{
			$userAgent = Useragent::parse( $row['browser'] );

			if( in_array( $userAgent->platform, array( 'iPhone', 'Windows Phone OS', 'BlackBerry', 'Android', 'Tizen' ) ) )
			{
				$mobiles++;
			}
			elseif( in_array( $userAgent->platform, array( 'iPad / iPod Touch', 'Kindle', 'Kindle Fire', 'Playbook' ) ) )
			{
				$tablets++;
			}
			elseif( in_array( $userAgent->platform, array( 'Nintendo 3DS', 'New Nintendo 3DS', 'Nintendo Wii', 'Nintendo WiiU', 'PlayStation 3', 'PlayStation 4', 'PlayStation Vita', 'Xbox 360', 'Xbox One' ) ) )
			{
				$consoles++;
			}
			else
			{
				$desktops++;
			}
		}

		Db::i()->insert( 'core_statistics', array(
			'type' 		=> 'devices', 
			'value_1'	=> $mobiles,
			'value_2'	=> $tablets,
			'value_3'	=> $consoles,
			'value_4'	=> $desktops,
			'time'		=> time()
		) );

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