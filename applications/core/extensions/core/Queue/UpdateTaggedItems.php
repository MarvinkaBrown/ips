<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		29 Mar 2024
 */

namespace IPS\core\extensions\core\Queue;

use IPS\Content;
use IPS\Content\Item;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use OutOfRangeException;
use function count;
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
class UpdateTaggedItems extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['processed'] = 0;
		$data['count'] = (int) Db::i()->select( 'count(*)', 'core_tags', [ 'tag_text=?', $data['tag'] ] )->first();
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
		if( empty( $data['tag'] ) )
		{
			throw new QueueOutOfRangeException;
		}

		$rows = iterator_to_array(
			Db::i()->select( '*', 'core_tags', [ 'tag_text=?', $data['tag'] ], 'tag_added', [ $offset, REBUILD_SLOW ] )
		);

		if( !count( $rows ) )
		{
			throw new QueueOutOfRangeException;
		}

		$itemClasses = Content::routedClasses( false, false, true );

		foreach( $rows as $row )
		{
			foreach( $itemClasses as $itemClass )
			{
				/* @var Item $itemClass */
				if( $itemClass::$application == $row['tag_meta_app'] and $itemClass::$module == $row['tag_meta_area'] )
				{
					try
					{
						$item = $itemClass::load( $row['tag_meta_id'] );
						$currentTags = $item->tags();

						/* If the tag is a prefix, we will just leave the prefix out of the tag set */
						if( !$row['tag_prefix'] )
						{
							$index = array_search( $data['tag'], $currentTags );
							if( $index !== false )
							{
								unset( $currentTags[ $index ] );
							}
						}

						/* Are we renaming the tag? Put the new name in */
						if( isset( $data['new'] ) and $data['new'] )
						{
							if( $row['tag_prefix'] )
							{
								$currentTags['prefix'] = $data['new'];
							}
							else
							{
								$currentTags[] = $data['new'];
							}
						}

						$item->setTags( $currentTags );
					}
					catch( OutOfRangeException ){}
					break;
				}
			}

			/* We can't use the offset because the rows are deleted from the core_tags table as we go */
			$data['processed']++;
		}

		if( $data['processed'] >= $data['count'] )
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
			'text' => Member::loggedIn()->language()->addToStack( ( isset( $data['delete'] ) ? 'queue_deleting_tag' : 'queue_renaming_tag' ), true, [ 'sprintf' => [ $data['tag'] ] ] ),
			'complete' => ( $data['processed'] ? round( 100 / $data['count'] * $data['processed'], 2 ) : 0 )
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

	}
}