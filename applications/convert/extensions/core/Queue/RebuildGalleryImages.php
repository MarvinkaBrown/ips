<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		16 Oct 2015
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\convert\App;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
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
class RebuildGalleryImages extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		try
		{
			$data['count'] = Db::i()->select( 'count(image_id)', 'gallery_images' )->first();
		}
		catch( Exception $e )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return NULL;
		}

		$data['completed'] = 0;
		
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
	public function run( mixed &$data, int $offset ): int
	{
		if ( !class_exists( 'IPS\gallery\Image' ) OR !Application::appIsEnabled( 'gallery' ) )
		{
			throw new OutOfRangeException;
		}
		
		/* Intentionally no try/catch as it means app doesn't exist */
		try
		{
			$app = App::load( $data['app'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new OutOfRangeException;
		}

		$last = NULL;
		
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'gallery_images', array( "image_id>?", $offset ), 'image_id ASC', array( 0, REBUILD_SLOW ) ), 'IPS\gallery\Image' ) AS $image )
		{
			$data['completed']++;

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$app->checkLink( $image->id, 'gallery_images' );
			}
			catch( \OutOfRangeException $e )
			{
				$last = $image->id;
				continue;
			}

			try
			{
				$image->buildThumbnails();
				$image->save();
			}
			catch( DomainException | InvalidArgumentException $e )
			{
				$image->delete();
			}
			
			$last = $image->id;
		}

		if( $last === NULL )
		{
			throw new OutOfRangeException;
		}

		return $last;
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
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'queue_rebuilding_gallery_images' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}	
}