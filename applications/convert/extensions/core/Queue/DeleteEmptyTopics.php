<?php

/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		26 July 2016
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\convert\App;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\forums\Topic\ArchivedPost;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task\Queue\OutOfRangeException;
use UnderflowException;
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
class DeleteEmptyTopics extends QueueAbstract
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
			$data['count'] = Db::i()->select( 'count(tid)', 'forums_topics', array( 'forums_posts.pid IS NULL' ) )->join( 'forums_posts', 'forums_posts.topic_id=forums_topics.tid' )->first();
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
	 * @param	mixed			$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int				$offset	Offset
	 * @return	int		New offset or NULL if complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		if ( !class_exists( 'IPS\forums\Topic' ) OR !Application::appisEnabled( 'forums' ) )
		{
			throw new OutOfRangeException;
		}

		/* If app was removed, then cancel this */
		try
		{
			$app = App::load( $data['app'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new OutOfRangeException;
		}

		$last = NULL;

		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'forums_topics', array( "tid>? AND forums_posts.pid IS NULL", $offset ), "tid ASC", array( 0, REBUILD_SLOW ) )->join( 'forums_posts', 'forums_posts.topic_id=forums_topics.tid' ), 'IPS\forums\Topic' ) AS $topic )
		{
			$tid = $topic->tid;

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$app->checkLink( $tid, 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				$last = $tid;
				$data['completed']++;
				continue;
			}

			/* If the topic isn't archived, we can delete it */
			if ( !$topic->isArchived() )
			{
				$topic->delete();
			}
			else
			{
				/* Archived topics will erroneously be picked up by our query, but that's ok...we'll just check them here and delete if empty */
				try
				{
					/* Do we have any posts? This is more efficient than running a COUNT(*) query, funny enough */
					ArchivedPost::db()->select( 'archive_id', 'forums_archive_posts', array( "archive_topic_id=?", $topic->tid ), NULL, 1 )->first();
				}
				/* This topic is empty */
				catch( UnderflowException $e )
				{
					$topic->delete();
				}
			}

			$last = $tid;
			$data['completed']++;
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
        return array( 'text' => Member::loggedIn()->language()->addToStack( 'queue_deleting_empty_topics' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
    }
}