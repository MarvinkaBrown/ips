<?php
/**
 * @brief		Task Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		5 Aug 2013
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use Exception;
use InvalidArgumentException;
use IPS\core\AdminNotification;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use RuntimeException;
use function count;
use function defined;
use function function_exists;
use function is_array;
use function is_null;
use function strlen;
use function substr;
use function time;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Task Model
 */
class Task extends ActiveRecord
{
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param array $data							Row from database table
	 * @param bool $updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return    ActiveRecord|static
	 */
	public static function constructFromData( array $data, bool $updateMultitonStoreIfExists = TRUE ): ActiveRecord|static
	{
		/* Initiate an object */
		$classname = null;
		if ( $data['app'] )
		{
			$classname =  'IPS\\' . $data['app'] . '\tasks\\' . $data['key'];
		}

		if ( $classname === null OR ! class_exists( $classname ) )
		{
			throw new RuntimeException;
		}

		$obj = new $classname;
		$obj->_new = FALSE;

		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix )
			{
				$k = substr( $k, strlen( static::$databasePrefix ) );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();

		/* Return */
		return $obj;
	}

	/**
	 * Run a background queue task
	 *
	 * @return	mixed
	 */	
	public static function runQueue(): mixed
	{
		/* Get all enabled applications */
		$enabledApps = array();
		foreach( Application::enabledApplications() as $app )
		{
			$enabledApps[] = $app->directory;
		}

		$queueData	= Db::i()->select( '*', 'core_queue', array( Db::i()->in('app', $enabledApps ) ), 'priority ASC, RAND()', 1, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER )->first();
		$newOffset	= 0;

		$queueData['_originalOffset']	= $queueData['offset'];

		try
		{
			$extensions = Application::load( $queueData['app'] )->extensions( 'core', 'Queue', FALSE );
			if ( !isset( $extensions[ $queueData['key'] ] ) )
			{
				throw new Task\Queue\OutOfRangeException;
			}
			
			$class = new $extensions[ $queueData['key'] ];
			$json  = json_decode( $queueData['data'], TRUE );
			$newOffset = $class->run( $json, $queueData['offset'] );

			$queueData['offset']			= $newOffset;

			/* This is here for legacy purposes - background queue tasks should throw \IPS\Task\Queue\OutOfRangeException to indicate they are completed, but
				for now we'll still support this and log it for investigation */
			if ( is_null( $newOffset ) )
			{
				Log::log( $queueData['key'] . " returned a NULL offset - tasks should throw \\IPS\\Task\\Queue\\OutOfRangeException when they are finished", 'runQueue_log' );
				Db::i()->delete( 'core_queue', array( 'id=?', $queueData['id'] ) );

				/* Do we have a post-completion callback? */
				if( method_exists( $class, 'postComplete' ) )
				{
					$class->postComplete( $queueData, TRUE );
				}
			}
			/* Task completed successfully and a new offset was returned - store the value and then return it */
			else
			{
				Log::debug( $queueData['key'] . " returned a new offset of " . $newOffset, 'runQueue_log' );

				Db::i()->update( 'core_queue', array( 'offset' => $newOffset ), array( 'id=?', $queueData['id'] ) );
				
				$newData = json_encode( $json );
				
				/* Did it change?? */
				if ( $newData !== $queueData['data'] )
				{
					$queueData['data'] = $newData;
					Db::i()->update( 'core_queue', array( 'data' => $newData ), array( 'id=?', $queueData['id'] ) );
				}
			}
		}
		/* This means the task is done */
		catch(Task\Queue\OutOfRangeException $e )
		{
			Log::debug( $queueData['key'] . " completed successfully", 'runQueue_log' );

			Db::i()->delete( 'core_queue', array( 'id=?', $queueData['id'] ) );

			/* Do we have a post-completion callback? */
			if( isset( $class ) AND method_exists( $class, 'postComplete' ) )
			{
				$class->postComplete( $queueData, TRUE );
			}
		}
		/* Catch any OORE in the task and transform the exception so that we can display an error */
		catch( OutOfRangeException $e )
		{
			Log::log( $e, 'queue_oore');

			throw new RuntimeException( $e->getMessage() );
		}

		return $queueData;
	}

	/**
	 * Queue a background task
	 *
	 * @param string $app						The application that will be responsible for processing
	 * @param string $key						The key of the extension that will be responsible for processing
	 * @param mixed|null $data						Data necessary for processing
	 * @param int $priority					Run order. Values 1 to 5 are allowed, 1 being highest priority.
	 * @param mixed $checkForDuplicationKeys	Pass keys to check to prevent duplicate queue tasks being added
	 * @return	void
	 * @throws	InvalidArgumentException	If $app or $key is invalid
	 */	
	public static function queue( string $app, string $key, mixed $data = NULL, int $priority=5, mixed $checkForDuplicationKeys=NULL ) : void
	{
		try
		{
			$extensions = Application::load( $app )->extensions( 'core', 'Queue', FALSE );
		}
		catch ( OutOfRangeException $e )
		{
			throw new InvalidArgumentException;
		}
		if ( !isset( $extensions[ $key ] ) )
		{
			throw new InvalidArgumentException;
		}

		$class = new $extensions[ $key ];
		try
		{
			$data = $class->preQueueData( $data );
		}
		catch( OutOfRangeException $e )
		{
			$data = NULL;
		}

		if ( $data === NULL )
		{
			$class->postComplete( [], FALSE );
			return;
		}
		
		if ( is_array( $checkForDuplicationKeys ) and is_array( $data ) )
		{
			$insert = FALSE;
			foreach(Db::i()->select( '*', 'core_queue', array( '`app`=? AND `key`=?', $app, $key ) ) as $row )
			{
				if ( $row['data'] )
				{
					$oldData = json_decode( $row['data'], TRUE );
					$got = 0;
					
					foreach( $checkForDuplicationKeys as $k )
					{
						if ( isset( $oldData[ $k ] ) and isset( $data[ $k ] ) and $oldData[ $k ] == $data[ $k ] )
						{
							$got++;
						}
					}
					
					if ( $got === count( $checkForDuplicationKeys ) )
					{
						/* Ok, so we have a duplicate queue item, lets remove it so the new one which is set with the correct count is used and offset is returned to 0 to start over */
						Db::i()->delete( 'core_queue', array( 'id=?', $row['id'] ) );
					}
				}
			}
		}
	
		Db::i()->insert( 'core_queue', array(
			'data'		=> json_encode( $data ),
			'date'		=> time(),
			'app'		=> $app,
			'key'		=> $key,
			'priority'	=> $priority
		) );
		
		Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'queue' ) );
	}
	
	/* !Task */
	
	/**
	 * Get next queued task
	 *
	 * @return    Task|NULL
	 */
	public static function queued(): ?Task
	{		
		$fifteenMinutesAgo = ( time() - 900 );
		$enabledApps = [];
		foreach( Application::enabledApplications() as $app )
		{
			$enabledApps[] = $app->directory;
		}

		foreach (Db::i()->select( '*', 'core_tasks', array( [ 'next_run<?', ( time() + 60 ) ], [ 'enabled=1' ], [ Db::i()->in( 'app', $enabledApps ) ] ), 'next_run ASC', NULL, NULL, NULL, Db::SELECT_FROM_WRITE_SERVER ) as $task )
		{
			try
			{
				$task = static::constructFromData( $task );

				if ( !$task->running or $task->next_run < $fifteenMinutesAgo )
				{
					if ( $task->running )
					{
						$task->unlock();
					}
					else
					{
						return $task;
					}
				}
			}
			catch( Exception $e )
			{
				if( IN_DEV )
				{
					Log::log( $e, 'task_exception' );
				}
			}
		}
		return NULL;
	}
	
	/**
	 * Run and log
	 *
	 * @return	void
	 */
	public function runAndLog() : void
	{
		$result = NULL;
		$error = FALSE;
		
		try
		{
			$result = $this->run();
		}
		catch (Task\Exception $e )
		{
			$result = $e->getMessage();
			$error  = 1;
		}
		
		if ( $error !== FALSE or $result !== NULL )
		{
			Db::i()->insert( 'core_tasks_log', array(
				'task'	=> $this->id,
				'error'	=> $error,
				'log'	=> json_encode( $result ),
				'time'	=> time()
			) );
		}
	}
	
	/**
	 * Run
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws    Task\Exception
	 */
	public function run(): mixed
	{
		/* Enforce Lock. If affected rows = 0, the task has been locked by another process */
		if( (int) Db::i()->update( 'core_tasks', array( 'next_run' => time(), 'running' => 1 ), array( '`running`=0 AND `id`=?', $this->id ) ) === 0 )
		{
			return NULL;
		}

		/* Keep the task object aligned with the database changes made above */
		$this->running = TRUE;
		$this->next_run = time();

		$output = $this->execute();

		$this->running = 0;
		$this->lock_count = 0;
		$this->next_run = DateTime::create()->add( new DateInterval( $this->frequency ) )->getTimestamp();
		$this->last_run = DateTime::create()->getTimestamp();
		$this->save();
		
		AdminNotification::remove( 'core', 'ConfigurationError', "taskLock-{$this->id}" );
		
		return $output;
	}
	
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\core\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws    Task\Exception
	 */
	public function execute() : mixed
	{
		return NULL;
	}
	
	/**
	 * Run until timeout
	 *
	 * @param callback $callback	The code to run. Should return TRUE if we're happy to keep going, and FALSE if there's no more work to do
	 * @param int|null $limit		A hard limit on the number of times to call $callback, or NULL for no limit
	 * @return	void
	 */
	public function runUntilTimeout( callable $callback, int $limit=NULL ) : void
	{
		/* Work out the maximum execution time */
		$timeLeft = 90;
		if ( $phpMaxExecutionTime = ini_get('max_execution_time') and $phpMaxExecutionTime <= $timeLeft )
		{
			$timeLeft = $phpMaxExecutionTime - 2;
		}

		/* Factor in wait_timeout if possible */
		try
		{
			$mysqlTimeout = Db::i()->query( "SHOW SESSION VARIABLES LIKE 'wait_timeout'" )->fetch_assoc();
			$mysqlTimeout = $mysqlTimeout['Value'];

			if( $mysqlTimeout <= $timeLeft )
			{
				$timeLeft = $mysqlTimeout - 2;
			}
		}
		catch( Db\Exception $e ){}

		$timeTheLastRunTook = 0;
				
		/* Work out the memory limit */
		$memoryLeft = 0;
		$memoryUnlimited = FALSE;
		if ( function_exists( 'memory_get_usage' ) )
		{
			$memory_limit = ini_get('memory_limit');
			if ( $memory_limit == -1 )
			{
				$memoryUnlimited = TRUE;
			}
			else
			{
				if ( preg_match('/^(\d+)(.)$/', $memory_limit, $matches ) )
				{
					if ( $matches[2] == 'G' )
					{
						$memory_limit = $matches[1] * 1024 * 1024 * 1024;
					}
				    elseif ( $matches[2] == 'M' )
				    {
				        $memory_limit = $matches[1] * 1024 * 1024;
				    }
				    elseif ( $matches[2] == 'K' )
				    {
				        $memory_limit = $matches[1] * 1024;
				    }
				}
				$memoryLeft = $memory_limit - memory_get_usage( TRUE );
			}
		}
		$memoryTheLastRunTook = 0;
		
		/* Run until we run out of time or hit our limit */
		$calls = 0;
		do
		{
			/* Start a timer */
			$timer = microtime( TRUE );
			$memoryTimer = function_exists( 'memory_get_usage' ) ? memory_get_usage( TRUE ) : 0;
			
			/* Execute */
			if ( $callback() === FALSE )
			{
				break;
			}

			$calls++;

			/* If we have a limit and we've hit it, then stop now */
			if( $limit !== NULL AND $calls >= $limit )
			{
				break;
			}
			
			/* Decrease the time left */
			$timeTheLastRunTook = round( ( microtime( TRUE ) - $timer ), 2 );
			$timeLeft -= $timeTheLastRunTook;
			$memoryTheLastRunTook = function_exists( 'memory_get_usage' ) ? ( memory_get_usage( TRUE ) - $memoryTimer ) : 0;
			if ( !$memoryUnlimited )
			{
				$memoryLeft = $memory_limit - memory_get_usage( TRUE );
			}
			
		}
		while ( $timeLeft > $timeTheLastRunTook and ( $memoryUnlimited or $memoryLeft > $memoryTheLastRunTook ) );
	}
	
	/**
	 * Unlock
	 *
	 * @return	void
	 */
	public function unlock() : void
	{
		if ( $this->running )
		{
			$this->running = FALSE;
			if ( $this->lock_count < 3 ) // Allowing this to grow infinitely will eventually overflow. The warning triggers at 3, so we only need to know if it's more than 3 or not.
			{
				$this->lock_count++;
				
				if ( $this->lock_count >= 3 )
				{
					AdminNotification::send( 'core', 'ConfigurationError', "taskLock-{$this->id}" );
				}
			}
			$this->next_run = DateTime::create()->add( new DateInterval( $this->frequency ) )->getTimestamp();
			$this->save();
			$this->cleanup();
		}
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

	/* !ActiveRecord */
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_tasks';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static array $databaseIdFields = array( 'key' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
}