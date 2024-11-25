<?php
/**
 * @brief		Task Exception
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Aug 2013
 */

namespace IPS\Task;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Task;
use RuntimeException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Task Exception
 */
class Exception extends RuntimeException
{
	/**
	 * Constructor
	 *
	 * @param	Task		$task		The task with the issue
	 * @param	string|array	$message	Error Message
	 * @return	void
	 */
	public function __construct( Task $task, $message )
	{
		$task->running = FALSE;
		$task->next_run = DateTime::create()->add( new DateInterval( $task->frequency ) )->getTimestamp();
		$task->save();

		/* Exception message must be a string */
		if( is_array( $message ) )
		{
			$message = json_encode( $message );
		}
		
		parent::__construct( $message );
	}
}