<?php
/**
 * @brief		Background Task: Rebuild non-content item lazy loading
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 July 2018
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_NORMAL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild non-content item lazy loading
 */
class RebuildLazyLoadNonContent extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_NORMAL;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count']	= 0;
		$_extensionData = explode( '_', $data['extension'] );

		foreach( Application::load( $_extensionData[0] )->extensions( 'core', 'EditorLocations' ) as $_key => $extension )
		{
			if( $_key != $_extensionData[1] )
			{
				continue;
			}

			if( method_exists( $extension, 'contentCount' ) )
			{
				$count = $extension->contentCount();

				/* If there is nothing to rebuild, return NULL so the task won't be stored in the first place */
				if( !$count )
				{
					return NULL;
				}

				$data['count']	= (int) $count;
			}

			break;
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
		foreach( Application::allExtensions( 'core', 'EditorLocations', FALSE, NULL, NULL, TRUE ) as $_key => $extension )
		{
			if( $_key != $data['extension'] )
			{
				continue;
			}

			if( method_exists( $extension, 'rebuildLazyLoad' ) )
			{
				$did	= $extension->rebuildLazyLoad( $offset, $this->rebuild );
			}
			else
			{
				$did	= 0;
			}
		}

		if( $did != $this->rebuild )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		return ( $offset + $this->rebuild );
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
        return array( 'text' => Member::loggedIn()->language()->addToStack( 'rebuilding_lazyload_stuff', FALSE, array( 'sprintf' => Member::loggedIn()->language()->addToStack( 'editor__' . $data['extension'] ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
    }
}