<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		19 Nov 2016
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\convert\App;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException;
use function defined;
use const IPS\REBUILD_NORMAL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RecountQuestionRatings extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = Db::i()->select( 'count(tid)', 'forums_topics' )->first();

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
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		if ( !class_exists( 'IPS\forums\Topic' ) OR !Application::appisEnabled( 'forums' ) )
		{
			throw new OutOfRangeException;
		}

		$last = NULL;

		/* If the app doesn't exist, stop now */
		try
		{
			$app = App::load( $data['app'] );
		}
		catch( \OutOfRangeException $e )
		{
			throw new OutOfRangeException;
		}

		/* Loop over distinct topic IDs in forums_question_ratings - while using the DISTINCT flag can be slower, there generally aren't many of these */
		foreach( Db::i()->select( 'topic', 'forums_question_ratings', array( "topic>?", $offset ), "topic ASC", array( 0, REBUILD_NORMAL ), NULL, NULL, Db::SELECT_DISTINCT ) AS $topic )
		{
			$last = $topic;
			$data['completed']++;

			/* Is this converted content? */
			try
			{
				/* Just checking, we don't actually need anything */
				$app->checkLink( $topic, 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				continue;
			}

			/* Rebuild count */
			Db::i()->update( 'forums_topics', array(
				'question_rating'	=> (int) Db::i()->select( 'SUM(rating)', 'forums_question_ratings', array( 'topic=?', $topic ) )->first()
			), array( 'tid=?', $topic ) );
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
	 * @return	array	Text explaining task and percentage complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' =>  Member::loggedIn()->language()->addToStack( 'queue_recounting_question_ratings' ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}
}