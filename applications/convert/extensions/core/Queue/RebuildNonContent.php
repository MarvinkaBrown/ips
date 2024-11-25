<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		07 Oct 2015
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\convert\App;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException;
use function defined;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildNonContent extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
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
				$data['count']	= (int) $extension->contentCount();
			}
			
			break;
		}

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int					New offset or NULL if complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		$did = 0;
		foreach( Application::allExtensions( 'core', 'EditorLocations', FALSE ) as $_key => $extension )
		{
			if( $_key != $data['extension'] )
			{
				continue;
			}
			
			try
			{
				$app = App::load( $data['app'] );
			}
			catch( \OutOfRangeException $e )
			{
				throw new OutOfRangeException;
			}

			if( method_exists( $extension, 'rebuildContent' ) )
			{
				$did	= $extension->rebuildContent( $offset, $this->rebuild );
			}
			else
			{
				$did	= 0;
			}
		}

		if( $did == $this->rebuild )
		{
			return $offset + $this->rebuild;
		}

		/* Rebuild is complete */
		throw new OutOfRangeException;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array	Text explaning task and percentage complete
	 */
	public function getProgress( mixed $data, int $offset ): array
    {
        return array( 'text' => Member::loggedIn()->language()->addToStack( 'rebuilding_noncontent_posts', FALSE, array( 'sprintf' => Member::loggedIn()->language()->addToStack( 'editor__' . $data['extension'] ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
    }	
}