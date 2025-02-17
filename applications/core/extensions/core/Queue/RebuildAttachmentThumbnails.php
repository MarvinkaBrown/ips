<?php
/**
 * @brief		Background Task: Rebuild Attachment Thumbnails
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Nov 2015
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\File;
use IPS\Log;
use IPS\Member;
use IPS\Settings;
use OutOfRangeException;
use RuntimeException;
use function defined;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuilds thumbnails for attached images
 */
class RebuildAttachmentThumbnails extends QueueAbstract
{
	/**
	 * @brief Number of thumbnails to build per cycle
	 */
	public int $perCycle	= REBUILD_SLOW;
	
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		if( Settings::i()->attachment_image_size == '0x0' )
		{
			return null;
		}

		try
		{
			$data['count']		= Db::i()->select( 'MAX(attach_id)', 'core_attachments', array( 'attach_is_image=?', 1 ) )->first();
			$data['realCount']	= Db::i()->select( 'COUNT(*)', 'core_attachments', array( 'attach_is_image=?', 1 ) )->first();
		}
		catch( Exception $ex )
		{
			throw new OutOfRangeException;
		}
		
		if( $data['count'] == 0 or $data['realCount'] == 0 )
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
		if( Settings::i()->attachment_image_size == '0x0' )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$last	= NULL;
		
		Log::debug( "Rebuilding attachment thumbnails with an offset of " . $offset, 'rebuildAttachmentThumbnails' );

		$thumbDims	= Settings::i()->attachment_image_size ? explode( 'x', Settings::i()->attachment_image_size ) : array( 1000, 750 );

		foreach( Db::i()->select( '*', 'core_attachments', array( 'attach_id > ? AND attach_is_image=?', $offset, 1 ), 'attach_id ASC', array( 0, $this->perCycle ) ) as $attachment )
		{
			/* Did the rebuild previously time out on this? If so we need to skip it and move along */
			if( isset( Store::i()->currentAttachmentRebuild ) )
			{
				/* If the last rebuild cycle timed out, currentAttachmentRebuild might be set and we might have already rebuilt this attachment (the attachment that caused the rebuild to fail might come after this).
					If that is the case, we should skip rebuilding this attachment again. */
				if( Store::i()->currentAttachmentRebuild > $attachment['attach_id'] )
				{
					$last = $attachment['attach_id'];
					continue;
				}

				/* If the last rebuild cycle failed and we have just retrieved the attachment we last attempted to rebuild, skip it and move along */
				if( Store::i()->currentAttachmentRebuild == $attachment['attach_id'] )
				{
					unset( Store::i()->currentAttachmentRebuild );
					$last = $attachment['attach_id'];
					continue;
				}
			}

			/* Set the last attachment ID we rebuilt now */
			$last	= $attachment['attach_id'];

			/* Set a flag so we know which attachment we are attempting to rebuild - if it fails, we can skip it next time */
			Store::i()->currentAttachmentRebuild = $last;

			/* Increment the counter for the progress bar */
			$data['indexed']++;

			try
			{
				$file = File::get( 'core_Attachment', $attachment['attach_location'] );

				/* If there is no existing thumb location AND the existing dimensions are smaller than those needed for a thumbnail,
					we don't need to build a thumbnail */
				if( !$attachment['attach_thumb_location'] )
				{
					$dimensions	= $file->getImageDimensions();

					if ( $dimensions[0] < $thumbDims[0] and $dimensions[1] < $thumbDims[1] )
					{
						continue;
					}
				}

				if( $attachment['attach_thumb_location'] )
				{
					$thumb = File::get( 'core_Attachment', $attachment['attach_thumb_location'] );
					$file->thumbnailName		= $thumb->filename;
					$file->thumbnailContainer	= $thumb->container;
				}

				/* Generate Thumbnail */
				$image = $file->thumbnail( 'core_Attachment', $thumbDims[0], $thumbDims[1] );

				/* Get new thumbnail dimensions */
				$dimensions = $image->getImageDimensions();

				Db::i()->update( 'core_attachments', array(
					'attach_thumb_location'	=> (string) $image,
					'attach_thumb_width'	=> $dimensions[0],
					'attach_thumb_height'	=> $dimensions[1]
				), array( "attach_id=?", $attachment['attach_id'] ) );
			}
			catch( RuntimeException | InvalidArgumentException $e )
			{
				continue;
			}

			/* Now we will reset the rebuild flag we previously set since it rebuilt and saved successfully */
			unset( Store::i()->currentAttachmentRebuild );
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
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_attachments'), 'complete' => ( $data['realCount'] * $data['indexed'] ) > 0 ? round( 100 / $data['realCount'] * $data['indexed'], 2 ) : 100 );
	}	
}