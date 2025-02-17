<?php
/**
 * @brief		Background Task: Move Files from one storage method to another
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		28 May 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function is_numeric;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Move Files from one storage method to another
 */
class MoveFiles extends QueueAbstract
{
	
	/**
	 * @brief Number of files to move per cycle
	 */
	public int $batch = REBUILD_SLOW;
	
	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		$exploded	= explode( '_', $data['storageExtension'] );
		$classname	= "IPS\\{$exploded[2]}\\extensions\\core\\FileStorage\\{$exploded[3]}";
		$offset		= $offset ?: 0;
       
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[2] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$extension = new $classname;

		for ( $i = 0; $i < $this->batch; $i++ )
		{
			try
			{
				/* If we have nothing to move, don't bother with this */
				if( $data['count'] === 0 )
				{
					throw new UnderflowException;
				}

				$return = $extension->move( $offset, (int) $data['newConfiguration'], (int) $data['oldConfiguration'] );
				
				$offset++;
				
				/* Did we return a new offset? */
				if ( is_numeric( $return ) )
				{
					$offset = $return;
				}
			} 
			catch ( UnderflowException $e )
			{
				/* Move is done so remove second config ID to remove lock */
				$row = Db::i()->select( '*', 'core_sys_conf_settings', array( 'conf_key=?', 'upload_settings' ) )->first();
				$settings = json_decode( $row['conf_value'], TRUE );

				if ( isset( $settings[ $data['storageExtension'] ] ) )
				{
					$settings[ $data['storageExtension'] ] = $data['newConfiguration'];

					Settings::i()->changeValues( array( 'upload_settings' => json_encode( $settings ) ) );
				}
				
				/* Run the settingsUpdated method to clear out anything that needs rebuilding or whatever and so on */
				if ( method_exists( $extension, 'settingsUpdated' ) )
				{
					$extension::settingsUpdated( $data['newConfiguration'] );
				}
				
				throw new \IPS\Task\Queue\OutOfRangeException;
			}
			catch ( Exception $e )
			{
				Log::log( $e, 'move_files_bgtask' );
				$offset++;
				continue;
			}
		}
		
		return $offset;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' => Member::loggedIn()->language()->addToStack('moving_files', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $data['storageExtension'] ) ) ) ), 'complete' => $data['count'] ? round( ( 100 / $data['count'] * $offset ), 2 ) : 100 );
	}

	/**
	 * Parse data before queuing
	 *
	 * @param array $data
	 * @return    array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		return $data;
	}
}