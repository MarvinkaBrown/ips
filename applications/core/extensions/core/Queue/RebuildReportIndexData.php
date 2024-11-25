<?php
/**
 * @brief		Background Task: Rebuild Report Index Data
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		16 Sept 2022
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Item;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException as QueueException;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild Item Counts (comments, etc)
 */
class RebuildReportIndexData extends QueueAbstract
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
		try
		{			
			$data['count']		= Db::i()->select( 'MAX(id)', 'core_rc_index' )->first();
			$data['realCount']	= Db::i()->select( 'COUNT(*)', 'core_rc_index' )->first();
		}
		catch( DbException $ex )
		{
			return NULL;
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;
		
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	QueueException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		$last = NULL;

		try
		{
			foreach( Db::i()->select( '*', 'core_rc_index', array( 'id > ?',  $offset ), 'id ASC', array( 0, $this->index ) ) as $report )
			{
				$last = $report['id'];
				$classname = $report['class'];
				$exploded = explode( '\\', $classname );
				if ( ! class_exists( $classname ) or ! Application::appIsEnabled( $exploded[1] ) )
				{
					continue;
				}
			
				try
				{
					$content = $classname::load( $report['content_id'] );
					$itemId = 0;
					$nodeId = 0;
					$item = null;
					
					if ( $content instanceof Item )
					{
						$item = $content;
					}
					else
					{
						$item = $content->item();
					}
					
					$idColumn = $item::$databaseColumnId;
					$itemId = $item->$idColumn;
					if ( $node = $item->containerWrapper() )
					{
						$nodeId = $node->_id;
					}
					
					Db::i()->update( 'core_rc_index', [
						'item_id' => $itemId,
						'node_id' => $nodeId
					],
					[
						'id=?', $report['id']
					] );
				}
				catch( Exception $e )
				{
					continue;
				}
			}
		}
		catch( Exception $e )
		{
			throw new QueueException;
		}

		if( $last === NULL )
		{
			throw new QueueException;
		}
		
		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_report_index_data'), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}

}