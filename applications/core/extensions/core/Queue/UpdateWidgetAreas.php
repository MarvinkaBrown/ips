<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		19 Dec 2023
 */

namespace IPS\core\extensions\core\Queue;

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use IPS\Widget\Area;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_SLOW;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class UpdateWidgetAreas extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = (int) Db::i()->select( 'count(id)', 'core_widget_areas' )->first();
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	array						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	QueueOutOfRangeException	        Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		$limit = REBUILD_SLOW;
		$rows = iterator_to_array(
			Db::i()->select( '*', 'core_widget_areas', null, 'id', array( $offset, $limit ) )
		);

		foreach( $rows as $row )
		{
			if( !empty( $row['tree'] ) )
			{
				continue;
			}

			$areaWidgets = json_decode( $row['widgets'], true );
			if( !count( $areaWidgets ) )
			{
				continue;
			}

			$area = Area::create( $row['area'], $areaWidgets );

			foreach( $area->getAllWidgets() as $widget )
			{
				if( isset( $widget['configuration'] ) AND !empty( $widget['configuration'] ) )
				{
					Db::i()->replace( 'core_widgets_config', [
						'id' => $widget['unique'],
						'data' => json_encode( $widget['configuration'] )
					]);
				}
			}

			Db::i()->update( 'core_widget_areas', [
				'tree' => json_encode( $area->toArray( true, false ) ),
				'widgets' => '[]'
			], [ 'id=?', $row['id']] );
		}

		$offset += $limit;
		if( $offset >= $data['count'] )
		{
			throw new QueueOutOfRangeException;
		}

		return $offset;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	array					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( array $data, int $offset ): array
	{
		return array(
			'text' => 'Updating Widget Areas',
			'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100
		);
	}
}