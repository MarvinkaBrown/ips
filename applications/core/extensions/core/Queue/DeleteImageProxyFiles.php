<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Sep 2017
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\File;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
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
class DeleteImageProxyFiles extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count']			= Db::i()->select( 'count(*)', 'core_image_proxy' )->first();
		$data['deleted']		= 0;
		$data['cachePeriod']	= Settings::i()->image_proxy_cache_period;

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
	public function run( mixed &$data, int $offset ): int
	{
		/* We don't want to delete the files if we are caching indefinitely */
		if( !$data['cachePeriod'] )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		/* Kill the process if there table doesn't exist anymore */
		if( !Db::i()->checkForTable( 'core_image_proxy' ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$select = Db::i()->select( 'location', 'core_image_proxy', array(), 'cache_time ASC', REBUILD_SLOW );

		$completed	= 0;

		foreach ( $select as $location )
		{
			try
			{
				File::get( 'core_Imageproxycache', $location )->delete();
			}
			catch ( Exception $e ) { }

			Db::i()->delete( 'core_image_proxy', array( 'location=?', $location ) );

			$data['deleted']++;
			$completed++;
		}

		if( $completed === 0 )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		return $completed + $offset;
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
		return array( 'text' => Member::loggedIn()->language()->addToStack('deleting_imageproxy_files'), 'complete' => $data['count'] ? ( round( ( 100 / $data['count'] ) * $data['deleted'], 2 ) ) : 100 );
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
		Db::i()->dropTable( 'core_image_proxy' );
	}
}