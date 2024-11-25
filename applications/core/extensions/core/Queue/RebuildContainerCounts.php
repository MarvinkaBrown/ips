<?php
/**
 * @brief		Background Task: Rebuild Container Counts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Aug 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function defined;
use function get_class;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild Container Counts
 */
class RebuildContainerCounts extends QueueAbstract
{
	/**
	 * @brief Number of content items to index per cycle
	 */
	public int $index	= REBUILD_QUICK;
	
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];
		
		try
		{
			$data['count'] = Db::i()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable )->first();
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
		return $data;
	}

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
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$last = NULL;
		
		Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'rebuildItemCounts' );
		
		/* A pages database may have been deleted */
		try
		{
			$select   = Db::i()->select( '*', $classname::$databaseTable, array( $classname::$databasePrefix . $classname::$databaseColumnId . ' > ?',  $offset ), $classname::$databasePrefix . $classname::$databaseColumnId . ' ASC', array( 0, $this->index ) );
			$idColumn = $classname::$databaseColumnId;
			$iterator = new ActiveRecordIterator( $select, $classname );
	
			foreach( $iterator as $item )
			{
				if ( !isset( $item::$contentItemClass ) )
				{
					throw new \IPS\Task\Queue\OutOfRangeException;
				}

				$itemClass = $item::$contentItemClass;

				/* Deleting Pages categories or databases while a rebuild task remains can make Pages SPL autoloader fail */
				if ( ! class_exists( get_class( $item ) ) or ! class_exists( $itemClass ) )
				{
					throw new \IPS\Task\Queue\OutOfRangeException;
				}
				
				$item->resetCommentCounts();
				$item->setLastComment();
				$item->setLastReview();
				$item->save();
				
				$last = $item->$idColumn;
			}
		}
		catch( Exception $e )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $last;
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
        $class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}
		
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_item_counts', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$nodeTitle, FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{
		/* Clear guest cache */
		Db::i()->delete( 'core_cache' );
	}
}