<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		09 Apr 2024
 */

namespace IPS\core\extensions\core\Queue;

use IPS\Content\Tag;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Http\Url\Friendly;
use IPS\Member;
use IPS\Task;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
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
class ConvertOpenTags extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
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
		$tags = array_slice( $data['tags'], $offset, REBUILD_SLOW );
		foreach( $tags as $text )
		{
			/* Fail-safe */
			if( empty( $text ) )
			{
				continue;
			}

			try
			{
				$tag = Tag::load( $text, 'tag_text' );
			}
			catch( OutOfRangeException )
			{
				$tag = new Tag;
				$tag->text = $text;
				$tag->text_seo = Friendly::seoTitle( $text );
			}

			$tag->enabled = true;
			$tag->save();
			$tag->recount();
		}

		$offset += REBUILD_SLOW;
		if( $offset >= count( $data['tags'] ) )
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
		return [
			'text' => Member::loggedIn()->language()->addToStack( 'queue_converting_open_tags' ),
			'complete' => ( $offset ? round( 100 / count( $data['tags'] ) * $offset, 2 ) : 0 )
		];
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
		/* Kick off a task to delete any remaining tags */
		$queueData = json_decode( $data['data'], true );

		$tagsToDelete = iterator_to_array(
			Db::i()->select( 'distinct tag_text', 'core_tags', Db::i()->in( 'tag_text', $queueData['tags'], true ) )
		);

		foreach( $tagsToDelete as $tag )
		{
			Task::queue( 'core', 'UpdateTaggedItems', [ 'tag' => $tag, 'delete' => true ] );
		}
	}
}