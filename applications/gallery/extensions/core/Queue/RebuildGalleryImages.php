<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		04 Dec 2017
 */

namespace IPS\gallery\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\File;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_INTENSE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildGalleryImages extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_INTENSE;

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
			$data['count']		= Db::i()->select( 'COUNT(*)', 'gallery_images', array( 'image_media=?', 0 ) )->first();
		}
		catch( Exception )
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
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		if ( !Application::appIsEnabled( 'gallery' ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$iterator = new ActiveRecordIterator( Db::i()->select( '*', 'gallery_images', array( array( 'image_media=? AND image_id>?', 0, $offset ) ), 'image_id ASC', array( 0, $this->rebuild ) ), 'IPS\\gallery\\Image' );
		$last     = NULL;

		foreach( $iterator as $image )
		{
			try
			{
				/* Make sure the original image exists and has content */
				if ( $image->original_file_name )
				{
					$file = File::get( 'gallery_Images', $image->original_file_name );
					$file->contents();

					$image->buildThumbnails( $file );
					$image->save();
				}
			}
			catch ( Exception ) {}

			$last = $image->id;
			$data['indexed']++;
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
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_gallery_images' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['indexed'], 2 ) ) : 100 );
	}
}