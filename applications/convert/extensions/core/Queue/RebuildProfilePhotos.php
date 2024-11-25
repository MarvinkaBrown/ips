<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Converter
 * @since		16 Mar 2022
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\convert\App;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\File;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException;
use UnderflowException;
use function defined;
use const IPS\PHOTO_THUMBNAIL_SIZE;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildProfilePhotos extends QueueAbstract
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
			$data['count'] = Db::i()->select( 'count(member_id)', 'core_members', [ 'pp_photo_type=?', 'custom' ] )->first();
		}
		catch( UnderflowException $e )
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

		foreach( Db::i()->select( '*', 'core_members', [ "member_id>? AND pp_photo_type=?", $offset, 'custom' ], 'member_id ASC', [ 0, REBUILD_SLOW ] ) AS $member )
		{
			$data['completed']++;

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$app->checkLink( $member['member_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$last = $member['member_id'];
				continue;
			}

			try
			{
				$photo = File::get( 'core_Profile', $member['pp_main_photo'] );
				$thumbnail = $photo->thumbnail( 'core_Profile', PHOTO_THUMBNAIL_SIZE, PHOTO_THUMBNAIL_SIZE, TRUE );
				Db::i()->update( 'core_members', [ 'pp_thumb_photo' => (string) $thumbnail ], [ 'member_id=?', $member['member_id'] ] );
			}
			catch( Exception $e ) { }

			$last = $member['member_id'];
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
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'queue_rebuilding_profile_photos' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}
}